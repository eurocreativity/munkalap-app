<?php
/**
 * Munkalap App - Cég osztály
 */
require_once __DIR__ . '/Database.php';

class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Összes cég lekérése
     */
    public function getAll() {
        $sql = "SELECT * FROM companies ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Cég lekérése ID alapján
     */
    public function getById($id) {
        $sql = "SELECT * FROM companies WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Új cég létrehozása
     */
    public function create($data) {
        $sql = "INSERT INTO companies (name, address, tax_number, email, contact_person) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'] ?? '',
            $data['address'] ?? null,
            $data['tax_number'] ?? null,
            $data['email'] ?? null,
            $data['contact_person'] ?? null
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Cég frissítése
     */
    public function update($id, $data) {
        $sql = "UPDATE companies 
                SET name = ?, address = ?, tax_number = ?, email = ?, contact_person = ?
                WHERE id = ?";
        
        $params = [
            $data['name'] ?? '',
            $data['address'] ?? null,
            $data['tax_number'] ?? null,
            $data['email'] ?? null,
            $data['contact_person'] ?? null,
            $id
        ];
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Cég törlése
     */
    public function delete($id) {
        $sql = "DELETE FROM companies WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Ellenőrzi, hogy a céghez tartoznak-e munkalapok
     */
    public function hasWorksheets($id) {
        $sql = "SELECT COUNT(*) as count FROM worksheets WHERE company_id = ?";
        $result = $this->db->fetchOne($sql, [$id]);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Cégek száma
     */
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM companies";
        $result = $this->db->fetchOne($sql);
        return $result['count'] ?? 0;
    }
}
?>


