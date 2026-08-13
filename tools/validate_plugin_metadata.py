#!/usr/bin/env python3
"""Validate that the plugin version metadata stays internally consistent."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import re


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--plugin-dir", required=True, help="Plugin source directory.")
    parser.add_argument("--main-file", required=True, help="Main plugin file inside the source directory.")
    parser.add_argument("--version-only", action="store_true", help="Print only the validated version.")
    return parser.parse_args()


def require_match(pattern: str, text: str, label: str) -> str:
    match = re.search(pattern, text, re.MULTILINE)
    if not match:
        raise SystemExit(f"Missing {label}")
    return match.group(1).strip()


def main() -> int:
    args = parse_args()
    plugin_dir = Path(args.plugin_dir).resolve()
    main_path = plugin_dir / args.main_file
    readme_path = plugin_dir / "readme.txt"

    if not main_path.is_file():
        raise SystemExit(f"Main plugin file not found: {main_path}")
    if not readme_path.is_file():
        raise SystemExit(f"Plugin readme not found: {readme_path}")

    main_text = main_path.read_text(encoding="utf-8", errors="strict")
    readme_text = readme_path.read_text(encoding="utf-8", errors="strict")

    header_version = require_match(r"^ \* Version:\s*([^\s]+)", main_text, "plugin Version header")
    constant_version = require_match(
        r"define\(\s*'WCPCE_VERSION'\s*,\s*'([^']+)'\s*\);",
        main_text,
        "WCPCE_VERSION constant",
    )
    stable_tag = require_match(r"^Stable tag:\s*([^\s]+)", readme_text, "readme Stable tag")

    versions = {
        "plugin header": header_version,
        "WCPCE_VERSION": constant_version,
        "readme stable tag": stable_tag,
    }
    if len(set(versions.values())) != 1:
        details = ", ".join(f"{label}={value}" for label, value in versions.items())
        raise SystemExit(f"Plugin version metadata does not match: {details}")

    result = {
        "plugin_dir": plugin_dir.name,
        "main_file": args.main_file,
        "version": header_version,
        "php_files": len(list(plugin_dir.rglob("*.php"))),
    }

    if result["php_files"] == 0:
        raise SystemExit(f"No PHP files found in {plugin_dir}")

    if args.version_only:
        print(header_version)
    else:
        print(json.dumps(result, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
