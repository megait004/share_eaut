<?php
class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getTotalUsers() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    public function getLatestUsers($limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name, u.full_name, u.email, u.status, u.created_at
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getUserById($id) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateUser($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getUsersByRole($role_id) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.role_id = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$role_id]);
        return $stmt->fetchAll();
    }

    public function searchUsers($keyword) {
        $keyword = "%$keyword%";
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.full_name LIKE ? OR u.email LIKE ?
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$keyword, $keyword]);
        return $stmt->fetchAll();
    }

    public function getUserStats($user_id) {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(DISTINCT d.id) as document_count,
                COUNT(DISTINCT c.id) as comment_count,
                COUNT(DISTINCT l.id) as like_count
            FROM users u
            LEFT JOIN documents d ON u.id = d.user_id
            LEFT JOIN comments c ON u.id = c.user_id
            LEFT JOIN likes l ON u.id = l.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}