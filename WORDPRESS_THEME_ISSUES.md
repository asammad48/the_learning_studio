# WordPress Theme Remediation Backlog

This document is a handoff for continuing work on **The Learning Studio** with Claude Code or another coding agent. It consolidates the issues found during a source-level audit of the classic WordPress theme in `wordpress-theme/the-learning-studio/`.

## Repository rules

- Read and follow `AGENTS.md` before making changes.
- The installable classic-theme root is `wordpress-theme/the-learning-studio/`.
- Preserve the classic-theme architecture and current visual design unless the owner explicitly approves a redesign.
- Prefix theme functions, settings, metadata, and handles with `tls_` and use the `the-learning-studio` text domain.
- Escape output according to context and sanitize request data.
- Require capability checks and nonces for state-changing administrator actions.
- Enqueue assets from `functions.php`; do not hard-code asset tags or environment URLs.
- Do not commit secrets, credentials, databases, logs, dependencies, caches, `.env` files, or local paths.
- Do not manually edit generated legacy HTML. Update `data/*.json` and run `python3 scripts/build_site.py` when legacy content changes.
- Keep `wordpress-theme/the-learning-studio/legacy-data/` synchronized with `data/*.json` when migration content changes.
- The release ZIP must contain exactly one top-level `the-learning-studio/` directory.
- Complete one bounded fix at a time, test it, review the diff, commit it, and describe any remaining runtime limitation.

## Required checks

Run these checks before every commit:

```bash
find wordpress-theme/the-learning-studio -name '*.php' -print0 | xargs -0 -n1 php -l
node --check wordpress-theme/the-learning-studio/assets/navigation.js
python3 -m json.tool wordpress-theme/the-learning-studio/theme.json >/dev/null
python3 scripts/build_site.py
python3 scripts/build_wordpress_theme.py
unzip -t dist/the-learning-studio-1.0.0.zip
test "$(zipinfo -1 dist/the-learning-studio-1.0.0.zip | cut -d/ -f1 | sort -u)" = "the-learning-studio"
git diff --check
git status --short
```

If a change is visible in the runnable web application, test it in WordPress and capture desktop and mobile screenshots when a WordPress runtime is available.

## Important package distinction

`dist/the-learning-studio-1.0.0.zip` is an installable **WordPress theme ZIP**. Install it through **WordPress Admin -> Appearance -> Themes -> Add New -> Upload Theme**.

It is not a Local/LocalWP full-site import. Local's site-import workflow expects a site archive containing items such as `wp-content/` and a database. Do not add `wp-content/` to the theme ZIP because that would make it invalid for the normal theme uploader. If the owner needs one-click Local import, create and document a separate full-site export artifact.

## Work already completed

The current branch already includes these changes. Verify them before modifying related code:

1. Menu-item descriptions are rendered in the four registered theme menu locations.
2. Primary navigation supports nested submenus with responsive toggle controls, localized accessible labels, Escape handling, and outside-click closing.
3. Featured Subjects and Lessons are prioritized on the homepage, with deterministic fallback content.

Do not duplicate these implementations. Review and improve them only if testing reveals a defect or review feedback requires a change.

---

# Remaining remediation backlog

## 1. Safe, opt-in site setup after import

### Problem

The bundled importer creates Subjects, Lessons, and Pages, but does not produce a configured site. Administrators must manually configure the homepage, posts page, menus, menu assignments, privacy page, logo, and site identity.

### Required behavior

- Add a clearly labelled, opt-in setup action rather than silently changing an existing site.
- Require `manage_options` and a valid nonce.
- Create or reuse the required Pages without duplicating them.
- Create or reuse Primary, Footer Explore, Footer Studio, and Footer Legal menus.
- Add the correct Page/archive items without duplicate menu items.
- Assign menus to the registered theme locations.
- Optionally set the static homepage, posts page, and privacy-policy page after explicit confirmation.
- Do not overwrite an already configured setting without warning and confirmation.
- Report each created, reused, skipped, updated, or failed item.
- Make repeated execution idempotent.

### Acceptance criteria

- Running setup twice creates no duplicate Pages, menus, or menu items.
- Existing unrelated menus and settings remain unchanged.
- A newly configured WordPress installation receives working navigation and front/posts page assignments.
- Every state change has capability and nonce protection.

## 2. Replace assumed fallback-menu slugs

### Problem

Fallback navigation assumes `/subjects/`, `/blog/`, `/about/`, `/contact/`, `/privacy/`, and `/terms/`. Renaming Pages or selecting a different posts page can leave incorrect links.

### Required behavior

- Resolve actual Page IDs and WordPress options where possible.
- Use `get_permalink()` for resolved Pages.
- Use `get_option( 'page_for_posts' )` for the posts page.
- Use `get_privacy_policy_url()` for privacy.
- Provide safe archive/home fallbacks when a Page does not exist.
- Preserve escaped output and translated labels.

## 3. Make Subject search real

### Problem

