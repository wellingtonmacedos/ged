<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

class DashboardService
{
    public function getKpis(array $filtros = []): array
    {
        $escopo = $this->resolverEscopoUsuario();

        return [
            'por_status' => $this->totalDocumentosPorStatus($escopo, $filtros),
            'criados_periodo' => $this->documentosCriadosNoPeriodo($escopo, $filtros),
            'pendentes_assinatura' => $this->documentosPendentesAssinatura($escopo, $filtros),
            'assinados_periodo' => $this->documentosAssinadosPorPeriodo($escopo, $filtros),
            'por_departamento' => $this->volumePorDepartamento($escopo, $filtros),
            'ocr_sucesso_falha' => $this->ocrExecutadosVsFalhas($escopo, $filtros),
        ];
    }

    private function resolverEscopoUsuario(): array
    {
        $user = Auth::user();
        $perfil = $user['perfil'] ?? null;

        if ($perfil === 'SUPER_ADMIN') {
            return ['tipo' => 'global'];
        }

        $departamentoId = isset($user['departamento_id']) ? (int) $user['departamento_id'] : null;

        if ($departamentoId) {
            return [
                'tipo' => 'departamento',
                'departamento_id' => $departamentoId,
            ];
        }

        return ['tipo' => 'usuario', 'usuario_id' => (int) ($user['id'] ?? 0)];
    }

    private function aplicarEscopo(string $aliasDocumento, string $aliasPasta, array $escopo, array &$where, array &$params): void
    {
        if ($escopo['tipo'] === 'departamento') {
            $where[] = $aliasPasta . '.departamento_id = :escopo_departamento';
            $params[':escopo_departamento'] = $escopo['departamento_id'];
        }
    }

    private function intervaloDatas(array $filtros): array
    {
        $inicio = $filtros['inicio'] ?? null;
        $fim = $filtros['fim'] ?? null;

        if (!$inicio && !$fim) {
            $inicio = date('Y-m-01');
            $fim = date('Y-m-t');
        }

        return [$inicio, $fim];
    }

    private function totalDocumentosPorStatus(array $escopo, array $filtros): array
    {
        $db = Database::connection();

        $sql = 'SELECT d.status, COUNT(*) as total
                FROM documentos d
                INNER JOIN pastas p ON p.id = d.pasta_id';
        $where = ['1=1'];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'd.created_at >= :status_inicio';
            $params[':status_inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'd.created_at <= :status_fim';
            $params[':status_fim'] = $fim . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where) . ' GROUP BY d.status';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($result as $row) {
            $out[$row['status'] ?? ''] = (int) $row['total'];
        }

        return $out;
    }

    private function documentosCriadosNoPeriodo(array $escopo, array $filtros): int
    {
        $db = Database::connection();

        $sql = 'SELECT COUNT(*) as total
                FROM documentos d
                INNER JOIN pastas p ON p.id = d.pasta_id';
        $where = ['1=1'];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'd.created_at >= :inicio';
            $params[':inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'd.created_at <= :fim';
            $params[':fim'] = $fim . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    private function documentosPendentesAssinatura(array $escopo, array $filtros): int
    {
        $db = Database::connection();

        $sql = 'SELECT COUNT(DISTINCT d.id) as total
                FROM documentos d
                INNER JOIN pastas p ON p.id = d.pasta_id
                INNER JOIN assinaturas a ON a.documento_id = d.id
                WHERE a.status = "PENDENTE"';

        $where = [];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'd.created_at >= :pend_inicio';
            $params[':pend_inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'd.created_at <= :pend_fim';
            $params[':pend_fim'] = $fim . ' 23:59:59';
        }

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    private function documentosAssinadosPorPeriodo(array $escopo, array $filtros): array
    {
        $db = Database::connection();

        $sql = 'SELECT DATE_FORMAT(a.assinado_em, "%Y-%m") as mes, COUNT(*) as total
                FROM assinaturas a
                INNER JOIN documentos d ON d.id = a.documento_id
                INNER JOIN pastas p ON p.id = d.pasta_id
                WHERE a.status = "ASSINADO"';

        $where = [];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'a.assinado_em >= :assin_inicio';
            $params[':assin_inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'a.assinado_em <= :assin_fim';
            $params[':assin_fim'] = $fim . ' 23:59:59';
        }

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY mes ORDER BY mes ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['mes']] = (int) $row['total'];
        }

        return $out;
    }

    private function volumePorDepartamento(array $escopo, array $filtros): array
    {
        $db = Database::connection();

        $sql = 'SELECT dep.nome as departamento, COUNT(*) as total
                FROM documentos d
                INNER JOIN pastas p ON p.id = d.pasta_id
                INNER JOIN departamentos dep ON dep.id = p.departamento_id';

        $where = ['1=1'];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'd.created_at >= :dep_inicio';
            $params[':dep_inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'd.created_at <= :dep_fim';
            $params[':dep_fim'] = $fim . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where) . ' GROUP BY dep.nome ORDER BY total DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['departamento']] = (int) $row['total'];
        }

        return $out;
    }

    private function ocrExecutadosVsFalhas(array $escopo, array $filtros): array
    {
        $db = Database::connection();

        $sql = 'SELECT 
                    SUM(CASE WHEN ocr.id IS NOT NULL THEN 1 ELSE 0 END) as sucesso,
                    SUM(CASE WHEN ocr.id IS NULL THEN 0 ELSE 0 END) as falha
                FROM documentos d
                INNER JOIN pastas p ON p.id = d.pasta_id
                LEFT JOIN documentos_ocr ocr ON ocr.documento_id = d.id';

        $where = ['1=1'];
        $params = [];

        $this->aplicarEscopo('d', 'p', $escopo, $where, $params);

        [$inicio, $fim] = $this->intervaloDatas($filtros);
        if ($inicio) {
            $where[] = 'd.created_at >= :ocr_inicio';
            $params[':ocr_inicio'] = $inicio . ' 00:00:00';
        }
        if ($fim) {
            $where[] = 'd.created_at <= :ocr_fim';
            $params[':ocr_fim'] = $fim . ' 23:59:59';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $sucesso = (int) ($row['sucesso'] ?? 0);
        $falha = (int) ($row['falha'] ?? 0);

        return [
            'sucesso' => $sucesso,
            'falha' => $falha,
        ];
    }
}

