#!/usr/bin/env python3
"""
Template Migration Script - Replace hardcoded Spanish strings with i18n function calls

This script migrates Mustache templates from hardcoded Spanish strings to i18n keys.
It reads the i18n_strings_inventory.json file and replaces each Spanish string with
the corresponding {{#__}}category.key{{/__}} Mustache helper.

Usage:
    python3 migrate_templates.py [--dry-run] [--pilot] [--files file1.mustache file2.mustache]

Options:
    --dry-run: Show what would be changed without modifying files
    --pilot: Migrate only a small subset of files for testing (5-10 files)
    --files: Migrate specific files only

Author: Claude (Anthropic)
Date: 2025-11-14
"""

import json
import os
import re
import shutil
import sys
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Set


class TemplateMigrator:
    """Handles migration of Mustache templates to i18n"""

    def __init__(self, inventory_path: str = 'i18n_strings_inventory.json'):
        """Initialize the migrator with string inventory"""
        self.inventory_path = inventory_path
        self.inventory = self._load_inventory()
        self.translation_map = self._build_translation_map()
        self.backup_dir = Path('backups/templates_' + datetime.now().strftime('%Y%m%d_%H%M%S'))
        self.migration_log = []

    def _load_inventory(self) -> dict:
        """Load the string inventory JSON file"""
        try:
            with open(self.inventory_path, 'r', encoding='utf-8') as f:
                return json.load(f)
        except FileNotFoundError:
            print(f"Error: Inventory file {self.inventory_path} not found")
            sys.exit(1)
        except json.JSONDecodeError as e:
            print(f"Error: Invalid JSON in {self.inventory_path}: {e}")
            sys.exit(1)

    def _build_translation_map(self) -> Dict[str, str]:
        """
        Build a mapping from Spanish strings to i18n keys
        Returns: {spanish_text: 'category.key'}
        """
        translation_map = {}

        for key, data in self.inventory.get('translation_keys', {}).items():
            spanish_text = data.get('spanish', '')
            if spanish_text:
                translation_map[spanish_text] = key

        print(f"✓ Loaded {len(translation_map)} translation mappings")
        return translation_map

    def _escape_for_regex(self, text: str) -> str:
        """Escape special regex characters in the string"""
        # Escape all special regex characters
        special_chars = r'\.^$*+?{}[]|()'
        escaped = text
        for char in special_chars:
            escaped = escaped.replace(char, '\\' + char)
        return escaped

    def _create_backup(self, file_path: Path) -> None:
        """Create a backup of the original file"""
        self.backup_dir.mkdir(parents=True, exist_ok=True)

        # Preserve directory structure in backup
        # Convert to absolute path if needed
        abs_file_path = file_path.resolve() if not file_path.is_absolute() else file_path
        abs_cwd = Path.cwd().resolve()

        # Get relative path for backup structure
        try:
            relative_path = abs_file_path.relative_to(abs_cwd)
        except ValueError:
            # If file is not in cwd, use the file path as-is
            relative_path = Path(str(file_path).lstrip('/'))

        backup_path = self.backup_dir / relative_path
        backup_path.parent.mkdir(parents=True, exist_ok=True)

        shutil.copy2(file_path, backup_path)

    def _replace_in_context(self, content: str, spanish_text: str, i18n_key: str) -> Tuple[str, int]:
        """
        Replace Spanish text with i18n key, handling different contexts
        Returns: (modified_content, number_of_replacements)
        """
        replacements = 0
        escaped_text = self._escape_for_regex(spanish_text)

        # Context 1: HTML content (between tags)
        # Example: >Configuración del Sistema< -> >{{#__}}admin.configuración_del_sistema{{/__}}<
        pattern1 = f'(>){escaped_text}(<)'
        replacement1 = f'\\1{{{{#__}}}}{i18n_key}{{{{/__}}}}\\2'
        content, count1 = re.subn(pattern1, replacement1, content)
        replacements += count1

        # Context 2: HTML attributes (placeholder, title, value, etc.)
        # Example: placeholder="Buscar por nombre" -> placeholder="{{#__}}forms.buscar_por_nombre{{/__}}"
        attributes = ['placeholder', 'title', 'value', 'alt', 'aria-label', 'data-confirm']
        for attr in attributes:
            # Match: attribute="Spanish Text" or attribute='Spanish Text'
            pattern2 = f'({attr}=["\']){escaped_text}(["\'])'
            replacement2 = f'\\1{{{{#__}}}}{i18n_key}{{{{/__}}}}\\2'
            content, count2 = re.subn(pattern2, replacement2, content)
            replacements += count2

        # Context 3: Mustache variable output
        # Example: <h1>{{title}}: Configuración</h1> (only replace the hardcoded part)
        # We need to be careful not to replace inside {{}} blocks
        # Split content by mustache tags and only process text outside them
        parts = re.split(r'(\{\{[^}]+\}\})', content)
        for i, part in enumerate(parts):
            if not part.startswith('{{'):  # Only process non-mustache parts
                if spanish_text in part and '{{' not in part:
                    parts[i] = part.replace(spanish_text, f'{{{{#__}}}}{i18n_key}{{{{/__}}}}')
                    replacements += 1

        if replacements > 0:
            content = ''.join(parts)

        return content, replacements

    def migrate_file(self, file_path: Path, dry_run: bool = False) -> Dict:
        """
        Migrate a single template file
        Returns: Migration statistics for this file
        """
        stats = {
            'file': str(file_path),
            'replacements': 0,
            'strings_migrated': [],
            'errors': []
        }

        try:
            # Read original content
            with open(file_path, 'r', encoding='utf-8') as f:
                original_content = f.read()

            modified_content = original_content

            # Find all Spanish strings that appear in this file
            file_strings = []
            for spanish_text, i18n_key in self.translation_map.items():
                if spanish_text in original_content:
                    file_strings.append((spanish_text, i18n_key))

            # Sort by length (longest first) to handle overlapping strings
            file_strings.sort(key=lambda x: len(x[0]), reverse=True)

            # Replace each string
            for spanish_text, i18n_key in file_strings:
                modified_content, count = self._replace_in_context(
                    modified_content, spanish_text, i18n_key
                )
                if count > 0:
                    stats['replacements'] += count
                    stats['strings_migrated'].append({
                        'spanish': spanish_text,
                        'key': i18n_key,
                        'count': count
                    })

            # Only write if there were changes and not in dry-run mode
            if stats['replacements'] > 0 and not dry_run:
                # Create backup
                self._create_backup(file_path)

                # Write modified content
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(modified_content)

                print(f"✓ Migrated {file_path}: {stats['replacements']} replacements")
            elif stats['replacements'] > 0 and dry_run:
                print(f"[DRY-RUN] Would migrate {file_path}: {stats['replacements']} replacements")
            else:
                print(f"- Skipped {file_path}: No strings to migrate")

        except Exception as e:
            error_msg = f"Error processing {file_path}: {str(e)}"
            stats['errors'].append(error_msg)
            print(f"✗ {error_msg}")

        return stats

    def get_pilot_files(self) -> List[Path]:
        """
        Select 5-10 files for pilot migration
        Prioritizes files with the most strings to migrate
        """
        file_stats = {}

        # Count strings per file from translation_keys
        for key, data in self.inventory.get('translation_keys', {}).items():
            source_file = data.get('source_file', '')
            if source_file:
                if source_file not in file_stats:
                    file_stats[source_file] = 0
                file_stats[source_file] += 1

        # Convert to list and sort by string count (most strings first)
        file_list = [(Path(f), count) for f, count in file_stats.items() if Path(f).exists()]
        file_list.sort(key=lambda x: x[1], reverse=True)

        # Select top 8 files (good middle ground for pilot)
        pilot_files = [f[0] for f in file_list[:8]]

        print(f"\n📋 Selected {len(pilot_files)} pilot files:")
        for i, (file_path, count) in enumerate(file_list[:8], 1):
            print(f"   {i}. {file_path} ({count} strings)")
        print()

        return pilot_files

    def get_all_template_files(self) -> List[Path]:
        """Get all template files from inventory"""
        files_set = set()

        # Collect unique files from translation_keys
        for key, data in self.inventory.get('translation_keys', {}).items():
            source_file = data.get('source_file', '')
            if source_file:
                file_path = Path(source_file)
                if file_path.exists():
                    files_set.add(file_path)

        return list(files_set)

    def migrate_templates(self, files: List[Path], dry_run: bool = False) -> Dict:
        """
        Migrate multiple template files
        Returns: Overall migration statistics
        """
        overall_stats = {
            'total_files': len(files),
            'migrated_files': 0,
            'total_replacements': 0,
            'files_with_errors': 0,
            'file_details': []
        }

        print(f"\n🚀 Starting template migration...")
        print(f"   Mode: {'DRY-RUN' if dry_run else 'LIVE'}")
        print(f"   Files to process: {len(files)}")
        print(f"   Backup directory: {self.backup_dir}\n")

        for file_path in files:
            file_stats = self.migrate_file(file_path, dry_run)
            overall_stats['file_details'].append(file_stats)

            if file_stats['replacements'] > 0:
                overall_stats['migrated_files'] += 1
                overall_stats['total_replacements'] += file_stats['replacements']

            if file_stats['errors']:
                overall_stats['files_with_errors'] += 1

        return overall_stats

    def generate_report(self, stats: Dict, output_path: str = 'TEMPLATE_MIGRATION_REPORT.md') -> None:
        """Generate a detailed migration report in Markdown format"""
        report = f"""# Template Migration Report

**Date:** {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
**Inventory File:** {self.inventory_path}
**Backup Directory:** {self.backup_dir}

## Summary

- **Total Files Processed:** {stats['total_files']}
- **Files Migrated:** {stats['migrated_files']}
- **Files Skipped:** {stats['total_files'] - stats['migrated_files'] - stats['files_with_errors']}
- **Files with Errors:** {stats['files_with_errors']}
- **Total String Replacements:** {stats['total_replacements']}

## File Details

"""

        # Group files by status
        migrated = [f for f in stats['file_details'] if f['replacements'] > 0]
        skipped = [f for f in stats['file_details'] if f['replacements'] == 0 and not f['errors']]
        errors = [f for f in stats['file_details'] if f['errors']]

        # Migrated files
        if migrated:
            report += f"### ✅ Migrated Files ({len(migrated)})\n\n"
            for file_stats in migrated:
                report += f"#### {file_stats['file']}\n\n"
                report += f"**Replacements:** {file_stats['replacements']}\n\n"
                report += "**Strings Migrated:**\n\n"
                for string in file_stats['strings_migrated']:
                    report += f"- `{string['spanish']}` → `{{{{#__}}}}{string['key']}{{{{/__}}}}` ({string['count']}x)\n"
                report += "\n"

        # Skipped files
        if skipped:
            report += f"### ⏭️ Skipped Files ({len(skipped)})\n\n"
            report += "No strings to migrate in these files:\n\n"
            for file_stats in skipped:
                report += f"- {file_stats['file']}\n"
            report += "\n"

        # Files with errors
        if errors:
            report += f"### ❌ Files with Errors ({len(errors)})\n\n"
            for file_stats in errors:
                report += f"#### {file_stats['file']}\n\n"
                for error in file_stats['errors']:
                    report += f"- ⚠️ {error}\n"
                report += "\n"

        # Next steps
        report += """## Next Steps

1. **Review Migrated Files**: Check the migrated templates to ensure correct i18n key placement
2. **Test Functionality**: Load templates in browser with both Spanish and English locales
3. **Manual Adjustments**: Some complex templates may need manual i18n key adjustments
4. **Remove Backups**: After confirming migration success, remove backup directory
5. **Complete Remaining Files**: If this was a pilot, migrate remaining templates

## Rollback Instructions

If you need to rollback the migration:

```bash
# Restore from backups
cp -r {self.backup_dir}/* ./

# Or restore individual files
cp {self.backup_dir}/resources/views/admin/settings.mustache resources/views/admin/settings.mustache
```

## Migration Quality Checklist

- [ ] All Spanish strings replaced with i18n keys
- [ ] No broken HTML structure
- [ ] No broken Mustache syntax
- [ ] Attributes properly migrated (placeholder, title, etc.)
- [ ] Visual QA: Admin panel loads correctly
- [ ] Language switching works (ES ↔ EN)
- [ ] No console errors in browser
- [ ] All forms submit correctly
- [ ] All modals display correctly

---

*Generated by migrate_templates.py*
"""

        # Write report
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(report)

        print(f"\n📄 Migration report generated: {output_path}")


