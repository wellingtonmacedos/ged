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

    public function search(?string $titulo, ?string $status, ?int $departamentoId, ?string $inicio, ?string $fim, array $metadados = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT DISTINCT d.* FROM documentos d INNER JOIN pastas p ON p.id = d.pasta_id';
        $params = [];
        $where = ['1=1'];

        if ($titulo !== null && $titulo !== '') {
            $where[] = 'd.titulo LIKE :titulo';
            $params[':titulo'] = '%' . $titulo . '%';
        }

        if ($status !== null && $status !== '') {
            $where[] = 'd.status = :status';
            $params[':status'] = $status;
        }

        if ($departamentoId !== null) {
            $where[] = 'p.departamento_id = :departamento_id';
            $params[':departamento_id'] = $departamentoId;
        }

        if ($inicio !== null && $inicio !== '') {
            $where[] = 'd.created_at >= :inicio';
            $params[':inicio'] = $inicio;
        }

        if ($fim !== null && $fim !== '') {
            $where[] = 'd.created_at <= :fim';
            $params[':fim'] = $fim;
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

