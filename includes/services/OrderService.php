<?php
/**
 * Order Service
 * Handles database queries for orders
 */
class OrderService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get recent orders
     * 
     * @param int $limit Maximum number of orders to return
     * @return array Array of recent orders with student details
     */
    public function getRecentOrders($limit = 5) {
        $orders = [];
        
        $result = $this->conn->query("
            SELECT o.order_id, o.reg_no, o.order_date, o.total_cost, s.fistname, s.lastname 
            FROM orders o 
            JOIN student_table s ON o.reg_no = s.reg_no 
            ORDER BY o.order_date DESC 
            LIMIT {$limit}
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
}
