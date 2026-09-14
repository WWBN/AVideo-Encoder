<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/installer.php';
$configured = installerConfigured();
if (!$configured) { installerSession(); }
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
function h($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$configured = installerConfigured();
$checks = $configured ? [] : installerChecks();
$ready = !in_array(false, array_column($checks, 'ok'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup • AVideo Encoder</title>
    <link rel="icon" href="assets/favicon.png">
    <link rel="stylesheet" href="installer.css">
    <script src="installer.js" defer></script>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="https://avideo.com/" aria-label="AVideo"><img src="assets/logo.png" alt="AVideo" width="250" height="70"></a>
        <div class="product">VIDEO ENCODER</div>
        <div class="sidebar-heading">Your Encoder.<br>Ready to<br><span>connect.</span></div>
        <p class="sidebar-copy">Prepare videos for streaming and serve multiple AVideo sites.</p>
        <?php if (!$configured): ?>
        <nav aria-label="Setup steps">
            <a href="#database"><span>01</span><div>Database<small>Connection and storage</small></div></a>
            <a href="#network"><span>02</span><div>Your Encoder<small>Public address</small></div></a>
            <a href="#streamer"><span>03</span><div>AVideo site<small>Administrator access</small></div></a>
        </nav>
        <?php endif; ?>
        <div class="sidebar-footer"><span class="signal" aria-hidden="true"></span> GUIDED SETUP<small>MySQL / MariaDB · Windows / Linux</small></div>
    </aside>
    <main>
        <header class="topbar"><span>Initial setup</span><span class="pill">AVideo Encoder</span></header>
        <div class="content">
        <?php if ($configured): ?>
            <section class="complete card">
                <span class="complete-icon" aria-hidden="true">✓</span>
                <div class="eyebrow">SETUP COMPLETE</div>
                <h1>Installation complete.</h1>
                <p>Setup is locked. Your application is ready to open.</p>
                <a class="button primary" href="../">Open application</a>
            </section>
        <?php else: ?>
            <div class="eyebrow">GET STARTED</div>
            <h1>Set up your video Encoder.</h1>
            <p class="intro">Enter your details below. We will create the database, install the tables,<br class="desktop"> and generate your configuration file.</p>
            <section class="environment" aria-label="Server requirements">
                <div class="environment-title"><span class="status-dot <?= $ready ? '' : 'bad' ?>"></span><strong><?= $ready ? 'Environment ready' : 'Action required' ?></strong><span>Server check</span></div>
                <div class="checks">
                    <?php foreach ($checks as $check): ?>
                    <div class="check <?= $check['ok'] ? '' : 'failed' ?>" title="<?= h($check['detail']) ?>"><span aria-hidden="true"><?= $check['ok'] ? '✓' : '!' ?></span><?= h($check['label']) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$ready): ?>
                    <details open class="requirement-help"><summary>How to resolve missing requirements</summary>
                        <?php foreach ($checks as $check): if ($check['ok']) { continue; } $help = installerRequirementHelp($check); ?>
                        <h3><?= h($check['label']) ?></h3><p><?= h($help['text']) ?></p>
                        <?php if (!empty($help['command'])): ?><pre><code><?= h($help['command']) ?></code></pre><?php endif; ?>
                        <?php endforeach; ?><p>Then reload this page.</p>
                    </details>
                <?php endif; ?>
            </section>
            <?php include __DIR__ . '/ubuntu-help.php'; ?>
            <form id="configurationForm" data-ready="<?= $ready ? '1' : '0' ?>">
                <input type="hidden" name="install_csrf_token" value="<?= h($_SESSION['install_csrf_token']) ?>">
                <section class="card" id="database">
                    <div class="section-heading"><span class="number">01</span><div><h2>Database</h2><p>Where your Encoder stores its data.</p></div><span class="tag">MySQL / MariaDB</span></div>
                    <div class="fields">
                        <div class="field wide"><label for="databaseHost">Database host</label><input id="databaseHost" name="databaseHost" value="localhost" required maxlength="253" autocomplete="off" spellcheck="false"><small>Use localhost if the database runs on this server.</small></div>
                        <div class="field narrow"><label for="databasePort">Port</label><input id="databasePort" name="databasePort" type="number" value="3306" min="1" max="65535" required></div>
                        <div class="field"><label for="databaseUser">Username</label><input id="databaseUser" name="databaseUser" value="root" required maxlength="80" autocomplete="off" spellcheck="false"></div>
                        <div class="field"><label for="databasePass">Database password <span class="optional">if applicable</span></label><div class="password-field"><input id="databasePass" name="databasePass" type="password" autocomplete="new-password"><button type="button" class="reveal" data-target="databasePass" aria-label="Show database password" aria-pressed="false">Show</button></div></div>
                        <div class="field full"><label for="databaseName">Database name</label><input id="databaseName" name="databaseName" value="avideo_encoder" pattern="[A-Za-z0-9_\-]{1,64}" maxlength="64" required spellcheck="false"><small>We will create this database if it does not exist. Existing Encoder tables must contain no application data.</small></div>
                    </div>
                    <div class="field full"><label for="tablesPrefix">Table prefix</label><input id="tablesPrefix" name="tablesPrefix" pattern="[A-Za-z0-9_]{0,25}" maxlength="25" placeholder="Optional, for example encoder_"><small>Use a unique prefix to share a database with other applications.</small></div>
                    <div class="field full database-mode"><label for="createTables">Database setup</label><select id="createTables" name="createTables"><option value="2">Create database and tables</option><option value="1">Create tables in an existing database</option><option value="0">Use a schema imported manually</option></select><small>The selected Encoder tables must contain no application data.</small></div>
                    <div class="card-footer"><span>Testing the connection does not change any data.</span><button type="button" class="button secondary" id="testConnection">Test connection <span aria-hidden="true">↗</span></button></div>
                    <div id="connectionResult" class="inline-result" role="status" hidden></div>
                </section>
                <section class="card" id="network">
                    <div class="section-heading"><span class="number">02</span><div><h2>Your Encoder</h2><p>Set the public address of this Encoder.</p></div></div>
                    <div class="fields">
                        <div class="field full"><label for="webSiteRootURL">Encoder URL</label><input id="webSiteRootURL" name="webSiteRootURL" type="url" value="<?= h(installerURL()) ?>" required maxlength="254" spellcheck="false"><small>The public address used to access this installation.</small></div>
                    </div>
                    <details class="path-details"><summary>Automatically detected directory</summary><code><?= h(installerRoot()) ?></code><p>Setup writes the configuration to this Encoder videos directory.</p></details>
                </section>
                <section class="card" id="streamer">
                    <div class="section-heading"><span class="number">03</span><div><h2>AVideo site</h2><p>Connect this Encoder to your first video site.</p></div></div>
                    <div class="fields">
                        <div class="field full"><label for="siteURL">Your AVideo site URL</label><input id="siteURL" name="siteURL" type="url" placeholder="https://videos.example.com/" required maxlength="254" spellcheck="false"></div>
                        <div class="field"><label for="inputUser">Administrator username</label><input id="inputUser" name="inputUser" value="admin" required maxlength="45" autocomplete="username" spellcheck="false"></div>
                        <div class="field"><label for="inputPassword">Administrator password</label><div class="password-field"><input id="inputPassword" name="inputPassword" type="password" required autocomplete="current-password"><button type="button" class="reveal" data-target="inputPassword" aria-label="Show administrator password" aria-pressed="false">Show</button></div></div>
                    </div>
                    <div class="info"><span aria-hidden="true">i</span><p>We will verify these credentials with your AVideo site before installing. Need a video site? <a href="https://github.com/WWBN/AVideo" target="_blank" rel="noopener noreferrer">Explore AVideo ↗</a></p></div>
                </section>
                <section class="card"><h2>Encoding settings</h2><div class="fields">
                    <div class="field full"><label for="allowedStreamers">Allowed Streamers</label><textarea id="allowedStreamers" name="allowedStreamers" rows="3" placeholder="https://videos.example.com/"></textarea><small>Leave blank to allow all Streamers. Use a separate line for each allowed URL.</small></div>
                    <div class="field"><label for="defaultPriority">Default priority</label><input id="defaultPriority" name="defaultPriority" type="number" value="1" min="1" max="10" required><small>1 is the highest priority; 10 is the lowest.</small></div>
                </div></section>
                <section id="result" class="card result" tabindex="-1" aria-live="polite" hidden></section>
                <div class="submit-row"><p><strong>Everything in one step.</strong><br>Database, tables, and configuration.php.</p><button class="button primary" id="installButton" type="submit" <?= $ready ? '' : 'disabled' ?>>Install Encoder <span aria-hidden="true">→</span></button></div>
                <p class="install-note">If anything goes wrong, instructions and commands to run on your server will appear here.</p>
            </form>
            <noscript><p class="info">Enable JavaScript in your browser to test the connection and run setup.</p></noscript>
        <?php endif; ?>
        <footer class="page-footer"><span>AVideo Encoder</span><span>Infrastructure for your videos.</span></footer>
        </div>
    </main>
</div>
</body>
</html>
