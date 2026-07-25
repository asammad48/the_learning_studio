#!/usr/bin/env python3
"""Build a clean, upload-ready WordPress theme ZIP."""
from __future__ import annotations

import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME_SLUG = "the-learning-studio"
VERSION = "1.0.0"
THEME = ROOT / "wordpress-theme" / THEME_SLUG
OUTPUT = ROOT / "dist" / f"{THEME_SLUG}-{VERSION}.zip"
EXCLUDED_NAMES = {".DS_Store", "Thumbs.db"}
EXCLUDED_SUFFIXES = {".log", ".tmp", ".swp"}


def package_files() -> list[Path]:
    files = []
    for path in THEME.rglob("*"):
        if not path.is_file() or path.name in EXCLUDED_NAMES or path.suffix in EXCLUDED_SUFFIXES:
            continue
        if any(part in {"node_modules", "tests", ".git", ".github"} for part in path.parts):
            continue
        files.append(path)
    return sorted(files)


def main() -> None:
    required = (THEME / "style.css", THEME / "index.php")
    missing = [str(path) for path in required if not path.is_file()]
    if missing:
        raise SystemExit("Missing required classic-theme files: " + ", ".join(missing))

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.unlink(missing_ok=True)
    with zipfile.ZipFile(OUTPUT, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in package_files():
            archive.write(path, Path(THEME_SLUG) / path.relative_to(THEME))

    if not zipfile.is_zipfile(OUTPUT):
        raise SystemExit(f"Invalid ZIP produced: {OUTPUT}")
    print(f"Built {OUTPUT.relative_to(ROOT)} ({OUTPUT.stat().st_size:,} bytes)")


if __name__ == "__main__":
    main()
