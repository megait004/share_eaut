<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này']);
    exit;
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Lấy và kiểm tra dữ liệu
$name = trim($_POST['name'] ?? '');
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
    exit;
}

try {
    // Kiểm tra xem danh mục đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Danh mục này đã tồn tại']);
        exit;
    }

    // Thêm danh mục mới
    $stmt = $conn->prepare("INSERT INTO tags (name, created_at) VALUES (?, NOW())");
    if ($stmt->execute([$name])) {
        $id = $conn->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Thêm danh mục thành công',
            'id' => $id,
            'name' => $name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể thêm danh mục']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}