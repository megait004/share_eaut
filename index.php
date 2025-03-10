<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách tài liệu mới nhất
$latest_docs = $conn->query("
    SELECT d.*, u.full_name as uploader_name, c.name as category_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c2.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c2 ON d.id = c2.document_id
    WHERE d.status = 'approved'
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 5
")->fetchAll();

// Lấy danh sách danh mục
$categories = $conn->query("
    SELECT c.*, COUNT(d.id) as document_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

// Lấy số liệu thống kê
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM documents) as total_documents,
        (SELECT COUNT(*) FROM categories) as total_categories,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM comments) as total_comments
")->fetch();

// Kiểm tra xem người dùng đã đăng nhập chưa
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero bg-gradient text-white py-5" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="display-4 fw-bold mb-4 text-shadow text-black">Chào mừng đến với Hệ thống Quản lý Tài liệu</h1>
                <p class="lead mb-4 text-shadow text-black">Nơi lưu trữ và chia sẻ tài liệu an toàn, tiện lợi với cộng đồng</p>
                <?php if (!$user): ?>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg px-4 py-2 rounded-pill shadow-sm">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill">
                            <i class="fas fa-user-plus me-2"></i>Đăng ký
                        </a>
                    </div>
                <?php else: ?>
                    <a href="upload.php" class="btn btn-light btn-lg px-4 py-2 rounded-pill shadow-sm">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Tải lên tài liệu
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="position-relative">
                  <img src="assets/images/hero.png" alt="Hero Image">

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-item p-4 rounded-3 shadow-sm bg-white">
                    <div class="display-4 fw-bold text-primary mb-2">
                        <i class="fas fa-file-alt mb-3"></i>
                        <div class="counter"><?php echo number_format($stats['total_documents']); ?></div>
                    </div>
                    <p class="text-muted mb-0">Tài liệu</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-item p-4 rounded-3 shadow-sm bg-white">
                    <div class="display-4 fw-bold text-success mb-2">
                        <i class="fas fa-folder mb-3"></i>
                        <div class="counter"><?php echo number_format($stats['total_categories']); ?></div>
                    </div>
                    <p class="text-muted mb-0">Danh mục</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-item p-4 rounded-3 shadow-sm bg-white">
                    <div class="display-4 fw-bold text-info mb-2">
                        <i class="fas fa-users mb-3"></i>
                        <div class="counter"><?php echo number_format($stats['total_users']); ?></div>
                    </div>
                    <p class="text-muted mb-0">Người dùng</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-item p-4 rounded-3 shadow-sm bg-white">
                    <div class="display-4 fw-bold text-warning mb-2">
                        <i class="fas fa-comments mb-3"></i>
                        <div class="counter"><?php echo number_format($stats['total_comments']); ?></div>
                    </div>
                    <p class="text-muted mb-0">Bình luận</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Documents Section -->
<section class="latest-documents py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold" data-aos="fade-up">
            <i class="fas fa-star text-warning me-2"></i>Tài liệu mới nhất
        </h2>
        <div class="row">
            <?php foreach ($latest_docs as $index => $doc): ?>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                    <i class="fas fa-file-alt text-primary"></i>
                                </div>
                                <h5 class="card-title mb-0">
                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="text-decoration-none text-dark hover-primary">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h5>
                            </div>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($doc['description'], 0, 100)) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['uploader_name']); ?>&background=random"
                                         alt="<?php echo htmlspecialchars($doc['uploader_name']); ?>"
                                         class="rounded-circle me-2"
                                         width="30">
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-primary rounded-pill">
                                        <i class="fas fa-heart me-1"></i><?php echo $doc['like_count']; ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill ms-1">
                                        <i class="fas fa-comment me-1"></i><?php echo $doc['comment_count']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <small class="text-muted">
                                <i class="fas fa-folder me-1"></i>
                                <?php echo htmlspecialchars($doc['category_name']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="documents.php" class="btn btn-primary btn-lg rounded-pill px-5">
                <i class="fas fa-search me-2"></i>Xem tất cả tài liệu
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories py-5">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold" data-aos="fade-up">
            <i class="fas fa-folder-open text-primary me-2"></i>Danh mục tài liệu
        </h2>
        <div class="row">
            <?php foreach ($categories as $index => $category): ?>
                <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-4 mx-auto mb-3" style="width: fit-content;">
                                <i class="fas fa-folder-open text-primary fa-2x"></i>
                            </div>
                            <h5 class="card-title">
                                <a href="category.php?id=<?php echo $category['id']; ?>" class="text-decoration-none text-dark hover-primary">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </h5>
                            <p class="card-text mt-3">
                                <span class="badge bg-primary rounded-pill px-3 py-2">
                                    <i class="fas fa-file-alt me-1"></i>
                                    <?php echo $category['document_count']; ?> tài liệu
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.bg-gradient {
    position: relative;
    overflow: hidden;
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.05)" width="100" height="100"/></svg>');
    opacity: 0.05;
}

.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}

.hover-primary {
    transition: color 0.3s ease;
}

.hover-primary:hover {
    color: #6366F1 !important;
}

.transition-all {
    transition: all 0.3s ease;
}

.counter {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-item {
    background: white;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.1)!important;
}

.stat-item i {
    font-size: 2rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.latest-documents {
    background: #F8FAFC;
    padding: 5rem 0;
}

.categories {
    background: white;
    padding: 5rem 0;
}

.card {
    border: none;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.1)!important;
}
</style>

<!-- Add AOS Animation Library -->
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

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php include 'includes/footer.php'; ?>