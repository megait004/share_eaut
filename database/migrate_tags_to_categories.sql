-- Chuyển dữ liệu từ tags sang categories
INSERT INTO categories (name, created_at)
SELECT name, created_at FROM tags
ON DUPLICATE KEY UPDATE name = tags.name;

-- Cập nhật category_id trong bảng documents
UPDATE documents d
JOIN document_tags dt ON d.id = dt.document_id
JOIN tags t ON dt.tag_id = t.id
JOIN categories c ON c.name = t.name
SET d.category_id = c.id;

-- Xóa bảng document_tags và tags (chỉ thực hiện sau khi đã kiểm tra dữ liệu chuyển đổi thành công)
-- DROP TABLE IF EXISTS document_tags;
-- DROP TABLE IF EXISTS tags;