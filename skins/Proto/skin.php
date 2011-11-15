<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Proto skin file.
 * 
 * @package esoTalk
 */

ET::$skinInfo["Proto"] = array(
	"name" => "Proto",
	"description" => "The default esoTalk skin.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETSkin_Proto extends ETSkin {


/**
 * Initialize the skin.
 * 
 * @param ETController $sender The page controller.
 * @return void
 */
public function handler_init($sender)
{
	$sender->addCSSFile("skins/base/base.css", true);
	$sender->addCSSFile($this->getResource("styles.css"), true);

	// If we're viewing from a mobile browser, add the mobile CSS and change the master view.
	if (isMobileBrowser()) {
		$sender->addCSSFile($this->getResource("mobile.css"), true);
		$sender->masterView = "mobile.master";
		$sender->addToHead("<meta name='viewport' content='width=device-width; initial-scale=1.0; maximum-scale=1.0;'>");
	}

	// If custom colors have been set in this skin's settings, add some CSS to the page.
	$styles = array();

	// If a custom header color has been set...
	if ($c = C("skin.Proto.headerColor")) {
		$styles[] = "#hdr {background-color:$c}";

		// If the header color is in the top half of the lightness spectrum, add the "lightHdr" class to the body.
		$rgb = colorUnpack($c, true);
		$hsl = rgb2hsl($rgb);
		if ($hsl[2] >= 0.5) $sender->bodyClass .= " lightHdr";
	}

	// If a custom body color has been set...
	if ($c = C("skin.Proto.bodyColor")) {
		$styles[] = "body, .scrubberMore {background-color:$c !important}";

		// If the body color is in the bottom half of the lightness spectrum, add the "darkBody" class to the body.
		$rgb = colorUnpack($c, true);
		$hsl = rgb2hsl($rgb);
		if ($hsl[2] < 0.5) $sender->bodyClass .= " darkBody";

		// Slightly darken the body color and set it as the border color for the body content area.
		$hsl[2] = max(0, $hsl[2] - 0.1);
		$hsl[1] = min($hsl[1], 0.5);
		$b = colorPack(hsl2rgb($hsl), true);
		$styles[] = "#body-content {border-color:$b}";
	}

	// If a custom body background image has been set...
	if ($img = C("skin.Proto.bodyImage"))
		$styles[] = "body {background-image:url(".getWebPath($img)."); background-position:top center; background-attachment:fixed}";
	
	// Do we want this background image to not repeat?
	if ($img and C("skin.Proto.noRepeat"))
		$styles[] = "body {background-repeat:no-repeat}";

	// If we have any custom styles at all, add them to the page head.
	if (count($styles)) $sender->addToHead("<style type='text/css'>\n".implode("\n", $styles)."\n</style>");
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
	$form->setValue("headerColor", C("skin.Proto.headerColor"));
	$form->setValue("bodyColor", C("skin.Proto.bodyColor"));
	$form->setValue("noRepeat", (bool)C("skin.Proto.noRepeat"));
	$form->setValue("bodyImage", (bool)C("skin.Proto.bodyImage"));

	// If the form was submitted...
	if ($form->validPostBack("save")) {

		// Construct an array of config options to write.
		$config = array();
		$config["skin.Proto.headerColor"] = $form->getValue("headerColor");
		$config["skin.Proto.bodyColor"] = $form->getValue("bodyColor");

		// Upload a body bg image if necessary.
		if ($form->getValue("bodyImage") and !empty($_FILES["bodyImageFile"]["tmp_name"])) $config["skin.Proto.bodyImage"] = $this->uploadBackgroundImage($form);
		elseif (!$form->getValue("bodyImage")) $config["skin.Proto.bodyImage"] = false;
		$config["skin.Proto.noRepeat"] = (bool)$form->getValue("noRepeat");

		if (!$form->errorCount()) {

			// Write the config file.
			ET::writeConfig($config);

			$sender->message(T("message.changesSaved"), "success");
			$sender->redirect(URL("admin/appearance"));

		}
	}

	$sender->data("skinSettingsForm", $form);
	$sender->addCSSFile("js/lib/farbtastic/farbtastic.css");
	$sender->addJSFile("js/lib/farbtastic/farbtastic.js");
	return $this->getView("settings");
}


/**
 * Upload a background image.
 * 
 * @return void
 */
protected function uploadBackgroundImage($form)
{
	$uploader = ET::uploader();

	try {

		// Validate and get the uploaded file from this field.
		$file = $uploader->getUploadedFile("bodyImageFile");

		// Save it as an image, restricting it to a maximum size.
		$bg = $uploader->saveAsImage($file, PATH_UPLOADS."/bg", 1, 1, "min");
		$bg = str_replace(PATH_UPLOADS, "uploads", $bg);

		// Delete the old background image (if we didn't just overwrite it.)
		if ($bg != C("skin.Proto.bodyImage")) @unlink(C("skin.Proto.bodyImage"));

		return $bg;

	} catch (Exception $e) {

		// If something went wrong up there, add the error message to the form.
		$form->error("bodyImageFile", $e->getMessage());

	}
}

}

?>