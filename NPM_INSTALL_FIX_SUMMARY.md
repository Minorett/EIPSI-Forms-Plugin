# NPM Install Fix Summary - EIPSI Forms v1.5.5

## Date: 2025-02-20

## Problem

The `npm install --legacy-peer-deps` command was failing with the following error:

```
npm error code ETARGET
npm error notarget No matching version found for @wordpress/vips@^1.0.0-prerelease.
npm error notarget In most cases you or one of your dependencies are requesting
npm error notarget a package version that doesn't exist.
```

Additionally, after fixing the first error, a second error appeared:

```
[webpack-cli] Error: Cannot find module 'ajv/dist/compile/codegen'
```

## Root Cause Analysis

### Error 1: @wordpress/vips

The package `@wordpress/upload-media@0.25.0` (a dependency of `@wordpress/scripts@31.4.0`) was requesting `@wordpress/vips@^1.0.0-prerelease`, but this version doesn't exist in the npm registry. The only available version is `1.0.1-next.v.202602200903.0`.

### Error 2: ajv-keywords/ajv incompatibility

The package `ajv-keywords@5.1.0` requires `ajv@^8.8.2` as a peer dependency, but npm's deduplication was incorrectly resolving it to `ajv@6.12.6`, causing a runtime error when webpack tried to load the configuration.

## Solution

### Changes to package.json

1. **Added override for @wordpress/vips**:
   ```json
   "@wordpress/vips": "1.0.1-next.v.202602200903.0"
   ```

2. **Added override for ajv-keywords to use ajv@8**:
   ```json
   "ajv-keywords": {
       "ajv": "^8.18.0"
   }
   ```

3. **Added ajv@8 as a devDependency** (to ensure the correct version is available at the root level):
   ```json
   "devDependencies": {
       "@wordpress/scripts": "^31.4.0",
       "jsdom": "^27.2.0",
       "ajv": "^8.18.0"
   }
   ```

## Final package.json Configuration

```json
{
    "name": "eipsi-forms",
    "version": "1.5.5",
    "devDependencies": {
        "@wordpress/scripts": "^31.4.0",
        "jsdom": "^27.2.0",
        "ajv": "^8.18.0"
    },
    "overrides": {
        "webpack-dev-server": "^5.2.2",
        "lodash": "^4.17.21",
        "lodash-es": "^4.17.21",
        "@wordpress/vips": "1.0.1-next.v.202602200903.0",
        "ajv-keywords": {
            "ajv": "^8.18.0"
        }
    }
}
```

## Verification

After applying these changes:

- ✅ `npm install --legacy-peer-deps` runs successfully
- ✅ `npm run build` compiles successfully
- ⚠️ `npm run lint:js` shows formatting errors (pre-existing, unrelated to this fix)

## Notes for Future Maintenance

1. **Version Pinning**: The `@wordpress/vips` override uses a specific next version. Monitor the npm registry for a stable release and update accordingly.

2. **WordPress Scripts Updates**: When updating `@wordpress/scripts`, check if the new version resolves these dependency issues natively. Future versions may include compatible versions of `@wordpress/upload-media` and `schema-utils`.

3. **AJV Version**: The `ajv@8` dependency is added as a workaround for the `ajv-keywords` peer dependency issue. This may be resolved in future versions of `schema-utils` or `@wordpress/scripts`.

## Environment

- Node.js: v24.13.1
- npm: 11.8.0
- @wordpress/scripts: ^31.4.0 (resolves to 31.5.0)
