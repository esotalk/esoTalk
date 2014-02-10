<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Profiles"] = array(
	"name" => "Profiles",
	"description" => "Adds some fields to user profiles, including an 'About Me' section and a 'Location' field.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Profiles extends ETPlugin {

	public function handler_memberController_initProfile($sender, $member, $panes, $controls, $actions)
	{
		$panes->add("about", "<a href='".URL(memberURL($member["memberId"], $member["username"], "about"))."'>".T("About")."</a>", 0);
	}

	public function memberController_index($sender, $member = "")
	{
		$this->memberController_about($sender, $member);
	}

	public function memberController_about($sender, $member = "")
	{
		if (!($member = $sender->profile($member, "about"))) return;

		$about = @$member["preferences"]["about"];
		$about = ET::formatter()->init($about)->format()->get();
		$sender->data("about", $about);

		$sender->data("location", @$member["preferences"]["location"]);

		$sender->renderProfile($this->getView("about"));
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		// Hide the location on deleted posts and from guests.
		if ($post["deleteMemberId"] or empty($post["preferences"]["location"]) or (!C("esoTalk.members.visibleToGuests") and !ET::$session->user)) return;

		$formatted["info"][] = "<span class='location'>".sanitizeHTML($post["preferences"]["location"])."</span>";
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		// Hide the location from guests.
		if (C("esoTalk.members.visibleToGuests") or ET::$session->user) {
			$form->addSection("location", T("Location"));
			$form->setValue("location", ET::$session->preference("location"));
			$form->addField("location", "location", array(__CLASS__, "fieldLocation"), array($sender, "savePreference"));
		}

		$form->addSection("about", T("About"));
		$form->setValue("about", ET::$session->preference("about"));
		$form->addField("about", "about", array(__CLASS__, "fieldAbout"), array($sender, "savePreference"));
	}

	public static function fieldAbout($form)
	{
		return $form->input("about", "textarea", array("style" => "width:500px; height:150px"))."<br><small>".T("Write something about yourself.")."</small>";
	}

	public static function fieldLocation($form)
	{
		return $form->input("location", "text");
	}

}
