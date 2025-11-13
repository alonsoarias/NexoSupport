<?php
/**
 * TRANSLATION KEY GENERATOR - NexoSupport
 *
 * This script generates translation keys from hardcoded strings and creates
 * the necessary language files.
 *
 * Usage: php generate_translation_keys.php [input_csv] [lang_dir]
 *
 * @author NexoSupport Refactoring Initiative
 * @date 2025-11-13
 */

// Configuration
$inputCsv = $argv[1] ?? 'hardcoded_strings_report_detailed.csv';
$langDir = $argv[2] ?? '/home/user/NexoSupport/resources/lang';
$outputDir = __DIR__ . '/i18n_output';

// Colors for CLI output
class Color {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const NC = "\033[0m"; // No Color
}

echo Color::BLUE . "========================================\n" . Color::NC;
echo Color::BLUE . "  Translation Key Generator\n" . Color::NC;
echo Color::BLUE . "========================================\n" . Color::NC;
echo "\n";

// Create output directory
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Read CSV file
if (!file_exists($inputCsv)) {
    echo Color::RED . "Error: Input file not found: $inputCsv\n" . Color::NC;
    exit(1);
}

echo Color::YELLOW . "Reading CSV file: $inputCsv\n" . Color::NC;

$handle = fopen($inputCsv, 'r');
$header = fgetcsv($handle); // Skip header

$strings = [];
$categories = [];

while (($row = fgetcsv($handle)) !== false) {
    [$file, $line, $string, $context, $priority] = $row;

    // Determine category from file path
    $category = detectCategory($file);

    if (!isset($categories[$category])) {
        $categories[$category] = [];
    }

    // Generate translation key
    $key = generateKey($string, $category);

    $categories[$category][] = [
        'key' => $key,
        'string' => $string,
        'file' => $file,
        'line' => $line,
        'context' => $context,
        'priority' => $priority,
    ];

    $strings[] = [
        'category' => $category,
        'key' => $key,
        'string' => $string,
        'file' => $file,
        'line' => $line,
    ];
}

fclose($handle);

echo Color::GREEN . "✓ Found " . count($strings) . " strings in " . count($categories) . " categories\n" . Color::NC;
echo "\n";

// Generate language files for each category
echo Color::YELLOW . "Generating language files...\n" . Color::NC;

foreach ($categories as $category => $items) {
    generateLanguageFile($category, $items, $outputDir);
}

// Generate migration script
generateMigrationScript($strings, $outputDir);

// Generate summary report
generateSummaryReport($strings, $categories, $outputDir);

echo "\n";
echo Color::BLUE . "========================================\n" . Color::NC;
echo Color::GREEN . "✓ Generation complete!\n" . Color::NC;
echo Color::BLUE . "========================================\n" . Color::NC;
echo "\n";
echo "Files generated in: " . Color::YELLOW . "$outputDir\n" . Color::NC;
echo "\n";
echo "Next steps:\n";
echo "1. Review generated language files in $outputDir/lang/\n";
echo "2. Review migration script: $outputDir/migrate_templates.sh\n";
echo "3. Run migration script to update templates\n";
echo "4. Copy language files to /resources/lang/\n";
echo "\n";

################################################################################
# FUNCTIONS
################################################################################

/**
 * Detect category from file path
 */
function detectCategory(string $file): string
{
    if (strpos($file, 'admin') !== false) {
        if (strpos($file, 'users') !== false) return 'admin_users';
        if (strpos($file, 'roles') !== false) return 'admin_roles';
        if (strpos($file, 'permissions') !== false) return 'admin_permissions';
        if (strpos($file, 'settings') !== false) return 'admin_settings';
        if (strpos($file, 'plugins') !== false) return 'admin_plugins';
        if (strpos($file, 'backup') !== false) return 'admin_backup';
        if (strpos($file, 'logs') !== false) return 'admin_logs';
        if (strpos($file, 'audit') !== false) return 'admin_audit';
        if (strpos($file, 'email') !== false) return 'admin_email';
        return 'admin';
    }

    if (strpos($file, 'auth') !== false || strpos($file, 'login') !== false) {
        return 'auth';
    }

    if (strpos($file, 'dashboard') !== false) {
        return 'dashboard';
    }

    if (strpos($file, 'profile') !== false) {
        return 'profile';
    }

    if (strpos($file, 'theme') !== false || strpos($file, 'Theme') !== false) {
        return 'theme';
    }

    return 'common';
}

/**
 * Generate translation key from string
 */
function generateKey(string $string, string $category): string
{
    // Remove HTML tags
    $clean = strip_tags($string);

    // Convert to lowercase
    $clean = mb_strtolower($clean, 'UTF-8');

    // Replace Spanish characters
    $clean = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $clean
    );

    // Remove special characters
    $clean = preg_replace('/[^a-z0-9\s]/', '', $clean);

    // Replace spaces with underscores
    $clean = preg_replace('/\s+/', '_', trim($clean));

    // Limit length
    if (strlen($clean) > 50) {
        $words = explode('_', $clean);
        $clean = implode('_', array_slice($words, 0, 5));
    }

    return $clean;
}

/**
 * Generate language file for category
 */
