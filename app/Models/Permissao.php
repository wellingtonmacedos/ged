<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Permissao extends Model
{
    protected string $table = 'permissoes';

    public function findByUsuarioAndPasta(int $usuarioId, int $pastaId): ?array
    {
        $sql = 'SELECT * FROM permissoes WHERE usuario_id = :usuario_id AND pasta_id = :pasta_id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':pasta_id', $pastaId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $row;
    }
}

