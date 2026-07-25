# Repository agent guide

## Structure

- The repository root is the legacy static JSON/Python site.
- The installable classic WordPress theme root is `wordpress-theme/the-learning-studio/`.
- Do not manually edit generated legacy HTML; update `data/*.json` and run `python3 scripts/build_site.py`.
- Keep `wordpress-theme/the-learning-studio/legacy-data/` synchronized with `data/*.json` when migration content changes.

## WordPress conventions

- Use the `tls_` prefix and the `the-learning-studio` text domain.
- Escape output according to context and sanitize all request data.
- State-changing admin actions require capability checks and nonces.
- Enqueue assets from `functions.php`; do not add hard-coded asset tags or environment URLs.
- Do not add secrets, credentials, `.env` files, database exports, logs, or local paths.
- Preserve the classic-theme architecture and existing visual design.

## Checks and packaging

Run before committing:

```bash
find wordpress-theme/the-learning-studio -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m json.tool wordpress-theme/the-learning-studio/theme.json >/dev/null
python3 scripts/build_site.py
python3 scripts/build_wordpress_theme.py
unzip -t dist/the-learning-studio-1.0.0.zip
```

The release archive must contain exactly one top-level `the-learning-studio/` directory. Never package the repository root, tests, caches, dependencies, or development files.
