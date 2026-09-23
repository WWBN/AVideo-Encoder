<?php
require_once $global['systemRootPath'] . 'objects/EncoderVersion.php';
$encoderInstalled = EncoderVersion::installed($global['systemRootPath']);
$escapeVersion = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$encoderVersionStates = [
    'checking' => ['muted', 'Checking for updates...', 'Checking the installed commit against GitHub (master).', 'fa-clock'],
    'current' => ['success', 'Your code is up to date', 'The installed commit matches the latest commit on GitHub (master).', 'fa-check'],
    'update' => ['warning', 'Your code is outdated', 'Update available: %s new commits.', 'fa-arrow-up'],
    'newer' => ['info', 'Ahead of GitHub', 'The installed commit includes changes beyond GitHub (master).', 'fa-code-branch'],
    'diverged' => ['warning', 'Commit histories have diverged', 'Local and available code have diverged. Review the changes before updating.', 'fa-code-branch'],
    'unknown' => ['muted', 'Update status unknown', 'The installed commit could not be compared with GitHub (master). Update status is unknown.', 'fa-question']
];
$encoderReleaseStates = [
    'checking' => ['muted', 'Checking for updates...', 'A release is a published snapshot. The master branch may contain newer changes.'],
    'current' => ['success', 'Latest release installed', 'The installed commit matches the latest published release.'],
    'update' => ['warning', 'New release available', 'The latest published release includes changes not present in this installation.'],
    'newer' => ['info', 'Newer than the latest release', 'This installation includes commits made after the latest published release.'],
    'diverged' => ['warning', 'Different development history', 'Local and available code have diverged. Review the changes before updating.'],
    'unknown' => ['muted', 'Release status unavailable', 'The installed code could not be compared with the latest release. This does not confirm that the system is up to date.']
];
$encoderVersionLabels = [];
foreach ([$encoderVersionStates, $encoderReleaseStates] as $states) {
    foreach ($states as $state) {
        $encoderVersionLabels[$state[1]] = __($state[1]);
        $encoderVersionLabels[$state[2]] = __($state[2]);
    }
}
$encoderVersionLabels['Unavailable'] = __('Unavailable');
$encoderVersionLabels['View changes'] = __('View changes');
?>
<div class="version-dashboard version-dashboard-compact" id="encoderSoftwareVersion">
    <div class="version-toolbar">
        <p class="version-eyebrow"><i class="fas fa-code-branch" aria-hidden="true"></i> Encoder <span aria-hidden="true">/</span> <?php echo __('Updates & versions'); ?></p>
        <span class="version-source text-muted"><i class="fab fa-github" aria-hidden="true"></i> WWBN/AVideo-Encoder &middot; master</span>
    </div>
    <section class="panel panel-default version-hero" aria-label="<?php echo $escapeVersion(__('Code update status')); ?>">
        <div class="version-hero-glow text-muted" data-version-tone aria-hidden="true"></div>
        <div class="version-hero-main">
            <div class="version-hero-copy" role="status" aria-live="polite">
                <p class="version-eyebrow text-muted" data-version-tone><?php echo __('Code update status'); ?></p>
                <h2 class="version-hero-title" data-version-title><?php echo __('Checking for updates...'); ?></h2>
                <p class="version-hero-description text-muted" data-version-description><?php echo __('Checking the installed commit against GitHub (master).'); ?></p>
                <div class="version-actions">
                    <button type="button" class="btn btn-default" id="checkEncoderVersion"><i class="fas fa-sync" aria-hidden="true"></i> <?php echo __('Check for updates'); ?></button>
                    <a class="btn btn-default" data-version-guide href="https://github.com/WWBN/AVideo-Encoder/blob/master/RELEASING.md#installed-software-and-update-status" target="_blank" rel="noopener noreferrer"><i class="fas fa-book-open" aria-hidden="true"></i> <?php echo __('Update guide'); ?></a>
                    <span data-version-changes="master"></span>
                </div>
            </div>
            <div class="version-status-seal text-muted" data-version-tone aria-hidden="true"><div class="version-status-ring"><i class="fas fa-clock" data-version-icon></i></div></div>
        </div>
        <div class="version-commit-strip">
            <div class="version-commit">
                <p class="version-eyebrow text-muted"><i class="fas fa-server" aria-hidden="true"></i> <?php echo __('Installed code'); ?></p>
                <p class="version-commit-value">
                    <?php if ($encoderInstalled['commit']) { ?>
                        <a target="_blank" rel="noopener noreferrer" href="<?php echo EncoderVersion::REPOSITORY . '/commit/' . $encoderInstalled['commit']; ?>"><?php echo substr($encoderInstalled['commit'], 0, 12); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                    <?php } else { echo __('Unavailable'); } ?>
                </p>
                <p class="small text-muted"><?php echo __('Branch'); ?>: <?php echo $escapeVersion($encoderInstalled['branch'] ?: __('Unavailable')); ?></p>
                <p class="small text-muted"><?php echo __('Installed version'); ?>: <?php echo $escapeVersion($encoderInstalled['version'] ?: __('Unavailable')); ?></p>
            </div>
            <div class="version-commit-connector text-muted" data-version-tone aria-hidden="true"><span data-version-connector>?</span></div>
            <div class="version-commit">
                <p class="version-eyebrow text-muted"><i class="fab fa-github" aria-hidden="true"></i> <?php echo __('Latest on GitHub'); ?></p>
                <p class="version-commit-value" data-version-name="master"><?php echo __('Unavailable'); ?></p>
                <p class="small text-muted"><?php echo __('Last commit on GitHub'); ?> &middot; master</p>
            </div>
        </div>
        <div class="version-check-time text-muted"><i class="far fa-clock" aria-hidden="true"></i> <?php echo __('Results are cached for up to 1 hour. GitHub rate limits may delay new checks. Checking does not install updates.'); ?></div>
    </section>
    <?php if (!$encoderInstalled['commit']) { ?>
        <div class="alert alert-warning"><?php echo __('This installation has no readable Git or build metadata. Its update status cannot be determined.'); ?></div>
    <?php } elseif ($encoderInstalled['modified']) { ?>
        <div class="alert alert-warning"><?php echo __('Local tracked files have changes. The comparison refers to the installed commit; review local changes before updating.'); ?></div>
    <?php } ?>
    <div class="version-section-intro">
        <h2><?php echo __('Release & database'); ?></h2>
        <p class="text-muted"><?php echo __('The release number and the Encoder database version are independent. Code update status is determined by commit history.'); ?></p>
    </div>
    <div class="version-cards">
        <section class="panel panel-default version-card">
            <div class="version-card-heading"><h3><i class="fas fa-tag" aria-hidden="true"></i> <?php echo __('Latest release'); ?></h3><span class="version-pill text-muted" data-release-status role="status" aria-live="polite"><?php echo __('Checking for updates...'); ?></span></div>
            <p class="version-value" data-version-name="release"><?php echo __('Unavailable'); ?></p>
            <p class="text-muted" data-release-description><?php echo __('A release is a published snapshot. The master branch may contain newer changes.'); ?></p>
            <div class="version-card-links"><a href="https://github.com/WWBN/AVideo-Encoder/releases" target="_blank" rel="noopener noreferrer" data-release-notes><?php echo __('Release notes'); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a><span data-version-changes="release"></span></div>
        </section>
        <section class="panel panel-default version-card">
            <div class="version-card-heading"><h3><i class="fas fa-database" aria-hidden="true"></i> <?php echo __('Encoder version (database)'); ?></h3><span class="version-pill text-<?php echo $encoderUpdateFiles ? 'warning' : 'success'; ?>"><?php echo $encoderUpdateFiles ? sprintf(__('%s pending database updates'), count($encoderUpdateFiles)) : __('No pending local migrations'); ?></span></div>
            <p class="version-value"><?php echo $escapeVersion($config->getVersion()); ?></p>
            <p class="text-muted"><?php echo __('Tracks database migrations, independently of releases.'); ?></p>
            <p class="small text-muted"><?php echo __('The database version changes only when a database migration is applied. It is not expected to match the release tag or commit.'); ?></p>
        </section>
    </div>
    <details class="version-explanation">
        <summary><i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo __('Why are these versions different?'); ?></summary>
        <dl>
            <dt><?php echo __('Installed code'); ?></dt><dd class="text-muted"><?php echo __('The commit identifies the installed Git revision. Local file edits are not included in the comparison.'); ?></dd>
            <dt><?php echo __('Latest release'); ?></dt><dd class="text-muted"><?php echo __('A release is a published snapshot. The master branch may contain newer changes.'); ?></dd>
            <dt><?php echo __('How to update'); ?></dt><dd class="text-muted"><?php echo __('For a Git installation, update the code on the server. For Docker, deploy a newer image. Then apply any pending database updates below.'); ?></dd>
        </dl>
    </details>
