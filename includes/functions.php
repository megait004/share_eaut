<?php
// Hàm kiểm tra và làm sạch input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Hàm kiểm tra người dùng đã đăng nhập chưa
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra người dùng có phải admin không
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Hàm tạo thông báo
function set_message($type, $message) {
    $_SESSION[$type] = $message;
}

// Hàm hiển thị thông báo
function display_message() {
    $types = ['success', 'error', 'warning', 'info'];
    $output = '';

    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $output .= "<div class='alert alert-{$type}'>{$_SESSION[$type]}</div>";
            unset($_SESSION[$type]);
        }
    }

    return $output;
}

// Hàm tạo URL an toàn
function create_safe_url($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Hàm format dung lượng file
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>