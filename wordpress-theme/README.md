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

- **Lessons → Add New Lesson:** title, editor content, excerpt, featured image, subject, written/video format, duration, YouTube URL, featured status, a numeric featured-order priority, and any number of quiz question/answer rows (add or remove rows in the "Quick Quiz" box).
- **Lessons → Subjects:** hierarchical subjects, description, subject group, a Media Library image picker (with a fallback image URL field for backward compatibility), featured status, and a numeric featured-order priority. WordPress calculates lesson counts.
- **Posts:** editorial news and normal blog articles. Single posts show the author, categories, and tags.
- **Pages:** About, Contact, policies, and other permanent content. Pages display their featured image when one is set.
- **Appearance → Customize → Homepage content:** hero copy, feature-panel eyebrow/title/text/button label/button URL, Subjects section eyebrow/heading/item count/visibility, and Lessons section eyebrow/heading/item count/visibility.
- **Appearance → Customize → Site Identity:** title, tagline, and custom logo. The custom logo is used in both the header and the footer.
- **Appearance → Menus:** Primary navigation, Footer: Explore, Footer: Studio, and Footer: Legal.

If the Page assigned as the static front page (**Settings → Reading**) has editor content, that content renders between the hero and the Featured subjects section on the homepage. It is skipped when empty, so sites that only use the Customizer fields keep the original layout unchanged.

Featured subjects and lessons are prioritized on the homepage using their featured-order priority (higher first). When priorities tie, or an item isn't featured, subjects fall back to alphabetical order and lessons fall back to newest first, filling any remaining homepage positions.

The Subject directory (`/subjects/`) groups top-level subjects under their `_tls_subject_group` heading (an "Other subjects" heading collects ungrouped ones) and lists each subject's descendants as a nested link list beneath its card.

Menu item descriptions are displayed beneath their labels in these four theme locations. If the Description field is hidden in the classic menu editor, enable it from **Screen Options**.
The Primary navigation also supports nested menu items with keyboard-accessible submenu controls and responsive dropdown styling; top-level items and submenus (via keyboard focus) both remain reachable without JavaScript.

Site search matches Subject names, slugs, and descriptions in addition to the normal Lesson/Post/Page search, showing Subject matches in their own section above the paginated results.

The public templates include the homepage, posts page, lesson archive, individual lesson, subject directory, subject archive, standard archives, search, comments, and 404 states.

## Opt-in site setup

After activation (and typically after importing content), open **Tools → Learning Studio Setup** to create or reuse the Pages this theme expects (Subjects, About, Contact, Privacy Policy, Terms of Use, Blog, Home), build and assign its four navigation menus, and optionally set the homepage/posts page/privacy policy page under **Settings → Reading**.

This action does nothing until an administrator opens the page and submits the form. It is restricted to `manage_options` and protected by a nonce, matches Pages by slug and menus by name so repeated runs never create duplicates, and by default leaves any navigation location or Reading setting that is already configured untouched — reassigning menu locations or overwriting an already-configured homepage each require their own explicit checkbox. The results page reports exactly what was created, reused, skipped, or failed for every Page, menu, and setting.

## Import bundled legacy content

After activation, open **Tools → Learning Studio Import**. The form defaults to a dry run — it previews created/updated/skipped/error results for every Subject, Lesson, and Page without writing anything until "Dry run" is unchecked. A separate "Update content that already exists" checkbox controls whether records with a matching slug are updated or left untouched; leaving it unchecked only ever creates new content. The action is restricted to administrators and protected by a nonce. Updating an existing Lesson or Page never changes its publication status (draft/private stay as an administrator left them) — only newly created content is published. Every imported item also records its source slug, an import timestamp, and a content hash as post/term meta (`_tls_import_source`, `_tls_import_date`, `_tls_import_hash`) for future tooling.

WP-CLI is optional and supports the same `--dry-run` and `--update-existing` flags. From a WordPress installation, import bundled data with:

```bash
wp tls import --source=/path/to/wp-content/themes/the-learning-studio/legacy-data --dry-run
```

Or import directly from this repository with:

```bash
wp tls import --source=/absolute/path/to/the_learning_studio --update-existing
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
- Nested submenu items can only be expanded on mobile with a keyboard (via focus) or with JavaScript; there is no tap-to-expand affordance for touch users with JavaScript disabled.
- The included importer migrates textual data and metadata but does not download remote images into the Media Library; Subject images and Lesson featured images still need to be set manually or via the Media Library picker.
- The importer's dry run and skip-existing modes prevent accidental overwrites, but there is no rollback/undo for a completed import — restore from a database backup if a real run needs to be reverted.
- A suitable repository-owned theme preview image was not available, so the package intentionally has no `screenshot.png`.
- Full browser and WordPress runtime testing requires a WordPress installation; repository checks cover syntax, structure, assets, data, security-pattern scans, and ZIP integrity.
