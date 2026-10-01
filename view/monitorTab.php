<?php
// Admin "Monitor" tab pane. Its content (view/monitorDashboard.php) loads when the tab is opened
// and refreshes every 30 seconds while visible. Direct link: <Encoder URL>index.php#monitor
if (!Login::isAdmin()) {
    return;
}
?>
<div id="monitor" class="tab-pane fade">
    <div id="encoderMonitorDashboard" class="monitor-loading text-muted">
        <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> <?php echo __('Loading the monitor status...'); ?>
    </div>
</div>
<script>
$(function () {
    var url = <?php echo json_encode($global['webSiteRootURL'] . 'view/monitorDashboard.refresh.php?' . getPHPSessionIDURL(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var copiedText = <?php echo json_encode(__('Copied', true)); ?>;
    var failedText = <?php echo json_encode(__('Could not refresh the monitor status.', true)); ?>;
    var tab = $('#mainTabs a[href="#monitor"]');
    var loading = false;

    function isOpen() {
        return $('#monitor').hasClass('active');
    }
    function updateDot() {
        var tone = $('#encoderMonitorDashboard').attr('data-health-tone') || 'muted';
        tab.find('.monitor-tab-dot').attr('class', 'monitor-tab-dot text-' + tone);
    }
    function refresh(manual) {
        if (loading) { return; }
        loading = true;
        $('#encoderMonitorRefresh').prop('disabled', true).find('i').addClass('fa-spin');
        $.ajax({url: url, dataType: 'html', cache: false, timeout: 30000}).done(function (html) {
            $('#encoderMonitorDashboard').replaceWith(html);
            updateDot();
        }).fail(function () {
            if (manual) { avideoToastError(failedText); }
        }).always(function () {
            loading = false;
            $('#encoderMonitorRefresh').prop('disabled', false).find('i').removeClass('fa-spin');
        });
    }
    function setHash(open) {
        if (!window.history || !history.replaceState) { return; }
        if (open) {
            history.replaceState(null, '', '#monitor');
        } else if (location.hash === '#monitor') {
            history.replaceState(null, '', location.pathname + location.search);
        }
    }

    tab.on('shown.bs.tab', function () { setHash(true); refresh(false); });
    $('#mainTabs a[data-toggle="tab"]').not(tab).on('shown.bs.tab', function () { setHash(false); });
    // Links elsewhere on the page (for example the monitor warning) open the tab.
    $(document).on('click', 'a[data-monitor-open]', function (event) {
        event.preventDefault();
        tab.tab('show');
        $('html, body').animate({scrollTop: $('#mainTabs').offset().top - 70}, 200);
    });
    $(document).on('click', '#encoderMonitorRefresh', function () { refresh(true); });
    $(document).on('click', '[data-monitor-copy]', function () {
        var text = $(this).attr('data-monitor-copy');
        var done = function () { avideoToastSuccess(copiedText); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else if (typeof copyToClipboard === 'function') {
            copyToClipboard(text);
        }
    });
    setInterval(function () {
        if (isOpen() && !document.hidden) { refresh(false); }
    }, 30000);
    if (location.hash === '#monitor') { tab.tab('show'); }
});
</script>
