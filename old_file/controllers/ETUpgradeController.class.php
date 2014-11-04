<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The upgrade controller runs the upgrade model's upgrade method, updates the config file with the latest
 * version, and redirects to the index.
 *
 * @package esoTalk
 */
class ETUpgradeController extends ETController {


/**
 * Initialize the upgrade controller.
 *
 * @return void
 */
public function init()
{
	// Set the master view to the message master view.
	$this->masterView = "message.master";
	$this->title = T("Upgrade esoTalk");

	$this->trigger("init");
}


/**
 * Perform the upgrade process.
 *
 * @return void
 */
public function index()
{
	try {

		// Run the upgrade process.
		ET::upgradeModel()->upgrade(C("esoTalk.version"));

		// Update the version in the config file.
		ET::writeConfig(array("esoTalk.version" => ESOTALK_VERSION));

		// Show a success message and redirect.
		$this->message(T("message.upgradeSuccessful"), "success");
		$this->redirect(URL(""));

	} catch (Exception $e) {
		$this->fatalError($e->getMessage());
	}
}


/**
 * Show a fatal error, providing the user with the option to try again.
 *
 * @param string $error The error that occurred.
 * @return void
 */
protected function fatalError($error)
{
	$this->data("error", $error);
	$this->render("install/upgradeError");
	exit;
}

}