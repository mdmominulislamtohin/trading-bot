-- migrations/0003_users_rbac_and_auth.sql
-- Adds RBAC tables and extends users for verification & password reset

ALTER TABLE users 
  ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
  ADD COLUMN verification_token VARCHAR(128) DEFAULT NULL,
  ADD COLUMN verification_expires_at DATETIME DEFAULT NULL,
  ADD COLUMN password_reset_token VARCHAR(128) DEFAULT NULL,
  ADD COLUMN password_reset_expires_at DATETIME DEFAULT NULL,
  ADD COLUMN last_login DATETIME DEFAULT NULL,
  ADD COLUMN failed_login_attempts INT DEFAULT 0,
  ADD COLUMN locked_until DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS roles (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id BIGINT NOT NULL,
  permission_id BIGINT NOT NULL,
  PRIMARY KEY(role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id BIGINT NOT NULL,
  role_id BIGINT NOT NULL,
  PRIMARY KEY(user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default roles & permissions (idempotent)
INSERT INTO roles (name, description) 
  SELECT * FROM (SELECT 'superadmin','Full access') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'superadmin') LIMIT 1;
INSERT INTO roles (name, description) 
  SELECT * FROM (SELECT 'admin','Manage users and settings') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'admin') LIMIT 1;
INSERT INTO roles (name, description) 
  SELECT * FROM (SELECT 'manager','Operational tasks') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'manager') LIMIT 1;
INSERT INTO roles (name, description) 
  SELECT * FROM (SELECT 'support','Support access') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'support') LIMIT 1;
INSERT INTO roles (name, description) 
  SELECT * FROM (SELECT 'user','End user') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'user') LIMIT 1;

-- Permissions seed
INSERT INTO permissions (name, description)
  SELECT * FROM (SELECT 'manage_users','Create/edit users and assign roles') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'manage_users') LIMIT 1;
INSERT INTO permissions (name, description)
  SELECT * FROM (SELECT 'manage_settings','Edit site settings and API keys') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'manage_settings') LIMIT 1;
INSERT INTO permissions (name, description)
  SELECT * FROM (SELECT 'view_deposits','View deposits') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'view_deposits') LIMIT 1;
INSERT INTO permissions (name, description)
  SELECT * FROM (SELECT 'process_deposits','Process/credit deposits') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'process_deposits') LIMIT 1;
INSERT INTO permissions (name, description)
  SELECT * FROM (SELECT 'export_wallets','Export wallets (audit)') AS tmp
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'export_wallets') LIMIT 1;

-- Grant core permissions to roles (superadmin/all), admin -> manage_users/manage_settings
DO
BEGIN
  -- MySQL doesn't support DO with statements; we'll use INSERT ... SELECT with NOT EXISTS
END;

-- superadmin gets everything via inserts by mapping existing permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'superadmin' AND NOT EXISTS (
  SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name IN ('manage_users','manage_settings')
WHERE r.name = 'admin' AND NOT EXISTS (
  SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- end migration
