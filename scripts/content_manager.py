#!/usr/bin/env python3
"""Interactive content manager for The Learning Studio.

Run with:
    python3 scripts/content_manager.py

Use the menu to add subjects, lessons/blog posts, or simple pages. The tool
updates data/*.json and can rebuild the generated static site for you.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "data"
SUBJECTS_FILE = DATA / "subjects.json"
LESSONS_FILE = DATA / "lessons.json"
PAGES_FILE = DATA / "pages.json"
BUILD_SCRIPT = ROOT / "scripts" / "build_site.py"


def slugify(value: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", value.lower()).strip("-")
    return slug or "untitled"


def load_json(path: Path) -> list[dict]:
    return json.loads(path.read_text(encoding="utf-8"))


def save_json(path: Path, data: list[dict]) -> None:
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def ask(prompt: str, required: bool = False, default: str = "") -> str:
    suffix = ""
    if default:
        suffix = f" [{default}]"
    while True:
        value = input(f"{prompt}{suffix}: ").strip()
        if not value and default:
            return default
        if value or not required:
            return value
        print("This field is required.")


def ask_yes_no(prompt: str, default: bool = False) -> bool:
    default_label = "Y/n" if default else "y/N"
    value = input(f"{prompt} ({default_label}): ").strip().lower()
    if not value:
        return default
    return value in {"y", "yes"}


def ask_paragraphs() -> list[str]:
    print("Enter lesson/blog body paragraphs. Leave blank when finished.")
    paragraphs: list[str] = []
    while True:
        paragraph = ask(f"Paragraph {len(paragraphs) + 1}")
        if not paragraph:
            break
        paragraphs.append(paragraph)
    return paragraphs


def ask_quiz() -> list[dict]:
    print("Enter optional quiz questions. Leave the question blank when finished.")
    quiz: list[dict] = []
    while True:
        question = ask(f"Quiz question {len(quiz) + 1}")
        if not question:
            break
        answer = ask("Answer", required=True)
        quiz.append({"question": question, "answer": answer})
    return quiz


def choose_subject(subjects: list[dict]) -> dict:
    print("\nAvailable subjects:")
    for index, subject in enumerate(subjects, start=1):
        print(f"  {index}. {subject['name']} ({subject['slug']})")
    while True:
        value = ask("Select subject number", required=True)
        if value.isdigit() and 1 <= int(value) <= len(subjects):
            return subjects[int(value) - 1]
        print("Please enter a valid subject number.")


def add_subject() -> None:
    subjects = load_json(SUBJECTS_FILE)
    print("\nAdd subject")
    name = ask("Subject name", required=True)
    slug = ask("Slug", default=slugify(name), required=True)
    category = ask("Category", required=True)
    description = ask("Description", required=True)
    lessons = int(ask("Lesson count", default="0") or "0")
    featured = ask_yes_no("Show on homepage/popular sections?", default=True)
    image = ask("Optional image URL or /assets path")

    if any(subject["slug"] == slug for subject in subjects):
        print(f"A subject with slug '{slug}' already exists. Nothing was added.")
        return

    subject = {
        "slug": slug,
        "name": name,
        "category": category,
        "lessons": lessons,
        "featured": featured,
        "description": description,
    }
    if image:
        subject["image"] = image
    subjects.append(subject)
    save_json(SUBJECTS_FILE, subjects)
    print(f"Added subject: {name}")


def add_lesson() -> None:
    subjects = load_json(SUBJECTS_FILE)
    lessons = load_json(LESSONS_FILE)
    print("\nAdd lesson / blog post / video")
    selected = choose_subject(subjects)
    title = ask("Lesson/blog title", required=True)
    slug = ask("Slug", default=slugify(title), required=True)
    duration = ask("Duration label", default="8 min read", required=True)
    excerpt = ask("Short description / excerpt", required=True)
    youtube_url = ask("Optional YouTube URL")
    image = ask("Optional image URL or /assets path")
    featured = ask_yes_no("Feature this lesson?", default=True)
    body = ask_paragraphs()
    if not body:
        body = [excerpt]
    quiz = ask_quiz()

    if any(lesson["slug"] == slug for lesson in lessons):
        print(f"A lesson with slug '{slug}' already exists. Nothing was added.")
        return

    lesson = {
        "slug": slug,
        "title": title,
        "subject": selected["category"],
        "subjectSlug": selected["slug"],
        "duration": duration,
        "featured": featured,
        "excerpt": excerpt,
        "body": body,
        "quiz": quiz,
    }
    if youtube_url:
        lesson["youtubeUrl"] = youtube_url
    if image:
        lesson["image"] = image
    lessons.append(lesson)
    save_json(LESSONS_FILE, lessons)
    print(f"Added lesson/blog post: {title}")


def add_page() -> None:
    pages = load_json(PAGES_FILE)
    print("\nAdd simple page")
    title = ask("Page title", required=True)
    slug = ask("Slug", default=slugify(title), required=True)
    description = ask("Meta description / lead", required=True)
    eyebrow = ask("Eyebrow label", default="PAGE", required=True)
    heading = ask("Page heading", default=title, required=True)
    body = ask("Body text", required=True)
    form = "contact" if ask_yes_no("Include contact form?", default=False) else ""

    if any(page["slug"] == slug for page in pages):
        print(f"A page with slug '{slug}' already exists. Nothing was added.")
        return

    page = {
        "slug": slug,
        "title": title,
        "description": description,
        "eyebrow": eyebrow,
        "heading": heading,
        "body": body,
    }
    if form:
        page["form"] = form
    pages.append(page)
    save_json(PAGES_FILE, pages)
    print(f"Added page: {title}")


def list_content() -> None:
    subjects = load_json(SUBJECTS_FILE)
    lessons = load_json(LESSONS_FILE)
    pages = load_json(PAGES_FILE)
    print("\nSubjects")
    for subject in subjects:
        print(f"- {subject['name']} -> /subjects/{subject['slug']}/")
    print("\nLessons / blog posts")
    for lesson in lessons:
        media = " video" if lesson.get("youtubeUrl") else ""
        print(f"- {lesson['title']} -> /lessons/{lesson['slug']}/{media}")
    print("\nPages")
    for page in pages:
        print(f"- {page['title']} -> /{page['slug']}/")


def rebuild_site() -> None:
    subprocess.run([sys.executable, str(BUILD_SCRIPT)], cwd=ROOT, check=True)
    print("Website rebuilt successfully.")


def main() -> None:
    menu = {
        "1": ("Add subject", add_subject),
        "2": ("Add lesson / blog post / video", add_lesson),
        "3": ("Add simple page", add_page),
        "4": ("List current content", list_content),
        "5": ("Rebuild website", rebuild_site),
        "6": ("Exit", None),
    }
    while True:
        print("\nThe Learning Studio content manager")
        for key, (label, _) in menu.items():
            print(f"  {key}. {label}")
        choice = ask("Choose an option", required=True)
        if choice == "6":
            break
        action = menu.get(choice, (None, None))[1]
        if action is None:
            print("Please choose a valid option.")
            continue
        action()
        if choice in {"1", "2", "3"} and ask_yes_no("Rebuild website now?", default=True):
            rebuild_site()


if __name__ == "__main__":
    main()
