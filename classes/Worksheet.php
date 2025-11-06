<?php
/**
 * Munkalap App - Munkalap osztály
 */
require_once __DIR__ . '/Database.php';

class Worksheet {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Automatikus munkalap szám generálása (év/sorszám formátum)
     */
    public function generateWorksheetNumber($year = null) {
        if ($year === null) {
            $year = date('Y');
        }
        
        // Keresés az adott év legnagyobb sorszámára
        $sql = "SELECT worksheet_number FROM worksheets 
                WHERE worksheet_number LIKE ? 
                ORDER BY worksheet_number DESC 
                LIMIT 1";
        
        $result = $this->db->fetchOne($sql, [$year . '/%']);
        
        if ($result && preg_match('/^(\d{4})\/(\d+)$/', $result['worksheet_number'], $matches)) {
            $nextNumber = intval($matches[2]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%d/%03d', $year, $nextNumber);
    }
    
    /**
     * Összes munkalap lekérése (szűréssel)
     */
    public function getAll($filters = []) {
        $sql = "SELECT w.*, c.name as company_name 
                FROM worksheets w
                LEFT JOIN companies c ON w.company_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Cég szerinti szűrés
        if (!empty($filters['company_id'])) {
            $sql .= " AND w.company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        // Dátum szerinti szűrés (tól-ig)
        if (!empty($filters['date_from'])) {
            $sql .= " AND w.work_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND w.work_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Státusz szerinti szűrés
        if (!empty($filters['status'])) {
            $sql .= " AND w.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY w.work_date DESC, w.worksheet_number DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Munkalap lekérése ID alapján
     */
    public function getById($id) {
        $sql = "SELECT w.*, c.name as company_name 
                FROM worksheets w
                LEFT JOIN companies c ON w.company_id = c.id
                WHERE w.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Új munkalap létrehozása
     */
    public function create($data) {
        // Ha nincs munkalap szám megadva, generáljuk
        if (empty($data['worksheet_number'])) {
            $year = !empty($data['work_date']) ? date('Y', strtotime($data['work_date'])) : null;
            $data['worksheet_number'] = $this->generateWorksheetNumber($year);
        }
        
        $sql = "INSERT INTO worksheets (
                    company_id, worksheet_number, work_date, work_hours, description,
                    reporter_name, device_name, worker_name, work_type, 
                    transport_fee, travel_fee, payment_type, work_time, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['company_id'] ?? null,
            $data['worksheet_number'] ?? '',
            $data['work_date'] ?? date('Y-m-d'),
            $data['work_hours'] ?? 0,
            $data['description'] ?? null,
            $data['reporter_name'] ?? null,
            $data['device_name'] ?? null,
            $data['worker_name'] ?? null,
            $data['work_type'] ?? 'Helyi',
            $data['transport_fee'] ?? 0,
            $data['travel_fee'] ?? 0,
            $data['payment_type'] ?? 'Eseti',
            $data['work_time'] ?? null,
            $data['status'] ?? 'Aktív'
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Munkalap frissítése
     */
    public function update($id, $data) {
        $sql = "UPDATE worksheets 
                SET company_id = ?, worksheet_number = ?, work_date = ?, work_hours = ?, 
                    description = ?, reporter_name = ?, device_name = ?, worker_name = ?, 
                    work_type = ?, transport_fee = ?, travel_fee = ?, 
                    payment_type = ?, work_time = ?, status = ?
                WHERE id = ?";
        
        $params = [
            $data['company_id'] ?? null,
            $data['worksheet_number'] ?? '',
            $data['work_date'] ?? date('Y-m-d'),
            $data['work_hours'] ?? 0,
            $data['description'] ?? null,
            $data['reporter_name'] ?? null,
            $data['device_name'] ?? null,
            $data['worker_name'] ?? null,
            $data['work_type'] ?? 'Helyi',
            $data['transport_fee'] ?? 0,
            $data['travel_fee'] ?? 0,
            $data['payment_type'] ?? 'Eseti',
            $data['work_time'] ?? null,
            $data['status'] ?? 'Aktív',
            $id
        ];
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Munkalap törlése
     */
    public function delete($id) {
        $sql = "DELETE FROM worksheets WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Munkalapok száma
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM worksheets WHERE 1=1";
        $params = [];
        
        if (!empty($filters['company_id'])) {
            $sql .= " AND company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND work_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND work_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}
?>

