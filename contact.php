<?php
session_start();
require_once 'config/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Xử lý gửi form liên hệ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $errors = [];

    // Validate
    if (empty($name)) $errors[] = "Vui lòng nhập họ tên";
    if (empty($email)) $errors[] = "Vui lòng nhập email";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ";
    if (empty($subject)) $errors[] = "Vui lòng nhập tiêu đề";
    if (empty($message)) $errors[] = "Vui lòng nhập nội dung";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO contact_messages (name, email, subject, message, status)
                VALUES (?, ?, ?, ?, 'new')
            ");

            if ($stmt->execute([$name, $email, $subject, $message])) {
                $_SESSION['success'] = "Tin nhắn của bạn đã được gửi thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.";
                header("Location: contact.php");
                exit();
            } else {
                $errors[] = "Không thể gửi tin nhắn. Vui lòng thử lại sau.";
            }
        } catch (Exception $e) {
            $errors[] = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Include header
include_once 'includes/header.php';
?>

<style>
.contact-hero {
    background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
    color: white;
    padding: 6rem 0;
    margin-bottom: 4rem;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/pattern.png');
    opacity: 0.15;
    z-index: 0;
    animation: moveBackground 20s linear infinite;
}

@keyframes moveBackground {
    0% { background-position: 0 0; }
    100% { background-position: 100% 100%; }
}

.contact-hero .container {
    position: relative;
    z-index: 1;
}

.contact-info-box {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    border: 2px solid transparent;
    height: 100%;
}

.contact-info-box:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 97, 242, 0.15);
    border-color: rgba(0, 97, 242, 0.1);
}

.contact-info-box i {
    font-size: 3rem;
    margin-bottom: 1.8rem;
    background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
    transition: all 0.3s ease;
}

.contact-info-box:hover i {
    transform: scale(1.1);
}

.contact-form {
    background: white;
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.contact-form:hover {
    border-color: rgba(0, 97, 242, 0.1);
    box-shadow: 0 20px 40px rgba(0, 97, 242, 0.12);
}

.form-control {
    border-radius: 12px;
    padding: 0.9rem 1.5rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #0061f2;
    box-shadow: 0 0 0 0.25rem rgba(0, 97, 242, 0.15);
    transform: translateY(-2px);
}

.btn-gradient {
    background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
    border: none;
    color: white;
    padding: 1rem 2.5rem;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0));
    transition: all 0.6s ease;
}

.btn-gradient:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 97, 242, 0.25);
    color: white;
}

.btn-gradient:hover::before {
    left: 100%;
}

.map-container {
    height: 450px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.map-container:hover {
    border-color: rgba(0, 97, 242, 0.1);
    box-shadow: 0 20px 40px rgba(0, 97, 242, 0.12);
}

.contact-info-title {
    color: #1a1f36;
    font-weight: 700;
    margin-bottom: 1.2rem;
    font-size: 1.4rem;
}

.contact-info-text {
    color: #506690;
    font-size: 1.1rem;
    line-height: 1.6;
}

.form-label {
    font-weight: 600;
    color: #1a1f36;
    margin-bottom: 0.7rem;
}

.alert {
    border-radius: 12px;
    padding: 1rem 1.5rem;
    border: none;
    margin-bottom: 2rem;
}

.alert-success {
    background-color: rgba(0, 186, 148, 0.1);
    color: #00ba94;
}

.alert-danger {
    background-color: rgba(231, 74, 59, 0.1);
    color: #e74a3b;
}

.display-4 {
    font-weight: 800;
    margin-bottom: 1.5rem;
    font-size: 3.5rem;
    line-height: 1.2;
}

.lead {
    font-size: 1.25rem;
    line-height: 1.8;
    opacity: 0.9;
}

.social-links {
    margin-top: 1.5rem;
}

.social-links a i {
    font-size: 1.2rem;
}

.social-links a:hover {
   color: rgba(255, 255, 255, 0.7);
    margin-right: 1rem;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Liên hệ với chúng tôi</h1>
                <p class="lead mb-0">Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy để lại thông tin, chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>
            </div>
            <div class="col-lg-6 d-none d-lg-block text-center">
                <img src="assets/images/pattern.png" alt="Contact" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</section>

<div class="container mb-5">
    <!-- Thông tin liên hệ -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="contact-info-box text-center">
                <i class="fas fa-map-marker-alt"></i>
                <h4 class="contact-info-title">Địa chỉ</h4>
                <p class="contact-info-text">
                    Ngõ 42, Trần Bình<br>
                    Hà Nội, Việt Nam
                </p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="contact-info-box text-center">
                <i class="fas fa-phone-alt"></i>
                <h4 class="contact-info-title">Điện thoại</h4>
                <p class="contact-info-text">
                    Hotline: (84) 0528286001<br>
                </p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="contact-info-box text-center">
                <i class="fas fa-envelope"></i>
                <h4 class="contact-info-title">Email & Mạng xã hội</h4>
                <p class="contact-info-text">
                    nguyennguyengiap@gmail.com
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form liên hệ -->
        <div class="col-lg-8 mb-4">
            <div class="contact-form">
                <h3 class="mb-4">Gửi tin nhắn cho chúng tôi</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                               value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="form-label">Nội dung tin nhắn</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                    </button>
                </form>
            </div>
        </div>

        <!-- Bản đồ -->
        <div class="col-lg-4 mb-4">
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3723.924145351047!2d105.78753807500338!3d21.036207280615345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ab32dd484c53%3A0x4201b89c8bdfd968!2zTmfDtSA0MiBUcuG6p24gQsOsbmgsIE3hu7kgxJDDrG5oLCBD4bqndSBHaeG6pXksIEjDoCBO4buZaSwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1709799544334!5m2!1svi!2s"
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>