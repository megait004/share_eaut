-- Backup created at 2025-03-09 13:01:22
-- Database: document_management
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


--
-- Cấu trúc bảng `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `activities`
--

INSERT INTO `activities` VALUES ('1','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 16:38:14');
INSERT INTO `activities` VALUES ('2','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 16:46:26');
INSERT INTO `activities` VALUES ('3','2','admin@example.com','login','Admin logged in successfully','::1','2025-03-05 16:58:27');
INSERT INTO `activities` VALUES ('4','2','admin@example.com','login','Admin logged in successfully','::1','2025-03-05 16:59:57');
INSERT INTO `activities` VALUES ('5','2','admin@example.com','login','Admin logged in successfully','::1','2025-03-05 17:12:59');
INSERT INTO `activities` VALUES ('6','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 17:28:33');
INSERT INTO `activities` VALUES ('7','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 17:38:33');
INSERT INTO `activities` VALUES ('8','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 17:39:18');
INSERT INTO `activities` VALUES ('9','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 17:42:54');
INSERT INTO `activities` VALUES ('10','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 17:43:34');
INSERT INTO `activities` VALUES ('11','1','giapfc123@gmail.com','change_password','User changed password','::1','2025-03-05 17:45:51');
INSERT INTO `activities` VALUES ('12','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 17:45:55');
INSERT INTO `activities` VALUES ('13','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 17:46:01');
INSERT INTO `activities` VALUES ('14','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 17:51:15');
INSERT INTO `activities` VALUES ('15','3','doanh@gmail.com','login','User logged in successfully','::1','2025-03-05 17:51:43');
INSERT INTO `activities` VALUES ('16','3','doanh@gmail.com','logout','User logged out','::1','2025-03-05 17:55:49');
INSERT INTO `activities` VALUES ('17','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 17:55:58');
INSERT INTO `activities` VALUES ('18','3','doanh@gmail.com','login','User logged in successfully','::1','2025-03-05 17:57:31');
INSERT INTO `activities` VALUES ('19','3','doanh@gmail.com','logout','User logged out','::1','2025-03-05 17:58:55');
INSERT INTO `activities` VALUES ('20','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 17:59:01');
INSERT INTO `activities` VALUES ('21','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 18:02:18');
INSERT INTO `activities` VALUES ('22','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 18:02:26');
INSERT INTO `activities` VALUES ('23','2','admin@example.com','logout','User logged out','::1','2025-03-05 18:03:05');
INSERT INTO `activities` VALUES ('26','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 18:04:04');
INSERT INTO `activities` VALUES ('27','2','admin@example.com','logout','User logged out','::1','2025-03-05 18:08:05');
INSERT INTO `activities` VALUES ('28','2','admin@example.com','login','Admin logged in successfully','::1','2025-03-05 18:08:34');
INSERT INTO `activities` VALUES ('29','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 18:10:04');
INSERT INTO `activities` VALUES ('30','5','eaut20221477@gmail.com','login','User logged in successfully','::1','2025-03-05 18:10:55');
INSERT INTO `activities` VALUES ('31','5','eaut20221477@gmail.com','logout','User logged out','::1','2025-03-05 18:11:25');
INSERT INTO `activities` VALUES ('32','6','20221477@eaut.edu.vn','login','User logged in successfully','::1','2025-03-05 18:11:45');
INSERT INTO `activities` VALUES ('33','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 18:12:43');
INSERT INTO `activities` VALUES ('34','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 18:13:04');
INSERT INTO `activities` VALUES ('35','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 18:13:09');
INSERT INTO `activities` VALUES ('36','2','admin@example.com','logout','User logged out','::1','2025-03-05 18:31:25');
INSERT INTO `activities` VALUES ('37','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 18:31:30');
INSERT INTO `activities` VALUES ('38','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 18:31:51');
INSERT INTO `activities` VALUES ('39','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 22:33:49');
INSERT INTO `activities` VALUES ('40','2','admin@example.com','logout','User logged out','::1','2025-03-05 22:35:07');
INSERT INTO `activities` VALUES ('41','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 22:35:15');
INSERT INTO `activities` VALUES ('42','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-05 22:37:07');
INSERT INTO `activities` VALUES ('43','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 22:37:16');
INSERT INTO `activities` VALUES ('44','2','admin@example.com','login','User logged in successfully','::1','2025-03-05 22:45:38');
INSERT INTO `activities` VALUES ('45','2','admin@example.com','logout','User logged out','::1','2025-03-05 22:51:57');
INSERT INTO `activities` VALUES ('46','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-05 23:00:03');
INSERT INTO `activities` VALUES ('47','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-06 00:27:58');
INSERT INTO `activities` VALUES ('48','2','admin@example.com','login','User logged in successfully','::1','2025-03-06 00:28:09');
INSERT INTO `activities` VALUES ('49','2','admin@example.com','logout','User logged out','::1','2025-03-06 00:28:27');
INSERT INTO `activities` VALUES ('50','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-06 00:28:38');
INSERT INTO `activities` VALUES ('51','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-06 00:49:40');
INSERT INTO `activities` VALUES ('52','2','admin@example.com','login','User logged in successfully','::1','2025-03-06 00:52:14');
INSERT INTO `activities` VALUES ('53','2','admin@example.com','logout','User logged out','::1','2025-03-06 00:52:42');
INSERT INTO `activities` VALUES ('54','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-06 00:56:47');
INSERT INTO `activities` VALUES ('55','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-06 01:16:49');
INSERT INTO `activities` VALUES ('56','2','admin@example.com','login','User logged in successfully','::1','2025-03-06 01:16:58');
INSERT INTO `activities` VALUES ('57','2','admin@example.com','login','User logged in successfully','::1','2025-03-06 14:31:50');
INSERT INTO `activities` VALUES ('58','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 12:47:38');
INSERT INTO `activities` VALUES ('59','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 13:33:30');
INSERT INTO `activities` VALUES ('60','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 13:33:40');
INSERT INTO `activities` VALUES ('61','2','admin@example.com','logout','User logged out','::1','2025-03-09 13:35:41');
INSERT INTO `activities` VALUES ('62','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 13:36:51');
INSERT INTO `activities` VALUES ('63','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 13:50:50');
INSERT INTO `activities` VALUES ('64','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 13:56:08');
INSERT INTO `activities` VALUES ('65','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 13:56:44');
INSERT INTO `activities` VALUES ('66','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 13:57:03');
INSERT INTO `activities` VALUES ('67','2','admin@example.com','logout','User logged out','::1','2025-03-09 13:57:53');
INSERT INTO `activities` VALUES ('68','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 15:45:53');
INSERT INTO `activities` VALUES ('69','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 15:48:40');
INSERT INTO `activities` VALUES ('70','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 15:59:28');
INSERT INTO `activities` VALUES ('71','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 16:00:05');
INSERT INTO `activities` VALUES ('72','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 16:00:11');
INSERT INTO `activities` VALUES ('73','2','admin@example.com','logout','User logged out','::1','2025-03-09 16:01:09');
INSERT INTO `activities` VALUES ('74','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 16:01:14');
INSERT INTO `activities` VALUES ('75','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 16:01:35');
INSERT INTO `activities` VALUES ('76','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 16:01:42');
INSERT INTO `activities` VALUES ('77','1','giapfc123@gmail.com','login','User logged in successfully','::1','2025-03-09 18:41:55');
INSERT INTO `activities` VALUES ('78','1','giapfc123@gmail.com','logout','User logged out','::1','2025-03-09 19:45:00');
INSERT INTO `activities` VALUES ('79','2','admin@example.com','login','User logged in successfully','::1','2025-03-09 19:45:08');


--
-- Cấu trúc bảng `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Cấu trúc bảng `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` VALUES ('1','toán','tài liệu toán','2025-03-09 13:51:19','2025-03-09 13:51:19');
INSERT INTO `categories` VALUES ('5','english',NULL,'2025-03-06 00:31:19','2025-03-09 19:07:51');


