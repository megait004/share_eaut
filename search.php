<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

$search = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? 0;
$sort = $_GET['sort'] ?? 'latest';

// Xây dựng câu truy vấn cơ bản
$query = "
    SELECT d.*, u.full_name as uploader_name, c.name as category_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT cm.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments cm ON d.id = cm.document_id
";

// Thêm điều kiện tìm kiếm
$params = [];
$conditions = ["d.status = 'approved'"];

if (!empty($search)) {
    $conditions[] = "(d.title LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id) {
    $conditions[] = "d.category_id = ?";
    $params[] = $category_id;
}

$query .= " WHERE " . implode(" AND ", $conditions);

$query .= " GROUP BY d.id, d.title, d.description, d.filename, d.created_at, d.user_id, d.category_id, u.full_name, c.name";

// Thêm sắp xếp
switch ($sort) {
    case 'popular':
        $query .= " ORDER BY (COUNT(DISTINCT l.id) + COUNT(DISTINCT cm.id)) DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY d.created_at ASC";
        break;
    default:
        $query .= " ORDER BY d.created_at DESC";
}

// Thực hiện truy vấn
$stmt = $conn->prepare($query);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Lấy danh sách danh mục cho filter
$categories = $conn->query("
    SELECT c.*, COUNT(d.id) as document_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name ASC
")->fetchAll();

// Include header
include 'includes/header.php';
?>

<!-- Search Section -->
<style>
.search-section {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    padding: 4rem 0 2rem;
    position: relative;
    overflow: hidden;
}

.search-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.05)" width="100" height="100"/></svg>');
    opacity: 0.05;
}

.search-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 2rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.search-form {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    margin-bottom: 3rem;
}

.form-control, .form-select {
    border: 2px solid #E2E8F0;
    border-radius: 15px;
    padding: 0.8rem 1.5rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-btn {
    padding: 0.8rem 2.5rem;
    font-size: 1.1rem;
    border-radius: 50px;
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.results-section {
    background: #F8FAFC;
    padding: 4rem 0;
    min-height: 60vh;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.results-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2D3748;
    margin: 0;
}

.results-count {
    font-size: 1rem;
    color: #6366F1;
    background: rgba(99, 102, 241, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 50px;
}

.document-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
}

.document-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.document-card .card-body {
    padding: 1.5rem;
}

.document-card .card-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.document-card .card-title a {
    color: #2D3748;
    text-decoration: none;
    transition: color 0.3s ease;
}

.document-card .card-title a:hover {
    color: #6366F1;
}

.document-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #E2E8F0;
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
}

.badge.bg-primary {
    background: rgba(99, 102, 241, 0.1) !important;
    color: #6366F1;
}

.badge.bg-secondary {
    background: rgba(100, 116, 139, 0.1) !important;
    color: #64748B;
}

.empty-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.empty-results i {
    font-size: 4rem;
    color: #CBD5E0;
    margin-bottom: 1.5rem;
}

.empty-results h4 {
    color: #2D3748;
    margin-bottom: 1rem;
}

.empty-results p {
    color: #718096;
    margin: 0;
}
</style>

<section class="search-section">
    <div class="container">
        <h1 class="search-title" data-aos="fade-up">Tìm kiếm tài liệu</h1>

        <!-- Search Form -->
        <div class="search-form" data-aos="fade-up" data-aos-delay="100">
            <form action="search.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0"
                                   placeholder="Nhập từ khóa tìm kiếm..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    (<?php echo $category['document_count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select">
                            <option value="latest" <?php echo $sort == 'latest' ? 'selected' : ''; ?>>
                                <i class="fas fa-clock"></i> Mới nhất
                            </option>
                            <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>
                                <i class="fas fa-history"></i> Cũ nhất
                            </option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>
                                <i class="fas fa-fire"></i> Phổ biến nhất
                            </option>
                        </select>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search me-2"></i>Tìm kiếm
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Results Section -->
<section class="results-section">
    <div class="container">
        <div class="results-header" data-aos="fade-up">
            <h3 class="results-title">
                <?php if (!empty($search)): ?>
                    <i class="fas fa-search me-2"></i>
                    Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    <i class="fas fa-file-alt me-2"></i>
                    Tất cả tài liệu
                <?php endif; ?>
            </h3>
            <span class="results-count">
                <i class="fas fa-layer-group me-2"></i>
                <?php echo count($documents); ?> kết quả
            </span>
        </div>

        <?php if (empty($documents)): ?>
            <div class="empty-results" data-aos="fade-up">
                <i class="fas fa-search"></i>
                <h4>Không tìm thấy kết quả</h4>
                <p>Không tìm thấy tài liệu nào phù hợp với tiêu chí tìm kiếm của bạn.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($documents as $index => $doc): ?>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="document-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="document.php?id=<?php echo $doc['id']; ?>">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars(substr($doc['description'], 0, 100)) . '...'; ?>
                                </p>
                                <div class="document-meta">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['uploader_name']); ?>&background=random"
                                         alt="<?php echo htmlspecialchars($doc['uploader_name']); ?>"
                                         class="rounded-circle" width="32" height="32">
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-folder-open me-1"></i>
                                            <?php echo htmlspecialchars($doc['category_name']); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-heart me-1"></i><?php echo $doc['like_count']; ?>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-comment me-1"></i><?php echo $doc['comment_count']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Add AOS Animation -->
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