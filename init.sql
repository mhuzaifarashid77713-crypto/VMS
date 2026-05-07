-- VMS Database Auto Setup
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(150)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vaccines (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150)   NOT NULL,
    manufacturer VARCHAR(150)   NOT NULL,
    price        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    quantity     INT            NOT NULL DEFAULT 0,
    expiry_date  DATE           NOT NULL,
    description  TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin: email=admin@vms.com | password=admin123
INSERT IGNORE INTO users (full_name, email, password, role)
VALUES ('Admin', 'admin@vms.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
