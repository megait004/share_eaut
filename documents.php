<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Lấy các tham số lọc và sắp xếp
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$sort = $_GET['sort'] ?? 'latest';
$page = $_GET['page'] ?? 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Lấy danh sách danh mục
$categories = $conn->query("
    SELECT c.*, COUNT(d.id) as doc_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

// Xây dựng câu truy vấn tài liệu
$query = "
    SELECT d.*, u.full_name as uploader_name, c.name as category_name
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.status = 'approved'
";

// Thêm điều kiện lọc theo danh mục nếu có
if ($category_id) {
    $query .= " AND d.category_id = " . $category_id;
}

// Thêm điều kiện sắp xếp
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY d.created_at ASC";
        break;
    case 'popular':
        $query .= " ORDER BY (SELECT COUNT(*) FROM likes l WHERE l.document_id = d.id) DESC";
        break;
    default:
        $query .= " ORDER BY d.created_at DESC";
}

$query .= " LIMIT $per_page OFFSET $offset";

$documents = $conn->query($query)->fetchAll();

// Đếm tổng số tài liệu để phân trang
$count_query = "SELECT COUNT(*) as total FROM documents d";
if ($category_id) {
    $count_query .= " WHERE d.category_id = " . $category_id;
}
$total_documents = $conn->query($count_query)->fetch()['total'];
$total_pages = ceil($total_documents / $per_page);

// Lấy thông tin category nếu đang lọc theo category
$current_category = null;
if ($category_id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $current_category = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_category ? htmlspecialchars($current_category['name']) : 'Tất cả tài liệu'; ?> - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .filter-sidebar {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .filter-sidebar .list-group-item {
            border: none;
            padding: 0.8rem 1rem;
            margin-bottom: 5px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .filter-sidebar .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .filter-sidebar .list-group-item.active {
            background-color: #6366F1;
            border-color: #6366F1;
        }

        .sort-dropdown .dropdown-item.active {
            background-color: #6366F1;
            color: #fff;
        }

        .document-card {
            height: 100%;
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .document-card .card-body {
            padding: 1.5rem;
        }

        .document-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .document-card .card-title a {
            color: #2D3748;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .document-card .card-title a:hover {
            color: #6366F1;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 50px;
        }

        .badge.bg-info {
            background-color: rgba(99, 102, 241, 0.1) !important;
            color: #6366F1;
        }

        .main-content {
            min-height: calc(100vh - 56px - 300px);
            padding: 3rem 0;
            background-color: #F7FAFC;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2D3748;
            margin-bottom: 0;
        }

        .sort-dropdown .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            border-color: #E2E8F0;
            color: #4A5568;
        }

        .sort-dropdown .btn:hover {
            background-color: #6366F1;
            border-color: #6366F1;
            color: #fff;
        }

        .pagination {
            gap: 5px;
        }

        .pagination .page-link {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            color: #4A5568;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background-color: #6366F1;
            color: #fff;
        }

        .pagination .page-link:hover {
            background-color: #EDF2F7;
            color: #2D3748;
            transform: translateY(-2px);
        }

        .document-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E2E8F0;
        }

        .document-meta img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .document-meta .meta-info {
            flex: 1;
        }

        .document-meta .meta-info p {
            margin: 0;
            line-height: 1.4;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: #6366F1;
            margin-bottom: 1rem;
        }

        .category-count {
            background-color: #EDF2F7;
            color: #4A5568;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row">
                <!-- Sidebar lọc -->
                <div class="col-md-3" data-aos="fade-right">
                    <div class="filter-sidebar">
                        <h5 class="fw-bold mb-4">Danh mục</h5>
                        <div class="list-group">
                            <a href="documents.php"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo !$category_id ? 'active' : ''; ?>">
                                <span><i class="fas fa-folder me-2"></i>Tất cả tài liệu</span>
                                <span class="category-count"><?php echo $total_documents; ?></span>
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="?category=<?php echo $category['id']; ?>"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                    <span><i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($category['name']); ?></span>
                                    <span class="category-count"><?php echo $category['doc_count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Danh sách tài liệu -->
                <div class="col-md-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="page-title" data-aos="fade-up">
                            <?php if ($current_category): ?>
                                <i class="fas fa-folder-open text-primary me-2"></i>
                                Tài liệu về "<?php echo htmlspecialchars($current_category['name']); ?>"
                            <?php else: ?>
                                <i class="fas fa-books text-primary me-2"></i>
                                Tất cả tài liệu
                            <?php endif; ?>
                        </h2>
                        <div class="sort-dropdown" data-aos="fade-left">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-2"></i>
                                <?php
                                switch ($sort) {
                                    case 'popular':
                                        echo 'Phổ biến nhất';
                                        break;
                                    case 'oldest':
                                        echo 'Cũ nhất';
                                        break;
                                    default:
                                        echo 'Mới nhất';
                                }
                                ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item <?php echo $sort === 'latest' ? 'active' : ''; ?>"
                                       href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>sort=latest">
                                        <i class="fas fa-clock me-2"></i>Mới nhất
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo $sort === 'oldest' ? 'active' : ''; ?>"
                                       href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>sort=oldest">
                                        <i class="fas fa-history me-2"></i>Cũ nhất
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo $sort === 'popular' ? 'active' : ''; ?>"
                                       href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>sort=popular">
                                        <i class="fas fa-fire me-2"></i>Phổ biến nhất
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <?php if (empty($documents)): ?>
                        <div class="empty-state" data-aos="fade-up">
                            <i class="fas fa-folder-open"></i>
                            <h3 class="mt-4">Không có tài liệu nào</h3>
                            <p class="text-muted">Chưa có tài liệu nào được thêm vào danh mục này.</p>
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($documents as $index => $doc): ?>
                                <div class="col" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                    <div class="card document-card">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                                    <?php echo htmlspecialchars($doc['title']); ?>
                                                </a>
                                            </h5>
                                            <?php if ($doc['category_name']): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-folder me-1"></i>
                                                    <?php echo htmlspecialchars($doc['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="document-meta">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['uploader_name']); ?>&background=random"
                                                     alt="<?php echo htmlspecialchars($doc['uploader_name']); ?>">
                                                <div class="meta-info">
                                                    <p class="text-muted mb-1">
                                                        <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                                    </p>
                                                    <p class="text-muted small">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Phân trang -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-5" data-aos="fade-up">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>page=<?php echo $i; ?>&sort=<?php echo $sort; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo $category_id ? "category=$category_id&" : ''; ?>page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html>