-- Residence Visitor Management System
-- MySQL 5.7+ / 8.x / MariaDB 10.4+
-- Character set: utf8mb4 / InnoDB
--
-- Create the database first if needed:
-- CREATE DATABASE residence_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE residence_management;

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
    id TINYINT UNSIGNED NOT NULL A88UTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id TINYINT UNSIGNED NOT NULL,
    permission_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    KEY idx_rp_permission (permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_user_time (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_password_resets_token (token),
    KEY idx_password_resets_user (user_id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS residents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    resident_code VARCHAR(20) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    unit_number VARCHAR(50) NOT NULL,
    emergency_contact VARCHAR(120) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    status ENUM('pending','active','suspended','moved_out','inactive') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_residents_code (resident_code),
    UNIQUE KEY uq_residents_user (user_id),
    KEY idx_residents_status (status),
    KEY idx_residents_phone (phone),
    KEY idx_residents_unit (unit_number),
    CONSTRAINT fk_residents_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resident_qr_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resident_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    revoked_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resident_qr_token (token),
    KEY idx_resident_qr_resident (resident_id, is_active),
    CONSTRAINT fk_resident_qr_resident FOREIGN KEY (resident_id) REFERENCES residents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitor_invite_links (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resident_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    revoked_at DATETIME DEFAULT NULL,
    used_at DATETIME DEFAULT NULL,
    visitor_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invite_links_token (token),
    KEY idx_invite_links_resident (resident_id),
    CONSTRAINT fk_invite_links_resident FOREIGN KEY (resident_id) REFERENCES residents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitor_invitations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resident_id INT UNSIGNED NOT NULL,
    visitor_id INT UNSIGNED DEFAULT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active','revoked','expired','used') NOT NULL DEFAULT 'active',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invitations_token (token),
    KEY idx_invitations_resident (resident_id),
    CONSTRAINT fk_invitations_resident FOREIGN KEY (resident_id) REFERENCES residents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resident_id INT UNSIGNED NOT NULL,
    invitation_id INT UNSIGNED DEFAULT NULL,
    invite_link_id INT UNSIGNED DEFAULT NULL,
    visitor_name VARCHAR(120) NOT NULL,
    car_plate VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    purpose VARCHAR(150) NOT NULL,
    visit_date DATE NOT NULL,
    valid_until DATETIME NOT NULL,
    status ENUM('pending','approved','checked_in','checked_out','expired','rejected') NOT NULL DEFAULT 'pending',
    qr_token CHAR(64) NOT NULL,
    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    checked_in_at DATETIME DEFAULT NULL,
    checked_in_by INT UNSIGNED DEFAULT NULL,
    checked_out_at DATETIME DEFAULT NULL,
    checked_out_by INT UNSIGNED DEFAULT NULL,
    rejection_reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_visitors_qr (qr_token),
    KEY idx_visitors_status (status),
    KEY idx_visitors_date (visit_date),
    KEY idx_visitors_resident (resident_id),
    KEY idx_visitors_phone (phone),
    KEY idx_visitors_plate (car_plate),
    CONSTRAINT fk_visitors_resident FOREIGN KEY (resident_id) REFERENCES residents (id),
    CONSTRAINT fk_visitors_invitation FOREIGN KEY (invitation_id) REFERENCES visitor_invitations (id) ON DELETE SET NULL,
    CONSTRAINT fk_visitors_invite_link FOREIGN KEY (invite_link_id) REFERENCES visitor_invite_links (id) ON DELETE SET NULL,
    CONSTRAINT fk_visitors_approved_by FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_visitors_checked_in_by FOREIGN KEY (checked_in_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_visitors_checked_out_by FOREIGN KEY (checked_out_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitor_scans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_id INT UNSIGNED DEFAULT NULL,
    token VARCHAR(128) NOT NULL,
    scanned_by INT UNSIGNED NOT NULL,
    result ENUM('success','rejected') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_scans_visitor (visitor_id),
    KEY idx_scans_created (created_at),
    CONSTRAINT fk_scans_visitor FOREIGN KEY (visitor_id) REFERENCES visitors (id) ON DELETE SET NULL,
    CONSTRAINT fk_scans_user FOREIGN KEY (scanned_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitor_blacklist (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) DEFAULT NULL,
    car_plate VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    reason VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_blacklist_phone (phone),
    KEY idx_blacklist_plate (car_plate),
    KEY idx_blacklist_active (is_active),
    CONSTRAINT fk_blacklist_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    content TEXT NOT NULL,
    audience ENUM('all','residents','security') NOT NULL DEFAULT 'all',
    status ENUM('draft','published','inactive') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_announcements_status (status),
    KEY idx_announcements_audience (audience),
    CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(500) NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'system',
    related_type VARCHAR(40) DEFAULT NULL,
    related_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(500) NOT NULL,
    module VARCHAR(40) NOT NULL DEFAULT 'system',
    entity_type VARCHAR(40) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_activity_module (module),
    KEY idx_activity_created (created_at),
    KEY idx_activity_entity (entity_type, entity_id),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (id, name, slug, description) VALUES
(1, 'Administrator', 'admin', 'Full system access'),
(2, 'Security', 'security', 'Visitor access control'),
(3, 'Resident', 'resident', 'Resident self-service');

INSERT INTO permissions (id, name, slug, description) VALUES
(1, 'View dashboard', 'dashboard.view', NULL),
(2, 'View residents', 'residents.view', NULL),
(3, 'Create residents', 'residents.create', NULL),
(4, 'Edit residents', 'residents.edit', NULL),
(5, 'Delete residents', 'residents.delete', NULL),
(6, 'View visitors', 'visitors.view', NULL),
(7, 'Create visitors', 'visitors.create', NULL),
(8, 'Edit visitors', 'visitors.edit', NULL),
(9, 'Check in visitors', 'visitors.checkin', NULL),
(10, 'Check out visitors', 'visitors.checkout', NULL),
(11, 'Scan visitor QR', 'checkin.scan', NULL),
(12, 'View staff', 'staff.view', NULL),
(13, 'Create staff', 'staff.create', NULL),
(14, 'Edit staff', 'staff.edit', NULL),
(15, 'View reports', 'reports.view', NULL),
(16, 'Export reports', 'reports.export', NULL),
(17, 'View announcements', 'announcements.view', NULL),
(18, 'Create announcements', 'announcements.create', NULL),
(19, 'Edit announcements', 'announcements.edit', NULL),
(20, 'View blacklist', 'blacklist.view', NULL),
(21, 'Manage blacklist', 'blacklist.manage', NULL),
(22, 'View activity logs', 'logs.view', NULL),
(23, 'View notifications', 'notifications.view', NULL);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id) VALUES
(2, 1), (2, 6), (2, 9), (2, 10), (2, 11), (2, 17), (2, 23),
(3, 6), (3, 7), (3, 17), (3, 23);

INSERT INTO users (id, username, email, password_hash, role_id, full_name, phone, status, must_change_password, created_at, updated_at) VALUES
(1, 'admin', 'admin@residence.local', '$2y$12$lMAXEbvYFgkuLEkuMcw/bedy8M0PLNoufpQFjZZ3v88OFJQ68dOQu', 1, 'System Administrator', '0120000001', 'active', 0, NOW(), NOW()),
(2, 'security', 'security@residence.local', '$2y$12$ezm31wijWx5q5WZybcQ9QOJh0jo9TMtywZT/.6/bqW.tYlDG2hxhC', 2, 'Gate Security', '0120000002', 'active', 0, NOW(), NOW()),
(3, 'resident', 'resident@residence.local', '$2y$12$S6KmUSN4qsn6uMArdf5MRuLymzqHiqWin2LOSfIs0y1IJzbt038gy', 3, 'Ahmad Bin Ali', '0120000003', 'active', 0, NOW(), NOW());

INSERT INTO residents (id, user_id, resident_code, full_name, gender, phone, email, address, unit_number, emergency_contact, emergency_contact_phone, status, created_at, updated_at) VALUES
(1, 3, 'RES-2026-0001', 'Ahmad Bin Ali', 'male', '0120000003', 'resident@residence.local', 'Block A, Jalan Residensi', 'A-12-03', 'Siti Ali', '0120000013', 'active', NOW(), NOW()),
(2, NULL, 'RES-2026-0002', 'Lim Wei Ling', 'female', '0120000004', 'lim@residence.local', 'Block B, Jalan Residensi', 'B-08-11', 'Lim Chong', '0120000014', 'active', NOW(), NOW());

INSERT INTO resident_qr_codes (resident_id, token, is_active, created_at) VALUES
(1, '67927ca05175202c77c9f9d2a08c42b6b6fb0c59d8fc79e08dd2e554d670d97c', 1, NOW());

INSERT INTO visitor_invitations (id, resident_id, visitor_id, token, expires_at, status, is_active, created_at) VALUES
(1, 1, NULL, '9c2ee2e867c75b6fc95beedd898a32a3804223a212a947a1882623486116f7e2', CONCAT(CURDATE(), ' 23:59:59'), 'active', 1, NOW());

INSERT INTO visitors (id, resident_id, invitation_id, visitor_name, car_plate, phone, purpose, visit_date, valid_until, status, qr_token, approved_by, approved_at, created_at, updated_at) VALUES
(1, 1, 1, 'Tan Mei Ling', 'WXY1234', '0195551001', 'Family visit', CURDATE(), CONCAT(CURDATE(), ' 23:59:59'), 'approved', '9c2ee2e867c75b6fc95beedd898a32a3804223a212a947a1882623486116f7e2', 1, NOW(), NOW(), NOW());

UPDATE visitor_invitations SET visitor_id = 1 WHERE id = 1;

INSERT INTO announcements (title, content, audience, status, created_by, published_at, created_at, updated_at) VALUES
('Welcome to the visitor system', 'Residents may register visitors or share a one-time invitation link. Security staff will scan visitor QR codes at the gate.', 'all', 'published', 1, NOW(), NOW(), NOW());
