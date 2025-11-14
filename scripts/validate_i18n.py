#!/usr/bin/env python3
"""
i18n Validation Script - Validate internationalization implementation

This script validates the i18n migration by checking:
- Language files exist and are valid PHP
- All translation keys in inventory exist in language files
- Migrated templates have valid Mustache syntax
- No missing or duplicate keys
- All i18n function calls have valid syntax

Usage:
    python3 validate_i18n.py

Author: Claude (Anthropic)
Date: 2025-11-14
"""

import json
import re
import sys
from pathlib import Path
from typing import Dict, List, Set, Tuple
from collections import defaultdict


class I18nValidator:
    """Validates i18n implementation"""

    def __init__(self, inventory_path: str = 'i18n_strings_inventory.json'):
        """Initialize validator"""
        self.inventory_path = inventory_path
        self.inventory = self._load_inventory()
        self.errors = []
        self.warnings = []
        self.stats = {
            'total_keys': 0,
            'missing_keys': 0,
            'duplicate_keys': 0,
            'valid_templates': 0,
            'invalid_templates': 0,
            'language_files_checked': 0,
        }

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

    def _parse_php_array(self, content: str) -> Dict[str, str]:
        """
        Parse a simple PHP return array to extract key-value pairs
        This is a basic parser for our language files
        """
        translations = {}

        # Match pattern: 'key' => 'value',
        pattern = r"'([^']+)'\s*=>\s*'([^']*(?:\\'[^']*)*)',?"

        matches = re.finditer(pattern, content)
        for match in matches:
            key = match.group(1)
            value = match.group(2)
            translations[key] = value

        return translations

    def validate_language_files(self, locale: str = 'en') -> Dict[str, Dict[str, str]]:
        """
        Validate that all language files exist and can be loaded
        Returns: Dictionary of category -> translations
        """
        print(f"\n🔍 Validating language files for locale: {locale}")

        lang_dir = Path(f'resources/lang/{locale}')
        if not lang_dir.exists():
            self.errors.append(f"Language directory not found: {lang_dir}")
            return {}

        categories = ['admin', 'forms', 'messages', 'help', 'uncategorized']
        all_translations = {}

        for category in categories:
            file_path = lang_dir / f'{category}.php'

            if not file_path.exists():
                self.errors.append(f"Language file not found: {file_path}")
                continue

            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()

                # Basic PHP syntax validation
                if 'return [' not in content:
                    self.errors.append(f"Invalid PHP array format in {file_path}")
                    continue

                # Parse translations
                translations = self._parse_php_array(content)
                all_translations[category] = translations

                self.stats['language_files_checked'] += 1
                print(f"   ✓ {category}.php: {len(translations)} translations loaded")

            except Exception as e:
                self.errors.append(f"Error reading {file_path}: {str(e)}")

        return all_translations

    def validate_translation_keys(self, locale: str = 'en') -> List[str]:
        """
        Validate that all keys in inventory exist in language files
        Returns: List of missing keys
        """
        print(f"\n🔍 Validating translation keys...")

        # Load all translations
        all_translations = self.validate_language_files(locale)

        # Get all expected keys from inventory
        expected_keys = set(self.inventory.get('translation_keys', {}).keys())
        self.stats['total_keys'] = len(expected_keys)

        # Check each key exists
        missing_keys = []
        for full_key in expected_keys:
            # Split key into category.key_name
            parts = full_key.split('.', 1)
            if len(parts) != 2:
                self.warnings.append(f"Invalid key format: {full_key}")
                continue

            category, key_name = parts

            if category not in all_translations:
                missing_keys.append(full_key)
                continue

            if key_name not in all_translations[category]:
                missing_keys.append(full_key)

        self.stats['missing_keys'] = len(missing_keys)

        if missing_keys:
            print(f"   ✗ Found {len(missing_keys)} missing translation keys")
            for key in missing_keys[:10]:  # Show first 10
                print(f"      - {key}")
            if len(missing_keys) > 10:
                print(f"      ... and {len(missing_keys) - 10} more")
        else:
            print(f"   ✓ All {len(expected_keys)} translation keys found")

        return missing_keys

    def validate_duplicate_keys(self, locale: str = 'en') -> Dict[str, List[str]]:
        """
        Check for duplicate keys in language files
        Returns: Dictionary of duplicate key -> list of categories
        """
        print(f"\n🔍 Checking for duplicate keys...")

        all_translations = self.validate_language_files(locale)

        # Track which categories each key appears in
        key_locations = defaultdict(list)

        for category, translations in all_translations.items():
            for key in translations.keys():
                key_locations[key].append(category)

        # Find duplicates (keys that appear in multiple categories)
        duplicates = {key: cats for key, cats in key_locations.items() if len(cats) > 1}

        self.stats['duplicate_keys'] = len(duplicates)

        if duplicates:
            print(f"   ⚠️  Found {len(duplicates)} duplicate keys across categories")
            for key, categories in list(duplicates.items())[:10]:
                print(f"      - '{key}' in: {', '.join(categories)}")
            if len(duplicates) > 10:
                print(f"      ... and {len(duplicates) - 10} more")
        else:
            print(f"   ✓ No duplicate keys found")

        return duplicates

    def validate_template_syntax(self, template_path: Path) -> Tuple[bool, List[str]]:
        """
        Validate Mustache syntax in a template file
        Returns: (is_valid, list_of_errors)
        """
        errors = []

        try:
            with open(template_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # Check for balanced Mustache tags
            open_tags = content.count('{{')
            close_tags = content.count('}}')
            if open_tags != close_tags:
                errors.append(f"Unbalanced Mustache tags: {open_tags} {{ vs {close_tags} }}")

            # Check for i18n function calls
            i18n_pattern = r'\{\{#__\}\}(.+?)\{\{/__\}\}'
            i18n_calls = re.findall(i18n_pattern, content)

            # Validate each i18n key format
            for i18n_key in i18n_calls:
                # Should be category.key_name format
                if '.' not in i18n_key:
                    errors.append(f"Invalid i18n key format (missing category): {i18n_key}")

                # Check if key exists in inventory
                if i18n_key not in self.inventory.get('translation_keys', {}):
                    errors.append(f"Unknown i18n key: {i18n_key}")

            # Check for unclosed sections
            section_opens = re.findall(r'\{\{#(\w+)\}\}', content)
            section_closes = re.findall(r'\{\{/(\w+)\}\}', content)

            # Build a simple stack to check matching
            open_stack = []
            all_tags = re.findall(r'\{\{([#/])(\w+)\}\}', content)

            for tag_type, tag_name in all_tags:
                if tag_type == '#':
                    open_stack.append(tag_name)
                elif tag_type == '/':
                    if not open_stack:
                        errors.append(f"Closing tag without opening: {tag_name}")
                    elif open_stack[-1] == tag_name:
                        open_stack.pop()
                    else:
                        errors.append(f"Mismatched tags: expected {open_stack[-1]}, got {tag_name}")

            if open_stack:
                errors.append(f"Unclosed sections: {', '.join(open_stack)}")

        except Exception as e:
            errors.append(f"Error reading template: {str(e)}")

        return len(errors) == 0, errors

    def validate_migrated_templates(self) -> Dict[str, List[str]]:
        """
        Validate all migrated templates
        Returns: Dictionary of template -> list of errors
        """
        print(f"\n🔍 Validating migrated template syntax...")

        # Get list of migrated templates from inventory
        migrated_files = set()
        for key, data in self.inventory.get('translation_keys', {}).items():
            source_file = data.get('source_file', '')
            if source_file:
                migrated_files.add(Path(source_file))

        invalid_templates = {}

        for template_path in sorted(migrated_files):
            if not template_path.exists():
                self.warnings.append(f"Template file not found: {template_path}")
                continue

            is_valid, errors = self.validate_template_syntax(template_path)

            if is_valid:
                self.stats['valid_templates'] += 1
            else:
                self.stats['invalid_templates'] += 1
                invalid_templates[str(template_path)] = errors

        if invalid_templates:
            print(f"   ✗ Found {len(invalid_templates)} templates with errors:")
            for template, errors in list(invalid_templates.items())[:5]:
                print(f"\n      {template}:")
                for error in errors[:3]:
                    print(f"         - {error}")
            if len(invalid_templates) > 5:
                print(f"\n      ... and {len(invalid_templates) - 5} more templates with errors")
        else:
            print(f"   ✓ All {self.stats['valid_templates']} templates have valid syntax")

        return invalid_templates

    def validate_i18n_helper_exists(self) -> bool:
        """Check that the __() helper function exists"""
        print(f"\n🔍 Checking i18n helper function...")

        # Check if Translator class exists
        translator_path = Path('core/I18n/Translator.php')

        if not translator_path.exists():
            self.errors.append(f"Translator class not found: {translator_path}")
            print(f"   ✗ Translator class not found")
            return False

        try:
            with open(translator_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # Check for translate method
            if 'function translate(' not in content and 'public function translate(' not in content:
                self.errors.append("Translator::translate() method not found")
                print(f"   ✗ translate() method not found in Translator class")
                return False

            print(f"   ✓ Translator class found at {translator_path}")
            return True

        except Exception as e:
            self.errors.append(f"Error reading Translator class: {str(e)}")
            print(f"   ✗ Error reading Translator class")
            return False

    def generate_report(self, output_path: str = 'I18N_VALIDATION_REPORT.md') -> None:
        """Generate validation report"""

        # Run all validations
        print("\n" + "="*70)
        print("i18n VALIDATION REPORT")
        print("="*70)

        self.validate_i18n_helper_exists()
        missing_keys = self.validate_translation_keys('en')
        duplicates = self.validate_duplicate_keys('en')
        invalid_templates = self.validate_migrated_templates()

        # Generate markdown report
        report = f"""# i18n Validation Report

**Date:** {self._get_timestamp()}
**Inventory:** {self.inventory_path}

## Summary

### Overall Status: {"✅ PASSED" if not self.errors else "❌ FAILED"}

**Statistics:**
- Total Translation Keys: {self.stats['total_keys']}
- Missing Keys: {self.stats['missing_keys']}
- Duplicate Keys: {self.stats['duplicate_keys']}
- Valid Templates: {self.stats['valid_templates']}
- Invalid Templates: {self.stats['invalid_templates']}
- Language Files Checked: {self.stats['language_files_checked']}

---

## Validation Results

### ✅ Translation Keys ({self.stats['total_keys']} total)

"""

        if missing_keys:
            report += f"**Status:** ❌ FAILED - {len(missing_keys)} missing keys\n\n"
            report += "**Missing Keys:**\n"
            for key in missing_keys[:20]:
                report += f"- `{key}`\n"
            if len(missing_keys) > 20:
                report += f"\n... and {len(missing_keys) - 20} more\n"
        else:
            report += f"**Status:** ✅ PASSED - All keys present\n\n"

        report += "\n---\n\n"

        # Duplicate keys section
        report += f"### {'⚠️' if duplicates else '✅'} Duplicate Keys\n\n"

        if duplicates:
            report += f"**Status:** ⚠️ WARNING - {len(duplicates)} duplicate keys found\n\n"
            report += "**Note:** Duplicate keys across categories may be intentional (e.g., 'description' in multiple contexts)\n\n"
            report += "**Duplicates:**\n"
            for key, categories in list(duplicates.items())[:20]:
                report += f"- `{key}` in: {', '.join(categories)}\n"
            if len(duplicates) > 20:
                report += f"\n... and {len(duplicates) - 20} more\n"
        else:
            report += "**Status:** ✅ PASSED - No duplicate keys\n\n"

        report += "\n---\n\n"

        # Template syntax section
        report += f"### {'✅' if not invalid_templates else '❌'} Template Syntax\n\n"

        if invalid_templates:
            report += f"**Status:** ❌ FAILED - {len(invalid_templates)} templates with errors\n\n"
            for template, errors in invalid_templates.items():
                report += f"**{template}:**\n"
                for error in errors:
                    report += f"- {error}\n"
                report += "\n"
        else:
            report += f"**Status:** ✅ PASSED - All {self.stats['valid_templates']} templates valid\n\n"

        report += "\n---\n\n"

        # Errors section
        if self.errors:
            report += "## ❌ Errors\n\n"
            for error in self.errors:
                report += f"- {error}\n"
            report += "\n---\n\n"

        # Warnings section
        if self.warnings:
            report += "## ⚠️ Warnings\n\n"
            for warning in self.warnings:
                report += f"- {warning}\n"
            report += "\n---\n\n"

        # Recommendations
        report += """## Recommendations

### Next Steps

"""

        if self.errors:
            report += "1. **Fix Critical Errors:** Address all errors listed above\n"
            report += "2. **Re-run Validation:** Run this script again after fixes\n"
        else:
            report += "1. **Manual Testing:** Test i18n in browser with language switching\n"
            report += "2. **Visual QA:** Verify all translated strings display correctly\n"
            report += "3. **Functional Testing:** Test all forms, buttons, and interactions\n"

        if self.warnings:
            report += f"\n**Review {len(self.warnings)} warnings** before deployment\n"

        report += """

### Testing Checklist

- [ ] Load admin panel in Spanish
- [ ] Load admin panel in English
- [ ] Switch languages using language selector
- [ ] Verify all forms display correctly
- [ ] Check browser console for errors
- [ ] Test CRUD operations (Create, Read, Update, Delete)
- [ ] Verify modal dialogs and alerts
- [ ] Test navigation menus
- [ ] Check error messages

---

**Generated by:** scripts/validate_i18n.py
**Status:** {"✅ Validation Passed" if not self.errors else "❌ Validation Failed"}
"""

        # Write report
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(report)

        print(f"\n📄 Validation report generated: {output_path}")

    def _get_timestamp(self) -> str:
        """Get current timestamp"""
        from datetime import datetime
        return datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    def run_validation(self) -> int:
        """
        Run all validations and return exit code
        Returns: 0 if passed, 1 if failed
        """
        self.generate_report()

        # Print summary
        print("\n" + "="*70)
        print("VALIDATION SUMMARY")
        print("="*70)
        print(f"Translation Keys:    {'✅ PASSED' if self.stats['missing_keys'] == 0 else '❌ FAILED'}")
        print(f"Template Syntax:     {'✅ PASSED' if self.stats['invalid_templates'] == 0 else '❌ FAILED'}")
        print(f"Errors:              {len(self.errors)}")
        print(f"Warnings:            {len(self.warnings)}")

        if self.errors:
            print("\n❌ VALIDATION FAILED")
            print("="*70)
            return 1
        else:
            print("\n✅ VALIDATION PASSED")
            print("="*70)
            return 0


def main():
    """Main entry point"""
    validator = I18nValidator()
    exit_code = validator.run_validation()
    sys.exit(exit_code)


if __name__ == '__main__':
    main()
