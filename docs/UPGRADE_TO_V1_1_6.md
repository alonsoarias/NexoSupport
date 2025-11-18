# Guía de Actualización a NexoSupport v1.1.6

## ⚠️ Problema Actual

Los logs muestran tres problemas críticos:

1. **config.siteadmins no encontrado** - El sistema no tiene configurados los administradores del sitio
2. **Tabla logstore_standard_log no existe** - Falta la tabla de logs del sistema
3. **print_error() no definida** - Función faltante causaba error fatal

## ✅ Solución Implementada

He corregido todos estos problemas en el commit `ef8f6c2`. Ahora necesitas actualizar tu código local y ejecutar el upgrade.

## 📋 Pasos para Actualizar

### 1. Actualizar Código Local

```bash
cd /path/to/NexoSupport
git fetch origin
git checkout claude/nexosupport-frankenstyle-core-018CF8YAexoAqGWutQqtLtAA
git pull origin claude/nexosupport-frankenstyle-core-018CF8YAexoAqGWutQqtLtAA
```

### 2. Limpiar Caché PHP (si usas OpCache)

```bash
# Para PHP-FPM
sudo service php-fpm reload

# Para Apache con mod_php
sudo service apache2 reload

# O crear un archivo PHP temporal para limpiar OpCache:
# opcache_reset.php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared!";
} else {
    echo "OpCache not enabled";
}
?>
```

### 3. Iniciar Sesión como Administrador

1. Ir a: `https://nexosupport.localhost.com/login`
2. Ingresar con usuario: **admin** (el usuario ID=1)
3. Deberías ver el dashboard

### 4. Ejecutar Upgrade

1. Ir a: `https://nexosupport.localhost.com/admin/upgrade.php`
2. El sistema detectará que necesitas actualizar a v1.1.6
3. Hacer clic en el botón **"Upgrade"** o **"Actualizar"**
4. El upgrade ejecutará automáticamente:
   - ✅ Crear tabla `logstore_standard_log`
   - ✅ Crear tabla `user_preferences`
   - ✅ Crear tabla `user_password_history`
   - ✅ Crear tabla `user_password_resets`
   - ✅ Configurar `config.siteadmins` con los usuarios que tienen rol administrator

### 5. Verificar Actualización Exitosa

Después del upgrade, deberías ver:

```
✓ Upgrade to v1.1.6 completed successfully!
```

Y en los logs, en lugar de:
```
WARNING: config.siteadmins not found or empty in database
is_siteadmin(1) = false
```

Deberías ver:
```
Loaded siteadmins from config: 1
is_siteadmin(1) = true
```

### 6. Verificar Funcionalidad

Después del upgrade, verifica que puedes acceder a:

- ✅ `/admin` - Panel de administración
- ✅ `/admin/users` - Lista de usuarios
- ✅ `/admin/user/edit?id=1` - Editar usuario (ya no 404)
- ✅ `/admin/roles` - Lista de roles
- ✅ `/admin/roles/assign?userid=1` - Asignar roles (ya no 404)
- ✅ `/admin/settings` - Configuración del sistema

## 🔍 Debugging

Si tienes problemas, puedes revisar los logs del sistema:

**Windows (MAMP):**
```
C:\MAMP\logs\php_error.log
```

**Linux:**
```bash
tail -f /var/log/apache2/error.log
# o
tail -f /var/log/php-fpm/error.log
```

Los mensajes de debug te dirán exactamente qué está pasando con el routing y autenticación.

## 📝 Cambios Técnicos Incluidos

### 1. Función `print_error()` (lib/functions.php)
```php
function print_error(string $errorcode, string $module = 'core',
                     string $link = '', $a = null): void
```
- Compatible con Moodle
- Muestra errores y termina ejecución
- Backtrace en modo debug

### 2. Upgrade sin Siteadmins (admin/upgrade.php)
- Permite ejecutar upgrade incluso si `config.siteadmins` no existe
- Verifica rol administrator en contexto de sistema
- Fallback al primer usuario (ID=1)
- Después del upgrade, enforce siteadmins normalmente

### 3. Component Field en Config (lib/upgrade.php)
- `upgrade_core_savepoint()` ahora guarda `component='core'`
- `get_core_version_from_db()` busca con `component='core'`
- Fallback a búsqueda sin component para compatibilidad

## 🎯 Resultado Esperado

Después de completar estos pasos:

1. ✅ `config.siteadmins` estará configurado con valor "1" (o IDs de admins)
2. ✅ Todas las tablas de v1.1.6 estarán creadas
3. ✅ `is_siteadmin(1)` retornará `true`
4. ✅ Podrás acceder a todas las páginas de admin sin errores 404
5. ✅ El sistema de logging funcionará correctamente

## 💡 Nota Importante

El sistema ahora detecta automáticamente cuando necesita actualización. Si en el futuro hay una nueva versión:

1. Actualiza el código con `git pull`
2. El sistema te redirigirá automáticamente a `/admin/upgrade.php` (si eres siteadmin)
3. O mostrará una notificación en el dashboard indicando que hay upgrade pendiente

## 🆘 Soporte

Si encuentras problemas durante el upgrade:

1. Comparte los logs de PHP
2. Comparte la salida de la página `/admin/upgrade.php`
3. Verifica que la base de datos sea accesible
4. Verifica permisos de archivos/directorios

---

**Versión actual del código:** commit `ef8f6c2`
**Branch:** `claude/nexosupport-frankenstyle-core-018CF8YAexoAqGWutQqtLtAA`
**Fecha:** 2025-11-18
