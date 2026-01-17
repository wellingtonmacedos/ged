<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Documento;
use App\Models\Assinatura;
use App\Models\DocumentoOcr;
use App\Models\LogAuditoria;
use PDO;

class RelatorioService
{
    private PDO $db;
    private Documento $documento;
    private Assinatura $assinatura;
    private DocumentoOcr $documentoOcr;
    private LogAuditoria $logAuditoria;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->documento = new Documento();
        $this->assinatura = new Assinatura();
        $this->documentoOcr = new DocumentoOcr();
        $this->logAuditoria = new LogAuditoria();
    }

    public function documentos(array $filtros): array
    {
        $sql = 'SELECT d.*, p.nome AS pasta_nome, dept.nome AS departamento_nome FROM documentos d INNER JOIN pastas p ON p.id = d.pasta_id INNER JOIN departamentos dept ON dept.id = p.departamento_id';
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['departamento_id'])) {
            $where[] = 'dept.id = :departamento_id';
            $params[':departamento_id'] = (int) $filtros['departamento_id'];
        }

        if (!empty($filtros['pasta_id'])) {
            $where[] = 'd.pasta_id = :pasta_id';
            $params[':pasta_id'] = (int) $filtros['pasta_id'];
        }

        if (!empty($filtros['status'])) {
            $where[] = 'd.status = :status';
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['inicio'])) {
            $where[] = 'd.created_at >= :inicio';
            $params[':inicio'] = $filtros['inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['fim'])) {
            $where[] = 'd.created_at <= :fim';
            $params[':fim'] = $filtros['fim'] . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY d.created_at DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($rows);
        $porStatus = [
            'EM_EDICAO' => 0,
            'PENDENTE_ASSINATURA' => 0,
            'ASSINADO' => 0,
        ];

        $ultimaData = null;

        foreach ($rows as $row) {
            $status = $row['status'] ?? null;
            if (isset($porStatus[$status])) {
                $porStatus[$status]++;
            }
            if (!empty($row['created_at'])) {
                if ($ultimaData === null || $row['created_at'] > $ultimaData) {
                    $ultimaData = $row['created_at'];
                }
            }
        }

        return [
            'total' => $total,
            'por_status' => $porStatus,
            'ultima_movimentacao' => $ultimaData,
            'linhas' => $rows,
        ];
    }

    public function assinaturas(array $filtros): array
    {
        $sql = 'SELECT a.*, d.titulo, u.nome AS usuario_nome, dept.nome AS departamento_nome FROM assinaturas a INNER JOIN documentos d ON d.id = a.documento_id INNER JOIN users u ON u.id = a.usuario_id INNER JOIN pastas p ON p.id = d.pasta_id INNER JOIN departamentos dept ON dept.id = p.departamento_id';
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'u.id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        if (!empty($filtros['departamento_id'])) {
            $where[] = 'dept.id = :departamento_id';
            $params[':departamento_id'] = (int) $filtros['departamento_id'];
        }

        if (!empty($filtros['inicio'])) {
            $where[] = 'a.assinado_em >= :inicio';
            $params[':inicio'] = $filtros['inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['fim'])) {
            $where[] = 'a.assinado_em <= :fim';
            $params[':fim'] = $filtros['fim'] . ' 23:59:59';
        }

        $where[] = 'a.status = "ASSINADO"';

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY a.assinado_em DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($rows);
        $porUsuario = [];

        foreach ($rows as $row) {
            $uid = (int) $row['usuario_id'];
            if (!isset($porUsuario[$uid])) {
                $porUsuario[$uid] = [
                    'usuario_id' => $uid,
                    'usuario_nome' => $row['usuario_nome'] ?? '',
                    'total' => 0,
                ];
            }
            $porUsuario[$uid]['total']++;
        }

        return [
            'total' => $total,
            'por_usuario' => array_values($porUsuario),
            'linhas' => $rows,
        ];
    }

    public function auditoria(array $filtros): array
    {
        $sql = 'SELECT l.* FROM logs_auditoria l';
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'l.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        if (!empty($filtros['acao'])) {
            $where[] = 'l.acao = :acao';
            $params[':acao'] = $filtros['acao'];
        }

        if (!empty($filtros['entidade'])) {
            $where[] = 'l.entidade = :entidade';
            $params[':entidade'] = $filtros['entidade'];
        }

        if (!empty($filtros['inicio'])) {
            $where[] = 'l.created_at >= :inicio';
            $params[':inicio'] = $filtros['inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['fim'])) {
            $where[] = 'l.created_at <= :fim';
            $params[':fim'] = $filtros['fim'] . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY l.created_at DESC LIMIT 500';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => count($rows),
            'linhas' => $rows,
        ];
    }

    public function ocr(array $filtros): array
    {
        $sql = 'SELECT ocr.*, d.titulo, dept.nome AS departamento_nome FROM documentos_ocr ocr INNER JOIN documentos d ON d.id = ocr.documento_id INNER JOIN documentos_arquivos da ON da.id = ocr.documento_arquivo_id INNER JOIN pastas p ON p.id = d.pasta_id INNER JOIN departamentos dept ON dept.id = p.departamento_id';
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['departamento_id'])) {
            $where[] = 'dept.id = :departamento_id';
            $params[':departamento_id'] = (int) $filtros['departamento_id'];
        }

        if (!empty($filtros['inicio'])) {
            $where[] = 'ocr.created_at >= :inicio';
            $params[':inicio'] = $filtros['inicio'] . ' 00:00:00';
        }

        if (!empty($filtros['fim'])) {
            $where[] = 'ocr.created_at <= :fim';
            $params[':fim'] = $filtros['fim'] . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY ocr.created_at DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($rows);
        $paginas = 0;
        foreach ($rows as $row) {
            $paginas += (int) ($row['paginas_processadas'] ?? 0);
        }

        return [
            'total' => $total,
            'paginas' => $paginas,
            'linhas' => $rows,
        ];
    }
}

