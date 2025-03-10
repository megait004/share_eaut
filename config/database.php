<?php

class Database {
    private $host = "localhost";
    private $db_name = "document_management";
    private $username = "root";
    private $password = "";
    private $conn = null;

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            }
            return $this->conn;
        } catch(PDOException $e) {
            throw new Exception("Không thể kết nối đến database: " . $e->getMessage());
        }
    }

    public function closeConnection() {
        $this->conn = null;
    }

    // Hàm để thực thi câu lệnh SQL an toàn
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Lỗi thực thi truy vấn: " . $e->getMessage());
        }
    }

    // Hàm để lấy một bản ghi
    public function fetchOne($sql, $params = []) {
        return $this->executeQuery($sql, $params)->fetch();
    }

    // Hàm để lấy nhiều bản ghi
    public function fetchAll($sql, $params = []) {
        return $this->executeQuery($sql, $params)->fetchAll();
    }

    // Hàm để lấy số bản ghi bị ảnh hưởng
    public function getRowCount($sql, $params = []) {
        return $this->executeQuery($sql, $params)->rowCount();
    }

    // Hàm để lấy ID của bản ghi vừa thêm vào
    public function getLastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    // Hàm để bắt đầu transaction
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    // Hàm để commit transaction
    public function commit() {
        return $this->getConnection()->commit();
    }

    // Hàm để rollback transaction
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
}