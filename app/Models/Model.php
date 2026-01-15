<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    public function all(): array
    {
        $sql = 'SELECT * FROM ' . $this->table;
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn(string $col): string => ':' . $col, $columns);

        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = array_keys($data);
        $assignments = array_map(fn(string $col): string => $col . ' = :' . $col, $columns);

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(',', $assignments) . ' WHERE ' . $this->primaryKey . ' = :id';
        $stmt = $this->db->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