</div>
<script>
$(function () {
    var labels = <?php echo json_encode($encoderVersionLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var codeStates = <?php echo json_encode($encoderVersionStates); ?>;
    var releaseStates = <?php echo json_encode($encoderReleaseStates); ?>;
    var panel = $('#encoderSoftwareVersion');
    var loaded = false;
    var loading = false;
    function setState(channel, state, commits) {
        var status = (channel === 'master' ? codeStates : releaseStates)[state];
        if (channel === 'master') {
            panel.find('[data-version-title]').text(labels[status[1]]);
            panel.find('[data-version-description]').text(labels[status[2]].replace('%s', commits || 0));
            panel.find('[data-version-tone]').removeClass('text-muted text-success text-warning text-info').addClass('text-' + status[0]);
            panel.find('[data-version-icon]').attr('class', 'fas ' + status[3]);
            panel.find('[data-version-guide]').attr('class', 'btn btn-' + (status[0] === 'muted' ? 'default' : status[0]));
            panel.find('[data-version-connector]').text(state === 'current' ? '=' : (state === 'unknown' || state === 'checking' ? '?' : '≠'));
        } else {
            panel.find('[data-release-status]').attr('class', 'version-pill text-' + status[0]).text(labels[status[1]]);
            panel.find('[data-release-description]').text(labels[status[2]]);
        }
    }
    function renderChannel(channel, data) {
        var states = channel === 'master' ? codeStates : releaseStates;
        var state = data && states[data.state] ? data.state : 'unknown';
        setState(channel, state, data && data.commits);
        var name = panel.find('[data-version-name="' + channel + '"]').empty();
        var changes = panel.find('[data-version-changes="' + channel + '"]').empty();
        if (data) {
            $('<a>', {href: data.url, target: '_blank', rel: 'noopener noreferrer'}).text(data.name).appendTo(name);
            if (data.changesUrl && state !== 'current') {
                $('<a>', {href: data.changesUrl, target: '_blank', rel: 'noopener noreferrer', 'class': channel === 'master' ? 'btn btn-default' : ''}).text(labels['View changes']).appendTo(changes);
            }
        } else {
            name.text(labels['Unavailable']);
        }
        if (channel === 'release') {
            panel.find('[data-release-notes]').attr('href', data ? data.url : 'https://github.com/WWBN/AVideo-Encoder/releases');
        }
    }
    function checkVersion() {
        if (loading) { return; }
        loading = true;
        loaded = true;
        $('#checkEncoderVersion').prop('disabled', true);
        setState('master', 'checking');
        setState('release', 'checking');
        modal.showPleaseWait();
        $.ajax({
            url: <?php echo json_encode($global['webSiteRootURL'] . 'view/encoderVersion.json.php?' . getPHPSessionIDURL(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            dataType: 'json',
            timeout: 30000
        }).done(function (response) {
            var result = response && !response.error ? response.result : null;
            renderChannel('release', result && result.release);
            renderChannel('master', result && result.master);
        }).fail(function () {
            renderChannel('release', null);
            renderChannel('master', null);
        }).always(function () {
            loading = false;
            $('#checkEncoderVersion').prop('disabled', false);
            modal.hidePleaseWait();
        });
    }
    $('#checkEncoderVersion').on('click', checkVersion);
    $('a[href="#update"]').on('shown.bs.tab', function () {
        if (!loaded) { checkVersion(); }
    });
    if ($('#update').hasClass('active')) { checkVersion(); }
});
</script>
