<?php
/**
 * Check and Install JavaScript Runtimes for yt-dlp
 *
 * This script checks if Deno and Node.js are installed and accessible
 * by the web server user (www-data). If not, it provides installation
 * instructions for Ubuntu.
 *
 * Usage: php checkJSRuntime.php
 *
 * @author AVideo Encoder
 * @version 1.0
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
 * Check Deno installation
 */
function checkDeno() {
    printHeader("Checking Deno Runtime");

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
        printMsg("✓ Deno is installed", COLOR_GREEN);
        printMsg("  Path: " . $status['path'], COLOR_RESET);
        printMsg("  Version: " . $status['version'], COLOR_RESET);

        // Check permissions
        $perms = getFilePermissions($status['path']);
        $owner = getFileOwner($status['path']);
        printMsg("  Permissions: $perms", COLOR_RESET);
        printMsg("  Owner: " . ($owner ? "{$owner['user']}:{$owner['group']}" : 'unknown'), COLOR_RESET);

        // Check if accessible by www-data
        if (isExecutableByWwwData($status['path'])) {
            $status['accessible'] = true;
            $status['permissions_ok'] = true;
            printMsg("✓ Deno is accessible by www-data", COLOR_GREEN);
        } else {
            printMsg("✗ Deno is NOT accessible by www-data", COLOR_RED);

            // Check if it's in a user home directory
            if (strpos($status['path'], '/root/') !== false || strpos($status['path'], '/home/') !== false) {
                printMsg("  → The binary is in a home directory that www-data cannot access", COLOR_YELLOW);
            }
        }
    } else {
        printMsg("✗ Deno is NOT installed", COLOR_RED);
    }

    return $status;
}

/**
 * Check Node.js installation
 */
function checkNodeJS() {
    printHeader("Checking Node.js Runtime");

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
        printMsg("✓ Node.js is installed", COLOR_GREEN);
        printMsg("  Path: " . $status['path'], COLOR_RESET);
        printMsg("  Version: " . $status['version'], COLOR_RESET);

        // Check permissions
        $perms = getFilePermissions($status['path']);
        $owner = getFileOwner($status['path']);
        printMsg("  Permissions: $perms", COLOR_RESET);
        printMsg("  Owner: " . ($owner ? "{$owner['user']}:{$owner['group']}" : 'unknown'), COLOR_RESET);

        // Check if accessible by www-data
        if (isExecutableByWwwData($status['path'])) {
            $status['accessible'] = true;
            $status['permissions_ok'] = true;
            printMsg("✓ Node.js is accessible by www-data", COLOR_GREEN);
        } else {
            printMsg("✗ Node.js is NOT accessible by www-data", COLOR_RED);
        }
    } else {
        printMsg("✗ Node.js is NOT installed", COLOR_RED);
    }

    return $status;
}

/**
 * Check yt-dlp installation
 */
function checkYtdlp() {
    printHeader("Checking yt-dlp");

    $status = [
        'installed' => false,
        'path' => '',
        'version' => ''
    ];

    if (commandExists('yt-dlp')) {
        $status['installed'] = true;
        $status['path'] = getCommandPath('yt-dlp');
        $status['version'] = getVersion('yt-dlp');

        printMsg("✓ yt-dlp is installed", COLOR_GREEN);
        printMsg("  Path: " . $status['path'], COLOR_RESET);
        printMsg("  Version: " . $status['version'], COLOR_RESET);
    } else {
        printMsg("✗ yt-dlp is NOT installed", COLOR_RED);
    }

    return $status;
}

/**
 * Print installation instructions for Deno
 */
