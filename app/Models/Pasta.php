<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Pasta extends Model
{
    protected string $table = 'pastas';

    public function findByDepartamentoAndParent(int $departamentoId, ?int $parentId): array
    {
        if ($parentId === null) {
            $sql = 'SELECT * FROM pastas WHERE departamento_id = :departamento_id AND parent_id IS NULL ORDER BY nome';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT * FROM pastas WHERE departamento_id = :departamento_id AND parent_id = :parent_id ORDER BY nome';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBreadcrumb(int $pastaId): array
    {
        $breadcrumb = [];
        $currentId = $pastaId;

        // Limite de segurança para evitar loop infinito
        $limit = 50; 
        
        while ($currentId && $limit-- > 0) {
            $pasta = $this->find($currentId);
            if (!$pasta) {
                break;
            }
            array_unshift($breadcrumb, $pasta);
            $currentId = $pasta['parent_id'] ? (int) $pasta['parent_id'] : null;
        }

        return $breadcrumb;
    }
}

