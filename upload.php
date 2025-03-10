<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Document.php';
require_once 'classes/Settings.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

$auth = new Auth($conn);
$auth->requireLogin();

$document = new Document($conn);
$max_file_size = 50 * 1024 * 1024; // 50MB

// Get current user ID based on session
$current_user_id = $auth->getCurrentId();

// Khởi tạo các biến
$errors = [];
$title = '';
$description = '';
$category_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedFile = $_FILES['file'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = !empty($_POST['category']) ? (int)$_POST['category'] : null;
    $visibility = $_POST['visibility'] ?? 'public';

    if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Vui lòng chọn file để tải lên";
    } elseif ($uploadedFile['size'] > $max_file_size) {
        $errors[] = "File không được vượt quá 50MB";
    }

    if (empty($title)) {
        $errors[] = "Vui lòng nhập tiêu đề tài liệu";
    }

    // Kiểm tra category_id có tồn tại không
    if ($category_id !== null) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if ($stmt->rowCount() === 0) {
            $errors[] = "Danh mục không tồn tại";
        }
    }

    if (empty($errors)) {
        try {
            $result = $document->upload($uploadedFile, $title, $description, $current_user_id, $category_id);
            if ($result) {
                $_SESSION['success'] = "Tải lên tài liệu thành công!";
                header("Location: view_document.php?id=" . $result);
                exit();
            } else {
                $errors[] = "Có lỗi xảy ra khi tải file lên";
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Include header
include 'includes/header.php';
?>

<style>
.upload-header {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    padding: 3rem 0;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
}

.upload-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.05)" width="100" height="100"/></svg>');
    opacity: 0.05;
}

.upload-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.upload-subtitle {
    color: rgba(255, 255, 255, 0.9);
    margin-top: 1rem;
    font-size: 1.1rem;
}

.upload-container {
    max-width: 800px;
    margin: -2rem auto 2rem;
    position: relative;
    z-index: 1;
}

.upload-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    padding: 2rem;
}

.upload-area {
    border: 2px dashed rgba(99, 102, 241, 0.3);
    border-radius: 15px;
    padding: 3rem 2rem;
    text-align: center;
    background: rgba(99, 102, 241, 0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
}

.upload-area:hover, .upload-area.dragover {
    border-color: #6366F1;
    background: rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.upload-icon {
    font-size: 4rem;
    color: #6366F1;
    margin-bottom: 1.5rem;
    opacity: 0.8;
}

.upload-text {
    font-size: 1.25rem;
    color: #2D3748;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.upload-hint {
    color: #718096;
    font-size: 0.95rem;
    margin-bottom: 0;
}

.selected-file {
    background: white;
    border-radius: 15px;
    padding: 1.25rem;
    margin: 1rem 0;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.selected-file:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.file-info {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.file-icon {
    font-size: 1.75rem;
    color: #6366F1;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 12px;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #2D3748;
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.file-size {
    color: #718096;
    font-size: 0.9rem;
}

.btn-remove {
    background: rgba(239, 68, 68, 0.1);
    border: none;
    color: #EF4444;
    cursor: pointer;
    padding: 0.75rem;
    font-size: 1.1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-remove:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #DC2626;
    transform: translateY(-2px);
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    margin-top: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2D3748;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: #6366F1;
}

.form-label {
    font-weight: 500;
    color: #4A5568;
    margin-bottom: 0.75rem;
}

.form-label span {
    color: #EF4444;
}

.form-control, .form-select {
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.btn-upload-submit {
    width: 100%;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    border: none;
    color: white;
    margin-top: 2rem;
    transition: all 0.3s ease;
}

.btn-upload-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
}

.alert {
    border: none;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 2rem;
    background: rgba(239, 68, 68, 0.1);
    color: #DC2626;
}

.alert ul {
    margin-bottom: 0;
    padding-left: 1.5rem;
}

@media (max-width: 768px) {
    .upload-container {
        margin: 1rem;
    }

    .upload-card {
        padding: 1.5rem;
    }

    .upload-area {
        padding: 2rem 1.5rem;
    }

    .upload-icon {
        font-size: 3rem;
    }
}
</style>

<section class="upload-header">
    <div class="container">
        <h1 class="upload-title" data-aos="fade-up">
            <i class="fas fa-cloud-upload-alt me-3"></i>Tải lên tài liệu mới
        </h1>
        <p class="upload-subtitle" data-aos="fade-up" data-aos-delay="100">
            Chia sẻ tài liệu của bạn với cộng đồng
        </p>
    </div>
</section>

<div class="container">
    <div class="upload-container">
        <div class="upload-card" data-aos="fade-up" data-aos-delay="200">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="dropZone" onclick="document.getElementById('fileInput').click();">
                    <input type="file" id="fileInput" name="file" class="file-input"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" style="display: none;">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3 class="upload-text">Kéo thả file hoặc click để tải lên</h3>
                    <p class="upload-hint">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR (Tối đa 50MB)</p>
                </div>

                <div class="selected-files"></div>

                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Thông tin tài liệu
                    </h4>

                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề tài liệu <span>*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?php echo htmlspecialchars($title); ?>"
                               placeholder="Nhập tiêu đề tài liệu">
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Danh mục</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                        <?php echo ($category_id !== null && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description"
                                  placeholder="Nhập mô tả chi tiết về tài liệu..."><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-upload-submit">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Tải lên tài liệu
                    </button>
                </div>
            </form>
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
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const selectedFiles = document.querySelector('.selected-files');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    // Handle selected files
    fileInput.addEventListener('change', handleFiles, false);

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropZone.classList.remove('dragover');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles({ target: { files: files } });
    }

    function handleFiles(e) {
        const files = e.target.files;
        if (files.length > 0) {
            updateFileList(files[0]);
            fileInput.files = files;
        }
    }

    function updateFileList(file) {
        const fileSize = formatFileSize(file.size);
        const fileType = file.type || 'unknown';
        const fileIcon = getFileIcon(file.name);

        selectedFiles.innerHTML = `
            <div class="selected-file">
                <div class="file-info">
                    <div class="file-icon"><i class="${fileIcon}"></i></div>
                    <div>
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${fileSize} - ${fileType}</div>
                    </div>
                </div>
                <button type="button" class="btn-remove" onclick="removeFile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileIcon(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'docx': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'xlsx': 'fas fa-file-excel',
            'txt': 'fas fa-file-alt',
            'zip': 'fas fa-file-archive',
            'rar': 'fas fa-file-archive'
        };
        return icons[extension] || 'fas fa-file';
    }
});

function removeFile() {
    document.getElementById('fileInput').value = '';
    document.querySelector('.selected-files').innerHTML = '';
}
</script>

<?php include 'includes/footer.php'; ?>