<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Documento extends Model
{
    protected string $table = 'documentos';

    public function findByPasta(int $pastaId): array
    {
        $sql = 'SELECT * FROM documentos WHERE pasta_id = :pasta_id ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pasta_id', $pastaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByPastaIds(array $pastaIds): array
    {
        if (empty($pastaIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach (array_values($pastaIds) as $index => $id) {
            $key = ':id' . $index;
            $placeholders[] = $key;
            $params[$key] = (int) $id;
        }

        $sql = 'SELECT pasta_id, COUNT(*) AS total FROM documentos WHERE pasta_id IN (' . implode(',', $placeholders) . ') GROUP BY pasta_id';
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['pasta_id']] = (int) $row['total'];
        }

        return $result;
    }

    public function searchInFolder(int $pastaId, string $termo): array
    {
        // Busca por título ou conteúdo OCR (se existir) dentro da pasta
        $sql = 'SELECT DISTINCT d.* FROM documentos d 
                LEFT JOIN documentos_ocr ocr ON ocr.documento_id = d.id 
                WHERE d.pasta_id = :pasta_id 
                AND (d.titulo LIKE :termo OR ocr.texto_extraido LIKE :termo) 
                ORDER BY d.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pasta_id', $pastaId, PDO::PARAM_INT);
        $stmt->bindValue(':termo', '%' . $termo . '%');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(?string $titulo, ?string $status, ?int $departamentoId, ?int $pastaId, ?string $inicio, ?string $fim, ?int $ano, ?string $mes, array $metadados = [], int $limit = 20, int $offset = 0, bool $usarOcr = false): array
    {
        $sql = 'SELECT DISTINCT d.* FROM documentos d INNER JOIN pastas p ON p.id = d.pasta_id';
        $params = [];
        $where = ['1=1'];

        if ($usarOcr) {
            $sql .= ' LEFT JOIN documentos_ocr ocr ON ocr.documento_id = d.id';
        }

        if ($titulo !== null && $titulo !== '') {
            if ($usarOcr) {
                $where[] = '(d.titulo LIKE :titulo OR ocr.texto_extraido LIKE :ocr_texto)';
                $params[':titulo'] = '%' . $titulo . '%';
                $params[':ocr_texto'] = '%' . $titulo . '%';
            } else {
                $where[] = 'd.titulo LIKE :titulo';
                $params[':titulo'] = '%' . $titulo . '%';
            }
        }

        if ($status !== null && $status !== '') {
            $where[] = 'd.status = :status';
            $params[':status'] = $status;
        }

        if ($departamentoId !== null) {
            $where[] = 'p.departamento_id = :departamento_id';
            $params[':departamento_id'] = $departamentoId;
        }

        if ($pastaId !== null) {
            $where[] = 'd.pasta_id = :pasta_id';
            $params[':pasta_id'] = $pastaId;
        }

        if ($inicio !== null && $inicio !== '') {
            $where[] = 'd.created_at >= :inicio';
            $params[':inicio'] = $inicio;
        }

        if ($fim !== null && $fim !== '') {
            $where[] = 'd.created_at <= :fim';
            $params[':fim'] = $fim;
        }

        if ($ano !== null) {
            $where[] = 'YEAR(d.created_at) = :ano';
            $params[':ano'] = $ano;
        }

        if ($mes !== null && $mes !== '') {
            $where[] = 'DATE_FORMAT(d.created_at, "%m") = :mes';
            $params[':mes'] = $mes;
        }

        // Filtro de metadados (AND para cada par chave/valor)
        if (!empty($metadados)) {
            $i = 0;
            foreach ($metadados as $key => $value) {
                if ($value !== '') {
                    $alias = "dm$i";
                    $sql .= " INNER JOIN documentos_metadados $alias ON $alias.documento_id = d.id";
                    $where[] = "$alias.chave = :meta_key_$i AND $alias.valor LIKE :meta_val_$i";
                    $params[":meta_key_$i"] = $key;
                    $params[":meta_val_$i"] = '%' . $value . '%';
                    $i++;
                }
            }
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

}
