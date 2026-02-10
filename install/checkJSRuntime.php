<?php
/**
 * Check and Install JavaScript Runtimes for yt-dlp
 *
 * This script checks if Deno and Node.js are installed and accessible
 * by the web server user (www-data). If not, it automatically installs
 * and configures them for Ubuntu/Debian systems.
 *
 * Usage: php checkJSRuntime.php
 *
 * @author AVideo Encoder
 * @version 2.0
 */

if (php_sapi_name() !== 'cli') {
    die('Command Line only');
}

// Colors for terminal output
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
define('COLOR_BOLD', "\033[1m");

/**
 * Print colored message to console
 */
function printMsg($message, $color = COLOR_RESET) {
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

/**
 * Print section header
 */
function printHeader($title) {
    echo PHP_EOL;
    printMsg("═══════════════════════════════════════════════════════════════", COLOR_BLUE);
    printMsg(" $title", COLOR_BOLD . COLOR_BLUE);
    printMsg("═══════════════════════════════════════════════════════════════", COLOR_BLUE);
    echo PHP_EOL;
}

/**
 * Execute a command and display output in real-time
 */
function execCommand($command, $description = '') {
    if (!empty($description)) {
        printMsg("→ $description", COLOR_YELLOW);
    }
    printMsg("  Running: $command", COLOR_RESET);

    $returnCode = 0;
    passthru($command . " 2>&1", $returnCode);

    return $returnCode === 0;
}

/**
 * Execute a command silently and return success status
 */
function execCommandSilent($command) {
    $output = [];
    $returnCode = 0;
    exec($command . " 2>&1", $output, $returnCode);
    return ['success' => $returnCode === 0, 'output' => implode("\n", $output)];
}

/**
 * Check if a command exists in PATH
 */
function commandExists($command) {
    $output = shell_exec("which $command 2>/dev/null");
    return !empty(trim($output ?? ''));
}

/**
 * Get the path of a command
 */
function getCommandPath($command) {
    $output = shell_exec("which $command 2>/dev/null");
    return trim($output ?? '');
}

/**
 * Check if a file is executable by www-data
 */
function isExecutableByWwwData($path) {
    if (empty($path) || !file_exists($path)) {
        return false;
    }

    // Try to execute as www-data
    $output = shell_exec("sudo -u www-data $path --version 2>&1");

    // Check for permission denied errors
    if (stripos($output, 'Permission denied') !== false) {
        return false;
    }

    return !empty($output);
}

/**
 * Get version of a runtime
 */
function getVersion($command) {
    $output = shell_exec("$command --version 2>/dev/null | head -1");
    return trim($output ?? '');
}

/**
 * Check file permissions
 */
function getFilePermissions($path) {
    if (!file_exists($path)) {
        return null;
    }
    return substr(sprintf('%o', fileperms($path)), -4);
}

/**
 * Check file owner
 */
function getFileOwner($path) {
    if (!file_exists($path)) {
        return null;
    }
    $stat = stat($path);
    $userInfo = posix_getpwuid($stat['uid']);
    $groupInfo = posix_getgrgid($stat['gid']);
    return [
        'user' => $userInfo['name'] ?? $stat['uid'],
        'group' => $groupInfo['name'] ?? $stat['gid']
    ];
}

/**
 * Check if running as root
 */
function isRoot() {
    return trim(shell_exec('whoami')) === 'root';
}

/**
 * Check Deno installation
 */
function checkDeno() {
    $status = [
        'installed' => false,
        'path' => '',
        'version' => '',
        'accessible' => false,
        'permissions_ok' => false
    ];

    // Common Deno locations
    $denoPaths = [
        '/usr/local/bin/deno',
        '/usr/bin/deno',
        getenv('HOME') . '/.deno/bin/deno',
        '/root/.deno/bin/deno'
    ];

    // Check if deno is in PATH
    if (commandExists('deno')) {
        $status['installed'] = true;
        $status['path'] = getCommandPath('deno');
        $status['version'] = getVersion('deno');
    } else {
        // Check common locations
        foreach ($denoPaths as $path) {
            if (file_exists($path)) {
                $status['installed'] = true;
                $status['path'] = $path;
                $status['version'] = getVersion($path);
                break;
            }
        }
    }

    if ($status['installed']) {
        // Check if accessible by www-data
        if (isExecutableByWwwData($status['path'])) {
            $status['accessible'] = true;
            $status['permissions_ok'] = true;
        }
    }

    return $status;
}

/**
 * Install Deno runtime
 */
function installDeno() {
    printHeader("Installing Deno Runtime");

    if (!isRoot()) {
        printMsg("✗ Root privileges required to install Deno", COLOR_RED);
        return false;
    }

    // Install Deno using the official installer
    printMsg("Downloading and installing Deno...", COLOR_YELLOW);

    // Use DENO_INSTALL to install to /usr/local
    $installCmd = "DENO_INSTALL=/usr/local curl -fsSL https://deno.land/install.sh | sh";
    $result = execCommand($installCmd, "Installing Deno to /usr/local");

    if (!$result) {
        // Try alternative: install to root home and copy
        printMsg("Trying alternative installation method...", COLOR_YELLOW);
        execCommand("curl -fsSL https://deno.land/install.sh | sh", "Installing Deno to home directory");

        // Copy to global location
        $homeDenoPath = getenv('HOME') . '/.deno/bin/deno';
        if (file_exists($homeDenoPath)) {
            execCommand("cp $homeDenoPath /usr/local/bin/deno", "Copying Deno to /usr/local/bin");
            execCommand("chmod 755 /usr/local/bin/deno", "Setting permissions");
        }
    }

    // Verify installation
    if (file_exists('/usr/local/bin/deno')) {
        execCommand("chmod 755 /usr/local/bin/deno", "Ensuring correct permissions");
        printMsg("✓ Deno installed successfully", COLOR_GREEN);
        return true;
    }

    printMsg("✗ Deno installation failed", COLOR_RED);
    return false;
}

/**
 * Fix Deno permissions for www-data access
 */
function fixDenoPermissions($denoStatus) {
    printHeader("Fixing Deno Permissions");

    if (!isRoot()) {
        printMsg("✗ Root privileges required to fix permissions", COLOR_RED);
        return false;
    }

    $sourcePath = $denoStatus['path'];
    if (empty($sourcePath)) {
        $sourcePath = '/root/.deno/bin/deno';
    }

    if (!file_exists($sourcePath)) {
        printMsg("✗ Deno binary not found at: $sourcePath", COLOR_RED);
        return false;
    }

    // Check if it's in a home directory - need to copy to global location
    if (strpos($sourcePath, '/root/') !== false || strpos($sourcePath, '/home/') !== false) {
        printMsg("Deno is in a home directory, copying to /usr/local/bin...", COLOR_YELLOW);

        execCommand("rm -f /usr/local/bin/deno", "Removing existing deno link/file");
        execCommand("cp $sourcePath /usr/local/bin/deno", "Copying Deno binary");
        execCommand("chmod 755 /usr/local/bin/deno", "Setting permissions");
    } else {
        // Just fix permissions
        execCommand("chmod 755 $sourcePath", "Fixing Deno permissions");
    }

    // Verify
    if (isExecutableByWwwData('/usr/local/bin/deno') || isExecutableByWwwData($sourcePath)) {
        printMsg("✓ Deno is now accessible by www-data", COLOR_GREEN);
        return true;
    }

    printMsg("✗ Failed to fix Deno permissions", COLOR_RED);
    return false;
}

/**
 * Check Node.js installation
 */
function checkNodeJS() {
    $status = [
        'installed' => false,
        'path' => '',
        'version' => '',
        'accessible' => false,
        'permissions_ok' => false
    ];

    // Common Node.js locations
    $nodePaths = [
        '/usr/local/bin/node',
        '/usr/bin/node',
        '/usr/bin/nodejs'
    ];

    // Check if node is in PATH
    if (commandExists('node')) {
        $status['installed'] = true;
        $status['path'] = getCommandPath('node');
        $status['version'] = getVersion('node');
    } elseif (commandExists('nodejs')) {
        $status['installed'] = true;
        $status['path'] = getCommandPath('nodejs');
        $status['version'] = getVersion('nodejs');
    } else {
        // Check common locations
        foreach ($nodePaths as $path) {
            if (file_exists($path)) {
                $status['installed'] = true;
                $status['path'] = $path;
                $status['version'] = getVersion($path);
                break;
            }
        }
    }

    if ($status['installed']) {
        // Check if accessible by www-data
        if (isExecutableByWwwData($status['path'])) {
            $status['accessible'] = true;
            $status['permissions_ok'] = true;
        }
    }

    return $status;
}

/**
 * Install Node.js runtime
 */
function installNodeJS() {
    printHeader("Installing Node.js Runtime");

    if (!isRoot()) {
        printMsg("✗ Root privileges required to install Node.js", COLOR_RED);
        return false;
    }

    // Update apt first
    execCommand("apt-get update", "Updating package lists");

    // Try to install Node.js LTS via NodeSource
    printMsg("Installing Node.js LTS via NodeSource...", COLOR_YELLOW);

    $result = execCommand("curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -", "Setting up NodeSource repository");

    if ($result) {
        execCommand("apt-get install -y nodejs", "Installing Node.js");
    } else {
        // Fallback to Ubuntu repository
        printMsg("NodeSource setup failed, trying Ubuntu repository...", COLOR_YELLOW);
        execCommand("apt-get install -y nodejs npm", "Installing Node.js from Ubuntu repository");
    }

    // Verify installation
    if (commandExists('node')) {
        $version = getVersion('node');
        printMsg("✓ Node.js installed successfully: $version", COLOR_GREEN);
        return true;
    }

    printMsg("✗ Node.js installation failed", COLOR_RED);
    return false;
}

/**
 * Fix Node.js permissions for www-data access
 */
function fixNodePermissions($nodeStatus) {
    printHeader("Fixing Node.js Permissions");

    if (!isRoot()) {
        printMsg("✗ Root privileges required to fix permissions", COLOR_RED);
        return false;
    }

    $nodePath = $nodeStatus['path'];
    if (empty($nodePath) || !file_exists($nodePath)) {
        printMsg("✗ Node.js binary not found", COLOR_RED);
        return false;
    }

    execCommand("chmod 755 $nodePath", "Fixing Node.js permissions");

    // Verify
    if (isExecutableByWwwData($nodePath)) {
        printMsg("✓ Node.js is now accessible by www-data", COLOR_GREEN);
        return true;
    }

    printMsg("✗ Failed to fix Node.js permissions", COLOR_RED);
    return false;
}

/**
 * Check yt-dlp installation
 */
function checkYtdlp() {
    $status = [
        'installed' => false,
        'path' => '',
        'version' => ''
    ];

    if (commandExists('yt-dlp')) {
        $status['installed'] = true;
        $status['path'] = getCommandPath('yt-dlp');
        $status['version'] = getVersion('yt-dlp');
    }

    return $status;
}

/**
 * Install yt-dlp
 */
function installYtdlp() {
    printHeader("Installing yt-dlp");

    if (!isRoot()) {
        printMsg("✗ Root privileges required to install yt-dlp", COLOR_RED);
        return false;
    }

    // Try pip first (recommended method)
    if (commandExists('pip3')) {
        printMsg("Installing yt-dlp via pip3...", COLOR_YELLOW);
        $result = execCommand("pip3 install --upgrade yt-dlp", "Installing yt-dlp with pip3");
        if ($result && commandExists('yt-dlp')) {
            printMsg("✓ yt-dlp installed successfully via pip3", COLOR_GREEN);
            return true;
        }
    }

    // Try direct download as fallback
    printMsg("Trying direct download method...", COLOR_YELLOW);
    execCommand("curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp", "Downloading yt-dlp");
    execCommand("chmod a+rx /usr/local/bin/yt-dlp", "Setting permissions");

    if (commandExists('yt-dlp')) {
        printMsg("✓ yt-dlp installed successfully", COLOR_GREEN);
        return true;
    }

    printMsg("✗ yt-dlp installation failed", COLOR_RED);
    return false;
}

/**
 * Update yt-dlp to latest version
 */
function updateYtdlp() {
    printHeader("Updating yt-dlp");

    // Try self-update first
    $result = execCommandSilent("yt-dlp -U");
    if ($result['success']) {
        printMsg("✓ yt-dlp updated successfully", COLOR_GREEN);
        return true;
    }

    // Try pip upgrade
    if (commandExists('pip3')) {
        printMsg("Trying pip3 upgrade...", COLOR_YELLOW);
        execCommand("pip3 install --upgrade yt-dlp", "Upgrading yt-dlp with pip3");
    }

    $version = getVersion('yt-dlp');
    printMsg("yt-dlp version: $version", COLOR_GREEN);
    return true;
}

/**
 * Configure yt-dlp to use Node.js if Deno is not available
 */
function configureYtdlpRuntime($denoStatus, $nodeStatus) {
    if ($nodeStatus['accessible'] && !$denoStatus['accessible']) {
        printHeader("Configuring yt-dlp Runtime");
        printMsg("Configuring yt-dlp to use Node.js...", COLOR_YELLOW);

        // Create or update yt-dlp config
        $configContent = "--js-runtimes nodejs\n";
        $configFile = "/etc/yt-dlp.conf";

        // Check if config already has this setting
        if (file_exists($configFile)) {
            $currentConfig = file_get_contents($configFile);
            if (strpos($currentConfig, '--js-runtimes') === false) {
                file_put_contents($configFile, $currentConfig . $configContent);
                printMsg("✓ Added Node.js runtime configuration to $configFile", COLOR_GREEN);
            } else {
                printMsg("Runtime already configured in $configFile", COLOR_RESET);
            }
        } else {
            file_put_contents($configFile, $configContent);
            printMsg("✓ Created $configFile with Node.js runtime configuration", COLOR_GREEN);
        }
    }
}

/**
 * Display status of a runtime
 */
function displayStatus($name, $status, $isAccessibilityCheck = true) {
    if ($isAccessibilityCheck) {
        if ($status['accessible']) {
            printMsg("✓ $name: " . $status['version'] . " (accessible by www-data)", COLOR_GREEN);
            printMsg("  Path: " . $status['path'], COLOR_RESET);
        } elseif ($status['installed']) {
            printMsg("⚠ $name: " . $status['version'] . " (NOT accessible by www-data)", COLOR_YELLOW);
            printMsg("  Path: " . $status['path'], COLOR_RESET);
        } else {
            printMsg("✗ $name: Not installed", COLOR_RED);
        }
    } else {
        if ($status['installed']) {
            printMsg("✓ $name: " . $status['version'], COLOR_GREEN);
            printMsg("  Path: " . $status['path'], COLOR_RESET);
        } else {
            printMsg("✗ $name: Not installed", COLOR_RED);
        }
    }
}

/**
 * Summary and final status
 */
function printSummary($denoStatus, $nodeStatus, $ytdlpStatus) {
    printHeader("Final Status");

    // yt-dlp status
    displayStatus("yt-dlp", $ytdlpStatus, false);

    // Deno status
    displayStatus("Deno", $denoStatus, true);

    // Node.js status
    displayStatus("Node.js", $nodeStatus, true);

    echo PHP_EOL;

    // Overall status
    $allGood = true;
    if ($denoStatus['accessible'] || $nodeStatus['accessible']) {
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_GREEN);
        printMsg(" ✓ YouTube downloads should work correctly!", COLOR_GREEN);
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_GREEN);
    } else {
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_RED);
        printMsg(" ✗ YouTube downloads may NOT work!", COLOR_RED);
        printMsg("   No JavaScript runtime is accessible by www-data.", COLOR_RED);
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_RED);
        $allGood = false;
    }

    if (!$ytdlpStatus['installed']) {
        $allGood = false;
    }

    echo PHP_EOL;

    return $allGood;
}

