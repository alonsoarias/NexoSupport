# Análisis Completo del Flujo de Login

## Resumen del Problema

Tu sistema de autenticación tiene **debug completo** pero la contraseña en la base de datos **NO coincide** con la que estás intentando usar.

## Estado Actual (Según debug-auth.php)

```
Usuario: admin
Email: alonsoariasb@gmail.com
Status: ACTIVE
Hash en DB: $argon2id$v=19$m=65536,t=4,p=1 (Argon2id)
Contraseña probada: admin123 ✗ FALLÓ
```

**El problema:** El hash en la base de datos es **Argon2id**, pero tu contraseña real es **"Admin.123+"**, NO "admin123".

## Flujo de Autenticación (Completo)

### 1. Usuario envía formulario
```
Formulario: /resources/views/auth/login.mustache
├── Campo: username (name="username")
├── Campo: password (name="password")
└── Action: POST /login
```

### 2. Router recibe POST
```
Archivo: /public_html/index.php línea 125
├── Ruta: POST /login
├── Handler: AuthController::processLogin()
└── Inyecta: Database $database
```

### 3. AuthController procesa
```
Archivo: /modules/Controllers/AuthController.php:73
├── Obtiene $body = $request->getParsedBody()
├── Extrae: $username = $body['username']
├── Extrae: $password = $body['password']
└── Llama: $authService->authenticate($username, $password, $ip)
```

**Logs generados:**
```
[AuthController] ======== LOGIN REQUEST START ========
[AuthController] Request method: POST
[AuthController] Username: 'admin'
[AuthController] Password present: YES
[AuthController] Password length: 10
[AuthController] POST data keys: ["username","password"]
```

### 4. AuthService autentica
```
Archivo: /core/Auth/AuthService.php:40
├── Verifica si cuenta está bloqueada
├── Busca usuario por username
├── Si no existe, busca por email
├── Verifica contraseña con Helpers::verifyPassword()
├── Verifica status del usuario
└── Si todo OK: return $user
```

**Logs generados:**
```
[AuthService] Attempting authentication for: admin
[UserManager::getUserByUsername] Looking for username: 'admin'
[UserManager::getUserByUsername] Result: found ID 1
[AuthService] User found - ID: 1, Username: admin, Status: active
[AuthService] Password hash from DB: $argon2id$v=19$m=65536...
[Helpers::verifyPassword] Hash algorithm: argon2id
[Helpers::verifyPassword] Password length: 10
[Helpers::verifyPassword] Verification result: FAILED
[AuthService] Password mismatch for user: admin
```

### 5. Helpers::verifyPassword
```
Archivo: /core/Utils/Helpers.php:89
├── Obtiene info del hash: password_get_info($hash)
├── Log: algoritmo, preview del hash, longitud
├── Ejecuta: password_verify($password, $hash)
└── Return: true/false
```

**El problema está aquí:** `password_verify()` devuelve `false` porque:
- Hash en DB: Argon2id con contraseña desconocida
- Contraseña que intentas: "Admin.123+"
- **NO COINCIDEN**

### 6. Si falla
```
AuthController:
├── Log: "Authentication FAILED"
├── Set: $_SESSION['login_error'] = 'auth.invalid_credentials'
└── Redirect: /login
```

### 7. Si tiene éxito
```
AuthController:
├── Log: "Authentication SUCCESS"
├── Llama: $authService->createSession($user)
├── Set: $_SESSION['user_id']
├── Set: $_SESSION['authenticated']
└── Redirect: /dashboard
```

## ¿Por Qué Falla Tu Login?

