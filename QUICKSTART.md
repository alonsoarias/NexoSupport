# NexoSupport - Inicio Rápido

## ⚠️ PASOS CRÍTICOS (¡No omitir!)

### 1. Instalar dependencias de Composer

```bash
cd nexosupport
composer install
```

> **IMPORTANTE:** Sin este paso, el sistema NO funcionará. Verás pantalla en blanco o error 500.

### 2. Verificar que todo esté bien

```bash
php check.php
```

Si ves "✓ Todos los requisitos están cumplidos!", puedes continuar.

### 3. Configurar permisos

```bash
chmod -R 777 var/
```

### 4. Configurar servidor web

**Punto CRÍTICO:** El document root DEBE apuntar a `public_html/`

#### Apache

1. Habilitar mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. El archivo `.htaccess` ya está en `public_html/`

3. Asegurar que `AllowOverride All` esté en la configuración del VirtualHost

#### Nginx

Ver `INSTALL.md` para configuración completa.

### 5. Acceder al instalador

```
http://tu-dominio/install
```

## Solución Rápida de Problemas

### Pantalla en blanco
```bash
composer install
```

### Error 404 en /install
```bash
# Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Error de permisos
```bash
chmod -R 777 var/
```

### Verificar todo
```bash
php check.php
```

## Problemas Después de la Instalación

### Advertencias de mbstring en logs

Si ves advertencias sobre `mbstring.http_input`, `mbstring.http_output`, etc.:
- Ver `INSTALL.md` sección "Advertencias de mbstring deprecadas"
- O consultar `.php.ini.recommended` para configuración completa
- **Nota:** No afectan el funcionamiento, solo ensucian los logs

## ¿Necesitas más ayuda?

- Guía completa: `INSTALL.md`
- Documentación: `README.md`
- Configuración PHP recomendada: `.php.ini.recommended`
- Soporte: soporteplataformas@iser.edu.co
