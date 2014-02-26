<?php
// Copyright 2014 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["UserSince"] = array(
	"name" => "User Since",
	"description" => "Shows the year that the user joined the forum on their posts.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_UserSince extends ETPlugin {

	public function handler_postModel_getPostsBefore($sender, $sql)
	{
		$sql->select("m.joinTime", "joinTime");
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		if ($post["deleteMemberId"]) return;

		if (date("Y", $post["joinTime"]) < date("Y"))
			$formatted["info"][] = "<span class='usersince'>".sprintf(T("User since %s"), date("Y", $post["joinTime"]))."</span>";
	}

}
