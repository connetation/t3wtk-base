class NavbarTwopanel {
    private static readonly NAV_ID             = 'mainNav';
    private static readonly NAV_CLASS          = 'conn-navbar-twopanel';
    private static readonly TRIGGER_SEL        = '.conn-megamenu-trigger';
    private static readonly INNER_TRIGGER_SEL  = '.conn-twopanel-trigger';
    private static readonly CLASS_IS_OPEN      = 'is-open';
    private static readonly CLASS_IS_ACTIVE    = 'is-active';
    private static readonly BREAKPOINT_DESKTOP = 992;

    private readonly triggers: NodeListOf<HTMLElement>;
    private activePanel: HTMLElement | null = null;
    private closeTimer: ReturnType<typeof setTimeout> | null = null;

    constructor(private readonly nav: HTMLElement) {
        this.triggers = nav.querySelectorAll<HTMLElement>(NavbarTwopanel.TRIGGER_SEL);
        if (!this.triggers.length) return;
        this.init();
    }

    private getPanel(trigger: HTMLElement): HTMLElement | null {
        return this.nav.querySelector<HTMLElement>(`[data-megamenu-panel="${trigger.dataset.megamenu}"]`);
    }

    private openPanel(panel: HTMLElement, trigger: HTMLElement): void {
        if (this.activePanel === panel) return;
        if (this.activePanel) this.closePanel(this.activePanel, true);
        if (this.closeTimer) clearTimeout(this.closeTimer);
        this.activePanel = panel;
        trigger.classList.add(NavbarTwopanel.CLASS_IS_OPEN);

        const typo3Active = panel.querySelector<HTMLElement>(
            `${NavbarTwopanel.INNER_TRIGGER_SEL}.${NavbarTwopanel.CLASS_IS_ACTIVE}[data-twopanel-trigger]`
        );
        const defaultTrigger = typo3Active ?? panel.querySelector<HTMLElement>(
            `${NavbarTwopanel.INNER_TRIGGER_SEL}[data-twopanel-trigger]`
        );
        if (defaultTrigger) {
            const section = panel.querySelector<HTMLElement>(
                `[data-twopanel-section="${defaultTrigger.dataset.twopanelTrigger}"]`
            );
            const resolved = section
                ? { t: defaultTrigger, s: section }
                : (() => {
                    const fb  = panel.querySelector<HTMLElement>(`${NavbarTwopanel.INNER_TRIGGER_SEL}[data-twopanel-trigger]`);
                    const fbs = fb ? panel.querySelector<HTMLElement>(`[data-twopanel-section="${fb.dataset.twopanelTrigger}"]`) : null;
                    return fb && fbs ? { t: fb, s: fbs } : null;
                })();
            if (resolved) this.activateSection(panel, resolved.t, resolved.s);
        }

        panel.classList.add(NavbarTwopanel.CLASS_IS_OPEN);
    }

    private closePanel(panel: HTMLElement, instant = false): void {
        const trigger = this.nav.querySelector<HTMLElement>(
            `[data-megamenu="${panel.dataset.megamenuPanel}"]`
        );
        if (trigger) trigger.classList.remove(NavbarTwopanel.CLASS_IS_OPEN);
        if (this.activePanel === panel) this.activePanel = null;

        if (instant) {
            panel.style.transition = 'none';
            panel.classList.remove(NavbarTwopanel.CLASS_IS_OPEN);
            void panel.offsetWidth; // force reflow so the style applies before transition is restored
            panel.style.transition = '';
        } else {
            panel.classList.remove(NavbarTwopanel.CLASS_IS_OPEN);
        }
    }

    private activateSection(
        panel: HTMLElement,
        innerTrigger: HTMLElement,
        section: HTMLElement,
    ): void {
        panel.querySelectorAll<HTMLElement>(NavbarTwopanel.INNER_TRIGGER_SEL)
            .forEach(t => t.classList.remove(NavbarTwopanel.CLASS_IS_ACTIVE));
        panel.querySelectorAll<HTMLElement>('[data-twopanel-section]')
            .forEach(s => s.classList.remove(NavbarTwopanel.CLASS_IS_ACTIVE));
        innerTrigger.classList.add(NavbarTwopanel.CLASS_IS_ACTIVE);
        section.classList.add(NavbarTwopanel.CLASS_IS_ACTIVE);
    }

    private init(): void {
        this.triggers.forEach(trigger => {
            const panel = this.getPanel(trigger);
            if (!panel) return;

            trigger.addEventListener('mouseenter', () => {
                if (this.closeTimer) clearTimeout(this.closeTimer);
                this.openPanel(panel, trigger);
            });
            trigger.addEventListener('mouseleave', () => {
                this.closeTimer = setTimeout(() => this.closePanel(panel), 180);
            });
            panel.addEventListener('mouseenter', () => {
                if (this.closeTimer) clearTimeout(this.closeTimer);
            });
            panel.addEventListener('mouseleave', () => {
                this.closeTimer = setTimeout(() => this.closePanel(panel), 180);
            });

            panel.querySelectorAll<HTMLElement>(NavbarTwopanel.INNER_TRIGGER_SEL).forEach(innerTrigger => {
                const sectionIndex = innerTrigger.dataset.twopanelTrigger;
                const section = sectionIndex
                    ? panel.querySelector<HTMLElement>(`[data-twopanel-section="${sectionIndex}"]`)
                    : null;
                if (!section) return;
                innerTrigger.addEventListener('mouseenter', () => {
                    this.activateSection(panel, innerTrigger, section);
                });
            });

            trigger.querySelector('.conn-megamenu-toggle')?.addEventListener('click', (e: Event) => {
                if (window.innerWidth >= NavbarTwopanel.BREAKPOINT_DESKTOP) {
                    e.preventDefault();
                    this.activePanel === panel
                        ? this.closePanel(panel)
                        : this.openPanel(panel, trigger);
                }
            });
        });

        document.addEventListener('click', (e: MouseEvent) => {
            if (this.activePanel && !this.nav.contains(e.target as Node)) {
                this.closePanel(this.activePanel);
            }
        });
        document.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Escape' && this.activePanel) this.closePanel(this.activePanel);
        });
    }

    static initAll(): void {
        const nav = document.getElementById(NavbarTwopanel.NAV_ID);
        if (!nav || !nav.classList.contains(NavbarTwopanel.NAV_CLASS)) return;
        new NavbarTwopanel(nav);
    }
}

import { registry } from '../Shared/registry';
registry.registerPage(() => NavbarTwopanel.initAll());
