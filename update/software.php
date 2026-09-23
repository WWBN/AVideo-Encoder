<?php
require_once $global['systemRootPath'] . 'objects/EncoderVersion.php';
$encoderInstalled = EncoderVersion::installed($global['systemRootPath']);
$encoderVersionLabels = [];
foreach ([
    'unknown' => 'Could not verify. Try again later.',
    'current' => 'Up to date with this channel.',
    'update' => 'Update available: %s new commits.',
    'newer' => 'Installed code is newer than this channel.',
    'diverged' => 'Local and available code have diverged. Review the changes before updating.',
    'checking' => 'Checking for updates...',
    'changes' => 'View changes',
    'unavailable' => 'Unavailable'
] as $key => $label) {
    $encoderVersionLabels[$key] = __($label);
}
?>
<div class="panel panel-default" id="encoderSoftwareVersion">
    <div class="panel-heading"><i class="fas fa-code-branch" aria-hidden="true"></i> <?php echo __('Encoder software'); ?></div>
    <div class="panel-body">
        <dl class="dl-horizontal">
            <dt><?php echo __('Installed version'); ?></dt>
            <dd><?php echo htmlspecialchars($encoderInstalled['version'] ?: __('Unavailable'), ENT_QUOTES, 'UTF-8'); ?></dd>
            <dt><?php echo __('Installed commit'); ?></dt>
            <dd>
                <?php if ($encoderInstalled['commit']) { ?>
                    <a target="_blank" rel="noopener noreferrer" href="<?php echo EncoderVersion::REPOSITORY . '/commit/' . $encoderInstalled['commit']; ?>"><code><?php echo substr($encoderInstalled['commit'], 0, 12); ?></code></a>
                <?php } else { echo __('Unavailable'); } ?>
            </dd>
            <?php if ($encoderInstalled['branch']) { ?>
                <dt><?php echo __('Branch'); ?></dt>
                <dd><?php echo htmlspecialchars($encoderInstalled['branch'], ENT_QUOTES, 'UTF-8'); ?></dd>
            <?php } ?>
        </dl>
        <?php if (!$encoderInstalled['commit']) { ?>
            <div class="alert alert-warning"><?php echo __('This installation has no readable Git or build metadata. Its update status cannot be determined.'); ?></div>
        <?php } elseif ($encoderInstalled['modified']) { ?>
            <div class="alert alert-warning"><?php echo __('Local tracked files have changes. The comparison below refers to the installed commit; review local changes before updating.'); ?></div>
        <?php } ?>
        <div class="row">
            <?php foreach (['release' => 'Latest stable release', 'master' => 'Development (master)'] as $channel => $label) { ?>
                <div class="col-sm-6">
                    <h4><?php echo __($label); ?> <span data-version-name="<?php echo $channel; ?>"></span></h4>
                    <div class="alert alert-info" data-version-status="<?php echo $channel; ?>" role="status" aria-live="polite"><?php echo __('Checking for updates...'); ?></div>
                    <p data-version-changes="<?php echo $channel; ?>"></p>
                </div>
            <?php } ?>
        </div>
        <button type="button" class="btn btn-default" id="checkEncoderVersion"><i class="fas fa-sync" aria-hidden="true"></i> <?php echo __('Check for updates'); ?></button>
        <p class="help-block"><?php echo __('Results are cached for up to 1 hour. GitHub rate limits may delay new checks. Checking does not install updates.'); ?></p>
        <p><?php echo __('For a Git installation, update the code on the server. For Docker, deploy a newer image. Then apply any pending database updates below.'); ?></p>
    </div>
</div>
<script>
$(function () {
    var labels = <?php echo json_encode($encoderVersionLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var panel = $('#encoderSoftwareVersion');
    var loaded = false;
    var loading = false;
    function renderChannel(channel, data) {
        var status = panel.find('[data-version-status="' + channel + '"]');
        var state = data && labels[data.state] ? data.state : 'unknown';
        var color = state === 'current' ? 'success' : (state === 'update' || state === 'diverged' ? 'warning' : 'info');
        status.attr('class', 'alert alert-' + color).text(labels[state].replace('%s', data ? data.commits : 0));
        var name = panel.find('[data-version-name="' + channel + '"]').empty();
        var changes = panel.find('[data-version-changes="' + channel + '"]').empty();
        if (data) {
            $('<a>', {href: data.url, target: '_blank', rel: 'noopener noreferrer'}).text(data.name).appendTo(name);
            if (data.changesUrl) {
                $('<a>', {href: data.changesUrl, target: '_blank', rel: 'noopener noreferrer'}).text(labels.changes).appendTo(changes);
            }
        } else {
            name.text(labels.unavailable);
        }
    }
    function checkVersion() {
        if (loading) { return; }
        loading = true;
        loaded = true;
        $('#checkEncoderVersion').prop('disabled', true);
        panel.find('[data-version-status]').attr('class', 'alert alert-info').text(labels.checking);
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
