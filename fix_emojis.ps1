$file = "c:\xampp\htdocs\Proyecto_Usuarios\templates\home\api_docs.html.twig"

# Leer archivo original en ISO-8859-1 para ver los bytes reales
$content = Get-Content $file -Raw -Encoding UTF8

# Mapeo de emojis malformados a correctos
$replacements = @{
    'ðŸ"' = '🔐'
    'ðŸ'¥' = '👥'
    'ðŸ'¬' = '💬'
    'ðŸ"'' = '🔒'
    'âœ‰ï¸' = '✉️'
    'ðŸ"‹' = '📋'
    'ðŸ"' = '📝'
    'â€' = '⚠️'
    'â†' = '←'
    'â†'' = '↑'
}

foreach ($key in $replacements.Keys) {
    $content = $content -replace [regex]::Escape($key), $replacements[$key]
}

# Guardar con UTF-8 sin BOM
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($file, $content, $utf8NoBom)

Write-Host "Emojis corregidos!" -ForegroundColor Green
