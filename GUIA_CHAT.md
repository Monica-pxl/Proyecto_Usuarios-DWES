# 💬 Sistema de Chat - Guía Completa

## 🚀 Inicio Rápido

### 1. Acceder a la Interfaz Web

Abre tu navegador y visita:
```
http://localhost:8000/chat.html
```

### 2. Iniciar Sesión

Usa las credenciales de algún usuario existente:
- **Correo:** admin@proyecto.com
- **Contraseña:** admin123

O cualquier otro usuario de tu base de datos.

---

## 📱 Endpoints de la API

### Endpoint Principal - Información Completa del Chat
**GET** `/api/chat/info`

Retorna toda la información del chat en una sola llamada:
- Información del usuario actual
- Mensajes del chat general
- Lista de salas privadas
- Usuarios disponibles para chatear
- Estadísticas generales

**Ejemplo de respuesta:**
```json
{
  "success": true,
  "usuario": {
    "id": 1,
    "nombre": "Admin",
    "correo": "admin@proyecto.com"
  },
  "chatGeneral": {
    "id": 1,
    "nombre": "General",
    "cantidadMensajes": 15,
    "mensajes": [...]
  },
  "salasPrivadas": [...],
  "usuariosDisponibles": [...],
  "estadisticas": {
    "totalUsuarios": 3,
    "usuariosEnLinea": 1,
    "salasPrivadasActivas": 2,
    "mensajesGeneralHoy": 15
  }
}
```

---

### Chat General - Obtener Mensajes
**GET** `/api/chat/general/mensajes`

Obtiene todos los mensajes del chat general desde que el usuario inició sesión.

**Respuesta:**
```json
{
  "success": true,
  "sala": {
    "id": 1,
    "nombre": "General"
  },
  "mensajes": [
    {
      "id": 1,
      "contenido": "Hola a todos!",
      "fechaCreacion": "2026-01-27 12:30:00",
      "autor": {
        "id": 1,
        "nombre": "Admin",
        "correo": "admin@proyecto.com"
      },
      "sala": {
        "id": 1,
        "nombre": "General",
        "tipo": "general"
      }
    }
  ],
  "total": 1
}
```

---

### Chat General - Enviar Mensaje
**POST** `/api/chat/general/mensaje`

Envía un mensaje al chat general.

**Body:**
```json
{
  "contenido": "Hola, este es mi mensaje"
}
```

**Respuesta:**
```json
{
  "success": true,
  "mensaje": {
    "id": 2,
    "contenido": "Hola, este es mi mensaje",
    "fechaCreacion": "2026-01-27 12:35:00",
    "autor": {...}
  }
}
```

---

### Usuarios En Línea
**GET** `/api/chat/usuarios-online`

Lista todos los usuarios y su estado (en línea/desconectado).

**Respuesta:**
```json
{
  "success": true,
  "usuarios": [
    {
      "id": 1,
      "nombre": "Admin",
      "correo": "admin@proyecto.com",
      "enLinea": true,
      "ultimaActividad": "2026-01-27 12:30:00"
    }
  ],
  "total": 3,
  "enLinea": 1
}
```

---

### Buscar Mensajes
**GET** `/api/chat/buscar?q=hola`

Busca mensajes que contengan el texto especificado.

**Parámetros:**
- `q` (string, mínimo 3 caracteres): Texto a buscar

**Respuesta:**
```json
{
  "success": true,
  "query": "hola",
  "resultados": [...],
  "total": 5
}
```

---

## 🔧 Endpoints Existentes (de ApiAuthController)

### Chat General (Modo Compatible)
- **GET** `/api/general` - Obtener mensajes del chat general
- **POST** `/api/general` - Enviar mensaje al chat general

### Salas Privadas
- **POST** `/api/sala-privada/crear` - Crear una sala privada
- **GET** `/api/sala-privada` - Obtener salas privadas del usuario
- **GET** `/api/sala-privada/{id}/mensajes` - Obtener mensajes de una sala
- **POST** `/api/sala-privada/{id}/mensaje` - Enviar mensaje a sala privada
- **DELETE** `/api/sala-privada/{id}` - Eliminar sala privada
- **POST** `/api/sala-privada/{id}/aceptar` - Aceptar invitación
- **POST** `/api/sala-privada/{id}/rechazar` - Rechazar invitación

---

## 🎨 Características de la Interfaz Web

### Chat General
- ✅ Ver mensajes en tiempo real
- ✅ Enviar mensajes
- ✅ Auto-refresh cada 3 segundos
- ✅ Indicador de usuarios en línea
- ✅ Diseño moderno y responsive

### Chats Privados
- ✅ Lista de conversaciones privadas
- ✅ Ver mensajes de cada conversación
- ✅ Enviar mensajes privados
- ✅ Indicador de mensajes no leídos (próximamente)

### Funcionalidades
- ✅ Login/Logout
- ✅ Cambio entre chat general y privado
- ✅ Scroll automático a últimos mensajes
- ✅ Escape de HTML para seguridad
- ✅ Formato de hora legible

---

## 📊 Reglas de Negocio

