<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class AuditIntegrityService
{
    public function verifyTenantChain(int $tenantId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT id, tenant_id, usuario_id, acao, entidade, entidade_id, dados, ip, created_at, hash_anterior, hash_evento
             FROM logs_auditoria
             WHERE tenant_id = :tenant_id
             ORDER BY id ASC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->verifyChain($logs);
    }

    public function verifyDocumentoChain(int $documentoId): array
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT id, tenant_id, usuario_id, acao, entidade, entidade_id, dados, ip, created_at, hash_anterior, hash_evento
             FROM logs_auditoria
             WHERE entidade = 'documentos' AND entidade_id = :documento_id
             ORDER BY id ASC"
        );
        $stmt->execute([':documento_id' => $documentoId]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->verifyChain($logs);
    }

    private function verifyChain(array $logs): array
    {
        $total = count($logs);
        $status = 'OK';
        $primeiroInvalido = null;
        $hashInicial = null;
        $hashFinal = null;
        $agora = date('Y-m-d H:i:s');

        $hashAnterior = null;
        $ultimoCreated = null;

        foreach ($logs as $index => $log) {
            $baseJson = json_encode(
                [
                    'tenant_id' => isset($log['tenant_id']) ? (int) $log['tenant_id'] : null,
                    'usuario_id' => isset($log['usuario_id']) ? (int) $log['usuario_id'] : null,
                    'acao' => $log['acao'],
                    'entidade' => $log['entidade'],
                    'entidade_id' => isset($log['entidade_id']) ? (int) $log['entidade_id'] : null,
                    'dados' => $log['dados'] ?? '',
                    'ip' => $log['ip'],
                    'created_at' => $log['created_at'],
                    'hash_anterior' => $hashAnterior,
                ],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $recalculado = hash('sha256', $baseJson);

            $hashAnteriorBanco = $log['hash_anterior'] ?: null;
            $hashEventoBanco = $log['hash_evento'] ?: null;

            $ordemDataOk = true;
            if ($ultimoCreated !== null && $log['created_at'] < $ultimoCreated) {
                $ordemDataOk = false;
            }

            if ($hashAnteriorBanco !== $hashAnterior ||
                $hashEventoBanco !== $recalculado ||
                !$ordemDataOk) {
                $status = 'CORROMPIDO';
                $primeiroInvalido = [
                    'id' => $log['id'],
                    'esperado_hash_anterior' => $hashAnterior,
                    'encontrado_hash_anterior' => $hashAnteriorBanco,
                    'esperado_hash_evento' => $recalculado,
                    'encontrado_hash_evento' => $hashEventoBanco,
                    'ordem_cronologica_valida' => $ordemDataOk,
                ];
                break;
            }

            if ($index === 0) {
                $hashInicial = $recalculado;
            }

            $hashAnterior = $recalculado;
            $hashFinal = $recalculado;
            $ultimoCreated = $log['created_at'];
        }

        return [
            'status' => $status,
            'primeiro_evento_invalido' => $primeiroInvalido,
            'total_eventos_verificados' => $total,
            'hash_inicial' => $hashInicial,
            'hash_final' => $hashFinal,
            'data_verificacao' => $agora,
        ];
    }
}

