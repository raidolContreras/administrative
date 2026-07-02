-- @UP
CREATE TABLE attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    disk VARCHAR(10) NOT NULL DEFAULT 'private',
    path VARCHAR(255) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL DEFAULT 0,
    entity_type VARCHAR(60) NULL,
    entity_id VARCHAR(40) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_attachments_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS attachments;
