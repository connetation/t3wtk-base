# Connetation Sitepackage for TYPO3 LTS 14

Bootstrap 5 sitepackage with light/dark theme and service dashboard.

## Structure

```
packages/sitepackage/          # TYPO3 extension (conn/sitepackage)
├── Classes/
│   └── DataProcessing/        # PHP data processors
├── Configuration/
│   ├── Sets/Sitepackage/      # TYPO3 v14 Site Sets (TypoScript, constants)
│   ├── TCA/                   # Domain model TCA (project, projectcategory)
│   ├── TCA/Overrides/         # tt_content & pages TCA extensions
│   ├── TypoScript/            # Page object & content element TypoScript
│   ├── FlexForms/             # Backend element settings (Dashboard)
│   └── TsConfig/              # Backend layouts
├── Resources/
│   ├── Private/
│   │   ├── JavaScript/        # JS source (dashboard.js, main.js)
│   │   ├── Language/          # XLF translation files (EN)
│   │   ├── Layouts/           # Fluid page layouts
│   │   ├── Partials/          # Fluid partials (Navigation, Dashboard, …)
│   │   ├── Scss/              # SCSS source (Bootstrap + custom)
│   │   └── Templates/         # Fluid page & content element templates
│   └── Public/
│       ├── Css/main.css       # Compiled — committed to git
│       ├── Icons/             # Custom SVG icons
│       └── Js/main.js         # Compiled — committed to git
├── composer.json
├── ext_emconf.php
├── ext_tables.php
├── ext_tables.sql
└── package.json               # Node build tooling
```

## Installation

1. Ensure `composer.json` at the project root has a path repository:
   ```json
   "repositories": [{ "type": "path", "url": "packages/*" }]
   ```
2. Run `composer require conn/sitepackage`
3. Activate the extension in TYPO3 Backend → Admin Tools → Extensions

## Build Assets

Build output (`Resources/Public/Css/main.css` and `Resources/Public/Js/main.js`) is committed to git and deployed as-is — no build step needed on the server.

To rebuild locally:

```bash
cd packages/sitepackage
npm install
npm run build    # production build (CSS + JS)
npm run dev      # watch mode
```

Bootstrap 5 is fully bundled into `main.js` and compiled into `main.css` via SCSS — no separate Bootstrap CDN or script tag required.

## Features

- **TYPO3 LTS 14** — Site Sets, modern TCA conventions
- **Bootstrap 5.3** — full integration via SCSS import + JS bundle
- **Light / Dark theme** — CSS custom properties, `data-bs-theme`, persisted in `localStorage` under key `conn-theme`, respects OS `prefers-color-scheme`
- **Backend layouts**: Default, Landing Page
- **Custom page fields**: subtitle, header image, hide breadcrumb
- **Content element**: Service Dashboard

### Service Dashboard

An interactive overview for services and tools, with a custom backend icon.

- Manage services as inline records directly in the content element
- **Dashboard Config tab** — define categories per dashboard instance inline
- Filter by status and category via dropdowns
- Full-text search across title, category, and tags
- Grid / List view toggle with image thumbnails in both views
- Detail modal with description, tags, and visit link
- Status badges with theme-aware colours for light and dark mode

**Status values:** Active · In Progress · Coming Soon · Inactive

**Image handling:** TYPO3 server-side resizing per context — 800×450 card, 1200×675 modal, 80px wide list icon (aspect-ratio preserved)
