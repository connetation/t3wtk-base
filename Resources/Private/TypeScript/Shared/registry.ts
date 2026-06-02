/**
 * Central component registry.
 *
 * Components call registry.register(selector, factory) as a module side-effect.
 * A single DOMContentLoaded listener fires all of them.
 * Call registry.init(root) after injecting AJAX content to reinitialise
 * per-element components within that subtree.
 */

type ElementFactory = (el: HTMLElement) => void;

interface PerElementDef {
    kind: 'element';
    selector: string;
    factory: ElementFactory;
}

interface PageDef {
    kind: 'page';
    factory: () => void;
}

type ComponentDef = PerElementDef | PageDef;

class ConnElementsRegistry {
    private readonly defs: ComponentDef[] = [];
    private domReady = false;

    constructor() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.domReady = true;
                this.runAll(document);
            }, { once: true });
        } else {
            this.domReady = true;
        }
    }

    /**
     * Register a per-element component.
     * factory(el) is called once for every matching element found.
     * Works correctly with registry.init(root) for AJAX subtrees.
     */
    register(selector: string, factory: ElementFactory): void {
        this.defs.push({ kind: 'element', selector, factory });
        if (this.domReady) {
            document.querySelectorAll<HTMLElement>(selector).forEach(factory);
        }
    }

    /**
     * Register a page-level singleton initialiser (navbar, animations, etc.).
     * Runs once on DOMContentLoaded and is intentionally skipped when
     * registry.init(root) is called with a non-document root.
     */
    registerPage(factory: () => void): void {
        this.defs.push({ kind: 'page', factory });
        if (this.domReady) factory();
    }

    /**
     * Initialise all per-element components within root.
     * Pass a newly inserted DOM node after AJAX content loads.
     * Page-level singletons are only re-run when root is the document itself.
     */
    init(root: ParentNode = document): void {
        const isDocument = root === document;
        this.defs.forEach(def => {
            if (def.kind === 'element') {
                root.querySelectorAll<HTMLElement>(def.selector).forEach(def.factory);
            } else if (isDocument) {
                def.factory();
            }
        });
    }

    private runAll(root: ParentNode): void {
        this.defs.forEach(def => {
            if (def.kind === 'element') {
                root.querySelectorAll<HTMLElement>(def.selector).forEach(def.factory);
            } else {
                def.factory();
            }
        });
    }
}

export const registry = new ConnElementsRegistry();
