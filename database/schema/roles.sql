-- ISER Authentication System - Roles Schema
-- Tablas de roles y permisos

-- Tabla de roles
CREATE TABLE IF NOT EXISTS iser_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    level INT UNSIGNED DEFAULT 0,
    is_system BOOLEAN DEFAULT FALSE,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_slug (slug),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar roles por defecto
INSERT INTO iser_roles (name, slug, description, level, is_system, created_at, updated_at) VALUES
('Administrador', 'admin', 'Administrador del sistema con acceso completo', 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Moderador', 'moderator', 'Moderador con permisos limitados', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Usuario', 'user', 'Usuario estándar del sistema', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Invitado', 'guest', 'Usuario invitado con acceso limitado', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Tabla de permisos
CREATE TABLE IF NOT EXISTS iser_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50),
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_slug (slug),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar permisos por defecto
INSERT INTO iser_permissions (name, slug, description, module, created_at, updated_at) VALUES
('Ver usuarios', 'users.view', 'Ver listado de usuarios', 'users', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Crear usuarios', 'users.create', 'Crear nuevos usuarios', 'users', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Editar usuarios', 'users.edit', 'Editar usuarios existentes', 'users', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Eliminar usuarios', 'users.delete', 'Eliminar usuarios', 'users', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Ver roles', 'roles.view', 'Ver listado de roles', 'roles', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Crear roles', 'roles.create', 'Crear nuevos roles', 'roles', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Editar roles', 'roles.edit', 'Editar roles existentes', 'roles', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Eliminar roles', 'roles.delete', 'Eliminar roles', 'roles', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Gestionar permisos', 'permissions.manage', 'Gestionar permisos del sistema', 'permissions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Tabla de relación roles-usuarios
CREATE TABLE IF NOT EXISTS iser_user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_at INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    expires_at INT UNSIGNED,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES iser_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES iser_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de relación roles-permisos
CREATE TABLE IF NOT EXISTS iser_role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    granted_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (role_id) REFERENCES iser_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES iser_permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignar todos los permisos al rol de Administrador
INSERT INTO iser_role_permissions (role_id, permission_id, granted_at)
SELECT 1, id, UNIX_TIMESTAMP() FROM iser_permissions;
