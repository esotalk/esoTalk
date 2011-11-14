<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * A static class that is a central point of access for all things esoTalk. This class:
 * - Contains methods to get instances of models and other classes.
 * - Keeps track of global elements such as plugins, the skin, the controller, the database, the session,
 *   and the cache.
 * - Triggers events, calling handler functions in loaded plugins.
 * - Provides methods to read and alter configuration files and language definitions.
 * 
 * @package esoTalk
 */
class ET {


/**
 * The relative path to the root esoTalk folder. (For example, at http://example.com/test/path/forum/index.php,
 * the web path would be /test/path/forum/.
 * @var string
 */ 
public static $webPath = "";


/**
 * An collecting array of skin information. Skins add their information to this array when their skin.php file
 * is included.
 * @var array
 */
public static $skinInfo = array();


/**
 * An collecting array of plugin information. Plugins add their information to this array when their
 * plugin.php file is included.
 * @var array
 */
public static $pluginInfo = array();


/**
 * An collecting array of language information. Languages add their information to this array when their
 * definitions.php file is included.
 * @var array
 */
public static $languageInfo = array();


/**
 * The name of the current skin.
 * @var string
 */
public static $skinName = "";


/**
 * An instance of the current skin object.
 * @var ETSkin
 */
public static $skin;


/**
 * An array containing instances of loaded plugin objects.
 * @var array
 */
public static $plugins = array();


/**
 * The name of the current controller.
 * @var string
 */
public static $controllerName = "";


/**
 * An instnace of the current controller object.
 * @var ETController
 */
public static $controller;


/**
 * An instance of the ETSession class.
 * @var ETSession
 */
public static $session;


/**
 * An instance of the ETDatabase class.
 * @var ETDatabase
 */
public static $database;


/**
 * An instance of the ETCache class.
 * @var ETCache
 */
public static $cache;


/**
 * Shortcut function to fetch a new ETSQLQuery object (or an ETSQLResult object if a query string is passed.)
 * 
 * @param string $sql An optional SQL query string to run.
 * @return ETSQLQuery|ETSQLResult
 */
public static function SQL($sql = "")
{
	if ($sql) return ET::$database->query($sql);
	else return ETFactory::make("sqlQuery");
}


/**
 * Trigger an event and call event handlers within plugins.
 * 
 * @param string $event The name of the event.
 * @param array $parameters An array of parameters to pass to the event handlers as arguments.
 * @return array An array of values returned by the event handlers.
 */
public static function trigger($event, $parameters = array())
{
	$returns = array();
	foreach (self::$plugins as $plugin) {
		if (method_exists($plugin, "handler_$event")) {
			$returns[] = call_user_func_array(array($plugin, "handler_$event"), $parameters);
		}
	}
	return $returns;
}


/**
 * Check for updates to the esoTalk software.
 * 
 * @return array|bool The new version information, or false if we are running the latest version.
 */
public static function checkForUpdates()
{
	$json = file_get_contents("http://get.esotalk.com/versions.txt");
	$packages = json_decode($json, true);
	
	// Compare the installed version and the latest version. Show a message if there is a new version.
	if (isset($packages["esoTalk"]) and version_compare($packages["esoTalk"]["version"], ESOTALK_VERSION, ">") == -1) return $packages["esoTalk"];

	return false;
}


//***** CONFIG-RELATED FUNCTIONS

/**
 * An array of configuration settings.
 * @var array
 */
public static $config = array();


/**
 * Load values from a config file into the config array.
 * 
 * @param string $file The config file to load values from.
 * @return void
 */
public static function loadConfig($file)
{
	include $file;
	ET::$config = array_merge(ET::$config, $config);
}


/**
 * Fetch the value of a configuration option, falling back to a default if it isn't set.
 * 
 * @param string $key The name of the configuration option.
 * @param mixed $default A default value to fall back on if the config option isn't set.
 * @return mixed The value of the config option.
 */
public static function config($key, $default = null)
{
	return isset(ET::$config[$key]) ? ET::$config[$key] : $default;
}


/**
 * Write out an array of values to the config.php file.
 * 
 * @param array $values The config values to write.
 * @return void
 */
public static function writeConfig($values)
{
	// Include the config file so we can re-write the values contained within it.
	if (file_exists($file = PATH_CONFIG."/config.php")) include $file;

	// Now add the $values to the $config array.
	if (!isset($config) or !is_array($config)) $config = array();
	$config = array_merge($config, $values);
	self::$config = array_merge(self::$config, $values);

	// Finally, loop through and write the config array to the config file.
	$contents = "<?php\n";
	foreach ($config as $k => $v) $contents .= '$config["'.$k.'"] = '.var_export($v, true).";\n";
	$contents .= "\n// Last updated by: ".ET::$session->user["username"]." (".ET::$session->ip.") @ ".date("r")."\n?>";
	file_put_contents($file, $contents);
}



//***** LANGUAGE-RELATED FUNCTIONS

/**
 * The name of the current language.
 * @var string
 */
public static $language = "";


/**
 * An array of language definitions.
 * @var array
 */
public static $definitions = array();


/**
 * The language state can be saved with saveLanguageState() and reverted back to what it was before with
 * reverLanguageState(). These variables store the old language information so it can be restored upon revert.
 */
private static $_language = "";
private static $_definitions = array();


/**
 * Load a language and its definition files, depending on what plugins are enabled.
 * 
 * @param string $language The name of the language.
 * @return void
 */
public static function loadLanguage($language = "")
{
	// Clear the currently loaded definitions.
	self::$definitions = array();

	// If the specified language doesn't exist, use the default language.
	self::$language = file_exists(PATH_LANGUAGES."/".sanitizeFileName($language)."/definitions.php") ? $language : C("esoTalk.language");

	// Load the main definitions file.
	$languagePath = PATH_LANGUAGES."/".sanitizeFileName(self::$language);
	self::loadDefinitions("$languagePath/definitions.php");

	// Loop through the loaded plugins and include their definition files, if they exist.
	foreach (C("esoTalk.enabledPlugins") as $plugin) {
		if (file_exists($file = "$languagePath/definitions.".sanitizeFileName($plugin).".php"))
			self::loadDefinitions($file);
	}
}


/**
 * Load definitions from a language file into the definitions array.
 * 
 * @param string $file The file to load definitions from.
 * @return void
 */
public static function loadDefinitions($file)
{
	include $file;
	ET::$definitions = array_merge(ET::$definitions, (array)@$definitions);
}


/**
 * Save the current language state so it can be restored later.
 * 
 * @return void
 */
public static function saveLanguageState()
{
	self::$_definitions = self::$definitions;
	self::$_language = self::$language;
}


/**
 * Revert to the previous language state, saved by saveLanguageState().
 * 
 * @return void
 */
public static function revertLanguageState()
{
	self::$language = self::$_language;
	self::$definitions = self::$_definitions;
}


/**
 * Add a definition to the definitions array, but only if it has not already been defined.
 * 
 * @param string $key The definition key.
 * @param string $value The definition value.
 * @return void
 */
public static function define($key, $value)
{
	if (isset(self::$definitions[$key])) return false;
	self::$definitions[$key] = $value;
}


/**
 * Fetch the translation of a string, falling back to a default if it is not defined. The string provided will
 * be used if a fallback is required but not provided.
 * 
 * @param string $string The string to translate (ie. the definition key).
 * @param string $default A default value to fall back to if the string is not defined.
 * @return string The translation.
 */
public static function translate($string, $default = false)
{
	return isset(ET::$definitions[$string]) ? ET::$definitions[$string] : ($default ? $default : $string);
}


/**
 * Get an array of available language packs in the languages directory.
 * 
 * @return array An array of the names of all available languages, in alphabetical order.
 */
public static function getLanguages()
{
	$languages = array();
	if ($handle = opendir(PATH_LANGUAGES)) {
	    while (false !== ($file = readdir($handle))) {

	        if ($file[0] != "." and file_exists($defs = PATH_LANGUAGES."/$file/definitions.php")) {

	        	// Include the file so we get the language information in ET::$languageInfo.
	        	include_once $defs;
				$languages[] = $file;
			}

		}
	}
	sort($languages);
	return $languages;
}



//***** CLASS INSANTIATION FUNCTIONS

/**
 * An array of class instances, with the class factory names as the keys.
 * @var array
 */
private static $instances = array();


/**
 * Get an instance of the class specified by $factoryName. So that we don't constantly get new instances and
 * increase overhead, we store instances in ET::$instances and reuse them as needed.
 * 
 * @return mixed An instance of the specified class.
 */
public static function getInstance($factoryName)
{
	if (!isset(self::$instances[$factoryName]))
		self::$instances[$factoryName] = ETFactory::make($factoryName);
	return self::$instances[$factoryName];
}


/**
 * Shortcut functions to get common classes, including models and the formatter.
 */
public static function memberModel()
{
	return self::getInstance("memberModel");
}

public static function conversationModel()
{
	return self::getInstance("conversationModel");
}

public static function postModel()
{
	return self::getInstance("postModel");
}

public static function searchModel()
{
	return self::getInstance("searchModel");
}

public static function channelModel()
{
	return self::getInstance("channelModel");
}

public static function groupModel()
{
	return self::getInstance("groupModel");
}

public static function upgradeModel()
{
	return self::getInstance("upgradeModel");
}

public static function activityModel()
{
	return self::getInstance("activityModel");
}

public static function formatter()
{
	return self::getInstance("format");
}

public static function uploader()
{
	return self::getInstance("upload");
}



//***** ERROR HANDLING FUNCTIONS

/**
 * Halt page execution and show a fatal error message.
 * 
 * @param Exception $exception The exception that was the cause of the fatal error.
 * @return void
 */
public static function fatalError($exception)
{
	// Get the information about the exception.
	$errorNumber = $exception->getCode();
	$message = $exception->getMessage();
	$file = $exception->getFile();
	$line = $exception->getLine();
	$backtrace = $exception->getTrace();

	// Use the controller's response type, or just use the default one.
	$responseType = (self::$controller and self::$controller->responseType) ? self::$controller->responseType : RESPONSE_TYPE_DEFAULT;

	// Clean the output buffer and send headers if possible.
	@ob_end_clean();
	if (!headers_sent()) {
		header("HTTP/1.0 500 Internal Server Error");
		header("Content-Type: text/html; charset=utf-8");
	}

	// See if we can get the lines of the file that caused the error.
	if (is_string($file) and is_numeric($line) and file_exists($file)) $errorLines = file($file);
	else $errorLines = false;

	$data = array();
	$data["pageTitle"] = T("Fatal Error");
	
	// Render the view into $data["content"], so it will be outputted within the master view.
	ob_start();
	include PATH_VIEWS."/error.php";
	$data["content"] = ob_get_contents();
	ob_end_clean();

	// Render the master view, or just output the content if we can't find one.
	if ($responseType === RESPONSE_TYPE_DEFAULT and file_exists($view = PATH_VIEWS."/message.master.php"))
		include $view;
	else
		echo $data["content"];

	exit;
}


/**
 * Render a "404 Not Found" error.
 * 
 * @return void
 */
public static function notFound()
{
	header("HTTP/1.1 404 Not Found");

	$data = array();
	$data["pageTitle"] = T("Page Not Found");

	// Render the view into $data["content"], so it will be outputted within the master view.
	ob_start();
	include PATH_VIEWS."/404.php";
	$data["content"] = ob_get_contents();
	ob_end_clean();

	// Render the master view.
	include PATH_VIEWS."/message.master.php";
	exit;
}

}
?>