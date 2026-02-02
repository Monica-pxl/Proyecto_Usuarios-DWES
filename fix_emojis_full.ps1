$file = "c:\xampp\htdocs\Proyecto_Usuarios\templates\home\api_docs.html.twig"

# Leer el archivo como bytes
$bytes = [System.IO.File]::ReadAllBytes($file)

# Crear el nuevo contenido byte por byte, reemplazando las secuencias corruptas
$newBytes = New-Object System.Collections.ArrayList

for ($i = 0; $i -lt $bytes.Length; $i++) {
    # 🔐 (lock) - Bytes corruptos: C3 B0 C5 B8 94 94 -> Bytes correctos: F0 9F 94 90
    if ($i -lt ($bytes.Length - 5) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x94 -and $bytes[$i+5] -eq 0x94) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x94)
        [void]$newBytes.Add(0x90)
        $i += 5
        continue
    }

    # 👥 (people) - Bytes corruptos: C3 B0 C5 B8 91 C2 A5 -> Bytes correctos: F0 9F 91 A5
    if ($i -lt ($bytes.Length - 6) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x91 -and $bytes[$i+5] -eq 0xC2 -and $bytes[$i+6] -eq 0xA5) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x91)
        [void]$newBytes.Add(0xA5)
        $i += 6
        continue
    }

    # 💬 (speech balloon) - Bytes corruptos: C3 B0 C5 B8 92 C2 AC -> Bytes correctos: F0 9F 92 AC
    if ($i -lt ($bytes.Length - 6) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x92 -and $bytes[$i+5] -eq 0xC2 -and $bytes[$i+6] -eq 0xAC) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x92)
        [void]$newBytes.Add(0xAC)
        $i += 6
        continue
    }

    # 🔒 (lock) - Bytes corruptos: C3 B0 C5 B8 94 91 -> Bytes correctos: F0 9F 94 92
    if ($i -lt ($bytes.Length - 5) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x94 -and $bytes[$i+5] -eq 0x91) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x94)
        [void]$newBytes.Add(0x92)
        $i += 5
        continue
    }

    # ✉️ (envelope) - Bytes corruptos: C3 A2 C5 93 C5 A1 C3 AF C2 B8 C2 8F -> Bytes correctos: E2 9C 89 EF B8 8F
    if ($i -lt ($bytes.Length - 11) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xA2 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0x93 -and
        $bytes[$i+4] -eq 0xC5 -and $bytes[$i+5] -eq 0xA1 -and
        $bytes[$i+6] -eq 0xC3 -and $bytes[$i+7] -eq 0xAF -and
        $bytes[$i+8] -eq 0xC2 -and $bytes[$i+9] -eq 0xB8 -and
        $bytes[$i+10] -eq 0xC2 -and $bytes[$i+11] -eq 0x8F) {
        [void]$newBytes.Add(0xE2)
        [void]$newBytes.Add(0x9C)
        [void]$newBytes.Add(0x89)
        [void]$newBytes.Add(0xEF)
        [void]$newBytes.Add(0xB8)
        [void]$newBytes.Add(0x8F)
        $i += 11
        continue
    }

    # 🔍 (magnifying glass) - Bytes corruptos: C3 B0 C5 B8 94 C2 8D -> Bytes correctos: F0 9F 94 8D
    if ($i -lt ($bytes.Length - 6) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x94 -and $bytes[$i+5] -eq 0xC2 -and $bytes[$i+6] -eq 0x8D) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x94)
        [void]$newBytes.Add(0x8D)
        $i += 6
        continue
    }

    # 📋 (clipboard) - Bytes corruptos: C3 B0 C5 B8 93 C5 A0 -> Bytes correctos: F0 9F 93 8B
    if ($i -lt ($bytes.Length - 6) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x93 -and $bytes[$i+5] -eq 0xC5 -and $bytes[$i+6] -eq 0xA0) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x93)
        [void]$newBytes.Add(0x8B)
        $i += 6
        continue
    }

    # 📝 (memo) - Bytes corruptos: C3 B0 C5 B8 93 C2 9D -> Bytes correctos: F0 9F 93 9D
    if ($i -lt ($bytes.Length - 6) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xB0 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xB8 -and
        $bytes[$i+4] -eq 0x93 -and $bytes[$i+5] -eq 0xC2 -and $bytes[$i+6] -eq 0x9D) {
        [void]$newBytes.Add(0xF0)
        [void]$newBytes.Add(0x9F)
        [void]$newBytes.Add(0x93)
        [void]$newBytes.Add(0x9D)
        $i += 6
        continue
    }

    # ← (left arrow) - Bytes corruptos: C3 A2 C2 86 C2 90 -> Bytes correctos: E2 86 90
    if ($i -lt ($bytes.Length - 5) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xA2 -and
        $bytes[$i+2] -eq 0xC2 -and $bytes[$i+3] -eq 0x86 -and
        $bytes[$i+4] -eq 0xC2 -and $bytes[$i+5] -eq 0x90) {
        [void]$newBytes.Add(0xE2)
        [void]$newBytes.Add(0x86)
        [void]$newBytes.Add(0x90)
        $i += 5
        continue
    }

    # ↑ (up arrow) - Bytes corruptos: C3 A2 C2 86 C2 91 -> Bytes correctos: E2 86 91
    if ($i -lt ($bytes.Length - 5) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xA2 -and
        $bytes[$i+2] -eq 0xC2 -and $bytes[$i+3] -eq 0x86 -and
        $bytes[$i+4] -eq 0xC2 -and $bytes[$i+5] -eq 0x91) {
        [void]$newBytes.Add(0xE2)
        [void]$newBytes.Add(0x86)
        [void]$newBytes.Add(0x91)
        $i += 5
        continue
    }

    # ⚠️ (warning) - Bytes corruptos: C3 A2 C5 A1 C2 A0 C3 AF C2 B8 C2 8F -> Bytes correctos: E2 9A A0 EF B8 8F
    if ($i -lt ($bytes.Length - 11) -and
        $bytes[$i] -eq 0xC3 -and $bytes[$i+1] -eq 0xA2 -and
        $bytes[$i+2] -eq 0xC5 -and $bytes[$i+3] -eq 0xA1 -and
        $bytes[$i+4] -eq 0xC2 -and $bytes[$i+5] -eq 0xA0 -and
        $bytes[$i+6] -eq 0xC3 -and $bytes[$i+7] -eq 0xAF -and
        $bytes[$i+8] -eq 0xC2 -and $bytes[$i+9] -eq 0xB8 -and
        $bytes[$i+10] -eq 0xC2 -and $bytes[$i+11] -eq 0x8F) {
        [void]$newBytes.Add(0xE2)
        [void]$newBytes.Add(0x9A)
        [void]$newBytes.Add(0xA0)
        [void]$newBytes.Add(0xEF)
        [void]$newBytes.Add(0xB8)
        [void]$newBytes.Add(0x8F)
        $i += 11
        continue
    }

    # Si no coincide con ningún patrón, mantener el byte original
    [void]$newBytes.Add($bytes[$i])
}

# Guardar el archivo con los bytes corregidos
[System.IO.File]::WriteAllBytes($file, $newBytes.ToArray())

Write-Host "Emojis corregidos exitosamente!" -ForegroundColor Green
