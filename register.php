<?php
session_start();

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Auth.php';

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

// Khởi tạo Auth
$auth = new Auth($conn);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $required_fields = ['full_name', 'email', 'password', 'confirm_password'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Vui lòng điền đầy đủ thông tin");
            }
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }

        $password = $_POST['password'];

        // Validate password requirements
        if (strlen($password) < 8) {
            throw new Exception("Mật khẩu phải có ít nhất 8 ký tự");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Mật khẩu phải có ít nhất 1 chữ hoa");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Mật khẩu phải có ít nhất 1 chữ thường");
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Mật khẩu phải có ít nhất 1 số");
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Mật khẩu xác nhận không khớp");
        }

        // Đăng ký tài khoản mới
        $user_data = [
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'password' => $_POST['password']
        ];

        $user_id = $auth->register($user_data);

        if ($user_id) {
            $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
            header('Location: login.php');
            exit();
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Hệ thống Quản lý Tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .auth-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            position: relative;
            overflow: hidden;
        }

        .auth-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" width="100" height="100"/></svg>');
            opacity: 0.1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            margin: 2rem auto;
        }

        .auth-card .card-body {
            padding: 3rem;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-brand img {
            height: 50px;
            margin-bottom: 1rem;
        }

        .auth-brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2D3748;
            margin: 0;
        }

        .auth-brand p {
            color: #718096;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            border: 2px solid #E2E8F0;
            border-radius: 15px;
            padding: 1rem 1rem 1rem 3rem;
            height: auto;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-size: 1.2rem;
        }

        .form-label {
            color: #4A5568;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-auth {
            padding: 1rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-auth-primary {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            border: none;
            color: white;
        }

        .btn-auth-outline {
            background: transparent;
            border: 2px solid #6366F1;
            color: #6366F1;
        }

        .btn-auth-outline:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #718096;
        }

        .auth-footer a {
            color: #6366F1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #4F46E5;
            text-decoration: underline;
        }

        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .alert-success {
            background-color: #DCFCE7;
            color: #16A34A;
        }
    </style>
</head>
<body>
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="auth-card" data-aos="fade-up">
                        <div class="card-body">
                            <div class="auth-brand">
                                <h1>Hệ thống Quản lý Tài liệu</h1>
                                <p>Tạo tài khoản mới</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="registerForm">
                                <div class="form-group">
                                    <label for="full_name" class="form-label">Họ và tên</label>
                                    <i class="fas fa-user form-icon"></i>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <i class="fas fa-envelope form-icon"></i>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <i class="fas fa-lock form-icon"></i>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                    <i class="fas fa-lock form-icon"></i>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <div class="d-grid gap-3 mt-4">
                                    <button type="submit" class="btn btn-auth btn-auth-primary">
                                        <i class="fas fa-user-plus me-2"></i>Đăng ký
                                    </button>
                                    <a href="login.php" class="btn btn-auth btn-auth-outline">
                                        <i class="fas fa-sign-in-alt me-2"></i>Đã có tài khoản? Đăng nhập
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                once: true
            });
        });
    </script>
</body>
</html>