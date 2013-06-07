<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Gravatar"] = array(
	"name" => "Gravatar",
	"description" => "Allows users to choose to use their Gravatar.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Gravatar extends ETPlugin {

	function init()
	{
		// Override the avatar function.

		/**
		 * Return an image tag containing a member's avatar.
		 *
		 * @param array $member An array of the member's details. (email is required in this implementation.)
		 * @param string $avatarFormat The format of the member's avatar (as stored in the database - jpg|gif|png.)
		 * @param string $className CSS class names to apply to the avatar.
		 */
		function avatar($member = array(), $className = "")
		{
			$esoTalkDefault = getResource("core/skin/avatar.png", true);
			if (empty($member["email"])) $url = $esoTalkDefault;
			else {
			
				$default = C("plugin.Gravatar.default");
				if (!$default or empty($member)) $default = $esoTalkDefault;

				$protocol = C("esoTalk.https") ? "https" : "http";
				$url = "$protocol://www.gravatar.com/avatar/".md5(strtolower(trim($member["email"])))."?d=".urlencode($default)."&s=64";

			}

			return "<img src='$url' alt='' class='avatar $className'/>";
		}
	}

	// Change the avatar field on the settings page.
	function handler_settingsController_initGeneral($sender, $form)
	{
		$form->removeField("avatar", "avatar");
		$form->addField("avatar", "avatar", array($this, "fieldAvatar"));
	}

	function fieldAvatar($form)
	{
		return T("Change your avatar on <a href='http://gravatar.com' target='_blank'>Gravatar.com</a>.");
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
		$form->action = URL("admin/plugins");
		$form->setValue("default", C("plugin.Gravatar.default"));

		// If the form was submitted...
		if ($form->validPostBack("save")) {

			// Construct an array of config options to write.
			$config = array();
			$config["plugin.Gravatar.default"] = $form->getValue("default");

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("gravatarSettingsForm", $form);
		return $this->getView("settings");
	}

}
