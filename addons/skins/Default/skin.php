<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Default skin file.
 * 
 * @package esoTalk
 */

ET::$skinInfo["Default"] = array(
	"name" => "Default",
	"description" => "The default esoTalk skin.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETSkin_Default extends ETSkin {


/**
 * Initialize the skin.
 * 
 * @param ETController $sender The page controller.
 * @return void
 */
public function handler_init($sender)
{
	$sender->addCSSFile((C("esoTalk.https") ? "https" : "http")."://fonts.googleapis.com/css?family=Open+Sans:400,600");
	$sender->addCSSFile("core/skin/base.css", true);
	$sender->addCSSFile("core/skin/font-awesome.css", true);
	$sender->addCSSFile($this->resource("styles.css"), true);

	// If we're viewing from a mobile browser, add the mobile CSS and change the master view.
	if ($isMobile = isMobileBrowser()) {
		$sender->addCSSFile($this->resource("mobile.css"), true);
		$sender->masterView = "mobile.master";
		$sender->addToHead("<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0'>");
	}

	$sender->addCSSFile("config/colors.css", true);

	if (!C("skin.Default.primaryColor")) $this->writeColors("#364159");
}


/**
 * Write the skin's color configuration and CSS.
 * 
 * @param string $primary The primary color.
 * @return void
 */
protected function writeColors($primary)
{
	ET::writeConfig(array("skin.Default.primaryColor" => $primary));

	$rgb = colorUnpack($primary, true);
	$hsl = rgb2hsl($rgb);
	$hsl[1] = min(0.4, $hsl[1]);
	$hsl[2] = min(0.4, $hsl[2]);

	$primary = colorPack(hsl2rgb($hsl), true);

	$hsl[1] = 0;
	$secondary = colorPack(hsl2rgb(array(2 => 0.6) + $hsl), true);
	$tertiary = colorPack(hsl2rgb(array(2 => 0.9) + $hsl), true);

	$css = file_get_contents($this->resource("colors.css"));
	$css = str_replace(array("{primary}", "{secondary}", "{tertiary}"), array($primary, $secondary, $tertiary), $css);
	file_put_contents(PATH_CONFIG."/colors.css", $css);
}


/**
 * Construct and process the settings form for this skin, and return the path to the view that should be 
 * rendered.
 * 
 * @param ETController $sender The page controller.
 * @return string The path to the settings view to render.
 */
public function settings($sender)
{
	// Set up the settings form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/appearance");
	$form->setValue("primaryColor", C("skin.Default.primaryColor"));

	// If the form was submitted...
	if ($form->validPostBack("save")) {
		$this->writeColors($form->getValue("primaryColor"));

		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/appearance"));
	}

	$sender->data("skinSettingsForm", $form);
	$sender->addCSSFile("core/js/lib/farbtastic/farbtastic.css");
	$sender->addJSFile("core/js/lib/farbtastic/farbtastic.js");
	return $this->view("settings");
}


}
