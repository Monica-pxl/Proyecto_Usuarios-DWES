# Comandos útiles para el proyecto

## 🚀 Inicialización

### 1. Crear/actualizar la base de datos
```bash
# Crear la base de datos (si no existe)
php bin/console doctrine:database:create

# Actualizar el esquema de la base de datos
php bin/console doctrine:schema:update --force

# O usar migraciones (recomendado)
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 2. Iniciar el servidor
```bash
# Con Symfony CLI (recomendado)
symfony server:start

# Con PHP built-in server
php -S localhost:8000 -t public

# Con XAMPP
# Simplemente abre http://localhost/Proyecto_Usuarios/public
```

---

## 🧪 Testing

### Probar la API con el script PHP
```bash
php test_api.php
```

### Probar con cURL

**Registro:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d "{\"nombre\":\"Test User\",\"correo\":\"test@test.com\",\"password\":\"test123\"}"
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d "{\"correo\":\"test@test.com\",\"password\":\"test123\"}"
```

**Perfil (requiere token):**
```bash
curl -X GET http://localhost:8000/api/perfil \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---

## 🔍 Debugging

### Ver rutas registradas
```bash
php bin/console debug:router
```

### Ver configuración de seguridad
```bash
php bin/console debug:config security
```

### Ver servicios disponibles
```bash
php bin/console debug:container
```

### Limpiar caché
```bash
php bin/console cache:clear
```

### Ver logs en tiempo real
```bash
# Linux/Mac
tail -f var/log/dev.log

# Windows PowerShell
Get-Content var/log/dev.log -Wait -Tail 50
```

---

## 🗄️ Base de datos

### Ver el esquema actual
```bash
php bin/console doctrine:schema:validate
```

### Crear fixtures (datos de prueba)
```bash
php bin/console doctrine:fixtures:load
```

### Crear un usuario manualmente en la consola
```bash
php bin/console
# Luego en la consola de Symfony:
$em = $container->get('doctrine')->getManager();
$hasher = $container->get('security.password_hasher');

$user = new App\Entity\User();
$user->setNombre('Admin');
$user->setCorreo('admin@example.com');
$user->setPassword($hasher->hashPassword($user, 'admin123'));
$user->setEstado(true);

$em->persist($user);
$em->flush();
```

### Consultar usuarios en la base de datos
```bash
# En MySQL/MariaDB
php bin/console doctrine:query:sql "SELECT * FROM user"

# O conectarse directamente
mysql -u root -p nombre_base_datos
# Luego:
SELECT id, nombre, correo, estado FROM user;
```

---

## 🔧 Mantenimiento

### Actualizar dependencias
```bash
composer update
```

### Instalar nueva dependencia
```bash
composer require nombre/paquete
```

### Ver versión de Symfony
```bash
php bin/console --version
```

### Verificar requisitos del sistema
```bash
symfony check:requirements
```

---

## 📝 Crear nuevos elementos

### Crear un nuevo controlador
```bash
php bin/console make:controller NombreController
```

### Crear una nueva entidad
```bash
php bin/console make:entity NombreEntidad
```

### Crear un servicio
```bash
php bin/console make:service NombreService
```

### Crear una migración
```bash
php bin/console make:migration
```

---

## 🐛 Solución de problemas comunes

### Error: "No route found for GET /api/login"
```bash
# Verificar que las rutas están registradas
php bin/console debug:router | grep api

# Limpiar caché
php bin/console cache:clear
```

### Error: "Table 'user' doesn't exist"
```bash
# Crear las tablas
php bin/console doctrine:schema:update --force
```

### Error: "Authentication credentials could not be found"
```bash
# Verificar que el token se está enviando correctamente
# Header debe ser: Authorization: Bearer {token}
```

### Error: "Access Denied"
```bash
# Verificar el access_control en config/packages/security.yaml
php bin/console debug:config security
```

### Token inválido o usuario inactivo
```bash
# Ver el estado del usuario en la base de datos
php bin/console doctrine:query:sql "SELECT id, correo, estado, token_autenticacion FROM user WHERE correo='test@test.com'"
```

---

## 📊 Monitoreo

### Ver peticiones en tiempo real (con Symfony CLI)
```bash
symfony server:log
```

### Ver estadísticas del servidor
```bash
symfony server:status
```

---

## 🔐 Seguridad

### Generar un nuevo secret
```bash
# Editar .env y cambiar APP_SECRET
php bin/console secrets:generate-keys
```

### Hash una contraseña manualmente
```bash
php bin/console security:hash-password
# Luego introduce la contraseña cuando te lo pida
```

---

## 📦 Producción

### Optimizar para producción
```bash
# Establecer el entorno
export APP_ENV=prod

# Limpiar y calentar caché
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Optimizar autoload de Composer
composer dump-autoload --optimize --classmap-authoritative
```

### Dump de la base de datos
```bash
# Exportar
mysqldump -u root -p nombre_base_datos > backup.sql

# Importar
mysql -u root -p nombre_base_datos < backup.sql
```

---

## 📱 Comandos rápidos comunes

```bash
# Resetear todo (¡CUIDADO! Borra los datos)
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Verificar la aplicación
php bin/console lint:container
php bin/console lint:yaml config
php bin/console lint:twig templates

# Ver información del proyecto
composer show
php bin/console about
```

---

## 🎯 Workflow de desarrollo típico

```bash
# 1. Iniciar servidor
symfony server:start -d

# 2. Ver logs en otra terminal
symfony server:log

# 3. Hacer cambios en el código...

# 4. Si cambias entidades, crear migración
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 5. Limpiar caché si es necesario
php bin/console cache:clear

# 6. Probar
php test_api.php

# O con navegador:
# http://localhost:8000/

# 7. Ver logs de errores
tail -f var/log/dev.log
```

---

## 📚 Recursos útiles

- Documentación de Symfony: https://symfony.com/doc/current/index.html
- Symfony Security: https://symfony.com/doc/current/security.html
- Doctrine ORM: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/
- API Platform (si quieres expandir): https://api-platform.com/

---

**¡Comandos listos para usar! 🎉**
