# Connetation Base for TYPO3 LTS 14

Bootstrap 5 base extension with light/dark theme. Provides the reusable foundation for all Connetation TYPO3 projects — templates, TypoScript, TCA, SCSS, and PHP classes.

## Structure

```
conn/base/                         # TYPO3 extension (conn/base, ext key: conn_base)
├── Classes/
│   └── DataProcessing/            # PHP data processors
├── Configuration/
│   ├── Sets/Base/                 # TYPO3 v14 Site Sets (TypoScript, constants, settings)
│   ├── TCA/Overrides/             # tt_content & pages TCA extensions
│   ├── TypoScript/                # Page object & content element TypoScript
│   └── TsConfig/                  # Backend layouts
├── Resources/
│   ├── Private/
│   │   ├── JavaScript/            # JS source (main.js)
│   │   ├── Language/              # XLF translation files (EN/DE)
│   │   ├── Layouts/               # Fluid page layouts
│   │   ├── Partials/              # Fluid partials (Navigation, Page, Media)
│   │   ├── Scss/                  # SCSS source (Bootstrap 5 + custom)
│   │   └── Templates/             # Fluid page templates
│   └── Public/
│       ├── Css/main.css           # Compiled — committed to git
│       ├── Icons/                 # Custom SVG icons
│       └── Js/main.js             # Compiled — committed to git
├── composer.json
├── ext_emconf.php
├── ext_tables.php
├── ext_tables.sql
└── package.json                   # Node build tooling
```

## Installation

```json
"repositories": [
    { "type": "composer", "url": "https://composer-v2.connetation.at/" }
],
"require": {
    "conn/base": "^1.0"
}
```

In your TYPO3 site config (`config/sites/<site>/config.yaml`):

```yaml
dependencies:
  - conn/sitepackage
  - conn/elements   # optional component library
```

`conn/sitepackage` should declare `conn/base` in its Set dependencies — TYPO3 loads them in order.

## Build Assets

Build output (`Resources/Public/Css/main.css` and `Resources/Public/Js/main.js`) is committed to git and deployed as-is — no build step needed on the server.

To rebuild locally:

```bash
cd vendor/conn/base  # or your local development copy
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
- **PHP namespace**: `Connetation\Base`

## Extension hierarchy

```
TYPO3 site
├── conn/sitepackage  (project layer — extends conn/base)
│   └── depends on: conn/base
├── conn/base         (this package — reusable foundation)
└── conn/elements     (optional component library — added per project)
```
