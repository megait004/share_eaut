<?php

class Settings {
    private static $instance = null;
    private $conn;
    private $settings = [];

    private function __construct($conn) {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        $this->conn = $conn;
        $this->loadSettings();
    }

    public static function getInstance($conn = null) {
        if (self::$instance === null) {
            if (!$conn) {
                throw new Exception("Database connection is required for first initialization");
            }
            self::$instance = new self($conn);
        }
        return self::$instance;
    }

    private function loadSettings() {
        try {
            $stmt = $this->conn->query("SELECT `key`, `value` FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['key']] = $row['value'];
            }
        } catch (PDOException $e) {
            error_log("Error loading settings: " . $e->getMessage());
            throw new Exception("Không thể tải cấu hình hệ thống");
        }
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            $stmt->execute([$key, $value, $value]);
            $this->settings[$key] = $value;
            return true;
        } catch (PDOException $e) {
            error_log("Error setting value: " . $e->getMessage());
            throw new Exception("Không thể cập nhật cấu hình");
        }
    }

    public function isRegistrationAllowed() {
        return filter_var($this->get('allow_registration', 'true'), FILTER_VALIDATE_BOOLEAN);
    }

    public function isEmailVerificationRequired() {
        return filter_var($this->get('require_email_verification', 'true'), FILTER_VALIDATE_BOOLEAN);
    }

    public function getDefaultUserRole() {
        return $this->get('default_user_role', 'user');
    }

    public function getSystemEmail() {
        return $this->get('system_email', 'noreply@example.com');
    }

    public function getSystemName() {
        return $this->get('system_name', 'Hệ thống quản lý tài liệu');
    }

    public function getMaxUploadSize() {
        return (int) $this->get('max_upload_size', '5242880'); // Default: 5MB
    }

    public function getAllowedFileTypes() {
        $types = $this->get('allowed_file_types', 'pdf,doc,docx,xls,xlsx,txt');
        return explode(',', $types);
    }

    public function getMaxLoginAttempts() {
        return (int) $this->get('max_login_attempts', '5');
    }

    public function getLockoutDuration() {
        return (int) $this->get('lockout_duration', '900'); // Default: 15 minutes
    }

    public function getPasswordMinLength() {
        return (int) $this->get('password_min_length', '6');
    }

    public function getPasswordRequirements() {
        return [
            'min_length' => $this->getPasswordMinLength(),
            'require_uppercase' => filter_var($this->get('password_require_uppercase', 'true'), FILTER_VALIDATE_BOOLEAN),
            'require_lowercase' => filter_var($this->get('password_require_lowercase', 'true'), FILTER_VALIDATE_BOOLEAN),
            'require_number' => filter_var($this->get('password_require_number', 'true'), FILTER_VALIDATE_BOOLEAN),
            'require_special' => filter_var($this->get('password_require_special', 'true'), FILTER_VALIDATE_BOOLEAN)
        ];
    }

    public function getAll() {
        return $this->settings;
    }

    // Các helper methods
    public function areCommentsAllowed() {
        return $this->get('allow_comments') === '1';
    }

    public function areCommentsModerated() {
        return $this->get('moderate_comments') === '1';
    }

    public function getSpamKeywords() {
        $keywords = $this->get('spam_keywords', '');
        return array_filter(array_map('trim', explode(',', $keywords)));
    }

    public function isCommentSpam($content) {
        $keywords = $this->getSpamKeywords();
        if (empty($keywords)) {
            return false;
        }
        $content = mb_strtolower($content);
        foreach ($keywords as $keyword) {
            if (!empty($keyword) && mb_strpos($content, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    public function isDocumentModerationEnabled() {
        return filter_var($this->get('require_document_moderation', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    public function setDocumentModeration($enabled) {
        return $this->set('require_document_moderation', $enabled ? 'true' : 'false');
    }
}