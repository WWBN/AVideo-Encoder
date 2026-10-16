<?php
require_once __DIR__ . '/../videos/configuration.php';
require_once __DIR__ . '/../objects/Streamer.php';
require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/LocalEncoderPromotion.php';
require_once __DIR__ . '/../locale/function.php';
if (!Login::isLogged() || !Login::canUpload()) {
    header('Location: ' . $global['webSiteRootURL']);
    exit;
}
$site = Login::getStreamerURL();
$guide = EncoderLocalEncoderPromotion::getURL($site, getAdvancedCustomizedObjectData());
if ($guide === '') { http_response_code(404); exit; }
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$launcherConfig = array('site' => $site,
    'login' => array('url' => $global['webSiteRootURL'] . 'view/localEncoderLogin.json.php', 'field' => 'csrfToken', 'token' => getEncoderAdminCSRFToken()),
    'text' => array(
        'invalid' => __('This video link is not valid. Open this page again from your AVideo site.'),
        'ready' => __('Local Encoder is running on this computer. You can open it now.'),
        'open' => __('Open Local Encoder'),
        'opening' => __('Opening Local Encoder... Accept the browser prompt. The application may open in another tab.'),
        'checking' => __('Checking whether Local Encoder is running on this computer...'),
        'connecting' => __('Signing in to Local Encoder with your current account...'),
        'loginFailed' => __('Automatic sign-in is unavailable. You can sign in inside Local Encoder with your username and password.'),
        'unknown' => __('We could not confirm that Local Encoder is running. Open the program, then Check again, or download it below.'),
        'computer' => __('Local Encoder runs on Windows, macOS and Linux. Open this page on the computer where you want to process videos.')
    ));
function localEncoderEscape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Local Encoder</title>
    <link rel="stylesheet" href="<?php echo localEncoderEscape($global['webSiteRootURL'] . 'node_modules/bootstrap/dist/css/bootstrap.min.css'); ?>">
    <!-- Reuse the current Streamer's integration asset, as other Encoder pages reuse its UI assets. -->
    <script src="<?php echo localEncoderEscape(rtrim($site, '/') . '/plugin/CustomizeAdvanced/localEncoderLauncher.js?v=2'); ?>" defer></script>
</head>
<body>
<div class="container">
    <h1>Local Encoder</h1>
    <div class="panel panel-primary" id="local-encoder-launcher">
        <div class="panel-heading"><?php echo __('Open Local Encoder'); ?></div>
        <div class="panel-body">
            <p data-status role="status" aria-live="polite"><?php echo __('Checking whether Local Encoder is running on this computer...'); ?></p>
            <p><?php echo __('Your current account will connect automatically. Review your video before adding it to the queue.'); ?></p>
            <p><strong><?php echo localEncoderEscape($site); ?></strong></p>
            <p><a data-control class="hidden"><?php echo __('Open the running control page'); ?></a></p>
            <p>
                <a data-open class="btn btn-primary" href="<?php echo localEncoderEscape('avideo-local-encoder://open?site=' . rawurlencode($site)); ?>"><?php echo __('Open Local Encoder'); ?></a>
                <button data-check type="button" class="btn btn-default"><?php echo __('Check again'); ?></button>
                <a class="btn btn-default" href="<?php echo localEncoderEscape($guide . '#local-encoder-downloads'); ?>"><?php echo __('Learn more and download'); ?></a>
            </p>
            <p><?php echo __('Extract the whole download and open LocalEncoder once. Accept your browser opening prompt if it appears.'); ?></p>
        </div>
    </div>
    <p><?php echo __('Local Encoder complements your Web Encoder. Keep a working Web Encoder configured.'); ?></p>
    <p><a href="<?php echo localEncoderEscape($global['webSiteRootURL']); ?>"><?php echo __('Back'); ?></a></p>
</div>
<script type="application/json" id="local-encoder-config"><?php echo json_encode($launcherConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?></script>
</body></html>
