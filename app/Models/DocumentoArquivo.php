<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class DocumentoArquivo extends Model
{
    protected string $table = 'documentos_arquivos';

    public function findByDocumento(int $documentoId): array
    {
        $sql = 'SELECT * FROM documentos_arquivos WHERE documento_id = :documento_id ORDER BY versao DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAtual(int $documentoId): ?array
    {
        $sql = 'SELECT * FROM documentos_arquivos WHERE documento_id = :documento_id ORDER BY versao DESC LIMIT 1';
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

