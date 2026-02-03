# Script para agregar los botones faltantes manualmente
# Ejecutar este script para completar las mejoras en api_docs.html.twig

$filePath = "templates\home\api_docs.html.twig"
$content = Get-Content -Path $filePath -Raw

# Arreglar el formato del botón de actualizar (remover `n literal)
$content = $content -replace "access-protected"">ðŸ""' Requiere Token</span>``n                    <button", "access-protected"">ðŸ""' Requiere Token</span>`r`n                    <button"

# Agregar botón a /api/privado/cambiarchat
$content = $content -replace '(<span class="endpoint-path">/api/privado/cambiarchat</span>\s+<span class="access-badge access-protected">[^<]+</span>)\s+</div>', '$1`r`n                    <button class="test-button" onclick="toggleTestSection(''cambiarchat'')">🧪 Probar endpoint</button>`r`n                </div>'

# Agregar botón a /api/invitar
$content = $content -replace '(<span class="endpoint-path">/api/invitar</span>\s+<span class="access-badge access-protected">[^<]+</span>)\s+</div>', '$1`r`n                    <button class="test-button" onclick="toggleTestSection(''invitar'')">🧪 Probar endpoint</button>`r`n                </div>'

# Agregar sección de testing a /api/privado/salir
$sectionSalir = @"
                <!-- SECCIÓN DE TESTING -->
                <div id="test-salir" class="test-section">
                    <h4>🧪 Probar este endpoint</h4>
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">ID de la sala:</label>
                        <input type="number" class="test-input" name="sala_id" placeholder="5" value="5">
                    </div>
                    <button class="execute-button" onclick="testEndpoint('salir', 'POST', '/api/privado/salir')">🚀 Ejecutar</button>
                    <div class="test-output"></div>
                </div>
"@

# Buscar el cierre del endpoint /api/privado/salir y agregar la sección
$content = $content -replace '(<!-- POST /api/privado/salir -->.*?</div>\s+</div>)\s+(</section>)', ('$1' + "`r`n$sectionSalir`r`n                " + '$2')

# Guardar el archivo
$content | Set-Content -Path $filePath -Encoding UTF8

Write-Host "✅ Script ejecutado. Revisa el archivo api_docs.html.twig" -ForegroundColor Green
Write-Host ""
Write-Host "Cambios aplicados:" -ForegroundColor Yellow
Write-Host "1. Corregido formato del botón en /api/actualizar" -ForegroundColor Cyan
Write-Host "2. Agregado botón a /api/privado/cambiarchat" -ForegroundColor Cyan
Write-Host "3. Agregado botón a /api/invitar" -ForegroundColor Cyan
Write-Host "4. Agregada sección de testing a /api/privado/salir" -ForegroundColor Cyan
