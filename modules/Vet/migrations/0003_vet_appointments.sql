-- @UP
CREATE TABLE vet_appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pet_id BIGINT UNSIGNED NOT NULL,
    scheduled_at DATETIME NOT NULL,
    reason VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'programada',
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_vet_appt_pet (pet_id),
    KEY idx_vet_appt_when (scheduled_at),
    KEY idx_vet_appt_status (status),
    CONSTRAINT fk_vet_appt_pet FOREIGN KEY (pet_id) REFERENCES vet_pets (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS vet_appointments;
