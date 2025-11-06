<?php
/**
 * Munkalap App - Anyag osztály
 */
require_once __DIR__ . '/Database.php';

class Material {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Anyagok lekérése munkalap ID alapján
     */
    public function getByWorksheetId($worksheetId) {
        $sql = "SELECT * FROM materials WHERE worksheet_id = ? ORDER BY id ASC";
        return $this->db->fetchAll($sql, [$worksheetId]);
    }
    
    /**
     * Anyag lekérése ID alapján
     */
    public function getById($id) {
        $sql = "SELECT * FROM materials WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Új anyag létrehozása
     */
    public function create($data) {
        // Bruttó ár számítása
        $netPrice = floatval($data['net_price'] ?? 0);
        $vatRate = floatval($data['vat_rate'] ?? 27);
        $vatAmount = $netPrice * ($vatRate / 100);
        $grossPrice = $netPrice + $vatAmount;
        
        $sql = "INSERT INTO materials (
                    worksheet_id, product_name, quantity, unit, 
                    net_price, vat_rate, vat_amount, gross_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['worksheet_id'] ?? null,
            $data['product_name'] ?? '',
            $data['quantity'] ?? 0,
            $data['unit'] ?? 'db',
            $netPrice,
            $vatRate,
            $vatAmount,
            $grossPrice
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Anyag frissítése
     */
    public function update($id, $data) {
        // Bruttó ár számítása
        $netPrice = floatval($data['net_price'] ?? 0);
        $vatRate = floatval($data['vat_rate'] ?? 27);
        $vatAmount = $netPrice * ($vatRate / 100);
        $grossPrice = $netPrice + $vatAmount;
        
        $sql = "UPDATE materials 
                SET product_name = ?, quantity = ?, unit = ?, 
                    net_price = ?, vat_rate = ?, vat_amount = ?, gross_price = ?
                WHERE id = ?";
        
        $params = [
            $data['product_name'] ?? '',
            $data['quantity'] ?? 0,
            $data['unit'] ?? 'db',
            $netPrice,
            $vatRate,
            $vatAmount,
            $grossPrice,
            $id
        ];
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Anyag törlése
     */
    public function delete($id) {
        $sql = "DELETE FROM materials WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Összes anyag törlése egy munkalaphoz
     */
    public function deleteByWorksheetId($worksheetId) {
        $sql = "DELETE FROM materials WHERE worksheet_id = ?";
        return $this->db->execute($sql, [$worksheetId]);
    }
    
    /**
     * Bruttó ár számítása
     */
    public static function calculateGrossPrice($netPrice, $vatRate = 27) {
        $netPrice = floatval($netPrice);
        $vatRate = floatval($vatRate);
        $vatAmount = $netPrice * ($vatRate / 100);
        return $netPrice + $vatAmount;
    }
    
    /**
     * ÁFA összeg számítása
     */
    public static function calculateVatAmount($netPrice, $vatRate = 27) {
        $netPrice = floatval($netPrice);
        $vatRate = floatval($vatRate);
        return $netPrice * ($vatRate / 100);
    }
}
?>


