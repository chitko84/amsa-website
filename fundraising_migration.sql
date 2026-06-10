CREATE TABLE IF NOT EXISTS fundraising (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('published','draft') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS fundraising_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fundraising_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 1,
    KEY fk_fundraising_images_fundraising (fundraising_id),
    CONSTRAINT fk_fundraising_images_fundraising
        FOREIGN KEY (fundraising_id)
        REFERENCES fundraising(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
