# The Learning Studio

Static, SEO-friendly website for The Learning Studio.

## WordPress CMS version

A custom WordPress theme is available in `wordpress-theme/the-learning-studio/`. It adds a browser-based content workflow with:

- a **Lesson** content type for written, video, and combined lessons;
- a hierarchical **Subject** taxonomy;
- lesson duration, YouTube, featured, and quiz fields;
- dynamic home, lesson archive, subject directory, subject landing, and lesson templates; and
- a WP-CLI importer for the existing subjects, lessons, and pages.

See `wordpress-theme/README.md` for architecture, installation, editing, migration, testing, limitations, and release instructions. Build the upload-ready archive with `python3 scripts/build_wordpress_theme.py`; it is written to `dist/the-learning-studio-1.0.0.zip`. The static generator documented below remains available and is not required after migrating to WordPress.

## Content structure

Use the data files as the source of truth:

- `data/subjects.json` — add or edit website subjects.
- `data/lessons.json` — add or edit videos / written lessons.

Do not manually maintain generated page lists. After changing the data files, run:

```bash
python3 scripts/build_site.py
```

The build script regenerates:

- `index.html`
- `subjects/index.html`
- `subjects/<subject-slug>/index.html`
- `blog/index.html`
- `lessons/<lesson-slug>/index.html`
- supporting pages such as `about/`, `contact/`, `privacy/`, and `terms/`

## Templates and styling

- `templates/layout.html` — shared HTML shell, navigation, footer, metadata.
- `templates/page.html` — reusable simple-page template.
- `assets/site.css` — shared visual design system.


## Content manager menu

For a guided menu with forms, run:

```bash
python3 scripts/content_manager.py
```

The menu lets you:

1. Add a subject.
2. Add a lesson / blog post / video.
3. Add a simple page.
4. List current content.
5. Rebuild the website.

When you add a lesson or blog post, the form asks for the current structure fields: title, slug, related subject, duration, short description, optional YouTube URL, optional image URL/path, body paragraphs, and optional quiz questions.

Simple pages are stored in `data/pages.json`.

## Adding a new subject

1. Add the subject to `data/subjects.json`.
2. Run `python3 scripts/build_site.py`.
3. Commit the updated data file and generated HTML pages.

## Adding a new video or lesson

1. Add the lesson to `data/lessons.json`.
2. Set `subjectSlug` to the related subject slug from `data/subjects.json`.
3. Run `python3 scripts/build_site.py`.
4. Commit the updated data file and generated HTML pages.
