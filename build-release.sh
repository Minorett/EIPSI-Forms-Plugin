#!/bin/bash
# EIPSI Forms - Release Package Builder
# This script prepares a clean distribution package

set -e  # Exit on error

PLUGIN_SLUG="eipsi-forms"
VERSION=$(grep "^ \* Version:" vas-dinamico-forms.php | head -1 | awk '{print $3}' | tr -d '\r')
BUILD_DIR="dist"
ARCHIVE_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
METADATA_FILE="release-metadata-${VERSION}.json"

echo "============================================"
echo "EIPSI Forms - Release Package Builder"
echo "============================================"
echo "Version: ${VERSION}"
echo "Build Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Step 1: Clean old build artifacts
echo "Step 1: Cleaning old build artifacts..."
rm -rf build/
rm -rf node_modules/
rm -rf ${BUILD_DIR}/
rm -f ${PLUGIN_SLUG}-*.zip
rm -f release-metadata-*.json
echo "✓ Cleaned"
echo ""

# Step 2: Install dependencies
echo "Step 2: Installing npm dependencies with npm ci..."
npm ci --quiet
echo "✓ Dependencies installed"
echo ""

# Step 3: Build blocks
echo "Step 3: Building Gutenberg blocks..."
npm run build
echo "✓ Blocks compiled"
echo ""

# Step 4: Verify build output
echo "Step 4: Verifying build output..."
if [ ! -d "build" ]; then
    echo "✗ ERROR: build/ directory not found!"
    exit 1
fi
if [ ! -f "build/index.js" ]; then
    echo "✗ ERROR: build/index.js not found!"
    exit 1
fi
if [ ! -f "build/index.asset.php" ]; then
    echo "✗ ERROR: build/index.asset.php not found!"
    exit 1
fi
echo "✓ Build output verified"
echo ""

# Step 5: Check for wp-cli
echo "Step 5: Checking for wp-cli..."
if command -v wp &> /dev/null; then
    echo "✓ wp-cli found"
    USE_WP_CLI=true
else
    echo "⚠ wp-cli not found, will use manual method"
    USE_WP_CLI=false
fi
echo ""

# Step 6: Create distribution package
echo "Step 6: Creating distribution package..."
if [ "$USE_WP_CLI" = true ]; then
    # Use wp-cli (respects .distignore automatically)
    wp dist-archive . --plugin-dirname=${PLUGIN_SLUG}
    mv ${PLUGIN_SLUG}.zip ${ARCHIVE_NAME}
else
    # Manual method: create clean copy
    mkdir -p ${BUILD_DIR}/${PLUGIN_SLUG}
    
    # Copy files using rsync with .distignore exclusions
    if [ -f ".distignore" ]; then
        echo "Using .distignore for exclusions..."
        rsync -av --exclude-from=.distignore . ${BUILD_DIR}/${PLUGIN_SLUG}/
    else
        echo "✗ ERROR: .distignore file not found!"
        exit 1
    fi
    
    # Create zip archive
    cd ${BUILD_DIR}
    zip -rq ../${ARCHIVE_NAME} ${PLUGIN_SLUG}/
    cd ..
    
    # Clean up temporary directory
    rm -rf ${BUILD_DIR}
fi
echo "✓ Package created: ${ARCHIVE_NAME}"
echo ""

# Step 7: Verify package
echo "Step 7: Verifying package contents..."
echo ""
echo "Package size:"
du -h ${ARCHIVE_NAME}
echo ""

echo "Checking for excluded files (should find none)..."
EXCLUDED_FOUND=0

# Check for test files
if unzip -l ${ARCHIVE_NAME} | grep -q "test-.*\.\(js\|html\|sh\)"; then
    echo "✗ WARNING: Test files found in package!"
    EXCLUDED_FOUND=1
fi

# Check for developer docs
if unzip -l ${ARCHIVE_NAME} | grep -q "AUDIT.*\.md"; then
    echo "✗ WARNING: Audit documentation found in package!"
    EXCLUDED_FOUND=1
fi

# Check for .git directory
if unzip -l ${ARCHIVE_NAME} | grep -q "\.git/"; then
    echo "✗ WARNING: .git directory found in package!"
    EXCLUDED_FOUND=1
fi

# Check for node_modules
if unzip -l ${ARCHIVE_NAME} | grep -q "node_modules/"; then
    echo "✗ WARNING: node_modules found in package!"
    EXCLUDED_FOUND=1
fi

if [ $EXCLUDED_FOUND -eq 0 ]; then
    echo "✓ No excluded files found"
fi
echo ""

# Step 8: Generate checksums
echo "Step 8: Generating checksums..."
MD5_CHECKSUM=$(md5sum ${ARCHIVE_NAME} | awk '{print $1}')
SHA256_CHECKSUM=$(sha256sum ${ARCHIVE_NAME} | awk '{print $1}')
PACKAGE_SIZE=$(du -h ${ARCHIVE_NAME} | awk '{print $1}')
PACKAGE_SIZE_BYTES=$(stat -c%s ${ARCHIVE_NAME})
FILE_COUNT=$(unzip -l ${ARCHIVE_NAME} | tail -n 1 | awk '{print $2}')

echo "✓ MD5: ${MD5_CHECKSUM}"
echo "✓ SHA256: ${SHA256_CHECKSUM}"
echo ""

# Step 9: Create metadata file
echo "Step 9: Creating release metadata..."
cat > ${METADATA_FILE} <<EOF
{
  "plugin": "EIPSI Forms",
  "slug": "${PLUGIN_SLUG}",
  "version": "${VERSION}",
  "archive": "${ARCHIVE_NAME}",
  "buildDate": "$(date -u '+%Y-%m-%d %H:%M:%S UTC')",
  "size": {
    "bytes": ${PACKAGE_SIZE_BYTES},
    "human": "${PACKAGE_SIZE}"
  },
  "fileCount": ${FILE_COUNT},
  "checksums": {
    "md5": "${MD5_CHECKSUM}",
    "sha256": "${SHA256_CHECKSUM}"
  },
  "requirements": {
    "wordpress": "5.8+",
    "php": "7.4+",
    "gutenberg": true
  },
  "verification": {
    "excludedFilesCheck": $([ $EXCLUDED_FOUND -eq 0 ] && echo "true" || echo "false"),
    "buildOutputVerified": true
  }
}
EOF
echo "✓ Metadata saved to: ${METADATA_FILE}"
echo ""

# Step 10: Summary
echo "============================================"
echo "Build Summary"
echo "============================================"
echo "Package: ${ARCHIVE_NAME}"
echo "Size: ${PACKAGE_SIZE} (${PACKAGE_SIZE_BYTES} bytes)"
echo "Files: ${FILE_COUNT}"
echo "MD5: ${MD5_CHECKSUM}"
echo "SHA256: ${SHA256_CHECKSUM}"
echo ""
echo "Package contents:"
unzip -l ${ARCHIVE_NAME} | tail -n 1
echo ""

if [ $EXCLUDED_FOUND -eq 0 ]; then
    echo "✓ Release package is ready!"
    echo ""
    echo "Next steps:"
    echo "1. Review metadata: cat ${METADATA_FILE}"
    echo "2. Test installation: unzip ${ARCHIVE_NAME} -d /path/to/wordpress/wp-content/plugins/"
    echo "3. Run smoke tests (see SMOKE_TEST_PROCEDURES.md)"
    echo "4. Upload to WordPress.org or distribute"
else
    echo "⚠ WARNING: Package may contain excluded files!"
    echo "Please review the package contents before distribution."
fi
echo ""