function generateLanguageFile(string $category, array $items, string $outputDir): void
{
    $langDir = "$outputDir/lang";

    // Create directories
    if (!is_dir("$langDir/es")) mkdir("$langDir/es", 0755, true);
    if (!is_dir("$langDir/en")) mkdir("$langDir/en", 0755, true);

    // Generate Spanish file
    $esFile = "$langDir/es/$category.php";
    $esContent = generatePhpArray($items, 'es');
    file_put_contents($esFile, $esContent);

    // Generate English file (with TODO placeholders)
    $enFile = "$langDir/en/$category.php";
    $enContent = generatePhpArray($items, 'en');
    file_put_contents($enFile, $enContent);

    echo Color::GREEN . "  ✓ Generated $category.php (es, en)\n" . Color::NC;
}

/**
 * Generate PHP array content
 */
function generatePhpArray(array $items, string $locale): string
{
    $content = "<?php\n\n";
    $content .= "/**\n";
    $content .= " * " . strtoupper($locale) . " translations\n";
    $content .= " * \n";
    $content .= " * Auto-generated by i18n extraction tool\n";
    $content .= " * Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= " */\n\n";
    $content .= "return [\n";

    // Remove duplicates
    $seen = [];

    foreach ($items as $item) {
        $key = $item['key'];

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $string = $item['string'];

        // Add comment with context
        $content .= "    // File: {$item['file']}:{$item['line']}\n";

        if ($locale === 'es') {
            // Spanish - use original string
            $content .= "    '{$key}' => '" . addslashes($string) . "',\n\n";
        } else {
            // English - add TODO placeholder
            $content .= "    '{$key}' => 'TODO: Translate', // Original: " . addslashes($string) . "\n\n";
        }
    }

    $content .= "];\n";

    return $content;
}

/**
 * Generate migration script
 */
function generateMigrationScript(array $strings, string $outputDir): void
{
    $scriptFile = "$outputDir/migrate_templates.sh";

    $content = "#!/bin/bash\n\n";
    $content .= "# TEMPLATE MIGRATION SCRIPT\n";
    $content .= "# Auto-generated by i18n extraction tool\n";
    $content .= "# Date: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "set -e\n\n";
    $content .= "PROJECT_ROOT=\"/home/user/NexoSupport\"\n\n";
    $content .= "echo \"Starting template migration...\"\n\n";

    // Group by file
    $byFile = [];
    foreach ($strings as $item) {
        $file = $item['file'];
        if (!isset($byFile[$file])) {
            $byFile[$file] = [];
        }
        $byFile[$file][] = $item;
    }

    foreach ($byFile as $file => $items) {
        $content .= "# Migrate $file\n";
        $content .= "echo \"Migrating $file...\"\n";

        foreach ($items as $item) {
            $string = addslashes($item['string']);
            $key = $item['key'];
            $category = $item['category'];

            // sed command to replace hardcoded string with translation key
            $replacement = "{{#__}}{$category}.{$key}{{/__}}";

            $content .= "sed -i 's/" . preg_quote($string, '/') . "/$replacement/g' \"\$PROJECT_ROOT/$file\"\n";
        }

        $content .= "\n";
    }

    $content .= "echo \"Migration complete!\"\n";

    file_put_contents($scriptFile, $content);
    chmod($scriptFile, 0755);

    echo Color::GREEN . "  ✓ Generated migration script\n" . Color::NC;
}

/**
 * Generate summary report
 */
function generateSummaryReport(array $strings, array $categories, string $outputDir): void
{
    $reportFile = "$outputDir/TRANSLATION_SUMMARY.md";

    $content = "# i18n Translation Summary\n\n";
    $content .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "## Overview\n\n";
    $content .= "- **Total strings:** " . count($strings) . "\n";
    $content .= "- **Categories:** " . count($categories) . "\n\n";

    $content .= "## Breakdown by Category\n\n";
    $content .= "| Category | Strings | Language Files |\n";
    $content .= "|----------|---------|----------------|\n";

    foreach ($categories as $category => $items) {
        $count = count($items);
        $content .= "| $category | $count | es, en |\n";
    }

    $content .= "\n## Next Steps\n\n";
    $content .= "1. Review generated language files in `lang/`\n";
    $content .= "2. Translate English placeholders in `lang/en/*.php`\n";
    $content .= "3. Review migration script `migrate_templates.sh`\n";
    $content .= "4. Run migration script to update templates\n";
    $content .= "5. Copy language files to `/resources/lang/`\n";
    $content .= "6. Test with both locales\n\n";

    $content .= "## Files Generated\n\n";
    $content .= "```\n";
    $content .= "i18n_output/\n";
    $content .= "├── lang/\n";
    $content .= "│   ├── es/\n";

    foreach (array_keys($categories) as $category) {
        $content .= "│   │   ├── $category.php\n";
    }

    $content .= "│   └── en/\n";

    foreach (array_keys($categories) as $category) {
        $content .= "│       ├── $category.php\n";
    }

    $content .= "├── migrate_templates.sh\n";
    $content .= "└── TRANSLATION_SUMMARY.md\n";
    $content .= "```\n";

    file_put_contents($reportFile, $content);

    echo Color::GREEN . "  ✓ Generated summary report\n" . Color::NC;
}
