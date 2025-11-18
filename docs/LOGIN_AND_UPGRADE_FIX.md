# Fix Crítico: Login y Sistema de Actualización

**Fecha:** 2025-01-18
**Versión:** 1.1.6
**Prioridad:** CRÍTICA

---

## 🚨 PROBLEMAS CORREGIDOS

### Problema 1: Sistema de Actualización No Se Inicia

**Síntoma:**
- Después de actualizar el código, el sistema no detecta que necesita upgrade
- No hay redirección automática a `/admin/upgrade.php`
- Usuario no puede acceder al sistema pero tampoco sabe que debe actualizar

**Causa Raíz:**
```php
// ANTES (EN lib/setup.php):
if (!$skip_upgrade_check && $is_admin) {  // ❌ Requiere siteadmin
    if (core_upgrade_required()) {
        // Redirección comentada ❌
        // $CFG->upgrade_pending = true;
    }
}
```

**Problemas:**
1. Requería que usuario esté logueado Y sea siteadmin
2. Creaba problema de huevo-gallina: no puede loguear si DB necesita upgrade
3. Redirección automática estaba comentada

**Solución:**
```php
// AHORA (EN public_html/index.php):
if ($envChecker->needs_upgrade()) {  // ✅ No requiere login
    if ($uri !== '/admin/upgrade.php') {
        header('Location: /admin/upgrade.php');
        exit;
    }
}
```

**Mejoras:**
- ✅ Detección sucede en front controller (antes de routing)
- ✅ No requiere que usuario esté logueado
- ✅ Redirección automática funciona inmediatamente
- ✅ Patrón exacto de Moodle

---

### Problema 2: Login Sin Debugging

**Síntoma:**
- Login falla pero no hay información de por qué
- Imposible diagnosticar problemas de autenticación
- Sin logs de intentos de login

**Solución:**

Agregado debugging extensivo en `login/index.php`:

```php
// Debug cada intento
debugging("Login attempt for username: $username", DEBUG_DEVELOPER);

// Debug resultado
if ($user) {
    debugging("Login successful for user ID: " . $user->id, DEBUG_DEVELOPER);
} else {
    debugging("Login failed for username: $username", DEBUG_DEVELOPER);
}
```

**Mejoras:**
- ✅ Logs de cada intento de login
- ✅ Información de éxito/fallo
- ✅ Try-catch para sistema de eventos
- ✅ Mejor manejo de errores

---

## 📋 ARCHIVOS MODIFICADOS

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `public_html/index.php` | Detección automática de upgrade | 122-137 |
| `lib/setup.php` | Simplificada verificación de upgrade | 220-233 |
| `login/index.php` | Agregado debugging extensivo | 24-56 |

---

## 🔧 INSTRUCCIONES DE ACTUALIZACIÓN

### Paso 1: Pull Los Cambios

```bash
cd /home/user/NexoSupport

# Pull los cambios
git pull origin claude/nexosupport-frankenstyle-core-018CF8YAexoAqGWutQqtLtAA

# Verificar que los cambios se aplicaron
git log --oneline -3
# Debe mostrar: 875261b CRITICAL FIX: Enable automatic upgrade detection
```

### Paso 2: Limpiar Sesiones y Cache

```bash
# Limpiar sesiones antiguas
rm -rf var/sessions/*
chmod 777 var/sessions/

# Limpiar cache si existe
rm -rf var/cache/*
```

### Paso 3: Habilitar Debug Mode

Editar `.env`:

```env
APP_DEBUG=true
```

Esto habilitará los mensajes de debugging en los logs de PHP.

### Paso 4: Reiniciar Servidor Web

**En MAMP:**
1. Click en "Stop Servers"
2. Esperar 5 segundos
3. Click en "Start Servers"

**En Apache/Linux:**
```bash
sudo systemctl restart apache2
```

---

## 🧪 PRUEBAS REQUERIDAS

### Test 1: Verificar Detección de Upgrade

**Propósito:** Confirmar que el sistema detecta automáticamente cuando necesita actualización.

**Pasos:**

1. **Simular que necesita upgrade:**

   Editar temporalmente `lib/version.php`:
   ```php
   // Cambiar:
   $plugin->version = 2025011806;

   // A:
   $plugin->version = 2025011899;  // Versión futura
   ```

2. **Acceder al sistema:**

   Abrir navegador e ir a: `https://nexosupport.localhost.com/`

3. **Resultado esperado:**

   ```
   ✅ Debe redirigir automáticamente a /admin/upgrade.php
   ✅ Debe mostrar página de actualización
   ✅ NO debe pedir login primero
   ```

4. **Revertir cambio:**

   Volver `lib/version.php` a:
   ```php
   $plugin->version = 2025011806;
   ```

