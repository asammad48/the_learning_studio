# The Learning Studio

A small static HTML, CSS and JavaScript project that implements the supplied Learning Studio screenshots as a connected multi-page site.

## Project structure

```text
.
├── index.html                 # Home/overview page linking every implemented screen
├── subjects.html              # All subjects library with search and category filters
├── subject-business.html      # Business & Management subject landing screen
├── subject-ai.html            # Artificial Intelligence subject landing screen
├── lesson-management.html     # What Is Management lesson, quiz and FAQ screen
└── assets
    ├── css/styles.css         # Shared responsive styling and components
    └── js/app.js              # Subject rendering, filters, mobile nav and quiz behavior
```

## Run locally

Open `index.html` directly in a browser, or serve the folder locally:

```bash
python3 -m http.server 4173 --bind 127.0.0.1
```

Then visit `http://127.0.0.1:4173/`.
