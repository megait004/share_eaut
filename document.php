<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Lấy ID tài liệu
$document_id = $_GET['id'] ?? 0;

// Lấy thông tin tài liệu
$query = "
    SELECT d.*, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch();

if (!$document) {
    die("Không tìm thấy tài liệu!");
}

// Kiểm tra file có tồn tại
$file_path = 'uploads/' . $document['filename'];
if (!file_exists($file_path)) {
    die("File không tồn tại!");
}

// Lấy mime type của file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Gửi header phù hợp
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . $document['original_filename'] . '"');

// Đọc và xuất file
readfile($file_path);
exit;