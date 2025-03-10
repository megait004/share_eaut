<?php
session_start();

require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/ActivityLogger.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

// Khởi tạo các đối tượng cần thiết
$auth = new Auth($conn);
$activityLogger = new ActivityLogger($conn);

// Log hoạt động đăng xuất nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $activityLogger->log('logout', 'User logged out', $user_id);
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $activityLogger->log('logout', 'Admin logged out', $user_id);
}

// Xóa tất cả session
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: login.php');
exit();
?>