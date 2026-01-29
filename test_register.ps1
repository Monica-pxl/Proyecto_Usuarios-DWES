# Test de registro de usuario

$url = "http://localhost/Proyecto_Usuarios-DWES/public/api/register"

# Generar un correo único con timestamp
$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$correo = "test_$timestamp@test.com"

$body = @{
    nombre = "Usuario Test"
    correo = $correo
    password = "password123"
    latitud = 39.5
    longitud = -0.5
} | ConvertTo-Json

Write-Host "Registrando usuario con correo: $correo" -ForegroundColor Cyan

try {
    $response = Invoke-RestMethod -Uri $url -Method POST -Body $body -ContentType "application/json"
    Write-Host "`nRegistro exitoso!" -ForegroundColor Green
    Write-Host "Token: $($response.token)" -ForegroundColor Yellow
    Write-Host "ID: $($response.data.id)" -ForegroundColor Yellow
    Write-Host "Nombre: $($response.data.user.nombre)" -ForegroundColor Yellow
    
    # Ahora probar login con las mismas credenciales
    Write-Host "`n--- Probando login con las mismas credenciales ---" -ForegroundColor Cyan
    
    $loginUrl = "http://localhost/Proyecto_Usuarios-DWES/public/api/login"
    $loginBody = @{
        correo = $correo
        password = "password123"
        latitud = 39.5
        longitud = -0.5
    } | ConvertTo-Json
    
    $loginResponse = Invoke-RestMethod -Uri $loginUrl -Method POST -Body $loginBody -ContentType "application/json"
    Write-Host "`nLogin exitoso!" -ForegroundColor Green
    Write-Host "Token: $($loginResponse.token)" -ForegroundColor Yellow
    
} catch {
    Write-Host "`nError:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host $_.ErrorDetails.Message -ForegroundColor Red
    }
}