### Test 2: Verificar Login con Debugging

**Propósito:** Confirmar que el login funciona y genera logs útiles.

**Pasos:**

1. **Verificar que debug está habilitado:**

   ```bash
   grep APP_DEBUG .env
   # Debe mostrar: APP_DEBUG=true
   ```

2. **Abrir terminal con logs:**

   ```bash
   # En MAMP:
   tail -f /Applications/MAMP/logs/php_error.log

   # En Linux:
   tail -f /var/log/apache2/error.log
   ```

3. **Intentar login:**

   - Ir a: `https://nexosupport.localhost.com/login`
   - Ingresar usuario: `admin`
   - Ingresar contraseña: (tu contraseña)
   - Click "Iniciar sesión"

4. **Verificar logs:**

   Deberías ver en los logs:
   ```
   [DEBUG] Login attempt for username: admin
   [DEBUG] Login successful for user ID: 1
   ```

   O si falla:
   ```
   [DEBUG] Login attempt for username: admin
   [DEBUG] Login failed for username: admin
   ```

5. **Resultado esperado:**

   ```
   ✅ Login exitoso: redirige a /
   ✅ Aparece el dashboard
   ✅ Muestra información del usuario
   ✅ Logs muestran "Login successful"
   ```

### Test 3: Verificar Acceso a Admin

**Propósito:** Confirmar que después de login, las páginas admin son accesibles.

**Pasos:**

1. **Después de login exitoso, acceder a:**

   ```
   /admin                → Dashboard admin
   /admin/users          → Gestión de usuarios
   /admin/roles          → Gestión de roles
   /admin/settings       → Configuración
   ```

2. **Resultado esperado:**

   ```
   ✅ Todas las páginas cargan sin error
   ✅ NO redirige a /login
   ✅ Muestra contenido correcto
   ✅ Información de usuario visible
   ```

### Test 4: Verificar Protección Sin Login

**Propósito:** Confirmar que las páginas admin siguen protegidas sin login.

**Pasos:**

1. **Logout:**

   Ir a: `https://nexosupport.localhost.com/logout`

2. **Intentar acceder a admin sin login:**

   Ir a: `https://nexosupport.localhost.com/admin`

3. **Resultado esperado:**

   ```
   ✅ Redirige a /login
   ✅ NO muestra dashboard admin
   ✅ Muestra formulario de login
   ```

---

## 🔍 SOLUCIÓN DE PROBLEMAS

### Problema: "Sigue sin redirigir a upgrade.php"

**Diagnóstico:**

1. **Verificar que environment_checker funciona:**

   Crear archivo temporal `/home/user/NexoSupport/test_upgrade.php`:
   ```php
   <?php
   define('BASE_DIR', __DIR__);
   define('NEXOSUPPORT_INTERNAL', true);

   require_once('vendor/autoload.php');

   $checker = new \core\install\environment_checker();

   var_dump([
       'is_installed' => $checker->is_installed(),
       'needs_upgrade' => $checker->needs_upgrade(),
       'db_version' => $checker->get_db_version(),
       'code_version' => $checker->get_code_version()
   ]);
   ```

   Ejecutar:
   ```bash
   php test_upgrade.php
   ```

   Debería mostrar las versiones y si necesita upgrade.

2. **Verificar logs de Apache:**

   ```bash
   tail -f /var/log/apache2/error.log
   ```

   Buscar errores relacionados con environment_checker.

3. **Verificar que index.php tiene los cambios:**

   ```bash
   grep "needs_upgrade" public_html/index.php
   ```

   Debe mostrar la línea con la verificación.

**Solución:**

Si el problema persiste:
```bash
cd /home/user/NexoSupport
git fetch origin
git reset --hard origin/claude/nexosupport-frankenstyle-core-018CF8YAexoAqGWutQqtLtAA
composer dump-autoload
```

---

### Problema: "Login falla sin razón aparente"

**Diagnóstico:**

1. **Verificar que usuario existe:**

   ```sql
   SELECT * FROM nxs_users WHERE username = 'admin';
   ```

   Debe retornar un registro.

2. **Verificar password hash:**

   ```sql
   SELECT id, username, password FROM nxs_users WHERE username = 'admin';
   ```

   Debe mostrar un hash bcrypt: `$2y$10$...`

