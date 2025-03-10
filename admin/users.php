<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

$user = new User($conn);

// Xử lý xóa người dùng
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id != $_SESSION['admin_id']) { // Không cho phép xóa chính mình
        $user->deleteUser($user_id);
    }
    header("Location: users.php");
    exit();
}

// Xử lý thay đổi vai trò
if (isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $user_id = $_POST['user_id'] ?? 0;
    $new_role_id = $_POST['role_id'] ?? '';
    if ($user_id != $_SESSION['admin_id']) {
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$new_role_id, $user_id]);
    }
    header("Location: users.php");
    exit();
}

// Lấy danh sách người dùng với thống kê
$users = $conn->query("
    SELECT u.*,
           r.name as role_name,
           COUNT(DISTINCT d.id) as document_count,
           COUNT(DISTINCT c.id) as comment_count,
           COUNT(DISTINCT l.id) as like_count
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    GROUP BY u.id, u.full_name, u.email, u.role_id, u.status, u.created_at, u.updated_at, r.name
    ORDER BY u.id DESC
")->fetchAll();

// Lấy danh sách roles cho dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY name")->fetchAll();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-3">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4 class="mb-0 text-primary">
                            <i class="fas fa-users me-2"></i>
                            Quản lý người dùng
                        </h4>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill">
                                <i class="fas fa-user me-1"></i>
                                <?php echo count($users); ?> người dùng
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="bg-light">
                                    <th class="border-0 rounded-start text-primary">ID</th>
                                    <th class="border-0 text-primary">Họ tên</th>
                                    <th class="border-0 text-primary">Email</th>
                                    <th class="border-0 text-primary">Vai trò</th>
                                    <th class="border-0 text-primary">Thống kê hoạt động</th>
                                    <th class="border-0 rounded-end text-primary text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $usr): ?>
                                <tr class="align-middle">
                                    <td class="fw-bold">#<?php echo $usr['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <?php echo htmlspecialchars($usr['full_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($usr['email']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usr['id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                            <select name="role_id" class="form-select form-select-sm d-inline-block w-auto border-primary" onchange="this.form.submit()">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>"
                                                            <?php echo $usr['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($role['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <?php else: ?>
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            <?php echo ucfirst($usr['role_name']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="d-flex align-items-center text-primary">
                                                <i class="fas fa-file-alt me-1"></i>
                                                <span class="fw-bold"><?php echo $usr['document_count']; ?></span>
                                            </div>
                                            <div class="d-flex align-items-center text-success">
                                                <i class="fas fa-comment me-1"></i>
                                                <span class="fw-bold"><?php echo $usr['comment_count']; ?></span>
                                            </div>
                                            <div class="d-flex align-items-center text-danger">
                                                <i class="fas fa-heart me-1"></i>
                                                <span class="fw-bold"><?php echo $usr['like_count']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($usr['id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                                <i class="fas fa-trash-alt me-1"></i>
                                                Xóa
                                            </button>
                                        </form>
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
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

select.form-select {
    transition: all 0.2s ease;
}

select.form-select:hover {
    border-color: #0d6efd;
}

.badge {
    transition: all 0.2s ease;
}

.badge:hover {
    opacity: 0.9;
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>