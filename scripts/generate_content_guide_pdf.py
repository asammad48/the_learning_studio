#!/usr/bin/env python3
"""Generate the content-editing guide PDF (docs/content-guide.pdf).

Covers: importing the theme in WordPress Playground, and adding a Lesson,
a Subject, and a Blog post, both through the WordPress admin and through
the JSON pipeline. Pulls real examples straight from data/*.json and the
committed Playground blueprint, so the guide never drifts from the repo.

Run with:
    python scripts/generate_content_guide_pdf.py
"""
from __future__ import annotations

import json
import textwrap
import urllib.parse
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
OUT = ROOT / "docs" / "content-guide.pdf"

INK = (22, 22, 22)
ACCENT = (29, 95, 167)
MUTED = (100, 100, 100)
CODE_BG = (245, 245, 242)
LINE = (230, 230, 223)

REPO_URL = "https://github.com/asammad48/the_learning_studio"
PLAYGROUND_URL = "https://playground.wordpress.net/"


def build_playground_link() -> str:
    blueprint = json.loads((ROOT / "wordpress-theme" / "playground-blueprint.json").read_text(encoding="utf-8"))
    encoded = urllib.parse.quote(json.dumps(blueprint, separators=(",", ":")))
    return f"{PLAYGROUND_URL}#{encoded}"


class Guide(FPDF):
    def header(self) -> None:
        if self.page_no() == 1:
            return
        self.set_font("Helvetica", size=9)
        self.set_text_color(*MUTED)
        self.cell(0, 8, "The Learning Studio - Content Guide", align="L")
        self.set_x(-40)
        self.cell(30, 8, f"Page {self.page_no()}", align="R")
        self.ln(12)
        self.set_draw_color(*LINE)
        self.line(15, 20, 195, 20)
        self.set_text_color(*INK)

    def footer(self) -> None:
        pass

    def h1(self, text: str) -> None:
        self.set_font("Helvetica", "B", 20)
        self.set_text_color(*ACCENT)
        self.cell(0, 12, text, new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)
        self.ln(2)

    def h2(self, text: str) -> None:
        self.ln(2)
        self.set_font("Helvetica", "B", 14)
        self.set_text_color(*ACCENT)
        self.cell(0, 10, text, new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)
        self.ln(1)

    def h3(self, text: str) -> None:
        self.set_font("Helvetica", "B", 11.5)
        self.set_text_color(*INK)
        self.cell(0, 8, text, new_x="LMARGIN", new_y="NEXT")
        self.ln(0.5)

    def body(self, text: str) -> None:
        self.set_font("Helvetica", size=10.5)
        self.set_text_color(*INK)
        self.set_x(self.l_margin)
        self.multi_cell(180, 6, text, markdown=True, align="L", new_x="LMARGIN", new_y="NEXT")
        self.ln(1)

    def bullet(self, text: str) -> None:
        self.set_font("Helvetica", size=10.5)
        self.set_text_color(*INK)
        self.set_x(self.l_margin)
        indent = 5
        self.cell(indent, 6, "-")
        self.set_x(self.l_margin + indent)
        self.multi_cell(180 - indent, 6, text, markdown=True, align="L", new_x="LMARGIN", new_y="NEXT")

    def numbered(self, n: int, text: str) -> None:
        self.set_font("Helvetica", size=10.5)
        self.set_text_color(*INK)
        self.set_x(self.l_margin)
        indent = 7
        self.cell(indent, 6, f"{n}.")
        self.set_x(self.l_margin + indent)
        self.multi_cell(180 - indent, 6, text, markdown=True, align="L", new_x="LMARGIN", new_y="NEXT")

    def code_block(self, code: str, wrap_width: int = 94) -> None:
        self.set_font("Courier", size=8.5)
        self.set_text_color(*INK)
        self.set_fill_color(*CODE_BG)
        self.set_draw_color(*LINE)
        lines: list[str] = []
        for raw_line in code.split("\n"):
            wrapped = textwrap.wrap(
                raw_line, width=wrap_width, subsequent_indent="    ",
                break_long_words=True, break_on_hyphens=False,
            )
            lines.extend(wrapped or [""])
        pad = 3
        line_h = 4.3
        block_h = pad * 2 + line_h * len(lines)
        if self.get_y() + block_h > 277:
            self.add_page()
        x0, y0 = self.get_x(), self.get_y()
        self.rect(x0, y0, 180, block_h, style="DF")
        self.set_xy(x0 + pad, y0 + pad)
        for line in lines:
            self.set_x(x0 + pad)
            self.cell(174, line_h, line, new_x="LMARGIN", new_y="NEXT")
        self.set_xy(x0, y0 + block_h + 3)

    def link_line(self, label: str, url: str) -> None:
        """A label followed by the URL as visible, wrapped text. Only use
        for URLs short enough to read on the page."""
        self.set_font("Helvetica", size=10.5)
        self.set_x(self.l_margin)
        self.multi_cell(
            180, 6, f"**{label}:** [{url}]({url})", markdown=True, align="L",
            new_x="LMARGIN", new_y="NEXT", wrapmode="CHAR",
        )

    def click_label(self, label: str, url: str, note: str = "") -> None:
        """A short, clickable label linking to a URL too long to print
        legibly in full (e.g. an encoded Playground blueprint link)."""
        self.set_font("Helvetica", "B", 10.5)
        self.set_text_color(*ACCENT)
        self.set_x(self.l_margin)
        self.cell(0, 7, label, link=url, new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*INK)
        if note:
            self.set_font("Helvetica", "I", 9)
            self.set_text_color(*MUTED)
            self.multi_cell(180, 5, note, align="L", new_x="LMARGIN", new_y="NEXT")
            self.set_text_color(*INK)


