# Connetation Base for TYPO3 LTS 14

> **🚀 Quickstart:** the fastest way to start a new TYPO3 14 project with `connetation/t3wtk-base`, `connetation/t3wtk-elements` and `connetation/t3wtk-sitepackage` is **[t3wtk-quickstart-shell](https://github.com/connetation/t3wtk-quickstart-shell)** — a ready-to-use DDEV project template. Clone it and run `ddev setup-project`.

Bootstrap 5 base extension with light/dark theme. Provides the reusable foundation for all Connetation TYPO3 projects — templates, TypoScript, TCA, SCSS, and PHP classes.

## Structure

```
connetation/t3wtk-base/                         # TYPO3 extension (connetation/t3wtk-base, ext key: conn_t3wtk_base)
├── Classes/
│   └── DataProcessing/            # PHP data processors
├── Configuration/
│   ├── Sets/Base/                 # TYPO3 v14 Site Sets (TypoScript, constants, settings)
│   ├── Sets/Theme*/               # Theme Sets: connetation/t3wtk-theme-{indigo,forest,sunset,midnight,flat}
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

```bash
composer require connetation/t3wtk-base
```

Classic (non-Composer) installations can install the extension `conn_t3wtk_base` from the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/conn_t3wtk_base).

In your TYPO3 site config (`config/sites/<site>/config.yaml`):

```yaml
dependencies:
  - connetation/t3wtk-sitepackage
  - connetation/t3wtk-elements   # optional component library
```

`connetation/t3wtk-sitepackage` should declare `connetation/t3wtk-base` in its Set dependencies — TYPO3 loads them in order.

## Build Assets

Build output (`Resources/Public/Css/main.css` and `Resources/Public/Js/main.js`) is committed to git and deployed as-is — no build step needed on the server.

To rebuild locally:

```bash
cd vendor/connetation/t3wtk-base  # or your local development copy
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
- **PHP namespace**: `Connetation\T3wtkBase`

## Themes

Four ready-made themes ship as Site Sets. Each sets the light **and** dark
palette, the navigation layout and the footer layout — nothing else (no logo,
no content):

| Set | Colors | Navigation | Footer |
|---|---|---|---|
| `connetation/t3wtk-theme-indigo` | Indigo / violet | Horizontal, right aligned, grid dropdowns | Brand row, then 3 columns |
| `connetation/t3wtk-theme-forest` | Emerald / teal | Off-canvas panel from the right | Centered, dark green |
| `connetation/t3wtk-theme-sunset` | Rose / orange, warm paper | Horizontal, centered, list dropdowns | Minimal, one row |
| `connetation/t3wtk-theme-midnight` | Cyan / violet, dark navbar + footer | Off-canvas panel from the left | 3 columns, dark |
| `connetation/t3wtk-theme-flat` | Zinc / blue, flat: square corners, no shadows | Horizontal, right aligned, list dropdowns | 4 columns, dark |

### Using a theme

Add **one** theme Set to the site next to your sitepackage — in Site
Management → Sites → Sets, or in `config/sites/<site>/config.yaml`:

```yaml
dependencies:
  - connetation/t3wtk-sitepackage
  - connetation/t3wtk-elements
  - connetation/t3wtk-theme-midnight
```

Switching themes = replacing that one line (flush caches afterwards). Then set
the project's own logo (`branding.logo`, `branding.logoDark`, `footer.logo`,
…) in the site settings. Forest's and Flat's footer and Midnight's navbar/footer are
dark in light mode too — use a light logo variant there.

### Order and precedence

The position of a Set in the site's list (or in the backend picker) does
**not** matter. TYPO3 orders Sets by their `dependencies`, and a Set's
settings override the Sets it depends on:

1. `connetation/t3wtk-base` defaults
2. overridden by the theme Set (it depends on `connetation/t3wtk-base`)
3. overridden by the site's own `config/sites/<site>/settings.yaml` — always last

Consequences:

- **Change a single value** of a theme (e.g. `theme.light.accent`): put it in
  the site's `settings.yaml`. It wins over the theme.
- **Don't set theme keys** (colors, `navbar.*`, `footer.layout`, `footer.col*`)
  in another Set that doesn't depend on the theme, e.g. your sitepackage's Set.
  Two Sets without a dependency between them have no defined order, so either
  one could win.
- **Build your own variant**: create a Set that depends on the theme
  (`dependencies: [connetation/t3wtk-theme-midnight]`) and override what you need —
  it is guaranteed to win over the theme. Or copy
  `Configuration/Sets/Theme*/` as a starting point; all keys are defined in
  `Configuration/Sets/Base/settings.definitions.yaml`.

Visitors always start in light mode and switch with the theme toggle
(`themeToggle.enabled`, set by every theme).

## Extension hierarchy

```
TYPO3 site
├── connetation/t3wtk-sitepackage  (project layer — extends connetation/t3wtk-base)
│   └── depends on: connetation/t3wtk-base
├── connetation/t3wtk-base         (this package — reusable foundation)
└── connetation/t3wtk-elements     (optional component library — added per project)
```
