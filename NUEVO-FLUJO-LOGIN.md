# Sistema de Autenticación Simplificado - NexoSupport

## Resumen de Cambios

El sistema de autenticación ha sido completamente refactorizado para ser **más simple, robusto y fácil de depurar**.

### Cambios Principales

1. ✅ **AuthController simplificado** - Toda la lógica en un solo lugar
2. ✅ **AuthService eliminado** - Ya no es necesario, complejidad reducida
3. ✅ **UserManager limpio** - Solo métodos CRUD, sin lógica de auth
4. ✅ **Helpers simplificado** - Sin logging innecesario
5. ✅ **Logging centralizado** - Todo en AuthController con prefijo `[LOGIN]`
6. ✅ **Flujo directo** - Form → Router → Controller → Database → Session

---

## Arquitectura Simplificada

```
┌──────────────┐
│   USUARIO    │
└──────┬───────┘
       │ Ingresa username y password
       ▼
┌──────────────────────────────────────┐
│  FORMULARIO (login.mustache)         │
│  - method="POST"                     │
│  - action="/login"                   │
│  - Fields: username, password        │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│  ROUTER (index.php:125)              │
│  POST /login → AuthController        │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│  AUTHCONTROLLER                      │
│  processLogin()                      │
│                                      │
│  1. Obtiene username y password      │
│  2. Busca usuario en DB              │
│  3. Verifica contraseña con bcrypt   │
│  4. Valida status y bloqueos         │
│  5. Crea sesión si OK                │
│  6. Registra intentos en DB          │
│                                      │
│  ✓ Todo el logging aquí             │
│  ✓ Todo el control de flujo aquí    │
└──────────────┬───────────────────────┘
               │
               ├─ ÉXITO → /dashboard
               └─ FALLO → /login (con mensaje error)
```

---

## Flujo Paso a Paso

### 1. Usuario Envía Formulario

**Archivo:** `resources/views/auth/login.mustache`

