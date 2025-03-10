-- Bảng settings
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng contact_messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT,
    replied_at TIMESTAMP NULL,
    parent_id INT DEFAULT NULL,
    has_reply TINYINT(1) DEFAULT 0,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES contact_messages(id) ON DELETE CASCADE
);

-- Bảng roles (vai trò)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng role_permissions (quyền của vai trò)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Bảng activity_log (lịch sử hoạt động)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tạo bảng categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm cột category_id vào bảng documents
ALTER TABLE `documents`
ADD COLUMN `category_id` int(11) DEFAULT NULL,
ADD CONSTRAINT `fk_documents_category`
FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
ON DELETE SET NULL;

-- Thêm một số cài đặt mặc định
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Hệ thống quản lý tài liệu'),
('site_description', 'Hệ thống quản lý và chia sẻ tài liệu'),
('admin_email', 'admin@example.com'),
('items_per_page', '10'),
('maintenance_mode', '0'),
('max_file_size', '5'),
('allowed_file_types', 'pdf,doc,docx'),
('enable_file_scan', '0'),
('allow_registration', '1'),
('email_verification', '0'),
('default_user_role', 'user'),
('enable_comments', '1'),
('comment_moderation', '0'),
('spam_keywords', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Thêm vai trò mặc định
INSERT INTO roles (name) VALUES
('admin'),
('editor'),
('user')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Thêm quyền cho vai trò admin
INSERT INTO role_permissions (role_id, permission)
SELECT id, permission
FROM roles r
CROSS JOIN (
    SELECT 'view_documents' as permission UNION ALL
    SELECT 'upload_documents' UNION ALL
    SELECT 'edit_documents' UNION ALL
    SELECT 'delete_documents' UNION ALL
    SELECT 'manage_users' UNION ALL
    SELECT 'manage_roles' UNION ALL
    SELECT 'manage_settings'
) p
WHERE r.name = 'admin';