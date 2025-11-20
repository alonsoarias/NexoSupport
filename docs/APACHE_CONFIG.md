# Configuración de Apache para NexoSupport

## Principio: Exposición Mínima

Según la arquitectura Frankenstyle de NexoSupport, **SOLO `index.php`** debe existir en `public_html/`.

```
public_html/
└── index.php    ← ÚNICO archivo permitido
```

Todos los demás recursos (assets, scripts, etc.) se sirven a través del front controller.

---

## Configuración de Apache (VirtualHost)

Como no hay `.htaccess` en `public_html/`, la configuración debe hacerse directamente en el VirtualHost de Apache.

### Opción 1: Configuración Completa en VirtualHost (Recomendado)

```apache
<VirtualHost *:80>
    ServerName nexosupport.local
    ServerAdmin admin@nexosupport.local

    # Document Root apunta a public_html
    DocumentRoot /var/www/nexosupport/public_html

    <Directory /var/www/nexosupport/public_html>
        # Permitir acceso
        Require all granted

        # Habilitar RewriteEngine
        RewriteEngine On
        RewriteBase /

        # Si el archivo o directorio existe, servir directamente
        # Esto permite que index.php se sirva normalmente
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d

        # Todas las demás peticiones van a index.php
        RewriteRule ^(.*)$ index.php [QSA,L]

        # Prevenir listado de directorios
        Options -Indexes +FollowSymLinks

        # Proteger archivos sensibles (comenzando con .)
        <FilesMatch "^\.">
            Require all denied
        </FilesMatch>
    </Directory>

    # Configuración de PHP (opcional)
    <IfModule mod_php.c>
        php_value upload_max_filesize 50M
        php_value post_max_size 50M
        php_value max_execution_time 300
        php_value max_input_time 300
        php_flag display_errors Off
        php_flag log_errors On
    </IfModule>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/nexosupport-error.log
    CustomLog ${APACHE_LOG_DIR}/nexosupport-access.log combined
</VirtualHost>
```

### Opción 2: Permitir .htaccess (Menos Seguro)

Si necesitas permitir `.htaccess` en `public_html/`:

```apache
<VirtualHost *:80>
    ServerName nexosupport.local
    DocumentRoot /var/www/nexosupport/public_html

    <Directory /var/www/nexosupport/public_html>
        Require all granted

        # PERMITIR .htaccess
        AllowOverride All

        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/nexosupport-error.log
    CustomLog ${APACHE_LOG_DIR}/nexosupport-access.log combined
</VirtualHost>
```

Y crear archivo `public_html/.htaccess`:

```apache
# NexoSupport - Apache Configuration

# Habilitar rewrite engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Si el archivo o directorio existe, servir directamente
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Todas las demás peticiones van a index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Prevenir listado de directorios
Options -Indexes

# Proteger archivos sensibles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

---

## Configuración de Nginx (Alternativa)

Si usas Nginx en lugar de Apache:

```nginx
server {
    listen 80;
    server_name nexosupport.local;

    root /var/www/nexosupport/public_html;
    index index.php;

    # Logging
    access_log /var/log/nginx/nexosupport-access.log;
    error_log /var/log/nginx/nexosupport-error.log;

    # Front Controller Pattern
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Prevenir acceso a directorios fuera de public_html
    location ~ ^/(lib|admin|auth|install|var|vendor) {
        deny all;
    }
}
```

---

## Verificación de Configuración

### 1. Verificar mod_rewrite está habilitado (Apache)

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2

# CentOS/RHEL
# mod_rewrite suele estar habilitado por defecto
sudo systemctl restart httpd
```

### 2. Verificar Document Root

El Document Root debe apuntar a `public_html/`, NO a la raíz del proyecto:

❌ **Incorrecto:**
```
DocumentRoot /var/www/nexosupport
```

✅ **Correcto:**
```
DocumentRoot /var/www/nexosupport/public_html
```

### 3. Probar Routing

```bash
# Debe funcionar (front controller)
curl http://nexosupport.local/

# Debe redirigir a instalador o dashboard
curl -L http://nexosupport.local/

# Debe servir assets de themes
curl http://nexosupport.local/theme/core/pix/logo.png
```

---

## Seguridad: Proteger Directorios Sensibles

**IMPORTANTE:** Como el Document Root apunta a `public_html/`, los directorios sensibles NO son accesibles directamente desde web:

```
/var/www/nexosupport/
├── lib/           ← NO ACCESIBLE vía web ✅
├── admin/         ← NO ACCESIBLE vía web ✅
├── auth/          ← NO ACCESIBLE vía web ✅
├── install/       ← NO ACCESIBLE vía web ✅
├── vendor/        ← NO ACCESIBLE vía web ✅
└── public_html/   ← Document Root (ÚNICO directorio accesible)
    └── index.php  ← Front Controller
```

El acceso a `/admin`, `/login`, etc. se maneja internamente por el front controller mediante routing.

---

## Troubleshooting

### Problema: "404 Not Found" en todas las rutas excepto /

**Causa:** mod_rewrite no está habilitado o la configuración de rewrite no funciona.

**Solución:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Problema: "403 Forbidden"

**Causa:** Permisos incorrectos o configuración de `Require` mal configurada.

**Solución:**
```bash
# Verificar permisos
sudo chown -R www-data:www-data /var/www/nexosupport
sudo chmod -R 755 /var/www/nexosupport

# En VirtualHost usar:
Require all granted
```

### Problema: PHP no se ejecuta, se descarga

**Causa:** PHP no está configurado correctamente.

**Solución (Apache):**
```bash
# Ubuntu/Debian
sudo apt install libapache2-mod-php
sudo a2enmod php8.1
sudo systemctl restart apache2
```

**Solución (Nginx):**
```bash
# Verificar PHP-FPM está corriendo
sudo systemctl status php8.1-fpm
sudo systemctl start php8.1-fpm
```

---

## Ejemplo de Configuración Completa (Desarrollo Local)

```apache
# /etc/apache2/sites-available/nexosupport.conf

<VirtualHost *:80>
    ServerName nexosupport.local
    ServerAlias www.nexosupport.local
    ServerAdmin soporteplataformas@iser.edu.co

    DocumentRoot /var/www/nexosupport/public_html

    <Directory /var/www/nexosupport/public_html>
        Require all granted
        AllowOverride None
        Options -Indexes +FollowSymLinks

        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>

    # Logs de desarrollo
    ErrorLog /var/www/nexosupport/var/logs/apache-error.log
    CustomLog /var/www/nexosupport/var/logs/apache-access.log combined
    LogLevel warn
</VirtualHost>
```

Habilitar el sitio:

```bash
sudo ln -s /etc/apache2/sites-available/nexosupport.conf /etc/apache2/sites-enabled/
# O usar:
sudo a2ensite nexosupport
sudo systemctl reload apache2
```

Agregar a `/etc/hosts`:

```
127.0.0.1    nexosupport.local
```

---

## Referencias

- [Apache mod_rewrite Documentation](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)
- [Nginx Routing Configuration](https://nginx.org/en/docs/http/ngx_http_core_module.html#location)
- Moodle Security: [Protecting Document Root](https://docs.moodle.org/en/Security_recommendations)
