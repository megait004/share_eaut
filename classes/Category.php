<?php

class Category {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addCategory($name, $description = null) {
        // Kiểm tra xem tên danh mục đã tồn tại chưa
        if ($this->getCategoryByName($name)) {
            throw new Exception("Tên danh mục đã tồn tại");
        }

        try {
            $sql = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Mã lỗi cho duplicate entry
                throw new Exception("Tên danh mục đã tồn tại");
            }
            throw $e;
        }
    }

    public function updateCategory($id, $name, $description = null) {
        // Kiểm tra xem danh mục có tồn tại không
        $currentCategory = $this->getCategory($id);
        if (!$currentCategory) {
            throw new Exception("Không tìm thấy danh mục");
        }

        // Nếu tên mới khác tên cũ, kiểm tra xem tên mới đã tồn tại chưa
        if ($currentCategory['name'] !== $name) {
            $existingCategory = $this->getCategoryByName($name);
            if ($existingCategory) {
                throw new Exception("Tên danh mục đã tồn tại");
            }
        }

        try {
            $sql = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Mã lỗi cho duplicate entry
                throw new Exception("Tên danh mục đã tồn tại");
            }
            throw $e;
        }
    }

    public function deleteCategory($id) {
        // Kiểm tra xem danh mục có tồn tại không
        if (!$this->getCategory($id)) {
            throw new Exception("Không tìm thấy danh mục");
        }

        // Kiểm tra xem có tài liệu nào trong category không
        $sql = "SELECT COUNT(*) as count FROM documents WHERE category_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            throw new Exception("Không thể xóa danh mục này vì đang có " . $result['count'] . " tài liệu thuộc danh mục");
        }

        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getCategory($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllCategories() {
        $sql = "SELECT c.*, COUNT(d.id) as document_count
                FROM categories c
                LEFT JOIN documents d ON c.id = d.category_id
                GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
                ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryByName($name) {
        $sql = "SELECT * FROM categories WHERE name = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchCategories($keyword) {
        $keyword = "%$keyword%";
        $sql = "SELECT c.*, COUNT(d.id) as document_count
                FROM categories c
                LEFT JOIN documents d ON c.id = d.category_id
                WHERE c.name LIKE :keyword OR c.description LIKE :keyword
                GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
                ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryDocuments($category_id, $limit = 10, $offset = 0) {
        // Kiểm tra xem danh mục có tồn tại không
        if (!$this->getCategory($category_id)) {
            throw new Exception("Không tìm thấy danh mục");
        }

        $sql = "SELECT d.*, u.full_name as username
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.category_id = :category_id
                ORDER BY d.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryDocumentCount($category_id) {
        // Kiểm tra xem danh mục có tồn tại không
        if (!$this->getCategory($category_id)) {
            throw new Exception("Không tìm thấy danh mục");
        }

        $sql = "SELECT COUNT(*) as count FROM documents WHERE category_id = :category_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function validateName($name) {
        if (empty($name)) {
            throw new Exception("Tên danh mục không được để trống");
        }
        if (strlen($name) > 255) {
            throw new Exception("Tên danh mục không được vượt quá 255 ký tự");
        }
        return true;
    }

    public function getTotalCategories() {
        try {
            $sql = "SELECT COUNT(*) as total FROM categories";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total categories: " . $e->getMessage());
            throw new Exception("Không thể lấy tổng số danh mục");
        }
    }
}