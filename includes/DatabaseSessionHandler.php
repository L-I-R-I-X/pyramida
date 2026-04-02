<?php
/**
 * Database Session Handler
 * Хранит сессии в базе данных MySQL
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $pdo;
    private $tableName = 'sessions';
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }
    
    /**
     * Создаём таблицу сессий если она не существует
     */
    private function ensureTableExists(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT,
                expires INT(11) UNSIGNED NOT NULL,
                INDEX idx_expires (expires)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log('Failed to create sessions table: ' . $e->getMessage());
        }
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM {$this->tableName} WHERE id = :id AND expires > :now");
            $stmt->execute([
                ':id' => $id,
                ':now' => time()
            ]);
            $result = $stmt->fetchColumn();
            return $result ? (string)$result : '';
        } catch (PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            $expires = time() + ini_get('session.gc_maxlifetime');
            
            // Используем INSERT ... ON DUPLICATE KEY UPDATE для атомарности
            $stmt = $this->pdo->prepare("INSERT INTO {$this->tableName} (id, data, expires) 
                VALUES (:id, :data, :expires)
                ON DUPLICATE KEY UPDATE data = :data, expires = :expires");
            
            return $stmt->execute([
                ':id' => $id,
                ':data' => $data,
                ':expires' => $expires
            ]);
        } catch (PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy($id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }

    public function gc($maxlifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE expires < :now");
            $stmt->execute([':now' => time()]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Session GC error: " . $e->getMessage());
            return false;
        }
    }
}