The search placeholder promises “Search subjects, lessons, topics…”, but standard WordPress search does not return Subject taxonomy terms.

### Required behavior

- Search Subject names, slugs, and descriptions in addition to WordPress posts.
- Present matching Subjects as links to their taxonomy archives.
- Keep Lesson, Post, and Page results working.
- Avoid duplicate results.
- Preserve pagination or clearly separate Subject matches from paginated post results.
- Sanitize the query and escape all output.
- Provide useful empty states.

### Acceptance criteria

- Searching an exact Subject name returns that Subject even when no post content contains the phrase.
- Searching Lesson text still returns the Lesson.
- Searching special characters does not produce warnings or unsafe markup.

## 4. Improve homepage editability

### Problem

`front-page.php` ignores the assigned Page's editor content. Only five Customizer values are editable, while CTA text/URL, section titles, counts, visibility, and ordering are hard-coded.

### Recommended classic-theme approach

- Preserve the classic-theme architecture.
- Render the assigned front Page's block/editor content in a documented location.
- Add sanitized Customizer controls for:
  - panel eyebrow;
  - CTA label and URL;
  - Subject section eyebrow and heading;
  - Lesson section eyebrow and heading;
  - Subject and Lesson item counts within safe limits;
  - section visibility.
- Consider section ordering only if it can be implemented simply and accessibly.
- Do not convert to a block/FSE theme without explicit approval.

### Acceptance criteria

- Content entered in the assigned homepage Page is visible.
- Empty Page content does not add unnecessary whitespace.
- All new text and URL controls are sanitized and escaped.
- Defaults preserve the current appearance.

## 5. Add exact featured-content ordering

### Problem

Featured content now appears first, but featured Subjects are alphabetical and featured Lessons are ordered by date. Administrators cannot define an exact curated order.

### Required behavior

- Choose one understandable ordering model: numeric priority, `menu_order`, or explicit selection.
- Make ordering editable in the administrator interface.
- Validate and sanitize ordering values.
- Preserve deterministic fallback ordering.
- Avoid expensive queries on every homepage request.

## 6. Use Subject groups on the frontend

### Problem

`_tls_subject_group` is stored and imported but is not displayed or used.

### Required behavior

- Group the Subject directory under clear group headings.
- Provide an “Other” or “Ungrouped” section for missing values.
- Define deterministic group and Subject ordering.
- Escape group names.
- Decide how Subject group interacts with hierarchical parent/child Subjects.

## 7. Present hierarchical Subjects correctly

### Problem

The Subject taxonomy is hierarchical, but the directory renders a flat grid and does not identify parent/child relationships.

### Required behavior

- Show parent Subjects distinctly from children.
- Provide accessible nested markup or clear parent labels.
- Avoid showing the same Subject in confusing duplicate locations.
- Keep top-level homepage Subject selection intact unless the design intentionally changes it.
- Test deeply nested, empty, and orphaned terms.

## 8. Replace Subject image URLs with a Media Library picker

### Problem

Administrators must paste an image URL. The card renderer reads `_tls_image_id`, but the supplied interface never writes it.

### Required behavior

- Enqueue WordPress media scripts only on relevant Subject administration screens.
- Add Select/Change/Remove image actions and a preview.
- Store an attachment ID in `_tls_image_id`.
- Keep `_tls_image_url` as a temporary backward-compatible fallback.
- Validate that selected attachments are images.
- Require the existing capability and nonce protections.
- Render responsive WordPress image markup with meaningful alt behavior.

### Migration

- Do not delete existing URL metadata automatically.
- Prefer attachment ID when present and fall back to the legacy URL.
- Consider a separate migration tool for mapping same-site upload URLs to attachment IDs.

## 9. Import Subject and Lesson images

### Problem

The importer does not download media, create attachments, or set featured images.

### Required behavior

- Treat remote downloads as an explicit option, disabled by default.
- Validate schemes and file types and use WordPress HTTP/media APIs.
- Prevent SSRF and disallow unsafe/local network targets.
- Apply timeouts and useful size limits.
- Deduplicate previously imported media.
- Set Subject image attachment IDs and Lesson featured images.
- Preserve or derive useful alt text.
- Report every skipped or failed image.
- Do not make a text import fail completely because one image fails.

## 10. Make the importer safer

### Problems

- Matching slugs are overwritten.
- Imported content is always published.
- Per-item failures are skipped without useful reporting.
- There is no dry run, rollback, or import history.
- Page HTML is escaped as plain text.
- Rewrite rules are flushed after every import.

### Required behavior

- Add a dry-run preview.
- Provide explicit create/update/skip conflict choices.
- Do not change publication status unless the chosen import mode requires it.
- Return structured results containing created, updated, skipped, warning, and error items.
- Display specific WordPress error messages safely.
- Store source identifiers, an import timestamp, and a source/content hash.
- Avoid overwriting administrator-edited content without an explicit choice.
- Define a safe rollback strategy before promising an Undo action.
- Support structured Page content safely; do not blindly trust source HTML.
- Flush rewrite rules only when necessary and only once per completed operation.
- Keep the existing capability and nonce protections.

