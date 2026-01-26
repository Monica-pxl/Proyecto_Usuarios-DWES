# 🔒 Sistema de Salas Privadas con Invitaciones - Documentación

## Descripción
Sistema de chat privado que permite a los usuarios enviar invitaciones para crear salas de conversación privadas entre dos personas. El receptor debe aceptar la invitación antes de que la sala se active. Solo los usuarios que participan en la sala pueden ver y enviar mensajes.

## Características Implementadas

### 1. **Modelo de Datos**
- ✅ Campo `tipo` en la entidad `Sala` ('general' o 'privada')
- ✅ Campo `activa` para diferenciar salas activas de invitaciones pendientes
- ✅ Campo `creador` (ManyToOne con User) para identificar quién envía la invitación
- ✅ Relación ManyToMany entre `Sala` y `User` (participantes)
- ✅ Migración de base de datos aplicada

### 2. **Endpoints API**

#### POST `/api/sala-privada/crear`
Envía una invitación para crear una sala privada.
- **Body**: `{ "usuarioId": number }`
- **Response**: Confirmación de invitación enviada
- **Comportamiento**: 
  - Crea una sala con `activa = false` (pendiente)
  - Marca al usuario actual como `creador`
  - Agrega ambos usuarios como participantes
  - Si ya existe una sala (activa o pendiente), notifica

#### GET `/api/invitaciones-pendientes`
Obtiene las invitaciones pendientes recibidas por el usuario autenticado.
- **Response**: Lista de invitaciones donde el usuario NO es el creador
- **Incluye**: Información del remitente y fecha de creación
- **Filtros**: Solo salas pendientes (activa = false) y tipo privada

#### POST `/api/invitacion/{salaId}/aceptar`
Acepta una invitación a sala privada.
- **Params**: `salaId` (ID de la invitación/sala)
- **Response**: Datos de la sala ahora activa
- **Comportamiento**: 
  - Verifica que el usuario sea participante pero NO creador
  - Cambia el estado de la sala a `activa = true`
  - La sala queda disponible para ambos usuarios
- **Validaciones**: Solo el receptor puede aceptar

#### POST `/api/invitacion/{salaId}/rechazar`
Rechaza una invitación a sala privada.
- **Params**: `salaId` (ID de la invitación/sala)
- **Response**: Confirmación de rechazo
- **Comportamiento**: 
  - Verifica que el usuario sea participante pero NO creador
  - Elimina la sala completamente de la base de datos
- **Validaciones**: Solo el receptor puede rechazar

#### GET `/api/salas-privadas`
Obtiene todas las salas privadas ACTIVAS del usuario autenticado.
- **Response**: Lista de salas activas con participantes
- **Filtros**: Solo salas con `activa = true` y `tipo = 'privada'`
- **Incluye**: Información del otro usuario en cada sala

#### GET `/api/sala-privada/{salaId}/mensajes`
Obtiene los mensajes de una sala privada específica.
- **Params**: `salaId` (ID de la sala)
- **Response**: Lista de mensajes ordenados cronológicamente
- **Validación**: 
  - El usuario debe ser participante de la sala
  - La sala debe estar activa

#### POST `/api/sala-privada/{salaId}/mensaje`
Envía un mensaje a una sala privada.
- **Params**: `salaId` (ID de la sala)
- **Body**: `{ "contenido": string }`
- **Response**: Datos del mensaje creado
- **Validación**: 
  - El usuario debe ser participante de la sala
  - La sala debe estar activa

### 3. **Interfaz de Usuario**

#### Botón "🔒 Sala Privada"
- Ubicado junto al botón "Actualizar" en el Chat General
- Abre un modal con lista de usuarios disponibles
- Permite enviar invitación a cualquier usuario activo

#### Modal de Creación de Invitación
- Muestra todos los usuarios activos (excepto el actual)
- Botón "Crear sala" para cada usuario
- Cierre automático al enviar la invitación
- Mensaje de confirmación: "Invitación enviada a [Usuario]"

#### Sección "📬 Invitaciones Pendientes"
- Lista de invitaciones recibidas (no enviadas)
- Tarjetas con información del remitente
- Botones para Aceptar (✓) o Rechazar (✗)
- Badge amarillo para identificación visual
- Actualización automática cada 10 segundos
- Muestra fecha/hora de la invitación

#### Sección "🔒 Mis Salas Privadas"
- Lista solo las salas ACTIVAS (invitaciones aceptadas)
- Tarjetas clickeables con hover effect
- Muestra nombre del otro usuario
- Badge 🔒 para identificación visual
- No muestra salas pendientes

#### Chat Privado
- Interfaz similar al chat general
- Botón "← Volver" para cerrar el chat
- Muestra nombre de la sala y participantes
- Mensajes con colores diferenciados (verde para salas privadas)
- Campo de entrada específico para mensajes privados
- Solo accesible en salas ACTIVAS

### 4. **Funcionalidades JavaScript**

#### Funciones Principales
- `abrirModalSalaPrivada()`: Abre modal y carga usuarios disponibles
- `crearSalaPrivada(usuarioId, nombreUsuario)`: Envía invitación a usuario
- `cargarInvitacionesPendientes()`: Carga invitaciones recibidas pendientes
- `aceptarInvitacion(salaId, nombreRemitente)`: Acepta invitación y activa sala
- `rechazarInvitacion(salaId)`: Rechaza y elimina invitación
- `cargarSalasPrivadas()`: Actualiza lista de salas activas
- `abrirSalaPrivada(salaId, nombreSala, participantes)`: Abre chat privado
- `cerrarChatPrivado()`: Cierra chat privado
- `cargarMensajesSalaPrivada()`: Carga mensajes de sala actual
- `enviarMensajePrivado()`: Envía mensaje a sala privada

