# The Learning Studio WordPress theme

## Architecture and requirements

**The Learning Studio** is a classic WordPress theme with `theme.json` editor settings. Its theme root is `wordpress-theme/the-learning-studio/`. It requires WordPress 6.4+ and PHP 8.0+ and has no npm, Composer, or production compilation requirement.

The original static JSON/Python site remains at the repository root. WordPress does not need the static generator after content migration.

## Install the production ZIP

1. Build the package from the repository root with `python3 scripts/build_wordpress_theme.py`.
2. In WordPress, open **Appearance → Themes → Add New → Upload Theme**.
3. Upload `dist/the-learning-studio-1.0.0.zip`, install, and activate it.
4. Open **Settings → Permalinks** and click **Save Changes** if custom URLs do not appear immediately. Activation also flushes rewrite rules.
5. Configure the site name, tagline, logo, homepage, posts page, and menus.

The ZIP contains one `the-learning-studio/` folder with `style.css` and `index.php` directly inside it. No development server or asset build is needed.

## Editable WordPress areas

- **Lessons → Add New Lesson:** title, editor content, excerpt, featured image, subject, written/video format, duration, YouTube URL, featured status, and up to five quiz answers.
- **Lessons → Subjects:** hierarchical subjects, description, subject group, image URL, and featured status. WordPress calculates lesson counts.
- **Posts:** editorial news and normal blog articles.
- **Pages:** About, Contact, policies, and other permanent content.
- **Appearance → Customize → Homepage content:** hero and feature-panel copy.
- **Appearance → Customize → Site Identity:** title, tagline, and custom logo.
- **Appearance → Menus:** Primary navigation, Footer: Explore, Footer: Studio, and Footer: Legal.

Featured subjects and lessons are prioritized on the homepage. When fewer items are featured than the available homepage positions, the theme fills the remaining subject positions alphabetically and the remaining lesson positions with the latest published lessons.

Menu item descriptions are displayed beneath their labels in these four theme locations. If the Description field is hidden in the classic menu editor, enable it from **Screen Options**.
The Primary navigation also supports nested menu items with keyboard-accessible submenu controls and responsive dropdown styling.

The public templates include the homepage, posts page, lesson archive, individual lesson, subject directory, subject archive, standard archives, search, comments, and 404 states.

## Import bundled legacy content

After activation, open **Tools → Learning Studio Import** and select **Import bundled content**. The action is restricted to administrators and protected by a nonce. Existing subjects, lessons, and pages with matching slugs are updated.

WP-CLI is optional. From a WordPress installation, import bundled data with:

```bash
wp tls import --source=/path/to/wp-content/themes/the-learning-studio/legacy-data
```

Or import directly from this repository with:

```bash
wp tls import --source=/absolute/path/to/the_learning_studio
```

Back up a production database before importing.

## Development and release commands

There is no JavaScript/CSS compilation step. Run:

```bash
find wordpress-theme/the-learning-studio -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m json.tool wordpress-theme/the-learning-studio/theme.json >/dev/null
python3 scripts/build_site.py
python3 scripts/build_wordpress_theme.py
unzip -t dist/the-learning-studio-1.0.0.zip
```

## Plugins and responsibility boundaries

No plugin is required to display the theme. A maintained contact-form plugin is recommended because the theme intentionally does not process form submissions.

The Lesson post type, Subject taxonomy, lesson metadata, quizzes, and importer currently live in the theme to preserve the delivered site's behavior. Because content types should survive a theme switch, move `inc/content-types.php` and migration tooling into a companion plugin before operating a multi-theme production site. This release does not perform that potentially destructive migration automatically.

## Known limitations

- Quizzes reveal answers but do not track accounts, scores, progress, certificates, or enrollment; use a maintained LMS plugin if those features are required.
- Subject images use a Media Library URL field rather than a custom media-picker interface.
- The included importer migrates textual data and metadata but does not download remote images into the Media Library.
- A suitable repository-owned theme preview image was not available, so the package intentionally has no `screenshot.png`.
- Full browser and WordPress runtime testing requires a WordPress installation; repository checks cover syntax, structure, assets, data, security-pattern scans, and ZIP integrity.
