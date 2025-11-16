# Factores MFA - Arquitectura Frankenstyle

Este directorio contiene los factores de autenticación multifactor (MFA) como **subplugins Frankenstyle independientes**.

## Estructura

Cada factor es un plugin completo con la siguiente estructura:

```
factor_[nombre]/
├── version.php          # Metadatos del plugin
├── lib.php              # Funciones públicas y capabilities
├── classes/             # Clases PSR-4
│   └── factor.php       # Clase principal del factor
├── db/                  # Esquema de base de datos
│   └── install.php      # Definición de tablas
├── lang/                # Internacionalización
│   └── es/
│       └── factor_[nombre].php
└── templates/           # Plantillas Mustache
    ├── setup.mustache   # Formulario de configuración
    └── verify.mustache  # Formulario de verificación
```

## Factores Disponibles

### 1. factor_email
- **Componente**: `factor_email`
- **Peso**: 50 (Media prioridad)
- **Descripción**: Verificación mediante código enviado al correo electrónico
- **Tablas**: `mfa_email_codes`

### 2. factor_iprange
- **Componente**: `factor_iprange`
- **Peso**: 100 (Alta prioridad - pasivo)
- **Descripción**: Verificación basada en rango de IPs permitidas
- **Tablas**: `mfa_iprange_config`

### 3. factor_totp
- **Componente**: `factor_totp`
- **Peso**: 100 (Alta prioridad)
- **Descripción**: TOTP (Time-based One-Time Password) compatible con Google Authenticator
- **Tablas**: `mfa_totp_secrets`

### 4. factor_sms
- **Componente**: `factor_sms`
- **Peso**: 75 (Media-alta prioridad)
- **Descripción**: Verificación mediante código SMS
- **Tablas**: `mfa_sms_codes`

### 5. factor_backupcodes
- **Componente**: `factor_backupcodes`
- **Peso**: 25 (Baja prioridad - fallback)
- **Descripción**: Códigos de respaldo de un solo uso
- **Tablas**: `mfa_backup_codes`

## Autoloading PSR-4

Los namespaces de los factores están definidos en `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "factor_email\\": "admin/tool/mfa/factor/email/classes/",
      "factor_iprange\\": "admin/tool/mfa/factor/iprange/classes/",
      "factor_totp\\": "admin/tool/mfa/factor/totp/classes/",
      "factor_sms\\": "admin/tool/mfa/factor/sms/classes/",
      "factor_backupcodes\\": "admin/tool/mfa/factor/backupcodes/classes/"
    }
  }
}
```

## Descubrimiento de Factores

Los factores se descubren automáticamente mediante:

1. **components.json**: Define `"factor": "admin/tool/mfa/factor"`
2. **Plugin Manager**: Escanea el directorio y detecta todos los subdirectorios con `version.php`
3. **Autoloading**: Composer autocarga las clases según namespace

## Creación de Nuevos Factores

Para crear un nuevo factor MFA:

1. Crear directorio `admin/tool/mfa/factor/[nombre]/`
2. Crear `version.php` con component = `factor_[nombre]`
3. Crear `lib.php` con funciones públicas
4. Crear `classes/factor.php` con lógica del factor
5. Crear `db/install.php` si necesita tablas
6. Crear `lang/es/factor_[nombre].php` con strings
7. Agregar namespace en `composer.json`
8. Ejecutar `composer dump-autoload`

## Ejemplo version.php

```php
<?php
defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'factor_email';
$plugin->version = 2025011600;
$plugin->requires = 2025010100;
$plugin->release = '1.0.0';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
    'tool_mfa' => 2025011600,
];
```

## Integración con tool_mfa

El plugin padre `tool_mfa` gestiona todos los factores:

- **MFAManager**: Descubre y carga factores automáticamente
- **Factor Registry**: Mantiene registro de factores disponibles
- **Verification Flow**: Orquesta la verificación multi-factor
- **User Settings**: UI para habilitar/configurar factores

## Prioridades y Pesos

Los factores se evalúan según su peso:

1. **100** - Alta prioridad (IP Range, TOTP): Se verifican primero
2. **75** - Media-Alta (SMS): Verificación activa confiable
3. **50** - Media (Email): Verificación activa estándar
4. **25** - Baja (Backup Codes): Solo como fallback

## Seguridad

Cada factor debe implementar:

- ✅ Rate limiting (límite de intentos)
- ✅ Tiempo de expiración en códigos
- ✅ Hash de códigos (bcrypt/argon2)
- ✅ Registro de intentos fallidos
- ✅ Lockout temporal tras intentos excesivos
- ✅ Audit logging

## Testing

Para testear un factor:

```bash
# Verificar que el factor es descubierto
php -r "require 'vendor/autoload.php'; var_dump(class_exists('factor_email\factor'));"

# Ejecutar tests del factor
vendor/bin/phpunit admin/tool/mfa/factor/email/tests/
```

## Estado Actual

- ✅ Estructura Frankenstyle completa
- ✅ 5 factores implementados
- ✅ Autoloading PSR-4 configurado
- ✅ Internacionalización (español)
- ⚠️ Lógica de negocio en migración desde `classes/factors/`
- 📝 Pendiente: Templates Mustache
- 📝 Pendiente: Tests unitarios

## Próximos Pasos

1. Migrar lógica completa de `admin/tool/mfa/classes/factors/*.php` a cada subplugin
2. Crear templates Mustache para setup y verificación
3. Implementar tests unitarios
4. Crear schemas `db/install.php` para cada factor
5. Eliminar directorio legacy `classes/factors/`

---

**Arquitectura**: Frankenstyle
**Tipo de Plugin**: `factor`
**Plugin Padre**: `tool_mfa`
**Total Factores**: 5
