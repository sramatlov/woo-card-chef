#!/usr/bin/env python3
"""Build and validate a WordPress plugin install zip.

This script intentionally writes POSIX-style zip entries with forward slashes.
Do not use Windows-native zip builders for release artifacts unless they are
validated the same way.
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import posixpath
import sys
import zipfile


EXCLUDED_TOP_LEVEL = {
    ".git",
    ".github",
    ".idea",
    ".vscode",
    "dist",
    "node_modules",
    "tools",
}

EXCLUDED_FILE_NAMES = {
    ".DS_Store",
    ".gitignore",
    "Thumbs.db",
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source-dir", required=True, help="Plugin source directory.")
    parser.add_argument("--destination-zip", required=True, help="Output zip path.")
    parser.add_argument("--plugin-slug", default="", help="Plugin root folder name in the zip.")
    parser.add_argument("--main-file", default="", help="Main plugin PHP file inside the root folder.")
    return parser.parse_args()


def should_include(path: Path, source_dir: Path) -> bool:
    relative = path.relative_to(source_dir)
    if not relative.parts:
        return True

    if relative.parts[0] in EXCLUDED_TOP_LEVEL:
        return False

    if path.is_file() and path.name in EXCLUDED_FILE_NAMES:
        return False

    if path.is_file() and path.suffix.lower() == ".zip":
        return False

    return True


def zip_name(path: Path, source_dir: Path, plugin_slug: str) -> str:
    relative = path.relative_to(source_dir).as_posix()
    return posixpath.join(plugin_slug, relative)


def validate_zip(destination_zip: Path, plugin_slug: str, main_file: str) -> dict[str, object]:
    with zipfile.ZipFile(destination_zip, "r") as archive:
        names = archive.namelist()

    backslash_entries = [name for name in names if "\\" in name]
    absolute_entries = [name for name in names if name.startswith("/") or (len(name) > 1 and name[1] == ":")]
    traversal_entries = [name for name in names if "../" in name or name.startswith("..")]
    top_level = sorted({name.split("/", 1)[0] for name in names if name})
    main_entry = f"{plugin_slug}/{main_file}"
    root_entry = f"{plugin_slug}/"

    errors: list[str] = []
    if backslash_entries:
        errors.append("contains Windows backslash paths")
    if absolute_entries:
        errors.append("contains absolute paths")
    if traversal_entries:
        errors.append("contains parent traversal paths")
    if top_level != [plugin_slug]:
        errors.append(f"expected exactly one top-level folder {plugin_slug!r}; found {top_level!r}")
    if root_entry not in names:
        errors.append(f"missing root folder entry {root_entry!r}")
    if main_entry not in names:
        errors.append(f"missing main plugin file {main_entry!r}")

    for excluded in EXCLUDED_TOP_LEVEL:
        forbidden_prefix = f"{plugin_slug}/{excluded}/"
        if any(name.startswith(forbidden_prefix) for name in names):
            errors.append(f"contains excluded path {forbidden_prefix!r}")

    if errors:
        raise RuntimeError("Invalid WordPress plugin zip: " + "; ".join(errors))

    return {
        "zip": str(destination_zip),
        "plugin_slug": plugin_slug,
        "main_file": main_entry,
        "entries": len(names),
        "backslash_entries": len(backslash_entries),
        "size_bytes": destination_zip.stat().st_size,
    }


def main() -> int:
    args = parse_args()
    source_dir = Path(args.source_dir).resolve()
    destination_zip = Path(args.destination_zip).resolve()
    plugin_slug = args.plugin_slug.strip() or source_dir.name
    main_file = args.main_file.strip() or f"{plugin_slug}.php"

    if not source_dir.is_dir():
        raise SystemExit(f"Source directory does not exist: {source_dir}")

    main_path = source_dir / main_file
    if not main_path.is_file():
        raise SystemExit(f"Main plugin file not found: {main_path}")

    destination_zip.parent.mkdir(parents=True, exist_ok=True)
    if destination_zip.exists():
        destination_zip.unlink()

    directories = sorted(
        (path for path in source_dir.rglob("*") if path.is_dir() and should_include(path, source_dir)),
        key=lambda path: path.as_posix(),
    )
    files = sorted(
        (path for path in source_dir.rglob("*") if path.is_file() and should_include(path, source_dir)),
        key=lambda path: path.as_posix(),
    )

    with zipfile.ZipFile(destination_zip, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        archive.writestr(f"{plugin_slug}/", "")
        for directory in directories:
            archive.writestr(zip_name(directory, source_dir, plugin_slug) + "/", "")
        for file_path in files:
            archive.write(file_path, zip_name(file_path, source_dir, plugin_slug))

    print(json.dumps(validate_zip(destination_zip, plugin_slug, main_file), indent=2))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        raise SystemExit(1)
