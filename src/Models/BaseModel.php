<?php
declare(strict_types=1);

namespace SGPA\Models;

use SGPA\Core\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function create(array $data): string
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    protected function update(string $id, array $data): bool
    {
        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    protected function delete(string $id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    protected function find(string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    protected function findBy(array $criteria, array $orderBy = []): array
    {
        $where = [];
        $values = [];
        
        foreach ($criteria as $field => $value) {
            $where[] = "{$field} = ?";
            $values[] = $value;
        }
        
        $whereClause = implode(' AND ', $where);
        $orderByClause = '';
        
        if (!empty($orderBy)) {
            $orderByStr = [];
            foreach ($orderBy as $field => $direction) {
                $orderByStr[] = "{$field} {$direction}";
            }
            $orderByClause = ' ORDER BY ' . implode(', ', $orderByStr);
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} AND deleted_at IS NULL{$orderByClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
