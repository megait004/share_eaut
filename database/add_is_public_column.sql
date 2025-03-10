ALTER TABLE documents
ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái công khai của tài liệu (1: công khai, 0: riêng tư)';

-- Cập nhật tất cả tài liệu hiện có thành công khai
UPDATE documents SET is_public = 1;