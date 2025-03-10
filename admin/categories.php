<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Category.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

$category = new Category($conn);

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);

                // Check if category exists
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Category already exists!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    if ($stmt->execute([$name, $description])) {
                        $_SESSION['success'] = "Category added successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to add category!";
                    }
                }
                break;

            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);

                // Check if category exists (excluding current)
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Category name already exists!";
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $id])) {
                        $_SESSION['success'] = "Category updated successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to update category!";
                    }
                }
                break;

            case 'delete':
                $id = $_POST['id'];

                // Check if category has documents
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM documents WHERE category_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetch()['count'];

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete category with associated documents!";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $_SESSION['success'] = "Category deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete category!";
                    }
                }
                break;
        }

        header('Location: categories.php');
        exit();
    }
}

// Get all categories with document counts
$stmt = $conn->prepare("
    SELECT c.*, COUNT(d.id) as document_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Start output buffering for the content
ob_start();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý danh mục</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus me-2"></i>Thêm danh mục mới
    </button>
</div>

<!-- Categories Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">ID</th>
                        <th class="border-0">Tên danh mục</th>
                        <th class="border-0">Mô tả</th>
                        <th class="border-0">Số tài liệu</th>
                        <th class="border-0">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $cat['document_count']; ?> tài liệu
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary me-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editCategoryModal"
                                    data-id="<?php echo $cat['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($cat['name'] ?? ''); ?>"
                                    data-description="<?php echo htmlspecialchars($cat['description'] ?? ''); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($cat['document_count'] == 0): ?>
                            <form method="POST" action="" class="d-inline"
                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm danh mục mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm danh mục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" name="name" id="edit_category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" name="description" id="edit_category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý modal sửa danh mục
    const editCategoryModal = document.getElementById('editCategoryModal');
    if (editCategoryModal) {
        editCategoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');

            const modal = this;
            modal.querySelector('#edit_category_id').value = id;
            modal.querySelector('#edit_category_name').value = name;
            modal.querySelector('#edit_category_description').value = description;
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/admin_layout.php';
?>