<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

require_once 'classes/Settings.php';
require_once 'classes/Notification.php';

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);
$is_logged_in = isset($_SESSION['user_id']);

// Lấy ID tài liệu
$document_id = $_GET['id'] ?? 0;

// Xử lý thêm bình luận trước khi có bất kỳ output nào
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in && $settings->areCommentsAllowed()) {
    $content = $_POST['content'] ?? '';
    $current_user_id = $_SESSION['user_id'];
    $current_user_name = $_SESSION['user_name'];

    if (!empty($content)) {
        // Kiểm tra spam
        $is_spam = $settings->isCommentSpam($content);

        // Xác định trạng thái bình luận
        if ($is_spam) {
            $status = 'spam';
            $_SESSION['error'] = "Bình luận của bạn chứa từ khóa không được phép.";
        } else if ($settings->areCommentsModerated()) {
            $status = 'pending';
            $_SESSION['success'] = "Bình luận của bạn đang chờ kiểm duyệt.";
        } else {
            $status = 'approved';
            $_SESSION['success'] = "Đã thêm bình luận thành công.";
        }

        $stmt = $conn->prepare("
            INSERT INTO comments (document_id, user_id, content, status)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$document_id, $current_user_id, $content, $status])) {
            // Chỉ tạo thông báo nếu không phải là spam
            if ($status !== 'spam') {
                if ($status === 'pending') {
                    // Thông báo cho admin
                    $notification->create(
                        'new_comment',
                        "Bình luận mới cần duyệt từ " . $current_user_name,
                        null,
                        "admin/comments.php?document_id=" . $document_id
                    );
                } else {
                    // Thông báo cho người đăng tài liệu
                    if ($document['user_id'] != $current_user_id) {
                        $notification->create(
                            'document_comment',
                            $current_user_name . " đã bình luận về tài liệu của bạn",
                            $document['user_id'],
                            "view_document.php?id=" . $document_id
                        );
                    }
                }
            }
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi thêm bình luận";
        }

        header("Location: view_document.php?id=" . $document_id);
        exit();
    }
}

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

if (!$document) {
    $_SESSION['error'] = "Không tìm thấy tài liệu!";
    header("Location: index.php");
    exit();
}

// Include header sau khi xử lý logic
require_once 'includes/header.php';

// Lấy danh sách bình luận
$comments_sql = "
    SELECT c.*, u.full_name, u.email
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.document_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($comments_sql);
$stmt->execute([$document_id]);
$comments = $stmt->fetchAll();

// Xử lý like/unlike
if ($is_logged_in && isset($_POST['action'])) {
    if ($_POST['action'] === 'like') {
        $stmt = $conn->prepare("
            INSERT INTO likes (document_id, user_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
        ");
        if ($stmt->execute([$document_id, $_SESSION['user_id']])) {
            // Thông báo cho người đăng tài liệu
            if ($document['uploader_id'] != $_SESSION['user_id']) {
                $notification->create(
                    'document_like',
                    $_SESSION['user_name'] . " đã thích tài liệu của bạn",
                    $document['uploader_id'],
                    "view_document.php?id=" . $document_id
                );
            }
        }
    } elseif ($_POST['action'] === 'unlike') {
        $stmt = $conn->prepare("DELETE FROM likes WHERE document_id = ? AND user_id = ?");
        $stmt->execute([$document_id, $_SESSION['user_id']]);
    }
    exit();
}

// Kiểm tra user đã like chưa
$has_liked = false;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $has_liked = $stmt->fetchColumn() > 0;
}

// Lấy số lượt like
$stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE document_id = ?");
$stmt->execute([$document_id]);
$like_count = $stmt->fetchColumn();
?>

<!-- Thêm CSS vào phần head -->
<style>
.document-header {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.document-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" width="100" height="100"/></svg>');
    opacity: 0.1;
}

.document-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.document-meta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.document-meta img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.document-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50px;
    font-size: 0.9rem;
}

.document-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    overflow: hidden;
}

.document-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.document-actions {
    display: flex;
    gap: 1rem;
    margin: 2rem 0;
}

.btn-download {
    padding: 0.8rem 2rem;
    border-radius: 50px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.file-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: #F7FAFC;
    border-radius: 50px;
    color: #4A5568;
}

.category-badge {
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    background-color: rgba(99, 102, 241, 0.1);
    color: #6366F1;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.category-badge:hover {
    background-color: rgba(99, 102, 241, 0.2);
    color: #4F46E5;
}

.comments-section {
    margin-top: 3rem;
}

