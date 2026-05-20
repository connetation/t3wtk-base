/**
 * Connetation Sitepackage – Main JavaScript
 * TYPO3 LTS 14
 * Dashboard init is handled by EXT:conn_elements/elements.js
 */

import 'bootstrap';
import GLightbox from 'glightbox';

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

        document.querySelectorAll('#themeToggle').forEach(btn => {
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
// Init
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ConnTheme.init();
    initBootstrapComponents();
    initSmoothScroll();
    initNavbar();
    const lightbox = GLightbox({ selector: '.glightbox', autoplayVideos: false });

    lightbox.on('slide_changed', ({ current }) => {
        if (current.trigger?.dataset.autoplay === '1' && current.player) {
            current.player.play();
        }
    });
});
