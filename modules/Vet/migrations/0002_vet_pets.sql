-- @UP
CREATE TABLE vet_pets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    species VARCHAR(40) NOT NULL DEFAULT 'perro',
    breed VARCHAR(80) NULL,
    sex CHAR(1) NULL,
    birth_date DATE NULL,
    weight_kg DECIMAL(5,2) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_vet_pets_owner (owner_id),
    KEY idx_vet_pets_name (name),
    CONSTRAINT fk_vet_pets_owner FOREIGN KEY (owner_id) REFERENCES vet_owners (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS vet_pets;
