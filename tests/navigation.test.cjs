const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {join} = require('node:path');
const {runInNewContext} = require('node:vm');

// Minimal DOM fixture for interaction tests; it does not simulate layout.
class Element {
    constructor(tag = 'div', id = '', classes = []) {
        this.tag = tag;
        this.id = id;
        this.children = [];
        this.attributes = new Map();
        this.listeners = new Map();
        this.hidden = false;
        this.checked = false;
        const values = new Set(classes);
        this.classList = {
            add: (name) => values.add(name),
            contains: (name) => values.has(name),
            toggle: (name, force) => {
                const enabled = force ?? !values.has(name);
                enabled ? values.add(name) : values.delete(name);
                return enabled;
            },
        };
    }
    append(child) { child.parentElement = this; this.children.push(child); return child; }
    setAttribute(name, value) { this.attributes.set(name, String(value)); }
    getAttribute(name) { return this.attributes.get(name) ?? null; }
    addEventListener(name, listener) {
        if (!this.listeners.has(name)) this.listeners.set(name, []);
        this.listeners.get(name).push(listener);
    }
    emit(name, properties = {}) {
        (this.listeners.get(name) ?? []).forEach((listener) => listener({target: this, ...properties}));
    }
    click() { this.emit('click'); }
    focus() { this.focused = true; }
    contains(node) { return node === this || this.children.some((child) => child.contains(node)); }
    all() { return this.children.flatMap((child) => [child, ...child.all()]); }
    matches(selector) {
        if (selector.startsWith('.')) return this.classList.contains(selector.slice(1));
        if (selector.startsWith('#')) return this.id === selector.slice(1);
        if (selector.startsWith('[')) return this.attributes.has(selector.slice(1, -1));
        if (selector === 'input:checked') return this.tag === 'input' && this.checked;
        return this.tag === selector;
    }
    querySelectorAll(selector) {
        const parts = selector.split(' ');
        if (parts.length === 1) return this.all().filter((element) => element.matches(selector));
        return this.querySelectorAll(parts[0]).flatMap((element) => element.querySelectorAll(parts.slice(1).join(' ')));
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] ?? null; }
    getElementById(id) { return this.all().find((element) => element.id === id) ?? null; }
}

function navigationFixture() {
    const document = new Element('document');
    const header = document.append(new Element('header', 'headerNavigationContainer'));
    const language = header.append(new Element('details', '', ['encounters-language']));
    const summary = language.append(new Element('summary'));
    const toggle = header.append(new Element('button', '', ['pkp_site_nav_toggle']));
    toggle.hidden = true;
    const menu = header.append(new Element('nav', 'encounters-navigation'));
    const link = menu.append(new Element('a'));
    link.setAttribute('href', '/about');
    const button = menu.append(new Element('button'));
    button.setAttribute('data-encounters-submenu', '');
    button.setAttribute('aria-controls', 'submenu');
    button.setAttribute('aria-expanded', 'false');
    const submenu = menu.append(new Element('ul', 'submenu'));
    const desktop = new Element();
    desktop.matches = false;
    runInNewContext(readFileSync(join(__dirname, '../js/navigation.js'), 'utf8'), {
        document,
        window: {matchMedia: () => desktop},
    });
    return {document, header, language, summary, toggle, menu, link, button, submenu, desktop};
}

test('language menu closes on Escape and outside clicks', () => {
    const {document, language, summary, toggle} = navigationFixture();
    language.open = true;
    document.emit('click', {target: summary});
    assert.equal(language.open, true);
    language.emit('keydown', {key: 'Escape'});
    assert.equal(language.open, false);
    assert.equal(summary.focused, true);
    language.open = true;
    document.emit('click', {target: toggle});
    assert.equal(language.open, false);
});

test('mobile navigation toggles without parent scripts and Escape restores focus', () => {
    const {toggle, menu} = navigationFixture();
    assert.equal(toggle.hidden, false);
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    toggle.click();
    assert.equal(toggle.getAttribute('aria-expanded'), 'true');
    assert.equal(menu.classList.contains('pkp_site_nav_menu--isOpen'), true);
    menu.emit('keydown', {key: 'Escape'});
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    assert.equal(toggle.focused, true);
});

