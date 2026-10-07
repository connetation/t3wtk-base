/**
 * Connetation Base – Main JavaScript
 * TYPO3 LTS 14
 */

import * as bootstrap from 'bootstrap';
import GLightbox from 'glightbox';
import JustValidate from 'just-validate';

window.bootstrap = bootstrap;

// ─────────────────────────────────────────────
// Theme management (light / dark)
// ─────────────────────────────────────────────
const ConnTheme = (() => {
    const STORAGE_KEY = 'conn-theme';
    const ATTR = 'data-bs-theme';

    const getPreferred = () =>
        localStorage.getItem(STORAGE_KEY) || 'light';

    const apply = (theme) => {
        document.documentElement.setAttribute(ATTR, theme);
        localStorage.setItem(STORAGE_KEY, theme);
    };

    const toggle = () => {
        const current = document.documentElement.getAttribute(ATTR) || getPreferred();
        apply(current === 'dark' ? 'light' : 'dark');
    };

    const init = () => {
        apply(getPreferred());

        // Re-enable transitions after the first paint in the final theme
        requestAnimationFrame(() => requestAnimationFrame(() => {
            document.documentElement.classList.remove('conn-theme-preload');
        }));

        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.addEventListener('click', toggle);
        });
    };

    return { init, toggle, apply };
})();

// ─────────────────────────────────────────────
// Bootstrap tooltips & popovers init
// ─────────────────────────────────────────────
const initBootstrapComponents = () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        new bootstrap.Popover(el);
    });
};

// ─────────────────────────────────────────────
// Smooth scroll for anchor links
// ─────────────────────────────────────────────
const initSmoothScroll = () => {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
};

