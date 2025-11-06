<?php
/**
 * Munkalap App - Adatbázis osztály
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Privát konstruktor - Singleton minta
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Adatbázis kapcsolat hiba: " . $e->getMessage());
        }
    }
    
    /**
     * Singleton instance lekérése
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * PDO objektum lekérése
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Query futtatása (SELECT)
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query hiba: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Egy sor lekérése
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Több sor lekérése
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * INSERT/UPDATE/DELETE futtatása
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute hiba: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Utolsó beszúrt ID lekérése
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Tranzakció kezdése
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Tranzakció commit
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Tranzakció rollback
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
?>