def main():
    """Main entry point"""
    import argparse

    parser = argparse.ArgumentParser(
        description='Migrate Mustache templates from hardcoded strings to i18n keys'
    )
    parser.add_argument('--dry-run', action='store_true',
                       help='Show what would be changed without modifying files')
    parser.add_argument('--pilot', action='store_true',
                       help='Migrate only a small subset of files for testing')
    parser.add_argument('--files', nargs='+',
                       help='Migrate specific files only')
    parser.add_argument('--inventory', default='i18n_strings_inventory.json',
                       help='Path to inventory JSON file (default: i18n_strings_inventory.json)')

    args = parser.parse_args()

    # Initialize migrator
    migrator = TemplateMigrator(args.inventory)

    # Determine which files to migrate
    if args.files:
        files = [Path(f) for f in args.files]
        print(f"📁 Migrating {len(files)} specified files")
    elif args.pilot:
        files = migrator.get_pilot_files()
    else:
        files = migrator.get_all_template_files()
        print(f"📁 Migrating all {len(files)} template files")

    # Perform migration
    stats = migrator.migrate_templates(files, dry_run=args.dry_run)

    # Generate report
    if not args.dry_run:
        migrator.generate_report(stats)

    # Print summary
    print("\n" + "="*60)
    print("MIGRATION SUMMARY")
    print("="*60)
    print(f"Files processed:     {stats['total_files']}")
    print(f"Files migrated:      {stats['migrated_files']}")
    print(f"Total replacements:  {stats['total_replacements']}")
    print(f"Files with errors:   {stats['files_with_errors']}")

    if args.dry_run:
        print("\n⚠️  DRY-RUN MODE: No files were modified")
    else:
        print(f"\n✅ Migration complete! Backups saved to: {migrator.backup_dir}")

    print("="*60 + "\n")


if __name__ == '__main__':
    main()