### Usuario En Línea
Un usuario se considera "en línea" si:
- Ha iniciado sesión hace menos de 5 minutos
- Su campo `fechaInicioSesion` está actualizado

### Mensajes del Chat General
- Se muestran desde la fecha de inicio de sesión del usuario
- Por defecto, desde las últimas 24 horas
- Ordenados cronológicamente (antiguos primero)

### Salas Privadas
- Solo entre 2 usuarios
- Pueden estar activas o pendientes (invitación)
- Se pueden eliminar por cualquiera de los participantes

---

## 🧪 Cómo Probar

### Opción 1: Interfaz Web (Recomendada)

1. **Inicia el servidor:**
```powershell
symfony server:start
# o
php -S localhost:8000 -t public
```

2. **Abre el navegador:**
```
http://localhost:8000/chat.html
```

3. **Inicia sesión** con tus credenciales

4. **Explora:**
   - Tab "General" para ver el chat público
   - Tab "Privados" para ver chats privados
   - Envía mensajes
   - La interfaz se actualiza automáticamente

---

### Opción 2: PowerShell / cURL

```powershell
# 1. Login
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body '{"correo":"admin@proyecto.com","password":"admin123"}'

$token = $response.token

# 2. Obtener info completa del chat
Invoke-RestMethod -Uri "http://localhost:8000/api/chat/info" `
    -Headers @{"Authorization"="Bearer $token"}

# 3. Enviar mensaje al chat general
Invoke-RestMethod -Uri "http://localhost:8000/api/chat/general/mensaje" `
    -Method POST `
    -Headers @{"Authorization"="Bearer $token";"Content-Type"="application/json"} `
    -Body '{"contenido":"Hola desde PowerShell!"}'

# 4. Ver usuarios en línea
Invoke-RestMethod -Uri "http://localhost:8000/api/chat/usuarios-online" `
    -Headers @{"Authorization"="Bearer $token"}

# 5. Buscar mensajes
Invoke-RestMethod -Uri "http://localhost:8000/api/chat/buscar?q=hola" `
    -Headers @{"Authorization"="Bearer $token"}
```

---

### Opción 3: Postman

Importa estos requests:

**1. Login**
```
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "correo": "admin@proyecto.com",
  "password": "admin123"
}
```

**2. Info del Chat**
```
GET http://localhost:8000/api/chat/info
Authorization: Bearer {{token}}
```

**3. Enviar Mensaje**
```
POST http://localhost:8000/api/chat/general/mensaje
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "contenido": "Mi mensaje"
}
```

---

## 📁 Estructura de Archivos

```
src/
  Controller/
    ChatController.php          # Nuevo controlador de chat
    ApiAuthController.php       # Controlador existente (auth + salas)
    
public/
  chat.html                     # Interfaz web del chat
  
  
```

---

## ✨ Diferencias entre Endpoints

### `/api/general` vs `/api/chat/general/mensajes`

| Característica | /api/general | /api/chat/general/mensajes |
|----------------|--------------|----------------------------|
| Controlador | ApiAuthController | ChatController |
| Propósito | Compatible con versión anterior | Nueva versión mejorada |
| Respuesta | Básica | Detallada con sala |
| GET | Obtener mensajes | Obtener mensajes |
| POST | Enviar mensaje | ❌ |

### `/api/general` vs `/api/chat/general/mensaje`

| Característica | POST /api/general | POST /api/chat/general/mensaje |
|----------------|-------------------|--------------------------------|
| Función | Enviar mensaje | Enviar mensaje |
| Respuesta | Básica | Mensaje formateado completo |
| Recomendado | ✅ | ✅ |

**Ambos funcionan igual**, usa el que prefieras.

---

## 🎯 Casos de Uso

### Caso 1: Mostrar Dashboard del Chat

```javascript
const response = await fetch('/api/chat/info', {
    headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();

// Tendrás todo lo necesario:
console.log('Mensajes generales:', data.chatGeneral.mensajes);
console.log('Salas privadas:', data.salasPrivadas);
console.log('Usuarios disponibles:', data.usuariosDisponibles);
console.log('Estadísticas:', data.estadisticas);
```

### Caso 2: Auto-refresh de Mensajes

```javascript
setInterval(async () => {
    const response = await fetch('/api/chat/general/mensajes', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await response.json();
    updateMessagesUI(data.mensajes);
}, 3000); // Cada 3 segundos
```

### Caso 3: Indicador de Usuarios En Línea

```javascript
const response = await fetch('/api/chat/usuarios-online', {
    headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();

document.getElementById('online-count').textContent = 
    `${data.enLinea} usuarios en línea`;
```

---

## 🎉 ¡Todo Listo!

Tu sistema de chat está completamente funcional con:
- ✅ Chat general público
- ✅ Chats privados 1-a-1
- ✅ Interfaz web moderna
- ✅ API REST completa
- ✅ Usuarios en línea
- ✅ Búsqueda de mensajes
- ✅ Auto-refresh
- ✅ Diseño responsive

### Para empezar:

```powershell
# Inicia el servidor
symfony server:start

# Abre el navegador
start http://localhost:8000/chat.html
```

¡Disfruta tu sistema de chat! 💬🚀
