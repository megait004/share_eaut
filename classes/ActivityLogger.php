<?php
class ActivityLogger {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function log($action, $description, $user_id = null) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, description, created_at)
                    VALUES (:user_id, :action, :description, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id,
                'action' => $action,
                'description' => $description
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    public function getRecentActivities($limit = 10) {
        try {
            $sql = "SELECT al.*, u.full_name as user_name
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [];
        }
    }

    public function getUserActivities($user_id, $limit = 10) {
        try {
            $sql = "SELECT * FROM activity_logs
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user activities: " . $e->getMessage());
            return [];
        }
    }
}