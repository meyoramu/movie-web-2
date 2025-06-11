<?php

namespace CineVerse\Core\Database;

/**
 * Query Builder
 * 
 * Provides fluent interface for building database queries
 */
class QueryBuilder
{
    private DatabaseManager $db;
    private string $table;
    private array $select = ['*'];
    private array $joins = [];
    private array $where = [];
    private array $orderBy = [];
    private array $groupBy = [];
    private array $having = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $bindings = [];

    public function __construct(DatabaseManager $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Set SELECT columns
     */
    public function select(array $columns = ['*']): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Add WHERE condition
     */
    public function where(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = $this->getPlaceholder($column);
        $this->where[] = "{$column} {$operator} {$placeholder}";
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    /**
     * Add WHERE IN condition
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = ":{$column}_{$i}";
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $this->where[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    /**
     * Add WHERE LIKE condition
     */
    public function whereLike(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * Add WHERE NULL condition
     */
    public function whereNull(string $column): self
    {
        $this->where[] = "{$column} IS NULL";
        return $this;
    }

    /**
     * Add WHERE NOT NULL condition
     */
    public function whereNotNull(string $column): self
    {
        $this->where[] = "{$column} IS NOT NULL";
        return $this;
    }

    /**
     * Add JOIN
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Add GROUP BY
     */
    public function groupBy(string $column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * Add HAVING condition
     */
    public function having(string $column, string $operator, $value): self
    {
        $placeholder = $this->getPlaceholder($column);
        $this->having[] = "{$column} {$operator} {$placeholder}";
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * Set LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Paginate results
     */
    public function paginate(int $page, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countQuery = clone $this;
        $total = $countQuery->count();
        
        // Get paginated results
        $results = $this->limit($perPage)->offset($offset)->get();
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Get all results
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->db->fetchAll($sql, $this->bindings);
    }

    /**
     * Get first result
     */
    public function first(): ?array
    {
        $sql = $this->buildSelectQuery();
        return $this->db->fetch($sql, $this->bindings);
    }

    /**
     * Get count
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        
        $sql = $this->buildSelectQuery();
        $result = $this->db->fetch($sql, $this->bindings);
        
        $this->select = $originalSelect;
        
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Check if record exists
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert record
     */
    public function insert(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update records
     */
    public function update(array $data): int
    {
        if (empty($this->where)) {
            throw new \Exception("Cannot update without WHERE conditions");
        }

        $setParts = [];
        $updateBindings = [];
        
        foreach ($data as $column => $value) {
            $placeholder = ":set_{$column}";
            $setParts[] = "{$column} = {$placeholder}";
            $updateBindings[$placeholder] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $allBindings = array_merge($updateBindings, $this->bindings);
        
        return $this->db->query($sql, $allBindings)->rowCount();
    }

    /**
     * Delete records
     */
    public function delete(): int
    {
        if (empty($this->where)) {
            throw new \Exception("Cannot delete without WHERE conditions");
        }

        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        return $this->db->query($sql, $this->bindings)->rowCount();
    }

    /**
     * Build SELECT query
     */
    private function buildSelectQuery(): string
    {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Generate unique placeholder for binding
     */
    private function getPlaceholder(string $column): string
    {
        $base = ":{$column}";
        $counter = 0;
        
        while (isset($this->bindings[$base . ($counter ?: '')])) {
            $counter++;
        }
        
        return $base . ($counter ?: '');
    }
}
