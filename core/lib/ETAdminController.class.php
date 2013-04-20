<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * A class that defines a controller for a section in the administration panel. Constructs a standard menu
 * to be displayed on the admin wrapper view.
 *
 * @package esoTalk
 */
class ETAdminController extends ETController {


/**
 * Initialize the admin controller. Construct a menu to show all admin panels.
 *
 * @return void
 */
public function init()
{
	// If the user isn't an administrator, kick them out.
	if (!ET::$session->isAdmin()) $this->redirect(URL("user/login?return=".urlencode($this->selfURL)));

	parent::init();

	// Construct the menus for the side bar.
	$this->defaultMenu = ETFactory::make("menu");
	$this->menu = ETFactory::make("menu");

	$this->defaultMenu->add("dashboard", "<a href='".URL("admin/dashboard")."'>".T("Dashboard")."</a>");
	$this->defaultMenu->add("settings", "<a href='".URL("admin/settings")."'>".T("Forum Settings")."</a>");
	$this->defaultMenu->add("appearance", "<a href='".URL("admin/appearance")."'>".T("Appearance")."</a>");
	$this->defaultMenu->add("channels", "<a href='".URL("admin/channels")."'>".T("Channels")."</a>");
	$this->defaultMenu->add("plugins", "<a href='".URL("admin/plugins")."'>".T("Plugins")."</a>");

	$this->defaultMenu->highlight(ET::$controllerName);
	$this->menu->highlight(ET::$controllerName);

	$this->addJSFile("core/js/admin.js");
	$this->addCSSFile("core/skin/admin.css");
}


/**
 * Rather than just rendering the view passed to this function, we need to render the admin "wrapper" view
 * and include the specified view within that (except on AJAX/view response types.)
 *
 * @param string $view The name of the view to render.
 * @return void
 */
public function render($view = "")
{
	$this->data("menu", $this->menu);
	$this->data("defaultMenu", $this->defaultMenu);

	if (!in_array($this->responseType, array(RESPONSE_TYPE_VIEW, RESPONSE_TYPE_AJAX))) {
		$this->data("view", $view);
		parent::render("admin/index");
	}

	else parent::render($view);
}

}