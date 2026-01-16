# Sistema de Autenticación API - Symfony

Sistema completo de autenticación mediante API con tokens Bearer, registro de usuarios y página de inicio protegida.

## 🚀 Características

- ✅ Autenticación mediante API con tokens Bearer
- ✅ Login, Logout, Registro y Perfil de usuario
- ✅ Tokens aleatorios generados con `bin2hex(random_bytes(32))`
- ✅ Control de estado del usuario (activo/inactivo)
- ✅ Página de inicio protegida con interfaz web
- ✅ Sin campo `roles` en la base de datos (solo método `getRoles()`)
- ✅ Interfaz de login y registro integrada

## 📋 Endpoints de la API

### 1. POST `/api/login`
Autentica un usuario y genera un token de sesión.

**Request:**
```json
{
  "correo": "usuario@example.com",
  "password": "mipassword"
}
```

**Response exitoso (200):**
```json
{
  "success": true,
  "token": "a1b2c3d4e5f6...",
  "user": {
    "id": 1,
    "correo": "usuario@example.com",
    "nombre": "Juan Pérez"
  }
}
```

**Efectos:**
- ✅ Verifica credenciales
- ✅ Marca `estado = true` en el usuario
- ✅ Genera y guarda token en `tokenAutenticacion`

---

### 2. POST `/api/logout`
Cierra la sesión del usuario autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response exitoso (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}
```

**Efectos:**
- ✅ Marca `estado = false`
- ✅ Limpia `tokenAutenticacion = null`

---

### 3. POST `/api/register`
Registra un nuevo usuario en el sistema.

**Request:**
```json
{
  "nombre": "Juan Pérez",
  "correo": "juan@example.com",
  "password": "mipassword"
}
```

**Response exitoso (201):**
```json
{
  "success": true,
  "message": "Usuario registrado exitosamente",
  "user": {
    "id": 1,
    "nombre": "Juan Pérez",
    "correo": "juan@example.com"
  }
}
```

**Efectos:**
- ✅ Hashea la contraseña con `UserPasswordHasherInterface`
- ✅ Crea usuario con `estado = false`
- ✅ Establece `tokenAutenticacion = null`

---

### 4. GET `/api/perfil`
Obtiene información del usuario autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response exitoso (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "nombre": "Juan Pérez",
    "correo": "juan@example.com",
    "estado": true
  }
}
```

**Requiere:**
- ✅ Token válido en cabecera Authorization
- ✅ Usuario con `estado = true`

---

## 🌐 Página Web

### GET `/`
Muestra la página de inicio.

**Comportamiento:**
- Si NO hay usuario autenticado → Muestra formulario de login/registro
- Si hay usuario autenticado pero `estado = false` → Muestra login con mensaje de error
- Si hay usuario autenticado y `estado = true` → Muestra página de inicio protegida

---

## 🔐 Seguridad

### Configuración (`security.yaml`)

```yaml
providers:
  app_user_provider:
    entity:
      class: App\Entity\User
      property: correo

firewalls:
  api:
    pattern: ^/api
    stateless: true
    provider: app_user_provider
    custom_authenticator: App\Security\ApiAuthenticator
  
  main:
    lazy: true
    provider: app_user_provider
    logout:
      path: app_logout

access_control:
  - { path: ^/api/login, roles: PUBLIC_ACCESS }
  - { path: ^/api/register, roles: PUBLIC_ACCESS }
  - { path: ^/api, roles: ROLE_USER }
```

### Autenticador (`ApiAuthenticator.php`)

- ✅ Valida tokens Bearer en cabecera `Authorization`
- ✅ Busca usuario por `tokenAutenticacion`
- ✅ Verifica que `estado = true`
- ✅ Devuelve errores JSON en caso de fallo

---

## 📦 Estructura de la Entidad User

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id = null;
    private string $nombre;
    private string $correo;
    private string $password;
    private ?string $tokenAutenticacion = null;
    private bool $estado = true;
    
    // NO hay campo $roles en la base de datos
    
    public function getRoles(): array
    {
        return ['ROLE_USER']; // Siempre devuelve ROLE_USER
    }
}
```

---

## 🧪 Cómo probar

### 1. Crear la base de datos (si no existe)
```bash
php bin/console doctrine:database:create
```

### 2. Ejecutar migraciones
```bash
php bin/console doctrine:migrations:migrate
```

### 3. Iniciar el servidor
```bash
symfony server:start
# o
php -S localhost:8000 -t public
```

### 4. Probar con cURL

**Registrar usuario:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Test User","correo":"test@test.com","password":"test123"}'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"correo":"test@test.com","password":"test123"}'
```

