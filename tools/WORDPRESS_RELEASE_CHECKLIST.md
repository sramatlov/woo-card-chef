# WordPress release checklist

## Why this exists

Windows zip tooling can write entries with backslashes, for example:

```text
wc-product-card-elementor\wc-product-card-elementor.php
```

WordPress plugin zips must use forward slashes:

```text
wc-product-card-elementor/wc-product-card-elementor.php
```

## Required release path

Use GitHub Actions: **Validate and build plugin zip**.

The workflow:

- runs `php -l` on all plugin PHP files;
- reads the version from `wc-product-card-elementor.php`;
- runs `tools/build_wordpress_plugin_zip.py`;
- fails when the zip contains backslash paths, absolute paths, parent traversal paths, missing root folder, or missing main plugin file;
- uploads the install zip as a workflow artifact.

## Local fallback

If needed, build with Python:

```bash
python tools/build_wordpress_plugin_zip.py \
  --source-dir . \
  --destination-zip dist/woo-card-chef-vX.Y.Z-wordpress-install.zip \
  --plugin-slug wc-product-card-elementor \
  --main-file wc-product-card-elementor.php
```

Do not use PowerShell `Compress-Archive` for WordPress install zips.
