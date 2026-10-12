/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { Plugin } from '@ckeditor/ckeditor5-core';

/**
 * CKEditor's Style dropdown toggles styles independently, so picking a second
 * style of the same kind stacks classes (btn btn-primary btn-success,
 * <h1 class="h2 h3">) and whichever rule comes last in main.css wins. This
 * makes styles within a group mutually exclusive: applying one first removes
 * any other active style of the same group.
 *
 * Groups are derived from each style definition's classes:
 *   - buttons:       any style with the `btn` class
 *   - heading sizes: any style with one of the `h1`–`h6` classes
 */
const GROUPS = {
    button: (classes) => classes.includes('btn'),
    headingSize: (classes) => classes.some((cls) => /^h[1-6]$/.test(cls)),
};

export class ExclusiveStyles extends Plugin {
    static get pluginName() {
        return 'ExclusiveStyles';
    }

    afterInit() {
        const command = this.editor.commands.get('style');
        if (!command) {
            return;
        }

        // style name → group name
        const groupOf = new Map();
        for (const definition of this.editor.config.get('style.definitions') || []) {
            const classes = definition.classes || [];
            const group = Object.keys(GROUPS).find((name) => GROUPS[name](classes));
            if (group) {
                groupOf.set(definition.name, group);
            }
        }
        let removing = false;

        this.listenTo(command, 'execute', (evt, [options = {}]) => {
            const { styleName, forceValue } = options;
            const group = groupOf.get(styleName);
            if (removing || !group) {
                return;
            }
            // Toggling the active style off — nothing to clear.
            if (forceValue === false || (forceValue === undefined && command.value.includes(styleName))) {
                return;
            }

            const others = command.value.filter((name) => name !== styleName && groupOf.get(name) === group);
            removing = true;
            try {
                for (const name of others) {
                    command.execute({ styleName: name, forceValue: false });
                }
            } finally {
                removing = false;
            }
        }, { priority: 'high' });
    }
}
