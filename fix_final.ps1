$file = "c:\xampp\htdocs\Proyecto_Usuarios\templates\home\api_docs.html.twig"

# Leer el contenido
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Reemplazos de emojis corruptos
$replacements = @{
    'ðŸ"' = '🔍'
    'ðŸ"' = '🔐'
    'ðŸ'¥' = '👥'
    'ðŸ'¬' = '💬'
    'ðŸ"'' = '🔒'
    'âœ‰ï¸' = '✉️'
    'ðŸ"‹' = '📋'
    'ðŸ"' = '📝'
    'â†' = '←'
    'â†'' = '↑'
    'â€' = '⚠️'
}

foreach ($key in $replacements.Keys) {
    $content = $content.Replace($key, $replacements[$key])
}

# Guardar
[System.IO.File]::WriteAllText($file, $content, [System.Text.UTF8Encoding]::new($false))

Write-Host "Archivo corregido!" -ForegroundColor Green
