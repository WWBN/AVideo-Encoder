(function () {
    'use strict';
    var dataElement = document.getElementById('encoder-version-data');
    if (!dataElement) return;
    var data = JSON.parse(dataElement.textContent);
    var installed = data.installed;
    var repository = 'https://github.com/WWBN/AVideo-Encoder';
    var api = 'https://api.github.com/repos/WWBN/AVideo-Encoder';
    var busy = false;
    var started = false;
    function t(message) { return data.messages[message] || message; }
    function element(id) { return document.getElementById('encoder-' + id); }
    function text(id, value) { element(id).textContent = value; }
    function validSha(sha) { return typeof sha === 'string' && /^[0-9a-f]{40}$/.test(sha); }
    function requestError(error) {
        return error.rateLimited ? 'GitHub request limit reached. Please try again later.' : 'Could not check GitHub. Try again later.';
    }
    function status(message, style, count) {
        text('version-status', t(message).replace('%s', count));
        element('version-status').className = 'encoder-version-status' + (style ? ' is-' + style : '');
    }
    function link(id, label, path, title) {
        var anchor = document.createElement('a');
        anchor.href = repository + path;
        anchor.textContent = label;
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';
        if (title) anchor.title = title;
        element(id).textContent = '';
        element(id).appendChild(anchor);
    }
    function date(value) {
        var parsed = new Date(value);
        if (isNaN(parsed.getTime())) return t('Not available');
        // The application also uses locale identifiers such as pt_br / en_us.
        var locale = document.documentElement.lang.replace(/_/g, '-') || undefined;
        var options = { dateStyle: 'medium', timeStyle: 'long' };
        try { return parsed.toLocaleString(locale, options); }
        catch (error) { return parsed.toLocaleString(undefined, options); }
    }
    async function compareMaster(masterSha, refresh) {
        if (!validSha(installed.sha)) {
            status('Install metadata is unavailable; automatic comparison is not possible.');
            return;
        }
        if (installed.sha === masterSha) {
            status(installed.dirty ? 'Up to date with GitHub master, with local source changes.'
                : 'Up to date with GitHub master.', installed.dirty ? 'update' : 'current');
            return;
        }
        try {
            // With master as the base, behind_by counts commits missing locally.
            // Never compare release numbers, database versions or commit dates.
            var comparison = await request('/compare/' + masterSha + '...' + installed.sha + '?per_page=1', refresh);
            if (comparison.status === 'behind' && Number.isSafeInteger(comparison.behind_by) && comparison.behind_by > 0) {
                status(comparison.behind_by === 1 ? 'Outdated: 1 commit behind GitHub master.'
                    : 'Outdated: %s commits behind GitHub master.', 'update', comparison.behind_by);
            } else if (comparison.status === 'ahead') {
                status('Includes all commits from GitHub master, plus additional commits.');
            } else if (comparison.status === 'diverged') {
                status('History has diverged from GitHub master. Both sides have unique commits.', 'update');
            } else {
                throw new Error('Unknown comparison');
            }
            element('version-compare').href = repository + '/compare/' + (comparison.status === 'ahead'
                ? masterSha + '...' + installed.sha : installed.sha + '...' + masterSha);
            element('version-compare').hidden = false;
        } catch (error) {
            status(error.rateLimited ? requestError(error) : 'Could not compare this installation with GitHub master.');
        }
    }
    async function request(path, refresh) {
        var key = 'encoder-version:' + path;
        if (!refresh) {
            try {
                var cached = JSON.parse(sessionStorage.getItem(key));
                if (cached && Date.now() - cached.time < 300000) return cached.value;
            } catch (ignore) { /* Storage may be disabled. */ }
        }
        var controller = new AbortController();
        var timeout = setTimeout(function () { controller.abort(); }, 10000);
        try {
            var response = await fetch(api + path, {
                headers: { Accept: 'application/vnd.github+json' },
                credentials: 'omit', signal: controller.signal
            });
            if (!response.ok) {
                var error = new Error('GitHub request failed');
                error.status = response.status;
                error.rateLimited = response.status === 429 || (response.status === 403 && response.headers.get('X-RateLimit-Remaining') === '0');
                throw error;
            }
            var value = await response.json();
            try { sessionStorage.setItem(key, JSON.stringify({ time: Date.now(), value: value })); } catch (ignore) {}
            return value;
        } finally { clearTimeout(timeout); }
    }
    async function check(refresh) {
        if (busy) return;
        busy = true;
        started = true;
        element('version-refresh').disabled = true;
        element('version-panel').setAttribute('aria-busy', 'true');
        element('version-compare').hidden = true;
        status('Checking GitHub...');
        ['release-commit', 'release-date', 'master-date', 'master-status', 'version-checked'].forEach(function (id) { text(id, ''); });
        text('release-value', '…');
        text('master-value', '…');
        var releaseSha = null;
        var masterSha = null;
        var checked = false;
        try {
            await Promise.all([
                (async function () {
                    try {
                        var release = await request('/releases/latest', refresh);
                        if (!release.tag_name || release.draft || release.prerelease) throw new Error('Invalid release');
                        checked = true;
                        link('release-value', release.tag_name, '/releases/tag/' + encodeURIComponent(release.tag_name));
                        text('release-date', t('Published') + ': ' + date(release.published_at));
                        // Resolve the tag itself: target_commitish may be a moving branch name.
                        var commit = await request('/commits/' + encodeURIComponent(release.tag_name), refresh);
                        if (!validSha(commit.sha)) throw new Error('Invalid release commit');
                        releaseSha = commit.sha;
                        link('release-commit', t('Commit') + ': ' + releaseSha.slice(0, 12), '/commit/' + releaseSha, releaseSha);
                    } catch (error) {
                        if (!element('release-value').querySelector('a')) {
                            text('release-value', t(error.status === 404 ? 'No published release' : 'Not available'));
                        }
                        // Release availability must not override the repository status.
                        text('release-commit', t(error.status === 404 && !checked ? 'No published release' : requestError(error)));
                    }
                }()),
                (async function () {
                    try {
                        var commit = await request('/commits/master', refresh);
                        if (!validSha(commit.sha)) throw new Error('Invalid commit');
                        masterSha = commit.sha;
                        link('master-value', masterSha.slice(0, 12), '/commit/' + masterSha, masterSha);
                        text('master-date', t('Last commit on GitHub') + ': ' + date(commit.commit.committer.date));
                        await compareMaster(masterSha, refresh);
                    } catch (error) {
                        text('master-value', t('Not available'));
                        text('master-status', t(requestError(error)));
                        status(requestError(error));
                    }
                }())
            ]);
            if (masterSha && releaseSha) {
                text('master-status', t(masterSha === releaseSha
                    ? 'The release and master point to the same commit.'
                    : 'Master and the published release point to different commits.'));
            }
            if (checked || masterSha) text('version-checked', t('Checked at') + ' ' + date(new Date().toISOString()));
        } finally {
            busy = false;
            element('version-refresh').disabled = false;
            element('version-panel').setAttribute('aria-busy', 'false');
        }
    }
    element('version-refresh').addEventListener('click', function () { check(true); });
    // GitHub is queried only when the administrator opens the Update tab.
    $(function () {
        $('a[data-toggle="tab"][href="#update"]').on('shown.bs.tab', function () {
            if (!started) check(false);
        });
        if ($('#update').hasClass('active')) check(false);
    });
}());
