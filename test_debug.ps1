# Test de depuración completo

$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$correo = "debug_$timestamp@test.com"

Write-Host "=== TEST DE REGISTRO ===" -ForegroundColor Cyan
Write-Host "URL: http://localhost/Proyecto_Usuarios-DWES/public/api/register" -ForegroundColor Yellow
Write-Host "Correo: $correo" -ForegroundColor Yellow

$body = @{
    nombre = "Usuario Debug"
    correo = $correo
    password = "test123"
} | ConvertTo-Json

Write-Host "`nJSON enviado:" -ForegroundColor Gray
Write-Host $body -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri "http://localhost/Proyecto_Usuarios-DWES/public/api/register" `
        -Method POST `
        -Body $body `
        -ContentType "application/json" `
        -UseBasicParsing
    
    Write-Host "`nStatus Code: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Response:" -ForegroundColor Green
    Write-Host $response.Content -ForegroundColor White
    
} catch {
    Write-Host "`nERROR DETECTADO:" -ForegroundColor Red
    Write-Host "Status Code: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Response Body:" -ForegroundColor Red
        Write-Host $responseBody -ForegroundColor White
    }
    
    Write-Host "`nDetalles del error:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor White
}