## 11. Make footer branding consistent

### Problem

The header uses the configured custom logo, but the footer always renders a hard-coded `L` mark.

### Required behavior

- Reuse the configured custom logo in the footer.
- Retain the current `L` mark as the no-logo fallback.
- Keep appropriate image dimensions and alt behavior.
- Make footer column headings and closing text configurable with safe defaults.
- Preserve menu-location behavior.

## 12. Validate Lesson format and media

### Problem

Lesson format and YouTube URL are independent fields. A Video Lesson can have no valid video, and a Written Lesson can still contain one.

### Required behavior

- Validate supported YouTube URL forms when saving.
- Show an administrator warning for invalid or missing video URLs on video formats.
- Clearly define whether format is manually authoritative or derived from content.
- Do not silently change an administrator's chosen format without explaining it.
- Add a direct “Watch on YouTube” fallback link when a valid source URL exists.
- Decide whether a Lesson hero can show both video and featured image.

## 13. Decide whether quizzes are content reveals or LMS assessments

### Current limitation

Quizzes are five fixed question/answer rows rendered as `<details>` answer reveals. They do not score answers, track users, save attempts, track progress, enroll learners, or issue certificates.

### If reveal-only quizzes remain in scope

- Add repeatable rows rather than a fixed limit of five.
- Support reordering and deletion.
- Consider safe rich text for answers.
- Keep the component clearly described as a self-check, not a scored quiz.

### If LMS behavior is required

- Do not build account, enrollment, scoring, progress, and certificate systems casually inside the theme.
- Select a maintained LMS plugin or build a separately reviewed companion plugin.
- Document data ownership, privacy, permissions, migrations, and theme-switch behavior.

## 14. Improve Page and Post presentation

### Remaining gaps

- Standard Pages do not display featured images.
- Single Posts do not display featured images, author, categories, or tags.
- Cards show no summary unless a manual excerpt exists.
- Subject card descriptions are not length-limited.

### Required behavior

- Add featured-image presentation without changing existing layouts when no image exists.
- Add useful Post metadata with accessible, escaped links.
- Provide sensible automatically generated card excerpts while respecting manual excerpts.
- Apply a word/character limit to Subject card descriptions without damaging multibyte text.

## 15. Separate content functionality from the theme

### Problem

Lesson post type registration, Subject taxonomy registration, metadata, quiz fields, and migration tools live in the theme. Switching themes hides their interfaces and routes.

### Required approach

- Plan a companion plugin using the same `tls_` namespace and compatible metadata keys.
- Move content-type registration and content-management behavior without changing stored data.
- Keep presentation helpers and templates in the theme.
- Define activation/deactivation and rewrite behavior.
- Provide a migration path that does not register the same post type or taxonomy twice.
- Treat this as a separate architectural project after user-facing correctness issues are fixed.

## 16. Improve theme release completeness

### Remaining tasks

- Add a repository-owned `screenshot.png` with the correct WordPress theme-preview dimensions.
- Add a `languages/` directory and a generated translation template if translation delivery is required.
- Add WordPress Coding Standards configuration and PHPCS checks.
- Add automated WordPress integration tests for activation, content registration, importer behavior, menu rendering, Customizer persistence, and search.
- Add browser tests for desktop/mobile navigation and keyboard behavior.
- Add accessibility checks for templates and interactive components.
- Test supported WordPress and PHP version combinations.

---

# Recommended implementation order

Implement and commit these as separate, reviewable changes:

1. Safe opt-in setup for Pages, menus, and WordPress reading settings.
2. Dynamic fallback-menu URL resolution.
3. Real Subject taxonomy search.
4. Homepage editor content and additional Customizer controls.
5. Subject grouping and hierarchical directory presentation.
6. Subject Media Library picker.
7. Importer dry run, conflict policy, and detailed reporting.
8. Optional, secure media import.
9. Footer branding consistency and editable footer copy.
10. Lesson format/video validation and fallback link.
11. Page/Post metadata and featured-image presentation.
12. Companion-plugin architecture.
13. Quiz/LMS work only after product scope is explicitly decided.
14. Release screenshot, translations, coding standards, and runtime tests.

# Definition of done for every fix

- The change solves one documented problem without silently broadening scope.
- Administrator state changes have capability checks and nonces.
- Inputs are unslashed and sanitized; outputs are escaped for their context.
- Empty, missing, duplicate, and error states are handled.
- Existing content and settings are preserved unless the administrator explicitly approves an overwrite.
- The classic theme and existing visual design are preserved.
- PHP, JavaScript, JSON, legacy build, theme packaging, ZIP integrity, archive-root, and diff checks pass.
- A WordPress runtime test is performed when the behavior depends on WordPress/database state.
- Visible changes include desktop and mobile screenshots when a runtime is available.
- Documentation is updated.
- The working tree contains only intentional files before committing.
