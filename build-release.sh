#!/bin/bash
# EIPSI Forms - Release Package Builder
# This script prepares a clean distribution package

set -e  # Exit on error

PLUGIN_SLUG="vas-dinamico-forms"
VERSION=$(grep "Version:" vas-dinamico-forms.php | awk '{print $2}' | tr -d '\r')
BUILD_DIR="dist"
ARCHIVE_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "============================================"
echo "EIPSI Forms - Release Package Builder"
echo "============================================"
echo "Version: ${VERSION}"
echo ""

# Step 1: Clean old build artifacts
echo "Step 1: Cleaning old build artifacts..."
rm -rf build/
rm -rf node_modules/
rm -rf ${BUILD_DIR}/
echo "✓ Cleaned"
echo ""

# Step 2: Install dependencies
echo "Step 2: Installing npm dependencies..."
npm install --quiet
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

# Step 8: Summary
echo "============================================"
echo "Build Summary"
echo "============================================"
echo "Package: ${ARCHIVE_NAME}"
echo "Size: $(du -h ${ARCHIVE_NAME} | awk '{print $1}')"
echo ""
echo "Package contents:"
unzip -l ${ARCHIVE_NAME} | tail -n 1
echo ""

if [ $EXCLUDED_FOUND -eq 0 ]; then
    echo "✓ Release package is ready!"
    echo ""
    echo "Next steps:"
    echo "1. Test installation: unzip ${ARCHIVE_NAME} -d /path/to/wordpress/wp-content/plugins/"
    echo "2. Verify plugin functionality"
    echo "3. Upload to WordPress.org or distribute"
else
    echo "⚠ WARNING: Package may contain excluded files!"
    echo "Please review the package contents before distribution."
fi
echo ""
