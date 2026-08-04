/**
 * Connetation Base – Main JavaScript
 * TYPO3 LTS 14
 */

import * as bootstrap from 'bootstrap';
import GLightbox from 'glightbox';

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
// Navbar shrink on scroll
// ─────────────────────────────────────────────
const initNavbar = () => {
    const nav = document.getElementById('mainNav');
    if (!nav) return;
    const onScroll = () => nav.classList.toggle('navbar--scrolled', window.scrollY > 50);
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
// (same stagger fade/slide-in as conn/elements' CardGroup, CSS-driven —
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
    const lightbox = GLightbox({ selector: '.glightbox', autoplayVideos: false });

    lightbox.on('slide_changed', ({ current }) => {
        if (current.trigger?.dataset.autoplay === '1' && current.player) {
            current.player.play();
        }
    });
});
