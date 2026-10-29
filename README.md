> **🚀 Quickstart:** the fastest way to start a new TYPO3 14 project with `connetation/t3wtk-base`, `connetation/t3wtk-elements` and `connetation/t3wtk-sitepackage` is **[t3wtk-quickstart-shell](https://github.com/connetation/t3wtk-quickstart-shell)** — a ready-to-use DDEV project template. Clone it and run `ddev setup-project`.

# T3WTK – Connetation TYPO3 Web Toolkit

From an empty TYPO3 installation to a complete, themeable website in hours instead of weeks.

T3WTK is a modular set of TYPO3 extensions for TYPO3 14 LTS. It replaces the groundwork
that has to be redone on almost every project: sitepackage, page templates, navigation,
footer, content elements, dark mode, forms and SEO setup. Once installed via Composer,
your team can start with configuration, design and content right away.

## Packages

| Package | Purpose |
|---|---|
| `connetation/t3wtk-base` | Design system & foundation: page templates, navigation, footer, design tokens, light/dark theme, site settings, default theme |
| `connetation/t3wtk-elements` | 30+ content elements in eight categories |
| `connetation/t3wtk-sitepackage` | Project layer: theme overrides, forms, email templates, SEO configuration |
| `connetation/t3wtk-demosite` | Creates a complete demo page tree with sample content |
| `connetation/t3wtk-themes` | Theme library (in development) |

Project-specific code lives only in the sitepackage, so base and elements can be updated
via Composer like any other dependency without overwriting your customizations.

## Highlights

- **Design once, theme everywhere:** colors, logo, navbar, footer, buttons and border radius
  as TYPO3 site settings. 250+ options, editable in the Sites module.
- **Full dark mode** with its own color palette and theme toggle, per site, no extra CSS
- **30+ content elements:** hero and card sliders, video hero, before/after comparison,
  mosaic and lightbox galleries, accordion, tabs, flip cards, timeline, pricing tables,
  stats/counter, testimonials, team, map & location and more
- **Five container layouts** (2/3/4 columns, narrow/wide, wide/narrow), nestable
- **Shared design options for every element:** spacing, content width up to full-bleed,
  backgrounds (color, image, parallax), scroll and text animations
- **Built on TYPO3 standards:** Site Sets, Fluid, Composer, core "Text & Media" as a base,
  regular TYPO3 content records with no proprietary structures
- **Performance:** responsive images, lazy loading, CSS/JS loaded only for elements actually
  used on a page
- **Accessibility-minded markup:** Bootstrap 5, semantic HTML, clean heading hierarchy,
  keyboard-operable widgets
- **Included:** contact form (Form Framework) with branded email templates, SEO setup via
  cs_seo (Open Graph, Twitter Cards, structured data), favicon/branding set, three icon
  libraries (Bootstrap Icons, Font Awesome, Ionicons)

## Requirements

- TYPO3 14 LTS (tested on 14.3), Composer mode
- Uses `b13/container` and `clickstorm/cs_seo`

## Quick start

1. `composer require connetation/t3wtk-base connetation/t3wtk-elements`
2. Add the T3WTK site set to your site configuration
3. Configure colors, logo, navigation and footer in the site settings
4. Build pages with the ready-made content elements

Want to explore first? Install `connetation/t3wtk-demosite` or visit https://demosite.connetation.at

## Feedback & contributing

T3WTK is a young open-source project. Bug reports, ideas and pull requests are welcome via
GitHub Issues and Discussions.

---

## This package: `connetation/t3wtk-base`

Design system and foundation of the Connetation TYPO3 Web Toolkit (T3WTK) for TYPO3 14 LTS, themeable without writing code: page templates, navigation, footer, light/dark mode and 250+ site settings. Built on Site Sets and Bootstrap 5.

Bootstrap 5 base extension with light/dark theme. Provides the reusable foundation for all Connetation TYPO3 projects — templates, TypoScript, TCA, SCSS, and PHP classes.

### Structure

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

### Installation

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

### Build Assets

Build output (`Resources/Public/Css/main.css` and `Resources/Public/Js/main.js`) is committed to git and deployed as-is — no build step needed on the server.

To rebuild locally:

```bash
cd vendor/connetation/t3wtk-base  # or your local development copy
npm install
npm run build    # production build (CSS + JS)
npm run dev      # watch mode
```

Bootstrap 5 is fully bundled into `main.js` and compiled into `main.css` via SCSS — no separate Bootstrap CDN or script tag required.

### Features

- **TYPO3 LTS 14** — Site Sets, modern TCA conventions
- **Bootstrap 5.3** — full integration via SCSS import + JS bundle
- **Light / Dark theme** — CSS custom properties, `data-bs-theme`, persisted in `localStorage` under key `conn-theme`, respects OS `prefers-color-scheme`
- **Backend layouts**: Default, Landing Page
- **Custom page fields**: subtitle, header image, hide breadcrumb
- **PHP namespace**: `Connetation\T3wtkBase`

### Themes

Four ready-made themes ship as Site Sets. Each sets the light **and** dark
palette, the navigation layout and the footer layout — nothing else (no logo,
no content):

| Set | Colors | Navigation | Footer |
|---|---|---|---|
| `connetation/t3wtk-theme-indigo` | Indigo / violet | Horizontal, right aligned, grid dropdowns | Brand row, then 3 columns |
| `connetation/t3wtk-theme-forest` | Emerald / teal | Off-canvas panel from the right | Centered, dark green |
| `connetation/t3wtk-theme-sunset` | Rose / orange, warm paper | Horizontal, centered, list dropdowns | Minimal, one row |
| `connetation/t3wtk-theme-midnight` | Cyan / violet, dark navbar + footer | Off-canvas panel from the left | 3 columns, dark |
| `connetation/t3wtk-theme-flat` | Zinc / blue, flat: square corners, no shadows | Horizontal, right aligned, list dropdowns | Brand row + 3 columns, dark |

#### Using a theme

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

#### Order and precedence

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

### Extension hierarchy

```
TYPO3 site
├── connetation/t3wtk-sitepackage  (project layer — extends connetation/t3wtk-base)
│   └── depends on: connetation/t3wtk-base
├── connetation/t3wtk-base         (this package — reusable foundation)
└── connetation/t3wtk-elements     (optional component library — added per project)
```

## License

GPL-2.0-or-later · Developed by Connetation Web Engineering GmbH, Salzburg, Austria
