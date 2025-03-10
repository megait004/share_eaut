<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Document.php';

// Kiểm tra ID tài liệu
$document_id = $_GET['id'] ?? 0;
if (!$document_id) {
    die('ID tài liệu không hợp lệ');
}

// Lấy thông tin tài liệu
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$document_id]);
$document = $stmt->fetch();

if (!$document) {
    die('Không tìm thấy tài liệu');
}

// Kiểm tra quyền truy cập
if ($document['visibility'] === 'private' && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $document['user_id'])) {
    die('Bạn không có quyền tải xuống tài liệu này');
}

// Đường dẫn đến file
$file_path = 'uploads/' . $document['filename'];

// Kiểm tra file tồn tại
if (!file_exists($file_path)) {
    die('File không tồn tại');
}

// Tăng số lượt tải xuống
$stmt = $conn->prepare("UPDATE documents SET downloads = downloads + 1 WHERE id = ?");
$stmt->execute([$document_id]);

// Thiết lập headers để tải file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $document['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Đọc và xuất file
readfile($file_path);
exit;