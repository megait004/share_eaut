<?php
require_once __DIR__ . '/Settings.php';

class Auth {
    private $conn;
    private $settings;

    public function __construct($conn) {
        $this->conn = $conn;
        try {
            $this->settings = Settings::getInstance($conn);
        } catch (Exception $e) {
            // Nếu không thể khởi tạo Settings, tiếp tục với các giá trị mặc định
            error_log("Could not initialize Settings: " . $e->getMessage());
        }
    }

    public function register($data) {
        try {
            // Kiểm tra xem email đã tồn tại chưa
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email đã được sử dụng");
            }

            // Kiểm tra độ mạnh của mật khẩu
            if (strlen($data['password']) < 6) {
                throw new Exception("Mật khẩu phải có ít nhất 6 ký tự");
            }

            // Hash mật khẩu
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            // Lấy role_id cho người dùng mới (mặc định là user)
            $stmt = $this->conn->prepare("SELECT id FROM roles WHERE name = 'user'");
            $stmt->execute();
            $role = $stmt->fetch();
            $role_id = $role['id'];

            // Thêm người dùng mới
            $sql = "INSERT INTO users (full_name, email, password, role_id, status, created_at)
                    VALUES (:full_name, :email, :password, :role_id, 'active', NOW())";

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password' => $hashed_password,
                'role_id' => $role_id
            ]);

            if (!$success) {
                throw new Exception("Không thể tạo tài khoản");
            }

            return $this->conn->lastInsertId();

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Lỗi hệ thống, vui lòng thử lại sau");
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, r.name as role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    throw new Exception("Tài khoản của bạn đã bị khóa");
                }

                // Khởi tạo session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role_name'];

                // Nếu là admin, set thêm session admin
                if ($user['role_name'] === 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_name'] = $user['full_name'];
                }

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            throw new Exception("Lỗi hệ thống, vui lòng thử lại sau");
        }
    }

    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireAdmin() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: ../login.php');
            exit();
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['admin_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function updatePassword($user_id, $current_password, $new_password) {
        try {
            // Kiểm tra mật khẩu hiện tại
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception("Mật khẩu hiện tại không đúng");
            }

            // Kiểm tra độ mạnh của mật khẩu mới
            if (strlen($new_password) < 6) {
                throw new Exception("Mật khẩu mới phải có ít nhất 6 ký tự");
            }

            // Cập nhật mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashed_password, $user_id]);

        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            throw new Exception("Lỗi hệ thống, vui lòng thử lại sau");
        }
    }

    public function updateProfile($user_id, $data) {
        try {
            // Kiểm tra email mới có trùng với người dùng khác không
            if (isset($data['email'])) {
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $user_id]);
                if ($stmt->fetch()) {
                    throw new Exception("Email đã được sử dụng bởi người dùng khác");
                }
            }

            // Cập nhật thông tin
            $sql = "UPDATE users SET ";
            $params = [];
            $update_fields = [];

            foreach ($data as $key => $value) {
                if ($key !== 'password') { // Không cập nhật mật khẩu qua phương thức này
                    $update_fields[] = "$key = ?";
                    $params[] = $value;
                }
            }

            $sql .= implode(", ", $update_fields);
            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            throw new Exception("Lỗi hệ thống, vui lòng thử lại sau");
        }
    }

    public function adminLogin($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ? AND r.name = 'admin'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role_name'];
            return true;
        }
        return false;
    }

    public function isUserLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function adminLogout() {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_role']);
    }

    public function userLogout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_role']);
    }

    public function getCurrentId() {
        // Nếu đang ở trong admin area (URL chứa /admin/), ưu tiên dùng admin_id
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return $_SESSION['admin_id'];
        }

        // Nếu không phải admin area, ưu tiên user_id trước
        if ($this->isUserLoggedIn()) {
            return $_SESSION['user_id'];
        } elseif ($this->isAdmin()) {
            return $_SESSION['admin_id'];
        }

        return null;
    }

    public function getCurrentEmail() {
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return $_SESSION['admin_email'];
        }

        if ($this->isUserLoggedIn()) {
            return $_SESSION['user_email'];
        } elseif ($this->isAdmin()) {
            return $_SESSION['admin_email'];
        }

        return null;
    }

    public function getCurrentName() {
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return $_SESSION['admin_name'];
        }

        if ($this->isUserLoggedIn()) {
            return $_SESSION['user_name'];
        } elseif ($this->isAdmin()) {
            return $_SESSION['admin_name'];
        }

        return null;
    }

    public function getCurrentRole() {
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return $_SESSION['admin_role'];
        }

        if ($this->isUserLoggedIn()) {
            return $_SESSION['user_role'];
        } elseif ($this->isAdmin()) {
            return $_SESSION['admin_role'];
        }

        return null;
    }

    public function getActiveSessionType() {
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return 'admin';
        }

        if ($this->isUserLoggedIn()) {
            return 'user';
        } elseif ($this->isAdmin()) {
            return 'admin';
        }

        return null;
    }

    public function getCurrentUserData() {
        $is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

        if ($is_admin_area && $this->isAdmin()) {
            return [
                'id' => $_SESSION['admin_id'],
                'email' => $_SESSION['admin_email'],
                'name' => $_SESSION['admin_name'],
                'role' => $_SESSION['admin_role'],
                'is_admin' => true
            ];
        }

        if ($this->isUserLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role'],
                'is_admin' => false
            ];
        } elseif ($this->isAdmin()) {
            return [
                'id' => $_SESSION['admin_id'],
                'email' => $_SESSION['admin_email'],
                'name' => $_SESSION['admin_name'],
                'role' => $_SESSION['admin_role'],
                'is_admin' => true
            ];
        }

        return null;
    }
}