function printDenoInstallInstructions($denoStatus) {
    printHeader("Deno Installation Instructions (Ubuntu Only)");

    printMsg("Run the following commands as root:", COLOR_YELLOW);
    echo PHP_EOL;

    if (!$denoStatus['installed']) {
        printMsg("# Install Deno", COLOR_BLUE);
        echo "curl -fsSL https://deno.land/install.sh | sh" . PHP_EOL;
        echo PHP_EOL;
    }

    if ($denoStatus['installed'] && !$denoStatus['accessible']) {
        printMsg("# Copy Deno to a globally accessible location", COLOR_BLUE);

        $sourcePath = $denoStatus['path'];
        if (empty($sourcePath)) {
            $sourcePath = '/root/.deno/bin/deno';
        }

        echo "# Remove existing link/file if exists" . PHP_EOL;
        echo "sudo rm -f /usr/local/bin/deno" . PHP_EOL;
        echo PHP_EOL;
        echo "# Copy the binary" . PHP_EOL;
        echo "sudo cp $sourcePath /usr/local/bin/deno" . PHP_EOL;
        echo PHP_EOL;
        echo "# Set correct permissions" . PHP_EOL;
        echo "sudo chmod 755 /usr/local/bin/deno" . PHP_EOL;
        echo PHP_EOL;
        echo "# Verify it works" . PHP_EOL;
        echo "sudo -u www-data /usr/local/bin/deno --version" . PHP_EOL;
    }

    if (!$denoStatus['installed']) {
        echo PHP_EOL;
        printMsg("# After installing, copy to global location:", COLOR_BLUE);
        echo "sudo cp ~/.deno/bin/deno /usr/local/bin/deno" . PHP_EOL;
        echo "sudo chmod 755 /usr/local/bin/deno" . PHP_EOL;
        echo PHP_EOL;
        printMsg("# Verify installation:", COLOR_BLUE);
        echo "sudo -u www-data /usr/local/bin/deno --version" . PHP_EOL;
    }

    echo PHP_EOL;
}

/**
 * Print installation instructions for Node.js
 */
function printNodeJSInstallInstructions($nodeStatus) {
    printHeader("Node.js Installation Instructions (Ubuntu Only)");

    printMsg("Run the following commands as root:", COLOR_YELLOW);
    echo PHP_EOL;

    if (!$nodeStatus['installed']) {
        printMsg("# Option 1: Install Node.js from Ubuntu repository (simpler)", COLOR_BLUE);
        echo "sudo apt update" . PHP_EOL;
        echo "sudo apt install -y nodejs npm" . PHP_EOL;
        echo PHP_EOL;

        printMsg("# Option 2: Install Node.js LTS via NodeSource (recommended for latest version)", COLOR_BLUE);
        echo "curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -" . PHP_EOL;
        echo "sudo apt install -y nodejs" . PHP_EOL;
        echo PHP_EOL;

        printMsg("# Verify installation:", COLOR_BLUE);
        echo "node --version" . PHP_EOL;
        echo "sudo -u www-data node --version" . PHP_EOL;
    }

    if ($nodeStatus['installed'] && !$nodeStatus['accessible']) {
        printMsg("# Fix Node.js permissions", COLOR_BLUE);
        $nodePath = $nodeStatus['path'];

        echo "sudo chmod 755 $nodePath" . PHP_EOL;
        echo PHP_EOL;
        echo "# Verify it works" . PHP_EOL;
        echo "sudo -u www-data $nodePath --version" . PHP_EOL;
    }

    echo PHP_EOL;
}

/**
 * Print yt-dlp configuration instructions
 */
function printYtdlpConfigInstructions($denoStatus, $nodeStatus) {
    printHeader("yt-dlp Configuration");

    if ($denoStatus['accessible'] || $nodeStatus['accessible']) {
        printMsg("✓ At least one JavaScript runtime is available!", COLOR_GREEN);
        echo PHP_EOL;

        if ($denoStatus['accessible'] && $nodeStatus['accessible']) {
            printMsg("Both Deno and Node.js are available. yt-dlp will use Deno by default.", COLOR_RESET);
        } elseif ($nodeStatus['accessible'] && !$denoStatus['accessible']) {
            printMsg("Only Node.js is available. Configure yt-dlp to use it:", COLOR_YELLOW);
            echo PHP_EOL;
            echo "# Add to yt-dlp config file:" . PHP_EOL;
            echo "echo '--js-runtimes nodejs' | sudo tee -a /etc/yt-dlp.conf" . PHP_EOL;
        }
    } else {
        printMsg("✗ No JavaScript runtime is accessible by www-data!", COLOR_RED);
        printMsg("YouTube downloads will fail until you install Deno or Node.js.", COLOR_YELLOW);
    }

    echo PHP_EOL;
}

