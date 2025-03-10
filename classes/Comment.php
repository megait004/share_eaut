<?php

class Comment {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addComment($document_id, $user_id, $content) {
        $sql = "INSERT INTO comments (document_id, user_id, content, status) VALUES (:document_id, :user_id, :content, 'active')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':document_id', $document_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':content', $content);
        return $stmt->execute();
    }

    public function getCommentsByDocument($document_id, $status = 'active') {
        $sql = "SELECT c.*, u.full_name as username
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.document_id = :document_id
                AND c.status = :status
                ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':document_id', $document_id);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCommentsByDocument($document_id) {
        $sql = "SELECT c.*, u.full_name as username
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.document_id = :document_id
                ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':document_id', $document_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteComment($comment_id, $user_id) {
        // Kiểm tra quyền xóa (chỉ admin hoặc người tạo comment mới được xóa)
        $sql = "SELECT * FROM comments WHERE id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->execute();
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            throw new Exception("Không tìm thấy bình luận");
        }

        // Kiểm tra xem người dùng có phải admin hoặc chủ comment
        $sql = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment['user_id'] != $user_id && $user['role'] !== 'admin') {
            throw new Exception("Bạn không có quyền xóa bình luận này");
        }

        // Thực hiện xóa comment
        $sql = "DELETE FROM comments WHERE id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':comment_id', $comment_id);
        return $stmt->execute();
    }

    public function updateComment($comment_id, $user_id, $content) {
        // Kiểm tra quyền sửa (chỉ người tạo comment mới được sửa)
        $sql = "SELECT * FROM comments WHERE id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->execute();
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            throw new Exception("Không tìm thấy bình luận");
        }

        if ($comment['user_id'] != $user_id) {
            throw new Exception("Bạn không có quyền sửa bình luận này");
        }

        // Thực hiện cập nhật comment
        $sql = "UPDATE comments SET content = :content WHERE id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':comment_id', $comment_id);
        return $stmt->execute();
    }

    public function getComment($comment_id) {
        $sql = "SELECT c.*, u.full_name as username
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($comment_id, $user_id, $status) {
        // Chỉ admin mới có quyền thay đổi trạng thái comment
        $sql = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] !== 'admin') {
            throw new Exception("Bạn không có quyền thay đổi trạng thái bình luận");
        }

        // Kiểm tra status hợp lệ
        $valid_statuses = ['active', 'hidden', 'deleted'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Trạng thái không hợp lệ");
        }

        // Thực hiện cập nhật trạng thái
        $sql = "UPDATE comments SET status = :status WHERE id = :comment_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':comment_id', $comment_id);
        return $stmt->execute();
    }

    public function getCommentCount($document_id, $status = 'active') {
        $sql = "SELECT COUNT(*) as count FROM comments
                WHERE document_id = :document_id AND status = :status";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':document_id', $document_id);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getTotalComments() {
        try {
            $sql = "SELECT COUNT(*) as total FROM comments";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total comments: " . $e->getMessage());
            throw new Exception("Không thể lấy tổng số bình luận");
        }
    }

    public function getLatestComments($limit = 5) {
        try {
            $sql = "SELECT c.*, u.full_name as username, d.title as document_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN documents d ON c.document_id = d.id
                    WHERE c.status = 'active'
                    ORDER BY c.created_at DESC
                    LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting latest comments: " . $e->getMessage());
            throw new Exception("Không thể lấy danh sách bình luận mới nhất");
        }
    }
}