ASCII_REPLACEMENTS = {
    "—": "-", "–": "-", "‘": "'", "’": "'",
    "“": '"', "”": '"', "…": "...",
}


def _ascii_safe(value):
    if isinstance(value, str):
        for src, dest in ASCII_REPLACEMENTS.items():
            value = value.replace(src, dest)
        return value
    if isinstance(value, list):
        return [_ascii_safe(v) for v in value]
    if isinstance(value, dict):
        return {k: _ascii_safe(v) for k, v in value.items()}
    return value


def json_snippet(obj: dict, keys: list[str] | None = None) -> str:
    """Pretty-print an example for the PDF's Courier code blocks, which use
    fpdf2's core (non-Unicode) font - so typographic punctuation is swapped
    for plain ASCII here. The real data/*.json files are untouched."""
    data = {k: obj[k] for k in keys} if keys else obj
    return json.dumps(_ascii_safe(data), indent=2, ensure_ascii=True)


def main() -> None:
    subjects = json.loads((DATA / "subjects.json").read_text(encoding="utf-8"))
    lessons = json.loads((DATA / "lessons.json").read_text(encoding="utf-8"))
    posts = json.loads((DATA / "posts.json").read_text(encoding="utf-8"))

    example_subject = next(s for s in subjects if s["slug"] == "project-management")
    example_lesson = next(l for l in lessons if l["slug"] == "what-is-project-management")
    example_post = next(p for p in posts if p["slug"] == "why-we-built-the-learning-studio")

    playground_link = build_playground_link()

    pdf = Guide()
    pdf.set_auto_page_break(auto=True, margin=20)
    pdf.set_margins(15, 15, 15)

    # --- Cover page ---
    pdf.add_page()
    pdf.ln(50)
    pdf.set_font("Helvetica", "B", 30)
    pdf.set_text_color(*INK)
    pdf.cell(0, 16, "The Learning Studio", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "B", 16)
    pdf.set_text_color(*ACCENT)
    pdf.cell(0, 12, "Content Guide", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(6)
    pdf.set_font("Helvetica", size=11)
    pdf.set_text_color(*MUTED)
    pdf.multi_cell(
        0, 7,
        "Importing the theme in WordPress Playground, and adding a Lesson, "
        "a Subject, and a Blog post - through the WordPress admin and "
        "through the JSON content pipeline.",
        align="C",
    )
    pdf.ln(20)
    pdf.set_font("Helvetica", size=10)
    pdf.set_text_color(*INK)
    pdf.cell(0, 6, "Repository:", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_text_color(*ACCENT)
    pdf.cell(0, 6, REPO_URL, align="C", link=REPO_URL, new_x="LMARGIN", new_y="NEXT")

    # --- Table of contents ---
    pdf.add_page()
    pdf.h1("Contents")
    pdf.ln(4)
    toc = [
        "1. Importing the theme in WordPress Playground",
        "2. Adding a Lesson",
        "3. Adding a Subject",
        "4. Adding a Blog post",
        "5. Reference and links",
    ]
    for item in toc:
        pdf.set_font("Helvetica", size=12)
        pdf.cell(0, 9, item, new_x="LMARGIN", new_y="NEXT")

    # ============================================================
    # PART 1 - Playground import
    # ============================================================
    pdf.add_page()
    pdf.h1("1. Importing the theme in WordPress Playground")

    pdf.h2("Method A - One-click Blueprint (recommended)")
    pdf.body(
        "WordPress Playground runs a full WordPress site in your browser, with no "
        "server or install needed. A Blueprint is a small JSON file that tells "
        "Playground what to set up automatically: install the theme, import the "
        "sample content, and configure Pages/menus/Reading settings."
    )
    pdf.numbered(1, f"Open [{PLAYGROUND_URL}]({PLAYGROUND_URL})")
    pdf.numbered(2, "Click **Blueprint** in the toolbar, then paste the JSON below (or use the direct link at the end of this section).")
    pdf.numbered(3, "Playground installs the theme straight from GitHub, imports every Subject/Lesson/Page/Post, and runs the setup wizard automatically.")
    pdf.ln(2)
    pdf.h3("Blueprint JSON (wordpress-theme/playground-blueprint.json)")
    pdf.code_block((ROOT / "wordpress-theme" / "playground-blueprint.json").read_text(encoding="utf-8").rstrip())
    pdf.ln(2)
    pdf.body("**What each step does:**")
    pdf.bullet("`installTheme` with a `git:directory` resource pulls just the theme subfolder out of the repo (a monorepo) and activates it - no build step needed.")
    pdf.bullet("`runPHP` calls two theme functions directly: `tls_import_json_content()` (imports Subjects, Lessons, Pages, and Posts from `legacy-data/`) and `tls_run_site_setup()` (creates the required Pages, builds and assigns the 4 navigation menus, and configures Reading settings).")
    pdf.ln(2)
    pdf.click_label(">> Click to open this Blueprint directly in WordPress Playground", playground_link)

    pdf.add_page()
    pdf.h2("Method B - Manual theme upload")
    pdf.body("Use this on a real WordPress site, or if you would rather control each step by hand.")
    pdf.numbered(1, "Build the theme zip: `python scripts/build_wordpress_theme.py` (produces `dist/the-learning-studio-1.0.0.zip`).")
    pdf.numbered(2, "In wp-admin: **Appearance -> Themes -> Add New Theme -> Upload Theme**, choose the zip, then **Activate**.")
    pdf.numbered(3, "Go to **Tools -> Learning Studio Setup**. Check the boxes you want (reassign menus / configure Reading settings), then **Run setup**. This creates the required Pages (Home, Subjects, Blog, About, Contact, Privacy, Terms) and the 4 navigation menus.")
    pdf.numbered(4, "Go to **Tools -> Learning Studio Import**. Leave **Dry run** checked first to preview, then uncheck it and run for real. Check **Update existing** only if you want re-imports to overwrite content that already exists.")
    pdf.ln(3)
    pdf.h3("WP-CLI alternative")
    pdf.code_block("wp tls import --source=/absolute/path/to/the_learning_studio --dry-run --update-existing")
    pdf.body("Drop the dry-run flag to write for real; drop the update-existing flag to only create new content and leave existing content untouched.")

    # ============================================================
    # PART 2 - Lessons
    # ============================================================
    pdf.add_page()
    pdf.h1("2. Adding a Lesson")

    pdf.h2("Through the WordPress admin")
    pdf.numbered(1, "Go to **Lessons -> Add New Lesson**.")
    pdf.numbered(2, "**Title:** the lesson's title, e.g. \"What Is Project Management?\"")
    pdf.numbered(3, "**Content editor:** the full lesson body.")
    pdf.numbered(4, "**Excerpt:** a one to two sentence summary shown on lesson cards (enable the Excerpt panel from Screen Options if it is hidden).")
    pdf.numbered(5, "**Featured Image:** set in the sidebar - shown on cards and the lesson page.")
    pdf.numbered(6, "**Subjects** panel (sidebar): check the Subject this lesson belongs to.")
    pdf.numbered(7, "**Lesson Details** box (sidebar):")
    pdf.bullet("**Lesson format** - Written lesson / Video lesson / Video + written lesson")
    pdf.bullet("**Duration / read time** - free text, e.g. \"6 min read\"")
    pdf.bullet("**YouTube URL** - a normal youtube.com/watch?v=... link; the theme embeds it automatically")
    pdf.bullet("**Feature this lesson** - shows it in the homepage Featured Lessons section")
    pdf.bullet("**Featured order priority** - higher numbers appear first among featured lessons")
    pdf.numbered(8, "**Quick Quiz** box (main column): click **Add question** for each question/answer pair. A row only saves once both fields are filled; visitors reveal the answer by clicking the question on the lesson page.")
    pdf.numbered(9, "Click **Publish**.")

    pdf.add_page()
    pdf.h2("Through the JSON pipeline (source of truth)")
    pdf.body(
        "This is how the theme's sample content is built, and how bulk or "
        "repeatable content should be added. It keeps the static site and the "
        "WordPress import in sync from one file."
    )
    pdf.numbered(1, "Edit `data/lessons.json` - append an object (or run `python scripts/content_manager.py` and choose \"Add lesson / blog post / video\").")
    pdf.numbered(2, "**Important:** the `subject` field must equal the associated Subject's `category` field, not its `name`. `subjectSlug` must match the Subject's `slug`.")
    pdf.numbered(3, "Copy the same entry into `wordpress-theme/the-learning-studio/legacy-data/lessons.json` so the two files stay byte-identical.")
    pdf.numbered(4, "Run `python scripts/build_site.py` to regenerate the static HTML pages.")
    pdf.numbered(5, "Run `python scripts/build_wordpress_theme.py` to rebuild the theme zip with the updated `legacy-data/`.")
    pdf.numbered(6, "Import into WordPress via **Tools -> Learning Studio Import**, `wp tls import`, or the Playground blueprint from Part 1.")
    pdf.ln(2)
    pdf.h3("Real example - data/lessons.json")
    pdf.code_block(json_snippet(example_lesson))

    # ============================================================
    # PART 3 - Subjects
    # ============================================================
    pdf.add_page()
    pdf.h1("3. Adding a Subject")

    pdf.h2("Through the WordPress admin")
    pdf.numbered(1, "Go to **Lessons -> Subjects -> Add New Subject**.")
    pdf.numbered(2, "**Name:** e.g. \"Project Management\". The **Slug** auto-generates from the name, or set it manually.")
    pdf.numbered(3, "**Description:** shown on the subject's card and its archive page.")
    pdf.numbered(4, "**Subject group:** groups related subjects together on the **/subjects/** directory page, e.g. \"Business & Management\".")
    pdf.numbered(5, "**Image:** click **Select image** to choose or upload from the Media Library, or set a **Fallback image URL** for when no Media Library image is chosen.")
    pdf.numbered(6, "**Featured subject:** shows it in the homepage Featured Subjects section.")
    pdf.numbered(7, "**Featured order priority:** higher numbers appear first among featured subjects.")
    pdf.numbered(8, "Click **Add New Subject**.")

    pdf.add_page()
    pdf.h2("Through the JSON pipeline")
    pdf.numbered(1, "Edit `data/subjects.json` - append an object (or run `python scripts/content_manager.py` and choose \"Add subject\").")
    pdf.numbered(2, "`category` becomes both the Subject group shown on the directory page, and the value every associated Lesson's `subject` field must match.")
    pdf.numbered(3, "`lessons` is a manually maintained display count, not auto-computed from real Lesson entries.")
    pdf.numbered(4, "`image` is an absolute site path, e.g. `/assets/generated/subjects/your-slug.png`. Generate on-brand cover images with `python scripts/generate_cover_images.py`, or point it at any image already under `/assets/`.")
    pdf.numbered(5, "Copy the entry into `wordpress-theme/the-learning-studio/legacy-data/subjects.json`, then rebuild and import exactly as with Lessons.")
    pdf.ln(2)
    pdf.h3("Real example - data/subjects.json")
    pdf.code_block(json_snippet(example_subject))

    # ============================================================
    # PART 4 - Blog posts
    # ============================================================
    pdf.add_page()
    pdf.h1("4. Adding a Blog post")
    pdf.body(
        "Blog posts are genuine WordPress Posts (`post_type=post`) - a "
        "separate content type from Lessons. The **Blog** menu link is "
        "WordPress's own Posts page."
    )

    pdf.h2("Through the WordPress admin")
    pdf.numbered(1, "Go to **Posts -> Add New**.")
    pdf.numbered(2, "**Title** and the **content editor** for the post body.")
    pdf.numbered(3, "**Excerpt:** a short summary shown on the Blog listing.")
    pdf.numbered(4, "**Featured Image:** the cover shown on the Blog listing and the single post page.")
    pdf.numbered(5, "**Categories** / **Tags** panel (sidebar): assign existing ones or create new ones inline.")
    pdf.numbered(6, "Click **Publish**.")

    pdf.h2("Through the JSON pipeline")
    pdf.numbered(1, "Edit `data/posts.json` (optional file - older imports without it still work).")
    pdf.numbered(2, "`categories` and `tags` are arrays of plain names; the importer creates them if they do not already exist.")
    pdf.numbered(3, "`image` (optional) is copied into the Media Library and set as the real featured image on import - a plain URL is not enough for Posts, since `has_post_thumbnail()` needs a real attachment.")
    pdf.numbered(4, "Copy the entry into `wordpress-theme/the-learning-studio/legacy-data/posts.json`, then import via **Tools -> Learning Studio Import**, `wp tls import`, or the Playground blueprint.")
    pdf.body("Note: the static (non-WordPress) site does not have a separate blog-post concept - its **/blog/** page lists every Lesson instead. `posts.json` is WordPress-only.")
    pdf.ln(2)
    pdf.h3("Real example - data/posts.json")
    pdf.code_block(json_snippet(example_post))

    # ============================================================
    # PART 5 - Reference
    # ============================================================
    pdf.add_page()
    pdf.h1("5. Reference and links")

    pdf.h2("Links")
    pdf.link_line("Repository", REPO_URL)
    pdf.link_line("Theme folder", REPO_URL + "/tree/main/wordpress-theme/the-learning-studio")
    pdf.link_line("Playground blueprint file", REPO_URL + "/blob/main/wordpress-theme/playground-blueprint.json")
    pdf.link_line("WordPress Playground", PLAYGROUND_URL)
    pdf.click_label(">> One-click import link (Playground Blueprint)", playground_link)

    pdf.h2("Admin screens")
    pdf.bullet("**Tools -> Learning Studio Setup** - Pages, menus, Reading settings")
    pdf.bullet("**Tools -> Learning Studio Import** - imports data/*.json content")
    pdf.bullet("**Lessons -> All Lessons** / **Lessons -> Subjects**")
    pdf.bullet("**Posts -> All Posts** - Blog posts")
    pdf.bullet("**Appearance -> Customize** - hero, panel, subjects/lessons section controls")

    pdf.h2("Useful commands")
    pdf.code_block(
        "python scripts/content_manager.py          # interactive content editor\n"
        "python scripts/build_site.py                # rebuild the static site\n"
        "python scripts/build_wordpress_theme.py      # rebuild the theme zip\n"
        "python scripts/generate_cover_images.py      # regenerate on-brand images\n"
        "wp tls import --source=<path> [--dry-run] [--update-existing]"
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(f"Wrote {OUT.relative_to(ROOT)} ({OUT.stat().st_size:,} bytes)")


if __name__ == "__main__":
    main()
