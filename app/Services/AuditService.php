<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\LogAuditoria;

class AuditService
{
    private LogAuditoria $log;

    public function __construct()
    {
        $this->log = new LogAuditoria();
    }

    public function log(string $acao, string $entidade, ?int $entidadeId = null): void
    {
        $this->logAuditoria($acao, $entidade, $entidadeId, null);
    }

    public function logOperacional(string $acao, string $entidade, ?int $entidadeId = null, ?string $dados = null): void
    {
        $this->write('logs_operacionais', $acao, $entidade, $entidadeId, $dados, false);
    }

    public function logAuditoria(string $acao, string $entidade, ?int $entidadeId = null, ?string $dados = null): void
    {
        $this->write('logs_auditoria', $acao, $entidade, $entidadeId, $dados, true);
    }

    private function write(string $tabela, string $acao, string $entidade, ?int $entidadeId, ?string $dados, bool $comHash): void
    {
        $user = Auth::user();
        $usuarioId = $user ? (int) $user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $agora = date('Y-m-d H:i:s');

        $tenantId = $user && array_key_exists('tenant_id', $user) ? (int) $user['tenant_id'] : null;

        $db = Database::connection();

        $hashAnterior = null;
        if ($comHash) {
            $stmt = $db->prepare(
                "SELECT hash_evento FROM logs_auditoria WHERE tenant_id <=> :tenant_id ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([':tenant_id' => $tenantId]);
            $hashAnterior = $stmt->fetchColumn() ?: null;
        }

        $dadosString = $dados ?? '';

        $baseJson = json_encode(
            [
                'tenant_id' => $tenantId,
                'usuario_id' => $usuarioId,
                'acao' => $acao,
                'entidade' => $entidade,
                'entidade_id' => $entidadeId,
                'dados' => $dadosString,
                'ip' => $ip,
                'created_at' => $agora,
                'hash_anterior' => $hashAnterior,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $hashEvento = $comHash ? hash('sha256', $baseJson) : null;

        $data = [
            'tenant_id' => $tenantId,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'ip' => $ip,
            'dados' => $dadosString,
            'created_at' => $agora,
            'hash_anterior' => $hashAnterior,
            'hash_evento' => $hashEvento,
        ];

        if ($tabela === 'logs_auditoria') {
            $this->log->insert($data);
            return;
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $col): string => ':' . $col, $columns);

        $sql = 'INSERT INTO ' . $tabela . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmtInsert = $db->prepare($sql);
        foreach ($data as $column => $value) {
            $stmtInsert->bindValue(':' . $column, $value);
        }
        $stmtInsert->execute();
    }

    public function getDocumentHistory(int $documentoId): array
    {
        $db = \App\Core\Database::connection();

        // Logs gerais
        try {
            $stmt = $db->prepare("
                SELECT l.*, u.nome as usuario_nome 
                FROM logs_auditoria l 
                LEFT JOIN usuarios u ON l.usuario_id = u.id 
                WHERE l.entidade = 'documentos' AND l.entidade_id = ?
                ORDER BY l.created_at ASC
            ");
            $stmt->execute([$documentoId]);
            $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }

            $stmt = $db->prepare("
                SELECT l.* 
                FROM logs_auditoria l 
                WHERE l.entidade = 'documentos' AND l.entidade_id = ?
                ORDER BY l.created_at ASC
            ");
            $stmt->execute([$documentoId]);
            $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Versões
        $stmtV = $db->prepare("
            SELECT da.* 
            FROM documentos_arquivos da 
            WHERE da.documento_id = ? 
            ORDER BY da.versao ASC
        ");
        $stmtV->execute([$documentoId]);
        $versoes = $stmtV->fetchAll(\PDO::FETCH_ASSOC);

        // Assinaturas
        try {
            $stmtA = $db->prepare("
                SELECT 
                    a.id,
                    a.documento_id,
                    a.usuario_id,
                    a.ordem,
                    a.status,
                    a.assinatura_imagem,
                    a.ip,
                    a.assinado_em AS created_at,
                    NULL AS hash_assinatura,
                    u.nome AS usuario_nome
                FROM assinaturas a
                LEFT JOIN users u ON a.usuario_id = u.id
                WHERE a.documento_id = ?
                ORDER BY a.assinado_em ASC
            ");
            $stmtA->execute([$documentoId]);
            $assinaturas = $stmtA->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
            $assinaturas = [];
        }

        return [
            'logs' => $logs,
            'versoes' => $versoes,
            'assinaturas' => $assinaturas
        ];
    }
}
