# Test de Logout con Eliminación de Conversaciones
# Ejecutar: .\test_logout.ps1

Write-Host "=== TEST DE LOGOUT CON ELIMINACIÓN DE CONVERSACIONES ===" -ForegroundColor Cyan
Write-Host ""

# 1. Login del Usuario 1
Write-Host "1. Login Usuario 1 (Admin)..." -ForegroundColor Yellow
try {
    $loginBody = @{
        correo = "admin@proyecto.com"
        password = "admin123"
    } | ConvertTo-Json

    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/login" `
        -Method POST `
        -ContentType "application/json" `
        -Body $loginBody

    $token1 = $loginResponse.token
    $user1Id = $loginResponse.usuario.id
    Write-Host "✅ Login exitoso - Usuario ID: $user1Id" -ForegroundColor Green
    Write-Host ""
} catch {
    Write-Host "❌ Error en login: $_" -ForegroundColor Red
    exit
}

# 2. Crear una sala privada
Write-Host "2. Creando sala privada con usuario ID 2..." -ForegroundColor Yellow
try {
    $headers = @{
        Authorization = "Bearer $token1"
        "Content-Type" = "application/json"
    }
    
    $salaBody = @{
        usuarioId = 2
    } | ConvertTo-Json
    
    $crearSala = Invoke-RestMethod -Uri "http://localhost:8000/api/sala-privada/crear" `
        -Method POST `
        -Headers $headers `
        -Body $salaBody
    
    if ($crearSala.success) {
        Write-Host "✅ Sala privada creada" -ForegroundColor Green
        Write-Host "ID de la sala: $($crearSala.invitacion.id)" -ForegroundColor Gray
    }
    Write-Host ""
} catch {
    $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "⚠️  $($errorResponse.error)" -ForegroundColor Yellow
    Write-Host ""
}

# 3. Verificar salas privadas antes del logout
Write-Host "3. Verificando salas privadas antes del logout..." -ForegroundColor Yellow
try {
    $salasAntes = Invoke-RestMethod -Uri "http://localhost:8000/api/sala-privada" `
        -Method GET `
        -Headers @{Authorization = "Bearer $token1"}
    
    $cantidadAntes = $salasAntes.salas.Count
    Write-Host "✅ Salas privadas encontradas: $cantidadAntes" -ForegroundColor Green
    
    if ($cantidadAntes -gt 0) {
        foreach ($sala in $salasAntes.salas) {
            Write-Host "  - $($sala.nombre) (ID: $($sala.id))" -ForegroundColor Gray
        }
    }
    Write-Host ""
} catch {
    Write-Host "⚠️  Error al listar salas" -ForegroundColor Yellow
    Write-Host ""
}

# 4. Hacer logout (esto debe eliminar las conversaciones)
Write-Host "4. Cerrando sesión (eliminará las conversaciones)..." -ForegroundColor Yellow
try {
    $logoutResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/logout" `
        -Method POST `
        -Headers @{Authorization = "Bearer $token1"}
    
    if ($logoutResponse.success) {
        Write-Host "✅ Logout exitoso" -ForegroundColor Green
        Write-Host "Mensaje: $($logoutResponse.message)" -ForegroundColor Gray
        Write-Host "Salas privadas eliminadas: $($logoutResponse.salasPrivadasEliminadas)" -ForegroundColor Cyan
    }
    Write-Host ""
} catch {
    Write-Host "❌ Error al hacer logout: $_" -ForegroundColor Red
    Write-Host ""
}

# 5. Intentar verificar salas después del logout (debe dar error de autenticación)
Write-Host "5. Verificando que el token fue invalidado..." -ForegroundColor Yellow
try {
    $salasDespues = Invoke-RestMethod -Uri "http://localhost:8000/api/sala-privada" `
        -Method GET `
        -Headers @{Authorization = "Bearer $token1"}
    
    Write-Host "⚠️  El token aún funciona (no debería)" -ForegroundColor Yellow
    Write-Host ""
} catch {
    Write-Host "✅ Correcto: Token invalidado (error 401 esperado)" -ForegroundColor Green
    Write-Host ""
}

Write-Host "=== PRUEBA COMPLETADA ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Resumen:" -ForegroundColor Yellow
Write-Host "• Al cerrar sesión, todas las conversaciones privadas del usuario se eliminan automáticamente" -ForegroundColor White
Write-Host "• Esto afecta a ambos usuarios de la conversación" -ForegroundColor White
Write-Host "• El token de autenticación se invalida" -ForegroundColor White
Write-Host "• La ubicación y datos de sesión se limpian" -ForegroundColor White
Write-Host ""
