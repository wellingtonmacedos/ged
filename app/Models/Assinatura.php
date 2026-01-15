<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Assinatura extends Model
{
    protected string $table = 'assinaturas';

    public function pendentesPorDocumento(int $documentoId): array
    {
        $sql = 'SELECT * FROM assinaturas WHERE documento_id = :documento_id AND status = "PENDENTE" ORDER BY ordem ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function proximaPendente(int $documentoId): ?array
    {
        $sql = 'SELECT * FROM assinaturas WHERE documento_id = :documento_id AND status = "PENDENTE" ORDER BY ordem ASC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $row;
    }
}

