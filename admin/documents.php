<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Document.php';
require_once '../classes/Category.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

$document = new Document($conn);
$category = new Category($conn);

// Xử lý xóa tài liệu
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $document_id = $_POST['document_id'] ?? 0;
    try {
        if ($document->deleteDocument($document_id, $_SESSION['admin_id'])) {
            $_SESSION['success'] = "Đã xóa tài liệu thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: documents.php");
    exit();
}

// Xử lý thêm danh mục
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve_document':
            $document_id = $_POST['document_id'] ?? 0;
            try {
                $stmt = $conn->prepare("UPDATE documents SET status = 'approved' WHERE id = ?");
                if ($stmt->execute([$document_id])) {
                    // Lấy thông tin tài liệu và người đăng
                    $doc_info = $document->getDocumentById($document_id);
                    if ($doc_info) {
                        // Tạo thông báo cho người đăng
                        require_once '../classes/Notification.php';
                        $notification = new Notification($conn);
                        $notification->create(
                            'document_approved',
                            "Tài liệu '{$doc_info['title']}' của bạn đã được phê duyệt",
                            $doc_info['user_id'],
                            "view_document.php?id=" . $document_id
                        );
                    }
                    $_SESSION['success'] = "Đã phê duyệt tài liệu thành công!";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            }
            header("Location: documents.php");
            exit();
            break;

        case 'reject_document':
            $document_id = $_POST['document_id'] ?? 0;
            try {
                $stmt = $conn->prepare("UPDATE documents SET status = 'rejected' WHERE id = ?");
                if ($stmt->execute([$document_id])) {
                    // Lấy thông tin tài liệu và người đăng
                    $doc_info = $document->getDocumentById($document_id);
                    if ($doc_info) {
                        // Tạo thông báo cho người đăng
                        require_once '../classes/Notification.php';
                        $notification = new Notification($conn);
                        $notification->create(
                            'document_rejected',
                            "Tài liệu '{$doc_info['title']}' của bạn đã bị từ chối",
                            $doc_info['user_id'],
                            "view_document.php?id=" . $document_id
                        );
                    }
                    $_SESSION['success'] = "Đã từ chối tài liệu!";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            }
            header("Location: documents.php");
            exit();
            break;

        case 'add_category':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            try {
                $category->addCategory($name, $description);
                $_SESSION['success'] = "Thêm danh mục thành công!";
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            break;

        case 'edit_category':
            $id = $_POST['id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            try {
                $category->updateCategory($id, $name, $description);
                $_SESSION['success'] = "Cập nhật danh mục thành công!";
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            break;

        case 'delete_category':
            $id = $_POST['id'] ?? 0;
            try {
                $category->deleteCategory($id);
                $_SESSION['success'] = "Xóa danh mục thành công!";
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            break;
    }
    header("Location: documents.php");
    exit();
}

// Lấy danh sách tài liệu
$query = "
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count,
           cat.name as category_name
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    LEFT JOIN categories cat ON d.category_id = cat.id
    GROUP BY d.id, d.title, d.description, d.user_id, d.category_id, d.created_at, d.updated_at,
             u.full_name, cat.name
    ORDER BY d.created_at DESC
";

$documents = $conn->query($query)->fetchAll();

// Lấy danh sách danh mục
$categories = $category->getAllCategories();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Danh mục -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-gradient-primary border-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-folder me-2"></i>
                            Danh sách danh mục
                        </h5>
                        <button type="button" class="btn btn-light btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Thêm danh mục
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4">ID</th>
                                    <th class="border-0">Tên danh mục</th>
                                    <th class="border-0">Mô tả</th>
                                    <th class="border-0">Số tài liệu</th>
                                    <th class="border-0 text-end px-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr class="align-middle">
                                    <td class="px-4"><?php echo $cat['id']; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info rounded-pill px-3">
                                            <?php echo $cat['document_count']; ?> tài liệu
                                        </span>
                                    </td>
                                    <td class="text-end px-4">
                                        <button type="button" class="btn btn-primary btn-sm shadow-sm me-1"
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
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm shadow-sm">
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

            <!-- Tài liệu -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-gradient-primary border-0 py-3">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-file-alt me-2"></i>
                        Danh sách tài liệu
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4">ID</th>
                                    <th class="border-0">Tên tài liệu</th>
                                    <th class="border-0">Người đăng</th>
                                    <th class="border-0">Danh mục</th>
                                    <th class="border-0">Thống kê</th>
                                    <th class="border-0">Ngày đăng</th>
                                    <th class="border-0">Trạng thái</th>
                                    <th class="border-0 text-end px-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td class="px-4"><?php echo $doc['id']; ?></td>
                                    <td>
                                        <a href="../view_document.php?id=<?php echo $doc['id']; ?>"
                                           class="text-decoration-none fw-medium text-primary">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle text-muted me-2"></i>
                                            <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                            <?php echo htmlspecialchars($doc['category_name'] ?? 'Chưa phân loại'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="me-3">
                                            <i class="fas fa-heart text-danger"></i>
                                            <span class="ms-1"><?php echo $doc['like_count']; ?></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-comment text-primary"></i>
                                            <span class="ms-1"><?php echo $doc['comment_count']; ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt text-muted me-2"></i>
                                        <?php echo date('d/m/Y', strtotime($doc['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($doc['status'] == 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                                <i class="fas fa-check me-1"></i>Đã duyệt
                                            </span>
                                        <?php elseif ($doc['status'] == 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                                <i class="fas fa-times me-1"></i>Từ chối
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3">
                                                <i class="fas fa-clock me-1"></i>Chờ duyệt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="btn-group shadow-sm">
                                            <?php if ($doc['status'] == 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve_document">
                                                    <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Phê duyệt">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject_document">
                                                    <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Từ chối">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="../view_document.php?id=<?php echo $doc['id']; ?>"
                                               class="btn btn-info btn-sm" title="Xem">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
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

<!-- Modal thêm danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-folder-plus me-2"></i>
                    Thêm danh mục mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-2"></i>
                            Tên danh mục
                        </label>
                        <input type="text" class="form-control shadow-none" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i>
                            Mô tả
                        </label>
                        <textarea class="form-control shadow-none" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-save me-2"></i>
                        Thêm danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Sửa danh mục
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-2"></i>
                            Tên danh mục
                        </label>
                        <input type="text" class="form-control shadow-none" name="name" id="edit_category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i>
                            Mô tả
                        </label>
                        <textarea class="form-control shadow-none" name="description" id="edit_category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-save me-2"></i>
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe);
}

.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.btn-group .btn {
    border-radius: 0.375rem !important;
    margin: 0 0.125rem;
}

.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
}

.modal .btn-close {
    opacity: 1;
}

.alert {
    border-radius: 0.5rem;
}

.badge {
    font-weight: 500;
}
</style>

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

<?php require_once 'includes/admin_footer.php'; ?>