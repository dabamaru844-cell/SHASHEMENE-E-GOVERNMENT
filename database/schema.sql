-- SHASHE E GOVERNMENT Database Schema
-- MySQL 8+

CREATE DATABASE IF NOT EXISTS shashe_egovernment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shashe_egovernment;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;

DROP TABLE IF EXISTS notifications;

DROP TABLE IF EXISTS leave_requests;

DROP TABLE IF EXISTS attendance;

DROP TABLE IF EXISTS asset_assignments;

DROP TABLE IF EXISTS assets;

DROP TABLE IF EXISTS asset_categories;

DROP TABLE IF EXISTS employees;

DROP TABLE IF EXISTS departments;

DROP TABLE IF EXISTS password_resets;

DROP TABLE IF EXISTS users;

DROP TABLE IF EXISTS roles;

DROP TABLE IF EXISTS system_settings;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE = InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE = InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE employees (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    date_of_birth DATE NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    position VARCHAR(100) NOT NULL,
    employment_date DATE NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address TEXT,
    photo VARCHAR(255) DEFAULT NULL,
    employment_status ENUM(
        'active',
        'inactive',
        'terminated',
        'on_leave'
    ) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments (id),
    INDEX idx_name (last_name, first_name),
    INDEX idx_department (department_id),
    INDEX idx_status (employment_status)
) ENGINE = InnoDB;

ALTER TABLE users
ADD FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE SET NULL;

CREATE TABLE asset_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES asset_categories (id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id)
) ENGINE = InnoDB;

CREATE TABLE assets (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    asset_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    purchase_date DATE DEFAULT NULL,
    warranty_expiry DATE DEFAULT NULL,
    supplier VARCHAR(150) DEFAULT NULL,
    cost DECIMAL(12, 2) DEFAULT 0.00,
    status ENUM(
        'active',
        'assigned',
        'maintenance',
        'retired',
        'lost'
    ) DEFAULT 'active',
    location VARCHAR(150) DEFAULT NULL,
    description TEXT,
    document_path VARCHAR(255) DEFAULT NULL,
    department_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories (id),
    FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category_id)
) ENGINE = InnoDB;

CREATE TABLE asset_assignments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id INT UNSIGNED NOT NULL,
    asset_id INT UNSIGNED NOT NULL,
    assignment_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM(
        'active',
        'returned',
        'cancelled'
    ) DEFAULT 'active',
    notes TEXT,
    assigned_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees (id),
    FOREIGN KEY (asset_id) REFERENCES assets (id),
    FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_asset (asset_id),
    INDEX idx_status (status)
) ENGINE = InnoDB;

CREATE TABLE attendance (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    working_hours DECIMAL(4, 2) DEFAULT 0.00,
    status ENUM(
        'present',
        'absent',
        'late',
        'half_day',
        'on_leave'
    ) DEFAULT 'present',
    notes TEXT,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees (id),
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL,
    UNIQUE KEY uk_employee_date (employee_id, attendance_date),
    INDEX idx_date (attendance_date),
    INDEX idx_status (status)
) ENGINE = InnoDB;

CREATE TABLE leave_requests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id INT UNSIGNED NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    approval_status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees (id),
    FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_status (approval_status)
) ENGINE = InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE = InnoDB;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT UNSIGNED DEFAULT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE = InnoDB;

CREATE TABLE system_settings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- Seed roles
INSERT INTO
    roles (name, slug, description)
VALUES (
        'Administrator',
        'admin',
        'Full system access'
    ),
    (
        'HR Officer',
        'hr',
        'Employee and attendance management'
    ),
    (
        'IT Officer',
        'it',
        'IT asset management'
    ),
    (
        'Employee',
        'employee',
        'Self-service access'
    );

-- Seed departments
INSERT INTO
    departments (name, description)
VALUES
    ('Computer Science', 'Computer Science Department'),
    ('Human Resources', 'HR Department'),
    ('Information Technology', 'IT Department'),
    ('Finance', 'Finance Department'),
    ('Administration', 'Administration Department'),
    ('Business Management', 'Business Management Department'),
    ('Accounting', 'Accounting Department'),
    ('Tourism', 'Tourism Department'),
    ('Agriculture', 'Agriculture Department'),
    ('Civil Engineering', 'Civil Engineering Department'),
    ('Electrical Engineering', 'Electrical Engineering Department'),
    ('Mechanical Engineering', 'Mechanical Engineering Department'),
    ('Environmental Science', 'Environmental Science Department'),
    ('Law', 'Law Department'),
    ('Public Health', 'Public Health Department'),
    ('Social Work', 'Social Work Department'),
    ('Education', 'Education Department'),
    ('Economics', 'Economics Department'),
    ('Information Systems', 'Information Systems Department'),
    ('Biotechnology', 'Biotechnology Department'),
    ('Environmental Engineering', 'Environmental Engineering Department'),
    ('Urban Planning', 'Urban Planning Department'),
    ('Architecture', 'Architecture Department'),
    ('Industrial Design', 'Industrial Design Department'),
    ('Hospitality Management', 'Hospitality Management Department'),
    ('Tourism Management', 'Tourism Management Department');

-- Seed asset categories
INSERT INTO
    asset_categories (name, parent_id)
VALUES ('Computers', NULL),
    ('Printers', NULL),
    ('Network Devices', NULL);

INSERT INTO
    asset_categories (name, parent_id)
VALUES ('Desktop', 1),
    ('Laptop', 1),
    ('Laser Printer', 2),
    ('Inkjet Printer', 2),
    ('Router', 3),
    ('Switch', 3),
    ('Access Point', 3);

-- Default admin user (password: Admin@123)
INSERT INTO
    users (
        username,
        email,
        password,
        role_id
    )
VALUES (
        'admin',
        'admin@shashe.gov.et',
        '$2y$10$GQlXtTon0jGxvYSEkQKM5.ry2QZUEn4Xh1FN/tXcTVXu0T7mROge2',
        1
    );

-- System settings
INSERT INTO
    system_settings (setting_key, setting_value)
VALUES (
        'site_name',
        'SHASHE E GOVERNMENT'
    ),
    ('default_language', 'en'),
    ('max_upload_size', '10485760'),
    ('backup_enabled', '1');