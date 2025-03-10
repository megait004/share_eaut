<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo kết nối database nếu chưa có
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
}

require_once __DIR__ . '/../classes/Auth.php';
$auth = new Auth($conn);

// Get current user data
$current_user = $auth->getCurrentUserData();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            padding-top: 56px; /* Chiều cao của navbar */
        }
        .navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030;
            background-color: #212529 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .dropdown-menu {
            margin-top: 0.5rem;
            min-width: 200px;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            display: none;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            color: #212529;
            text-decoration: none;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .nav-item.dropdown .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        .nav-item.dropdown .dropdown-toggle:hover {
            cursor: pointer;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Quản lý tài liệu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="search.php">Tìm kiếm</a>
                </li>
                <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_documents.php">
                            <i class="fas fa-folder me-1"></i>Tài liệu của tôi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">Tải lên</a>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/">Quản trị</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">
                        <i class="fas fa-envelope me-1"></i>Liên hệ
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <?php if ($current_user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" id="userDropdownMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <span><?php echo htmlspecialchars($current_user['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdownMenu">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Hồ sơ cá nhân</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="my_documents.php">
                                    <i class="fas fa-folder"></i>
                                    <span>Tài liệu của tôi</span>
                                </a>
                            </li>
                            <?php if ($current_user['is_admin']): ?>
                                <li>
                                    <a class="dropdown-item" href="admin/">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Trang quản trị</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="messages.php">
                                    <i class="fas fa-envelope"></i>
                                    <span>Tin nhắn</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Đăng xuất</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['success'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo dropdown menu
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));

    // Thêm sự kiện click cho dropdown
    document.querySelector('#userDropdownMenu').addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = bootstrap.Dropdown.getInstance(this);
        if (dropdown) {
            dropdown.toggle();
        }
    });
});
</script>
</body>
</html>