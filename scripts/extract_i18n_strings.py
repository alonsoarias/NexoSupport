#!/usr/bin/env python3
"""
NexoSupport - i18n String Extraction Tool

Extracts hardcoded Spanish strings from Mustache templates
and generates a structured inventory for translation.

Usage: python3 extract_i18n_strings.py
Output: i18n_strings_inventory.json
"""

import os
import re
import json
from pathlib import Path
from datetime import datetime
from collections import defaultdict

# Base directory
BASE_DIR = Path('/home/user/NexoSupport')

# Template directories to scan (in priority order)
SCAN_PATHS = [
    ('admin', BASE_DIR / 'resources/views/admin', 'CRITICAL'),
    ('auth', BASE_DIR / 'resources/views/auth', 'HIGH'),
    ('dashboard', BASE_DIR / 'resources/views/dashboard', 'HIGH'),
    ('profile', BASE_DIR / 'resources/views/profile', 'MEDIUM'),
    ('admin_modules', BASE_DIR / 'modules/Admin/templates', 'HIGH'),
]

# Patterns to extract strings (Spanish text)
PATTERNS = [
    # HTML content between tags: <tag>Spanish Text</tag>
    (r'>([^<>{}\n]+[áéíóúñÁÉÍÓÚÑ][^<>{}]*)<', 'html_content'),

    # Attribute values: placeholder="Spanish Text"
    (r'placeholder=["\']([^"\']*[áéíóúñÁÉÍÓÚÑ][^"\']*)["\']', 'placeholder'),

    # Attribute values: title="Spanish Text"
    (r'title=["\']([^"\']*[áéíóúñÁÉÍÓÚÑ][^"\']*)["\']', 'title'),

    # Attribute values: alt="Spanish Text"
    (r'alt=["\']([^"\']*[áéíóúñÁÉÍÓÚÑ][^"\']*)["\']', 'alt'),

    # Attribute values: value="Spanish Text"
    (r'value=["\']([^"\']*[áéíóúñÁÉÍÓÚÑ][^"\']*)["\']', 'value'),

    # Data attributes: data-*="Spanish Text"
    (r'data-[a-z-]+=["\']([^"\']*[áéíóúñÁÉÍÓÚÑ][^"\']*)["\']', 'data_attr'),
]

# Words to ignore (common non-translatable content)
IGNORE_WORDS = {
    'id', 'class', 'div', 'span', 'href', 'src', 'type', 'name',
    'true', 'false', 'null', 'undefined', 'var', 'function',
    'return', 'if', 'else', 'for', 'while', '{{', '}}', '{#', '{/',
    'px', 'em', 'rem', '%', 'auto', 'none', 'block', 'inline',
}


def is_valid_spanish_text(text: str) -> bool:
    """Check if text is valid Spanish content worth translating."""
    # Remove whitespace
    text = text.strip()

    # Ignore empty strings
    if not text or len(text) < 2:
        return False

    # Ignore if it's just a number
    if text.isdigit():
        return False

    # Ignore common non-translatable words
    if text.lower() in IGNORE_WORDS:
        return False

    # Ignore if it looks like code/variable
    if text.startswith('{{') or text.startswith('{#') or text.startswith('{/'):
        return False

    # Ignore URLs
    if 'http://' in text or 'https://' in text:
        return False

    # Ignore if it's just punctuation
    if all(c in '.,;:!?¿¡-_()[]{}/<>"\'' for c in text):
        return False

    # Must contain at least one Spanish letter or common Spanish word
    spanish_indicators = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ']
    spanish_words = ['el', 'la', 'los', 'las', 'de', 'del', 'y', 'o', 'para', 'por',
                     'con', 'sin', 'en', 'usuario', 'contraseña', 'correo', 'nombre']

    has_spanish_char = any(char in text for char in spanish_indicators)
    has_spanish_word = any(f' {word} ' in f' {text.lower()} ' for word in spanish_words)

    return has_spanish_char or has_spanish_word