```html
<form method="POST" action="/login">
    <input type="text" name="username" required autofocus>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

### 2. Router Captura POST

**Archivo:** `public_html/index.php` línea 125

```php
$router->post('/login', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->processLogin($request);
}, 'login.process');
```

### 3. AuthController Procesa

**Archivo:** `modules/Controllers/AuthController.php:62`

```php
public function processLogin(ServerRequestInterface $request): ResponseInterface
{
    error_log("[LOGIN] Inicio del proceso de autenticación");

    // 1. Obtener datos del formulario
    $body = $request->getParsedBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    // 2. Validar datos
    if (empty($username) || empty($password)) {
        error_log("[LOGIN ERROR] Credenciales vacías");
        $_SESSION['login_error'] = 'Debes ingresar usuario y contraseña';
        return Response::redirect('/login');
    }

    // 3. Buscar usuario
    $user = $this->userManager->getUserByUsername($username);
    if (!$user) {
        $user = $this->userManager->getUserByEmail($username);
    }

    if (!$user) {
        error_log("[LOGIN ERROR] Usuario no existe");
        $this->recordFailedAttempt($username);
        $_SESSION['login_error'] = 'Credenciales inválidas';
        return Response::redirect('/login');
    }

    // 4. Verificar status
    if ($user['status'] !== 'active' || !empty($user['deleted_at'])) {
        error_log("[LOGIN ERROR] Usuario inactivo o eliminado");
        $_SESSION['login_error'] = 'Usuario no disponible';
        return Response::redirect('/login');
    }

    // 5. Verificar bloqueos
    if (($user['locked_until'] ?? 0) > time()) {
        error_log("[LOGIN ERROR] Cuenta bloqueada");
        $_SESSION['login_error'] = 'Cuenta bloqueada temporalmente';
        return Response::redirect('/login');
    }

    // 6. Verificar contraseña
    $passwordValid = password_verify($password, $user['password']);

    if (!$passwordValid) {
        error_log("[LOGIN ERROR] Contraseña incorrecta");
        $this->recordFailedAttempt($username, $user['id']);

        // Incrementar intentos fallidos
        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;

        // Bloquear después de 5 intentos
        if ($failedAttempts >= 5) {
            $this->userManager->update($user['id'], [
                'failed_login_attempts' => $failedAttempts,
                'locked_until' => time() + 900  // 15 minutos
            ]);
            $_SESSION['login_error'] = 'Cuenta bloqueada por 15 minutos';
        } else {
            $this->userManager->update($user['id'], [
                'failed_login_attempts' => $failedAttempts
            ]);
            $_SESSION['login_error'] = 'Credenciales inválidas';
        }

        return Response::redirect('/login');
    }

    // 7. Login exitoso
    error_log("[LOGIN SUCCESS] Contraseña verificada");

    // Resetear intentos fallidos
    $this->userManager->update($user['id'], [
        'failed_login_attempts' => 0,
        'locked_until' => null,
        'last_login_at' => time(),
        'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);

    // Registrar intento exitoso
    $this->recordSuccessfulAttempt($username);

    // Crear sesión
    $this->createSession($user);

    error_log("[LOGIN SUCCESS] Sesión creada - Redirigiendo");
    return Response::redirect('/dashboard');
}
```

### 4. Creación de Sesión

```php
private function createSession(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['authenticated'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    error_log("[SESSION] Sesión creada - User ID: {$user['id']}");
}
```

### 5. Registro de Intentos

```php
private function recordFailedAttempt(string $username, ?int $userId = null): void
{
    try {
        $this->db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'success' => 0,
            'attempted_at' => time(),
        ]);
    } catch (\Exception $e) {
        error_log("[LOGIN ERROR] No se pudo registrar intento: " . $e->getMessage());
    }
}
```

---

## Archivos Modificados

### Eliminados

- ❌ `core/Auth/AuthService.php` - Ya no se necesita

### Simplificados

- ✅ `modules/Controllers/AuthController.php` - Toda la lógica centralizada
- ✅ `modules/User/UserManager.php` - Solo CRUD, sin auth logic
- ✅ `core/Utils/Helpers.php` - Sin logging extra

### Sin Cambios

- ✅ `resources/views/auth/login.mustache` - Formulario correcto
- ✅ `public_html/index.php` - Rutas correctas
- ✅ `database/schema/schema.xml` - Tabla login_attempts incluida

---

## Sistema de Logging

Todo el logging está centralizado en `AuthController` con el prefijo `[LOGIN]`:

```
[LOGIN] Inicio del proceso de autenticación
[LOGIN] Username recibido: 'admin'
[LOGIN] Password recibido: OK (longitud: 10)
[LOGIN] Buscando usuario en base de datos...
[LOGIN] Usuario encontrado - ID: 1, Username: admin
[LOGIN] Verificando contraseña...
[LOGIN] Hash algorithm: bcrypt
[LOGIN SUCCESS] Contraseña verificada correctamente
[SESSION] Sesión creada - User ID: 1
[LOGIN SUCCESS] Sesión creada - Redirigiendo a dashboard
```

**Ubicación del log:** `C:\MAMP\logs\php_error.log`

**Filtrar logs de login:**
```bash
tail -f C:\MAMP\logs\php_error.log | grep "\[LOGIN"
```

---

## Seguridad Implementada

1. ✅ **Password hashing** - bcrypt con cost 12
2. ✅ **Account lockout** - 5 intentos fallidos = 15 minutos bloqueado
3. ✅ **Session regeneration** - ID regenerado en cada login
4. ✅ **Login attempt tracking** - Todos los intentos en DB
5. ✅ **IP recording** - Se guarda IP de cada intento
6. ✅ **Status validation** - Solo usuarios activos pueden entrar
7. ✅ **Deletion check** - Usuarios eliminados no pueden entrar
8. ✅ **Trim username** - Elimina espacios accidentales

---

## Testing

### Prueba Manual

1. Actualiza la contraseña:
```bash
php tools/update-admin-password.php "Admin.123+"
```

2. Ejecuta test completo:
```bash
php tools/test-login-flow.php
```

3. Intenta hacer login:
- URL: https://nexosupport.localhost.com/login
- Usuario: `admin`
- Password: `Admin.123+`

4. Revisa los logs:
```bash
tail -50 C:\MAMP\logs\php_error.log | grep "\[LOGIN"
```

### Script de Test

**Archivo:** `tools/test-login-flow.php`

Ejecutar:
```bash
php tools/test-login-flow.php
```

Verifica:
- ✓ Sistema inicializado
- ✓ Tabla login_attempts existe
- ✓ Usuario admin existe
- ✓ Hash de contraseña correcto (bcrypt)
- ✓ Contraseña 'Admin.123+' válida
- ✓ Cuenta no bloqueada
- ✓ Intentos fallidos dentro del límite
- ✓ Sesión configurada correctamente

---

## Troubleshooting

### Problema: "auth.invalid_credentials"

**Posibles causas:**

1. **Contraseña incorrecta**
   ```bash
   php tools/update-admin-password.php "Admin.123+"
   ```

2. **Usuario no existe**
   ```bash
   php tools/debug-auth.php
   ```

3. **Cuenta bloqueada**
   ```sql
   UPDATE iser_users SET locked_until = NULL, failed_login_attempts = 0 WHERE username = 'admin';
   ```

4. **Tabla login_attempts no existe**
   - Reinstala el sistema: https://nexosupport.localhost.com/install.php

### Problema: "Cuenta bloqueada"

**Solución manual:**
```sql
UPDATE iser_users
SET locked_until = NULL,
    failed_login_attempts = 0
WHERE username = 'admin';

DELETE FROM iser_login_attempts WHERE username = 'admin';
```

### Problema: No se registran intentos

**Verificar tabla:**
```sql
SHOW TABLES LIKE '%login_attempts';
DESCRIBE iser_login_attempts;
```

**Si no existe:**
- Reinstala o crea manualmente con SQL en FIX-LOGIN-NOW.md

---

## Ventajas del Nuevo Sistema

### Antes (Complejo)

```
Form → Router → Controller → AuthService → UserManager → Helpers
                                    ↓
                              Login attempts
                              Account locking
                              Session creation
```

- ❌ Lógica dispersa en múltiples archivos
- ❌ Logging en 4 lugares diferentes
- ❌ Difícil de seguir el flujo
- ❌ Múltiples capas de abstracción

### Ahora (Simple)

```
Form → Router → Controller → UserManager
                      ↓
                Todo en un lugar
```

- ✅ Lógica centralizada en AuthController
- ✅ Logging unificado con prefijo [LOGIN]
- ✅ Flujo fácil de seguir
- ✅ Una sola capa, más directo

---

## Próximos Pasos

1. **Ejecuta el test:**
   ```bash
   php tools/test-login-flow.php
   ```

2. **Si todo pasa, haz login:**
   - https://nexosupport.localhost.com/login
   - admin / Admin.123+

3. **Revisa los logs si falla:**
   ```bash
   tail -50 C:\MAMP\logs\php_error.log | grep "\[LOGIN"
   ```

4. **Comparte el output del log si necesitas ayuda**

---

## Resumen

El sistema de autenticación ahora es:
- ✅ **Simple** - Una sola clase controla todo
- ✅ **Robusto** - Manejo completo de errores
- ✅ **Seguro** - Bloqueos, tracking, validaciones
- ✅ **Depurable** - Logging centralizado y claro
- ✅ **Mantenible** - Código limpio y directo

**Todo funciona en:** `modules/Controllers/AuthController.php`

**Logs en:** `C:\MAMP\logs\php_error.log` (prefijo: `[LOGIN]`)

**Test con:** `php tools/test-login-flow.php`
