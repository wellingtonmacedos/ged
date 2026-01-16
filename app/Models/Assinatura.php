<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Assinatura extends Model
{
    protected string $table = 'assinaturas';

    public function pendentesPorUsuario(int $usuarioId): array
    {
        $sql = 'SELECT a.*, d.titulo FROM assinaturas a INNER JOIN documentos d ON d.id = a.documento_id WHERE a.usuario_id = :usuario_id AND a.status = "PENDENTE" ORDER BY a.ordem ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function pendentesPorDocumento(int $documentoId): array
    {
        $sql = 'SELECT * FROM assinaturas WHERE documento_id = :documento_id AND status = "PENDENTE" ORDER BY ordem ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento_id', $documentoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByDocumento(int $documentoId): array
    {
        $sql = 'SELECT a.*, u.nome AS usuario_nome FROM assinaturas a INNER JOIN users u ON u.id = a.usuario_id WHERE a.documento_id = :documento_id ORDER BY a.ordem ASC';
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
