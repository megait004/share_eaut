<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra xem có ID tài liệu được truyền vào không
$document_id = null;
if (isset($_POST['document_id']) && is_numeric($_POST['document_id'])) {
    $document_id = (int)$_POST['document_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $document_id = (int)$_GET['id'];
}

if ($document_id === null) {
    $_SESSION['error'] = "ID tài liệu không hợp lệ";
    header('Location: my_documents.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Kiểm tra xem tài liệu có tồn tại và thuộc về người dùng hiện tại không
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND (user_id = ? OR ?)");
    $stmt->execute([$document_id, $user_id, $is_admin]);
    $document = $stmt->fetch();

    if (!$document) {
        $_SESSION['error'] = "Bạn không có quyền xóa tài liệu này hoặc tài liệu không tồn tại";
        header('Location: my_documents.php');
        exit();
    }

    // Xóa file vật lý nếu tồn tại
    if (!empty($document['file_path']) && file_exists($document['file_path'])) {
        unlink($document['file_path']);
    }

    // Xóa record trong database
    $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);

    $_SESSION['success'] = "Tài liệu đã được xóa thành công";
    header('Location: my_documents.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Có lỗi xảy ra khi xóa tài liệu: " . $e->getMessage();
    header('Location: my_documents.php');
    exit();
}
?>