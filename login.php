<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

$auth = new Auth($conn);

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        if ($auth->login($email, $password)) {
            // Chuyển hướng đến trang chủ sau khi đăng nhập thành công
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không chính xác';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Login Section -->
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
}

.auth-card .card-body {
    padding: 3rem;
}

.auth-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2D3748;
    text-align: center;
    margin-bottom: 2rem;
}

.auth-subtitle {
    text-align: center;
    color: #718096;
    margin-bottom: 3rem;
}

.form-floating {
    margin-bottom: 1.5rem;
}

.form-floating .form-control {
    border: 2px solid #E2E8F0;
    border-radius: 15px;
    padding: 1rem 1rem;
    height: auto;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-floating .form-control:focus {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-floating label {
    padding: 1rem;
    color: #718096;
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

.auth-divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 2rem 0;
    color: #718096;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #E2E8F0;
}

.auth-divider span {
    padding: 0 1rem;
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
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

.auth-brand {
    text-align: center;
    margin-bottom: 3rem;
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
</style>

<section class="auth-section">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="auth-card" data-aos="fade-up">
                    <div class="card-body">
                        <div class="auth-brand">
                            <h1>Hệ thống Quản lý Tài liệu</h1>
                            <p>Đăng nhập để truy cập hệ thống</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="name@example.com" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>

                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Mật khẩu" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Mật khẩu
                                </label>
                            </div>

                            <div class="d-grid gap-3 mt-4">
                                <button class="btn btn-auth btn-auth-primary" type="submit">
                                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                                </button>
                                <a href="register.php" class="btn btn-auth btn-auth-outline">
                                    <i class="fas fa-user-plus me-2"></i>Đăng ký tài khoản mới
                                </a>
                            </div>

                            <div class="auth-divider">
                                <span>hoặc</span>
                            </div>

                            <div class="auth-footer">
                                <p class="mb-0">
                                    <a href="forgot-password.php">
                                        <i class="fas fa-key me-2"></i>Quên mật khẩu?
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add AOS Animation -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            once: true
        });
    });
</script>

<?php include 'includes/footer.php'; ?>