/**
 * Summary and status
 */
function printSummary($denoStatus, $nodeStatus, $ytdlpStatus) {
    printHeader("Summary");

    $allGood = true;

    // yt-dlp status
    if ($ytdlpStatus['installed']) {
        printMsg("✓ yt-dlp: " . $ytdlpStatus['version'], COLOR_GREEN);
    } else {
        printMsg("✗ yt-dlp: Not installed", COLOR_RED);
        $allGood = false;
    }

    // Deno status
    if ($denoStatus['accessible']) {
        printMsg("✓ Deno: " . $denoStatus['version'] . " (accessible by www-data)", COLOR_GREEN);
    } elseif ($denoStatus['installed']) {
        printMsg("⚠ Deno: " . $denoStatus['version'] . " (NOT accessible by www-data)", COLOR_YELLOW);
    } else {
        printMsg("✗ Deno: Not installed", COLOR_RED);
    }

    // Node.js status
    if ($nodeStatus['accessible']) {
        printMsg("✓ Node.js: " . $nodeStatus['version'] . " (accessible by www-data)", COLOR_GREEN);
    } elseif ($nodeStatus['installed']) {
        printMsg("⚠ Node.js: " . $nodeStatus['version'] . " (NOT accessible by www-data)", COLOR_YELLOW);
    } else {
        printMsg("✗ Node.js: Not installed", COLOR_RED);
    }

    echo PHP_EOL;

    // Overall status
    if ($denoStatus['accessible'] || $nodeStatus['accessible']) {
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_GREEN);
        printMsg(" ✓ YouTube downloads should work correctly!", COLOR_GREEN);
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_GREEN);
    } else {
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_RED);
        printMsg(" ✗ YouTube downloads will NOT work!", COLOR_RED);
        printMsg("   You need to install Deno or Node.js and make it accessible", COLOR_RED);
        printMsg("   to the www-data user.", COLOR_RED);
        printMsg("═══════════════════════════════════════════════════════════════", COLOR_RED);
        $allGood = false;
    }

    echo PHP_EOL;

    return $allGood;
}

// =============================================================================
// Main Execution
// =============================================================================

printMsg(PHP_EOL . "╔═══════════════════════════════════════════════════════════════╗", COLOR_BOLD);
printMsg("║     AVideo Encoder - JavaScript Runtime Checker                ║", COLOR_BOLD);
printMsg("║     For yt-dlp YouTube Download Support                        ║", COLOR_BOLD);
printMsg("╚═══════════════════════════════════════════════════════════════╝", COLOR_BOLD);

printMsg(PHP_EOL . "⚠ IMPORTANT: Installation commands are for Ubuntu/Debian only!", COLOR_YELLOW);

// Check current user
$currentUser = trim(shell_exec('whoami'));
printMsg("Running as user: $currentUser", COLOR_RESET);

if ($currentUser !== 'root') {
    printMsg("⚠ Warning: Some checks may fail without root privileges", COLOR_YELLOW);
}

// Run checks
$ytdlpStatus = checkYtdlp();
$denoStatus = checkDeno();
$nodeStatus = checkNodeJS();

// Print summary
$allGood = printSummary($denoStatus, $nodeStatus, $ytdlpStatus);

// Print installation instructions if needed
if (!$denoStatus['accessible']) {
    printDenoInstallInstructions($denoStatus);
}

if (!$nodeStatus['accessible']) {
    printNodeJSInstallInstructions($nodeStatus);
}

// Print yt-dlp config instructions
printYtdlpConfigInstructions($denoStatus, $nodeStatus);

// Exit with appropriate code
exit($allGood ? 0 : 1);
