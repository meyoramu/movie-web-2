<?php

namespace CineVerse\Core\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Database Manager
 * 
 * Handles database connections and provides query builder functionality
 */
class DatabaseManager
{
    private array $config;
    private ?PDO $connection = null;
    private array $connections = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get database connection
     */
    public function getConnection(string $name = null): PDO
    {
        $name = $name ?? $this->config['default'];
        
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }
        
        return $this->connections[$name];
    }

    /**
     * Create database connection
     */
    private function createConnection(string $name): PDO
    {
        $config = $this->config['connections'][$name] ?? null;
        
        if (!$config) {
            throw new Exception("Database connection '{$name}' not configured");
        }

        try {
            $dsn = $this->buildDsn($config);
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Build DSN string
     */
    private function buildDsn(array $config): string
    {
        $driver = $config['driver'];
        
        return match($driver) {
            'mysql' => "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
            'pgsql' => "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            default => throw new Exception("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Execute a query
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all results
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single result
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Execute insert and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * Execute update
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :where_{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        
        // Prefix where parameters to avoid conflicts
        $params = $data;
        foreach ($where as $key => $value) {
            $params["where_{$key}"] = $value;
        }
        
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Execute delete
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        return $this->query($sql, $where)->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Execute transaction with callback
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get query builder
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table): bool
    {
        $driver = $this->config['connections'][$this->config['default']]['driver'];
        
        if ($driver === 'mysql') {
            $sql = "SHOW TABLES LIKE :table";
        } else {
            $sql = "SELECT table_name FROM information_schema.tables WHERE table_name = :table";
        }
        
        $result = $this->fetch($sql, ['table' => $table]);
        return !empty($result);
    }

    /**
     * Run migrations
     */
    public function migrate(): void
    {
        $migrationsPath = $this->config['migrations']['path'];
        $migrationsTable = $this->config['migrations']['table'];
        
        // Create migrations table if it doesn't exist
        if (!$this->tableExists($migrationsTable)) {
            $this->createMigrationsTable($migrationsTable);
        }
        
        // Get executed migrations
        $executed = $this->fetchAll("SELECT migration FROM {$migrationsTable}");
        $executedMigrations = array_column($executed, 'migration');
        
        // Get migration files
        $migrationFiles = glob($migrationsPath . '/*.sql');
        sort($migrationFiles);
        
        $batch = $this->getNextBatch($migrationsTable);
        
        foreach ($migrationFiles as $file) {
            $migration = basename($file, '.sql');
            
            if (!in_array($migration, $executedMigrations)) {
                echo "Running migration: {$migration}\n";
                
                $sql = file_get_contents($file);
                $this->getConnection()->exec($sql);
                
                $this->insert($migrationsTable, [
                    'migration' => $migration,
                    'batch' => $batch
                ]);
                
                echo "Migration completed: {$migration}\n";
            }
        }
    }

    /**
     * Create migrations table
     */
    private function createMigrationsTable(string $table): void
    {
        $sql = "CREATE TABLE {$table} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->getConnection()->exec($sql);
    }

    /**
     * Get next migration batch number
     */
    private function getNextBatch(string $table): int
    {
        $result = $this->fetch("SELECT MAX(batch) as max_batch FROM {$table}");
        return ($result['max_batch'] ?? 0) + 1;
    }
}
