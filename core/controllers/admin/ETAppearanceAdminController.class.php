<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller handles changing of the forum appearance, including the management of skins and skin
 * settings.
 *
 * @package esoTalk
 */
class ETAppearanceAdminController extends ETAdminController {


/**
 * Show the appearearance page, including a list of skins.
 *
 * @return void
 */
public function action_index()
{
	$skins = $this->getSkins();

	$this->title = T("Appearance");
	$this->data("skins", $skins);
	$this->data("skin", C("esoTalk.skin") ? $skins[C("esoTalk.skin")] : array());
	$this->render("admin/appearance");
}


/**
 * Return a list of skins and their information.
 *
 * @return array
 */
protected function getSkins()
{
	$skins = array();

	// Get the installed skins and their details by reading the skins/ directory.
	if ($handle = opendir(PATH_SKINS)) {
	    while (false !== ($file = readdir($handle))) {

			// Make sure the skin is valid, and include its skin.php file.
	        if ($file[0] != "." and file_exists($skinFile = PATH_SKINS."/$file/skin.php") and (include_once $skinFile)) {

	        	// Add the skin's information and status to the array.
				$skins[$file] = array(
					"info" => ET::$skinInfo[$file],
					"selected" => $file == C("esoTalk.skin"),
					"selectedMobile" => $file == C("esoTalk.mobileSkin"),
					"settingsView" => false
				);

				// If this skin's settings function returns a view path, then store it.
				if ($skins[$file]["selected"]) $skins[$file]["settingsView"] = ET::$skin->settings($this);

			}

	    }
	    closedir($handle);
	}
	ksort($skins);

	return $skins;
}


/**
 * Activate a skin so it is used as the default skin.
 *
 * @param string $skin The name of the skin.
 * @return void
 */
public function action_activate($skin = "")
{
	if (!$this->validateToken()) return;

	// Get the skins and make sure this one exists.
	$skins = $this->getSkins();
	if (!$skin or !array_key_exists($skin, $skins)) return false;

	// Write the new setting to the config file.
	ET::writeConfig(array("esoTalk.skin" => $skin));

	// Clear skin cache.
	$files = glob(PATH_CACHE.'/css/*.*');
	foreach ($files as $file) unlink(realpath($file));

	$this->redirect(URL("admin/appearance"));
}


/**
 * Activate a skin so it is used as the mobile skin.
 *
 * @param string $skin The name of the skin.
 * @return void
 */
public function action_activateMobile($skin = "")
{
	if (!$this->validateToken()) return;

	// Get the skins and make sure this one exists.
	$skins = $this->getSkins();
	if (!$skin or !array_key_exists($skin, $skins)) return false;

	// Write the new setting to the config file.
	ET::writeConfig(array("esoTalk.mobileSkin" => $skin));

	// Clear skin cache.
	$files = glob(PATH_CACHE.'/css/*.*');
	foreach ($files as $file) unlink(realpath($file));

	$this->redirect(URL("admin/appearance"));
}


/**
 * Uninstall a skin by removing its directory.
 *
 * @param string $skin The name of the skin.
 * @return void
 */
public function action_uninstall($skin = "")
{
	if (!$this->validateToken()) return;

	// Get the skins and make sure this one exists.
	$skins = $this->getSkins();
	if (!$skin or !array_key_exists($skin, $skins)) return false;
	unset($skins[$skin]);

	// Attempt to remove the directory. If we couldn't, show a "not writable" message.
	if (!is_writable($file = PATH_SKINS) or !is_writable($file = PATH_SKINS."/$skin") or !rrmdir($file))
		$this->message(sprintf(T("message.notWritable"), $file), "warning");

	// Otherwise, show a success message.
	else $this->message(T("message.skinUninstalled"), "success");

	// If one of the skin config options is set to this skin, change it.
	$config = array();
	if (C("esoTalk.skin") == $skin) $config["esoTalk.skin"] = array_keys($skins)[0];
	if (C("esoTalk.mobileSkin") == $skin) $config["esoTalk.mobileSkin"] = array_keys($skins)[0];
	if (count($config)) ET::writeConfig($config);

	$this->redirect(URL("admin/appearance"));
}


// Install an uploaded skin.
/*
function installSkin()
{
	// If the uploaded file has any errors, don't proceed.
	if ($_FILES["installSkin"]["error"]) {
		$this->esoTalk->message("invalidSkin");
		return false;
	}

	// Temorarily move the uploaded skin into the skins directory so that we can read it.
	if (!move_uploaded_file($_FILES["installSkin"]["tmp_name"], "skins/{$_FILES["installSkin"]["name"]}")) {
		$this->esoTalk->message("notWritable", false, "skins/");
		return false;
	}

	// Unzip the skin. If we can't, show an error.
	if (!($files = unzip("skins/{$_FILES["installSkin"]["name"]}", "skins/"))) $this->esoTalk->message("invalidSkin");
	else {

		// Loop through the files in the zip and make sure it's a valid skin.
		$directories = 0; $skinFound = false;
		foreach ($files as $k => $file) {

			// Strip out annoying Mac OS X files!
			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
				unset($files[$k]);
				continue;
			}

			// If the zip has more than one base directory, it's not a valid skin.
			if ($file["directory"] and substr_count($file["name"], "/") < 2) $directories++;

			// Make sure there's an actual skin file in there.
			if (substr($file["name"], -8) == "skin.php") $skinFound = true;
		}

		// OK, this skin in valid!
		if ($skinFound and $directories == 1) {

			// Loop through skin files and write them to the skins directory.
			$error = false;
			foreach ($files as $k => $file) {

				// Make a directory if it doesn't exist!
				if ($file["directory"] and !is_dir("skins/{$file["name"]}")) mkdir("skins/{$file["name"]}");

				// Write a file.
				elseif (!$file["directory"]) {
					if (!writeFile("skins/{$file["name"]}", $file["content"])) {
						$this->esoTalk->message("notWritable", false, "skins/{$file["name"]}");
						$error = true;
						break;
					}
				}
			}

			// Everything copied over correctly - success!
			if (!$error) $this->esoTalk->message("skinAdded");
		}

		// Hmm, something went wrong. Show an error.
		else $this->esoTalk->message("invalidSkin");
	}

	// Delete the temporarily uploaded skin file.
	unlink("skins/{$_FILES["installSkin"]["name"]}");
}
*/

}
