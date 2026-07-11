#!/usr/bin/env python3
"""Build The Learning Studio static website from data JSON and templates."""
from __future__ import annotations

import html
import json
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
TEMPLATES = ROOT / "templates"


def load_json(name: str):
    return json.loads((DATA / name).read_text(encoding="utf-8"))


def tpl(name: str) -> str:
    return (TEMPLATES / name).read_text(encoding="utf-8")


def esc(value) -> str:
    return html.escape(str(value), quote=True)


def render(template: str, **values: str) -> str:
    for key, value in values.items():
        template = template.replace("{{ " + key + " }}", str(value))
    return template


def write_page(path: str, title: str, description: str, content: str) -> None:
    out = ROOT / path
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(
        render(tpl("layout.html"), title=esc(title), description=esc(description), content=content.rstrip()),
        encoding="utf-8",
    )


def optional_image(item: dict, alt: str) -> str:
    image = item.get("image", "").strip()
    if not image:
        return ""
    return f'<img class="media-thumb" src="{esc(image)}" alt="{esc(alt)}">'


def youtube_embed_url(url: str) -> str:
    url = (url or "").strip()
    if "youtu.be/" in url:
        video_id = url.split("youtu.be/", 1)[1].split("?", 1)[0].split("/", 1)[0]
        return f"https://www.youtube.com/embed/{video_id}"
    if "watch?v=" in url:
        video_id = url.split("watch?v=", 1)[1].split("&", 1)[0]
        return f"https://www.youtube.com/embed/{video_id}"
    if "youtube.com/embed/" in url:
        return url
    return ""


def subject_card(subject: dict) -> str:
    return f"""      <a class="card" href="/subjects/{esc(subject['slug'])}/">
        {optional_image(subject, subject['name'])}
        <span class="letter script">{esc(subject['name'][0])}</span>
        <b class="script">{esc(subject['name'])}</b>
        <span class="muted">{esc(subject['description'])}</span>
        <span class="pill mono">{esc(subject['lessons'])} lessons · {esc(subject['category'])}</span>
      </a>"""


def lesson_card(lesson: dict) -> str:
    media_label = "VIDEO + NOTES" if lesson.get("youtubeUrl") else esc(lesson["duration"])
    return f"""      <a class="card" href="/lessons/{esc(lesson['slug'])}/">
        {optional_image(lesson, lesson['title'])}
        <span class="mono pill">{esc(lesson['subject'])}</span>
        <b class="script">{esc(lesson['title'])}</b>
        <span class="muted">{esc(lesson['excerpt'])}</span>
        <span class="pill mono">{media_label}</span>
      </a>"""


def cards(items: list[str]) -> str:
    return "\n".join(items)


def build_home(subjects: list[dict], lessons: list[dict]) -> None:
    featured_subjects = [s for s in subjects if s.get("featured")][:8]
    content = f"""    <section class="hero">
      <div class="wrap hero-grid">
        <div>
          <span class="eyebrow mono">A GLOBAL LEARNING LIBRARY</span>
          <h1 class="h1 script">Learn complex<br>subjects, simply.</h1>
          <p class="lead">Study business, AI, technology, health, finance, marketing, psychology and operations through clear lessons made for real understanding.</p>
          <form class="search" action="/subjects/">
            <input name="q" placeholder="Search subjects, lessons, topics..." aria-label="Search subjects">
            <button class="btn">Search</button>
          </form>
        </div>
        <div class="panel">
          <span class="mono pill">FEATURED PATH</span>
          <h2 class="script">Business &amp; Management</h2>
          <p class="muted">Start with core definitions, functions, examples, videos and a quick quiz.</p>
          <a class="btn" href="/subjects/business-management/">Start learning</a>
        </div>
      </div>
    </section>
    <section class="section">
      <div class="wrap">
        <div class="section-head">
          <div>
            <span class="mono">POPULAR SUBJECTS</span>
            <h2 class="h2 script">Choose what to learn</h2>
          </div>
          <a href="/subjects/">View all subjects →</a>
        </div>
        <div class="grid">
{cards([subject_card(s) for s in featured_subjects])}
        </div>
      </div>
    </section>
    <section class="section alt">
      <div class="wrap">
        <span class="mono">LATEST LESSONS</span>
        <h2 class="h2 script">New written notes and videos</h2>
        <div class="grid">
{cards([lesson_card(l) for l in lessons[:3]])}
        </div>
      </div>
    </section>
    <section class="section">
      <div class="wrap">
        <span class="mono">HOW IT WORKS</span>
        <h2 class="h2 script">Watch. Read. Understand. Test. Revise.</h2>
        <div class="steps">
          <div class="step"><h3 class="script">Watch</h3><p class="muted">Short, clear video lessons from our YouTube library.</p></div>
          <div class="step"><h3 class="script">Read</h3><p class="muted">Clean notes, definitions and structured explanations.</p></div>
          <div class="step"><h3 class="script">Understand</h3><p class="muted">Real examples, case studies and visual mind maps.</p></div>
          <div class="step"><h3 class="script">Test</h3><p class="muted">MCQs and quizzes to check what you have learned.</p></div>
          <div class="step"><h3 class="script">Revise</h3><p class="muted">Quick summaries for students and busy professionals.</p></div>
        </div>
      </div>
    </section>"""
    write_page("index.html", "Learn complex subjects, simply", "A global learning library with videos, notes, examples, mind maps and quizzes.", content)


