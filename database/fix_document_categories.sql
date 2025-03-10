-- Cập nhật category_id cho các tài liệu dựa trên tên file
UPDATE documents d
JOIN categories c ON d.title LIKE CONCAT('%', c.name, '%')
SET d.category_id = c.id
WHERE d.category_id IS NULL;

-- Hoặc có thể cập nhật thủ công cho từng tài liệu cụ thể
-- UPDATE documents SET category_id = (SELECT id FROM categories WHERE name = 'php') WHERE title LIKE '%php%';
-- UPDATE documents SET category_id = (SELECT id FROM categories WHERE name = 'python') WHERE title LIKE '%python%';