--
-- Cấu trúc bảng `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Cấu trúc bảng `document_tags`
--

DROP TABLE IF EXISTS `document_tags`;
CREATE TABLE `document_tags` (
  `document_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`document_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `document_tags_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Cấu trúc bảng `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `download_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `visibility` enum('public','private') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `downloads` int DEFAULT '0',
  `category_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_documents_category` (`category_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_documents_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `documents`
--

INSERT INTO `documents` VALUES ('14','pdf',NULL,NULL,'ẻererere','67cd55648d856_1741509988.pdf','Đề thi kết thúc học phần Lập trình ứng dụng với Python.pdf','245065','application/pdf','1','2025-03-09 15:46:28','2025-03-09 15:46:28','public','0','1');
INSERT INTO `documents` VALUES ('15','bai tap',NULL,NULL,'xin chao','67cd588516996_1741510789.pdf','Đề thi kết thúc học phần Lập trình ứng dụng với Python.pdf','245065','application/pdf','1','2025-03-09 15:59:49','2025-03-09 18:42:05','public','1','1');
INSERT INTO `documents` VALUES ('16','english',NULL,NULL,'hay','67cd84bac0bd6_1741522106.docx','UNIT 1-2-TEST QUESTIONS.docx','25163','application/vnd.openxmlformats-officedocument.wordprocessingml.document','1','2025-03-09 19:08:26','2025-03-09 19:08:26','public','0','5');
INSERT INTO `documents` VALUES ('17','ffff',NULL,NULL,'ffff','67cd85c38c3a7_1741522371.pdf','Đề thi kết thúc học phần Lập trình ứng dụng với Python (1).pdf','245065','application/pdf','1','2025-03-09 19:12:51','2025-03-09 19:12:51','public','0','1');


--
-- Cấu trúc bảng `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_user_unique` (`document_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `likes`
--

INSERT INTO `likes` VALUES ('8','14','1','2025-03-09 15:46:45');


--
-- Cấu trúc bảng `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` VALUES ('1',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=4','0','2025-03-05 18:41:23');
INSERT INTO `notifications` VALUES ('2','2','comment_approved','Bình luận của bạn trên tài liệu \'em bé\' đã được phê duyệt','view_document.php?id=5','0','2025-03-05 18:46:59');
INSERT INTO `notifications` VALUES ('3','2','comment_approved','Bình luận của bạn trên tài liệu \'ảnh đẹp\' đã được phê duyệt','view_document.php?id=4','0','2025-03-05 18:47:00');
INSERT INTO `notifications` VALUES ('4',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=5','0','2025-03-05 18:47:58');
INSERT INTO `notifications` VALUES ('5','2','comment_approved','Bình luận của bạn trên tài liệu \'em bé\' đã được phê duyệt','view_document.php?id=5','0','2025-03-05 18:48:06');
INSERT INTO `notifications` VALUES ('6',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=4','0','2025-03-05 22:35:27');
INSERT INTO `notifications` VALUES ('7','1','comment_approved','Bình luận của bạn trên tài liệu \'ảnh đẹp\' đã được phê duyệt','view_document.php?id=4','0','2025-03-05 22:37:35');
INSERT INTO `notifications` VALUES ('8',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=1','0','2025-03-06 00:18:30');
INSERT INTO `notifications` VALUES ('9',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=1','0','2025-03-06 00:18:33');
INSERT INTO `notifications` VALUES ('10',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=1','0','2025-03-06 00:18:34');
INSERT INTO `notifications` VALUES ('11',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=2','0','2025-03-06 00:20:20');
INSERT INTO `notifications` VALUES ('12','1','comment_approved','Bình luận của bạn trên tài liệu \'sqlite\' đã được phê duyệt','view_document.php?id=2','0','2025-03-06 00:28:19');
INSERT INTO `notifications` VALUES ('13','1','comment_approved','Bình luận của bạn trên tài liệu \'fdfdf\' đã được phê duyệt','view_document.php?id=1','0','2025-03-06 00:28:20');
INSERT INTO `notifications` VALUES ('14','1','comment_approved','Bình luận của bạn trên tài liệu \'fdfdf\' đã được phê duyệt','view_document.php?id=1','0','2025-03-06 00:28:21');
INSERT INTO `notifications` VALUES ('15','1','comment_approved','Bình luận của bạn trên tài liệu \'fdfdf\' đã được phê duyệt','view_document.php?id=1','0','2025-03-06 00:28:21');
INSERT INTO `notifications` VALUES ('16',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=6','0','2025-03-06 01:52:34');
INSERT INTO `notifications` VALUES ('17','2','comment_approved','Bình luận của bạn trên tài liệu \'ảnh\' đã được phê duyệt','view_document.php?id=6','0','2025-03-06 01:52:42');
INSERT INTO `notifications` VALUES ('18',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=7','0','2025-03-06 01:53:20');
INSERT INTO `notifications` VALUES ('19','2','document_comment','Giáp đã bình luận về tài liệu của bạn','view_document.php?id=7','0','2025-03-09 12:47:50');
INSERT INTO `notifications` VALUES ('20','1','document_like','System Administrator đã thích tài liệu của bạn','view_document.php?id=3','0','2025-03-09 13:35:03');
INSERT INTO `notifications` VALUES ('21','1','document_comment','System Administrator đã bình luận về tài liệu của bạn','view_document.php?id=3','0','2025-03-09 13:35:11');
INSERT INTO `notifications` VALUES ('22',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=15','0','2025-03-09 16:01:19');
INSERT INTO `notifications` VALUES ('23',NULL,'new_comment','Bình luận mới cần duyệt từ Giáp','admin/comments.php?document_id=15','0','2025-03-09 16:01:27');
INSERT INTO `notifications` VALUES ('24','1','comment_approved','Bình luận của bạn trên tài liệu \'bai tap\' đã được phê duyệt','view_document.php?id=15','0','2025-03-09 16:02:02');
INSERT INTO `notifications` VALUES ('25',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=16','0','2025-03-09 19:49:10');
INSERT INTO `notifications` VALUES ('26','2','comment_approved','Bình luận của bạn trên tài liệu \'english\' đã được phê duyệt','view_document.php?id=16','0','2025-03-09 19:49:15');
INSERT INTO `notifications` VALUES ('27',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=17','0','2025-03-09 19:49:33');
INSERT INTO `notifications` VALUES ('28',NULL,'new_comment','Bình luận mới cần duyệt từ System Administrator','admin/comments.php?document_id=17','0','2025-03-09 19:49:59');


--
-- Cấu trúc bảng `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` int NOT NULL,
  `permission` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `role_permissions`
--

INSERT INTO `role_permissions` VALUES ('1','delete_documents','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','edit_documents','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','manage_roles','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','manage_settings','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','manage_users','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','upload_documents','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('1','view_documents','2025-03-05 17:18:37');
INSERT INTO `role_permissions` VALUES ('3','delete_documents','2025-03-05 17:18:29');
INSERT INTO `role_permissions` VALUES ('3','edit_documents','2025-03-05 17:18:29');
INSERT INTO `role_permissions` VALUES ('3','upload_documents','2025-03-05 17:18:29');
INSERT INTO `role_permissions` VALUES ('3','view_documents','2025-03-05 17:18:29');


--
-- Cấu trúc bảng `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` VALUES ('1','admin','2025-03-05 16:20:36','2025-03-05 16:20:36');
INSERT INTO `roles` VALUES ('2','editor','2025-03-05 16:20:36','2025-03-05 16:20:36');
INSERT INTO `roles` VALUES ('3','user','2025-03-05 16:20:36','2025-03-05 16:20:36');


--
-- Cấu trúc bảng `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` VALUES ('1','allow_registration','1','2025-03-05 18:10:24','2025-03-05 18:10:24');
INSERT INTO `settings` VALUES ('2','require_email_verification','1','2025-03-05 18:10:24','2025-03-05 18:10:24');
INSERT INTO `settings` VALUES ('3','default_user_role','user','2025-03-05 18:10:24','2025-03-05 18:10:24');
INSERT INTO `settings` VALUES ('4','allow_comments','1','2025-03-05 18:10:24','2025-03-05 18:10:24');
INSERT INTO `settings` VALUES ('5','moderate_comments','0','2025-03-05 18:10:24','2025-03-09 19:49:25');
INSERT INTO `settings` VALUES ('6','spam_keywords','cấm','2025-03-05 18:10:24','2025-03-09 16:01:06');


--
-- Cấu trúc bảng `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tags`
--

INSERT INTO `tags` VALUES ('1','php','2025-03-05 16:44:17');
INSERT INTO `tags` VALUES ('2','img','2025-03-05 17:28:54');
INSERT INTO `tags` VALUES ('3','file excel','2025-03-05 17:50:43');
INSERT INTO `tags` VALUES ('4','english','2025-03-06 00:31:19');
INSERT INTO `tags` VALUES ('5','hay','2025-03-06 00:32:25');
INSERT INTO `tags` VALUES ('6','sách giáo khoa','2025-03-06 00:34:56');
INSERT INTO `tags` VALUES ('7','hi','2025-03-06 00:37:55');
INSERT INTO `tags` VALUES ('8','hh','2025-03-06 00:40:32');
INSERT INTO `tags` VALUES ('9','fdfdf','2025-03-06 00:40:51');
INSERT INTO `tags` VALUES ('10','fdfdffdfdf','2025-03-06 01:21:57');
INSERT INTO `tags` VALUES ('11','anime','2025-03-06 01:25:12');
INSERT INTO `tags` VALUES ('12','gggg','2025-03-06 01:25:41');
INSERT INTO `tags` VALUES ('13','oooo','2025-03-06 01:29:22');


--
-- Cấu trúc bảng `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','banned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` VALUES ('1','giapfc123@gmail.com','$2y$10$oST75XLNtreI4h.0bvzu7ObWFXVfcioZuUzLmyXNwsRi9AiY6n68q','3','Giáp','active','2025-03-05 16:32:57','2025-03-05 17:45:51');
INSERT INTO `users` VALUES ('2','admin@example.com','$2y$10$is7ulEq/iVs8FzkfIIqf3.vrTkbyM1ycHvCySuqthT.xS62WnqfTa','1','System Administrator','active','2025-03-05 16:47:47','2025-03-05 16:47:47');
INSERT INTO `users` VALUES ('3','doanh@gmail.com','$2y$10$RqiapmiN8b5axxYfMnoIuehOEBlNizjbU2lM7DpC/Q7Qxy9se5muC','3','Nguyên Viết Doanh','active','2025-03-05 17:51:40','2025-03-05 17:51:40');
INSERT INTO `users` VALUES ('5','eaut20221477@gmail.com','$2y$10$5GAjp1.IjIKLvy0tbLym2uiZjWFQtme8wgpU9vQoyADsOHVNFTjuu','3','nguyen van a','inactive','2025-03-05 18:10:48','2025-03-05 18:10:48');
INSERT INTO `users` VALUES ('6','20221477@eaut.edu.vn','$2y$10$neHtOtVuoLfMoZBxF5EZw.TYn1mA0IpAqYKIb7l7aKBIjR5kHeVLu','3','eaut','inactive','2025-03-05 18:11:40','2025-03-05 18:11:40');


SET FOREIGN_KEY_CHECKS=1;
