<?php
class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAll($role = null) {
        $sql = "SELECT * FROM users";
        if ($role) {
            $sql .= " WHERE role = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (name, email, password, role, gender, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'],
            $data['gender'],
            $data['phone'],
            $data['address']
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE users SET name = ?, email = ?, role = ?, gender = ?, phone = ?, address = ?, status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['role'],
            $data['gender'],
            $data['phone'],
            $data['address'],
            $data['status'],
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}