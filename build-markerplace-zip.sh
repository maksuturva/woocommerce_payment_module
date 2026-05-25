#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -euo pipefail

# Define colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Svea Payments WooCommerce Marketplace Builder ===${NC}"

# Ensure we are in the plugin root directory (where the script is located)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

PLUGIN_SLUG="svea-payments-finland-for-woocommerce"
ZIP_NAME="${PLUGIN_SLUG}.zip"
TEMP_DIR="build-temp"
BUILD_TARGET="${TEMP_DIR}/${PLUGIN_SLUG}"

# 1. Build assets if node_modules exists
if [ -d "node_modules" ]; then
    echo -e "${BLUE}Running asset compilation (npm run build)...${NC}"
    npm run build
else
    echo -e "${YELLOW}Warning: node_modules not found. Skipping asset compilation.${NC}"
    echo -e "Make sure assets in 'assets/' are up-to-date."
fi

# 2. Clean up old build artifacts
echo -e "${BLUE}Cleaning up old build artifacts...${NC}"
rm -rf "${TEMP_DIR}" "${ZIP_NAME}"

# Ensure temp directory exists
mkdir -p "${BUILD_TARGET}"

# Define cleanup trap
cleanup() {
    if [ -d "${TEMP_DIR}" ]; then
        echo -e "${BLUE}Cleaning up temporary files...${NC}"
        rm -rf "${TEMP_DIR}"
    fi
}
trap cleanup EXIT

# 3. Copy files to temp directory using rsync to respect ignore files
echo -e "${BLUE}Copying plugin files and applying ignore lists...${NC}"

# We construct the rsync command to exclude:
# - All files/dirs in .gitignore
# - All files/dirs in .distignore
# - assets/icon* (as requested by user)
# - This script itself (build-markerplace-zip.sh)
# - The temp build folder
rsync -a \
    --exclude-from=".gitignore" \
    --exclude-from=".distignore" \
    --exclude="assets/icon*" \
    --exclude="build-markerplace-zip.sh" \
    --exclude="${TEMP_DIR}" \
    --exclude=".git" \
    --exclude=".github" \
    ./ "${BUILD_TARGET}/"

# 4. Generate the ZIP file
echo -e "${BLUE}Generating marketplace ZIP archive...${NC}"
cd "${TEMP_DIR}"
zip -r "../${ZIP_NAME}" "${PLUGIN_SLUG}" > /dev/null
cd ..

# 5. Verify the ZIP was created and display info
if [ -f "${ZIP_NAME}" ]; then
    ZIP_SIZE=$(du -sh "${ZIP_NAME}" | cut -f1)
    echo -e "${GREEN}✓ Success! Marketplace ZIP created successfully: ${ZIP_NAME} (${ZIP_SIZE})${NC}"
    echo -e "${GREEN}The ZIP contains a single root folder '${PLUGIN_SLUG}/' and is ready for WordPress Marketplace!${NC}"
else
    echo -e "${RED}✗ Error: Failed to create ZIP archive.${NC}"
    exit 1
fi
