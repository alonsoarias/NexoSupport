# 🔧 Herramientas de Diagnóstico - NexoSupport

## 📋 Scripts Disponibles

### 1. `test_permissions.php`
**Propósito:** Verificar que el sistema de permisos funcione correctamente

**Uso:**
```bash
php tools/diagnostics/test_permissions.php
```

**Qué verifica:**
- ✅ Inicialización de PermissionManager
- ✅ Cantidad de permisos en BD
- ✅ Módulos disponibles
- ✅ Método getPermissionsGroupedByModule()

---

### 2. `test_controller.php`
**Propósito:** Simular ejecución del PermissionController para diagnóstico

**Uso:**
```bash
php tools/diagnostics/test_controller.php
```

**Qué verifica:**
- ✅ Controller->index() ejecuta sin errores
- ✅ HTML generado contiene datos de permisos
- ✅ Busca módulos en el HTML
- ✅ Renderiza HTML completo

---

### 3. `test_mustache.php`
**Propósito:** Diagnosticar problemas de renderizado Mustache

**Uso:**
```bash
php tools/diagnostics/test_mustache.php
```

**Qué verifica:**
- ✅ Arrays asociativos vs indexados
- ✅ Compatibilidad con iteración Mustache
- ✅ Muestra conversión necesaria

---

### 4. `debug_permissions.php`
**Propósito:** Diagnóstico web completo (HTML)

**Uso:**
```bash
php tools/diagnostics/debug_permissions.php
```
O acceder vía CLI.

**Qué verifica:**
- ✅ Conexión a BD
- ✅ Existencia de tabla permissions
- ✅ Contenido y distribución
- ✅ Prueba de managers
- ✅ Renderiza HTML con tablas

---

## 🔒 Seguridad

**IMPORTANTE:** Estos archivos están fuera de `public_html` intencionalmente.

**NO son accesibles vía web** → solo por CLI

**Razón:** Contienen información sensible del sistema y no deben exponerse públicamente.

---

## 💡 Cuándo Usar

### `test_permissions.php`
- No se listan permisos en `/admin/permissions`
- Error "array vacío" en getPermissionsGroupedByModule()
- Problemas con PermissionManager

### `test_controller.php`
- Controller devuelve error
- HTML no se genera correctamente
- Problemas de renderizado

### `test_mustache.php`
- Arrays no se renderizan en vistas
- Iteración {{#array}} no funciona
- Problemas con arrays asociativos

### `debug_permissions.php`
- Diagnóstico visual completo
- Verificar estado de BD y managers
- Revisar HTML generado

---

## 📝 Ejemplo de Salida Exitosa

```
=== TEST DE PERMISOS ===

1. Inicializando aplicación...
   ✓ OK

2. Creando PermissionManager...
   ✓ OK

3. Test getPermissions()...
   Total obtenido: 32
   Primer permiso: Exportar Auditoría (audit.export)

4. Test countPermissions()...
   Total: 32

5. Test getModules()...
   Módulos encontrados: 9
   Módulos: audit, dashboard, logs, permissions, reports, roles, sessions, settings, users

6. Test getPermissionsGroupedByModule()...
   Módulos en el resultado: 9
   ✓ OK - Datos agrupados:
   - audit: 2 permisos
   - dashboard: 3 permisos
   [...]

=== FIN DEL TEST ===
```

---

## 🐛 Troubleshooting

### Error: "Cannot modify header information"
**Causa:** Script está enviando headers antes de tiempo
**Solución:** Usar los scripts desde CLI, no vía navegador

### Error: "Class not found"
**Causa:** Autoloader no cargado
**Solución:** Verificar que `vendor/autoload.php` existe

### Error: "Connection refused"
**Causa:** MySQL no disponible
**Solución:** Verificar que el servidor MySQL esté corriendo

---

**Ubicación:** `tools/diagnostics/`
**Acceso:** Solo CLI (por seguridad)
