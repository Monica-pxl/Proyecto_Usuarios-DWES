# Script de prueba para el endpoint de registro

$baseUrl = "http://localhost/Proyecto_Usuarios/public"

Write-Host "=== Prueba de Registro ===" -ForegroundColor Cyan
Write-Host ""

# Datos del nuevo usuario
$userData = @{
    nombre = "Usuario Prueba"
    correo = "prueba_$(Get-Random)@example.com"
    password = "123456"
} | ConvertTo-Json

Write-Host "Enviando peticion de registro..." -ForegroundColor Yellow
Write-Host "Datos: $userData" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/register" `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
        } `
        -Body $userData `
        -UseBasicParsing

    Write-Host "Respuesta exitosa (Status: $($response.StatusCode))" -ForegroundColor Green
    Write-Host ""
    Write-Host "Respuesta del servidor:" -ForegroundColor Cyan
    $response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10
} catch {
    Write-Host "Error en la peticion" -ForegroundColor Red
    Write-Host "Status Code: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Respuesta del servidor:" -ForegroundColor Yellow

    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        $responseBody | ConvertFrom-Json | ConvertTo-Json -Depth 10
    }
}

Write-Host ""
Write-Host "=== Fin de la prueba ===" -ForegroundColor Cyan
