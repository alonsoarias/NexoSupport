# Guía Rápida de Inicio - Reconstrucción de Navegación NexoSupport

## 🚀 Por dónde empezar

### Paso 1: Leer Documentación (30-45 minutos)

**OBLIGATORIO** leer en este orden:

1. **Este archivo** (QUICK_START.md) - 5 minutos
2. **agent.md** - Prompt completo - 30 minutos
   - Entender TODA la arquitectura
   - Memorizar paleta de colores ISER
   - Entender estructura de archivos
3. **IMPLEMENTATION_CHECKLIST.md** - Checklist de progreso - 10 minutos
   - Usarás esto para marcar tu progreso

### Paso 2: Preparar Entorno (15 minutos)

```bash
# 1. Crear branch de trabajo
git checkout -b feature/navigation-rebuild

# 2. Verificar que el proyecto funciona actualmente
# Abrir en navegador y probar:
# - Login funciona
# - Dashboard carga
# - Admin funciona (con permisos)
# - Crear usuario funciona
# Tomar screenshots de "antes" para comparar

# 3. Crear directorio para backups
mkdir -p backups/pre-rebuild
cp lib/classes/output/renderer.php backups/pre-rebuild/
cp lib/classes/output/page.php backups/pre-rebuild/
# (hacer backup de otros archivos que vas a modificar)

# 4. Commit inicial
git add .
git commit -m "chore: Preparación para reconstrucción de navegación

- Creado branch feature/navigation-rebuild
- Backups de archivos a modificar
- Sistema funcionando correctamente antes de cambios
"
```

### Paso 3: Familiarizarte con Sistema Actual (30 minutos)

**Explorar estos archivos** para entender qué existe:

```bash
# 1. Sistema de navegación actual
lib/classes/navigation/
├── navigation_node.php       # Nodo individual
├── navigation_tree.php       # Árbol jerárquico
├── navigation_builder.php    # Constructor
└── navigation_renderer.php   # Renderizador actual

# 2. Output/rendering actual
lib/classes/output/
├── renderer.php              # Método header() que vas a reemplazar
└── page.php                  # $PAGE global

# 3. Templates actuales
templates/navigation/
└── sidebar.mustache          # Sidebar actual (vas a reemplazar)

# 4. Rutas del sistema
lib/routing/routes.php        # TODAS estas rutas deben funcionar después

# 5. Sistema RBAC (NO tocar, solo usar)
lib/classes/rbac/
├── role.php
├── capability.php
└── access.php
```

**Probar funcionalidad actual**:
```bash
# Abrir en navegador y hacer click en:
# - Cada link del menú actual
# - Crear un usuario
# - Editar un rol
# - Asignar un rol a usuario
# - Cambiar configuración

# Observar:
# - ¿Qué colores se usan? (purple/blue) - estos van a desaparecer
# - ¿Cómo funciona el menú? - lo vas a mejorar
# - ¿Dónde están los breadcrumbs? - los vas a reconstruir
```

---

## 📋 Plan de Trabajo Sugerido

### SEMANA 1: Navegación Primaria + Secundaria

**Día 1-2: Navegación Primaria (Header)**
- [ ] Mañana: Eliminar UI antigua, crear clases backend
- [ ] Tarde: Template Mustache + SCSS con branding ISER
- [ ] Noche: JavaScript para mobile drawer + testing

**Día 3-4: Navegación Secundaria (Tabs)**
- [ ] Mañana: Crear clases backend + factory methods
- [ ] Tarde: Template Mustache + SCSS
- [ ] Noche: JavaScript para overflow + integración contextual

**Día 5: Testing y ajustes Semana 1**
- [ ] Probar TODAS las rutas principales
- [ ] Verificar responsive en devices reales
- [ ] Ajustar colores ISER si es necesario
- [ ] Screenshots para documentación

### SEMANA 2: Sidebar + Mobile + Documentación

**Día 6-7: Sidebar Mejorado**
- [ ] Mañana: Refactorizar clases, agregar badges/dividers
- [ ] Tarde: Mejorar template + SCSS
- [ ] Noche: JavaScript para collapse/expand + localStorage

**Día 8: Breadcrumbs + Mobile Drawer**
- [ ] Mañana: Sistema de breadcrumbs completo
- [ ] Tarde: Mobile drawer con gestures
- [ ] Noche: User menu dropdown

**Día 9: Testing Final**
- [ ] Probar las 24+ rutas críticas
- [ ] Testing cross-browser (Chrome, Firefox, Safari)
- [ ] Testing en devices reales
- [ ] Performance testing (<2s)
- [ ] Validar branding ISER al 100%

**Día 10: Documentación y Entrega**
- [ ] Escribir 4 archivos de documentación
- [ ] Testing report con screenshots
- [ ] Limpiar código (comentarios, minify, etc.)
- [ ] Merge request + code review

---

## 🎨 Paleta ISER (memorizar)

**Colores Primarios** (usar en elementos principales):
```
Verde:    #1B9E88  (navegación primaria, item activo)
Amarillo: #FCBD05  (acentos, active borders)
Rojo:     #EB4335  (alertas, errores)
Blanco:   #FFFFFF  (texto en primaria, backgrounds)
```

**Colores Secundarios** (máximo 30% del diseño):
```
Naranja:  #E27C32
Lima:     #CFDA4B
Azul:     #5894EF  (gradiente con verde en primaria)
Magenta:  #C82260
```

