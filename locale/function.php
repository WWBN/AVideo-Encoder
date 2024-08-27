<?php

include 'locale.json.php';

// Set Language variable
if (isset($_POST['lang']) && !empty($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
    if (isset($_SESSION['lang']) && $_SESSION['lang'] === $_POST['lang']) {
        echo '<script type="text/javascript">
    window.location.replace(document.referrer);
    </script>';
    }
}

if (!class_exists('Locale')) {
    $php_version = phpversion();

    // Extract the major and minor version numbers
    list($major, $minor) = explode('.', $php_version);

    // Construct the package name
    $package_name = "php{$major}.{$minor}-intl";

    // Generate the command to install the package
    $command = "sudo apt-get install $package_name";
    echo 'Install the PHP lib: ';
    echo $command;
    exit;
}

$locale = Locale::acceptFromHttp(@$_SERVER['HTTP_ACCEPT_LANGUAGE']);
$langBrowser = str_replace('-', '_', $locale);

function detecting_lang($languageBrowser, $default = 'en_US') {
    return !file_exists('../locale/' . $languageBrowser . '.php') ? $default : basename($languageBrowser, '.php');
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = detecting_lang($langBrowser);
}

if (isset($_SESSION['lang'])) {
    include $_SESSION['lang'] . '.php';
}

function display_lang($decoded_json, $searchKey) {
    foreach ($decoded_json as $key => $val) {
        $value = $decoded_json[$key]['value'];
        $label = $decoded_json[$key]['label'];
        $flag = $decoded_json[$key]['flag'];
        if ($value == $searchKey) {
            echo "<option data-content=\"<span class='flag'><i class='flagstrap-icon flagstrap-" . $flag . "' aria-hidden='true'></i></span><span class='lanG'>" . $label . "</span>\" value=\"" . $value . "\"";
            echo (isset($_SESSION['lang']) && $_SESSION['lang'] === $value) ? " selected></option>\n" : "></option>\n";
        }
    }
}
