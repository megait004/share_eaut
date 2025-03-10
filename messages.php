<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Xử lý gửi phản hồi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $message_id = $_POST['message_id'] ?? 0;
    $reply = trim($_POST['reply'] ?? '');

    if (!empty($reply)) {
        try {
            // Tạo tin nhắn mới với reference đến tin nhắn gốc
            $stmt = $conn->prepare("
                INSERT INTO contact_messages (name, email, subject, message, parent_id)
                SELECT ?, (SELECT email FROM users WHERE id = ?),
                       CONCAT('Re: ', subject), ?, ?
                FROM contact_messages
                WHERE id = ?
            ");

            if ($stmt->execute([
                $_SESSION['user_name'],
                $_SESSION['user_id'],
                $reply,
                $message_id,
                $message_id
            ])) {
                // Cập nhật trạng thái tin nhắn gốc
                $stmt = $conn->prepare("UPDATE contact_messages SET has_reply = 1 WHERE id = ?");
                $stmt->execute([$message_id]);

                $_SESSION['success'] = "Đã gửi phản hồi thành công!";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập nội dung phản hồi!";
    }

    header("Location: messages.php");
    exit();
}

// Lấy tin nhắn của người dùng và các phản hồi liên quan
$stmt = $conn->prepare("
    SELECT m.*,
           COALESCE(r.id, 0) as reply_id,
           r.message as user_reply,
           r.created_at as reply_created_at
    FROM contact_messages m
    LEFT JOIN contact_messages r ON m.id = r.parent_id
    WHERE m.email = (SELECT email FROM users WHERE id = ?)
    AND m.parent_id IS NULL
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin nhắn của tôi - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .message-card {
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 2px solid transparent;
            border-radius: 20px;
            overflow: hidden;
        }
        .message-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 97, 242, 0.15);
            border-color: rgba(0, 97, 242, 0.1);
        }
        .message-card .card-header {
            background: linear-gradient(135deg, rgba(0, 97, 242, 0.1) 0%, rgba(0, 186, 148, 0.1) 100%);
            border-bottom: 2px solid rgba(0, 97, 242, 0.05);
            padding: 1rem 1.5rem;
        }
        .status-new {
            color: #dc3545;
            font-weight: 600;
        }
        .status-read {
            color: #ffc107;
            font-weight: 600;
        }
        .status-replied {
            color: #28a745;
            font-weight: 600;
        }
        .message-header {
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        .message-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png');
            opacity: 0.15;
            animation: moveBackground 20s linear infinite;
        }
        @keyframes moveBackground {
            0% { background-position: 0 0; }
            100% { background-position: 100% 100%; }
        }
        .message-header .container {
            position: relative;
            z-index: 1;
        }
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            margin: 2rem 0;
        }
        .empty-state i {
            font-size: 5rem;
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }
        .empty-state h3 {
            color: #1a1f36;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .empty-state p {
            color: #506690;
            font-size: 1.1rem;
        }
        .message-thread {
            border-left: 3px solid #00ba94;
            margin-left: 1.5rem;
            padding-left: 1.5rem;
            margin-top: 1.5rem;
        }
        .admin-reply {
            background-color: rgba(0, 186, 148, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 2px solid rgba(0, 186, 148, 0.1);
            transition: all 0.3s ease;
        }
        .admin-reply:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 186, 148, 0.1);
        }
        .user-reply {
            background-color: rgba(0, 97, 242, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0 1rem 2rem;
            border: 2px solid rgba(0, 97, 242, 0.1);
            transition: all 0.3s ease;
        }
        .user-reply:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 97, 242, 0.1);
        }
        .btn-outline-primary {
            border: 2px solid #0061f2;
            color: #0061f2;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 97, 242, 0.15);
        }
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-footer {
            border-top: 2px solid rgba(0, 97, 242, 0.1);
            padding: 1.5rem;
        }
        .display-4 {
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.2;
        }
        .lead {
            font-size: 1.25rem;
            line-height: 1.8;
            opacity: 0.9;
        }
        .card-title {
            color: #1a1f36;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .card-text {
            color: #506690;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="message-header">
        <div class="container">
            <h1 class="display-4">Tin nhắn của tôi</h1>
            <p class="lead">Xem lịch sử liên hệ và trao đổi với quản trị viên</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Chưa có tin nhắn nào</h3>
                <p class="text-muted">Bạn chưa gửi tin nhắn liên hệ nào. Hãy <a href="contact.php">liên hệ với chúng tôi</a> nếu cần hỗ trợ.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($messages as $message): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card message-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <?php
                                    $status_class = match($message['status']) {
                                        'new' => 'status-new',
                                        'read' => 'status-read',
                                        'replied' => 'status-replied',
                                        default => ''
                                    };
                                    $status_text = match($message['status']) {
                                        'new' => 'Đã gửi',
                                        'read' => 'Đã xem',
                                        'replied' => 'Đã trả lời',
                                        default => 'Không xác định'
                                    };
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <i class="fas fa-circle me-1"></i><?php echo $status_text; ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>

                                <?php if ($message['status'] === 'replied'): ?>
                                    <div class="message-thread">
                                        <div class="admin-reply">
                                            <h6 class="mb-2">
                                                <i class="fas fa-reply me-2"></i>Phản hồi từ quản trị viên:
                                            </h6>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?></p>
                                            <small class="text-muted">
                                                Đã trả lời lúc: <?php echo date('d/m/Y H:i', strtotime($message['replied_at'])); ?>
                                            </small>
                                        </div>

                                        <?php if ($message['reply_id']): ?>
                                            <div class="user-reply">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-reply me-2"></i>Phản hồi của bạn:
                                                </h6>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['user_reply'])); ?></p>
                                                <small class="text-muted">
                                                    Đã gửi lúc: <?php echo date('d/m/Y H:i', strtotime($message['reply_created_at'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!$message['reply_id']): ?>
                                            <button type="button" class="btn btn-outline-primary mt-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#replyModal<?php echo $message['id']; ?>">
                                                <i class="fas fa-reply me-2"></i>Phản hồi lại
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Phản hồi -->
                    <?php if ($message['status'] === 'replied' && !$message['reply_id']): ?>
                        <div class="modal fade" id="replyModal<?php echo $message['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Phản hồi tin nhắn</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">

                                            <div class="mb-3">
                                                <label class="form-label">Tiêu đề gốc:</label>
                                                <div><?php echo htmlspecialchars($message['subject']); ?></div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Phản hồi của admin:</label>
                                                <div class="p-3 bg-light rounded">
                                                    <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="reply<?php echo $message['id']; ?>" class="form-label">Nội dung phản hồi của bạn:</label>
                                                <textarea class="form-control" id="reply<?php echo $message['id']; ?>"
                                                          name="reply" rows="5" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                            <button type="submit" class="btn btn-primary">Gửi phản hồi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>