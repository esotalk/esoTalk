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

	$this->defaultMenu->add("dashboard", "<a href='".URL("admin/dashboard")."'><i class='icon-dashboard'></i> ".T("Dashboard")."</a>");
	$this->defaultMenu->add("settings", "<a href='".URL("admin/settings")."'><i class='icon-cog'></i> ".T("Forum Settings")."</a>");
	$this->defaultMenu->add("appearance", "<a href='".URL("admin/appearance")."'><i class='icon-eye-open'></i> ".T("Appearance")."</a>");
	$this->defaultMenu->add("channels", "<a href='".URL("admin/channels")."'><i class='icon-tags'></i> ".T("Channels")."</a>");
	$this->defaultMenu->add("members", "<a href='".URL("members")."'><i class='icon-group'></i> ".T("Members")."</a>");
	$this->defaultMenu->add("plugins", "<a href='".URL("admin/plugins")."'><i class='icon-puzzle-piece'></i> ".T("Plugins")."</a>");

	$this->defaultMenu->highlight(ET::$controllerName);
	$this->menu->highlight(ET::$controllerName);

	if (C("esoTalk.registration.requireConfirmation") == "approval") {
		$count = ET::SQL()->select("COUNT(1)")->from("member")->where("confirmed", 0)->exec()->result();
		$this->menu->add("unapproved", "<a href='".URL("admin/unapproved")."'><i class='icon-lock'></i> ".T("Unapproved")." <span class='badge'>".$count."</span></a>");
	}

	if ($this->responseType === RESPONSE_TYPE_DEFAULT)
		$this->pushNavigation("admin", "administration", URL($this->selfURL));

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
