# METODOLOGÍA Y RESTRICCIONES - REFACTORIZACIÓN NEXOSUPPORT

**Fecha**: 2025-11-13
**Versión**: 1.0
**Proyecto**: NexoSupport Comprehensive Refactoring
**Responsable**: ISER Development Team

---

## 1. RESTRICCIONES CRÍTICAS DEL PROYECTO

### 1.1 Restricciones de Análisis y Documentación

#### PROHIBIDO ❌

1. **Proporcionar código de ejemplo en esta fase de análisis**
   - El código solo se generará DESPUÉS del análisis completo
   - Todos los documentos de diseño deben ser conceptuales

2. **Proporcionar estructuras de archivos específicas con código**
   - Se permiten diagramas de estructura
   - Se permiten esquemas conceptuales
   - NO se permiten fragmentos de código PHP/SQL/XML

3. **Proponer funcionalidades nuevas no existentes**
   - Solo refactorizar, mejorar y modularizar lo existente
   - NO agregar nuevas features no solicitadas

4. **Modificar lógica de negocio existente sin análisis previo**
   - Preservar TODA la funcionalidad actual
   - Los 35 permisos granulares deben mantenerse intactos
   - El sistema RBAC debe seguir funcionando igual

5. **Dejar código muerto o comentado**
   - Todo código sin uso DEBE ser eliminado
   - Código comentado extenso debe eliminarse o justificarse

