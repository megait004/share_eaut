<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

$auth = new Auth($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy danh sách tài liệu của người dùng
$stmt = $conn->prepare("
    SELECT d.*, c.name as category_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT cm.id) as comment_count
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments cm ON d.id = cm.document_id
    WHERE d.user_id = ?
    GROUP BY d.id
    ORDER BY d.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$documents = $stmt->fetchAll();

// Include header
require_once 'includes/header.php';
?>

<style>
.my-docs-header {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    padding: 3rem 0;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
}

.my-docs-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.05)" width="100" height="100"/></svg>');
    opacity: 0.05;
}

.my-docs-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.upload-btn {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: 50px;
    background: white;
    color: #4F46E5;
    border: none;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    color: #4F46E5;
}

.document-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    overflow: hidden;
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

.document-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
    padding: 1rem 0;
    border-top: 1px solid #E2E8F0;
    border-bottom: 1px solid #E2E8F0;
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

.badge.bg-info {
    background: rgba(56, 189, 248, 0.1) !important;
    color: #0EA5E9;
}

.document-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
}

.btn-edit {
    color: #6366F1;
    background: rgba(99, 102, 241, 0.1);
    border: none;
}

.btn-edit:hover {
    background: rgba(99, 102, 241, 0.2);
    color: #6366F1;
}

.btn-delete {
    color: #EF4444;
    background: rgba(239, 68, 68, 0.1);
    border: none;
}

.btn-delete:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #EF4444;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.empty-state i {
    font-size: 4rem;
    color: #CBD5E0;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: #2D3748;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #718096;
    margin-bottom: 2rem;
}

.modal-content {
    border: none;
    border-radius: 15px;
}

.modal-header {
    background: #F8FAFC;
    border-bottom: none;
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: none;
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: #2D3748;
}

.btn-modal {
    padding: 0.8rem 1.5rem;
    border-radius: 50px;
    font-weight: 500;
}

.btn-modal.btn-secondary {
    background: #E2E8F0;
    border: none;
    color: #4A5568;
}

.btn-modal.btn-danger {
    background: #EF4444;
    border: none;
}
</style>

<section class="my-docs-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center" data-aos="fade-up">
            <h1 class="my-docs-title">
                <i class="fas fa-folder-open me-3"></i>Tài liệu của tôi
            </h1>
            <a href="upload.php" class="upload-btn">
                <i class="fas fa-cloud-upload-alt me-2"></i>Tải lên tài liệu mới
            </a>
        </div>
    </div>
</section>

<div class="container">
    <?php if (empty($documents)): ?>
        <div class="empty-state" data-aos="fade-up">
            <i class="fas fa-file-alt"></i>
            <h3>Bạn chưa có tài liệu nào</h3>
            <p>Hãy bắt đầu bằng cách tải lên tài liệu đầu tiên của bạn</p>
            <a href="upload.php" class="upload-btn">
                <i class="fas fa-cloud-upload-alt me-2"></i>Tải lên ngay
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($documents as $index => $doc): ?>
                <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="document-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                    <?php echo htmlspecialchars($doc['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($doc['description'], 0, 100)) . '...'; ?>
                            </p>
                            <?php if ($doc['category_name']): ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-folder me-1"></i>
                                    <?php echo htmlspecialchars($doc['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <div class="document-meta">
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <i class="far fa-calendar text-muted me-1"></i>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($doc['created_at'])); ?>
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
                            <div class="document-actions">
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>"
                                   class="btn btn-action btn-edit">
                                    <i class="fas fa-edit me-1"></i>Sửa
                                </a>
                                <button type="button" class="btn btn-action btn-delete"
                                        onclick="confirmDelete(<?php echo $doc['id']; ?>)">
                                    <i class="fas fa-trash-alt me-1"></i>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Xác nhận xóa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc chắn muốn xóa tài liệu này không? Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Hủy
                </button>
                <form id="deleteForm" method="POST" action="delete_document.php" class="d-inline">
                    <input type="hidden" name="document_id" id="deleteDocumentId">
                    <button type="submit" class="btn btn-modal btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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

function confirmDelete(documentId) {
    document.getElementById('deleteDocumentId').value = documentId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>