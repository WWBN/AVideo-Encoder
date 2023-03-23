<?php
include 'locale.json.php';

// Set Language variable
if (isset($_POST['lang']) && !empty($_POST['lang'])) {
	$_SESSION['lang'] = $_POST['lang'];
if (isset($_SESSION['lang']) && $_SESSION['lang'] == $_POST['lang']) {
	echo '<script type="text/javascript">
	window.location.replace(document.referrer);
	</script>';
 }
}

	$locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$langBrowser =  str_replace('-', '_', $locale);

function detecting_lang($languageBrowser, $default = 'en_US') {
	$scanDir = '../locale/' . $languageBrowser . '.php';
		if (file_exists($scanDir) == false) {
			return $default;
		} else {
			return basename($languageBrowser, '.php');
			}
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
		$flag  = $decoded_json[$key]['flag'];
			if ($value == $searchKey) {
				echo "<option data-content=\"<span class='flag'><i class='flagstrap-icon flagstrap-" . $flag  ."' aria-hidden='true'></i></span><span class='lanG'>" . $label . "</span>\" value=\"" . $value . "\"";
				echo (isset($_SESSION['lang']) && $_SESSION['lang'] == $value) ? " selected></option>\n" : "></option>\n";
			}
	}
}

?>
