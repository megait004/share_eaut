<?php

class Contact {
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

    public function addContact($name, $email, $subject, $message, $parent_id = null) {
        try {
            $sql = "INSERT INTO contact_messages (name, email, subject, message, parent_id)
                    VALUES (:name, :email, :subject, :message, :parent_id)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':parent_id', $parent_id);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Không thể gửi liên hệ. Vui lòng thử lại sau.");
        }
    }

    public function getAllContacts($user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xem danh sách liên hệ");
        }

        // Chỉ lấy các tin nhắn gốc (không phải phản hồi)
        $sql = "SELECT * FROM contact_messages
                WHERE parent_id IS NULL
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContact($id, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xem thông tin liên hệ này");
        }

        // Lấy tin nhắn gốc và phản hồi của nó
        $sql = "SELECT m.*, r.message as reply_message, r.created_at as reply_date
                FROM contact_messages m
                LEFT JOIN contact_messages r ON m.id = r.parent_id
                WHERE m.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsRead($id, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền cập nhật trạng thái liên hệ");
        }

        $sql = "UPDATE contact_messages SET status = 'read' WHERE id = :id AND status = 'new'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function replyContact($contact_id, $reply_message, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền trả lời liên hệ");
        }

        try {
            $this->conn->beginTransaction();

            // Lấy thông tin liên hệ gốc
            $original = $this->getContact($contact_id, $user_id);
            if (!$original) {
                throw new Exception("Không tìm thấy liên hệ");
            }

            // Cập nhật trạng thái tin nhắn gốc
            $sql = "UPDATE contact_messages SET
                    status = 'replied',
                    has_reply = 1,
                    admin_reply = :reply_message,
                    replied_at = CURRENT_TIMESTAMP
                    WHERE id = :contact_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':contact_id', $contact_id);
            $stmt->bindParam(':reply_message', $reply_message);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Không thể trả lời liên hệ. Vui lòng thử lại sau.");
        }
    }

    public function deleteContact($id, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xóa liên hệ");
        }

        // Xóa tin nhắn (cascade sẽ tự động xóa các phản hồi)
        $sql = "DELETE FROM contact_messages WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function searchContacts($keyword, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền tìm kiếm liên hệ");
        }

        $keyword = "%$keyword%";
        $sql = "SELECT * FROM contact_messages
                WHERE parent_id IS NULL
                AND (name LIKE :keyword
                OR email LIKE :keyword
                OR subject LIKE :keyword
                OR message LIKE :keyword)
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContactsByStatus($status, $user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xem danh sách liên hệ");
        }

        // Kiểm tra status hợp lệ
        $valid_statuses = ['new', 'read', 'replied'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Trạng thái không hợp lệ");
        }

        $sql = "SELECT * FROM contact_messages
                WHERE status = :status
                AND parent_id IS NULL
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount($user_id) {
        // Kiểm tra quyền admin
        if (!$this->isAdmin($user_id)) {
            throw new Exception("Bạn không có quyền xem thông tin này");
        }

        $sql = "SELECT COUNT(*) as count FROM contact_messages
                WHERE status = 'new' AND parent_id IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function validateContact($name, $email, $subject, $message) {
        if (empty($name)) {
            throw new Exception("Tên không được để trống");
        }
        if (empty($email)) {
            throw new Exception("Email không được để trống");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }
        if (empty($subject)) {
            throw new Exception("Tiêu đề không được để trống");
        }
        if (empty($message)) {
            throw new Exception("Nội dung không được để trống");
        }
        if (strlen($message) > 1000) {
            throw new Exception("Nội dung không được vượt quá 1000 ký tự");
        }
        return true;
    }

    public function getTotalContacts() {
        try {
            $sql = "SELECT COUNT(*) as total FROM contact_messages WHERE parent_id IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total contacts: " . $e->getMessage());
            throw new Exception("Không thể lấy tổng số liên hệ");
        }
    }

    public function getTotalUnreadContacts() {
        try {
            $sql = "SELECT COUNT(*) as total FROM contact_messages WHERE status = 'new' AND parent_id IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total unread contacts: " . $e->getMessage());
            throw new Exception("Không thể lấy tổng số liên hệ chưa đọc");
        }
    }
}