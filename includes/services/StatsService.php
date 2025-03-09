<?php
/**
 * Statistics Service
 * Handles database queries for dashboard statistics
 */
class StatsService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get dashboard statistics counts
     * 
     * @return array Array containing counts for cafeterias, menu items, and staff
     */
    public function getDashboardStats() {
        $stats = [
            'cafeteria_count' => 0,
            'menu_count' => 0,
            'staff_count' => 0
        ];
        
        // Get cafeteria count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM Cafeteria");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['cafeteria_count'] = $row['count'];
        }
        
        // Get menu item count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM Item_table");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['menu_count'] = $row['count'];
        }
        
        // Get staff count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM staff");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['staff_count'] = $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get cafeteria for a staff member
     * 
     * @param int $staffId Staff ID
     * @return string Cafeteria name or "N/A" if not assigned
     */
    public function getStaffCafeteria($staffId) {
        $cafeteria = "N/A";
        
        $stmt = $this->conn->prepare("
            SELECT c.Name 
            FROM staff s 
            JOIN Cafeteria c ON s.Cafeteria_Id = c.Cafeteria_Id 
            WHERE s.staffId = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $staffId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $cafeteria = $row['Name'];
            }
            $stmt->close();
        }
        
        return $cafeteria;
    }
}
