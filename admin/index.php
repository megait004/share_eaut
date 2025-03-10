<?php
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Document.php';
require_once '../classes/User.php';
require_once '../classes/Comment.php';
require_once '../classes/Category.php';
require_once '../classes/Tag.php';
require_once '../classes/Contact.php';
require_once '../classes/Notification.php';

session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$document = new Document($conn);
$user = new User($conn);
$comment = new Comment($conn);
$category = new Category($conn);
$tag = new Tag($conn);
$contact = new Contact($conn);
$notification = new Notification($conn);

// Lấy thống kê
$total_documents = $document->getTotalDocuments();
$total_users = $user->getTotalUsers();
$total_comments = $comment->getTotalComments();
$total_categories = $category->getTotalCategories();
$total_tags = $tag->getTotalTags();
$total_contacts = $contact->getTotalContacts();
$unread_contacts = $contact->getTotalUnreadContacts();

// Lấy 5 tài liệu mới nhất
$latest_documents = $document->getLatestDocuments(5);

// Lấy 5 người dùng mới nhất
$latest_users = $user->getLatestUsers(5);

// Lấy 5 bình luận mới nhất
$latest_comments = $comment->getLatestComments(5);

// Lấy thông báo chưa đọc
$unread_count = $notification->getUnreadCount($_SESSION['admin_id']);
$notifications = $notification->getLatest($_SESSION['admin_id'], 5);

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800 fw-bold">Dashboard</h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="stats-icon primary-gradient">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Tổng tài liệu</h6>
                            <h3 class="card-title mb-0 fw-bold"><?php echo number_format($total_documents); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="stats-icon success-gradient">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Tổng người dùng</h6>
                            <h3 class="card-title mb-0 fw-bold"><?php echo number_format($total_users); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="stats-icon info-gradient">
                                <i class="fas fa-comments"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Tổng bình luận</h6>
                            <h3 class="card-title mb-0 fw-bold"><?php echo number_format($total_comments); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="stats-icon warning-gradient">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-subtitle text-muted mb-1">Liên hệ chưa đọc</h6>
                            <h3 class="card-title mb-0 fw-bold"><?php echo number_format($unread_contacts); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Latest Documents -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100 content-card">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-gray-800">Tài liệu mới nhất</h5>
                        <a href="documents.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="fas fa-arrow-right me-1"></i> Xem tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Tên tài liệu</th>
                                    <th class="border-0">Người đăng</th>
                                    <th class="border-0">Ngày đăng</th>
                                    <th class="border-0">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_documents as $doc): ?>
                                <tr class="table-row">
                                    <td>
                                        <a href="../view_document.php?id=<?php echo $doc['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['username']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                                    <td>
                                        <?php if ($doc['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Đã duyệt</span>
                                        <?php elseif ($doc['status'] == 'rejected'): ?>
                                            <span class="badge bg-danger">Từ chối</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Chờ duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Users -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100 content-card">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-gray-800">Người dùng mới nhất</h5>
                        <a href="users.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="fas fa-arrow-right me-1"></i> Xem tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Tên người dùng</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0">Ngày đăng ký</th>
                                    <th class="border-0">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_users as $usr): ?>
                                <tr class="table-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-title rounded-circle bg-primary">
                                                    <?php echo strtoupper(substr($usr['full_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <?php echo htmlspecialchars($usr['full_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($usr['created_at'])); ?></td>
                                    <td>
                                        <?php if ($usr['status'] == 'active'): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Khóa</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Comments -->
        <div class="col-12">
            <div class="card border-0 shadow-sm content-card">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-gray-800">Bình luận mới nhất</h5>
                        <a href="comments.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="fas fa-arrow-right me-1"></i> Xem tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Người bình luận</th>
                                    <th class="border-0">Tài liệu</th>
                                    <th class="border-0">Nội dung</th>
                                    <th class="border-0">Ngày bình luận</th>
                                    <th class="border-0">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_comments as $cmt): ?>
                                <tr class="table-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-title rounded-circle bg-info">
                                                    <?php echo strtoupper(substr($cmt['username'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <?php echo htmlspecialchars($cmt['username']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../view_document.php?id=<?php echo $cmt['document_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($cmt['document_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;">
                                            <?php echo htmlspecialchars($cmt['content']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($cmt['created_at'])); ?></td>
                                    <td>
                                        <?php if ($cmt['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Đã duyệt</span>
                                        <?php elseif ($cmt['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Spam</span>
                                        <?php endif; ?>
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

    <style>
    .stat-card {
        transition: transform 0.2s ease-in-out;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .content-card {
        transition: box-shadow 0.2s ease-in-out;
    }

    .content-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
        color: white;
    }

    .primary-gradient {
        background: linear-gradient(45deg, #4e73df, #6f42c1);
    }

    .success-gradient {
        background: linear-gradient(45deg, #1cc88a, #20c997);
    }

    .info-gradient {
        background: linear-gradient(45deg, #36b9cc, #0dcaf0);
    }

    .warning-gradient {
        background: linear-gradient(45deg, #f6c23e, #fd7e14);
    }

    .custom-table {
        margin: 0;
    }

    .custom-table thead th {
        background-color: #f8f9fc;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-top: none;
    }

    .table-row {
        transition: background-color 0.2s ease-in-out;
    }

    .table-row:hover {
        background-color: #f8f9fc;
    }

    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }

    .avatar {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
    }

    .text-gray-800 {
        color: #2d3748;
    }
    </style>
</div>

<?php require_once 'includes/admin_footer.php'; ?>