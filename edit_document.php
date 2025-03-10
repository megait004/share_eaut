<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'classes/Settings.php';
require_once 'classes/Notification.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để sửa tài liệu!";
    header("Location: login.php");
    exit();
}

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);

// Lấy ID tài liệu
$document_id = $_GET['id'] ?? 0;

// Lấy thông tin tài liệu
$query = "
    SELECT d.*, u.full_name as uploader_name, c.name as category_name, c.id as category_id
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch();

// Kiểm tra tài liệu tồn tại
if (!$document) {
    $_SESSION['error'] = "Không tìm thấy tài liệu!";
    header("Location: index.php");
    exit();
}

// Kiểm tra quyền sửa tài liệu
if ($document['user_id'] != $_SESSION['user_id'] && !isset($_SESSION['is_admin'])) {
    $_SESSION['error'] = "Bạn không có quyền sửa tài liệu này!";
    header("Location: view_document.php?id=" . $document_id);
    exit();
}

// Lấy danh sách danh mục
$category_query = "SELECT id, name FROM categories ORDER BY name";
$category_stmt = $conn->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll();

// Xử lý cập nhật tài liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $visibility = isset($_POST['is_public']) ? 'public' : 'private';

    // Validate dữ liệu
    $errors = [];
    if (empty($title)) {
        $errors[] = "Tiêu đề không được để trống";
    }
    if (empty($content)) {
        $errors[] = "Nội dung không được để trống";
    }

    if (empty($errors)) {
        // Cập nhật tài liệu
        $update_query = "
            UPDATE documents
            SET title = ?, content = ?, category_id = ?, visibility = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        $update_stmt = $conn->prepare($update_query);

        if ($update_stmt->execute([$title, $content, $category_id, $visibility, $document_id])) {
            $_SESSION['success'] = "Cập nhật tài liệu thành công!";

            // Tạo thông báo
            if ($document['user_id'] != $_SESSION['user_id']) {
                $notification->create(
                    'document_updated',
                    "Tài liệu '" . $title . "' đã được cập nhật",
                    $document['user_id'],
                    "view_document.php?id=" . $document_id
                );
            }

            header("Location: view_document.php?id=" . $document_id);
            exit();
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật tài liệu!";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title">Sửa tài liệu</h1>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="<?php echo htmlspecialchars($document['title'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="content" name="content" rows="10"
                                      required><?php echo htmlspecialchars($document['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Danh mục</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo ($category['id'] == ($document['category_id'] ?? null)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_public" name="is_public"
                                   <?php echo ($document['visibility'] ?? 'public') === 'public' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_public">Công khai tài liệu</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                            <a href="view_document.php?id=<?php echo $document_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>