// ─────────────────────────────────────────────
// Navbar scroll state + shrink on scroll
// navbar--scrolled: page is scrolled at all (styling hook).
// navbar--shrunk (site setting navbar.shrinkOnScroll): set while scrolling
// down past navbar.scrollRange, removed as soon as the user scrolls up or
// is back within the range.
// ─────────────────────────────────────────────
const initNavbar = () => {
    const nav = document.getElementById('mainNav');
    if (!nav) return;

    const shrink = document.body.classList.contains('navbar-shrink-on-scroll');
    const range = parseInt(document.body.dataset.navScrollRange, 10) || 100;
    const TOLERANCE = 5; // px — ignore tiny scroll jitter (trackpads, momentum)
    let lastY = window.scrollY;

    const onScroll = () => {
        const y = window.scrollY;
        nav.classList.toggle('navbar--scrolled', y > 50);
        if (!shrink) return;

        if (y <= range) {
            nav.classList.remove('navbar--shrunk');
            lastY = y;
            return;
        }
        if (Math.abs(y - lastY) < TOLERANCE) return;
        nav.classList.toggle('navbar--shrunk', y > lastY);
        lastY = y;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
};

// ─────────────────────────────────────────────
// NavTop — dropdown grid alignment + flyout submenu positioning
// ─────────────────────────────────────────────
const initNavTopDropdowns = () => {
    const nav = document.getElementById('mainNav');
    if (!nav) return;

    const align = nav.dataset.gridAlign || 'auto';
    nav.querySelectorAll('.conn-nav-top__dropdown').forEach(menu => {
        if (align !== 'auto') {
            menu.classList.add(`conn-grid-align-${align}`);
            return;
        }
        const applyAutoAlign = () => {
            const trigger = menu.previousElementSibling;
            if (!trigger) return;
            const rect = trigger.getBoundingClientRect();
            const triggerCenter = rect.left + rect.width / 2;
            menu.classList.remove('conn-grid-align-left', 'conn-grid-align-right', 'conn-grid-align-center');
            menu.classList.add(triggerCenter > window.innerWidth / 2 ? 'conn-grid-align-right' : 'conn-grid-align-left');
        };
        menu.closest('.nav-item').addEventListener('mouseenter', applyAutoAlign);
        const trigger = menu.previousElementSibling;
        if (trigger) trigger.addEventListener('click', applyAutoAlign);
    });

    nav.querySelectorAll('.conn-nav-top__dropdown').forEach(menu => {
        menu.closest('.nav-item').addEventListener('mouseenter', () => {
            menu.classList.remove('dropdown-menu-end');
            const rect = menu.getBoundingClientRect();
            if (rect.right > document.documentElement.clientWidth - 8) {
                menu.classList.add('dropdown-menu-end');
            }
        });
    });

    // Flyout submenus: open to the left when there is not enough space on the right.
    // Always reset to right-side first so getBoundingClientRect() measures the
    // right-opening position — otherwise a stale --dropstart class from the
    // previous hover would cause the position to alternate every other open.
    nav.querySelectorAll('.dropdown-submenu').forEach(sm => {
        sm.addEventListener('mouseenter', () => {
            const flyout = sm.querySelector(':scope > .dropdown-menu');
            if (!flyout) return;
            sm.classList.remove('dropdown-submenu--dropstart');
            const rect = flyout.getBoundingClientRect();
            // Use clientWidth (excludes scrollbar) + 8px buffer for sub-pixel cases
            if (rect.right > document.documentElement.clientWidth - 8) {
                sm.classList.add('dropdown-submenu--dropstart');
            }
        });
    });
};

// ─────────────────────────────────────────────
// Floating theme-toggle button — position offset + click handler
// ─────────────────────────────────────────────
const initThemeToggleFloat = () => {
    const el = document.querySelector('.conn-theme-toggle-float');
    if (!el) return;

    el.style.setProperty('--tg-x', `${el.dataset.tgX || '24'}px`);
    el.style.setProperty('--tg-y', `${el.dataset.tgY || '24'}px`);

    const btn = el.querySelector('.conn-float-toggle');
    if (!btn) return;
    btn.addEventListener('click', () => ConnTheme.toggle());
};

// ─────────────────────────────────────────────
// News/Event card grid — entrance reveal
// (same stagger fade/slide-in as connetation/t3wtk-elements' CardGroup, CSS-driven —
// see Scss/components/_news.scss and _events.scss for the keyframes/stagger)
// ─────────────────────────────────────────────
const initNewsCardReveal = () => {
    const groups = document.querySelectorAll('.conn-news-cards, .conn-event-cards');
    if (!groups.length) return;

    if (!('IntersectionObserver' in window)) {
        groups.forEach(group => group.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    groups.forEach(group => observer.observe(group));
};

// ─────────────────────────────────────────────
// Generic form validation (JustValidate)
//
// Fully opt-in and data-driven — no form/field names are hardcoded here, so
// this works for any current or future form (EXT:form or otherwise) without
// touching this file. A field is picked up if it carries [required] and/or
// [data-validate-type] (or is type="email"); its error text comes from its own
// data-validate-required-message / data-validate-type-message attributes
// (set via the form's own fluidAdditionalAttributes, using LLL: refs so they
// translate per site language) — nothing here is hardcoded to one language.
// If the field has none, the same attributes on its EXT:form container are
// used: Extensions/Form/Partials/Field/Field.fluid.html fills them from the
// error messages set in the form editor (validationErrorMessages).
// See vendor/connetation/t3wtk-sitepackage's Resources/Private/Forms/README.md for how to
// wire a new form/field up to this.
// ─────────────────────────────────────────────
const toKebabCase = (camelCase) => camelCase.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);

const initFormValidation = () => {
    const FIELD_SELECTOR = '[required], [data-validate-type], input[type="email"]';
    const initializedForms = new WeakSet();

    document.querySelectorAll(FIELD_SELECTOR).forEach((field) => {
        const form = field.closest('form');
        if (!form || !form.id || initializedForms.has(form)) return;
        initializedForms.add(form);

        const validation = new JustValidate(`#${form.id}`, {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'invalid-feedback',
            successFieldCssClass: 'is-valid',
            successLabelCssClass: 'valid-feedback',
            focusInvalidField: true,
            submitFormAutomatically: true,
        });

        form.querySelectorAll(FIELD_SELECTOR).forEach((el) => {
            if (!el.id) return;
            const rules = [];

            const message = (name) =>
                el.dataset[name] || el.closest(`[data-${toKebabCase(name)}]`)?.dataset[name] || undefined;

            if (el.hasAttribute('required')) {
                rules.push({
                    rule: 'required',
                    errorMessage: message('validateRequiredMessage'),
                });
            }

            // type="email" too: just-validate sets novalidate on the form, which
            // switches off the browser's own check for EXT:form's Email element.
            if (el.dataset.validateType === 'email' || el.type === 'email') {
                rules.push({
                    rule: 'email',
                    errorMessage: message('validateTypeMessage'),
                });
            }

            if (!rules.length) return;
            validation.addField(`#${el.id}`, rules);

            // just-validate only re-checks a field on input/change, so a
            // required field left empty via Tab/blur wouldn't show an error
            // until submit. Force a check on blur too.
            el.addEventListener('blur', () => validation.revalidateField(`#${el.id}`));

            // Let just-validate show (and own) the message for rules it knows. If it
            // finds the field valid, the server error comes from a validator it
            // doesn't cover (e.g. StringLength), so keep the server's message.
            const serverError = document.getElementById(`${el.id}-errors`);
            if (serverError) {
                validation.revalidateField(`#${el.id}`).then((isValid) => {
                    if (isValid) return;
                    serverError.remove();
                    el.removeAttribute('aria-describedby');
                });
            }
        });

        // just-validate re-submits via HTMLFormElement.submit(), which (unlike
        // a real button click or requestSubmit()) never includes the clicked
        // submit button's name/value. TYPO3's form framework relies on that
        // (e.g. the "__currentPage" button) to know the form is complete, so
        // without it the POST silently re-renders instead of running the
        // finishers. Re-inject it as a hidden field before the auto-submit.
        validation.onSuccess((event) => {
            const submitter = event.submitter;
            if (!submitter?.name) return;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value;
            event.target.appendChild(hidden);
        });
    });
};

// ─────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ConnTheme.init();
    initBootstrapComponents();
    initSmoothScroll();
    initNavbar();
    initNavTopDropdowns();
    initThemeToggleFloat();
    initNewsCardReveal();
    initFormValidation();
    const lightbox = GLightbox({ selector: '.glightbox', autoplayVideos: false });

    lightbox.on('slide_changed', ({ current }) => {
        if (current.trigger?.dataset.autoplay === '1' && current.player) {
            current.player.play();
        }
    });
});
