# Authentication and Site Administrators Fix

## Problems Fixed

### 1. **Undefined property: stdClass::$firstname**

**Problem:** El objeto `$USER` cargado desde la sesión no tenía todos los campos de la tabla users.

**Solution:** `lib/setup.php` ahora recarga el `$USER` completo desde la base de datos en cada request, asegurando que todos los campos estén disponibles.

**Code Location:** `lib/setup.php` líneas 134-162

### 2. **[[nopermissions]] Error - Site Administrators Cannot Access Admin Pages**

**Problem:** Los site administrators no podían acceder a páginas de administración aunque is_siteadmin() debería darles acceso completo.

**Root Cause:** La tabla `config` tiene estructura:
```
- id
- component (default 'core')
- name
- value
```

Pero el código estaba insertando registros SIN especificar `component`, lo que causaba problemas en las queries.

**Solution:**
- `install/stages/finish.php`: Ahora especifica `component='core'` en ambos registros (version y siteadmins)
- `lib/upgrade.php`: Migración v1.1.6 ahora especifica `component='core'` en siteadmins
- `lib/functions.php`: `is_siteadmin()` usa SQL directo y tiene debugging extensivo

### 3. **PHP mbstring Deprecated Warnings**

**Problem:**
```
PHP Deprecated: PHP Startup: Use of mbstring.http_input is deprecated
PHP Deprecated: PHP Startup: Use of mbstring.http_output is deprecated
PHP Deprecated: PHP Startup: Use of mbstring.internal_encoding is deprecated
```

**Solution:** Estos son warnings de configuración de PHP, no del código. Para corregirlos:

**En php.ini:**
```ini
; Comentar o eliminar estas líneas:
; mbstring.http_input = auto
; mbstring.http_output = pass
; mbstring.internal_encoding = UTF-8

; Agregar en su lugar:
; mbstring.language = neutral
default_charset = "UTF-8"
```

**En MAMP:**
1. Ir a: `MAMP > Preferences > PHP`
2. Ver versión de PHP activa (ej: 8.2.0)
3. Editar: `/Applications/MAMP/bin/php/php8.2.0/conf/php.ini`
4. Buscar y comentar las líneas de mbstring mencionadas
5. Reiniciar MAMP

---

## How It Works Now

### User Loading Process

```
1. Usuario hace login → authenticate_user_login() retorna objeto user completo
2. Objeto user se guarda en $_SESSION['USER']
3. En cada request:
   a. lib/setup.php detecta usuario en sesión
   b. Recarga usuario COMPLETO desde BD: $DB->get_record('users', ['id' => $userid])
   c. Verifica que no esté deleted o suspended
   d. Actualiza sesión con datos frescos
   e. $USER global tiene TODOS los campos actualizados
```

**Benefits:**
- ✅ $USER siempre tiene todos los campos (firstname, lastname, email, etc.)
- ✅ Cambios en BD se reflejan inmediatamente (sin re-login)
- ✅ Usuarios deleted/suspended son deslogueados automáticamente
- ✅ No hay "undefined property" errors

### Site Administrators Verification

```
1. Usuario intenta acceder a página admin
2. Página llama: require_capability('nexosupport/admin:manage')
3. require_capability() → has_capability()
4. has_capability() PRIMERO verifica: is_siteadmin($userid)
5. is_siteadmin():
   a. Query SQL: SELECT * FROM config WHERE name = 'siteadmins'
   b. Parsea value: "1,5,12" → [1, 5, 12]
   c. Verifica: in_array($userid, [1,5,12])
   d. Retorna true/false
6. Si is_siteadmin() = true → has_capability() retorna TRUE (bypass RBAC)
7. Si is_siteadmin() = false → verifica RBAC normalmente
```

**Benefits:**
- ✅ Site administrators tienen TODAS las capabilities
- ✅ No necesitan role assignments
- ✅ Verificación rápida (un solo query cacheable)
- ✅ Patrón exacto de Moodle

---

## Debugging

### Enable Debug Mode

**In .env:**
```env
APP_DEBUG=true
```

### Check Logs

**En MAMP:**
```
/Applications/MAMP/logs/php_error.log
```

**En Linux/Docker:**
```
/var/log/apache2/error.log
/var/log/nginx/error.log
```

### Verify Site Administrators

**SQL Query:**
```sql
SELECT * FROM nxs_config WHERE name = 'siteadmins';
```

**Expected Result:**
```
| id | component | name       | value |
|----|-----------|------------|-------|
| 2  | core      | siteadmins | 1     |
```

### Test is_siteadmin()

