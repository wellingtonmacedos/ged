<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class DocumentoMetadata extends Model
{
    protected string $table = 'documentos_metadados';

    public function findByDocumento(int $documentoId): array
    {
        $sql = 'SELECT * FROM documentos_metadados WHERE documento_id = :documento_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