// =============================================================================
// Main Execution
// =============================================================================

printMsg(PHP_EOL . "╔═══════════════════════════════════════════════════════════════╗", COLOR_BOLD);
printMsg("║     AVideo Encoder - JavaScript Runtime Installer             ║", COLOR_BOLD);
printMsg("║     For yt-dlp YouTube Download Support                        ║", COLOR_BOLD);
printMsg("╚═══════════════════════════════════════════════════════════════╝", COLOR_BOLD);

// Check current user
$currentUser = trim(shell_exec('whoami'));
printMsg(PHP_EOL . "Running as user: $currentUser", COLOR_RESET);

if (!isRoot()) {
    printMsg("⚠ Warning: Running without root privileges - installations may fail", COLOR_YELLOW);
    printMsg("  Recommend running as: sudo php " . basename(__FILE__), COLOR_YELLOW);
}

// =============================================================================
// Step 1: Check and install yt-dlp
// =============================================================================
printHeader("Step 1: Checking yt-dlp");

$ytdlpStatus = checkYtdlp();
displayStatus("yt-dlp", $ytdlpStatus, false);

if (!$ytdlpStatus['installed']) {
    printMsg(PHP_EOL . "yt-dlp is not installed. Installing now...", COLOR_YELLOW);
    installYtdlp();
    $ytdlpStatus = checkYtdlp();
} else {
    // Update yt-dlp to latest version
    printMsg(PHP_EOL . "Checking for yt-dlp updates...", COLOR_YELLOW);
    updateYtdlp();
    $ytdlpStatus = checkYtdlp();
}

