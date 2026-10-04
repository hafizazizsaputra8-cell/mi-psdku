CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12, 2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT products_price_non_negative CHECK (price >= 0)
);

CREATE INDEX products_name_index ON products (name);

INSERT INTO products (name, description, price, stock, image) VALUES

(
    'Laptop kerja 14 inch',
    'Laptop ringkas untuk kebutuhan kerja dan kuliah',
    8750000.00,
    6,
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
),

(
    'Webcam full HD',
    'Kamera full HD untuk rapat dan pembelajaran online',
    425000.00,
    14,
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
),

(
    'USB-C hub 6-in-1',
    'Hub USB-C dengan HDMI, USB, dan pembaca kartu',
    315000.00,
    18,
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
),

(
    'SSD eksternal 1TB',
    'Penyimpanan eksternal cepat dengan kapasitas 1TB',
    1125000.00,
    9,
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
),

(
    'Stand laptop aluminium',
    'Stand laptop ergonomis dengan bahan aluminium',
    275000.00,
    12,
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
);