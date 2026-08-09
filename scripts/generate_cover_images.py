#!/usr/bin/env python3
"""Generate on-brand cover images for Subjects and Blog posts.

Produces simple, original PNG cover graphics (solid ink background, accent
geometry, white title text) using the theme's own palette from theme.json.
No external downloads or stock photography involved.

Run with:
    python scripts/generate_cover_images.py
"""
from __future__ import annotations

import json
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
THEME = ROOT / "wordpress-theme" / "the-learning-studio"

# Images are written to both the static site's root-level assets/ (referenced
# by data/*.json via absolute "/assets/..." paths, matching how /assets/site.css
# is already linked) and the theme's own assets/ (so the WordPress theme ships
# them too, since Playground/GitHub installs only pull the theme subfolder).
OUTPUT_ROOTS = [ROOT, THEME]

INK = (22, 22, 22)
WHITE = (255, 255, 255)
ACCENT = (29, 95, 167)
CREAM = (251, 251, 248)

WIDTH, HEIGHT = 1200, 675

FONT_BOLD = "C:/Windows/Fonts/segoeuib.ttf"
FONT_REGULAR = "C:/Windows/Fonts/segoeui.ttf"
FONT_MONO = "C:/Windows/Fonts/consola.ttf"


def wrap_text(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.FreeTypeFont, max_width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        candidate = f"{current} {word}".strip()
        if draw.textlength(candidate, font=font) <= max_width:
            current = candidate
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


def make_cover(eyebrow: str, title: str, relative_path: str, letter: str) -> None:
    img = Image.new("RGB", (WIDTH, HEIGHT), INK)
    draw = ImageDraw.Draw(img)

    # Large translucent-feeling accent circle, bottom-right, for visual interest.
    circle_r = 420
    draw.ellipse(
        [WIDTH - circle_r + 160, HEIGHT - circle_r + 160, WIDTH + circle_r - 160, HEIGHT + circle_r - 160],
        fill=(29, 60, 95),
    )
    draw.ellipse(
        [WIDTH - 260, HEIGHT - 260, WIDTH + 260, HEIGHT + 260],
        fill=ACCENT,
    )

    # Big letter mark, matching the site's ".letter script" card treatment.
    letter_font = ImageFont.truetype(FONT_BOLD, 220)
    draw.text((72, 60), letter.upper(), font=letter_font, fill=ACCENT)

    eyebrow_font = ImageFont.truetype(FONT_MONO, 30)
    draw.text((76, 330), eyebrow.upper(), font=eyebrow_font, fill=ACCENT)

    title_font = ImageFont.truetype(FONT_BOLD, 64)
    lines = wrap_text(draw, title, title_font, WIDTH - 160)[:3]
    y = 380
    for line in lines:
        draw.text((76, y), line, font=title_font, fill=WHITE)
        y += 78

    accent_bar_height = 14
    draw.rectangle([0, HEIGHT - accent_bar_height, WIDTH, HEIGHT], fill=ACCENT)

    for output_root in OUTPUT_ROOTS:
        out_path = output_root / relative_path
        out_path.parent.mkdir(parents=True, exist_ok=True)
        img.save(out_path, "PNG", optimize=True)
        print(f"Wrote {out_path.relative_to(ROOT)}")


def main() -> None:
    subjects = json.loads((DATA / "subjects.json").read_text(encoding="utf-8"))
    posts = json.loads((DATA / "posts.json").read_text(encoding="utf-8"))

    for subject in subjects:
        make_cover(
            eyebrow=subject["category"],
            title=subject["name"],
            relative_path=f"assets/generated/subjects/{subject['slug']}.png",
            letter=subject["name"][0],
        )

    for post in posts:
        make_cover(
            eyebrow="From the studio",
            title=post["title"],
            relative_path=f"assets/generated/posts/{post['slug']}.png",
            letter=post["title"][0],
        )


if __name__ == "__main__":
    main()
