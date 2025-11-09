# ISER Authentication System

Sistema de Autenticación Modular basado en PHP 8.1+ con arquitectura inspirada en Moodle.

## 📋 Fase 1: Núcleo del Sistema

Esta fase establece la base técnica del sistema con todas las dependencias y estructura modular.

### Estado del Proyecto

- ✅ **Fase 1**: Núcleo del Sistema - **COMPLETADA**
- ⏳ **Fase 2**: Autenticación Manual - En planificación
- ⏳ **Fase 3**: Módulos Avanzados - En planificación

## 🚀 Características Implementadas (Fase 1)

### Núcleo del Sistema
- ✅ Sistema de configuración con `.env` o `config.php` (exclusivo)
- ✅ Gestión de entornos (desarrollo, producción, testing)
- ✅ Validación de requisitos del sistema (PHP 8.1+, extensiones)
- ✅ Autoloader PSR-4 personalizado con soporte modular
- ✅ Bootstrap ordenado del sistema

### Base de Datos
- ✅ Conexión PDO con MySQL/MariaDB
- ✅ Patrón Singleton para conexiones
- ✅ Abstracción de operaciones DB
- ✅ Soporte para transacciones anidadas
- ✅ Prepared statements automáticos

### Logging
- ✅ Integración con Monolog
- ✅ Múltiples canales (system, auth, database, security, api, error)
- ✅ Rotación automática de archivos
- ✅ Niveles de log configurables

### Routing
- ✅ Sistema de enrutamiento tipo Moodle
- ✅ Soporte para parámetros dinámicos
- ✅ Middleware global y por ruta
- ✅ Named routes
- ✅ Manejo de errores 404/500

### Sesiones JWT
- ✅ Generación y validación de tokens JWT
- ✅ Access tokens y refresh tokens
- ✅ Configuración flexible de expiración
- ✅ Múltiples métodos de transporte (header, cookie)

### Testing
- ✅ PHPUnit 10.5 configurado
- ✅ Tests unitarios del core
- ✅ Tests de integración
- ✅ Bootstrap de testing

## 📦 Requisitos del Sistema

### Requeridos
- **PHP**: 8.1 o superior
- **Extensiones PHP**:
  - pdo
  - pdo_mysql
  - json
  - mbstring
  - openssl
  - session
  - ctype
  - hash

### Recomendados
- curl
- gd
- xml
- zip

### Base de Datos
- MySQL 5.7+ o MariaDB 10.3+

## 🛠️ Instalación

### 1. Clonar el Repositorio

```bash
git clone <repository-url>
cd iser-auth-system
```

### 2. Instalar Dependencias

```bash
composer install
```

### 3. Configurar el Entorno

```bash
cp .env.example .env
```

Editar `.env` con tu configuración:

```env
APP_ENV=development
APP_DEBUG=true
APP_NAME="ISER Auth System"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=iser_auth
DB_USERNAME=root
DB_PASSWORD=your_password

JWT_SECRET=your-secret-key-here-change-in-production
```

### 4. Generar JWT Secret

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 5. Configurar Permisos

```bash
chmod 755 var/logs var/cache
```

### 6. Verificar Requisitos

```bash
composer check-requirements
```

## 🧪 Ejecutar Tests

### Todos los Tests

```bash
composer test
```

### Con Cobertura

```bash
composer test-coverage
```

### Tests Específicos

```bash
vendor/bin/phpunit tests/Unit/Core/EnvironmentTest.php
```

## 📁 Estructura del Proyecto

```
iser-auth-system/
├── public_html/          # Document root
│   ├── index.php        # Punto de entrada principal
│   ├── admin.php        # Panel de administración
│   ├── login.php        # Página de login
│   ├── .htaccess        # Configuración Apache
│   └── assets/          # Assets estáticos
├── core/                # Núcleo del sistema
│   ├── Bootstrap.php    # Inicializador del sistema
│   ├── Autoloader.php   # Autoloader personalizado
│   ├── Config/          # Gestión de configuración
│   ├── Database/        # Capa de base de datos
│   ├── Router/          # Sistema de routing
│   ├── Session/         # Manejo de sesiones JWT
│   ├── Utils/           # Utilidades (Logger, Helpers)
│   └── Interfaces/      # Interfaces del sistema
├── modules/             # Módulos del sistema
│   ├── auth/           # Módulos de autenticación
│   ├── admin/          # Módulos de administración
│   ├── user/           # Módulos de usuario
│   ├── theme/          # Temas
│   └── report/         # Reportes
├── tests/              # Tests PHPUnit
│   ├── Unit/           # Tests unitarios
│   ├── Integration/    # Tests de integración
│   └── bootstrap.php   # Bootstrap de tests
├── var/                # Datos variables
│   ├── logs/           # Archivos de log
│   └── cache/          # Cache del sistema
├── vendor/             # Dependencias de Composer
├── composer.json       # Configuración de Composer
├── phpunit.xml         # Configuración de PHPUnit
└── .env                # Configuración del entorno
```

## 🔧 Configuración

### Configuración mediante .env

El sistema utiliza archivos `.env` para la configuración. **No incluyas el archivo `.env` en el control de versiones.**

