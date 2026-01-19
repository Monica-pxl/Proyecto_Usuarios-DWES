# 💬 API de Chat General

## Descripción
Sistema de chat público en tiempo real para todos los usuarios activos de la aplicación.

## Características

✅ **Enviar y recibir mensajes en tiempo real**  
✅ **Mostrar solo mensajes desde que el usuario inició sesión**  
✅ **Solo usuarios activos pueden participar**  
✅ **Chat público compartido entre todos los usuarios**  
✅ **Actualización automática cada 5 segundos**

---

## Endpoints

### 1️⃣ GET `/api/general` - Obtener Mensajes

**Descripción:** Devuelve los mensajes del chat general desde que el usuario inició sesión.

**Método:** `GET`

**Autenticación:** ✅ Requerida (Token Bearer)

**Headers:**
```http
Authorization: Bearer {tu_token}
```

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "mensajes": [
    {
      "id": 1,
      "contenido": "¡Hola a todos!",
      "fechaCreacion": "2026-01-19 10:30:45",
      "autor": {
        "id": 2,
        "nombre": "Juan Pérez"
      }
    },
    {
      "id": 2,
      "contenido": "Bienvenido al chat",
      "fechaCreacion": "2026-01-19 10:31:12",
      "autor": {
        "id": 3,
        "nombre": "María García"
      }
    }
  ],
  "total": 2
}
```

**Errores Posibles:**

```json
// Usuario no autenticado (401)
{
  "error": "Usuario no autenticado"
}
```

---

### 2️⃣ POST `/api/general` - Enviar Mensaje

**Descripción:** Envía un nuevo mensaje al chat general.

**Método:** `POST`

**Autenticación:** ✅ Requerida (Token Bearer)

**Headers:**
```http
Authorization: Bearer {tu_token}
Content-Type: application/json
```

**Body:**
```json
{
  "contenido": "¡Hola a todos desde la API!"
}
```

**Respuesta Exitosa (201):**
```json
{
  "success": true,
  "mensaje": {
    "id": 3,
    "contenido": "¡Hola a todos desde la API!",
    "fechaCreacion": "2026-01-19 10:32:05",
    "autor": {
      "id": 1,
      "nombre": "Tu Nombre"
    }
  }
}
```

**Errores Posibles:**

```json
// Usuario no autenticado (401)
{
  "error": "Usuario no autenticado"
}

// Mensaje vacío (400)
{
  "error": "El contenido del mensaje es requerido"
}
```

---

## 🔧 Ejemplos de Uso

### JavaScript (Fetch API)

#### Obtener mensajes:
```javascript
const token = localStorage.getItem('auth_token');

fetch('http://localhost/api/general', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Mensajes:', data.mensajes);
  }
});
```

#### Enviar mensaje:
```javascript
const token = localStorage.getItem('auth_token');

fetch('http://localhost/api/general', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    contenido: '¡Hola a todos!'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Mensaje enviado:', data.mensaje);
  }
});
```

---

### cURL

#### Obtener mensajes:
```bash
curl -X GET "http://localhost/api/general" \
  -H "Authorization: Bearer tu_token_aqui"
```

#### Enviar mensaje:
```bash
curl -X POST "http://localhost/api/general" \
  -H "Authorization: Bearer tu_token_aqui" \
  -H "Content-Type: application/json" \
  -d '{"contenido":"¡Hola desde cURL!"}'
```

---

### Postman

1. **Obtener mensajes (GET):**
   - URL: `http://localhost/api/general`
   - Method: `GET`
   - Headers: `Authorization: Bearer {token}`

2. **Enviar mensaje (POST):**
   - URL: `http://localhost/api/general`
   - Method: `POST`
   - Headers:
     - `Authorization: Bearer {token}`
     - `Content-Type: application/json`
   - Body (raw JSON):
     ```json
     {
       "contenido": "Mi mensaje aqui"
     }
     ```

---

## 🎯 Flujo de Trabajo

### 1. Usuario inicia sesión
```
POST /api/login
{
  "correo": "usuario@email.com",
  "password": "password123"
}

Respuesta:
{
  "token": "abc123...",
  "user": { ... }
}
```

### 2. Sistema guarda fecha de inicio de sesión
- El backend guarda automáticamente `fechaInicioSesion` en la base de datos
- Solo se mostrarán mensajes posteriores a esta fecha

### 3. Usuario accede al chat
```
GET /api/general
Authorization: Bearer abc123...

Respuesta: Mensajes desde la fecha de inicio de sesión
```

### 4. Usuario envía mensaje
```
POST /api/general
{
  "contenido": "¡Hola!"
}

El mensaje se guarda en la sala "General"
```

