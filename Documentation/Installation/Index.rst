.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Install via Composer::

   composer require connetation/t3wtk-base

If you consume this package from a private Composer repository, add it to
your project's ``composer.json`` first::

   {
       "repositories": [
           { "type": "composer", "url": "<your-composer-repository-url>" }
       ],
       "require": {
           "connetation/t3wtk-base": "^1.0"
       }
   }

.. _installation-site-set:

Activating the Site Set
========================

In your TYPO3 site configuration (:file:`config/sites/<site>/config.yaml`),
add ``connetation/t3wtk-base`` as a Set dependency, either directly or via a project
sitepackage that depends on it::

   dependencies:
     - connetation/t3wtk-base

TYPO3 loads Site Sets in dependency order, so a project sitepackage that
declares ``connetation/t3wtk-base`` as a dependency does not need any further wiring.

.. _installation-assets:

Build assets
============

Compiled build output (``Resources/Public/Css/main.css`` and
``Resources/Public/Js/main.js``) is committed to the repository and
deployed as-is — no build step is required on the server.

To rebuild the assets locally::

   cd vendor/connetation/t3wtk-base
   npm install
   npm run build    # production build (CSS + JS)
   npm run dev      # watch mode

Bootstrap 5 is fully bundled into ``main.js`` and compiled into
``main.css`` via SCSS — no separate Bootstrap CDN or script tag is needed.
