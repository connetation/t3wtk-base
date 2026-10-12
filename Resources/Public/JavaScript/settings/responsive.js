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

/**
 * Site setting type "connResponsive" (Classes/Settings/ResponsiveSettingType.php):
 * Desktop / Tablet / Mobile tabs for one setting, stored as "desktop,tablet,mobile".
 * An empty tablet/mobile value means "same as the next larger device".
 *
 * options.choices → one select per device; otherwise a slider + number field
 * (options.min / max / step / unit). Same look as the content element field
 * connResponsiveRange; breakpoints come from devices.js (Utility/Devices.php).
 */
import { html, nothing } from 'lit';
import { BaseElement } from '@typo3/backend/settings/type/base.js';
import { TABLET_MIN, DESKTOP_MIN } from '@typo3/conn-t3wtk-base/devices.js';
import labels from '~labels/conn_t3wtk_base.be';

export const componentName = 'conn-settings-type-responsive';

const DEVICES = ['desktop', 'tablet', 'mobile'];
const ICONS = { desktop: 'actions-device-desktop', tablet: 'actions-device-tablet', mobile: 'actions-device-mobile' };

const label = (key) => labels.get('responsive_range.' + key);
const format = (text, ...args) => args.reduce((result, arg) => result.replace('%d', String(arg)), text);

/** "1200 px screen width and wider" / "768–1199 px …" / "… below 768 px" */
const rangeLabel = (device) => {
    if (device === 'desktop') {
        return format(label('range.from'), DESKTOP_MIN);
    }
    if (device === 'tablet') {
        return format(label('range.between'), TABLET_MIN, DESKTOP_MIN - 1);
    }
    return format(label('range.below'), TABLET_MIN);
};

class ResponsiveSettingElement extends BaseElement {
    static properties = {
        value: { type: String },
        active: { state: true },
    };

    constructor() {
        super();
        this.value = '';
        this.active = 'desktop';
    }

    get choices() {
        const choices = this.options?.choices;
        return choices && typeof choices === 'object' ? choices : null;
    }

    get parts() {
        const parts = String(this.value ?? '').split(',').map((part) => part.trim());
        return { desktop: parts[0] ?? '', tablet: parts[1] ?? '', mobile: parts[2] ?? '' };
    }

    /** The value a device actually uses (own value, else the next larger device's). */
    effective(device) {
        const parts = this.parts;
        for (let i = DEVICES.indexOf(device); i >= 0; i--) {
            if (parts[DEVICES[i]] !== '') {
                return parts[DEVICES[i]];
            }
        }
        return '';
    }

    display(value) {
        if (value === '') {
            return '';
        }
        if (this.choices) {
            return this.choices[value] ?? value;
        }
        return value + (this.options?.unit ?? '');
    }

    setPart(device, value) {
        const parts = this.parts;
        parts[device] = String(value ?? '').trim();
        if (device === 'desktop' && parts.desktop === '') {
            // Desktop can't inherit — keep a value.
            parts.desktop = this.effective('desktop') || String(this.options?.min ?? Object.keys(this.choices ?? {})[0] ?? '');
        }
        const serialized = DEVICES.map((d) => parts[d]);
        while (serialized.length > 1 && serialized[serialized.length - 1] === '') {
            serialized.pop();
        }
        this.value = serialized.join(',');
    }

    render() {
        const parts = this.parts;
        const active = this.active;
        const inherited = active !== 'desktop' && parts[active] === '';

        return html`
            <div class="conn-responsive-setting">
                <div class="btn-group btn-group-sm mb-1" role="tablist">
                    ${DEVICES.map((device) => html`
                        <button type="button" role="tab"
                            class="btn btn-default ${device === active ? 'active' : ''}"
                            aria-selected=${device === active ? 'true' : 'false'}
                            title="${label(device)}: ${rangeLabel(device)}"
                            @click=${() => { this.active = device; }}>
                            <typo3-backend-icon identifier=${ICONS[device]} size="small"></typo3-backend-icon>
                            ${label(device)}
                            <span class="ms-1 ${device !== 'desktop' && parts[device] === '' ? 'fst-italic opacity-50' : 'opacity-75'}">
                                ${this.display(this.effective(device))}
                            </span>
                        </button>`)}
                </div>
                <div class="form-text fw-semibold mt-0 mb-2">${label(active)}: ${rangeLabel(active)}</div>
                ${this.choices ? this.renderChoice(active, parts, inherited) : this.renderNumber(active, parts, inherited)}
                ${active !== 'desktop' ? html`
                    <div class="form-text d-flex align-items-center gap-2">
                        ${inherited ? html`<span>${label(active === 'tablet' ? 'inherit.desktop' : 'inherit.tablet')}</span>` : nothing}
                        ${!inherited && !this.readonly ? html`
                            <button type="button" class="btn btn-link btn-sm p-0" @click=${() => this.setPart(active, '')}>
                                ${label('reset')}
                            </button>` : nothing}
                    </div>` : nothing}
            </div>`;
    }

    renderChoice(active, parts, inherited) {
        return html`
            <select id=${this.formid} class="form-select" ?disabled=${this.readonly}
                @change=${(event) => this.setPart(active, event.target.value)}>
                ${active !== 'desktop' ? html`
                    <option value="" ?selected=${inherited}>
                        ${label(active === 'tablet' ? 'inherit.desktop' : 'inherit.mobile')}
                        (${this.display(this.effective(active))})
                    </option>` : nothing}
                ${Object.entries(this.choices).map(([key, text]) => html`
                    <option value=${key} ?selected=${parts[active] === key}>${text}${this.debug ? ` [${key}]` : ''}</option>`)}
            </select>`;
    }

    renderNumber(active, parts, inherited) {
        const min = this.options?.min ?? 0;
        const max = this.options?.max ?? 100;
        const step = this.options?.step ?? 1;
        const effective = this.effective(active);

        return html`
            <div class="d-flex align-items-center gap-3">
                <input type="range" class="form-range flex-grow-1" min=${min} max=${max} step=${step}
                    .value=${effective || String(min)} ?disabled=${this.readonly}
                    @input=${(event) => this.setPart(active, event.target.value)}>
                <div class="input-group input-group-sm" style="width: 7rem">
                    <input type="number" id=${this.formid} class="form-control" min=${min} max=${max} step=${step}
                        .value=${inherited ? '' : parts[active]} placeholder=${effective} ?readonly=${this.readonly}
                        @change=${(event) => this.setPart(active, event.target.value)}>
                    ${this.options?.unit ? html`<span class="input-group-text">${this.options.unit}</span>` : nothing}
                </div>
            </div>`;
    }
}

customElements.define(componentName, ResponsiveSettingElement);