En cualquier página PHP después de `require_once('config.php')`:

```php
global $USER;
var_dump([
    'userid' => $USER->id,
    'is_siteadmin' => is_siteadmin(),
    'siteadmins_list' => get_siteadmins()
]);
```

Expected output si eres siteadmin:
```
array(3) {
  ["userid"]=> int(1)
  ["is_siteadmin"]=> bool(true)
  ["siteadmins_list"]=> array(1) {
    [0]=> int(1)
  }
}
```

---

## Migration Guide

### For Fresh Installations

1. Run installer normally at `/install`
2. Create admin user
3. System automatically:
   - Saves config.version with component='core'
   - Saves config.siteadmins with component='core', value='1'
   - Assigns administrator role to user
4. Login works immediately
5. All admin pages accessible

### For Existing Installations (Upgrading from v1.1.5 or earlier)

**Option 1: Run Upgrade (Recommended)**

1. Navigate to `/admin/upgrade.php`
2. System detects version mismatch
3. Runs migration v1.1.6:
   - Finds all users with administrator role in system context
   - Creates/updates config.siteadmins with their IDs
   - Fallback: uses first user if no admins found
4. Login and verify access

**Option 2: Manual Fix (if upgrade fails)**

Connect to database and run:

```sql
-- Check if siteadmins exists
SELECT * FROM nxs_config WHERE name = 'siteadmins';

-- If it doesn't exist, insert it:
INSERT INTO nxs_config (component, name, value)
VALUES ('core', 'siteadmins', '1');

-- Replace '1' with your admin user ID
-- For multiple admins: '1,5,12'
```

Then clear PHP session:
- Delete all files in `/var/sessions/` or MAMP sessions folder
- Or run: `rm -rf var/sessions/*`

---

## Testing Checklist

- [ ] Fresh install creates admin user successfully
- [ ] Login with admin credentials works
- [ ] `/admin` dashboard loads without errors
- [ ] No "Undefined property: firstname" errors
- [ ] `/admin/users` accessible (no [[nopermissions]])
- [ ] `/admin/roles` accessible
- [ ] `/admin/settings` accessible
- [ ] User profile shows all fields correctly
- [ ] Logout works correctly
- [ ] Re-login works correctly

---

## Technical Details

### Files Modified

1. **lib/setup.php** (lines 134-162)
   - Reloads $USER from database on every request
   - Validates user still exists and is active
   - Updates session with fresh data

2. **lib/functions.php** (lines 645-672)
   - is_siteadmin() uses direct SQL query
   - Added extensive debugging
   - Static cache for performance

3. **install/stages/finish.php** (lines 63, 80)
   - Explicitly sets component='core' on version record
   - Explicitly sets component='core' on siteadmins record

4. **lib/upgrade.php** (lines 443, 460)
   - Migration v1.1.6 sets component='core' on new records
   - Uses SQL query to check existing records

### Database Schema

```sql
CREATE TABLE nxs_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  component VARCHAR(100) NOT NULL DEFAULT 'core',
  name VARCHAR(100) NOT NULL,
  value TEXT NOT NULL,
  UNIQUE KEY idx_component_name (component, name)
);
```

**Important Records:**

```sql
-- System version
INSERT INTO nxs_config VALUES (1, 'core', 'version', '2025011806');

-- Site administrators (comma-separated user IDs)
INSERT INTO nxs_config VALUES (2, 'core', 'siteadmins', '1');
```

---

## Common Issues

### Issue: "Cannot access admin pages"

**Check:**
1. Is user logged in? `var_dump($USER->id);`
2. Is user in siteadmins? `var_dump(is_siteadmin());`
3. Does config.siteadmins exist? Run SQL query above
4. Debug mode enabled? Check error logs

**Fix:**
- If siteadmins missing: Run migration or manual SQL insert
- If user not in list: Add user ID to config.siteadmins value
- Clear sessions and re-login

### Issue: "$USER missing fields"

**Check:**
1. Is database connected? `var_dump($DB);`
2. Does user exist in DB? Run: `SELECT * FROM nxs_users WHERE id = 1;`
3. Are all fields present in DB schema?

**Fix:**
- Run upgrade if coming from old version
- Verify install.xml has all fields
- Re-run installer if necessary

### Issue: "mbstring warnings"

**Not a NexoSupport issue** - this is PHP configuration.

**Fix:** See "PHP mbstring Deprecated Warnings" section above.

---

## Support

If issues persist:

1. Enable debug mode: `APP_DEBUG=true` in .env
2. Check error logs
3. Run SQL queries to verify database state
4. Contact: soporteplataformas@iser.edu.co
