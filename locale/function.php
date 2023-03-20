<?php
include 'locale.json.php';

if (empty($_SESSION['lang'])) {
$_SESSION['lang'] = 'en_US';
include 'en_US.php';
} else {
include $_SESSION['lang'] . '.php';
}

// Set Language variable
if (isset($_POST['lang']) && !empty($_POST['lang'])) {
	$_SESSION['lang'] = $_POST['lang'];
if (isset($_SESSION['lang']) && $_SESSION['lang'] == $_POST['lang']) {
	echo '<script type="text/javascript">
	window.location.replace(document.referrer);
	</script>';
 }
}


function display_lang($decoded_json, $searchKey) {
	foreach ($decoded_json as $key => $val) {
		$value = $decoded_json[$key]['value'];
		$label = $decoded_json[$key]['label'];
		$flag = $decoded_json[$key]['flag'];
			if ($value == $searchKey) {
				echo "<option data-content=\"<span class='flag'><i class='flagstrap-icon flagstrap-" . $flag  ."' aria-hidden='true'></i></span><span class='lanG'>" . $label . "</span>\" value=\"" . $value . "\"";
				echo (isset($_SESSION['lang']) && $_SESSION['lang'] == $value) ? " selected></option>\n" : "></option>\n";
				}
	}
}

?>
