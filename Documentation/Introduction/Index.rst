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
  under the ``Connetation\Base`` namespace

.. _extension-hierarchy:

Extension hierarchy
====================

conn/base has no dependency on any other Connetation extension. Other
Connetation packages build on top of it::

   TYPO3 site
   ├── conn/sitepackage  (project layer, extends conn/base)
   │   └── depends on: conn/base
   ├── conn/base         (this package, reusable foundation)
   └── conn/elements     (optional component library, added per project)