def extract_strings_from_file(file_path: Path) -> dict:
    """Extract all Spanish strings from a Mustache template file."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        print(f"  ERROR reading {file_path}: {e}")
        return {'error': str(e), 'strings': []}

    extracted = {
        'file': str(file_path.relative_to(BASE_DIR)),
        'strings': [],
        'line_count': content.count('\n') + 1,
    }

    # Apply each pattern
    all_matches = set()
    for pattern, pattern_type in PATTERNS:
        matches = re.findall(pattern, content, re.MULTILINE | re.DOTALL)
        for match in matches:
            # Clean up the match
            clean_match = match.strip()

            # Validate it's Spanish text
            if is_valid_spanish_text(clean_match):
                all_matches.add((clean_match, pattern_type))

    # Convert to list of dicts
    for text, source_type in sorted(all_matches):
        extracted['strings'].append({
            'text': text,
            'source': source_type,
            'length': len(text),
        })

    return extracted


def categorize_string(text: str, file_path: str) -> str:
    """Categorize a string based on its content and source file."""
    text_lower = text.lower()

    # Check file path for context
    if 'auth' in file_path or 'login' in file_path:
        return 'auth'
    elif 'admin' in file_path:
        if any(word in text_lower for word in ['error', 'éxito', 'advertencia', 'correcto', 'fallido']):
            return 'messages'
        elif any(word in text_lower for word in ['nombre', 'email', 'contraseña', 'descripción']):
            return 'forms'
        elif any(word in text_lower for word in ['ayuda', 'ejemplo', 'nota', 'sugerencia']):
            return 'help'
        return 'admin'
    elif 'dashboard' in file_path:
        return 'dashboard'

    # Check content patterns
    if any(word in text_lower for word in ['crear', 'editar', 'eliminar', 'guardar', 'cancelar', 'buscar']):
        return 'common'
    elif any(word in text_lower for word in ['menú', 'inicio', 'configuración', 'perfil', 'salir']):
        return 'navigation'
    elif text.endswith('?') or 'instrucciones' in text_lower:
        return 'help'

    return 'uncategorized'


def generate_translation_key(text: str, category: str, index: int) -> str:
    """Generate a consistent translation key for a string."""
    # Remove special characters and convert to snake_case
    key_base = re.sub(r'[^\w\s]', '', text.lower())
    key_base = re.sub(r'\s+', '_', key_base)

    # Limit length
    if len(key_base) > 40:
        key_base = key_base[:40]

    # Remove trailing underscores
    key_base = key_base.strip('_')

    # If empty, use generic key
    if not key_base:
        key_base = f'string_{index}'

    return f'{category}.{key_base}'


def main():
    """Main extraction process."""
    print("=" * 60)
    print("NexoSupport i18n String Extraction Tool")
    print("=" * 60)
    print()

    results = {
        'metadata': {
            'extraction_date': datetime.now().isoformat(),
            'base_directory': str(BASE_DIR),
            'total_files': 0,
            'total_strings': 0,
        },
        'by_category': defaultdict(list),
        'by_file': [],
        'translation_keys': {},
    }

    total_files = 0
    total_strings = 0

    # Scan each directory
    for category, path, priority in SCAN_PATHS:
        print(f"Scanning {category.upper()} ({priority} priority)")
        print(f"  Path: {path}")

        if not path.exists():
            print(f"  ⚠️  Directory not found, skipping")
            print()
            continue

        # Find all .mustache files
        mustache_files = list(path.rglob('*.mustache'))
        print(f"  Found {len(mustache_files)} Mustache files")

        category_strings = 0
        for file_path in mustache_files:
            print(f"    Processing: {file_path.name}...", end='')

            file_data = extract_strings_from_file(file_path)

            if 'error' in file_data:
                print(f" ERROR")
                continue

            strings_found = len(file_data['strings'])
            print(f" {strings_found} strings")

            # Add to results
            results['by_file'].append(file_data)
            total_files += 1
            total_strings += strings_found
            category_strings += strings_found

            # Categorize each string
            for string_data in file_data['strings']:
                str_category = categorize_string(
                    string_data['text'],
                    file_data['file']
                )
                results['by_category'][str_category].append({
                    'text': string_data['text'],
                    'file': file_data['file'],
                    'source': string_data['source'],
                })

        print(f"  ✓ {category.upper()}: {category_strings} strings from {len(mustache_files)} files")
        print()

    # Generate translation keys
    print("Generating translation keys...")
    key_index = 0
    for category, strings in results['by_category'].items():
        for i, string_data in enumerate(strings):
            key = generate_translation_key(string_data['text'], category, key_index)

            # Ensure uniqueness
            original_key = key
            counter = 1
            while key in results['translation_keys']:
                key = f"{original_key}_{counter}"
                counter += 1

            results['translation_keys'][key] = {
                'spanish': string_data['text'],
                'english': '',  # To be filled manually
                'category': category,
                'source_file': string_data['file'],
            }
            key_index += 1

    # Update metadata
    results['metadata']['total_files'] = total_files
    results['metadata']['total_strings'] = total_strings
    results['metadata']['unique_strings'] = len(results['translation_keys'])

    # Category summary
    results['category_summary'] = {
        category: len(strings)
        for category, strings in results['by_category'].items()
    }

    # Convert defaultdict to dict for JSON serialization
    results['by_category'] = dict(results['by_category'])

    # Save to JSON
    output_file = BASE_DIR / 'i18n_strings_inventory.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)

    print("=" * 60)
    print("EXTRACTION COMPLETE")
    print("=" * 60)
    print(f"Total files scanned: {total_files}")
    print(f"Total strings found: {total_strings}")
    print(f"Unique strings: {len(results['translation_keys'])}")
    print()
    print("Strings by category:")
    for category, count in sorted(results['category_summary'].items(),
                                   key=lambda x: x[1], reverse=True):
        print(f"  {category:15s}: {count:4d} strings")
    print()
    print(f"Results saved to: {output_file}")
    print()
    print("Next steps:")
    print("  1. Review i18n_strings_inventory.json")
    print("  2. Add English translations to translation_keys")
    print("  3. Update lang files (es/en)")
    print("  4. Migrate templates to use translation keys")


if __name__ == '__main__':
    main()
