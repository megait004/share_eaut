<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Settings.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

$settings = Settings::getInstance($conn);
$success_message = '';
$error_message = '';

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Cài đặt người dùng
        $settings->set('allow_registration', $_POST['allow_registration'] ?? '0');
        $settings->set('require_email_verification', $_POST['require_email_verification'] ?? '0');
        $settings->set('default_user_role', $_POST['default_user_role'] ?? 'user');

        // Cài đặt kiểm duyệt
        $settings->setDocumentModeration($_POST['require_document_moderation'] ?? '0');
        $settings->set('moderate_comments', $_POST['moderate_comments'] ?? '0');

        // Cài đặt bình luận
        $settings->set('allow_comments', $_POST['allow_comments'] ?? '0');
        $settings->set('spam_keywords', $_POST['spam_keywords'] ?? '');

        $success_message = "Đã cập nhật cài đặt thành công!";
    } catch (Exception $e) {
        $error_message = "Có lỗi xảy ra: " . $e->getMessage();
    }
}

// Lấy danh sách roles cho dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY name")->fetchAll();

require_once 'includes/admin_header.php';
?>

<style>
.settings-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.settings-card .card-header {
    background: linear-gradient(45deg, #4b6cb7, #182848);
    color: white;
    border-bottom: none;
    padding: 1rem;
}

.settings-card .card-header h4 {
    font-size: 1.2rem;
    font-weight: 600;
}

.settings-card .card-body {
    padding: 1.5rem;
}

.form-check-input:checked {
    background-color: #4b6cb7;
    border-color: #4b6cb7;
}

.form-select:focus, .form-control:focus {
    border-color: #4b6cb7;
    box-shadow: 0 0 0 0.25rem rgba(75, 108, 183, 0.25);
}

.btn-primary {
    background: linear-gradient(45deg, #4b6cb7, #182848);
    border: none;
    padding: 0.8rem 2rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    background: linear-gradient(45deg, #182848, #4b6cb7);
}

.alert {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-check-label {
    font-weight: 500;
}

.section-title {
    color: #182848;
    font-weight: 600;
    margin-bottom: 2rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.section-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: linear-gradient(45deg, #4b6cb7, #182848);
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="section-title">Cài đặt hệ thống</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-users-cog me-2"></i>Cài đặt người dùng</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="allow_registration"
                                       name="allow_registration" value="1"
                                       <?php echo $settings->isRegistrationAllowed() ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_registration">Cho phép đăng ký</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="require_email_verification"
                                       name="require_email_verification" value="1"
                                       <?php echo $settings->isEmailVerificationRequired() ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_email_verification">Yêu cầu xác thực email</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="default_user_role" class="form-label">Vai trò mặc định cho người dùng mới</label>
                            <select class="form-select" id="default_user_role" name="default_user_role">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['name']; ?>"
                                            <?php echo $settings->getDefaultUserRole() === $role['name'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Cài đặt kiểm duyệt</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="require_document_moderation"
                                       name="require_document_moderation" value="1"
                                       <?php echo $settings->isDocumentModerationEnabled() ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_document_moderation">
                                    Yêu cầu kiểm duyệt tài liệu trước khi đăng
                                </label>
                            </div>
                            <small class="text-muted">
                                Khi bật, tài liệu mới đăng sẽ cần được admin duyệt trước khi hiển thị công khai
                            </small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="moderate_comments"
                                       name="moderate_comments" value="1"
                                       <?php echo $settings->areCommentsModerated() ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="moderate_comments">
                                    Kiểm duyệt bình luận
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Cài đặt bình luận</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="allow_comments"
                                       name="allow_comments" value="1"
                                       <?php echo $settings->areCommentsAllowed() ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_comments">Cho phép bình luận</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spam_keywords" class="form-label">Từ khóa spam (phân cách bằng dấu phẩy)</label>
                            <textarea class="form-control" id="spam_keywords" name="spam_keywords" rows="3"
                                      placeholder="Ví dụ: sex, casino, gambling"><?php echo $settings->get('spam_keywords'); ?></textarea>
                            <div class="form-text">Các bình luận chứa những từ khóa này sẽ bị đánh dấu là spam</div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Lưu cài đặt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>