def build_subjects(subjects: list[dict], lessons: list[dict]) -> None:
    content = f"""    <section class="page-hero">
      <div class="wrap">
        <span class="mono">BROWSE THE LIBRARY</span>
        <h1 class="h2 script">All subjects</h1>
        <p class="lead">Explore every subject in the library, grouped by category. Search or filter to find exactly what you want to learn.</p>
      </div>
    </section>
    <section class="section">
      <div class="wrap">
        <div class="grid">
{cards([subject_card(s) for s in subjects])}
        </div>
      </div>
    </section>"""
    write_page("subjects/index.html", "All subjects", "Explore every subject in the library, grouped by category.", content)

    for subject in subjects:
        related = [l for l in lessons if l.get("subjectSlug") == subject["slug"]]
        if not related:
            related = [{"slug": "what-is-management", "title": f"Introduction to {subject['name']}", "subject": subject["category"], "excerpt": "Start with key concepts, definitions and examples.", "duration": "8 min read"}]
        lesson_cards = cards([lesson_card(l) for l in related])
        hero_image = optional_image(subject, subject["name"])
        content = f"""    <section class="page-hero">
      <div class="wrap">
        <span class="mono">{esc(subject['category']).upper()}</span>
        <h1 class="h2 script">{esc(subject['name'])}</h1>
        <p class="lead">{esc(subject['description'])}</p>
        {hero_image}
      </div>
    </section>
    <section class="section">
      <div class="wrap">
        <div class="section-head">
          <div>
            <span class="mono">LESSONS</span>
            <h2 class="h2 script">Lessons in this subject</h2>
          </div>
          <a href="/blog/">Browse all lessons →</a>
        </div>
        <div class="grid">
{lesson_cards}
        </div>
      </div>
    </section>"""
        write_page(f"subjects/{subject['slug']}/index.html", subject["name"], subject["description"], content)


def build_blog(lessons: list[dict]) -> None:
    content = f"""    <section class="page-hero">
      <div class="wrap">
        <span class="mono">LESSON LIBRARY</span>
        <h1 class="h2 script">Blog &amp; Lessons</h1>
        <p class="lead">Every written lesson in one place — a structured library of clear, search-friendly explanations.</p>
      </div>
    </section>
    <section class="section">
      <div class="wrap">
        <div class="grid">
{cards([lesson_card(l) for l in lessons])}
        </div>
      </div>
    </section>"""
    write_page("blog/index.html", "Blog & Lessons", "Every written lesson in one place.", content)


def build_lessons(lessons: list[dict]) -> None:
    for lesson in lessons:
        paragraphs = "\n".join(f"        <p>{esc(p)}</p>" for p in lesson.get("body", []))
        quiz = "\n".join(f"          <div>{esc(q['question'])} <strong>{esc(q['answer'])}</strong></div>" for q in lesson.get("quiz", []))
        embed = youtube_embed_url(lesson.get("youtubeUrl", ""))
        video = f'<div class="video-frame"><iframe src="{esc(embed)}" title="{esc(lesson["title"])}" allowfullscreen loading="lazy"></iframe></div>' if embed else optional_image(lesson, lesson["title"])
        content = f"""    <section class="page-hero">
      <div class="wrap">
        <span class="eyebrow mono">LESSON · {esc(lesson['duration']).upper()}</span>
        <h1 class="h2 script">{esc(lesson['title'])}</h1>
        <p class="lead">{esc(lesson['excerpt'])}</p>
        <p><a href="/subjects/{esc(lesson['subjectSlug'])}/">{esc(lesson['subject'])}</a></p>
        {video}
      </div>
    </section>
    <article class="article">
      <h2 class="script">Simple explanation</h2>
{paragraphs}
      <h2 class="script">Quick quiz</h2>
      <div class="quiz">
{quiz}
      </div>
    </article>"""
        write_page(f"lessons/{lesson['slug']}/index.html", lesson["title"], lesson["excerpt"], content)


def build_simple_pages(pages: list[dict]) -> None:
    page_template = tpl("page.html")
    for page in pages:
        form = ""
        if page.get("form") == "contact":
            form = '<form class="grid"><input placeholder="Name"><input placeholder="Email"><textarea placeholder="Message" rows="6"></textarea><button class="btn">Send message</button></form>'
        body = f"      <p>{esc(page['body'])}</p>" + form
        content = render(
            page_template,
            eyebrow=esc(page["eyebrow"]),
            heading=esc(page["heading"]),
            lead=esc(page["description"]),
            body=body,
        ).rstrip()
        write_page(f"{page['slug']}/index.html", page["title"], page["description"], content)


def clean_generated_subject_and_lesson_pages(subjects: list[dict], lessons: list[dict]) -> None:
    expected_subjects = {s["slug"] for s in subjects}
    subjects_dir = ROOT / "subjects"
    if subjects_dir.exists():
        for child in subjects_dir.iterdir():
            if child.is_dir() and child.name not in expected_subjects:
                shutil.rmtree(child)
    expected_lessons = {l["slug"] for l in lessons}
    lessons_dir = ROOT / "lessons"
    if lessons_dir.exists():
        for child in lessons_dir.iterdir():
            if child.is_dir() and child.name not in expected_lessons:
                shutil.rmtree(child)


def main() -> None:
    subjects = load_json("subjects.json")
    lessons = load_json("lessons.json")
    pages = load_json("pages.json")
    clean_generated_subject_and_lesson_pages(subjects, lessons)
    build_home(subjects, lessons)
    build_subjects(subjects, lessons)
    build_blog(lessons)
    build_lessons(lessons)
    build_simple_pages(pages)


if __name__ == "__main__":
    main()
