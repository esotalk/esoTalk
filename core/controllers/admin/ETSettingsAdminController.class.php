<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller handles the settings section of the admin CP. It sets up and processes the settings form,
 * including uploading a header image.
 *
 * @package esoTalk
 */
class ETSettingsAdminController extends ETAdminController {


/**
 * Show and process the settings form.
 *
 * @return void
 */
public function index()
{
	// Make an array of languages for the default forum language select.
	$languages = array();
	foreach (ET::getLanguages() as $v) {
		$languages[$v] = ET::$languageInfo[$v]["name"];
	}

	// Get a list of member groups.
	$groups = ET::groupModel()->getAll();

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/settings");

	// Set the default values for the forum inputs.
	$form->setValue("forumTitle", C("esoTalk.forumTitle"));
	$form->setValue("language", C("esoTalk.language"));
	$form->setValue("forumHeader", C("esoTalk.forumLogo") ? "image" : "title");
	$form->setValue("defaultRoute", C("esoTalk.defaultRoute"));
	$form->setValue("forumVisibleToGuests", C("esoTalk.visibleToGuests"));
	$form->setValue("memberListVisibleToGuests", C("esoTalk.members.visibleToGuests"));
	$form->setValue("registrationOpen", C("esoTalk.registration.open"));
	$form->setValue("requireConfirmation", C("esoTalk.registration.requireConfirmation"));

	$c = C("esoTalk.conversation.editPostTimeLimit");
	if ($c === -1) $form->setValue("editPostMode", "forever");
	elseif ($c === "reply") $form->setValue("editPostMode", "reply");
	else {
		$form->setValue("editPostMode", "custom");
		$form->setValue("editPostTimeLimit", $c);
	}
	

	// If the save button was clicked...
	if ($form->validPostBack("save")) {

		// Construct an array of config options to write.
		$config = array(
			"esoTalk.forumTitle" => $form->getValue("forumTitle"),
			"esoTalk.language" => $form->getValue("language"),
			"esoTalk.forumLogo" => $form->getValue("forumHeader") == "image" ? $this->uploadHeaderImage($form) : false,
			"esoTalk.defaultRoute" => $form->getValue("defaultRoute"),
			"esoTalk.visibleToGuests" => $form->getValue("forumVisibleToGuests"),
			"esoTalk.members.visibleToGuests" => $form->getValue("forumVisibleToGuests") and $form->getValue("memberListVisibleToGuests"),
			"esoTalk.registration.open" => $form->getValue("registrationOpen"),
			"esoTalk.registration.requireConfirmation" => in_array($v = $form->getValue("requireConfirmation"), array(false, "email", "admin")) ? $v : false,
		);

		switch ($form->getValue("editPostMode")) {
			case "forever": $config["esoTalk.conversation.editPostTimeLimit"] = -1; break;
			case "reply": $config["esoTalk.conversation.editPostTimeLimit"] = "reply"; break;
			case "custom": $config["esoTalk.conversation.editPostTimeLimit"] = (int)$form->getValue("editPostTimeLimit"); break;
		}

		// Make sure a forum title is present.
		if (!strlen($config["esoTalk.forumTitle"])) $form->error("forumTitle", T("message.empty"));

		if (!$form->errorCount()) {
			ET::writeConfig($config);
			$this->message(T("message.changesSaved"), "success");
			$this->redirect(URL("admin/settings"));
		}

	}

	$this->data("form", $form);
	$this->data("languages", $languages);
	$this->data("groups", $groups);
	$this->title = T("Forum Settings");
	$this->render("admin/settings");
}


/**
 * Upload a header image.
 *
 * @return void
 */
protected function uploadHeaderImage($form)
{
	$uploader = ET::uploader();

	try {

		// Validate and get the uploaded file from this field.
		$file = $uploader->getUploadedFile("forumHeaderImage");

		// Save it as an image, restricting it to a maximum size.
		$logo = $uploader->saveAsImage($file, PATH_UPLOADS."/logo", 500, 40, "max");
		$logo = str_replace(PATH_UPLOADS, "uploads", $logo);

		// Delete the old logo (if we didn't just overwrite it.)
		if ($logo != C("esoTalk.forumLogo")) @unlink(C("esoTalk.forumLogo"));

		return $logo;

	} catch (Exception $e) {

		// If something went wrong up there, add the error message to the form.
		$form->error("forumHeaderImage", $e->getMessage());

	}
}

}
