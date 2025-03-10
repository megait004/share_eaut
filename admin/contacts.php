<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

// Xử lý các hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? 0;
    $reply = $_POST['reply'] ?? '';

    switch ($action) {
        case 'reply':
            try {
                // Cập nhật trạng thái và thêm phản hồi
                $stmt = $conn->prepare("
                    UPDATE contact_messages
                    SET status = 'replied',
                        admin_reply = ?,
                        replied_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                if ($stmt->execute([$reply, $message_id])) {
                    // Gửi email thông báo (có thể thêm chức năng này sau)
                    $_SESSION['success'] = "Đã gửi phản hồi thành công!";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Lỗi: " . $e->getMessage();
            }
            break;

        case 'mark_read':
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success'] = "Đã đánh dấu là đã đọc!";
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success'] = "Đã xóa tin nhắn!";
            break;
    }

    header("Location: contacts.php");
    exit();
}

// Lấy danh sách tin nhắn
$status_filter = $_GET['status'] ?? 'all';
$query = "
    SELECT * FROM contact_messages
    WHERE 1=1
";

if ($status_filter !== 'all') {
    $query .= " AND status = " . $conn->quote($status_filter);
}

$query .= " ORDER BY created_at DESC";
$messages = $conn->query($query)->fetchAll();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
                        <h1 class="h3 fw-bold text-primary">Quản lý tin nhắn liên hệ</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-inbox me-1"></i> Tất cả
                                </a>
                                <a href="?status=new" class="btn <?php echo $status_filter === 'new' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-envelope me-1"></i> Chưa đọc
                                </a>
                                <a href="?status=read" class="btn <?php echo $status_filter === 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-envelope-open me-1"></i> Đã đọc
                                </a>
                                <a href="?status=replied" class="btn <?php echo $status_filter === 'replied' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-reply me-1"></i> Đã trả lời
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($messages as $message): ?>
                            <div class="col">
                                <div class="card message-card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <?php
                                            $status_class = match($message['status']) {
                                                'new' => 'status-new',
                                                'read' => 'status-read',
                                                'replied' => 'status-replied',
                                                default => ''
                                            };
                                            $status_text = match($message['status']) {
                                                'new' => 'Chưa đọc',
                                                'read' => 'Đã đọc',
                                                'replied' => 'Đã trả lời',
                                                default => 'Không xác định'
                                            };
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <i class="fas fa-circle me-1"></i><?php echo $status_text; ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-primary fw-bold mb-3">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </h5>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-circle me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($message['name']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($message['email']); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>

                                        <?php if ($message['status'] === 'replied'): ?>
                                            <div class="mt-4 p-3 bg-light rounded-3 border">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-circle admin-avatar me-2">
                                                        <i class="fas fa-user-shield"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">Phản hồi của Admin</h6>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?php echo date('d/m/Y H:i', strtotime($message['replied_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="reply-content">
                                                    <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <div class="btn-group w-100">
                                            <?php if ($message['status'] === 'new'): ?>
                                                <form method="POST" class="d-inline flex-fill">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-primary w-100">
                                                        <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($message['status'] !== 'replied'): ?>
                                                <button type="button" class="btn btn-outline-success flex-fill"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#replyModal<?php echo $message['id']; ?>">
                                                    <i class="fas fa-reply me-1"></i>Trả lời
                                                </button>
                                            <?php endif; ?>

                                            <form method="POST" class="d-inline flex-fill"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger w-100">
                                                    <i class="fas fa-trash me-1"></i>Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Trả lời -->
                            <div class="modal fade" id="replyModal<?php echo $message['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title">
                                                <i class="fas fa-reply me-2"></i>Trả lời tin nhắn
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="reply">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-user me-2"></i>Người gửi:
                                                    </label>
                                                    <div class="p-2 bg-light rounded">
                                                        <?php echo htmlspecialchars($message['name']); ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-heading me-2"></i>Tiêu đề:
                                                    </label>
                                                    <div class="p-2 bg-light rounded">
                                                        <?php echo htmlspecialchars($message['subject']); ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-envelope me-2"></i>Tin nhắn:
                                                    </label>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="reply<?php echo $message['id']; ?>" class="form-label fw-bold">
                                                        <i class="fas fa-pen me-2"></i>Nội dung trả lời:
                                                    </label>
                                                    <textarea class="form-control" id="reply<?php echo $message['id']; ?>"
                                                              name="reply" rows="5" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i>Đóng
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane me-1"></i>Gửi phản hồi
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.message-card {
    transition: all 0.3s ease;
    border-radius: 10px;
}

.message-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.status-new {
    background-color: #fef2f2;
    color: #dc3545;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.status-read {
    background-color: #fffbeb;
    color: #ffc107;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.status-replied {
    background-color: #ecfdf5;
    color: #28a745;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.admin-avatar {
    background-color: #cfe2ff;
    color: #0d6efd;
}

.message-content {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    white-space: pre-line;
}

.reply-content {
    background-color: #ffffff;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    white-space: pre-line;
}

.btn-group .btn {
    border-radius: 5px;
    margin: 0 2px;
}

.modal-content {
    border-radius: 10px;
    overflow: hidden;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>