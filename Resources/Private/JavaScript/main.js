/**
 * Connetation Base – Main JavaScript
 * TYPO3 LTS 14
 */

import * as bootstrap from 'bootstrap';
import GLightbox from 'glightbox';

// Bootstrap's ESM build (unlike the old UMD bundle) doesn't attach itself to
// `window` — other scripts on the page (navbar partials) call
// `bootstrap.Collapse`/`bootstrap.Dropdown` etc. expecting that global.
window.bootstrap = bootstrap;

// ─────────────────────────────────────────────
// Theme management (light / dark)
// ─────────────────────────────────────────────
const ConnTheme = (() => {
    const STORAGE_KEY = 'conn-theme';
    const ATTR = 'data-bs-theme';

    const getPreferred = () =>
        localStorage.getItem(STORAGE_KEY) ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

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

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem(STORAGE_KEY)) {
                apply(e.matches ? 'dark' : 'light');
            }
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
    initNewsCardReveal();
    const lightbox = GLightbox({ selector: '.glightbox', autoplayVideos: false });

    lightbox.on('slide_changed', ({ current }) => {
        if (current.trigger?.dataset.autoplay === '1' && current.player) {
            current.player.play();
        }
    });
});
