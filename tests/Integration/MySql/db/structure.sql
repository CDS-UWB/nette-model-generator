-- Simple MySQL structure for model generator integration tests

CREATE TABLE test_basic_columns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    text_value VARCHAR(255) NOT NULL,
    optional_text TEXT,
    bool_value BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE test_enum_columns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status ENUM('new', 'in_progress', 'done') NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium'
);

CREATE TABLE test_number_columns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tiny_value TINYINT,
    small_value SMALLINT,
    int_value INT,
    big_value BIGINT,
    decimal_value DECIMAL(10,2),
    float_value FLOAT,
    double_value DOUBLE
);

CREATE TABLE test_date_time_columns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date_value DATE,
    time_value TIME,
    datetime_value DATETIME,
    timestamp_value TIMESTAMP NULL
);

CREATE TABLE test_json_and_binary_columns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    json_value JSON,
    blob_value BLOB,
    long_text_value LONGTEXT
);
