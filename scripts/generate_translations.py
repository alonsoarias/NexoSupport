#!/usr/bin/env python3
"""
NexoSupport - Translation Generator

Generates English translations for extracted Spanish strings
and updates translation files.

Usage: python3 generate_translations.py
"""

import json
import re
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path('/home/user/NexoSupport')
INVENTORY_FILE = BASE_DIR / 'i18n_strings_inventory.json'
LANG_DIR = BASE_DIR / 'resources/lang'

# Common Spanish to English translations
TRANSLATION_DICT = {
    # Common words
    'configuración': 'configuration',
    'configurar': 'configure',
    'administración': 'administration',
    'administrador': 'administrator',
    'usuario': 'user',
    'usuarios': 'users',
    'contraseña': 'password',
    'correo': 'email',
    'nombre': 'name',
    'apellido': 'last name',
    'descripción': 'description',
    'crear': 'create',
    'editar': 'edit',
    'eliminar': 'delete',
    'guardar': 'save',
    'cancelar': 'cancel',
    'buscar': 'search',
    'nuevo': 'new',
    'nueva': 'new',
    'gestión': 'management',
    'sistema': 'system',
    'seguridad': 'security',
    'autenticación': 'authentication',
    'autorización': 'authorization',
    'permisos': 'permissions',
    'permiso': 'permission',
    'roles': 'roles',
    'rol': 'role',
    'inicio': 'home',
    'sesión': 'session',
    'duración': 'duration',
    'intentos': 'attempts',
    'máximo': 'maximum',
    'mínimo': 'minimum',
    'opciones': 'options',
    'avanzado': 'advanced',
    'básico': 'basic',
    'general': 'general',
    'información': 'information',
    'detalles': 'details',
    'estado': 'status',
    'activo': 'active',
    'inactivo': 'inactive',
    'habilitado': 'enabled',
    'deshabilitado': 'disabled',
    'sí': 'yes',
    'no': 'no',
    'aplicación': 'application',
    'versión': 'version',
    'fecha': 'date',
    'hora': 'time',
    'archivo': 'file',
    'archivos': 'files',
    'carpeta': 'folder',
    'directorio': 'directory',
    'base de datos': 'database',
    'tabla': 'table',
    'registro': 'record',
    'registros': 'records',
    'tema': 'theme',
    'temas': 'themes',
    'apariencia': 'appearance',
    'color': 'color',
    'colores': 'colors',
    'fuente': 'font',
    'texto': 'text',
    'imagen': 'image',
    'logo': 'logo',
    'icono': 'icon',
    'menú': 'menu',
    'navegación': 'navigation',
    'página': 'page',
    'sección': 'section',
    'módulo': 'module',
    'módulos': 'modules',
    'plugin': 'plugin',
    'plugins': 'plugins',
    'herramienta': 'tool',
    'herramientas': 'tools',
    'reportes': 'reports',
    'reporte': 'report',
    'registro de actividad': 'activity log',
    'auditoría': 'audit',
    'bloqueo': 'lockout',
    'fallido': 'failed',
    'exitoso': 'successful',
    'error': 'error',
    'advertencia': 'warning',
    'éxito': 'success',
    'mensaje': 'message',
    'notificación': 'notification',
    'ayuda': 'help',
    'documentación': 'documentation',
    'soporte': 'support',
    'contacto': 'contact',
    'idioma': 'language',
    'zona horaria': 'timezone',
    'formato': 'format',
    'predeterminado': 'default',
    'por defecto': 'default',
    'personalizado': 'custom',
    'personalizar': 'customize',
}


def translate_text(spanish_text: str) -> str:
    """Translate Spanish text to English using translation dictionary."""
    # Start with original text lowercased
    english = spanish_text.lower()

    # Replace known translations (whole words)
    for es, en in TRANSLATION_DICT.items():
        # Use word boundaries to avoid partial matches
        pattern = r'\b' + re.escape(es) + r'\b'
        english = re.sub(pattern, en, english, flags=re.IGNORECASE)

    # Capitalize first letter
    if english:
        english = english[0].upper() + english[1:]

    # Handle some common patterns
    english = english.replace(' de la ', ' of the ')
    english = english.replace(' de los ', ' of the ')
    english = english.replace(' del ', ' of the ')
    english = english.replace(' y ', ' and ')
    english = english.replace(' o ', ' or ')
    english = english.replace(' para ', ' for ')
    english = english.replace(' con ', ' with ')
    english = english.replace(' sin ', ' without ')
    english = english.replace(' en ', ' in ')

    return english


