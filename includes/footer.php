<!-- Footer -->
<style>
html {
    height: 100%;
}

body {
    min-height: 100%;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1 0 auto;
}

footer {
    flex-shrink: 0;
    background-color: #212529;
    color: #fff;
    padding: 2rem 0;
    margin-top: auto;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 2rem;
}

.footer-section {
    flex: 1;
    min-width: 250px;
}

.footer-section h5 {
    color: #fff;
    margin-bottom: 1rem;
    font-weight: 600;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section ul li a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section ul li a:hover {
    color: #fff;
}

.footer-bottom {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.social-links a {
    color: rgba(255, 255, 255, 0.7);
    margin-right: 1rem;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.social-links a:hover {
    color: #fff;
}
</style>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h5>Về chúng tôi</h5>
                <p>Hệ thống quản lý tài liệu trực tuyến, nơi chia sẻ và lưu trữ tài liệu an toàn, tiện lợi.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/giapzech"><i class="fab fa-facebook"></i></a>
                    <a href="https://x.com/AnimeCute41004"><i class="fab fa-twitter"></i></a>
                    <a href="https://github.com/megait004"><i class="fab fa-linkedin"></i></a>
                    <a href="https://github.com/megait004"><i class="fab fa-github"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h5>Liên kết nhanh</h5>
                <ul>
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="documents.php">Tài liệu</a></li>
                    <li><a href="upload.php">Tải lên</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h5>Thông tin liên hệ</h5>
                <ul>
                    <li><i class="fas fa-map-marker-alt me-2"></i> Ngõ 42, Trần Bình, Hà Nội</li>
                    <li><i class="fas fa-phone me-2"></i> (84) 0528286001</li>
                    <li><i class="fas fa-envelope me-2"></i> Nguyễn Nguyên Giáp</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">© 2025 Hệ thống quản lý tài liệu Copyright © Giapztech 2025</p>
            <small>
                <a href="#" class="text-muted text-decoration-none">Điều khoản sử dụng</a> |
                <a href="#" class="text-muted text-decoration-none">Chính sách bảo mật</a>
            </small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<!-- Custom JS -->
<script>
// Thêm class active cho nav-link hiện tại
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });
});
</script>