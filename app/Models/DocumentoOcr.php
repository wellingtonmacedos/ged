<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class DocumentoOcr extends Model
{
    protected string $table = 'documentos_ocr';

    public function findByDocumentoArquivo(int $documentoId, int $arquivoId): ?array
    {
        $sql = 'SELECT * FROM documentos_ocr WHERE documento_id = :documento_id AND documento_arquivo_id = :arquivo_id ORDER BY id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->bindValue(':arquivo_id', $arquivoId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $row;
    }
}

