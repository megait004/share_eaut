<?php
require_once '../config/database.php';

try {
    // Kết nối database
    $database = new Database();
    $conn = $database->getConnection();

    // Đọc nội dung file SQL
    $sql = file_get_contents(__DIR__ . '/add_is_public_column.sql');

    // Thực thi câu lệnh SQL
    $conn->exec($sql);

    echo "Đã thêm cột is_public vào bảng documents thành công!";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}