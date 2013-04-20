<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["AboutMe"] = array(
	"name" => "About Me",
	"description" => "Adds a simple 'About Me' section to user profiles.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_AboutMe extends ETPlugin {

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
		$sender->renderProfile($this->getView("about"));
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		$form->addSection("about", T("About"));

		$form->setValue("about", ET::$session->preference("about"));
		$form->addField("about", "about", array(__CLASS__, "fieldAbout"), array($sender, "savePreference"));
	}

	public static function fieldAbout($form)
	{
		return $form->input("about", "textarea", array("style" => "width:500px; height:150px"))."<br><small>Write something about yourself!</small>";
	}

}
