<?php
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../../classes/Notification.php';
$notification = new Notification($conn);
$notifications = $notification->getLatest($_SESSION['admin_id']);
$unread_count = $notification->getUnreadCount($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hệ thống quản lý tài liệu</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Admin CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <nav class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-shield-alt me-2"></i>Admin Panel</h2>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>" href="documents.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Quản lý tài liệu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                    <i class="fas fa-comments"></i>
                    <span>Bình luận</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>" href="contacts.php">
                    <i class="fas fa-envelope"></i>
                    <span>Liên hệ</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Báo cáo</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white admin-navbar">
        <div class="container-fluid">
            <button class="btn btn-link d-lg-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand d-lg-none" href="#">
                <i class="fas fa-shield-alt me-2"></i>Admin
            </a>

            <ul class="navbar-nav ms-auto">
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">thông báo chưa đọc</span>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                        <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                            <h6 class="mb-0">Thông báo</h6>
                            <?php if (!empty($notifications)): ?>
                                <a href="#" class="text-primary text-decoration-none mark-all-read">
                                    <small>Đánh dấu đã đọc</small>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="notifications-list">
                            <?php if (empty($notifications)): ?>
                                <div class="dropdown-item text-center text-muted py-3">
                                    <i class="fas fa-bell-slash mb-2"></i>
                                    <p class="mb-0">Không có thông báo mới</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link'] ?? '#'); ?>"
                                       class="dropdown-item notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                                       data-id="<?php echo $notif['id']; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 notification-icon">
                                                <?php
                                                $icon_class = match ($notif['type'] ?? '') {
                                                    'comment' => 'fa-comment',
                                                    'like' => 'fa-heart',
                                                    'document' => 'fa-file-alt',
                                                    'system' => 'fa-cog',
                                                    default => 'fa-bell'
                                                };
                                                ?>
                                                <i class="fas <?php echo $icon_class; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-1 notification-text">
                                                    <?php echo htmlspecialchars($notif['message']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo $notification->getTimeAgo($notif['created_at']); ?>
                                                </small>
                                            </div>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge bg-primary ms-2">Mới</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($notifications)): ?>
                            <div class="dropdown-divider my-0"></div>
                            <a href="notifications.php" class="dropdown-item text-center py-2">
                                <small>Xem tất cả thông báo</small>
                            </a>
                        <?php endif; ?>
                    </div>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-fw me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../index.php">
                                <i class="fas fa-home me-2"></i>Trang chủ
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Cài đặt
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

    <!-- JavaScript for Sidebar Toggle -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminSidebar = document.querySelector('.admin-sidebar');
        const adminContent = document.querySelector('.admin-content');
        const adminNavbar = document.querySelector('.admin-navbar');

        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!adminSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    adminSidebar.classList.remove('show');
                }
            }
        });

        // Handle notifications
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('unread')) return;
                const notifId = this.dataset.id;
                fetch('mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notifId
                }).then(() => {
                    this.classList.remove('unread');
                    updateUnreadCount();
                });
            });
        });

        // Mark all notifications as read
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch('mark_all_notifications_read.php', {
                    method: 'POST'
                }).then(() => {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    updateUnreadCount();
                });
            });
        }

        function updateUnreadCount() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.nav-link .badge');
                    if (data.unread_count > 0) {
                        if (badge) {
                            badge.textContent = data.unread_count;
                        } else {
                            const navLink = document.querySelector('.nav-link');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            newBadge.textContent = data.unread_count;
                            navLink.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                });
        }
    });
    </script>

    <style>
    /* Notifications Dropdown Styles */
    .notifications-dropdown {
        width: 320px;
        max-height: 480px;
        overflow-y: auto;
        padding: 0;
    }

    .notifications-list {
        max-height: 360px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 0.75rem 1rem;
        border-left: 3px solid transparent;
        white-space: normal;
    }

    .notification-item:hover {
        background-color: rgba(0,0,0,.05);
    }

    .notification-item.unread {
        background-color: #e8f0fe;
        border-left-color: #1a73e8;
    }

    .notification-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: 50%;
        color: #6c757d;
    }

    .notification-text {
        font-size: 0.875rem;
        line-height: 1.4;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .unread .notification-icon {
        background-color: #1a73e8;
        color: white;
    }

    /* Custom Scrollbar for Webkit Browsers */
    .notifications-list::-webkit-scrollbar {
        width: 6px;
    }

    .notifications-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .notifications-list::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .notifications-list::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    /* Badge Position Adjustment */
    .nav-link .badge {
        font-size: 0.65rem;
        padding: 0.25em 0.45em;
    }

    @media (max-width: 576px) {
        .notifications-dropdown {
            width: 300px;
        }
    }
    </style>
</body>
</html>