// =============================================================================
// Step 2: Check and install Deno
// =============================================================================
printHeader("Step 2: Checking Deno Runtime");

$denoStatus = checkDeno();
displayStatus("Deno", $denoStatus, true);

if (!$denoStatus['installed']) {
    printMsg(PHP_EOL . "Deno is not installed. Installing now...", COLOR_YELLOW);
    installDeno();
    $denoStatus = checkDeno();
} elseif (!$denoStatus['accessible']) {
    printMsg(PHP_EOL . "Deno is installed but not accessible by www-data. Fixing...", COLOR_YELLOW);
    fixDenoPermissions($denoStatus);
    $denoStatus = checkDeno();
}

// =============================================================================
// Step 3: Check and install Node.js (as fallback)
// =============================================================================
printHeader("Step 3: Checking Node.js Runtime");

$nodeStatus = checkNodeJS();
displayStatus("Node.js", $nodeStatus, true);

// Install Node.js if Deno is not accessible (as a fallback)
if (!$denoStatus['accessible']) {
    if (!$nodeStatus['installed']) {
        printMsg(PHP_EOL . "Node.js is not installed. Installing as fallback...", COLOR_YELLOW);
        installNodeJS();
        $nodeStatus = checkNodeJS();
    } elseif (!$nodeStatus['accessible']) {
        printMsg(PHP_EOL . "Node.js is installed but not accessible by www-data. Fixing...", COLOR_YELLOW);
        fixNodePermissions($nodeStatus);
        $nodeStatus = checkNodeJS();
    }
}

// =============================================================================
// Step 4: Configure yt-dlp runtime if needed
// =============================================================================
$denoStatus = checkDeno();
$nodeStatus = checkNodeJS();
configureYtdlpRuntime($denoStatus, $nodeStatus);

// =============================================================================
// Final Summary
// =============================================================================
$allGood = printSummary($denoStatus, $nodeStatus, $ytdlpStatus);

// Exit with appropriate code
exit($allGood ? 0 : 1);
