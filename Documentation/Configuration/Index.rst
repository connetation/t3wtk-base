.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

.. _configuration-site-settings:

Site settings
=============

conn/t3kit-base exposes its configurable options as TYPO3 v14 Site Settings,
defined in :file:`Configuration/Sets/Base/settings.definitions.yaml`. These
are editable per site under :guilabel:`Site Management > Edit Site
Configuration > Settings` in the TYPO3 backend, and include:

- **Theme** — light/dark mode behaviour
- **Navbar position** — sticky, fixed-top, fixed-bottom, or static
- **Logo position** — left, center, or right
- **Offcanvas slide direction** — start, end, top, or bottom
- **Offcanvas body scroll and backdrop** — boolean toggles
- **Social media links** — per-platform URLs, rendered via the social icon
  panel partial

Every dropdown/choice setting uses the ``enum:`` key so it renders as a
proper select field in the backend, rather than a free-text input.

.. _configuration-backend-layouts:

Backend layouts
================

Two backend page layouts are provided out of the box: **Default** and
**Landing Page**. Additional custom page fields are available on every
page: subtitle, header image, and hide breadcrumb.

.. _configuration-rte:

Rich Text Editor
=================

RTE presets are defined in :file:`Configuration/RTE/Conn.yaml` (extending
:file:`Configuration/RTE/Default.yaml`). Projects that need a different
button/style set than the shipped defaults should override this preset
rather than editing it in place.

.. _configuration-theme-vars:

Theme variables
================

``Connetation\T3kitBase\Classes\DataProcessing\ThemeVarsProcessor`` maps page
and site-level theme settings to CSS custom properties. It merges into the
existing Fluid ``settings`` variable rather than overwriting it, so other
data processors' settings (for example from conn/t3kit-elements) are preserved.