```
╔════════════════════════════════════════════════════════════════╗
║  RAZÓN PRINCIPAL: CONTRASEÑA NO COINCIDE                       ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  Hash en DB:        $argon2id$... (algoritmo Argon2id)       ║
║  Contraseña real:   Desconocida (fue cambiada en algún punto)║
║  Intentas con:      Admin.123+                                ║
║                                                                ║
║  Resultado:         password_verify() = FALSE                 ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

## El Código Está Correcto

✅ Formulario HTML envía datos correctamente
✅ Router recibe y procesa POST correctamente
✅ AuthController obtiene username y password correctamente
✅ Request->getParsedBody() funciona correctamente
✅ AuthService busca usuario correctamente
✅ Helpers::verifyPassword ejecuta correctamente
✅ Session se crearía correctamente si password coincidiera

**El ÚNICO problema:** La contraseña en la base de datos NO es "Admin.123+"

## Solución

### Opción 1: Script Automático (RECOMENDADO)

```bash
cd C:\MAMP\htdocs\NexoSupport
fix-login-complete.bat
```

Este script:
1. ✓ Verifica tabla login_attempts
2. ✓ Verifica usuario admin
3. ✓ **Actualiza contraseña a "Admin.123+" con bcrypt**
4. ✓ Limpia bloqueos y intentos fallidos
5. ✓ Muestra credenciales finales

### Opción 2: Manual

```bash
cd C:\MAMP\htdocs\NexoSupport
php tools\update-admin-password.php "Admin.123+"
```

## Después de Actualizar la Contraseña

1. Abre: https://nexosupport.localhost.com/login
2. Usuario: `admin`
3. Contraseña: `Admin.123+`
4. Click en "Iniciar Sesión"

**Logs que deberías ver (en C:\MAMP\logs\php_error.log):**

```
[AuthController] ======== LOGIN REQUEST START ========
[AuthController] Username: 'admin'
[AuthController] Password present: YES
[AuthController] Password length: 10
[UserManager::getUserByUsername] Result: found ID 1
[AuthService] User found - ID: 1, Username: admin, Status: active
[Helpers::verifyPassword] Hash algorithm: bcrypt
[Helpers::verifyPassword] Verification result: SUCCESS
[AuthService] Password verification: SUCCESS
[AuthService] Authentication SUCCESSFUL for user: admin
[AuthController] Authentication SUCCESS, creating session
[AuthController] Session created, redirecting to dashboard
```

## Si Aún Falla Después de Actualizar

Ejecuta el diagnóstico completo:

```bash
diagnostic.bat
```

Y comparte TODO el output, especialmente:
- Hash algorithm actual en DB
- Resultado de password verification
- Logs de [AuthController] y [AuthService]

## Resumen Visual del Flujo

```
┌─────────────────────────────────────────────────────────────────┐
│                     FLUJO DE AUTENTICACIÓN                       │
└─────────────────────────────────────────────────────────────────┘

  Usuario ingresa datos
         │
         ├──> Formulario: resources/views/auth/login.mustache
         │    Fields: username, password
         │    Method: POST, Action: /login
         │
         ▼
  Router captura POST
         │
         ├──> public_html/index.php línea 125
         │    Route: POST /login
         │    Handler: AuthController::processLogin
         │
         ▼
  AuthController procesa
         │
         ├──> modules/Controllers/AuthController.php:73
         │    - Obtiene POST data
         │    - Extrae username y password
         │    - Log: Request info
         │
         ▼
  AuthService autentica
         │
         ├──> core/Auth/AuthService.php:40
         │    - Busca usuario en DB
         │    - Verifica si está bloqueado
         │    - Llama a verifyPassword
         │
         ▼
  Helpers::verifyPassword
         │
         ├──> core/Utils/Helpers.php:89
         │    - Lee hash de DB
         │    - Log: algoritmo y preview
         │    - Ejecuta: password_verify()
         │
         ▼
  ┌─────────────────┐
  │ ¿Password OK?   │
  └─────────────────┘
         │
    ┌────┴────┐
    │         │
   SÍ        NO
    │         │
    │         └──> Set error
    │              Redirect /login
    │              Mensaje: auth.invalid_credentials
    │
    └──> Create session
         Set user_id
         Set authenticated
         Redirect /dashboard
         ✓ LOGIN EXITOSO
```

## Archivos Importantes

### Frontend
- `resources/views/auth/login.mustache` - Formulario de login
- Envía POST con: username, password

### Routing
- `public_html/index.php:125` - Ruta POST /login
- Handler: AuthController::processLogin

### Controllers
- `modules/Controllers/AuthController.php`
  - showLogin() - Muestra formulario
  - processLogin() - Procesa autenticación
  - logout() - Cierra sesión

### Services
- `core/Auth/AuthService.php`
  - authenticate() - Lógica de autenticación
  - createSession() - Crea sesión
  - destroySession() - Destruye sesión

### Managers
- `modules/User/UserManager.php`
  - getUserByUsername() - Busca por username
  - getUserByEmail() - Busca por email
  - recordLoginAttempt() - Registra intento
  - isAccountLocked() - Verifica bloqueo

### Utils
- `core/Utils/Helpers.php`
  - verifyPassword() - Verifica contraseña
  - hashPassword() - Genera hash bcrypt

## Debugging Completo Implementado

Todos estos archivos tienen logging extensivo:

✅ AuthController - Request details
✅ AuthService - Authentication flow
✅ UserManager - Database queries
✅ Helpers::verifyPassword - Hash verification

Los logs aparecen en: `C:\MAMP\logs\php_error.log`

Filtrar por: `[Auth` para ver solo logs de autenticación