**Colores Neutrales** (textos, bordes, backgrounds):
```
Gris claro:  #CFCFCF
Gris medio:  #9C9C9B
Gris oscuro: #646363
Negro:       #000000
```

**Tipografía**: Verdana o Arial (nunca más de 2 fonts)

---

## 🚨 Errores Comunes a Evitar

### ❌ NO HACER:

1. **Mantener colores antiguos**
   - Purple/blue del sistema anterior
   - Cualquier color no ISER

2. **Olvidar probar rutas**
   - Cambiar algo y no probar inmediatamente
   - Asumir que si una ruta funciona, todas funcionan

3. **Romper backend**
   - Modificar sistema RBAC
   - Cambiar estructura de BD
   - Tocar routing sin entender

4. **Ignorar responsive**
   - Diseñar solo para desktop
   - Olvidar probar en mobile real

5. **Saltarse documentación**
   - Pensar "ya documentaré después"
   - No tomar screenshots durante desarrollo

### ✅ SÍ HACER:

1. **Commit frecuente**
   - Cada 1-2 horas de trabajo
   - Mensajes descriptivos
   - Usar conventional commits

2. **Testing continuo**
   - Después de cada cambio
   - Verificar rutas principales
   - Probar en navegador real

3. **Validar colores constantemente**
   - Usar DevTools color picker
   - Comparar con paleta ISER
   - Preguntarte "¿este color es ISER?"

4. **Preguntar cuando tengas duda**
   - No asumir
   - Referirse al prompt
   - Mejor preguntar que hacer mal

---

## 🛠️ Herramientas Útiles

### Durante Desarrollo:

**Chrome DevTools**:
- F12 → Device Toolbar (responsive testing)
- Color picker (verificar colores ISER)
- Console (ver errores JS/PHP)
- Network (performance)

**VSCode Extensions** (recomendadas):
- PHP Intelephense (autocompletado PHP)
- ESLint (JavaScript linting)
- SCSS IntelliSense (autocompletado SCSS)
- Prettier (formateo)

**Testing**:
```bash
# Verificar sintaxis PHP
php -l archivo.php

# Compilar SCSS (si tienes sass instalado)
sass theme/core/scss/navigation.scss theme/core/style/navigation.css

# Minificar CSS (online o con herramienta)
# cssnano, clean-css, etc.
```

### Para Screenshots:

**Desktop**:
- Chrome DevTools (Cmd+Shift+M en Mac, Ctrl+Shift+M en Windows)
- Captura de pantalla completa de página

**Mobile** (recomendado testing real):
- iPhone/iPad: Safari + Remote Debugging
- Android: Chrome Remote Debugging

---

## 📞 Recursos y Referencias

### Si te atascas:

1. **Referirse a prompt completo** (agent.md)
   - Tiene TODO explicado con ejemplos

2. **Revisar código de Moodle** (inspiración, NO copiar):
   - https://github.com/moodle/moodle
   - Ver cómo ellos estructuran navegación

3. **Documentación PHP/JS**:
   - https://www.php.net/manual/es/
   - https://developer.mozilla.org/es/

4. **Bootstrap 5** (si necesitas componentes):
   - https://getbootstrap.com/docs/5.0/
   - Pero siempre aplicar colores ISER

### Contacto:

Si encuentras problemas insuperables:
- Alonso Arias - soporteplataformas@iser.edu.co
- Documentar el problema detalladamente
- Incluir screenshots, código, y mensajes de error

---

## ✅ Checklist Pre-inicio

**Antes de escribir código, verifica**:

- [ ] Leído prompt completo (agent.md)
- [ ] Entendida paleta ISER (8 colores memorizados)
- [ ] Entendidas restricciones tipográficas
- [ ] Explorado código actual (30 min)
- [ ] Probado funcionalidad actual (15 min)
- [ ] Creado branch de trabajo
- [ ] Hecho backup de archivos
- [ ] Commit inicial realizado
- [ ] Entorno de desarrollo funcionando
- [ ] Chrome DevTools configurado
- [ ] Checklist de implementación impreso/abierto

---

## 🎯 Objetivo Final (recordatorio)

**Crear una interfaz que**:
- Se vea como Moodle 4.x
- Use 100% branding ISER
- Todas las funcionalidades actuales funcionen
- Sea responsive y rápida
- Esté documentada

**NO es**:
- Cambiar algunos colores del sistema actual
- Ajustar CSS existente

**SÍ es**:
- Borrar UI actual
- Crear TODO nuevo
- Asegurar que TODO funciona

---

## 🚀 ¡A trabajar!

**Tu primer commit debería ser**:
```bash
git commit -m "chore: Preparación para reconstrucción de navegación

- Leído prompt completo y checklist
- Entendida paleta de colores ISER
- Explorado código actual
- Probado funcionalidad actual
- Sistema funcionando correctamente
- Listo para iniciar FASE 1
"
```

**Tu segundo commit será**:
```bash
git commit -m "chore: Eliminar UI antigua del header

- Comentado HTML navbar actual en renderer.php
- Comentados estilos purple/blue
- App funcional sin estilos (temporal)
- Preparado para nueva navegación primaria
"
```

**¡Adelante! 💪**

Recuerda:
- Testing continuo
- Commits frecuentes  
- Validación de colores ISER
- Todas las rutas deben funcionar

**Éxito en la reconstrucción!** 🎉
