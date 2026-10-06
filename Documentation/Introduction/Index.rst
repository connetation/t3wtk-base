.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

.. _what-it-does:

What does it do?
=================

Connetation Base provides the reusable foundation for TYPO3 v14 sites built
on Bootstrap 5:

- **TYPO3 LTS 14 Site Sets** and modern TCA conventions
- **Bootstrap 5.3** — full integration via bundled SCSS and JavaScript, no
  external CDN or script tag required
- **Light / dark theme** — implemented with CSS custom properties and
  ``data-bs-theme``, persisted in ``localStorage`` under the key
  ``conn-theme``, and respecting the visitor's OS
  ``prefers-color-scheme``
- **Backend page layouts** — Default and Landing Page
- **Custom page fields** — subtitle, header image, hide breadcrumb
- Reusable Fluid partials for navigation (including an offcanvas navbar),
  page footer, and social media icon panels
- PHP data processors and ViewHelpers (``ThemeVarsProcessor``,
  ``ResolvePathViewHelper``, ``ResponsiveImageViewHelper``, and others)
  under the ``Connetation\T3wtkBase`` namespace

.. _extension-hierarchy:

Extension hierarchy
====================

connetation/t3wtk-base has no dependency on any other Connetation extension. Other
Connetation packages build on top of it::

   TYPO3 site
   ├── connetation/t3wtk-sitepackage  (project layer, extends connetation/t3wtk-base)
   │   └── depends on: connetation/t3wtk-base
   ├── connetation/t3wtk-base         (this package, reusable foundation)
   └── connetation/t3wtk-elements     (optional component library, added per project)
