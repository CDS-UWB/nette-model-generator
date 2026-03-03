-- Simple PostgreSQL structure for model generator integration tests

CREATE TABLE test_basic_columns (
    id SERIAL PRIMARY KEY,
    text_value VARCHAR(255) NOT NULL,
    optional_text TEXT,
    bool_value BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TYPE columns_status AS ENUM ('new', 'in_progress', 'done');
CREATE TYPE columns_priority AS ENUM ('low', 'medium', 'high');

CREATE TABLE test_enum_columns (
    id SERIAL PRIMARY KEY,
    status columns_status NOT NULL,
    priority columns_priority DEFAULT 'medium'
);

CREATE TABLE test_number_columns (
    id SERIAL PRIMARY KEY,
    tiny_value SMALLINT,
    small_value SMALLINT,
    int_value INTEGER,
    big_value BIGINT,
    decimal_value DECIMAL(10,2),
    float_value REAL,
    double_value DOUBLE PRECISION
);

CREATE TABLE test_date_time_columns (
    id SERIAL PRIMARY KEY,
    date_value DATE,
    time_value TIME WITHOUT TIME ZONE,
    datetime_value TIMESTAMP WITHOUT TIME ZONE,
    timestamp_value TIMESTAMP WITHOUT TIME ZONE
);

CREATE TABLE test_json_and_binary_columns (
    id SERIAL PRIMARY KEY,
    json_value JSONB,
    blob_value BYTEA,
    long_text_value TEXT
);
