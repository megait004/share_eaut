<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Notification.php';

// Khởi tạo kết nối database và các đối tượng cần thiết
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

$notification = new Notification($conn);

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy tổng số thông báo và danh sách theo phân trang
$total_notifications = $notification->getTotalNotifications($_SESSION['admin_id']);
$total_pages = ceil($total_notifications / $limit);
$notifications = $notification->getNotificationsPaginated($_SESSION['admin_id'], $limit, $offset);

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="page-header-left">
                    <h1 class="h3 mb-0">Thông báo</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Thông báo</li>
                        </ol>
                    </nav>
                </div>
                <?php if (!empty($notifications)): ?>
                    <div class="page-header-right">
                        <button type="button" class="btn btn-primary mark-all-read">
                            <i class="fas fa-check-double me-2"></i>Đánh dấu tất cả đã đọc
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notifications List -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <?php if (!empty($notifications)): ?>
                        <div class="notifications-container">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-card <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                                     data-id="<?php echo $notif['id']; ?>">
                                    <div class="notification-icon">
                                        <?php
                                        $icon_class = match ($notif['type'] ?? '') {
                                            'comment' => 'fa-comment text-primary',
                                            'like' => 'fa-heart text-danger',
                                            'document' => 'fa-file-alt text-success',
                                            'system' => 'fa-cog text-warning',
                                            default => 'fa-bell text-info'
                                        };
                                        ?>
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <h6 class="notification-title mb-1">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="badge bg-primary ms-2">Mới</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo $notification->getTimeAgo($notif['created_at']); ?>
                                            </small>
                                        </div>
                                        <?php if ($notif['link']): ?>
                                            <div class="notification-actions mt-2">
                                                <a href="<?php echo htmlspecialchars($notif['link']); ?>"
                                                   class="btn btn-sm btn-light">
                                                    <i class="fas fa-external-link-alt me-1"></i>
                                                    Xem chi tiết
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
                                <h4 class="text-muted">Không có thông báo nào</h4>
                                <p class="text-muted mb-4">Các thông báo mới sẽ xuất hiện tại đây</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Quay lại Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Notifications Container */
.notifications-container {
    max-width: 800px;
    margin: 0 auto;
}

/* Notification Card */
.notification-card {
    display: flex;
    align-items: flex-start;
    padding: 1.25rem;
    margin-bottom: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,.1);
    transition: all 0.3s ease;
}

.notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,.1);
}

.notification-card.unread {
    background-color: #f8f9ff;
    border-left: 4px solid #1a73e8;
}

/* Notification Icon */
.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
    margin-right: 1rem;
    flex-shrink: 0;
}

.notification-icon i {
    font-size: 1.25rem;
}

/* Notification Content */
.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

/* Empty State */
.empty-state {
    padding: 3rem;
    text-align: center;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,.1);
}

.empty-state i {
    margin-bottom: 1.5rem;
}

/* Pagination Customization */
.pagination {
    margin-bottom: 2rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    color: #1a73e8;
    border: none;
    margin: 0 0.25rem;
    border-radius: 0.25rem !important;
}

.page-link:hover {
    background-color: #e8f0fe;
    color: #1a73e8;
}

.page-item.active .page-link {
    background-color: #1a73e8;
    border-color: #1a73e8;
}

/* Responsive Adjustments */
@media (max-width: 576px) {
    .notification-card {
        padding: 1rem;
    }

    .notification-icon {
        width: 32px;
        height: 32px;
    }

    .notification-icon i {
        font-size: 1rem;
    }

    .notification-title {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý đánh dấu đã đọc khi click vào thông báo
    document.querySelectorAll('.notification-card.unread').forEach(card => {
        card.addEventListener('click', function() {
            const notifId = this.dataset.id;
            markAsRead(notifId, this);
        });
    });

    // Xử lý nút đánh dấu tất cả đã đọc
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllAsRead();
        });
    }

    // Hàm đánh dấu một thông báo đã đọc
    function markAsRead(notifId, element) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notifId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('unread');
                const badge = element.querySelector('.badge');
                if (badge) badge.remove();
                updateUnreadCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Hàm đánh dấu tất cả thông báo đã đọc
    function markAllAsRead() {
        fetch('mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-card.unread').forEach(card => {
                    card.classList.remove('unread');
                    const badge = card.querySelector('.badge');
                    if (badge) badge.remove();
                });
                updateUnreadCount();

                const markAllReadBtn = document.querySelector('.mark-all-read');
                if (markAllReadBtn) markAllReadBtn.style.display = 'none';
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Hàm cập nhật số thông báo chưa đọc
    function updateUnreadCount() {
        fetch('check_notifications.php')
            .then(response => response.json())
            .then(data => {
                const headerBadge = document.querySelector('#unread-notifications-count');
                if (headerBadge) {
                    if (data.unread_count > 0) {
                        headerBadge.textContent = data.unread_count;
                        headerBadge.style.display = 'inline-block';
                    } else {
                        headerBadge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
