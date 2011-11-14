<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The install controller handles the whole of the installation process, from checking for warnings and errors
 * to performing the installation.
 *
 * @package esoTalk
 */
class ETInstallController extends ETController {


/**
 * Initialize the install controller.
 *
 * @return void
 */
public function init()
{
	$this->addJSFile("js/lib/jquery.js");

	// Set the master view to the message master view.
	$this->masterView = "message.master";
	$this->title = T("Install esoTalk");

	// If any fatal errors should prevent the installation from taking place, dispatch to the "errors" method.
	if ($errors = $this->fatalChecks()) {
		$this->data("errors", $errors);
		$this->controllerMethod = "errors";
	}

	// Prevent JS and CSS from being aggregated, as we might not be able to write to the cache folder.
	ET::$config["esoTalk.aggregateCSS"] = ET::$config["esoTalk.aggregateJS"] = false;

	$this->trigger("init");
}


/**
 * When we first arrive at the installer, check for any warnings (non-fatal errors.) If there are any fatal
 * errors, we won't even get this far due to the check in init().
 *
 * @return void
 */
public function index()
{
	$this->warnings();
}


/**
 * Render a list of fatal errors. The actual check for errors occurs and is passed to the view in init().
 *
 * @return void
 */
public function errors()
{
	$this->data("fatal", true);
	$this->render("install/warnings");
}


/**
 * Check for warnings (non-fatal errors) and render a list of them. If there aren't any, proceed to the next step.
 *
 * @return void
 */
public function warnings()
{
	$errors = $this->warningChecks();
	if (!$errors) $this->redirect(URL("install/info"));

	$this->data("errors", $errors);
	$this->render("install/warnings");
}


/**
 * Set up and show the main installation data-entry form.
 *
 * @return void
 */
public function info()
{
	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("install/info");

	// Set some default values.
	$form->setValue("mysqlHost", "localhost");
	$form->setValue("tablePrefix", "et_");

	// If we have values stored in the session, use them.
	if ($values = ET::$session->get("install")) $form->setValues($values);

	// Work out what the base URL is.
	$dir = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/"));
	$baseURL = "http://{$_SERVER["HTTP_HOST"]}{$dir}/";
	$form->setValue("baseURL", $baseURL);

	// Work out if we can handle friendly URLs.
	if (!empty($_SERVER["REQUEST_URI"])) $form->setValue("friendlyURLs", true);

	// If the form was submitted...
	if ($form->isPostBack("submit")) {

		$values = $form->getValues();

		// Make sure the title isn't empty.
		if (!strlen($values["forumTitle"]))
			$form->error("forumTitle", T("message.empty"));

		// Make sure the admin's details are valid.
		if ($error = ET::memberModel()->validateUsername($values["adminUser"], false)) $form->error("adminUser", T("message.$error"));
		if ($error = ET::memberModel()->validateEmail($values["adminEmail"], false)) $form->error("adminEmail", T("message.$error"));
		if ($error = ET::memberModel()->validatePassword($values["adminPass"])) $form->error("adminPass", T("message.$error"));
		if ($values["adminPass"] != $values["adminConfirm"]) $form->error("adminConfirm", T("message.passwordsDontMatch"));

		// Try and connect to the database.
		try {
			ET::$database->init($values["mysqlHost"], $values["mysqlUser"], $values["mysqlPass"], $values["mysqlDB"]);
			ET::$database->connection();
		} catch (PDOException $e) {
			$form->error("mysql", sprintf(T("message.connectionError"), $e->getMessage()));
		}

		// Check to see if there are any conflicting tables already in the database.
		// If there are, show an error with a hidden input. If the form is submitted again with this hidden input,
		// proceed to perform the installation regardless.
		if (!$form->errorCount() and $values["tablePrefix"] != @$values["confirmTablePrefix"]) {

			// Get a list of all existing tables.
			$theirTables = array();
			$result = ET::SQL("SHOW TABLES");
			while ($table = $result->result()) $theirTables[] = $table;

			// Just do a check for the member table. If it exists with this prefix, we have a conflict.
			if (in_array($values["tablePrefix"]."member", $theirTables)) {

				$form->error("tablePrefix", T("message.tablePrefixConflict"));

				$form->addHidden("confirmTablePrefix", $values["tablePrefix"], true);

			}
		}

		// If there are no errors, proceed to the installation step.
		if (!$form->errorCount()) {

			// Put all the POST data into the session and proceed to the install step.
			ET::$session->store("install", $values);
			$this->redirect(URL("install/install"));

		}

	}

	$this->data("form", $form);
	$this->render("install/info");
}


/**
 * Now that all necessary checks have been made and data has been gathered, perform the installation.
 *
 * @return void
 */
public function install()
{
	// If we aren't supposed to be here, get out.
	if (!($info = ET::$session->get("install"))) $this->redirect(URL("install/info"));

	// Make sure the base URL has a trailing slash.
	if (substr($info["baseURL"], -1) != "/") $info["baseURL"] .= "/";

	// Prepare the $config variable with the installation settings.
	$config = array(
		"esoTalk.installed" => true,
		"esoTalk.version" => ESOTALK_VERSION,
		"esoTalk.database.host" => $info["mysqlHost"],
		"esoTalk.database.user" => $info["mysqlUser"],
		"esoTalk.database.password" => $info["mysqlPass"],
		"esoTalk.database.dbName" => $info["mysqlDB"],
		"esoTalk.database.prefix" => $info["tablePrefix"],
		"esoTalk.forumTitle" => $info["forumTitle"],
		"esoTalk.baseURL" => $info["baseURL"],
		"esoTalk.emailFrom" => "do_not_reply@{$_SERVER["HTTP_HOST"]}",
		"esoTalk.cookie.name" => preg_replace(array("/\s+/", "/[^\w]/"), array("_", ""), $info["forumTitle"]),
		"esoTalk.urls.friendly" => !empty($info["friendlyURLs"]),
		"esoTalk.urls.rewrite" => !empty($info["friendlyURLs"]) and function_exists("apache_get_modules") and in_array("mod_rewrite", apache_get_modules())
	);

	// Merge these new config settings into our current conifg variable.
	ET::$config = array_merge(ET::$config, $config);

	// Initialize the database with our MySQL details.
	ET::$database->init(C("esoTalk.database.host"), C("esoTalk.database.user"), C("esoTalk.database.password"), C("esoTalk.database.dbName"), C("esoTalk.database.prefix"), C("esoTalk.database.connectionOptions"));

	// Run the upgrade model's install function.
	try {
		ET::upgradeModel()->install($info);
	} catch (Exception $e) {
		$this->fatalError($e->getMessage());
	}

	// Write the $config variable to config.php.
	@unlink(PATH_CONFIG."/config.php");
	ET::writeConfig($config);

	// Write custom.css and index.html as empty files (if they're not already there.)
	if (!file_exists("../config/custom.css")) file_put_contents(PATH_CONFIG."/custom.css", "");
	if (!file_exists("../config/index.html")) file_put_contents(PATH_CONFIG."/index.html", "");

	// Write a .htaccess file if they are using friendly URLs (and mod_rewrite).
	if (C("esoTalk.urls.rewrite")) {
		file_put_contents(PATH_ROOT."/.htaccess", "# Generated by esoTalk
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>");
	}

	// Write a robots.txt file.
	file_put_contents(PATH_ROOT."/robots.txt", "User-agent: *
Crawl-delay: 10
Disallow: /search/
Disallow: /members/
Disallow: /user/
Disallow: /conversation/start/
Sitemap: ".C("esoTalk.baseURL")."sitemap.php");

	// Clear the session of install data.
	ET::$session->remove("install");

	// Re-initialize the session and log the administrator in.
	ET::$session = ETFactory::make("session");
	ET::$session->loginWithMemberId(1);

	// Redirect them to the administration page.
	$this->redirect(URL("admin"));
}


/**
 * Show a fatal error, providing the user with options to go back to the details form or try again.
 *
 * @param string $error The error that occurred.
 * @return void
 */
protected function fatalError($error)
{
	$this->data("error", $error);
	$this->render("install/error");
	exit;
}


/**
 * Perform warning checks (non-fatal errors).
 *
 * @return array An array of warnings that were found.
 */
protected function warningChecks()
{
	$errors = array();

	// We don't like register_globals!
	if (ini_get("register_globals")) $errors[] = T("message.registerGlobalsWarning");

	// Check for safe_mode.
	if (ini_get("safe_mode")) $errors[] = T("message.safeModeWarning");

	// Check for the gd extension.
	if (!extension_loaded("gd") and !extension_loaded("gd2")) $errors[] = T("message.gdNotEnabledWarning");

	return $errors;
}


/**
 * Perform error checks (fatal errors).
 *
 * @return array An array of errors that were found.
 */
protected function fatalChecks()
{
	$errors = array();

	// Make sure the installer is not locked.
	if ($this->controllerMethod != "finish" and C("esoTalk.installed")) $errors[] = T("message.esoTalkAlreadyInstalled");

	// Check the PHP version.
	if (!version_compare(PHP_VERSION, "5.0.0", ">=")) $errors[] = T("message.greaterPHPVersionRequired");

	// Check for the MySQL extension.
	if (!extension_loaded("mysql")) $errors[] = T("message.greaterMySQLVersionRequired");

	// Check file permissions.
	$fileErrors = array();
	$filesToCheck = array("", "uploads", "uploads/avatars", "plugins", "skins", "languages", "config", "cache");
	sort($filesToCheck);

	// Go through each file (directory)...
	foreach ($filesToCheck as $file) {

		// If it doesn't exist and we can't create it, or if it does exist but we can't write to it, add it as
		// an errorous file.
		if ((!file_exists(PATH_ROOT."/$file") and !@mkdir(PATH_ROOT."/$file")) or (!is_writable(PATH_ROOT."/$file") and !@chmod(PATH_ROOT."/$file", 0777))) {

			// If this directory name is empty (referring to the root directory), use the directory one level up.
			if (!$file) {
				$realPath = realpath($file);
				$fileErrors[] = substr($realPath, strrpos($realPath, "/") + 1)."/";
			}
			else $fileErrors[] = $file."/";

		}
	}
	if (count($fileErrors)) $errors[] = sprintf(T("message.installerFilesNotWritable"), implode("</strong>, <strong>", $fileErrors));

	return $errors;
}

}