**Ver perfil (con token):**
```bash
curl -X GET http://localhost:8000/api/perfil \
  -H "Authorization: Bearer {TU_TOKEN_AQUI}"
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer {TU_TOKEN_AQUI}"
```

### 5. Probar en el navegador

1. Abre `http://localhost:8000/`
2. Verás el formulario de login
3. Haz clic en "Regístrate aquí" para crear una cuenta
4. Después de registrarte, inicia sesión
5. Serás redirigido a la página de inicio protegida

---

## 📁 Archivos creados/modificados

### Controladores:
- ✅ `src/Controller/ApiAuthController.php` - Endpoints de autenticación API
- ✅ `src/Controller/HomeController.php` - Página de inicio

### Seguridad:
- ✅ `src/Security/ApiAuthenticator.php` - Autenticador de tokens Bearer
- ✅ `config/packages/security.yaml` - Configuración de seguridad

### Vistas:
- ✅ `templates/home/login.html.twig` - Formulario de login/registro
- ✅ `templates/home/home.html.twig` - Página de inicio protegida

### Configuración:
- ✅ `config/routes.yaml` - Rutas adicionales

---

## ⚙️ Validaciones implementadas

### Login:
- ✅ Requiere `correo` y `password`
- ✅ Verifica que el usuario exista
- ✅ Valida contraseña con hasher de Symfony
- ✅ Devuelve error 401 si las credenciales son incorrectas

### Register:
- ✅ Requiere `nombre`, `correo` y `password`
- ✅ Verifica que el correo no esté registrado
- ✅ Hashea la contraseña automáticamente
- ✅ Devuelve error 409 si el correo ya existe

### Perfil:
- ✅ Requiere token Bearer válido
- ✅ Verifica que el usuario esté activo (`estado = true`)
- ✅ Devuelve error 401 si no hay token
- ✅ Devuelve error 403 si el usuario está inactivo

### Todas las rutas API (excepto login/register):
- ✅ Requieren autenticación con token Bearer
- ✅ Verifican que `estado = true`

---

## 🎯 Flujo de autenticación

```
1. Usuario visita "/" 
   → No autenticado → Muestra login

2. Usuario hace clic en "Registrarse"
   → POST /api/register
   → Crea usuario con estado=false

3. Usuario hace login
   → POST /api/login
   → Marca estado=true
   → Genera token aleatorio
   → Guarda token en user.tokenAutenticacion
   → Devuelve token al cliente

4. Cliente guarda token en localStorage

5. Usuario es redirigido a "/"
   → Autenticado + estado=true → Muestra home

6. Usuario accede a APIs protegidas
   → Envía token en header Authorization
   → ApiAuthenticator valida el token
   → Verifica estado=true
   → Permite acceso

7. Usuario hace logout
   → POST /api/logout con token
   → Marca estado=false
   → Limpia tokenAutenticacion
   → Redirige a login
```

---

## 🔧 Personalización

### Cambiar tiempo de expiración del token
Actualmente los tokens no expiran. Para añadir expiración:

1. Añade campo `tokenExpiracion` a User:
```php
#[ORM\Column(nullable: true)]
private ?\DateTimeInterface $tokenExpiracion = null;
```

2. En el login, establece la expiración:
```php
$user->setTokenExpiracion(new \DateTime('+1 day'));
```

3. En `ApiAuthenticator`, verifica la expiración:
```php
if ($user->getTokenExpiracion() < new \DateTime()) {
    throw new CustomUserMessageAuthenticationException('Token expirado');
}
```

### Añadir más roles
Aunque no hay campo `roles` en la BD, puedes modificar `getRoles()`:

```php
public function getRoles(): array
{
    $roles = ['ROLE_USER'];
    
    // Ejemplo: añadir ROLE_ADMIN si el correo es admin
    if ($this->correo === 'admin@example.com') {
        $roles[] = 'ROLE_ADMIN';
    }
    
    return $roles;
}
```

---

## ✅ Checklist de implementación

- [x] POST /api/login - Autentica y genera token
- [x] POST /api/logout - Cierra sesión
- [x] POST /api/register - Registra nuevo usuario
- [x] GET /api/perfil - Obtiene datos del usuario
- [x] Verificación de estado=true en todas las APIs
- [x] ApiAuthenticator con Bearer token
- [x] HomeController con vista protegida
- [x] Formulario de login/registro integrado
- [x] Sin campo roles en User (solo getRoles())
- [x] Configuración de security.yaml
- [x] Password hashing con UserPasswordHasherInterface

---

## 📞 Soporte

Para cualquier duda o problema:
1. Verifica que la base de datos esté actualizada: `php bin/console doctrine:schema:update --force`
2. Limpia la caché: `php bin/console cache:clear`
3. Revisa los logs: `var/log/dev.log`

---

**¡Sistema listo para usar! 🎉**
