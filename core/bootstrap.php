<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Bootstrap
 *
 * Main entry point. Does the following:
 * 1. Sets up the environment.
 * 2. Includes the configuration.
 * 3. Requires and registers essential classes.
 * 4. Sets up plugins.
 * 5. Initializes the session, the database, and the cache.
 * 6. Parses the page request.
 * 7. Sets up skins.
 * 8. Sets up the language.
 * 9. Sets up the appropriate controller.
 * 10. Dispatches to the controller, which will in turn render the page.
 *
 * @package esoTalk
 */


//***** 1. SET UP ENVIRONMENT

// By default, only display important errors (no warnings or notices.)
ini_set("display_errors", "On");
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);

// Make sure a default timezone is set... silly PHP 5.
if (ini_get("date.timezone") == "") date_default_timezone_set("GMT");

// Define directory constants.
if (!defined("PATH_CONTROLLERS")) define("PATH_CONTROLLERS", PATH_CORE."/controllers");
if (!defined("PATH_LIBRARY")) define("PATH_LIBRARY", PATH_CORE."/lib");
if (!defined("PATH_MODELS")) define("PATH_MODELS", PATH_CORE."/models");
if (!defined("PATH_VIEWS")) define("PATH_VIEWS", PATH_CORE."/views");

// Include some essential files.
require PATH_LIBRARY."/functions.general.php";
require PATH_LIBRARY."/ET.class.php"; // phone home!

// Set up error and exception handling.
function errorHandler($code, $message, $file, $line)
{
	// Make sure this error code is included in error_reporting.
	if ((error_reporting() & $code) != $code) return false;

	ET::fatalError(new ErrorException($message, $code, 1, $file, $line));
}
set_error_handler("errorHandler", E_USER_ERROR);
set_exception_handler(array("ET", "fatalError"));

// Determine the relative path to this forum. For example, if the forum is at http://forum.com/test/forum/,
// the web path should be /test/forum.
$parts = explode("/", $_SERVER["PHP_SELF"]);
$key = array_search("index.php", $parts);
if ($key !== false) ET::$webPath = implode("/", array_slice($parts, 0, $key));

// Undo register_globals.
undoRegisterGlobals();

// If magic quotes is on, strip the slashes that it added.
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map("undoMagicQuotes", $_REQUEST);
	$_GET = array_map("undoMagicQuotes", $_GET);
	$_POST = array_map("undoMagicQuotes", $_POST);
	$_COOKIE = array_map("undoMagicQuotes", $_COOKIE);
}


//***** 2. INCLUDE CONFIGURATION

// Include our config files.
ET::loadConfig(PATH_CORE."/config.defaults.php");

// If the config path is different from the default, but there's still a config file at the default location, include it.
if (PATH_CONFIG != PATH_ROOT."/config" and file_exists($file = PATH_ROOT."/config/config.php")) ET::loadConfig($file);

// Include the real config file.
if (file_exists($file = PATH_CONFIG."/config.php")) ET::loadConfig($file);

// In debug mode, show all errors (except for strict standards).
if (C("esoTalk.debug")) error_reporting(E_ALL & ~E_STRICT);

// Do we want to force HTTPS?
if (C("esoTalk.https") and (!array_key_exists("HTTPS", $_SERVER) or $_SERVER["HTTPS"] != "on")) {
    header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
    exit;
}



//***** 3. REQUIRE AND REGISTER ESSENTIAL CLASSES

// Require base classes that may be extended.
require PATH_LIBRARY."/ETFactory.class.php";
require PATH_LIBRARY."/ETPluggable.class.php";
require PATH_LIBRARY."/ETController.class.php";
require PATH_LIBRARY."/ETAdminController.class.php";
require PATH_LIBRARY."/ETModel.class.php";
require PATH_LIBRARY."/ETPlugin.class.php";
require PATH_LIBRARY."/ETSkin.class.php";

// Register main classes.
ETFactory::register("database", "ETDatabase", PATH_LIBRARY."/ETDatabase.class.php");
ETFactory::register("databaseStructure", "ETDatabaseStructure", PATH_LIBRARY."/ETDatabaseStructure.class.php");
ETFactory::register("sqlQuery", "ETSQLQuery", PATH_LIBRARY."/ETSQLQuery.class.php");
ETFactory::register("sqlResult", "ETSQLResult", PATH_LIBRARY."/ETSQLResult.class.php");
ETFactory::register("sqlRaw", "ETSQLRaw", PATH_LIBRARY."/ETSQLRaw.class.php");
ETFactory::register("session", "ETSession", PATH_LIBRARY."/ETSession.class.php");
ETFactory::register("cache", "ETCache", PATH_LIBRARY."/ETCache.class.php");
ETFactory::register("form", "ETForm", PATH_LIBRARY."/ETForm.class.php");
ETFactory::register("format", "ETFormat", PATH_LIBRARY."/ETFormat.class.php");
ETFactory::register("upload", "ETUpload", PATH_LIBRARY."/ETUpload.class.php");
ETFactory::register("menu", "ETMenu", PATH_LIBRARY."/ETMenu.class.php");

