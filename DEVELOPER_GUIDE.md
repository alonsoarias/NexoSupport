# NexoSupport Developer Guide

**Version:** 1.0.0
**Last Updated:** 2025-11-12
**Target Audience:** Core Developers & Contributors

---

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture](#system-architecture)
3. [Development Environment](#development-environment)
4. [Core Components](#core-components)
5. [Database Architecture](#database-architecture)
6. [Authentication & Authorization](#authentication--authorization)
7. [Routing & Request Handling](#routing--request-handling)
8. [Plugin System Architecture](#plugin-system-architecture)
9. [Internationalization (i18n)](#internationalization-i18n)
10. [Templating with Mustache](#templating-with-mustache)
11. [Logging & Monitoring](#logging--monitoring)
12. [Coding Standards](#coding-standards)
13. [Testing Strategy](#testing-strategy)
14. [Contributing Guidelines](#contributing-guidelines)
15. [Security Best Practices](#security-best-practices)
16. [Performance Optimization](#performance-optimization)

---

## Introduction

NexoSupport is a modern, extensible PHP 8.1+ authentication and support system built with:

- **Modern PHP**: PHP 8.1+ with strict types, enums, and latest features
- **PSR Standards**: PSR-1, PSR-4, PSR-12 compliance
- **MVC Architecture**: Clean separation of concerns
- **Plugin System**: Dynamic, type-based plugin loading
- **Multi-Database**: MySQL, PostgreSQL, SQLite support
- **JWT Authentication**: Secure, stateless authentication
- **RBAC**: 35+ granular permissions
- **i18n**: Multi-language support (ES/EN)
- **Responsive UI**: Modern, mobile-first design

### Technology Stack

| Layer | Technology |
|-------|-----------|
| **Language** | PHP 8.1+ |
| **Database** | MySQL 5.7+, PostgreSQL 12+, SQLite 3+ |
| **Template Engine** | Mustache (logic-less templates) |
| **Authentication** | JWT (JSON Web Tokens) |
| **Logging** | Monolog |
| **Autoloading** | Composer (PSR-4) |
| **Frontend** | HTML5, CSS3, JavaScript ES6+ |
| **Icons** | Bootstrap Icons |
| **Package Manager** | Composer |

---

## System Architecture

### High-Level Architecture

```
┌────────────────────────────────────────────────────────────┐
│                      Client (Browser)                       │
└───────────────────────┬────────────────────────────────────┘
                        │ HTTP/HTTPS
                        ▼
┌────────────────────────────────────────────────────────────┐
│                   Web Server (Apache/Nginx)                 │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│                     public_html/index.php                   │
│                    (Front Controller)                       │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│                         Router                              │
│              (Route Matching & Dispatch)                    │
└─────────┬───────────────────────────────┬──────────────────┘
          │                               │
          ▼                               ▼
┌──────────────────┐          ┌──────────────────────┐
│   Controllers    │          │   Middleware         │
│   (HTTP Logic)   │◄─────────│   (Auth, CORS, etc)  │
└────────┬─────────┘          └──────────────────────┘
         │
         ▼
┌──────────────────┐
│    Services      │
│  (Business Logic)│
└────────┬─────────┘
         │
         ▼
┌──────────────────┐          ┌──────────────────────┐
│     Models       │◄─────────│      Database        │
│  (Data Access)   │          │   (MySQL/Postgres)   │
└────────┬─────────┘          └──────────────────────┘
         │
         ▼
┌──────────────────┐
│      Views       │
│   (Mustache)     │
└──────────────────┘
```

### Directory Structure

```
NexoSupport/
├── config/                      # Configuration files
│   ├── config.php              # Main configuration
│   ├── database.php            # Database settings
│   └── jwt.php                 # JWT configuration
├── core/                       # Core framework classes
│   ├── Auth/                   # Authentication
│   │   ├── JWTAuth.php
│   │   ├── PermissionChecker.php
│   │   └── Session.php
│   ├── Database/               # Database abstraction
│   │   ├── Database.php
│   │   └── SchemaInstaller.php
│   ├── Http/                   # HTTP handling
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Router.php
│   ├── Plugin/                 # Plugin system
│   │   ├── PluginManager.php
│   │   ├── PluginInstaller.php
│   │   └── PluginConfig.php
│   └── View/                   # Templating
│       └── MustacheRenderer.php
├── modules/                    # Application modules
│   ├── Admin/                  # Admin controllers
│   │   ├── AdminPlugins.php
│   │   ├── AdminUsers.php
│   │   └── AdminSettings.php
│   ├── Auth/                   # Auth controllers
│   │   ├── LoginController.php
│   │   └── RegisterController.php
│   ├── Plugin/                 # Plugin management
│   │   ├── PluginManager.php
│   │   └── PluginInstaller.php
│   └── plugins/                # Installed plugins
│       ├── tools/
│       ├── auth/
│       ├── themes/
│       ├── reports/
│       ├── modules/
│       └── integrations/
├── public_html/                # Web root
│   ├── index.php              # Front controller
│   ├── .htaccess              # Apache config
│   ├── assets/                # Public assets
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/               # User uploads
├── resources/                  # Application resources
│   ├── lang/                  # Translations
│   │   ├── es/
│   │   └── en/
│   └── views/                 # Mustache templates
│       ├── admin/
│       ├── auth/
│       └── layouts/
├── storage/                    # Storage directory
│   ├── logs/                  # Application logs
│   ├── cache/                 # Cache files
│   └── sessions/              # Session data
├── tests/                      # Test suite
│   ├── Unit/
│   └── Integration/
├── vendor/                     # Composer dependencies
├── composer.json              # Composer config
├── .env                       # Environment variables
└── README.md                  # Project readme
```

### Request Lifecycle

```
1. HTTP Request arrives at public_html/index.php
   ↓
2. Load Composer autoloader
   ↓
3. Load configuration from config/config.php
   ↓
4. Initialize core services:
   - Database connection
   - Router
   - Template renderer
   - Logger
   ↓
5. Router matches request to route
   ↓
6. Execute middleware (authentication, CORS, etc.)
   ↓
7. Instantiate controller
   ↓
8. Execute controller method
   ↓
9. Controller calls services
   ↓
10. Services interact with models/database
    ↓
11. Controller returns Response object
    ↓
12. Render view (if HTML response)
    ↓
13. Send response to client
```

---

## Development Environment

### Requirements

- **PHP**: 8.1 or higher
- **Database**: MySQL 5.7+, PostgreSQL 12+, or SQLite 3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Composer**: Latest version
- **Extensions**:
  - `ext-pdo`
  - `ext-pdo_mysql` or `ext-pdo_pgsql` or `ext-pdo_sqlite`
  - `ext-mbstring`
  - `ext-openssl`
  - `ext-json`
  - `ext-xml`
  - `ext-zip`

### Local Setup

1. **Clone Repository**
   ```bash
   git clone https://github.com/yourorg/NexoSupport.git
   cd NexoSupport
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   nano .env
   ```

   Update database credentials:
   ```env
   DB_TYPE=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=nexosupport
   DB_USER=root
   DB_PASS=password
   ```

4. **Install Database**
   ```bash
   php install.php
   ```

   Or navigate to: `http://localhost/install.php`

5. **Configure Web Server**

   **Apache** (`.htaccess` included):
   ```apache
   <VirtualHost *:80>
       ServerName nexosupport.local
       DocumentRoot /path/to/NexoSupport/public_html

       <Directory /path/to/NexoSupport/public_html>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   **Nginx**:
   ```nginx
   server {
       listen 80;
       server_name nexosupport.local;
       root /path/to/NexoSupport/public_html;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }
   }
   ```

6. **Set Permissions**
   ```bash
   chmod -R 775 storage/
   chmod -R 775 public_html/uploads/
   chown -R www-data:www-data storage/
   chown -R www-data:www-data public_html/uploads/
   ```

7. **Access Application**
   - Frontend: `http://nexosupport.local`
   - Admin: `http://nexosupport.local/admin`

### Development Tools

**Recommended IDE**: PHPStorm, VS Code with PHP extensions

**Useful Composer Scripts**:
```json
{
  "scripts": {
    "test": "phpunit",
    "test-coverage": "phpunit --coverage-html coverage",
    "cs-check": "phpcs --standard=PSR12 core/ modules/",
    "cs-fix": "phpcbf --standard=PSR12 core/ modules/",
    "analyse": "phpstan analyse core/ modules/ -l 8"
  }
}
```

**Run Scripts**:
```bash
composer test              # Run unit tests
composer cs-check          # Check coding standards
composer cs-fix            # Fix coding standards
composer analyse           # Static analysis
```

---

## Core Components

### 1. Database Layer (`core/Database/`)

**Purpose**: Database abstraction supporting multiple database types

**Key Classes**:

#### `Database.php`

```php
class Database
{
    private \PDO $pdo;
    private string $type; // 'mysql', 'pgsql', 'sqlite'

    public function __construct(array $config);
    public function getConnection(): \PDO;
    public function query(string $sql): \PDOStatement;
    public function prepare(string $sql): \PDOStatement;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
}
```

**Usage**:
```php
$db = new Database([
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'nexosupport',
    'username' => 'root',
    'password' => 'password'
]);

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => 1]);
$user = $stmt->fetch();
```

#### `SchemaInstaller.php`

**Purpose**: Install database schemas from XML definitions

```php
class SchemaInstaller
{
    public function __construct(
        \PDO $pdo,
        string $tablePrefix = '',
        bool $dropExisting = false
    );

    public function installFromXML(string $xmlPath): bool;
    public function getCreatedTables(): array;
    public function dropTable(string $tableName): bool;
}
```

**XML Schema Format**: See [PLUGIN_DEVELOPMENT.md](./PLUGIN_DEVELOPMENT.md#database-schema-installxml)

### 2. HTTP Layer (`core/Http/`)

#### `Request.php`

**Purpose**: HTTP request abstraction

```php
class Request
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getPath(): string;
    public function getQuery(): array;
    public function getPost(): array;
    public function getHeaders(): array;
    public function getHeader(string $name, ?string $default = null): ?string;
    public function getBody(): string;
    public function isMethod(string $method): bool;
    public function isGet(): bool;
    public function isPost(): bool;
    public function isPut(): bool;
    public function isDelete(): bool;
    public function wantsJson(): bool;
}
```

#### `Response.php`

**Purpose**: HTTP response builder

```php
class Response
{
    public static function html(string $content, int $status = 200): self;
    public static function json(array $data, int $status = 200): self;
    public static function redirect(string $url, int $status = 302): self;
    public static function notFound(string $message = 'Not Found'): self;
    public static function forbidden(string $message = 'Forbidden'): self;
    public static function serverError(string $message = 'Server Error'): self;

    public function send(): void;
}
```

**Usage**:
```php
// HTML response
return Response::html($renderer->render('page', $data));

// JSON response
return Response::json([
    'success' => true,
    'data' => $results
]);

// Redirect
return Response::redirect('/admin/dashboard');

// Error responses
return Response::notFound('User not found');
return Response::forbidden('Access denied');
```

#### `Router.php`

**Purpose**: Route registration and matching

```php
class Router
{
    public function get(string $path, callable $handler, ?string $name = null): void;
    public function post(string $path, callable $handler, ?string $name = null): void;
    public function put(string $path, callable $handler, ?string $name = null): void;
    public function delete(string $path, callable $handler, ?string $name = null): void;
    public function match(Request $request): ?Response;
}
```

**Usage**:
```php
$router = new Router();

// Simple route
$router->get('/about', function($request) {
    return Response::html('About page');
});

// Route with parameters
$router->get('/users/{id}', function($request) {
    $id = $request->getParam('id');
    // ...
});

// Named route
$router->post('/users', function($request) {
    // ...
}, 'users.create');

// Match request
$response = $router->match($request);
$response->send();
```

### 3. Authentication (`core/Auth/`)

#### `JWTAuth.php`

**Purpose**: JWT token generation and validation

```php
class JWTAuth
{
    public function __construct(string $secret, string $algorithm = 'HS256');

    public function generateToken(array $payload, int $expiresIn = 3600): string;
    public function validateToken(string $token): ?array;
    public function refreshToken(string $token): ?string;
    public function revokeToken(string $token): bool;
}
```

**Token Payload**:
```json
{
  "sub": 123,              // User ID
  "name": "John Doe",      // User name
  "email": "john@example.com",
  "roles": ["admin"],      // User roles
  "iat": 1699876543,       // Issued at
  "exp": 1699880143        // Expires at
}
```

#### `PermissionChecker.php`

**Purpose**: RBAC permission checking

```php
class PermissionChecker
{
    public function __construct(array $userPermissions);

    public function has(string $permission): bool;
    public function hasAny(array $permissions): bool;
    public function hasAll(array $permissions): bool;
    public function can(string $permission): bool; // Alias for has()
}
```

**Usage**:
```php
$checker = new PermissionChecker($user['permissions']);

if ($checker->has('admin.users.delete')) {
    // Allow deletion
}

if ($checker->hasAny(['admin.full', 'admin.users.manage'])) {
    // Allow access
}
```

### 4. Plugin System (`core/Plugin/`)

#### `PluginManager.php`

**Purpose**: Plugin database operations

```php
class PluginManager
{
    public function getAll(): array;
    public function getEnabled(): array;
    public function getByType(string $type): array;
    public function getBySlug(string $slug): ?array;
    public function create(array $data): int;
    public function update(string $slug, array $data): bool;
    public function updateVersion(string $slug, string $version, string $manifest): bool;
    public function delete(string $slug): bool;
    public function enable(string $slug): bool;
    public function disable(string $slug): bool;
    public function getDependents(string $slug): array;
}
```

#### `PluginInstaller.php`

**Purpose**: Plugin installation/uninstallation/updates

```php
class PluginInstaller
{
    public function install(string $zipPath): array;
    public function uninstall(string $slug): array;
    public function update(string $slug, string $zipPath): array;
    public function enable(string $slug): array;
    public function disable(string $slug): array;
}
```

**See**: [PLUGIN_DEVELOPMENT.md](./PLUGIN_DEVELOPMENT.md) for plugin architecture

### 5. View Layer (`core/View/`)

#### `MustacheRenderer.php`

**Purpose**: Mustache template rendering with i18n support

```php
class MustacheRenderer
{
    public function __construct(string $templatePath, string $cachePath);

    public function render(string $template, array $data = []): string;
    public function renderWithLayout(
        string $template,
        array $data = [],
        string $layout = 'layouts/default'
    ): string;
}
```

**Usage**:
```php
$renderer = new MustacheRenderer(
    __DIR__ . '/resources/views',
    __DIR__ . '/storage/cache/views'
);

// Render template
echo $renderer->render('admin/dashboard', [
    'user' => $user,
    'stats' => $stats
]);

// Render with layout
echo $renderer->renderWithLayout('admin/users', $data, 'layouts/admin');
```

### 6. Logging (`core/Logger/`)

**Purpose**: Application logging using Monolog

```php
class Logger
{
    public static function emergency(string $message, array $context = []): void;
    public static function alert(string $message, array $context = []): void;
    public static function critical(string $message, array $context = []): void;
    public static function error(string $message, array $context = []): void;
    public static function warning(string $message, array $context = []): void;
    public static function notice(string $message, array $context = []): void;
    public static function info(string $message, array $context = []): void;
    public static function debug(string $message, array $context = []): void;
}
```

**Usage**:
```php
Logger::info('User logged in', [
    'user_id' => $user['id'],
    'ip' => $_SERVER['REMOTE_ADDR']
]);

Logger::error('Database connection failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

---

## Database Architecture

### Core Tables

#### `users`

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_active (active)
);
```

#### `roles`

```sql
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);
```

#### `permissions`

```sql
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
);
```

#### `user_roles`

```sql
CREATE TABLE user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

#### `role_permissions`

```sql
CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

#### `plugins`

```sql
CREATE TABLE plugins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    version VARCHAR(20) NOT NULL,
    enabled TINYINT(1) DEFAULT 0,
    manifest TEXT,
    config TEXT,
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_type (type),
    INDEX idx_enabled (enabled)
);
```

### Database Normalization (3NF)

NexoSupport follows Third Normal Form (3NF) principles:

1. **First Normal Form (1NF)**:
   - All columns contain atomic values
   - No repeating groups
   - Each row is unique (primary key)

2. **Second Normal Form (2NF)**:
   - Satisfies 1NF
   - No partial dependencies (all non-key attributes depend on entire primary key)

3. **Third Normal Form (3NF)**:
   - Satisfies 2NF
   - No transitive dependencies (non-key attributes don't depend on other non-key attributes)

**Example**:
- `users` table contains user data
- `roles` table contains role definitions
- `user_roles` junction table links users to roles (many-to-many)
- No user data is duplicated in roles
- No role data is duplicated in users

---

## Authentication & Authorization

### Authentication Flow

```
1. User submits login credentials
   ↓
2. LoginController validates credentials
   ↓
3. Hash password and compare with database
   ↓
4. If valid:
   a. Fetch user roles and permissions
   b. Generate JWT token
   c. Return token to client
   ↓
5. Client stores token (cookie/localStorage)
   ↓
6. Client includes token in subsequent requests
   ↓
7. Middleware validates token
   ↓
8. If valid, attach user to request
   ↓
9. Controller checks permissions
   ↓
10. Execute action if authorized
```

### Implementing Authentication Middleware

```php
<?php

namespace Core\Middleware;

use Core\Auth\JWTAuth;
use Core\Http\Request;
use Core\Http\Response;

class AuthMiddleware
{
    private JWTAuth $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Extract token from Authorization header
        $authHeader = $request->getHeader('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return Response::json([
                'success' => false,
                'message' => 'No authentication token provided'
            ], 401);
        }

        $token = substr($authHeader, 7);

        // Validate token
        $payload = $this->jwt->validateToken($token);

        if (!$payload) {
            return Response::json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Attach user data to request
        $request->setAttribute('user', $payload);

        // Continue to next middleware/controller
        return $next($request);
    }
}
```

### Permission Checking in Controllers

```php
<?php

namespace Modules\Admin;

use Core\Auth\PermissionChecker;
use Core\Http\Request;
use Core\Http\Response;

class AdminController
{
    public function deleteUser(Request $request): Response
    {
        $user = $request->getAttribute('user');
        $permissions = new PermissionChecker($user['permissions']);

        // Check permission
        if (!$permissions->has('admin.users.delete')) {
            return Response::forbidden('You do not have permission to delete users');
        }

        // Proceed with deletion
        $userId = $request->getParam('id');
        // ... delete logic

        return Response::json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
```

---

## Routing & Request Handling

### Route Definition

Routes are defined in `public_html/index.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Http\Router;
use Core\Http\Request;
use Core\Database\Database;
use Modules\Admin\AdminPlugins;

// Initialize services
$database = new Database($config['database']);
$router = new Router();

// Public routes
$router->get('/', function($request) {
    return Response::html('Welcome to NexoSupport');
});

// Authentication routes
$router->post('/auth/login', function($request) use ($database) {
    $controller = new LoginController($database);
    return $controller->login($request);
});

// Admin routes (with middleware)
$router->get('/admin/plugins', function($request) use ($database) {
    // Check authentication
    $controller = new AdminPlugins($database);
    return $controller->index();
}, 'admin.plugins.index');

// Route with parameter
$router->get('/admin/plugins/{slug}', function($request) use ($database) {
    $uri = $request->getUri()->getPath();
    $parts = explode('/', trim($uri, '/'));
    $slug = $parts[2] ?? '';

    $controller = new AdminPlugins($database);
    return $controller->show($slug);
});

// Handle request
$request = Request::createFromGlobals();
$response = $router->match($request);

if ($response) {
    $response->send();
} else {
    Response::notFound()->send();
}
```

### Route Parameters

```php
// Named parameters
$router->get('/users/{id}', function($request) {
    $id = $request->getParam('id');
    // ...
});

// Multiple parameters
$router->get('/posts/{year}/{month}/{slug}', function($request) {
    $year = $request->getParam('year');
    $month = $request->getParam('month');
    $slug = $request->getParam('slug');
    // ...
});

// Optional parameters (with regex)
$router->get('/articles/{id}?', function($request) {
    $id = $request->getParam('id', 'all');
    // ...
});
```

### Middleware Chain

```php
// Create middleware stack
$middlewares = [
    new CorsMiddleware(),
    new AuthMiddleware($jwt),
    new PermissionMiddleware(['admin.full'])
];

// Apply middleware
$handler = function($request) use ($controller) {
    return $controller->handle($request);
};

foreach (array_reverse($middlewares) as $middleware) {
    $handler = function($request) use ($middleware, $handler) {
        return $middleware->handle($request, $handler);
    };
}

$response = $handler($request);
```

---

## Plugin System Architecture

### Plugin Loading Process

```
1. System Initialization
   ↓
2. PluginManager fetches enabled plugins from database
   ↓
3. For each plugin:
   a. Load plugin.json manifest
   b. Register PSR-4 autoloader for plugin namespace
   c. Instantiate main plugin class
   d. Register hooks defined in manifest
   ↓
4. During Request Processing:
   a. Trigger relevant hooks (e.g., admin.menu)
   b. Execute registered callbacks in priority order
   c. Collect and merge results
   ↓
5. Plugin functionality is available throughout request
```

### Hook Execution

```php
<?php

namespace Core\Plugin;

class HookManager
{
    private array $hooks = [];

    /**
     * Register a hook callback
     */
    public function register(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Execute a hook
     */
    public function execute(string $hookName, $data = null)
    {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }

        foreach ($this->hooks[$hookName] as $hook) {
            $data = call_user_func($hook['callback'], $data);
        }

        return $data;
    }

    /**
     * Check if hook exists
     */
    public function has(string $hookName): bool
    {
        return isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }
}
```

**Usage**:
```php
$hookManager = new HookManager();

// Register hooks from plugins
$hookManager->register('admin.menu', 'MyPlugin\\Plugin::addMenuItem', 10);
$hookManager->register('admin.menu', 'AnotherPlugin\\Plugin::addMenuItem', 20);

// Execute hook
$menuItems = ['Dashboard', 'Users', 'Settings'];
$menuItems = $hookManager->execute('admin.menu', $menuItems);

// Result includes items from all registered plugins
```

---

## Internationalization (i18n)

### Translation Files

Location: `resources/lang/{locale}/{domain}.php`

**Example** (`resources/lang/es/common.php`):
```php
<?php

return [
    'welcome' => 'Bienvenido',
    'hello' => 'Hola, {name}',
    'dashboard' => 'Panel de Control',
    'logout' => 'Cerrar Sesión',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'delete' => 'Eliminar',
    'edit' => 'Editar'
];
```

### Translation Helper

```php
<?php

function __(string $key, array $replacements = [], ?string $locale = null): string
{
    static $translations = [];
    static $currentLocale = 'es';

    if ($locale !== null) {
        $currentLocale = $locale;
    }

    // Parse key: "domain.key"
    [$domain, $translationKey] = explode('.', $key, 2);

    // Load translations if not loaded
    $cacheKey = "{$currentLocale}.{$domain}";
    if (!isset($translations[$cacheKey])) {
        $file = __DIR__ . "/resources/lang/{$currentLocale}/{$domain}.php";
        if (file_exists($file)) {
            $translations[$cacheKey] = require $file;
        } else {
            $translations[$cacheKey] = [];
        }
    }

    // Get translation
    $translation = $translations[$cacheKey][$translationKey] ?? $key;

    // Replace placeholders
    foreach ($replacements as $placeholder => $value) {
        $translation = str_replace("{{$placeholder}}", $value, $translation);
    }

    return $translation;
}
```

**Usage in PHP**:
```php
echo __('common.welcome');
// Output: "Bienvenido"

echo __('common.hello', ['name' => 'Juan']);
// Output: "Hola, Juan"

echo __('plugins.install_success', ['plugin' => 'My Plugin']);
// Output: "My Plugin instalado correctamente"
```

**Usage in Mustache Templates**:
```mustache
<h1>{{#__}}common.dashboard{{/__}}</h1>
<button>{{#__}}common.save{{/__}}</button>
<a href="/logout">{{#__}}common.logout{{/__}}</a>
```

### Adding New Translations

1. Add to Spanish file (`resources/lang/es/{domain}.php`)
2. Add matching key to English file (`resources/lang/en/{domain}.php`)
3. Use in code with `__()` helper
4. Clear cache if necessary

---

## Templating with Mustache

### Mustache Basics

Mustache is a logic-less template system. No PHP code in templates.

**Variables**:
```mustache
{{name}}                 <!-- Output escaped -->
{{{html}}}              <!-- Output unescaped -->
{{#user}}{{name}}{{/user}}  <!-- Nested -->
```

**Conditionals**:
```mustache
{{#show}}
    This is shown if 'show' is truthy
{{/show}}

{{^hide}}
    This is shown if 'hide' is falsy
{{/hide}}
```

**Lists**:
```mustache
{{#items}}
    <li>{{name}}</li>
{{/items}}
```

**Lambdas** (Functions):
```mustache
{{#__}}common.welcome{{/__}}
```

### Template Inheritance

**Layout** (`layouts/admin.mustache`):
```mustache
<!DOCTYPE html>
<html>
<head>
    <title>{{title}} - NexoSupport</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <nav>
        {{> partials/admin-nav}}
    </nav>

    <main>
        {{{content}}}
    </main>

    <footer>
        {{> partials/footer}}
    </footer>
</body>
</html>
```

**Page Template** (`admin/users.mustache`):
```mustache
<h1>{{#__}}users.title{{/__}}</h1>

<table>
    <thead>
        <tr>
            <th>{{#__}}users.username{{/__}}</th>
            <th>{{#__}}users.email{{/__}}</th>
            <th>{{#__}}users.actions{{/__}}</th>
        </tr>
    </thead>
    <tbody>
        {{#users}}
        <tr>
            <td>{{username}}</td>
            <td>{{email}}</td>
            <td>
                <a href="/admin/users/{{id}}/edit">{{#__}}common.edit{{/__}}</a>
            </td>
        </tr>
        {{/users}}
    </tbody>
</table>
```

**Partial** (`partials/admin-nav.mustache`):
```mustache
<ul class="nav">
    <li><a href="/admin">{{#__}}common.dashboard{{/__}}</a></li>
    <li><a href="/admin/users">{{#__}}users.title{{/__}}</a></li>
    <li><a href="/admin/plugins">{{#__}}plugins.title{{/__}}</a></li>
    <li><a href="/admin/settings">{{#__}}settings.title{{/__}}</a></li>
</ul>
```

### Rendering in Controller

```php
<?php

namespace Modules\Admin;

use Core\View\MustacheRenderer;
use Core\Http\Response;

class UserController
{
    private MustacheRenderer $renderer;

    public function __construct(MustacheRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function index(): Response
    {
        $users = $this->userModel->getAll();

        $html = $this->renderer->renderWithLayout(
            'admin/users',
            ['users' => $users],
            'layouts/admin'
        );

        return Response::html($html);
    }
}
```

---

## Logging & Monitoring

### Log Levels

| Level | When to Use |
|-------|-------------|
| `emergency` | System is unusable |
| `alert` | Action must be taken immediately |
| `critical` | Critical conditions |
| `error` | Runtime errors (not requiring immediate action) |
| `warning` | Exceptional occurrences (not errors) |
| `notice` | Normal but significant events |
| `info` | Interesting events (user login, SQL logs) |
| `debug` | Detailed debug information |

### Logging Examples

```php
// Authentication
Logger::info('User logged in', [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'ip' => $_SERVER['REMOTE_ADDR']
]);

// Plugin operations
Logger::info('Plugin installed', [
    'plugin' => $slug,
    'version' => $version
]);

// Errors
Logger::error('Database query failed', [
    'query' => $sql,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);

// Performance monitoring
$start = microtime(true);
// ... operation
$duration = microtime(true) - $start;
Logger::debug('Operation completed', [
    'operation' => 'process_data',
    'duration' => $duration,
    'memory' => memory_get_usage(true)
]);
```

### Log Rotation

Configure in `config/logging.php`:

```php
<?php

return [
    'default' => 'daily',

    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/nexosupport.log',
            'level' => 'debug',
            'days' => 14, // Keep 14 days of logs
        ],

        'single' => [
            'driver' => 'single',
            'path' => __DIR__ . '/../storage/logs/nexosupport.log',
            'level' => 'info',
        ],
    ]
];
```

---

## Coding Standards

### PSR-12 Compliance

Follow PSR-12 Extended Coding Style:

```php
<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Database\Database;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin plugin controller
 */
class AdminPlugins
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Constructor
     *
     * @param Database $db Database connection
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * List all plugins
     *
     * @return Response
     */
    public function index(): Response
    {
        $plugins = $this->pluginManager->getAll();

        return Response::html(
            $this->renderer->render('admin/plugins/index', [
                'plugins' => $plugins
            ])
        );
    }
}
```

### Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| **Classes** | PascalCase | `PluginManager`, `UserController` |
| **Methods** | camelCase | `getUser()`, `validateToken()` |
| **Variables** | camelCase | `$userId`, `$pluginData` |
| **Constants** | UPPER_SNAKE_CASE | `MAX_UPLOAD_SIZE`, `DEFAULT_TIMEOUT` |
| **Namespaces** | PascalCase | `Core\Auth`, `Modules\Admin` |
| **Database Tables** | snake_case | `users`, `plugin_config` |
| **Database Columns** | snake_case | `user_id`, `created_at` |

### Type Hints

Always use strict types and type hints:

```php
<?php

declare(strict_types=1);

class Example
{
    // Property type hints
    private string $name;
    private int $age;
    private ?array $data = null;

    // Parameter and return type hints
    public function processData(string $input, int $limit): array
    {
        // ...
        return $result;
    }

    // Nullable return type
    public function findUser(int $id): ?array
    {
        // ...
        return $user ?? null;
    }

    // Union types (PHP 8.0+)
    public function getValue(): int|string
    {
        // ...
    }
}
```

### Error Handling

```php
try {
    $result = $this->dangerousOperation();

    Logger::info('Operation succeeded', [
        'operation' => 'dangerous_operation',
        'result' => $result
    ]);

    return Response::json([
        'success' => true,
        'data' => $result
    ]);

} catch (\InvalidArgumentException $e) {
    Logger::warning('Invalid argument', [
        'error' => $e->getMessage()
    ]);

    return Response::json([
        'success' => false,
        'message' => 'Invalid input provided'
    ], 400);

} catch (\Exception $e) {
    Logger::error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    return Response::json([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}
```

---

## Testing Strategy

### Unit Tests

**Location**: `tests/Unit/`

**Example**:
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Auth\PermissionChecker;

class PermissionCheckerTest extends TestCase
{
    public function testHasPermission(): void
    {
        $permissions = ['admin.users.view', 'admin.users.edit'];
        $checker = new PermissionChecker($permissions);

        $this->assertTrue($checker->has('admin.users.view'));
        $this->assertFalse($checker->has('admin.users.delete'));
    }

    public function testHasAnyPermission(): void
    {
        $permissions = ['admin.users.view'];
        $checker = new PermissionChecker($permissions);

        $this->assertTrue($checker->hasAny([
            'admin.users.view',
            'admin.users.edit'
        ]));

        $this->assertFalse($checker->hasAny([
            'admin.users.delete',
            'admin.users.create'
        ]));
    }
}
```

### Integration Tests

**Location**: `tests/Integration/`

**Example**:
```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Core\Database\Database;
use Modules\Plugin\PluginManager;

class PluginManagerTest extends TestCase
{
    private Database $db;
    private PluginManager $manager;

    protected function setUp(): void
    {
        $this->db = new Database([
            'type' => 'sqlite',
            'database' => ':memory:'
        ]);

        $this->setupTestDatabase();

        $this->manager = new PluginManager($this->db);
    }

    public function testCreatePlugin(): void
    {
        $pluginId = $this->manager->create([
            'slug' => 'test-plugin',
            'name' => 'Test Plugin',
            'type' => 'tool',
            'version' => '1.0.0',
            'manifest' => json_encode(['test' => true])
        ]);

        $this->assertIsInt($pluginId);
        $this->assertGreaterThan(0, $pluginId);

        $plugin = $this->manager->getBySlug('test-plugin');
        $this->assertNotNull($plugin);
        $this->assertEquals('Test Plugin', $plugin['name']);
    }
}
```

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific test file
./vendor/bin/phpunit tests/Unit/PermissionCheckerTest.php

# With coverage
./vendor/bin/phpunit --coverage-html coverage/

# Filter by test name
./vendor/bin/phpunit --filter testHasPermission
```

---

## Contributing Guidelines

### Git Workflow

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/my-feature
   ```
3. **Make changes**
4. **Run tests**
   ```bash
   composer test
   composer cs-check
   ```
5. **Commit with descriptive message**
   ```bash
   git commit -m "feat: add user export functionality"
   ```
6. **Push to your fork**
   ```bash
   git push origin feature/my-feature
   ```
7. **Create pull request**

### Commit Message Format

Follow Conventional Commits:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style (formatting, semicolons)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance

**Examples**:
```
feat(plugins): add plugin update system

Implement complete plugin update functionality with automatic backup
and rollback on failure. Includes UI components and backend logic.

Closes #123
```

```
fix(auth): prevent token refresh loop

Fixed infinite loop when token expires during refresh attempt.

Fixes #456
```

### Pull Request Checklist

- [ ] Code follows PSR-12 coding standards
- [ ] All tests pass
- [ ] New tests added for new functionality
- [ ] Documentation updated
- [ ] No breaking changes (or documented if necessary)
- [ ] Commit messages follow convention
- [ ] Branch is up-to-date with main

---

## Security Best Practices

### Input Validation

```php
// Sanitize strings
$slug = filter_var($_POST['slug'], FILTER_SANITIZE_STRING);

// Validate format
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    throw new \InvalidArgumentException('Invalid slug format');
}

// Validate email
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    throw new \InvalidArgumentException('Invalid email');
}

// Validate integers
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false) {
    throw new \InvalidArgumentException('Invalid ID');
}
```

### SQL Injection Prevention

```php
// ALWAYS use prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);

// NEVER concatenate user input
// BAD: $db->query("SELECT * FROM users WHERE id = " . $_GET['id']);
```

### XSS Prevention

```php
// Escape output in PHP
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// In Mustache templates, {{ }} automatically escapes
{{user_input}}      <!-- Escaped -->
{{{user_input}}}    <!-- NOT escaped - use with caution -->
```

### CSRF Protection

```php
// Generate token
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify token
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// In forms
<input type="hidden" name="csrf_token" value="{{csrf_token}}">

// In controllers
if (!$this->verifyCsrfToken($_POST['csrf_token'])) {
    throw new \Exception('Invalid CSRF token', 403);
}
```

### Password Hashing

```php
// Hash password
$hash = password_hash($password, PASSWORD_ARGON2ID);

// Verify password
if (password_verify($inputPassword, $storedHash)) {
    // Password correct
}

// Check if rehash needed
if (password_needs_rehash($storedHash, PASSWORD_ARGON2ID)) {
    $newHash = password_hash($password, PASSWORD_ARGON2ID);
    // Update in database
}
```

---

## Performance Optimization

### Database Query Optimization

```php
// Use indexes
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_plugin_slug ON plugins(slug);

// Limit results
$stmt = $db->prepare("SELECT * FROM users LIMIT :limit OFFSET :offset");

// Select only needed columns
$stmt = $db->prepare("SELECT id, username, email FROM users");
// Instead of: SELECT *

// Use JOIN instead of multiple queries
$stmt = $db->prepare("
    SELECT u.*, r.name as role_name
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.id = :id
");
```

### Caching

```php
// Simple file cache
class Cache
{
    private string $cacheDir;

    public function get(string $key, callable $callback, int $ttl = 3600)
    {
        $file = $this->cacheDir . '/' . md5($key) . '.cache';

        if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
            return unserialize(file_get_contents($file));
        }

        $data = $callback();
        file_put_contents($file, serialize($data));

        return $data;
    }

    public function forget(string $key): void
    {
        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Usage
$cache = new Cache(__DIR__ . '/storage/cache');

$plugins = $cache->get('enabled_plugins', function() use ($pluginManager) {
    return $pluginManager->getEnabled();
}, 3600);
```

### Asset Optimization

```php
// Minify CSS/JS in production
if (ENVIRONMENT === 'production') {
    $css = file_get_contents('styles.css');
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace([' {', '{ ', ' }', '; '], ['{', '{', '}', ';'], $css);
    file_put_contents('styles.min.css', $css);
}

// Combine assets
$combinedCss = '';
foreach ($cssFiles as $file) {
    $combinedCss .= file_get_contents($file);
}
file_put_contents('combined.css', $combinedCss);
```

---

## Appendix: Useful Resources

### Official Documentation

- **PHP Manual**: https://www.php.net/manual/
- **PSR Standards**: https://www.php-fig.org/psr/
- **Composer**: https://getcomposer.org/doc/
- **Mustache**: https://mustache.github.io/
- **Monolog**: https://github.com/Seldaek/monolog

### Tools

- **PHPStorm**: https://www.jetbrains.com/phpstorm/
- **PHP_CodeSniffer**: https://github.com/squizlabs/PHP_CodeSniffer
- **PHPStan**: https://phpstan.org/
- **PHPUnit**: https://phpunit.de/

### Community

- **GitHub**: https://github.com/nexosupport/nexosupport
- **Forum**: https://community.nexosupport.com
- **Discord**: https://discord.gg/nexosupport

---

**Document Version**: 1.0.0
**Last Updated**: 2025-11-12
**License**: MIT
