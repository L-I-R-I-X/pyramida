<?php
/**
 * Обработчик сессий с хранением в БД
 * Совместим с PHP 8.0+
 */
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $tableName = 'sessions';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createSessionsTable();
    }
    
    private function createSessionsTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function read($id) {
        $stmt = $this->pdo->prepare("SELECT data FROM {$this->tableName} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ? $result['data'] : '';
    }
    
    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        // ✅ Используем РАЗНЫЕ плейсхолдеры для INSERT и UPDATE
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tableName} (id, data) 
            VALUES (:sid, :sdata)
            ON DUPLICATE KEY UPDATE data = :udata, updated_at = NOW()
        ");
        return $stmt->execute([
            'sid' => $id,
            'sdata' => $data,
            'udata' => $data
        ]);
    }
    
    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->tableName} 
            WHERE updated_at < DATE_SUB(NOW(), INTERVAL :lifetime SECOND)
        ");
        return $stmt->execute(['lifetime' => $maxlifetime]);
    }
}
?>