test('submenu disclosure preserves the parent link and closes on Escape', () => {
    const {button, submenu, menu, link} = navigationFixture();
    assert.equal(submenu.hidden, true);
    button.click();
    assert.equal(button.getAttribute('aria-expanded'), 'true');
    assert.equal(submenu.hidden, false);
    assert.equal(link.getAttribute('href'), '/about');
    menu.emit('keydown', {key: 'Escape'});
    assert.equal(submenu.hidden, true);
    assert.equal(button.focused, true);
});

test('Escape on the mobile Menu button closes the menu and its submenus', () => {
    const {toggle, menu, button, submenu} = navigationFixture();
    toggle.click();
    button.click();
    toggle.emit('keydown', {key: 'Escape'});
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    assert.equal(menu.classList.contains('pkp_site_nav_menu--isOpen'), false);
    assert.equal(button.getAttribute('aria-expanded'), 'false');
    assert.equal(submenu.hidden, true);
});

test('outside clicks and breakpoint changes reset expanded menus', () => {
    const {document, toggle, button, submenu, desktop} = navigationFixture();
    toggle.click();
    button.click();
    document.emit('click', {target: new Element()});
    assert.equal(submenu.hidden, true);
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
    toggle.click();
    button.click();
    desktop.matches = true;
    desktop.emit('change');
    assert.equal(submenu.hidden, true);
    assert.equal(toggle.getAttribute('aria-expanded'), 'false');
});

test('reviewer interests and journal consent follow registration selections', () => {
    const document = new Element('document');
    const reviewer = document.append(new Element('div', 'reviewerOptinGroup'));
    const checkbox = reviewer.append(new Element('input'));
    const interests = document.append(new Element('div', 'reviewerInterests'));
    const contexts = document.append(new Element('div', 'contextOptinGroup'));
    const context = contexts.append(new Element());
    const roles = context.append(new Element('div', '', ['roles']));
    const role = roles.append(new Element('input'));
    const consent = context.append(new Element('div', '', ['context_privacy']));
    runInNewContext(readFileSync(join(__dirname, '../js/forms.js'), 'utf8'), {document});
    assert.equal(interests.classList.contains('is_visible'), false);
    assert.equal(interests.hidden, true);
    checkbox.checked = true;
    reviewer.emit('change');
    assert.equal(interests.classList.contains('is_visible'), true);
    assert.equal(interests.hidden, false);
    checkbox.checked = false;
    reviewer.emit('change');
    assert.equal(interests.hidden, true);
    role.checked = true;
    roles.emit('change');
    assert.equal(consent.classList.contains('context_privacy_visible'), true);
    assert.equal(consent.hidden, false);
    role.checked = false;
    roles.emit('change');
    assert.equal(consent.classList.contains('context_privacy_visible'), false);
    assert.equal(consent.hidden, true);
});

test('search month names follow the page language without changing selected values', () => {
    for (const [lang, january, december] of [['en', 'Jan', 'Dec'], ['es', 'ene', 'dic'], ['fr', 'janv.', 'déc.']]) {
        const document = new Element('document');
        document.documentElement = {lang};
        const selects = ['dateFromMonth', 'dateToMonth'].map((id) => {
            const select = document.append(new Element('select', id));
            select.options = [{value: '', textContent: ''}, ...Array.from({length: 12}, (_, month) => ({value: String(month + 1), textContent: 'English month', selected: month === 8}))];
            return select;
        });
        runInNewContext(readFileSync(join(__dirname, '../js/forms.js'), 'utf8'), {document});
        for (const select of selects) {
            assert.equal(select.options[0].textContent, '');
            assert.equal(select.options[1].textContent, january);
            assert.equal(select.options[12].textContent, december);
            assert.equal(select.options[9].value, '9');
            assert.equal(select.options[9].selected, true);
        }
    }
});