6. **Duplicar lógica sin extraer a funciones reutilizables**
   - Aplicar DRY (Don't Repeat Yourself) estrictamente
   - Máximo 2 repeticiones antes de refactorizar

7. **Ignorar advertencias de análisis estático**
   - PHPStan/Psalm level 5 mínimo
   - Resolver todas las advertencias críticas

#### PERMITIDO ✅

1. **Describir arquitecturas de alto nivel**
   - Diagramas conceptuales
   - Flujos de procesos
   - Diagramas de componentes

2. **Describir patrones a implementar**
   - Patrones de diseño (Factory, Strategy, Observer, etc.)
   - Arquitectura de plugins
   - Sistemas de hooks

3. **Describir flujos y procesos**
   - Flujo de instalación
   - Flujo de actualización
   - Flujo de autenticación

4. **Describir estructuras conceptuales**
   - Estructura de directorios (sin código)
   - Relaciones entre componentes
   - Dependencias

5. **Describir requisitos y especificaciones**
   - Requisitos funcionales
   - Requisitos no funcionales
   - Casos de uso

6. **Identificar y eliminar código muerto/redundante**
   - Análisis exhaustivo de código sin uso
   - Consolidación de duplicados
   - Limpieza de imports

7. **Refactorizar código duplicado a componentes reutilizables**
   - Extraer funciones comunes
   - Crear traits para compartir funcionalidad
   - Crear componentes reutilizables

8. **Optimizar y simplificar código existente**
   - Reducir complejidad ciclomática
   - Mejorar legibilidad
   - Aplicar SOLID

---

## 2. METODOLOGÍA DE TRABAJO

### 2.1 Orden de Ejecución (Fases Secuenciales)

```
ETAPA I: COMPRENSIÓN Y PLANIFICACIÓN
├── FASE 1: Análisis Exhaustivo del Proyecto ✅ COMPLETADA
├── FASE 2: Documentar Restricciones y Metodología (actual)
└── FASE 3: Limpieza de Código ✅ COMPLETADA (identificación)

ETAPA II: FUNDAMENTOS DEL SISTEMA
├── FASE 4: Normalización de BD a 3FN
├── FASE 5: Sistema de Instalación vía XML
└── FASE 6: Sistema de Internacionalización

ETAPA III: ARQUITECTURA MODULAR
├── FASE 7: Sistema de Plugins Dinámico
└── FASE 8: Segmentación de Herramientas

ETAPA IV: INTERFAZ Y EXPERIENCIA
├── FASE 9: Theme Configurable del Core
└── FASE 10: Rediseño del Instalador Web

ETAPA V: MANTENIMIENTO Y EVOLUCIÓN
└── FASE 11: Sistema de Actualización Robusto

ETAPA VI: VALIDACIÓN
└── FASE 12-15: Criterios de Éxito y Entregables
```

### 2.2 Proceso por Fase

Cada fase debe seguir este flujo:

```
1. ANALIZAR
   ↓
   - Entender el código existente
   - Identificar componentes afectados
   - Documentar estado actual

2. DOCUMENTAR
   ↓
   - Crear documento de diseño
   - Describir arquitectura propuesta
   - Definir requisitos

3. DISEÑAR
   ↓
   - Diseñar solución de refactorización
   - Identificar patrones a aplicar
   - Planificar cambios

4. PLANIFICAR
   ↓
   - Dividir en tareas pequeñas
   - Estimar esfuerzo
   - Definir orden de implementación

5. IMPLEMENTAR (solo después de aprobación)
   ↓
   - Escribir código
   - Aplicar cambios
   - Seguir estándares

6. PROBAR
   ↓
   - Tests unitarios
   - Tests de integración
   - Tests manuales

7. DOCUMENTAR CAMBIOS
   ↓
   - Actualizar documentación
   - Registrar en CHANGELOG
   - Documentar decisiones
```

### 2.3 Principio de Aprobación

**CRÍTICO**: NO se debe generar código sin:
1. ✅ Análisis exhaustivo completado
2. ✅ Documento de diseño creado
3. ✅ Aprobación explícita del usuario
4. ✅ Plan de implementación definido

---

## 3. ESTÁNDARES DE CALIDAD DE CÓDIGO

### 3.1 Estándares PSR (PHP Standards Recommendations)

#### PSR-1: Basic Coding Standard

✅ **Aplicar estrictamente**:
- Archivos deben usar solo `<?php` y `<?=` tags
- Archivos deben usar UTF-8 sin BOM
- Archivos deben declarar symbols (classes, functions, constants) O causar side-effects, pero NO ambos
- Namespaces y clases deben seguir PSR-4
- Nombres de clases en `StudlyCaps`
- Constantes de clase en `UPPER_CASE` con underscores
- Nombres de métodos en `camelCase`

#### PSR-4: Autoloading Standard

✅ **Ya implementado en el proyecto**:
```php
"autoload": {
    "psr-4": {
        "ISER\\": "modules/",
        "ISER\\Core\\": "core/"
    }
}
```

✅ **Verificar cumplimiento**:
- Namespace debe coincidir con estructura de directorios
- Nombre de clase debe coincidir con nombre de archivo
- Un archivo = Una clase

#### PSR-12: Extended Coding Style

✅ **Aplicar estrictamente**:
- Indentación con 4 espacios (NO tabs)
- Líneas <= 120 caracteres (preferiblemente <= 80)
- Keywords y tipos en lowercase
- `declare(strict_types=1);` en archivos PHP
- Llaves de apertura `{` en nueva línea para classes/methods
- Llaves de cierre `}` en nueva línea

**Ejemplo**:
```php
<?php

declare(strict_types=1);

namespace ISER\ModuleName;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

class ClassName
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function methodName(string $param): bool
    {
        // Implementation
        return true;
    }
}
```

### 3.2 Principios SOLID

#### S - Single Responsibility Principle
- ✅ Una clase = Una responsabilidad
- ✅ Si una clase hace múltiples cosas → dividir

**Ejemplo de violación**:
```
UserManager que:
- Gestiona CRUD de usuarios
- Envía emails
- Genera reportes
→ Dividir en: UserManager, UserMailer, UserReportGenerator
```

#### O - Open/Closed Principle
- ✅ Abierto para extensión (plugins, herencia)
- ✅ Cerrado para modificación (no cambiar core)

**Implementación**:
- Sistema de plugins permite extensión sin modificar core
- Interfaces permiten implementaciones alternativas

#### L - Liskov Substitution Principle
- ✅ Subclases deben poder reemplazar a su clase base
- ✅ Implementaciones de interfaz deben ser intercambiables

#### I - Interface Segregation Principle
- ✅ Interfaces pequeñas y específicas
- ✅ No forzar a implementar métodos no usados

#### D - Dependency Inversion Principle
- ✅ Depender de abstracciones (interfaces), no de concreciones
- ⚠️ **MEJORA NECESARIA**: Actualmente hay mucha dependencia de clases concretas

**Mejora futura**: Implementar Dependency Injection Container

---

### 3.3 Clean Code Principles

#### Nombres Descriptivos

✅ **Bueno**:
```php
$userManager = new UserManager($database);
$activeUsers = $userManager->getActiveUsers();
```

❌ **Malo**:
```php
$um = new UM($db);
$users = $um->get();
```

#### Funciones Pequeñas

✅ **Meta**: Funciones <= 20 líneas
✅ **Ideal**: Funciones <= 10 líneas

**Razón**: Fácil de entender, probar y mantener

#### Evitar Código Duplicado (DRY)

✅ **Regla de tres**: Si se repite 3+ veces → refactorizar

#### Comentarios Solo Cuando Sea Necesario

✅ **Comentar**:
- Decisiones de diseño no obvias
- Hacks temporales (con TODO)
- Lógica de negocio compleja
- Algoritmos no triviales

❌ **NO comentar**:
- Código obvio
- Qué hace el código (debe ser autoexplicativo)

**Ejemplo bueno**:
```php
// Usamos SHA256 en lugar de bcrypt porque el sistema legacy
// requiere compatibilidad con tokens de 64 caracteres exactos
$token = hash('sha256', $data);
```

**Ejemplo malo**:
```php
// Incrementar contador
$counter++;
```

#### Manejo Robusto de Errores

✅ **Usar excepciones** para errores excepcionales
✅ **Validar inputs** en puntos de entrada
✅ **Loggear errores** con contexto adecuado
✅ **Fallar rápido** (fail-fast)

---

### 3.4 Testing

#### Cobertura de Tests

✅ **Meta**: Coverage > 70%
✅ **Ideal**: Coverage > 80%

#### Tipos de Tests

**Tests Unitarios**:
- Testear lógica de negocio aislada
- Mockear dependencias externas
- Rápidos (<100ms por test)

**Tests de Integración**:
- Testear componentes trabajando juntos
- Base de datos de prueba
- Más lentos (aceptable)

**Tests End-to-End**:
- Testear flujos completos de usuario
- Simulación de navegador (si es necesario)

#### Estructura de Test

```php
public function testMethodName()
{
    // Arrange (preparar)
    $user = new User(['name' => 'Test User']);

    // Act (ejecutar)
    $result = $user->getName();

    // Assert (verificar)
    $this->assertEquals('Test User', $result);
}
```

---

## 4. CONTROL DE VERSIONES (Git)

### 4.1 Branch Strategy

**Branching Model**: Git Flow simplificado

```
main (producción)
  ↓
develop (desarrollo)
  ↓
feature/* (features)
  ├── feature/plugin-installer
  ├── feature/theme-configurator
  └── feature/update-system

hotfix/* (fixes críticos)
  └── hotfix/security-patch-xyz
```

**Branch actual**: `claude/nexosupport-comprehensive-refactoring-011CV5uXmSnUiHg4HyR1fRq3`

### 4.2 Commit Messages

**Formato**: Conventional Commits

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: Nueva funcionalidad
- `fix`: Corrección de bug
- `docs`: Cambios en documentación
- `style`: Cambios de formato (no afectan código)
- `refactor`: Refactorización (no agrega features ni corrige bugs)
- `perf`: Mejora de performance
- `test`: Agregar o corregir tests
- `chore`: Cambios en build, herramientas, etc.

**Ejemplos**:
```
docs: add comprehensive project analysis

feat(plugins): implement web plugin installer with zip upload

refactor(roles): consolidate Role and Roles directories

fix(auth): correct JWT expiration validation

chore: remove dead code (Logger, RoleManager duplicate)
```

### 4.3 Pull Request Process

1. Crear feature branch
2. Desarrollar cambios
3. Ejecutar tests localmente
4. Commit con mensajes descriptivos
5. Push a remote
6. Crear PR con descripción detallada
7. Code review
8. Aprobar y merge

---

## 5. GESTIÓN DE DEPENDENCIAS

### 5.1 Composer

**Archivo**: `composer.json`

**Principios**:
- ✅ Usar versiones con parche: `^6.10` (permite 6.10.x, no 7.x)
- ✅ Mantener dependencias actualizadas
- ✅ Revisar seguridad: `composer audit`
- ⚠️ Evitar dependencias innecesarias

**Comandos útiles**:
```bash
composer install          # Instalar dependencias
composer update           # Actualizar dependencias
composer require pkg      # Agregar dependencia
composer remove pkg       # Eliminar dependencia
composer audit            # Revisar vulnerabilidades
composer outdated         # Ver paquetes desactualizados
```

### 5.2 Autoloading

**PSR-4 configurado**:
```json
"autoload": {
    "psr-4": {
        "ISER\\": "modules/",
        "ISER\\Core\\": "core/"
    },
    "files": [
        "core/Autoloader.php",
        "core/I18n/Translator.php"
    ]
}
```

**Regenerar autoload**:
```bash
composer dump-autoload
```

---

## 6. SEGURIDAD

### 6.1 Principios de Seguridad

✅ **Validación de inputs**
- Validar TODOS los inputs del usuario
- Sanitizar antes de usar
- Usar whitelists, no blacklists

✅ **Sanitización de outputs**
- Escapar HTML: `htmlspecialchars()`
- Usar prepared statements para SQL
- Validar URLs antes de redireccionar

✅ **Protección CSRF**
- Token CSRF en todos los formularios
- Verificar token en backend

✅ **Protección SQL Injection**
- SIEMPRE usar prepared statements
- NUNCA concatenar SQL con inputs de usuario

✅ **Protección XSS**
- Escapar outputs en templates
- Content Security Policy headers

✅ **Autenticación segura**
- JWT con secret fuerte
- Tokens con expiración
- Refresh tokens

✅ **Autorización robusta**
- Verificar permisos en CADA acción
- RBAC estricto
- Principio de menor privilegio

✅ **Logging de acciones sensibles**
- Login/logout
- Cambios de permisos
- Modificaciones de usuarios
- Acceso a datos sensibles

✅ **Rate limiting**
- Login attempts limitados
- API endpoints protegidos

### 6.2 Vulnerabilidades OWASP Top 10

Protección contra:
1. ✅ Injection (SQL, Command, LDAP)
2. ✅ Broken Authentication
3. ✅ Sensitive Data Exposure
4. ✅ XML External Entities (XXE)
5. ✅ Broken Access Control
6. ✅ Security Misconfiguration
7. ✅ Cross-Site Scripting (XSS)
8. ✅ Insecure Deserialization
9. ✅ Using Components with Known Vulnerabilities
10. ✅ Insufficient Logging & Monitoring

---

## 7. PERFORMANCE

### 7.1 Optimización de Queries

✅ **Usar índices apropiados**
✅ **Evitar N+1 queries**
✅ **Usar LIMIT en queries grandes**
✅ **Eager loading cuando sea posible**
✅ **Caché de queries frecuentes**

### 7.2 Caché

**Niveles de caché**:
1. **Configuración** - Cache en memoria
2. **Permisos** - Cache por 1 hora
3. **Traducciones** - Cache permanente (invalidar en cambio)
4. **Assets** - Browser cache + CDN

### 7.3 Lazy Loading

- Cargar plugins solo cuando se necesitan
- Cargar módulos bajo demanda
- Defer JavaScript no crítico

---

## 8. DOCUMENTACIÓN

### 8.1 Documentación de Código (PHPDoc)

**Obligatorio**:
- Docblocks en todas las clases
- Docblocks en todos los métodos públicos
- Tipos de parámetros y returns

**Ejemplo**:
```php
/**
 * Get user by ID
 *
 * Retrieves a user from the database by their unique identifier.
 * Returns null if the user is not found or has been soft-deleted.
 *
 * @param int $userId User unique identifier
 * @return array|null User data or null if not found
 * @throws DatabaseException If database connection fails
 */
public function getUserById(int $userId): ?array
{
    // Implementation
}
```

### 8.2 Documentación de Arquitectura

**Documentos requeridos**:
- `ANALYSIS.md` ✅ Completado
- `CODE_CLEANUP_REPORT.md` ✅ Completado
- `METHODOLOGY_AND_RESTRICTIONS.md` (este documento)
- `DATABASE_NORMALIZATION_ANALYSIS.md`
- `PLUGIN_SYSTEM_SPEC.md`
- `I18N_SPEC.md`
- `THEME_SPEC.md`
- `XML_PARSER_SPEC.md`
- `INSTALLER_SPEC.md`
- `UPDATE_SYSTEM_SPEC.md`
- `DEVELOPER_GUIDE.md`
- `USER_MANUAL.md`
- `ADMIN_MANUAL.md`

### 8.3 CHANGELOG

**Formato**: Keep a Changelog

```markdown
# Changelog

## [Unreleased]
### Added
- Web plugin installer with zip upload
- Theme configurator in admin panel

### Changed
- Consolidated Role and Roles directories
- Improved router architecture

### Removed
- Dead code: Logger, RoleManager duplicate

## [1.0.0] - 2024-XX-XX
### Added
- Initial release
```

---

## 9. PROCESO DE REFACTORIZACIÓN ESPECÍFICO

### 9.1 Refactorización Incremental

**Principio**: Cambios pequeños, frecuentes y probados

❌ **NO hacer**:
- Refactorización masiva en un solo commit
- Cambios que afecten múltiples sistemas a la vez
- Refactorización sin tests

✅ **SÍ hacer**:
- Cambios pequeños y atómicos
- Un commit por cambio lógico
- Tests antes y después de cada cambio
- Documentar cada cambio

### 9.2 Orden de Refactorización

1. **Primero**: Limpieza de código muerto
2. **Segundo**: Consolidación de duplicados
3. **Tercero**: Mejoras estructurales
4. **Cuarto**: Nuevas funcionalidades
5. **Quinto**: Optimizaciones

### 9.3 Testing Durante Refactorización

**Flujo**:
```
1. Ejecutar tests existentes (baseline)
2. Refactorizar código
3. Ejecutar tests nuevamente
4. Verificar que siguen pasando
5. Si fallan → revertir cambio
6. Si pasan → commit
```

---

## 10. CRITERIOS DE ACEPTACIÓN

### 10.1 Para Cada Fase

✅ **Documentación completa**
✅ **Diseño aprobado**
✅ **Tests pasando (100%)**
✅ **Coverage >= 70%**
✅ **Code review aprobado**
✅ **Sin warnings de PHPStan level 5**
✅ **Funcionalidad existente preservada**

### 10.2 Para el Proyecto Completo

✅ **Todos los sistemas funcionando**
✅ **Sin código muerto**
✅ **Sin código duplicado**
✅ **Base de datos en 3FN**
✅ **Sistema de plugins funcional**
✅ **i18n completo**
✅ **Theme configurable**
✅ **Instalador moderno**
✅ **Sistema de actualización**
✅ **Documentación completa**

---

## 11. GESTIÓN DE RIESGOS

### 11.1 Riesgos Identificados

**Riesgo 1**: Breaking changes que rompan funcionalidad existente
- **Mitigación**: Tests exhaustivos, refactorización incremental

**Riesgo 2**: Eliminación de código usado dinámicamente
- **Mitigación**: Análisis de llamadas dinámicas, testing completo

**Riesgo 3**: Conflictos con plugins externos
- **Mitigación**: Versionado semántico, DEPRECATIONS.md

**Riesgo 4**: Pérdida de datos en migraciones de BD
- **Mitigación**: Backups antes de cada migración, scripts reversibles

### 11.2 Plan de Rollback

**Para cada cambio importante**:
1. Crear backup completo
2. Documentar estado anterior
3. Implementar cambio
4. Si falla → restaurar backup
5. Documentar lección aprendida

---

## 12. COMUNICACIÓN Y COORDINACIÓN

### 12.1 Reportes de Progreso

**Frecuencia**: Al completar cada fase

**Contenido**:
- Fase completada
- Cambios realizados
- Problemas encontrados
- Soluciones implementadas
- Próximos pasos

### 12.2 Decisiones de Diseño

**Proceso**:
1. Identificar decisión necesaria
2. Documentar opciones
3. Analizar pros/cons
4. Proponer solución
5. Obtener aprobación
6. Documentar decisión final

---

## 13. HERRAMIENTAS REQUERIDAS

### 13.1 Desarrollo

- **PHP**: >= 8.1
- **Composer**: >= 2.x
- **Git**: >= 2.x
- **IDE**: PhpStorm / VSCode con extensiones PHP

### 13.2 Testing

- **PHPUnit**: >= 10.5
- **PHPStan**: >= 1.x
- **Psalm**: (opcional)

### 13.3 Base de Datos

- **MySQL**: >= 5.7 / MariaDB >= 10.3
- **PostgreSQL**: >= 12 (opcional)
- **SQLite**: >= 3.x (opcional)

---

## 14. PRÓXIMOS PASOS

**Inmediato**:
1. ✅ Análisis exhaustivo - COMPLETADO
2. ✅ Identificación de código muerto - COMPLETADO
3. ✅ Metodología y restricciones - COMPLETADO
4. ⏭️ Análisis de normalización de BD
5. ⏭️ Diseño de mejoras a sistemas existentes

**Mediano Plazo**:
- Diseño del sistema de actualización
- Diseño del instalador de plugins web
- Diseño del theme configurator
- Diseño del instalador web moderno

**Largo Plazo**:
- Implementación de todos los diseños
- Testing exhaustivo
- Documentación completa
- Despliegue

---

## 15. CONCLUSIÓN

Esta metodología asegura que la refactorización de NexoSupport sea:

✅ **Sistemática** - Proceso ordenado y predecible
✅ **Segura** - Sin romper funcionalidad existente
✅ **Documentada** - Todo cambio registrado
✅ **Probada** - Tests en cada paso
✅ **Incremental** - Cambios pequeños y frecuentes
✅ **Reversible** - Posibilidad de rollback
✅ **Mantenible** - Código limpio y bien estructurado

**El éxito del proyecto depende de seguir esta metodología rigurosamente.**

---

**Fin del Documento de Metodología y Restricciones**

**Próximo Documento**: `DATABASE_NORMALIZATION_ANALYSIS.md`