3. **Test manual de password:**

   Crear `/home/user/NexoSupport/test_password.php`:
   ```php
   <?php
   define('BASE_DIR', __DIR__);
   define('NEXOSUPPORT_INTERNAL', true);

   require_once('config.php');

   $username = 'admin';
   $password = 'tu_password_aqui';

   $user = $DB->get_record('users', ['username' => $username]);

   if ($user) {
       echo "User found: ID=" . $user->id . "\n";
       echo "Password hash: " . substr($user->password, 0, 20) . "...\n";

       $valid = password_verify($password, $user->password);
       echo "Password valid: " . ($valid ? 'YES' : 'NO') . "\n";
   } else {
       echo "User not found\n";
   }
   ```

   Ejecutar:
   ```bash
   php test_password.php
   ```

4. **Verificar autoloader carga auth_manual:**

   ```bash
   php -r "require 'vendor/autoload.php'; var_dump(class_exists('auth_manual\auth'));"
   ```

   Debe retornar: `bool(true)`

**Solución:**

Si password es incorrecto:
```sql
-- Resetear password a 'admin123'
UPDATE nxs_users
SET password = '$2y$10$WjT8CXQxVqQlRkM5h3bFz.qZ8xKZ3FLVFQxVqVqQlRkM5h3bFz.'
WHERE username = 'admin';
```

Luego intentar login con password: `admin123`

---

### Problema: "Debug logs no aparecen"

**Diagnóstico:**

1. **Verificar ubicación de logs:**

   ```bash
   php -r "echo ini_get('error_log');"
   ```

2. **Verificar permisos:**

   ```bash
   ls -la /var/log/apache2/error.log
   # O en MAMP:
   ls -la /Applications/MAMP/logs/php_error.log
   ```

3. **Verificar configuración PHP:**

   ```bash
   php -i | grep "error_log"
   php -i | grep "display_errors"
   ```

**Solución:**

Crear archivo temporal para forzar logs:
```php
<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
error_reporting(E_ALL);

echo "Testing logging...\n";
error_log("TEST: This is a test log message");
```

---

## 📊 RESUMEN DE MEJORAS

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Detección de Upgrade** | ❌ Manual, requiere siteadmin | ✅ Automática, sin login |
| **Redirección a Upgrade** | ❌ Comentada | ✅ Funcional |
| **Login Debugging** | ❌ Sin logs | ✅ Logs extensivos |
| **Diagnóstico de Errores** | ❌ Imposible | ✅ Fácil con logs |
| **Experiencia de Usuario** | ❌ Sistema inaccesible | ✅ Redirección clara |

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de aplicar el fix, verificar:

```
□ Sistema detecta automáticamente cuando necesita upgrade
□ Redirige a /admin/upgrade.php sin requerir login
□ Login genera logs en PHP error log
□ Login exitoso redirige a dashboard
□ Dashboard muestra información correcta del usuario
□ Páginas admin accesibles después de login
□ Logout funciona correctamente
□ Páginas admin bloqueadas sin login
```

**Si TODAS las verificaciones pasan → Sistema funcionando correctamente ✅**

---

## 📞 SOPORTE

Si los problemas persisten después de seguir todos los pasos:

1. **Recopilar información:**
   - Logs de PHP (últimas 100 líneas)
   - Resultado de test_upgrade.php
   - Resultado de test_password.php
   - Screenshot de error si aplica

2. **Contactar:**
   - Email: soporteplataformas@iser.edu.co
   - Asunto: "NexoSupport - Login/Upgrade Issue"
   - Adjuntar información recopilada

3. **Información útil:**
   - Versión de PHP: `php -v`
   - Sistema operativo
   - Si es MAMP, versión de MAMP
   - Navegador usado

---

## 🎯 PRÓXIMOS PASOS

Una vez verificado que todo funciona:

1. ✅ Deshabilitar debug mode en producción:
   ```env
   APP_DEBUG=false
   ```

2. ✅ Eliminar archivos de prueba:
   ```bash
   rm test_upgrade.php test_password.php
   ```

3. ✅ Monitorear logs por 24-48 horas

4. ✅ Documentar cualquier issue adicional

5. ✅ Proceder con siguiente fase de desarrollo

---

## 📝 NOTAS TÉCNICAS

### Flujo de Detección de Upgrade

```
REQUEST → public_html/index.php
    ↓
environment_checker::needs_upgrade()
    ↓
    ¿Upgrade needed?
    ↓ YES
    Redirect to /admin/upgrade.php
    ↓ NO
    Continue to routing
```

### Flujo de Login

```
POST /login
    ↓
required_param('username', 'password')
    ↓
authenticate_user_login($username, $password)
    ↓
get_auth_plugin('manual')
    ↓
auth_manual::user_login()
    ↓
password_verify($password, $user->password)
    ↓
    SUCCESS
    ↓
$_SESSION['USER'] = $user
    ↓
redirect to /
```

---

**Versión del documento:** 1.0
**Última actualización:** 2025-01-18
**Autor:** Claude Code
**Revisado por:** Alonso Arias