// Register models.
ETFactory::register("searchModel", "ETSearchModel", PATH_MODELS."/ETSearchModel.class.php");
ETFactory::register("conversationModel", "ETConversationModel", PATH_MODELS."/ETConversationModel.class.php");
ETFactory::register("memberModel", "ETMemberModel", PATH_MODELS."/ETMemberModel.class.php");
ETFactory::register("postModel", "ETPostModel", PATH_MODELS."/ETPostModel.class.php");
ETFactory::register("channelModel", "ETChannelModel", PATH_MODELS."/ETChannelModel.class.php");
ETFactory::register("groupModel", "ETGroupModel", PATH_MODELS."/ETGroupModel.class.php");
ETFactory::register("activityModel", "ETActivityModel", PATH_MODELS."/ETActivityModel.class.php");
ETFactory::register("upgradeModel", "ETUpgradeModel", PATH_MODELS."/ETUpgradeModel.class.php");

// If esoTalk hasn't been installed, register the install controller and set it as the default route.
if (!C("esoTalk.installed")) {
	ETFactory::registerController("install", "ETInstallController", PATH_CONTROLLERS."/ETInstallController.class.php");
	ET::$config["esoTalk.defaultRoute"] = "install";
}

elseif (C("esoTalk.version") != ESOTALK_VERSION) {
	ETFactory::registerController("upgrade", "ETUpgradeController", PATH_CONTROLLERS."/ETUpgradeController.class.php");
}

// Otherwise, register all the default controllers and admin controllers.
else {
	ETFactory::registerController("conversations", "ETConversationsController", PATH_CONTROLLERS."/ETConversationsController.class.php");
	ETFactory::registerController("conversation", "ETConversationController", PATH_CONTROLLERS."/ETConversationController.class.php");
	ETFactory::registerController("post", "ETPostController", PATH_CONTROLLERS."/ETPostController.class.php");
	ETFactory::registerController("user", "ETUserController", PATH_CONTROLLERS."/ETUserController.class.php");
	ETFactory::registerController("settings", "ETSettingsController", PATH_CONTROLLERS."/ETSettingsController.class.php");
	ETFactory::registerController("channels", "ETChannelsController", PATH_CONTROLLERS."/ETChannelsController.class.php");
	ETFactory::registerController("member", "ETMemberController", PATH_CONTROLLERS."/ETMemberController.class.php");
	ETFactory::registerController("members", "ETMembersController", PATH_CONTROLLERS."/ETMembersController.class.php");
	// ETFactory::registerController("feed", "ETFeedController", PATH_CONTROLLERS."/ETFeedController.class.php");
	ETFactory::registerController("admin", "ETAdminController", PATH_CONTROLLERS."/ETAdminController.class.php");

	ETFactory::registerAdminController("dashboard", "ETDashboardAdminController", PATH_CONTROLLERS."/admin/ETDashboardAdminController.class.php");
	ETFactory::registerAdminController("settings", "ETSettingsAdminController", PATH_CONTROLLERS."/admin/ETSettingsAdminController.class.php");
	ETFactory::registerAdminController("appearance", "ETAppearanceAdminController", PATH_CONTROLLERS."/admin/ETAppearanceAdminController.class.php");
	ETFactory::registerAdminController("channels", "ETChannelsAdminController", PATH_CONTROLLERS."/admin/ETChannelsAdminController.class.php");
	ETFactory::registerAdminController("plugins", "ETPluginsAdminController", PATH_CONTROLLERS."/admin/ETPluginsAdminController.class.php");
	ETFactory::registerAdminController("groups", "ETGroupsAdminController", PATH_CONTROLLERS."/admin/ETGroupsAdminController.class.php");
	ETFactory::registerAdminController("languages", "ETLanguagesAdminController", PATH_CONTROLLERS."/admin/ETLanguagesAdminController.class.php");
}


//***** 5. SET UP PLUGINS

if (C("esoTalk.installed")) {

	foreach (C("esoTalk.enabledPlugins") as $v) {
		if (file_exists($file = PATH_PLUGINS."/".sanitizeFileName($v)."/plugin.php")) include_once $file;
		$className = "ETPlugin_$v";
		if (class_exists($className)) ET::$plugins[$v] = new $className("addons/plugins/".$v);
	}

}


//***** 6. INITIALIZE SESSION AND DATABASE, AND CACHE

// Initialize the cache.
$cacheClass = C("esoTalk.cache");
ET::$cache = ETFactory::make($cacheClass ? $cacheClass : "cache");

// Connect to the database.
ET::$database = ETFactory::make("database");
ET::$database->init(C("esoTalk.database.host"), C("esoTalk.database.user"), C("esoTalk.database.password"), C("esoTalk.database.dbName"), C("esoTalk.database.prefix"), C("esoTalk.database.connectionOptions"), C("esoTalk.database.port"));

