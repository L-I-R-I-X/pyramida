<?php
/**
 * Обработчик сессий, хранящий данные в базе данных
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $pdo;
    private $tableName = 'sessions';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
            
            // Проверяем, существует ли запись
            $stmt = $this->pdo->prepare("SELECT id FROM {$this->tableName} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            if ($stmt->fetch()) {
                // Обновляем
                $stmt = $this->pdo->prepare("UPDATE {$this->tableName} SET data = :data, expires = :expires WHERE id = :id");
            } else {
                // Вставляем
                $stmt = $this->pdo->prepare("INSERT INTO {$this->tableName} (id, data, expires) VALUES (:id, :data, :expires)");
            }
            
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
