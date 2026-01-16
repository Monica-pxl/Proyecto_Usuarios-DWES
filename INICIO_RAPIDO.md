# 🚀 Inicio Rápido

## ⚡ 3 pasos para comenzar

### 1️⃣ Preparar la base de datos
```bash
php bin/console doctrine:schema:update --force
```

### 2️⃣ Iniciar el servidor
```bash
# Con Symfony CLI (recomendado)
symfony server:start

# O con PHP
php -S localhost:8000 -t public
```

### 3️⃣ Abrir en el navegador
```
http://localhost:8000/
```

---

## 🎯 Primer uso

1. **Verás el formulario de login**
2. **Haz clic en "Regístrate aquí"**
3. **Completa el formulario de registro**
4. **Inicia sesión con tus credenciales**
5. **¡Listo! Estás en la página de inicio**

---

## 🧪 Prueba rápida con cURL

```bash
# 1. Registrar
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Test","correo":"test@example.com","password":"test123"}'

# 2. Login (guarda el token que devuelve)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"correo":"test@example.com","password":"test123"}'

# 3. Ver perfil (reemplaza YOUR_TOKEN con el token real)
curl -X GET http://localhost:8000/api/perfil \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📖 Documentación completa

Lee estos archivos para más información:

- **RESUMEN.md** - Resumen del sistema completo
- **README_AUTH.md** - Documentación detallada de la API
- **EJEMPLOS_FRONTEND.md** - Integración con JavaScript, React, Vue
- **COMANDOS.md** - Todos los comandos útiles

---

## ❓ Problemas comunes

### "No route found"
```bash
php bin/console cache:clear
```

### "Table doesn't exist"
```bash
php bin/console doctrine:schema:update --force
```

### "Access denied"
Verifica que estés enviando el token en la cabecera:
```
Authorization: Bearer {tu_token}
```

---

## 🎉 ¡Eso es todo!

Ya tienes un sistema de autenticación completo funcionando.

**Siguiente paso:** Lee README_AUTH.md para entender todos los endpoints disponibles.
