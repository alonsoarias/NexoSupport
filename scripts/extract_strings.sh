#!/bin/bash

##############################################################################
# NexoSupport - i18n String Extraction Script
#
# Purpose: Extract hardcoded Spanish strings from Mustache templates
# Output: JSON file with categorized strings for translation
#
# Usage: bash extract_strings.sh
##############################################################################

OUTPUT_FILE="i18n_strings_extracted.json"
TEMP_FILE="i18n_strings_temp.txt"

echo "================================================"
echo "NexoSupport i18n String Extraction"
echo "================================================"
echo ""

# Clean up old files
rm -f "$OUTPUT_FILE" "$TEMP_FILE"

# Initialize JSON structure
cat > "$OUTPUT_FILE" << 'EOF'
{
  "extraction_date": "$(date -Iseconds)",
  "total_files": 0,
  "total_strings": 0,
  "categories": {
    "admin": [],
    "auth": [],
    "common": [],
    "messages": [],
    "forms": [],
    "navigation": [],
    "help": [],
    "uncategorized": []
  },
  "files_scanned": []
}
EOF

# Function to extract strings from a file
extract_from_file() {
    local file="$1"
    local category="$2"

    echo "Scanning: $file"

    # Extract text between HTML tags and mustache blocks
    # Look for:
    # 1. <tag>Spanish Text</tag>
    # 2. placeholder="Spanish Text"
    # 3. title="Spanish Text"
    # 4. alt="Spanish Text"
    # 5. Text in buttons, labels, etc.

    # Extract strings (this is a simplified version - more complex regex needed for production)
    grep -oP '(?<=>)[^<{]+(?=<)' "$file" | \
        grep -v '^\s*$' | \
        grep -v '^[0-9]*$' | \
        grep -v '^\{\{' | \
        grep -v '^\s*<' | \
        sed 's/^[[:space:]]*//' | \
        sed 's/[[:space:]]*$//' | \
        grep -E '[A-Za-zÁÉÍÓÚáéíóúñÑ]' >> "$TEMP_FILE"
}

echo "Step 1: Scanning admin templates..."
echo "----------------------------------------------"

# Scan admin templates (CRITICAL priority)
admin_count=0
for file in /home/user/NexoSupport/resources/views/admin/**/*.mustache; do
    if [ -f "$file" ]; then
        extract_from_file "$file" "admin"
        ((admin_count++))
    fi
done
echo "✓ Admin templates scanned: $admin_count files"
echo ""

echo "Step 2: Scanning auth templates..."
echo "----------------------------------------------"

# Scan auth templates (HIGH priority)
auth_count=0
for file in /home/user/NexoSupport/resources/views/auth/*.mustache; do
    if [ -f "$file" ]; then
        extract_from_file "$file" "auth"
        ((auth_count++))
    fi
done
echo "✓ Auth templates scanned: $auth_count files"
echo ""

echo "Step 3: Scanning module templates..."
echo "----------------------------------------------"

# Scan Admin module templates
module_count=0
for file in /home/user/NexoSupport/modules/Admin/templates/*.mustache; do
    if [ -f "$file" ]; then
        extract_from_file "$file" "admin"
        ((module_count++))
    fi
done
echo "✓ Module templates scanned: $module_count files"
echo ""

# Count unique strings
if [ -f "$TEMP_FILE" ]; then
    total_strings=$(sort -u "$TEMP_FILE" | wc -l)
    echo "================================================"
    echo "Extraction Complete!"
    echo "================================================"
    echo "Total unique strings found: $total_strings"
    echo "Total files scanned: $((admin_count + auth_count + module_count))"
    echo ""
    echo "Results saved to: $TEMP_FILE"
    echo ""
    echo "Next steps:"
    echo "1. Review extracted strings"
    echo "2. Categorize by context"
    echo "3. Create translation keys"
    echo "4. Update translation files"
else
    echo "ERROR: No strings extracted"
    exit 1
fi