### 5. Otros usuarios ven el mensaje
- Solo usuarios con estado activo (`estado = true`)
- Solo mensajes desde su propia fecha de inicio de sesión

---

## 🗄️ Estructura de Base de Datos

### Tabla: `user`
```sql
- id
- nombre
- correo
- password
- token_autenticacion
- estado (boolean)
- fecha_inicio_sesion (datetime) ← NUEVO CAMPO
- latitud
- longitud
- fecha_actualizacion_ubicacion
```

### Tabla: `sala`
```sql
- id
- nombre (ej: "General")
- activa (boolean)
- fecha_creacion
```

### Tabla: `mensage`
```sql
- id
- contenido
- fecha_creacion
- autor_id (FK → user.id)
- sala_id (FK → sala.id)
- leido_por (array)
```

---

## 🔒 Seguridad

- ✅ **Autenticación requerida:** Todos los endpoints requieren token válido
- ✅ **Solo usuarios activos:** Solo usuarios con `estado = true` pueden participar
- ✅ **Validación de contenido:** Los mensajes vacíos son rechazados
- ✅ **Sala automática:** La sala "General" se crea automáticamente si no existe

---

## 📋 Notas Importantes

1. **Sala General:**
   - Se crea automáticamente la primera vez que se accede
   - Nombre: "General"
   - Activa: `true`

2. **Fecha de inicio de sesión:**
   - Se guarda automáticamente en el login
   - Determina qué mensajes ve cada usuario
   - Cada nueva sesión actualiza esta fecha

3. **Solo usuarios activos:**
   - Los mensajes solo se muestran de usuarios con `estado = true`
   - Usuarios inactivos no aparecen en el chat

4. **Actualización en tiempo real:**
   - El frontend actualiza mensajes cada 5 segundos
   - Para aplicaciones más profesionales, considerar WebSockets

---

## 🎨 Interfaz de Usuario

El chat se muestra en la página de inicio (`/home`):

- **Ubicación:** Debajo de la sección "Usuarios Cercanos"
- **Características visuales:**
  - Área de mensajes con scroll automático
  - Mensajes propios alineados a la derecha (azul)
  - Mensajes de otros alineados a la izquierda (blanco)
  - Input con botón de enviar
  - Botón de actualizar manual

---

## 🚀 Migración de Base de Datos

Si ya tienes la base de datos creada, ejecuta:

```bash
php bin/console doctrine:schema:update --force
```

Esto agregará el nuevo campo `fecha_inicio_sesion` a la tabla `user`.

---

## 📝 Ejemplo Completo de Integración

```html
<!DOCTYPE html>
<html>
<head>
    <title>Chat General</title>
</head>
<body>
    <div id="mensajes"></div>
    <input type="text" id="nuevoMensaje" placeholder="Escribe un mensaje...">
    <button onclick="enviarMensaje()">Enviar</button>

    <script>
        const token = localStorage.getItem('auth_token');
        const API_URL = 'http://localhost/api/general';

        // Cargar mensajes
        async function cargarMensajes() {
            const res = await fetch(API_URL, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            
            if (data.success) {
                const div = document.getElementById('mensajes');
                div.innerHTML = data.mensajes.map(m => 
                    `<p><strong>${m.autor.nombre}:</strong> ${m.contenido}</p>`
                ).join('');
            }
        }

        // Enviar mensaje
        async function enviarMensaje() {
            const input = document.getElementById('nuevoMensaje');
            const contenido = input.value.trim();
            
            if (!contenido) return;

            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ contenido })
            });

            if (res.ok) {
                input.value = '';
                cargarMensajes();
            }
        }

        // Actualizar cada 5 segundos
        setInterval(cargarMensajes, 5000);
        cargarMensajes(); // Carga inicial
    </script>
</body>
</html>
```

---

## ✅ Checklist de Implementación

- [x] Crear campo `fechaInicioSesion` en entidad User
- [x] Actualizar método login para guardar fecha de inicio
- [x] Crear endpoint GET `/api/general`
- [x] Crear endpoint POST `/api/general`
- [x] Crear/obtener sala "General" automáticamente
- [x] Filtrar mensajes por fecha de inicio de sesión
- [x] Filtrar solo usuarios activos
- [x] Agregar sección de chat en home.html.twig
- [x] Implementar JavaScript para cargar/enviar mensajes
- [x] Actualización automática cada 5 segundos
- [x] Manejo de errores de autenticación
- [x] Ejecutar migración de base de datos

---

## 🎉 ¡Listo!

El chat general está completamente implementado y funcional. Los usuarios autenticados pueden:
- Ver mensajes desde que iniciaron sesión
- Enviar mensajes al chat público
- Ver mensajes solo de usuarios activos
- Disfrutar de actualizaciones en tiempo real
