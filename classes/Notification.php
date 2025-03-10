<?php
class Notification {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($type, $message, $user_id, $link = null) {
        $sql = "INSERT INTO notifications (type, message, user_id, link, created_at)
                VALUES (:type, :message, :user_id, :link, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'type' => $type,
            'message' => $message,
            'user_id' => $user_id,
            'link' => $link
        ]);
    }

    public function getLatest($user_id, $limit = 5) {
        $sql = "SELECT * FROM notifications
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationsPaginated($user_id, $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM notifications
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalNotifications($user_id) {
        $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) as count FROM notifications
                WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    public function markAsRead($notification_id, $user_id) {
        $sql = "UPDATE notifications
                SET is_read = 1
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $notification_id,
            'user_id' => $user_id
        ]);
    }

    public function markAllAsRead($user_id) {
        $sql = "UPDATE notifications
                SET is_read = 1
                WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['user_id' => $user_id]);
    }

    public function getTimeAgo($datetime) {
        $time = strtotime($datetime);
        $current = time();
        $diff = $current - $time;

        $intervals = [
            31536000 => 'năm',
            2592000 => 'tháng',
            604800 => 'tuần',
            86400 => 'ngày',
            3600 => 'giờ',
            60 => 'phút',
            1 => 'giây'
        ];

        foreach ($intervals as $seconds => $label) {
            $interval = floor($diff / $seconds);
            if ($interval >= 1) {
                return $interval . ' ' . $label . ' trước';
            }
        }

        return 'Vừa xong';
    }

    public function deleteNotification($notification_id, $user_id) {
        $sql = "DELETE FROM notifications
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $notification_id,
            'user_id' => $user_id
        ]);
    }

    public function deleteAllNotifications($user_id) {
        $sql = "DELETE FROM notifications WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['user_id' => $user_id]);
    }
}