def generate_lang_file_content(translations: dict, category: str) -> str:
    """Generate PHP array content for a translation file."""
    lines = ["<?php\n", "\nreturn [\n"]

    # Sort translations by key
    sorted_keys = sorted(translations.keys())

    for key in sorted_keys:
        # Extract just the key part after the category
        if '.' in key:
            short_key = key.split('.', 1)[1]
        else:
            short_key = key

        value = translations[key]

        # Escape single quotes in the value
        value_escaped = value.replace("'", "\\'")

        lines.append(f"    '{short_key}' => '{value_escaped}',\n")

    lines.append("];\n")

    return ''.join(lines)


def main():
    """Main translation generation process."""
    print("=" * 60)
    print("NexoSupport Translation Generator")
    print("=" * 60)
    print()

    # Load inventory
    if not INVENTORY_FILE.exists():
        print(f"ERROR: Inventory file not found: {INVENTORY_FILE}")
        print("Run extract_i18n_strings.py first")
        return

    with open(INVENTORY_FILE, 'r', encoding='utf-8') as f:
        inventory = json.load(f)

    translation_keys = inventory['translation_keys']

    print(f"Loaded {len(translation_keys)} strings from inventory")
    print()

    # Group translations by category
    translations_by_category = defaultdict(lambda: {'es': {}, 'en': {}})

    for key, data in translation_keys.items():
        category = data['category']
        spanish = data['spanish']
        english = translate_text(spanish)

        translations_by_category[category]['es'][key] = spanish
        translations_by_category[category]['en'][key] = english

    print("Generated translations by category:")
    for category in sorted(translations_by_category.keys()):
        count = len(translations_by_category[category]['es'])
        print(f"  {category:15s}: {count:3d} strings")
    print()

    # Create/update language files
    print("Updating language files...")

    for category, langs in translations_by_category.items():
        # Spanish files
        es_file = LANG_DIR / 'es' / f'{category}.php'
        es_file.parent.mkdir(parents=True, exist_ok=True)

        # English files
        en_file = LANG_DIR / 'en' / f'{category}.php'
        en_file.parent.mkdir(parents=True, exist_ok=True)

        # Load existing files if they exist
        existing_es = {}
        existing_en = {}

        if es_file.exists():
            # Read existing translations (simplified - just extract array)
            with open(es_file, 'r', encoding='utf-8') as f:
                content = f.read()
                # Extract existing keys (simple regex)
                matches = re.findall(r"'([^']+)'\s*=>\s*'([^']*)'", content)
                for k, v in matches:
                    existing_es[f"{category}.{k}"] = v

        if en_file.exists():
            with open(en_file, 'r', encoding='utf-8') as f:
                content = f.read()
                matches = re.findall(r"'([^']+)'\s*=>\s*'([^']*)'", content)
                for k, v in matches:
                    existing_en[f"{category}.{k}"] = v

        # Merge existing with new (existing takes precedence)
        merged_es = {**langs['es'], **existing_es}
        merged_en = {**langs['en'], **existing_en}

        # Write Spanish file
        es_content = generate_lang_file_content(merged_es, category)
        with open(es_file, 'w', encoding='utf-8') as f:
            f.write(es_content)
        print(f"  ✓ Updated: {es_file.relative_to(BASE_DIR)}")

        # Write English file
        en_content = generate_lang_file_content(merged_en, category)
        with open(en_file, 'w', encoding='utf-8') as f:
            f.write(en_content)
        print(f"  ✓ Updated: {en_file.relative_to(BASE_DIR)}")

    print()
    print("=" * 60)
    print("TRANSLATION GENERATION COMPLETE")
    print("=" * 60)
    print()
    print("Language files updated:")
    for category in sorted(translations_by_category.keys()):
        print(f"  • {category}.php (es, en)")
    print()
    print("Next steps:")
    print("  1. Review generated English translations")
    print("  2. Manually correct any mistranslations")
    print("  3. Run template migration script")
    print("  4. Test both languages in the application")


if __name__ == '__main__':
    main()
