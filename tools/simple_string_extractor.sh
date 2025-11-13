#!/bin/bash

# Simple String Extractor for i18n Audit
# Finds Spanish hardcoded strings in Mustache templates

OUTPUT="i18n_strings_found.txt"

echo "NexoSupport - Hardcoded String Extraction"
echo "=========================================="
echo ""

# Initialize counters
total=0

# Header
echo "HARDCODED STRINGS FOUND - $(date)" > "$OUTPUT"
echo "======================================" >> "$OUTPUT"
echo "" >> "$OUTPUT"

# Function to scan directory
scan_directory() {
    local dir="$1"
    local label="$2"
    local count=0

    echo "Scanning: $label"
    echo "" >> "$OUTPUT"
    echo "=== $label ===" >> "$OUTPUT"
    echo "" >> "$OUTPUT"

    # Find all mustache files and grep for Spanish patterns
    while IFS= read -r file; do
        local file_strings=0

        # Find lines with Spanish capital letters not in {{#__}}...{{/__}} blocks
        while IFS=: read -r line_num line_content; do
            # Skip lines that already use translation syntax
            if echo "$line_content" | grep -q '{{#__}}\|{{/__}}'; then
                continue
            fi

            # Check if line has Spanish text
            if echo "$line_content" | grep -qP '[A-ZÁÉÍÓÚÑ][a-záéíóúñ]{3,}'; then
                echo "  $file:$line_num" >> "$OUTPUT"
                echo "    $line_content" >> "$OUTPUT"
                echo "" >> "$OUTPUT"
                ((file_strings++))
                ((count++))
                ((total++))
            fi
        done < <(grep -n . "$file")

    done < <(find "$dir" -name "*.mustache" 2>/dev/null)

    echo "  Found: $count strings" >> "$OUTPUT"
    echo "  → Found: $count strings"
    echo ""
}

# Scan directories
if [ -d "/home/user/NexoSupport/resources/views/admin" ]; then
    scan_directory "/home/user/NexoSupport/resources/views/admin" "Admin Views"
fi

if [ -d "/home/user/NexoSupport/resources/views/auth" ]; then
    scan_directory "/home/user/NexoSupport/resources/views/auth" "Auth Views"
fi

if [ -d "/home/user/NexoSupport/resources/views/dashboard" ]; then
    scan_directory "/home/user/NexoSupport/resources/views/dashboard" "Dashboard Views"
fi

if [ -d "/home/user/NexoSupport/resources/views/profile" ]; then
    scan_directory "/home/user/NexoSupport/resources/views/profile" "Profile Views"
fi

if [ -d "/home/user/NexoSupport/modules/Admin/templates" ]; then
    scan_directory "/home/user/NexoSupport/modules/Admin/templates" "Admin Module Templates"
fi

if [ -d "/home/user/NexoSupport/modules/Theme/Iser/templates" ]; then
    scan_directory "/home/user/NexoSupport/modules/Theme/Iser/templates" "Theme Templates"
fi

# Summary
echo "" >> "$OUTPUT"
echo "======================================" >> "$OUTPUT"
echo "TOTAL HARDCODED STRINGS: $total" >> "$OUTPUT"
echo "======================================" >> "$OUTPUT"

echo "========================================"
echo "TOTAL HARDCODED STRINGS: $total"
echo "========================================"
echo ""
echo "Report saved to: $OUTPUT"
echo ""
