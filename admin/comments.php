<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Settings.php';
require_once '../classes/Notification.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

// Khởi tạo các đối tượng
$auth = new Auth($conn);
$auth->requireAdmin();

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);

// Xử lý các hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = $_POST['comment_id'] ?? 0;

    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success'] = "Đã xóa bình luận thành công!";
            break;

        case 'approve':
            $stmt = $conn->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$comment_id]);

            // Lấy thông tin bình luận để thông báo
            $stmt = $conn->prepare("
                SELECT c.*, u.email, d.title
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN documents d ON c.document_id = d.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            // Tạo thông báo cho người viết bình luận
            if ($comment) {
                $notification->create(
                    'comment_approved',
                    "Bình luận của bạn trên tài liệu '{$comment['title']}' đã được phê duyệt",
                    $comment['user_id'],
                    "view_document.php?id=" . $comment['document_id']
                );
            }

            $_SESSION['success'] = "Đã phê duyệt bình luận thành công!";
            break;

        case 'reject':
            $stmt = $conn->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success'] = "Đã từ chối bình luận thành công!";
            break;
    }

    header("Location: comments.php");
    exit();
}

// Lấy danh sách bình luận với thông tin liên quan
$stmt = $conn->prepare("
    SELECT c.*,
           u.email as user_email,
           u.full_name as user_name,
           d.title as document_title,
           d.id as document_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN documents d ON c.document_id = d.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$comments = $stmt->fetchAll();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-comments me-2"></i>
                            Quản lý bình luận
                        </h1>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-bs-toggle="collapse" data-bs-target="#filterSection">
                                <i class="fas fa-filter"></i> Bộ lọc
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%">ID</th>
                                    <th width="15%">Người dùng</th>
                                    <th width="20%">Tài liệu</th>
                                    <th width="25%">Nội dung</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="15%">Thời gian</th>
                                    <th width="10%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td class="text-center"><?php echo $comment['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2 bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($comment['user_name']); ?></div>
                                                <small class="text-muted"><?php echo $comment['user_email']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../view_document.php?id=<?php echo $comment['document_id']; ?>" class="text-decoration-none" target="_blank">
                                            <i class="fas fa-file-alt me-1"></i>
                                            <?php echo htmlspecialchars($comment['document_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="comment-content">
                                            <?php echo htmlspecialchars($comment['content']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($settings->areCommentsModerated()): ?>
                                            <?php if ($comment['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock me-1"></i> Chờ duyệt
                                                </span>
                                            <?php elseif ($comment['status'] === 'approved'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i> Đã duyệt
                                                </span>
                                            <?php elseif ($comment['status'] === 'spam'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-ban me-1"></i> Spam
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($comment['status'] === 'spam'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-ban me-1"></i> Spam
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i> Đã đăng
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($comment['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Phê duyệt">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" title="Từ chối">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline delete-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.comment-content {
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.badge {
    padding: 0.5em 0.8em;
}

.btn-group .btn {
    margin: 0 2px;
}

.card {
    border: none;
    margin-bottom: 30px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

.delete-form button {
    transition: all 0.2s;
}

.delete-form button:hover {
    transform: scale(1.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xác nhận xóa bình luận
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>