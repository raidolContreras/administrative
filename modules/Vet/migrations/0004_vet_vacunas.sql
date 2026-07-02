-- @UP
CREATE TABLE vet_vacunas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pet_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    applied_at DATE NOT NULL,
    dose VARCHAR(60) NULL,
    next_dose_at DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_vet_vacunas_pet_id (pet_id),
    KEY idx_vet_vacunas_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS vet_vacunas;