### Configuración mediante config.php

Alternativamente, puedes usar `config.php`:

```php
<?php
return [
    'APP_ENV' => 'production',
    'APP_DEBUG' => false,
    'DB_HOST' => 'localhost',
    // ...
];
```

**Nota**: Solo puedes usar **uno** de los métodos (`.env` O `config.php`), no ambos.

## 📝 Uso Básico

### Inicializar el Sistema

```php
use ISER\Core\Bootstrap;

$app = new Bootstrap(__DIR__);
$app->init();
```

### Agregar Rutas

```php
$router = $app->getRouter();

$router->get('/', function() {
    return 'Hello World!';
});

$router->post('/api/data', 'ApiController@handleData');
```

### Usar la Base de Datos

```php
$db = $app->getDatabase();

// Insert
$id = $db->insert('users', [
    'username' => 'john',
    'email' => 'john@example.com'
]);

// Select
$user = $db->selectOne('users', ['id' => $id]);

// Update
$db->update('users', ['email' => 'newemail@example.com'], ['id' => $id]);
```

### Logging

```php
use ISER\Core\Utils\Logger;

Logger::info('User logged in', ['user_id' => 123]);
Logger::error('Database connection failed', ['error' => $e->getMessage()]);
Logger::security('Failed login attempt', ['username' => 'admin']);
```

### Generar JWT Token

```php
$jwt = $app->getJWTSession();

$tokens = $jwt->generateTokenPair([
    'user_id' => 123,
    'username' => 'john',
    'roles' => ['user']
]);

echo $tokens['access_token'];
```

## 🔒 Seguridad

### Mejores Prácticas Implementadas

- ✅ Prepared statements para prevenir SQL injection
- ✅ Escape de HTML para prevenir XSS
- ✅ JWT para manejo seguro de sesiones
- ✅ Bcrypt para hashing de contraseñas
- ✅ Headers de seguridad en `.htaccess`
- ✅ Protección de archivos sensibles
- ✅ Validación de entradas

### Headers de Seguridad

El archivo `.htaccess` incluye:
- X-Content-Type-Options
- X-XSS-Protection
- X-Frame-Options
- Referrer-Policy

## 📊 Logging

### Canales Disponibles

- `system`: Eventos generales del sistema
- `auth`: Eventos de autenticación
- `database`: Operaciones de base de datos
- `security`: Eventos de seguridad
- `api`: Llamadas API
- `error`: Errores del sistema

### Niveles de Log

- DEBUG
- INFO
- NOTICE
- WARNING
- ERROR
- CRITICAL
- ALERT
- EMERGENCY

### Ubicación de Logs

Los logs se almacenan en `var/logs/` con rotación automática.

## 🧩 Arquitectura Modular

El sistema sigue una arquitectura modular inspirada en Moodle:

### Crear un Módulo

```php
namespace ISER\Modules\Auth\Manual;

use ISER\Core\Interfaces\ModuleInterface;

class ManualAuth implements ModuleInterface
{
    public function init(): void { }
    public function getName(): string { return 'manual'; }
    public function getRoutes(): array { return []; }
    // ... implementar otros métodos
}
```

## 🔄 API Endpoints (Fase 1)

### Sistema

```
GET /api/system-info  - Información del sistema
GET /api/health       - Health check
```

## 📈 Próximas Fases

### Fase 2: Autenticación Manual
- Sistema completo de login/logout
- Gestión de usuarios
- Roles y permisos
- Recuperación de contraseña
- MFA (Multi-Factor Authentication)
- reCAPTCHA

### Fase 3: Módulos Avanzados
- Reportes avanzados
- Dashboard de administración
- Auditoría completa
- Integración con sistemas externos

## 🤝 Contribuir

Este es un proyecto propietario de ISER. Para contribuir, contacta al equipo de desarrollo.

## 📄 Licencia

Proprietary - ISER © 2024

## 👥 Equipo de Desarrollo

ISER Development Team

## 📞 Soporte

Para soporte técnico, contacta a: dev@iser.edu

## 🎯 Validación de la Fase 1

### Checklist de Funcionalidades

- ✅ El sistema carga correctamente con todas las dependencias
- ✅ La configuración desde .env funciona correctamente
- ✅ La conexión a base de datos se establece sin errores
- ✅ El autoloader encuentra todas las clases del core
- ✅ El router responde a URLs básicas
- ✅ El sistema de logging registra eventos correctamente
- ✅ Los tests PHPUnit ejecutan sin errores
- ✅ La estructura modular está reconocida por el sistema

### Verificación

```bash
# 1. Instalar dependencias
composer install

# 2. Ejecutar tests
composer test

# 3. Verificar requisitos
composer check-requirements

# 4. Acceder al sistema
# Abrir http://localhost/public_html/ en el navegador
```

## 📚 Documentación Adicional

- [Moodle Architecture](https://docs.moodle.org/dev/Core_APIs) - Referencia de arquitectura
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/) - Estándar de autoloading
- [JWT RFC 7519](https://tools.ietf.org/html/rfc7519) - Especificación JWT

---

**Versión**: 1.0.0 - Fase 1 Completada
**Fecha**: 2024
