#!/bin/bash

################################################################################
# HARDCODED STRINGS EXTRACTION TOOL - NexoSupport
#
# This script extracts hardcoded strings from Mustache templates that should
# be internationalized using the {{#__}}key{{/__}} syntax.
#
# Usage: ./extract_hardcoded_strings.sh [output_file]
#
# Author: NexoSupport Refactoring Initiative
# Date: 2025-11-13
################################################################################

set -e

# Configuration
PROJECT_ROOT="/home/user/NexoSupport"
OUTPUT_FILE="${1:-hardcoded_strings_report.txt}"
DETAILED_OUTPUT="${OUTPUT_FILE%.txt}_detailed.csv"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  NexoSupport i18n String Extractor${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Create output directory
mkdir -p "$(dirname "$OUTPUT_FILE")"

# Start report
{
    echo "HARDCODED STRINGS EXTRACTION REPORT - NexoSupport"
    echo "Generated: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "================================================"
    echo ""
} > "$OUTPUT_FILE"

# CSV header
echo "File,Line,String,Context,Priority" > "$DETAILED_OUTPUT"

total_strings=0

################################################################################
# Function: Extract strings from Mustache templates
################################################################################
extract_from_mustache() {
    local search_dir="$1"
    local category="$2"

    echo -e "${YELLOW}Scanning ${category}...${NC}"

    {
        echo ""
        echo "=== ${category} ==="
        echo ""
    } >> "$OUTPUT_FILE"

    local count=0

    # Find all .mustache files
    while IFS= read -r file; do
        local file_count=0

        # Extract Spanish text patterns (words starting with capital letters, accented chars)
        # This regex matches Spanish text that's NOT inside {{#__}} translation blocks

        # Pattern 1: HTML text content (between tags)
        while IFS= read -r line_num; do
            local line_content=$(sed -n "${line_num}p" "$file")

            # Skip if line contains {{#__}} or {{/__}} (already translated)
            if echo "$line_content" | grep -q '{{#__}}\|{{/__}}'; then
                continue
            fi

            # Extract text between > and <
            local extracted=$(echo "$line_content" | grep -oP '(?<=>)[^<]+(?=<)' | grep -P '[A-ZÁÉÍÓÚÑ]' || true)

            if [ ! -z "$extracted" ]; then
                # Clean and trim
                extracted=$(echo "$extracted" | xargs)

                if [ ${#extracted} -gt 3 ]; then
                    echo "  Line $line_num: $extracted" >> "$OUTPUT_FILE"

                    # Add to CSV
                    local relative_path=$(echo "$file" | sed "s|$PROJECT_ROOT/||")
                    echo "\"$relative_path\",$line_num,\"$extracted\",\"HTML content\",\"HIGH\"" >> "$DETAILED_OUTPUT"

                    ((file_count++))
                    ((count++))
                    ((total_strings++))
                fi
            fi
        done < <(grep -n '[A-ZÁÉÍÓÚÑ]' "$file" | cut -d: -f1)

        # Pattern 2: Attribute values (placeholder, title, alt, etc.)
        while IFS= read -r line_num; do
            local line_content=$(sed -n "${line_num}p" "$file")

            # Skip if already translated
            if echo "$line_content" | grep -q '{{#__}}\|{{/__}}'; then
                continue
            fi

            # Extract attribute values
            local extracted=$(echo "$line_content" | grep -oP '(?<=(placeholder|title|alt|aria-label)=")[^"]+' | grep -P '[A-ZÁÉÍÓÚÑ]' || true)

            if [ ! -z "$extracted" ]; then
                extracted=$(echo "$extracted" | xargs)

                if [ ${#extracted} -gt 3 ]; then
                    echo "  Line $line_num: [ATTR] $extracted" >> "$OUTPUT_FILE"

                    local relative_path=$(echo "$file" | sed "s|$PROJECT_ROOT/||")
                    echo "\"$relative_path\",$line_num,\"$extracted\",\"Attribute\",\"HIGH\"" >> "$DETAILED_OUTPUT"

                    ((file_count++))
                    ((count++))
                    ((total_strings++))
                fi
            fi
        done < <(grep -n -E '(placeholder|title|alt|aria-label)=' "$file" | cut -d: -f1)

        if [ $file_count -gt 0 ]; then
            local relative_path=$(echo "$file" | sed "s|$PROJECT_ROOT/||")
            echo "$relative_path: $file_count strings" >> "$OUTPUT_FILE"
        fi

    done < <(find "$search_dir" -name "*.mustache" -type f 2>/dev/null)

    echo "  Found: $count strings" >> "$OUTPUT_FILE"
    echo -e "${GREEN}  Found: $count strings${NC}"
}

################################################################################
# Scan different directories
################################################################################

# Admin templates
if [ -d "$PROJECT_ROOT/modules/Admin/templates" ]; then
    extract_from_mustache "$PROJECT_ROOT/modules/Admin/templates" "Admin Templates"
fi

# Resource views
if [ -d "$PROJECT_ROOT/resources/views" ]; then
    extract_from_mustache "$PROJECT_ROOT/resources/views" "Resource Views"
fi

# Theme templates
if [ -d "$PROJECT_ROOT/modules/Theme" ]; then
    extract_from_mustache "$PROJECT_ROOT/modules/Theme" "Theme Templates"
fi

# Auth templates
if [ -d "$PROJECT_ROOT/modules/Auth" ]; then
    extract_from_mustache "$PROJECT_ROOT/modules/Auth" "Auth Templates"
fi

################################################################################
# Summary
################################################################################

{
    echo ""
    echo "================================================"
    echo "SUMMARY"
    echo "================================================"
    echo "Total hardcoded strings found: $total_strings"
    echo ""
    echo "Next steps:"
    echo "1. Review the detailed CSV report: $DETAILED_OUTPUT"
    echo "2. Create translation keys for each string"
    echo "3. Update templates to use {{#__}}key{{/__}} syntax"
    echo "4. Add translations to /resources/lang/ files"
    echo ""
} >> "$OUTPUT_FILE"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ Extraction complete!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "Total hardcoded strings found: ${RED}$total_strings${NC}"
echo ""
echo -e "Reports generated:"
echo -e "  ${YELLOW}Summary:${NC} $OUTPUT_FILE"
echo -e "  ${YELLOW}Detailed CSV:${NC} $DETAILED_OUTPUT"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review the detailed CSV report"
echo "2. Run the key generator: ./generate_translation_keys.sh"
echo "3. Update templates with translation keys"
echo ""
