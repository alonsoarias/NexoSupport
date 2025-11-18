# Guía de Instalación de NexoSupport

## Requisitos Previos

### Software Requerido
- PHP >= 8.1
- MySQL 5.7+ o MariaDB 10.2+
- Servidor Web: Apache 2.4+ o Nginx 1.18+
- Composer (para instalar dependencias)

### Extensiones PHP Requeridas
- PDO
- pdo_mysql
- json
- mbstring

## Paso 1: Obtener el Código

```bash
git clone <repo-url> nexosupport
cd nexosupport
```

## Paso 2: Instalar Dependencias ⚠️ CRÍTICO

**Este paso es OBLIGATORIO. El sistema no funcionará sin él.**

```bash
composer install
```

> **Importante:** Si olvidas este paso, el instalador NO se cargará y verás un error 500 o pantalla en blanco. El autoloader de Composer es necesario para cargar todas las clases del sistema.

Para verificar que todo está correcto, ejecuta:
```bash
php check.php
```

## Paso 3: Configurar Permisos

```bash
chmod -R 755 var/
chmod -R 755 public_html/
```

## Paso 4: Configurar Servidor Web

### Opción A: Apache

**Document Root:** Debe apuntar a `public_html/`

El archivo `.htaccess` ya está incluido en `public_html/`. Asegúrese de que:

1. **mod_rewrite esté habilitado:**
```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. **AllowOverride esté configurado:**

Editar `/etc/apache2/sites-available/nexosupport.conf`:

```apache
<VirtualHost *:80>
    ServerName nexosupport.localhost.com
    DocumentRoot /ruta/a/nexosupport/public_html

    <Directory /ruta/a/nexosupport/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/nexosupport-error.log
    CustomLog ${APACHE_LOG_DIR}/nexosupport-access.log combined
</VirtualHost>
```

3. **Habilitar el sitio:**
```bash
sudo a2ensite nexosupport
sudo systemctl reload apache2
```

### Opción B: Nginx

Crear `/etc/nginx/sites-available/nexosupport`:

```nginx
server {
    listen 80;
    server_name nexosupport.localhost.com;

    root /ruta/a/nexosupport/public_html;
    index index.php;

    # Logging
    access_log /var/log/nginx/nexosupport-access.log;
    error_log /var/log/nginx/nexosupport-error.log;

    # Prevenir acceso a archivos ocultos
    location ~ /\. {
        deny all;
    }

    # Routing - TODO va a index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Procesar PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Denegar acceso a archivos sensibles
    location ~ /\.(env|installed) {
        deny all;
    }
}
```

Habilitar el sitio:
```bash
sudo ln -s /etc/nginx/sites-available/nexosupport /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Paso 5: Configurar /etc/hosts (Desarrollo Local)

Si está desarrollando localmente, agregar a `/etc/hosts`:

```
127.0.0.1   nexosupport.localhost.com
```

## Paso 6: Acceder al Instalador Web

1. Abrir navegador
2. Navegar a: `http://nexosupport.localhost.com/install`
3. Seguir los pasos del instalador:
   - Verificación de requisitos
   - Configuración de base de datos
   - Creación de usuario administrador

## Paso 7: Verificar Instalación

Después de completar el instalador:

1. Verificar que existe el archivo `.installed` en la raíz
2. Verificar que existe el archivo `.env` con la configuración
3. Acceder a `/login` para iniciar sesión

## Troubleshooting

### Pantalla en blanco o Error 500

**Causa:** No se ejecutó `composer install`

**Solución:**
```bash
cd /ruta/a/nexosupport
composer install
```

Para verificar que todo está bien:
```bash
php check.php
```

### Error: "Not Found" al acceder a /install

**Causa:** mod_rewrite no habilitado o .htaccess no funciona

**Solución Apache:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Verificar que `AllowOverride All` esté en la configuración del VirtualHost.

**Solución Nginx:**
Verificar que la configuración `try_files` esté correcta (ver arriba).

### Error: "Permission denied" en var/

**Solución:**
```bash
chmod -R 755 var/
chown -R www-data:www-data var/  # Usuario del servidor web
```

### Error de conexión a base de datos

Verificar:
1. MySQL/MariaDB está corriendo
2. Usuario y contraseña son correctos
3. Base de datos existe (el instalador la crea automáticamente)

### Error: "Class not found"

**Solución:**
```bash
composer dump-autoload
```

### Advertencias de mbstring deprecadas (PHP 8.x)

**Síntoma:** Mensajes en el log como:
```
PHP Deprecated: PHP Startup: Use of mbstring.http_input is deprecated
PHP Deprecated: PHP Startup: Use of mbstring.http_output is deprecated
PHP Deprecated: PHP Startup: Use of mbstring.internal_encoding is deprecated
```

**Causa:** Configuraciones obsoletas en `php.ini`

**Solución:**

**MAMP (Windows/Mac):**
1. Abrir el archivo `php.ini`:
   - MAMP: `/Applications/MAMP/bin/php/php8.x.x/conf/php.ini`
   - MAMP Windows: `C:\MAMP\bin\php\php8.x.x\php.ini`

2. Buscar y comentar (agregar `;` al inicio) estas líneas:
```ini
;mbstring.http_input = pass
;mbstring.http_output = pass
;mbstring.internal_encoding = UTF-8
```

3. Reiniciar MAMP/Apache

**Linux (Apache/Nginx):**
1. Editar php.ini:
```bash
sudo nano /etc/php/8.x/apache2/php.ini
# o
sudo nano /etc/php/8.x/fpm/php.ini
```

2. Comentar las mismas líneas

3. Reiniciar servicio:
```bash
sudo systemctl restart apache2
# o
sudo systemctl restart php8.x-fpm
```

> **Nota:** Estas advertencias no afectan el funcionamiento del sistema, solo ensucian los logs.

## Configuración de Producción

### 1. Cambiar modo a producción

En `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

### 2. Configurar HTTPS

Apache:
```apache
<VirtualHost *:443>
    ServerName nexosupport.example.com

    SSLEngine on
    SSLCertificateFile /ruta/cert.pem
    SSLCertificateKeyFile /ruta/key.pem

    # ... resto de configuración
</VirtualHost>
```

Nginx:
```nginx
server {
    listen 443 ssl http2;
    server_name nexosupport.example.com;

    ssl_certificate /ruta/cert.pem;
    ssl_certificate_key /ruta/key.pem;

    # ... resto de configuración
}
```

### 3. Configurar backups de base de datos

```bash
# Crear cron job para backup diario
0 2 * * * mysqldump -u usuario -p'password' nexosupport > /backups/nexosupport_$(date +\%Y\%m\%d).sql
```

## Soporte

Para problemas de instalación, contactar:
- **Soporte Técnico:** soporteplataformas@iser.edu.co
- **Nexo Operativo:** nexo.operativo@iser.edu.co

---

**NexoSupport v1.0.0**