// Initialize the session.
ET::$session = ETFactory::make("session");

// Check if any plugins need upgrading by comparing the versions in ET::$pluginInfo with the versions in
// ET::$config.
foreach (ET::$plugins as $k => $v) {
	if (C("$k.version") != ET::$pluginInfo[$k]["version"]) {
		if ($v->setup(C("$k.version"))) ET::writeConfig(array("$k.version" => ET::$pluginInfo[$k]["version"]));
	}
}



//***** 7. PARSE REQUEST

// If $_GET["p"] was explicitly specified, use that.
if (!empty($_GET["p"])) {
	$request = $_GET["p"];
	unset($_GET["p"]);
}

// If friendly URLs are turned on, process the REQUEST_URI into what the value of $_GET["p"] would normally be.
elseif (C("esoTalk.urls.friendly") and isset($_SERVER["REQUEST_URI"])) {

	// Remove the base path from the request URI.
	$request = preg_replace("|^".preg_quote(ET::$webPath)."(/index\.php)?|", "", $_SERVER["REQUEST_URI"]);

	// If there is a querystring, remove it.
	$selfURL = $request;
	if (($pos = strpos($request, "?")) !== false) $request = substr_replace($request, "", $pos);

	// Explode the request string. Make sure index.php is not the first item.
	$parts = explode("/", trim(urldecode($request), "/"));
	if ($parts[0] == "index.php") array_shift($parts);
	$request = implode("/", $parts);
}

// If we have no request information, use the default route.
if (empty($request)) $request = C("esoTalk.defaultRoute");

// We need to work out what the URL to this exact page is and set it to the controller later.
// If we didn't work it out above, set it to $request and append any GET variables.
if (empty($selfURL)) {
	$selfURL = $request;
	if (!empty($_GET)) $selfURL .= "?".http_build_query($_GET);
}

$requestParts = explode("/", $request);



//***** 8. SET UP SKIN

// If the user is an administrator and we're in the admin section, use the admin skin.
if (ET::$session->isAdmin() and $requestParts[0] == "admin") $skinName = C("esoTalk.adminSkin");

// If it's a mobile browser, use the mobile skin.
elseif (isMobileBrowser()) $skinName = C("esoTalk.mobileSkin");

// Otherwise, use the default skin.
else $skinName = C("esoTalk.skin");

// Include the skin file and instantiate its class.
ET::$skinName = $skinName;
if (file_exists($file = PATH_SKINS."/$skinName/skin.php")) include_once $file;
$skinClass = "ETSkin_".$skinName;
if (class_exists($skinClass)) ET::$skin = new $skinClass("addons/skins/".$skinName);

// If we haven't got a working skin, just use the base class. It'll be ugly, but it'll do.
if (empty(ET::$skin)) ET::$skin = new ETSkin("");

// Add the class as a plugin as well so that its event handlers are called through the normal process.
array_unshift(ET::$plugins, ET::$skin);



//***** 9. SET UP LANGUAGE

ET::loadLanguage(ET::$session->preference("language"));



//***** 10. SET UP CONTROLLER

// If the first part of the request is "admin", presume we're in the admin section.
if ($requestParts[0] == "admin") {
	$controllers = ETFactory::$adminControllers;
	array_shift($requestParts);
	if (empty($requestParts[0])) $requestParts[0] = "dashboard";
}

// Otherwise, use normal public controllers.
else {
	$controllers = ETFactory::$controllers;

	// If the first character of the URL parameter is numeric, assume the conversation controller.
	if ($requestParts[0] and is_numeric($requestParts[0][0])) array_unshift($requestParts, "conversation");
}

// Parse the request store all of the request information.
list(ET::$controllerName, ET::$controller, $method, $arguments, $responseType) = parseRequest($requestParts, $controllers);

ET::$controller->selfURL = $selfURL;
ET::$controller->controllerMethod = $method;
ET::$controller->responseType = $responseType;



//***** 11. SHOW THE PAGE

// Initialize plugins.
foreach (ET::$plugins as $plugin) $plugin->init();

// Include the config/custom.php file, a convenient way to override things.
if (file_exists($file = PATH_CONFIG."/custom.php")) include $file;

// Include render functions. We do this after we initialize plugins so that they can override any functions if they want.
require PATH_LIBRARY."/functions.render.php";

// Initialize the controller.
ET::$controller->init();

if (!C("esoTalk.gzipOutput") or C("esoTalk.debug") or !ob_start("ob_gzhandler")) ob_start();

// Dispatch request information to the controller. The controller will then call the appropriate function which
// will in turn render the page.
ET::$controller->dispatch(ET::$controller->controllerMethod, $arguments);

ob_end_flush();



//***** 12. CLEANUP

ET::$database->close();