#### Características
- Carga automática al iniciar sesión
- Actualización automática de invitaciones cada 10 segundos
- Validación de contenido vacío
- Manejo de errores con mensajes informativos
- Confirmación antes de rechazar invitación
- Apertura automática de sala al aceptar invitación
- Scroll automático a nuevos mensajes

## Flujo de Uso

### Flujo Completo: Envío y Aceptación de Invitación

1. **Usuario A envía invitación**:
   - Hace clic en "🔒 Sala Privada"
   - Selecciona a Usuario B de la lista
   - Sistema crea sala con `activa = false`
   - Mensaje: "Invitación enviada a Usuario B"
   - Usuario A NO ve la sala en "Mis Salas Privadas" (está pendiente)

2. **Usuario B recibe invitación**:
   - La invitación aparece en "📬 Invitaciones Pendientes"
   - Ve tarjeta con información de Usuario A
   - Actualización automática cada 10 segundos
   - Opciones: Aceptar o Rechazar

3. **Usuario B acepta la invitación**:
   - Hace clic en "✓ Aceptar"
   - Sistema cambia sala a `activa = true`
   - Mensaje: "Invitación aceptada. Ahora puedes chatear con Usuario A"
   - Chat se abre automáticamente
   - Sala aparece en "Mis Salas Privadas" para AMBOS usuarios

4. **Usuario B rechaza la invitación**:
   - Hace clic en "✗ Rechazar"
   - Confirmación: "¿Estás seguro?"
   - Sistema elimina la sala completamente
   - Mensaje: "Invitación rechazada"
   - Usuario A ya no verá ninguna referencia a esa invitación

5. **Acceder a Sala Activa**:
   - Cualquiera de los dos usuarios ve la sala en "Mis Salas Privadas"
   - Hace clic en la tarjeta de la sala
   - Se abre el chat con historial de mensajes
   - Pueden enviar y recibir mensajes libremente

### Casos Especiales

- **Invitación duplicada**: Si Usuario A intenta enviar otra invitación a Usuario B mientras hay una pendiente, recibe error: "Ya existe una invitación pendiente con este usuario"
- **Sala ya activa**: Si intentan crear una sala cuando ya existe una activa, recibe mensaje: "Ya existe una sala activa con este usuario"
- **Mensajes antes de aceptar**: No se pueden enviar mensajes mientras la sala está pendiente
- **Usuario inactivo**: No se pueden enviar invitaciones a usuarios que no están conectados

## Seguridad

- ✅ Autenticación mediante token Bearer requerida en todos los endpoints
- ✅ Verificación de participación en sala antes de acceder/enviar mensajes
- ✅ Solo usuarios activos pueden crear/participar en salas
- ✅ Solo el receptor puede aceptar/rechazar invitaciones (no el creador)
- ✅ Prevención de salas duplicadas entre mismos usuarios
- ✅ Validación de estado de sala (activa) antes de permitir mensajes
- ✅ Validación de entrada en todos los endpoints
- ✅ Eliminación completa al rechazar (sin rastros)

## Diferencias vs Chat General

| Característica | Chat General | Salas Privadas |
|----------------|--------------|----------------|
| Acceso | Todos los usuarios activos | Solo participantes invitados |
| Creación | Automática (única sala) | Mediante invitación |
| Activación | Inmediata | Requiere aceptación |
| Participantes | Ilimitados | 2 usuarios (1 a 1) |
| Mensajes visibles | Desde inicio de sesión | Todo el historial |
| Color | Azul | Verde |
| Identificador | 💬 | 🔒 |

## Estilos

- Tarjetas con efecto hover (elevación y sombra)
- Colores diferenciados:
  - Chat general: azul (`bg-primary`)
  - Chat privado: verde (`bg-success`)
  - Invitaciones pendientes: amarillo (`bg-warning`)
- Diseño responsive con Bootstrap 5
- Iconos emoji para mejor UX
- Confirmación visual de acciones

## Mejoras Futuras (Opcionales)

- [ ] Notificaciones push en tiempo real (WebSockets)
- [ ] Contador de invitaciones pendientes en badge
- [ ] Indicador de mensajes no leídos por sala
- [ ] Opción de eliminar/archivar salas activas
- [ ] Búsqueda de usuarios en modal
- [ ] Historial de mensajes con paginación
- [ ] Envío de archivos/imágenes en salas privadas
- [ ] Estados de lectura de mensajes
- [ ] Typing indicators
- [ ] Permitir cancelar invitaciones enviadas
- [ ] Salas grupales (más de 2 usuarios)

## Notas Técnicas

- Base de datos actualizada con campo `creador_id` en tabla `sala`
- Salas pendientes tienen `activa = false`, activas tienen `activa = true`
- Salas existentes (general) configuradas como `activa = true` por defecto
- Compatible con sistema de chat general existente
- Sin interferencia con funcionalidades previas
- Las invitaciones rechazadas se eliminan completamente (no hay historial de rechazos)
