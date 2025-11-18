<?php
class Payment {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function submitPayment($data) {
        $sql = "INSERT INTO payments (student_id, fee_type, amount, payment_date, receipt_screenshot, bank_reference, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['student_id'],
            $data['fee_type'],
            $data['amount'],
            $data['payment_date'],
            $data['receipt_screenshot'],
            $data['bank_reference'],
            $data['remarks']
        ]);
    }
    
    public function getPendingPayments() {
        $sql = "SELECT p.*, s.student_id, u.name as student_name FROM payments p 
                JOIN students s ON p.student_id = s.id 
                JOIN users u ON s.user_id = u.id 
                WHERE p.status = 'pending' ORDER BY p.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function verifyPayment($id, $status, $verified_by, $remarks = null) {
        $sql = "UPDATE payments SET status = ?, verified_by = ?, verified_at = NOW(), remarks = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $verified_by, $remarks, $id]);
    }
    
    public function getStudentPayments($student_id) {
        $sql = "SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    }
    
    public function getFeeBalance($student_id) {
        $sql = "SELECT * FROM fee_balances WHERE student_id = ? AND status != 'paid' ORDER BY due_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    }
}