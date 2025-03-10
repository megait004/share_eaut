<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-sticky">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users me-2"></i>
                Quản lý người dùng
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'documents.php' ? 'active' : ''; ?>" href="documents.php">
                <i class="fas fa-file-alt me-2"></i>
                Quản lý tài liệu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                <i class="fas fa-comments me-2"></i>
                Bình luận
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'contacts.php' ? 'active' : ''; ?>" href="contacts.php">
                <i class="fas fa-envelope me-2"></i>
                Liên hệ
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>
                Báo cáo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-cog me-2"></i>
                Cài đặt
            </a>
        </li>
    </ul>
</div>