.comments-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.comment-form {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.comment-textarea {
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 1rem;
    resize: none;
    transition: all 0.3s ease;
}

.comment-textarea:focus {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.comment-item {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.comment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.comment-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-meta {
    flex: 1;
}

.comment-author {
    font-weight: 600;
    color: #2D3748;
    margin-bottom: 0.25rem;
}

.comment-date {
    font-size: 0.875rem;
    color: #718096;
}

.comment-content {
    color: #4A5568;
    line-height: 1.6;
}

.empty-comments {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.empty-comments i {
    font-size: 3rem;
    color: #CBD5E0;
    margin-bottom: 1rem;
}

.auth-prompt {
    text-align: center;
    padding: 2rem;
    background: #EBF4FF;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.auth-prompt a {
    color: #6366F1;
    text-decoration: none;
    font-weight: 500;
}

.auth-prompt a:hover {
    text-decoration: underline;
}
</style>

<!-- Thay thế nội dung cũ bằng giao diện mới -->
<div class="document-header">
    <div class="container">
        <div data-aos="fade-up">
            <h1 class="document-title"><?php echo htmlspecialchars($document['title']); ?></h1>
            <div class="document-meta">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($document['uploader_name']); ?>&background=random"
                     alt="<?php echo htmlspecialchars($document['uploader_name']); ?>">
                <div>
                    <div class="mb-1"><?php echo htmlspecialchars($document['uploader_name']); ?></div>
                    <div class="opacity-75">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('d/m/Y H:i', strtotime($document['created_at'])); ?>
                    </div>
                </div>
            </div>
            <div class="document-stats">
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span>2.5k lượt xem</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-heart"></i>
                    <span><?php echo $like_count; ?> lượt thích</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-comments"></i>
                    <span><?php echo count($comments); ?> bình luận</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Nội dung tài liệu -->
            <div class="document-content mb-4" data-aos="fade-up">
                <?php if ($document['file_type'] === 'application/pdf'): ?>
                    <iframe src="document.php?id=<?php echo $document['id']; ?>"
                            style="width: 100%; height: 800px; border-radius: 10px;" frameborder="0"></iframe>
                <?php else: ?>
                    <?php echo nl2br(htmlspecialchars($document['content'] ?? '')); ?>
                <?php endif; ?>
            </div>

            <!-- Thông tin và hành động -->
            <div class="document-actions" data-aos="fade-up">
                <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary btn-download">
                    <i class="fas fa-download me-2"></i>Tải xuống
                </a>
                <div class="file-info">
                    <i class="fas fa-file"></i>
                    <span><?php echo htmlspecialchars($document['original_filename'] ?? ''); ?></span>
                    <span>(<?php echo formatFileSize($document['file_size'] ?? 0); ?>)</span>
                </div>
            </div>

            <?php if ($document['category_name']): ?>
                <div class="mb-4" data-aos="fade-up">
                    <a href="documents.php?category=<?php echo $document['category_id']; ?>"
                       class="category-badge">
                        <i class="fas fa-folder me-2"></i>
                        <?php echo htmlspecialchars($document['category_name']); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Phần bình luận -->
            <div class="comments-section">
                <div class="comments-header" data-aos="fade-up">
                    <h3 class="mb-0">
                        <i class="fas fa-comments text-primary me-2"></i>
                        Bình luận (<?php echo count($comments); ?>)
                    </h3>
                </div>

                <?php if ($settings->areCommentsAllowed()): ?>
                    <?php if ($is_logged_in): ?>
                        <div class="comment-form" data-aos="fade-up">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <textarea class="form-control comment-textarea" name="content" rows="3" required
                                              placeholder="Chia sẻ suy nghĩ của bạn về tài liệu này..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-download">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi bình luận
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="auth-prompt" data-aos="fade-up">
                            <i class="fas fa-lock mb-3 text-primary"></i>
                            <h5>Đăng nhập để bình luận</h5>
                            <p class="mb-0">
                                Vui lòng <a href="login.php">đăng nhập</a> hoặc <a href="register.php">đăng ký</a>
                                để tham gia thảo luận
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning" data-aos="fade-up">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Tính năng bình luận đang bị vô hiệu hóa
                    </div>
                <?php endif; ?>

                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <div class="empty-comments" data-aos="fade-up">
                            <i class="fas fa-comments"></i>
                            <h4 class="mt-3">Chưa có bình luận nào</h4>
                            <p class="text-muted mb-0">Hãy là người đầu tiên bình luận về tài liệu này</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $index => $comment): ?>
                            <div class="comment-item" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                <div class="comment-header">
                                    <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower($comment['email'])); ?>?s=96&d=mp"
                                         class="comment-avatar" alt="<?php echo htmlspecialchars($comment['full_name']); ?>">
                                    <div class="comment-meta">
                                        <div class="comment-author">
                                            <?php echo htmlspecialchars($comment['full_name']); ?>
                                        </div>
                                        <div class="comment-date">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Có thể thêm sidebar với các tài liệu liên quan hoặc thông tin bổ sung -->
        </div>
    </div>
</div>

<!-- Thêm AOS Animation -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            once: true
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

<?php
// Thêm hàm format file size ở đầu file, sau phần require
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>