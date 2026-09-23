<?php
if (!class_exists('Login') || !Login::isAdmin()) {
    http_response_code(403);
    return;
}
require_once __DIR__ . '/../objects/EncoderVersion.php';
$installedVersion = EncoderVersion::installed($global['systemRootPath']);
$versionEscape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$versionMessages = [];
foreach ([
    'Checking GitHub...', 'Check again', 'Not available', 'No published release',
    'GitHub request limit reached. Please try again later.',
    'Could not check GitHub. Try again later.', 'Could not compare this installation with GitHub master.',
    'Up to date with GitHub master.', 'Up to date with GitHub master, with local source changes.',
    'Outdated: 1 commit behind GitHub master.', 'Outdated: %s commits behind GitHub master.',
    'Includes all commits from GitHub master, plus additional commits.',
    'History has diverged from GitHub master. Both sides have unique commits.',
    'Install metadata is unavailable; automatic comparison is not possible.',
    'Checked at', 'View changes', 'Published', 'Commit', 'Last commit on GitHub',
    'The release and master point to the same commit.', 'Master and the published release point to different commits.'
] as $message) {
    $versionMessages[$message] = __($message);
}
?>
<link rel="stylesheet" href="<?php echo $versionEscape($global['webSiteRootURL']); ?>update/version.css?v=<?php echo filemtime(__DIR__ . '/version.css'); ?>">
<section id="encoder-version-panel" class="encoder-versions" aria-labelledby="encoder-version-title">
    <div class="encoder-version-heading">
        <div>
            <div class="encoder-version-eyebrow">AVideo Encoder</div>
            <h2 id="encoder-version-title"><?php echo __('Versions and updates'); ?></h2>
            <p><?php echo __('Update status is based on the installed commit compared with GitHub master.'); ?></p>
        </div>
        <button type="button" class="btn btn-default" id="encoder-version-refresh">
            <i class="fas fa-sync-alt" aria-hidden="true"></i> <span><?php echo __('Check again'); ?></span>
        </button>
    </div>
    <div id="encoder-version-status" class="encoder-version-status" role="status" aria-live="polite">
        <?php echo __('Checking GitHub...'); ?>
    </div>
    <div class="encoder-version-grid">
        <article class="encoder-version-card">
            <h3><i class="fas fa-server" aria-hidden="true"></i> <?php echo __('Installed code'); ?></h3>
            <div class="encoder-version-value">
                <?php if ($installedVersion['sha']) { ?>
                    <a class="encoder-commit" href="https://github.com/WWBN/AVideo-Encoder/commit/<?php echo $installedVersion['sha']; ?>" target="_blank" rel="noopener noreferrer" title="<?php echo $installedVersion['sha']; ?>"><?php echo substr($installedVersion['sha'], 0, 12); ?></a>
                <?php } else { echo __('Not available'); } ?>
            </div>
            <p><?php echo __('Exact commit of this installation.'); ?></p>
            <?php if ($installedVersion['source'] === 'git') { ?>
                <div class="encoder-version-detail"><?php echo __('Branch'); ?>: <strong><?php echo $versionEscape($installedVersion['branch'] ?: __('Detached HEAD')); ?></strong></div>
                <?php if ($installedVersion['tag']) { ?>
                    <div class="encoder-version-detail"><?php echo __('Local tag'); ?>: <strong><?php echo $versionEscape($installedVersion['tag']); ?></strong></div>
                <?php } ?>
                <?php if ($installedVersion['dirty']) { ?>
                    <span class="encoder-version-badge"><?php echo __('Local source changes'); ?></span>
                <?php } ?>
            <?php } elseif ($installedVersion['sha']) { ?>
                <div class="encoder-version-detail"><?php echo __('Commit recorded in the installation package.'); ?></div>
            <?php } else { ?>
                <div class="encoder-version-detail"><?php echo __('This installation has no readable Git or build metadata.'); ?></div>
            <?php } ?>
        </article>
        <article class="encoder-version-card">
            <h3><i class="fas fa-tag" aria-hidden="true"></i> <?php echo __('Latest stable release'); ?></h3>
            <div class="encoder-version-value" id="encoder-release-value">&mdash;</div>
            <p><?php echo __('Published version, identified by its release tag.'); ?></p>
            <div class="encoder-version-detail" id="encoder-release-commit"></div>
            <div class="encoder-version-detail" id="encoder-release-date"></div>
            <a class="encoder-version-link" href="https://github.com/WWBN/AVideo-Encoder/releases/latest" target="_blank" rel="noopener noreferrer"><?php echo __('Release notes'); ?> <span aria-hidden="true">&rarr;</span></a>
        </article>
        <article class="encoder-version-card">
            <h3><i class="fab fa-github" aria-hidden="true"></i> GitHub <span class="encoder-version-branch">master</span></h3>
            <div class="encoder-version-value encoder-commit" id="encoder-master-value">&mdash;</div>
            <p><?php echo __('Latest code on master. May include changes not yet released.'); ?></p>
            <div class="encoder-version-detail encoder-version-commit-date" id="encoder-master-date"></div>
            <div class="encoder-version-detail" id="encoder-master-status"></div>
            <a class="encoder-version-link" href="https://github.com/WWBN/AVideo-Encoder/commits/master" target="_blank" rel="noopener noreferrer"><?php echo __('Commit history'); ?> <span aria-hidden="true">&rarr;</span></a>
        </article>
    </div>
    <div class="encoder-version-footer">
        <span id="encoder-version-checked"></span>
        <a id="encoder-version-compare" hidden target="_blank" rel="noopener noreferrer"><?php echo __('View changes'); ?> <span aria-hidden="true">&rarr;</span></a>
    </div>
    <p class="encoder-version-note"><i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo __('The Encoder release number is independent of the AVideo version and the database schema. Update status is determined by commit history, not version numbers.'); ?></p>
</section>
<script type="application/json" id="encoder-version-data"><?php echo json_encode(['installed' => $installedVersion, 'messages' => $versionMessages], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
<script src="<?php echo $versionEscape($global['webSiteRootURL']); ?>update/version.js?v=<?php echo filemtime(__DIR__ . '/version.js'); ?>"></script>
