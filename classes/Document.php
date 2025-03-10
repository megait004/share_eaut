<?php

class Document {
    private $conn;
    private $upload_dir = 'uploads/';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function upload($file, $title, $description, $user_id, $category_id = null) {
        // Kiểm tra và tạo thư mục upload nếu chưa tồn tại
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }

        // Tạo tên file ngẫu nhiên để tránh trùng lặp
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $this->upload_dir . $new_filename;

        // Di chuyển file tải lên vào thư mục đích
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            try {
                $this->conn->beginTransaction();

                // Kiểm tra cài đặt kiểm duyệt
                $settings = Settings::getInstance($this->conn);
                $status = $settings->isDocumentModerationEnabled() ? 'pending' : 'approved';

                // Lưu thông tin tài liệu vào database
                $sql = "INSERT INTO documents (title, description, filename, original_filename, file_size, file_type, user_id, category_id, status)
                        VALUES (:title, :description, :filename, :original_filename, :file_size, :file_type, :user_id, :category_id, :status)";

                $stmt = $this->conn->prepare($sql);

                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':filename', $new_filename);
                $stmt->bindParam(':original_filename', $file['name']);
                $stmt->bindParam(':file_size', $file['size']);
                $stmt->bindParam(':file_type', $file['type']);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':status', $status);

                if ($stmt->execute()) {
                    $document_id = $this->conn->lastInsertId();

                    // Nếu cần kiểm duyệt, tạo thông báo cho admin
                    if ($status === 'pending') {
                        require_once __DIR__ . '/Notification.php';
                        $notification = new Notification($this->conn);
                        $notification->create(
                            'new_document',
                            "Có tài liệu mới cần duyệt: '$title'",
                            null, // gửi cho tất cả admin
                            "admin/documents.php"
                        );
                    }

                    $this->conn->commit();
                    return $document_id;
                } else {
                    $this->conn->rollBack();
                    throw new Exception("Không thể lưu thông tin tài liệu vào database");
                }
            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }
        } else {
            throw new Exception("Không thể tải file lên server");
        }
    }

    public function getDocument($id) {
        $sql = "SELECT * FROM documents WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllDocuments($limit = 10, $offset = 0) {
        $sql = "SELECT d.*, u.username as uploader_name, c.name as category_name
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN categories c ON d.category_id = c.id
                ORDER BY d.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDocument($id, $user_id) {
        // Kiểm tra quyền xóa
        $document = $this->getDocument($id);
        if (!$document || ($document['user_id'] != $user_id && !$this->isAdmin($user_id))) {
            throw new Exception("Bạn không có quyền xóa tài liệu này");
        }

        try {
            $this->conn->beginTransaction();

            // Xóa các liên kết trước
            $this->conn->prepare("DELETE FROM likes WHERE document_id = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM comments WHERE document_id = ?")->execute([$id]);

            // Xóa file vật lý
            $file_path = $this->upload_dir . $document['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Xóa record trong database
            $sql = "DELETE FROM documents WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $result = $stmt->execute();

            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function isAdmin($user_id) {
        $sql = "SELECT r.name as role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['role_name'] === 'admin';
    }

    public function getTotalDocuments() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM documents");
        return $stmt->fetchColumn();
    }

    public function getLatestDocuments($limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT d.*, u.full_name as username, d.status
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY d.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getDocumentById($id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, u.full_name as username, c.name as category_name
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateDocument($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE documents SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    public function searchDocuments($keyword) {
        $keyword = "%$keyword%";
        $stmt = $this->conn->prepare("
            SELECT d.*, u.full_name as username, c.name as category_name
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.title LIKE ? OR d.description LIKE ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$keyword, $keyword]);
        return $stmt->fetchAll();
    }

    public function getDocumentsByCategory($category_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, u.full_name as username
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            WHERE d.category_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll();
    }

    public function getDocumentsByUser($user_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, c.name as category_name
            FROM documents d
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getDocumentStats($document_id) {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(DISTINCT l.id) as like_count,
                COUNT(DISTINCT c.id) as comment_count,
                COUNT(DISTINCT v.id) as view_count
            FROM documents d
            LEFT JOIN likes l ON d.id = l.document_id
            LEFT JOIN comments c ON d.id = c.document_id
            LEFT JOIN views v ON d.id = v.document_id
            WHERE d.id = ?
        ");
        $stmt->execute([$document_id]);
        return $stmt->fetch();
    }
}