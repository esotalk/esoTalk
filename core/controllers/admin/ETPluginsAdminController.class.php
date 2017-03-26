<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller handles the management of plugins.
 *
 * @package esoTalk
 */
class ETPluginsAdminController extends ETAdminController {


/**
 * Show the list of plugins.
 *
 * @return void
 */
public function action_index()
{
	$plugins = $this->getPlugins();

	$this->title = T("Plugins");
	$this->data("plugins", $plugins);
	$this->render("admin/plugins");
}


/**
 * Return a list of plugins and their information.
 *
 * @return array
 */
protected function getPlugins()
{
	// Get the installed plugins and their details by reading the plugins/ directory.
	if ($handle = opendir(PATH_PLUGINS)) {
	    while (false !== ($file = readdir($handle))) {

			// Make sure the plugin is valid, and include its plugin.php file.
	        if ($file[0] != "." and $file != "index.html" and file_exists($pluginFile = PATH_PLUGINS."/$file/plugin.php") and (include_once $pluginFile)) {

				// Add the plugin's information and status to the array.
				$plugins[$file] = array(
					"loaded"   => in_array($file, C("esoTalk.enabledPlugins")),
					"info"     => ET::$pluginInfo[$file],
					"settings" => false
				);

				// If this skin's settings function returns a view path, then store it.
				if ($plugins[$file]["loaded"]) $plugins[$file]["settings"] = method_exists(ET::$plugins[$file], "settings");
			}

	    }
	    closedir($handle);
	}

	ksort($plugins, SORT_NATURAL | SORT_FLAG_CASE);

	return $plugins;
}


/**
 * Toggle a plugin.
 *
 * @param string $plugin The name of the plugin.
 * @return void
 */
public function action_toggle($plugin = "")
{
	if (!$this->validateToken()) return;

	// Get the plugin.
	$plugins = $this->getPlugins();
	if (!$plugin or !array_key_exists($plugin, $plugins)) return;

	// Get the list of currently enabled plugins.
	$enabledPlugins = C("esoTalk.enabledPlugins");

	// If the plugin is currently enabled, take it out of the loaded plugins array.
	$k = array_search($plugin, $enabledPlugins);
	if ($k !== false) {
		unset($enabledPlugins[$k]);

		// Call the plugin's disable function.
		ET::$plugins[$plugin]->disable();
	}

	// Otherwise, if it's not enabled, add it to the array.
	else {
		if (isset($plugins[$plugin]["info"]["priority"]))
			addToArray($enabledPlugins, $plugin, $plugins[$plugin]["info"]["priority"]);
		else $enabledPlugins[] = $plugin;

		// Check the plugin's dependencies.
		$dependencyFailure = false;
		if (isset(ET::$pluginInfo[$plugin]["dependencies"]) and is_array(ET::$pluginInfo[$plugin]["dependencies"])) {
			foreach (ET::$pluginInfo[$plugin]["dependencies"] as $name => $minVersion) {

				// Check the dependency is met, whether it be a plugin or a version of esoTalk.
				if (($name == "esoTalk" and !version_compare(ESOTALK_VERSION, $minVersion, ">="))
					or ($name != "esoTalk" and (!isset(ET::$plugins[$name]) or !version_compare(ET::$pluginInfo[$name]["version"], $minVersion, ">=")))
					) {
						$this->message(sprintf(T("message.pluginDependencyNotMet"), $name, $minVersion), "warning");
						$dependencyFailure = true;
				}

			}
		}

		if ($dependencyFailure) {
			$this->redirect(URL("admin/plugins"));
			return;
		}

		// Set up an instance of the plugin so we can call its setup function.
		if (file_exists($file = PATH_PLUGINS."/".sanitizeFileName($plugin)."/plugin.php")) include_once $file;
		$className = "ETPlugin_$plugin";
		if (class_exists($className)) {
			$pluginObject = new $className("addons/plugins/".$plugin);

			// Call the plugin's setup function. If the setup failed, show a message.
			if (($msg = $pluginObject->setup(C("$plugin.version"))) !== true) {
				$this->message(sprintf(T("message.pluginCannotBeEnabled"), $plugin, $msg), "warning");
				$this->redirect(URL("admin/plugins"));
				return;
			}

			ET::writeConfig(array("$plugin.version" => ET::$pluginInfo[$plugin]["version"]));
		}
	}

	// Write to the config file.
	ET::writeConfig(array("esoTalk.enabledPlugins" => $enabledPlugins));

	$this->redirect(URL("admin/plugins"));
}


/**
 * Call a plugin's settings function and render a sheet containing the view it returns.
 *
 * @param string $plugin The name of the plugin.
 * @return void
 */
public function action_settings($plugin = "")
{
	// Get the plugin.
	$plugins = $this->getPlugins();
	if (!$plugin or !array_key_exists($plugin, $plugins)) return;
	$pluginArray = $plugins[$plugin];

	// If the plugin isn't loaded or doesn't have settings, we can't access its settings.
	if (!$pluginArray["loaded"] or !$pluginArray["settings"]) return;

	// Call the plugin's settings function and get the view it wants rendered.
	$view = ET::$plugins[$plugin]->settings($this);

	// Render the pluginSettings view, which will render the plugin's settings view.
	$this->data("plugin", $pluginArray);
	$this->data("view", $view);
	$this->render("admin/pluginSettings");
}


/**
 * Uninstall a plugin by calling its uninstall function and removing its directory.
 *
 * @param string $plugin The name of the plugin.
 * @return void
 */
public function action_uninstall($plugin = "")
{
	if (!$this->validateToken()) return;

	// Get the plugin.
	$plugins = $this->getPlugins();
	if (!$plugin or !array_key_exists($plugin, $plugins)) return;

	$enabledPlugins = C("esoTalk.enabledPlugins");

	// If the plugin is currently enabled, take it out of the loaded plugins array.
	$k = array_search($plugin, $enabledPlugins);
	if ($k !== false) {
		unset($enabledPlugins[$k]);

		// Call the plugin's disable function.
		ET::$plugins[$plugin]->disable();

		ET::writeConfig(array("esoTalk.enabledPlugins" => $enabledPlugins));
	}

	// Set up an instance of the plugin so we can call its uninstall function.
	if (file_exists($file = PATH_PLUGINS."/".sanitizeFileName($plugin)."/plugin.php")) include_once $file;
	$className = "ETPlugin_$plugin";
	if (class_exists($className)) {
		$pluginObject = new $className;
		$pluginObject->uninstall();
	}

	// Attempt to remove the directory. If we couldn't, show a "not writable" message.
	if (!is_writable($file = PATH_PLUGINS) or !is_writable($file = PATH_PLUGINS."/$plugin") or !rrmdir($file))
		$this->message(sprintf(T("message.notWritable"), $file), "warning");

	// Otherwise, show a success message.
	else $this->message(T("message.pluginUninstalled"), "success");

	$this->redirect(URL("admin/plugins"));
}


// Install an uploaded plugin.
// protected function installPlugin()
// {
// 	// If the uploaded file has any errors, don't proceed.
// 	if ($_FILES["installPlugin"]["error"]) {
// 		$this->esoTalk->message("invalidPlugin");
// 		return false;
// 	}

// 	// Temorarily move the uploaded plugin into the plugins directory so that we can read it.
// 	if (!move_uploaded_file($_FILES["installPlugin"]["tmp_name"], "plugins/{$_FILES["installPlugin"]["name"]}")) {
// 		$this->esoTalk->message("notWritable", false, "plugins/");
// 		return false;
// 	}

// 	// Unzip the plugin. If we can't, show an error.
// 	if (!($files = unzip("plugins/{$_FILES["installPlugin"]["name"]}", "plugins/"))) $this->esoTalk->message("invalidPlugin");
// 	else {

// 		// Loop through the files in the zip and make sure it's a valid plugin.
// 		$directories = 0; $pluginFound = false;
// 		foreach ($files as $k => $file) {

// 			// Strip out annoying Mac OS X files!
// 			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
// 				unset($files[$k]);
// 				continue;
// 			}

// 			// If the zip has more than one base directory, it's not a valid plugin.
// 			if ($file["directory"] and substr_count($file["name"], "/") < 2) $directories++;

// 			// Make sure there's an actual plugin file in there.
// 			if (substr($file["name"], -10) == "plugin.php") $pluginFound = true;
// 		}

// 		// OK, this plugin in valid!
// 		if ($pluginFound and $directories == 1) {

// 			// Loop through plugin files and write them to the plugins directory.
// 			$error = false;
// 			foreach ($files as $k => $file) {

// 				// Make a directory if it doesn't exist!
// 				if ($file["directory"] and !is_dir("plugins/{$file["name"]}")) mkdir("plugins/{$file["name"]}");

// 				// Write a file.
// 				elseif (!$file["directory"]) {
// 					if (!writeFile("plugins/{$file["name"]}", $file["content"])) {
// 						$this->esoTalk->message("notWritable", false, "plugins/{$file["name"]}");
// 						$error = true;
// 						break;
// 					}
// 				}
// 			}

// 			// Everything copied over correctly - success!
// 			if (!$error) $this->esoTalk->message("pluginAdded");
// 		}

// 		// Hmm, something went wrong. Show an error.
// 		else $this->esoTalk->message("invalidPlugin");
// 	}

// 	// Delete the temporarily uploaded plugin file.
// 	unlink("plugins/{$_FILES["installPlugin"]["name"]}");
// }

}
