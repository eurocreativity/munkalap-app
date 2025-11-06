<?php
/**
 * Munkalap App - Beállítások osztály
 */
require_once __DIR__ . '/Database.php';

class Settings {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Beállítás lekérése kulcs alapján
     */
    public function get($key, $default = null) {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $result = $this->db->fetchOne($sql, [$key]);
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Beállítás beállítása
     */
    public function set($key, $value) {
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP";
        return $this->db->execute($sql, [$key, $value, $value]);
    }
    
    /**
     * Összes beállítás lekérése
     */
    public function getAll() {
        $sql = "SELECT setting_key, setting_value FROM settings ORDER BY setting_key";
        $results = $this->db->fetchAll($sql);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * Több beállítás egyszerre mentése
     */
    public function setMultiple($settings) {
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP";
        
        $this->db->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                $this->db->execute($sql, [$key, $value]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
?>


