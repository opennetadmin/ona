<?php
/** internationalization.inc.php **/
$default_lang = 'en';

// Add new supported languages to this array
// Beware that the value here has to be the same as the name of the directory.
// ie : "fr_FR.UTF-8" implies the following valid directory : ./Locale/fr_FR.UTF-8/LC_MESSAGES/
$supported_langs = array(
	'de' => 'de_DE.UTF-8', 
	'en' => 'en_US.UTF8',
	'ja' => 'ja_JP.UTF-8',
	'fr' => 'fr_FR.UTF-8'
);

$hal = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$langs = explode(',', $hal);
foreach($langs as $lang){
	$lang_prefix = substr($lang, 0, 2);
	if (isset($supported_langs[$lang_prefix])) {
		$lc=$supported_langs[$lang_prefix];
		//echo 'LC='.$lc;
		break;
	}
	$lc=$supported_langs[$default_lang];
	//echo 'LC(default)='.$lc;
}

$folder = "locale";
$directory = str_replace(DIRECTORY_SEPARATOR,'/',realpath(dirname(__FILE__)))."/$folder";
$domain = "messages";
setlocale(LC_MESSAGES, $lc);
setlocale(LC_ALL, $lc);
bindtextdomain($domain, $directory);
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

/* For test purposes */
//echo " directory=".$directory."/".setlocale(LC_MESSAGES,0)."/LC_MESSAGES/".$domain.".po</br>";
//echo _("HELLO_WORLD")."</br>";
//echo _("Password");

?>
