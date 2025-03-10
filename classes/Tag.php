<?php

class Tag {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function isAdmin($user_id) {
        $sql = "SELECT role FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['role'] === 'admin';
    }

    public function addTag($name, $description, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền thêm thẻ");
        }

        // Kiểm tra xem tên tag đã tồn tại chưa
        if ($this->getTagByName($name)) {
            throw new Exception("Tên thẻ đã tồn tại");
        }

        try {
            $sql = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Mã lỗi cho duplicate entry
                throw new Exception("Tên thẻ đã tồn tại");
            }
            throw $e;
        }
    }

    public function updateTag($id, $name, $description, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền cập nhật thẻ");
        }

        // Kiểm tra xem tag có tồn tại không
        $currentTag = $this->getTag($id);
        if (!$currentTag) {
            throw new Exception("Không tìm thấy thẻ");
        }

        // Nếu tên mới khác tên cũ, kiểm tra xem tên mới đã tồn tại chưa
        if ($currentTag['name'] !== $name) {
            $existingTag = $this->getTagByName($name);
            if ($existingTag) {
                throw new Exception("Tên thẻ đã tồn tại");
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
            if ($e->getCode() == 23000) {
                throw new Exception("Tên thẻ đã tồn tại");
            }
            throw $e;
        }
    }

    public function deleteTag($id, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xóa thẻ");
        }

        // Kiểm tra xem tag có tồn tại không
        if (!$this->getTag($id)) {
            throw new Exception("Không tìm thấy thẻ");
        }

        // Kiểm tra xem có tài liệu nào trong category không
        $sql = "SELECT COUNT(*) as count FROM documents WHERE category_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            throw new Exception("Không thể xóa thẻ này vì đang có " . $result['count'] . " tài liệu thuộc thẻ");
        }

        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getTag($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTagByName($name) {
        $sql = "SELECT * FROM categories WHERE name = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllTags() {
        $sql = "SELECT c.*, COUNT(d.id) as document_count
                FROM categories c
                LEFT JOIN documents d ON c.id = d.category_id
                GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
                ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchTags($keyword) {
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

    public function getTagDocuments($tag_id, $limit = 10, $offset = 0) {
        // Kiểm tra xem tag có tồn tại không
        if (!$this->getTag($tag_id)) {
            throw new Exception("Không tìm thấy thẻ");
        }

        $sql = "SELECT d.*, u.full_name as username
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.category_id = :tag_id
                ORDER BY d.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tag_id', $tag_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validateName($name) {
        if (empty($name)) {
            throw new Exception("Tên thẻ không được để trống");
        }
        if (strlen($name) > 255) {
            throw new Exception("Tên thẻ không được vượt quá 255 ký tự");
        }
        return true;
    }

    public function getTotalTags() {
        try {
            $sql = "SELECT COUNT(*) as total FROM categories";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total tags: " . $e->getMessage());
            throw new Exception("Không thể lấy tổng số thẻ: " . $e->getMessage());
        }
    }
}