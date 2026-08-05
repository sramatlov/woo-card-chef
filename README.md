# Woo Card Chef

Private WordPress/WooCommerce Elementor plugin repository for Woo Card Chef.

## Release workflow

The installable plugin source lives in `wc-product-card-elementor/`. GitHub Actions validates that source and builds the WordPress install zip without requiring local PHP or PowerShell.

The workflow in `.github/workflows/validate-and-build.yml` will:

- fail when the expected plugin source is missing;
- run `php -l` on all plugin PHP files with PHP 7.4 and PHP 8.3;
- ensure the plugin header, `WCPCE_VERSION`, and readme stable tag match;
- read the plugin version from `wc-product-card-elementor/wc-product-card-elementor.php`;
- build a WordPress-safe install zip with `tools/build_wordpress_plugin_zip.py`;
- fail if zip entries contain Windows backslashes;
- upload the install zip as a GitHub Actions artifact.

Action dependencies are pinned to immutable commits. Dependabot checks for GitHub Actions updates every week.

## Important

Do not use PowerShell `Compress-Archive` for WordPress install zips. It can create Windows-style zip paths such as:

```text
wc-product-card-elementor\wc-product-card-elementor.php
```

WordPress expects:

```text
wc-product-card-elementor/wc-product-card-elementor.php
```

## Build locally

From the repository root:

```powershell
python tools/validate_plugin_metadata.py --plugin-dir wc-product-card-elementor --main-file wc-product-card-elementor.php
python tools/build_wordpress_plugin_zip.py --source-dir wc-product-card-elementor --destination-zip dist/woo-card-chef-v2.6.9-wordpress-install.zip --plugin-slug wc-product-card-elementor --main-file wc-product-card-elementor.php
```
