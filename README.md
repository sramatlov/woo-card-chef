# Woo Card Chef

Private WordPress/WooCommerce Elementor plugin repository for Woo Card Chef.

## Release workflow

This repository is set up so GitHub Actions can build the WordPress install zip without local PHP, local GitHub CLI, or PowerShell.

The workflow in `.github/workflows/validate-and-build.yml` will:

- install PHP on the GitHub runner;
- run `php -l` on all plugin PHP files;
- read the plugin version from `wc-product-card-elementor.php`;
- build a WordPress-safe install zip with `tools/build_wordpress_plugin_zip.py`;
- fail if zip entries contain Windows backslashes;
- upload the install zip as a GitHub Actions artifact.

## Important

Do not use PowerShell `Compress-Archive` for WordPress install zips. It can create Windows-style zip paths such as:

```text
wc-product-card-elementor\wc-product-card-elementor.php
```

WordPress expects:

```text
wc-product-card-elementor/wc-product-card-elementor.php
```

## Next step

Upload the plugin source files into the repository root so `wc-product-card-elementor.php` is at the root of this repository.

Expected root files/folders include:

- `assets/`
- `includes/`
- `templates/`
- `readme.txt`
- `uninstall.php`
- `wc-product-card-elementor.php`

After the source is uploaded, GitHub Actions should run automatically and produce the install zip artifact.
