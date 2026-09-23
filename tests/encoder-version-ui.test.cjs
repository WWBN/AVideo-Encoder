// Run with: node --test tests/encoder-version-ui.test.cjs
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const vm = require('node:vm');
const source = readFileSync(join(__dirname, '../update/version.js'), 'utf8');
const local = 'a'.repeat(40);
const master = 'b'.repeat(40);
const release = 'c'.repeat(40);

async function render(options = {}) {
    class Element {
        constructor() { this.children = []; this.events = {}; this.attributes = {}; }
        set textContent(value) { this.text = value; this.children = []; }
        get textContent() { return this.text; }
        appendChild(child) { this.children.push(child); }
        querySelector() { return this.children[0] || null; }
        setAttribute(key, value) { this.attributes[key] = value; }
        addEventListener(event, callback) { this.events[event] = callback; }
    }
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) elements.set(id, new Element());
        return elements.get(id);
    };
    element('encoder-version-data').textContent = JSON.stringify({
        installed: { sha: options.unknown ? null : options.identical ? master : local, dirty: !!options.dirty }, messages: {}
    });
    const requests = [];
    const storage = new Map();
    const context = {
        document: { getElementById: element, createElement: () => new Element(), documentElement: { lang: 'pt_br' } },
        $: target => typeof target === 'function' ? target() : { on() {}, hasClass: () => true },
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
        AbortController, setTimeout, clearTimeout,
        fetch: async url => {
            requests.push(url);
            const path = url.replace('https://api.github.com/repos/WWBN/AVideo-Encoder', '');
            let value;
            let code = 200;
            if (path === '/commits/master') {
                code = options.masterError || 200;
                value = { sha: master, commit: { committer: { date: '2026-09-23T17:30:30Z' } } };
            } else if (path === '/releases/latest') {
                // Delayed release errors must never replace the repository status.
                await new Promise(resolve => setImmediate(resolve));
                code = options.releaseError || 200;
                value = { tag_name: 'v999.0.0', published_at: '2026-09-22T12:00:00Z' };
            } else if (path === '/commits/v999.0.0') {
                value = { sha: release };
            } else {
                assert.equal(path, '/compare/' + master + '...' + local + '?per_page=1');
                code = options.compareError || 200;
                value = { status: options.comparison || 'behind', behind_by: options.count ?? 3 };
            }
            return { ok: code === 200, status: code, headers: { get: () => code === 403 ? '0' : null }, json: async () => value };
        }
    };
    vm.runInNewContext(source, context);
    for (let tries = 0; tries < 100 && element('encoder-version-refresh').disabled; tries++) {
        await new Promise(resolve => setImmediate(resolve));
    }
    assert.equal(element('encoder-version-refresh').disabled, false, 'refresh is enabled after completion');
    return { element: id => element('encoder-' + id), requests };
}

test('outdated status uses master history, never the unrelated release number', async () => {
    const ui = await render();
    assert.equal(ui.element('version-status').textContent, 'Outdated: 3 commits behind GitHub master.');
    assert.equal(ui.element('version-compare').href, 'https://github.com/WWBN/AVideo-Encoder/compare/' + local + '...' + master);
    assert.match(ui.element('master-date').textContent, /^Last commit on GitHub: .*2026/);
    assert.ok(!ui.requests.some(url => url.includes('/compare/' + release)));
});
test('one pending commit uses singular wording', async () => {
    assert.equal((await render({ count: 1 })).element('version-status').textContent, 'Outdated: 1 commit behind GitHub master.');
});
test('same SHA is current even if the release differs or is unavailable', async () => {
    const ui = await render({ identical: true, releaseError: 404 });
    assert.equal(ui.element('version-status').textContent, 'Up to date with GitHub master.');
    assert.equal(ui.element('version-status').className, 'encoder-version-status is-current');
    assert.ok(!ui.requests.some(url => url.includes('/compare/')));
});
test('local edits are disclosed when the base commit is current', async () => {
    const ui = await render({ identical: true, dirty: true });
    assert.equal(ui.element('version-status').textContent, 'Up to date with GitHub master, with local source changes.');
    assert.ok(!ui.element('version-status').className.includes('is-current'));
});
test('additional commits are distinguished from missing upstream commits', async () => {
    const ui = await render({ comparison: 'ahead' });
    assert.equal(ui.element('version-status').textContent, 'Includes all commits from GitHub master, plus additional commits.');
});
test('diverged histories are not marked current', async () => {
    const ui = await render({ comparison: 'diverged' });
    assert.match(ui.element('version-status').textContent, /History has diverged/);
});
test('unknown installation cannot be marked current or outdated', async () => {
    const ui = await render({ unknown: true });
    assert.match(ui.element('version-status').textContent, /automatic comparison is not possible/);
    assert.ok(!ui.requests.some(url => url.includes('/compare/')));
});
test('repository or comparison failures are explicit, with release data still available', async () => {
    const limited = await render({ masterError: 403 });
    assert.match(limited.element('version-status').textContent, /request limit reached/);
    assert.equal(limited.element('release-value').children[0].textContent, 'v999.0.0');
    const unavailable = await render({ compareError: 404 });
    assert.equal(unavailable.element('version-status').textContent, 'Could not compare this installation with GitHub master.');
    assert.equal(unavailable.element('version-compare').hidden, true);
});
