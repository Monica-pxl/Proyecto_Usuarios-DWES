#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import codecs

file_path = r"c:\xampp\htdocs\Proyecto_Usuarios\templates\home\api_docs.html.twig"

# Leer el archivo con encoding UTF-8
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Diccionario de reemplazos
replacements = {
    'ðŸ"': '🔍',   # magnifying glass
    'ðŸ"': '🔐',   # lock with key
    'ðŸ'¥': '👥',   # busts in silhouette
    'ðŸ'¬': '💬',   # speech balloon
    'ðŸ"'': '🔒',   # lock
    'âœ‰ï¸': '✉️',   # envelope
    'ðŸ"‹': '📋',   # clipboard
    'ðŸ"': '📝',   # memo
    'â†': '←',    # left arrow
    'â†'': '↑',    # up arrow
    'â€': '⚠️',   # warning sign
    'âœ"': '✓',   # check mark
    'ðŸ"š': '📚',   # books
}

# Realizar los reemplazos
for old, new in replacements.items():
    content = content.replace(old, new)

# Guardar el archivo con encoding UTF-8 sin BOM
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Archivo corregido exitosamente!")
