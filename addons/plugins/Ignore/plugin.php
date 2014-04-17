<?php
// Copyright 2014 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Ignore"] = array(
	"name" => "Ignore",
	"description" => "Allows users to ignore other users and hide their posts.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Ignore extends ETPlugin {

	// Setup: add a follow column to the member_channel table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_member")
			->column("ignored", "bool", 0)
			->exec(false);

		return true;
	}

	public function handler_memberController_initProfile($sender, &$member, $panes, $controls, $actions)
	{
		if (!ET::$session->user or $member["memberId"] == ET::$session->userId) return;

		$controls->separator(0);
	 	$controls->add("ignore", "<a href='".URL("member/ignore/".$member["memberId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL))."' id='ignoreLink'><i class='icon-eye-close'></i>".T($member["ignored"] ? "Unignore member" : "Ignore member")."</a>", 0);
	}

	// Add an action to toggle the ignoring status of a member.
	public function memberController_ignore($controller, $memberId = "")
	{
		if (!ET::$session->user or !$controller->validateToken()) return;

		// Make sure the member that we're trying to ignore exists.
		if (!ET::SQL()->select("memberId")->from("member")->where("memberId", (int)$memberId)->exec()->numRows()) return;

		// Work out if we're already ignored or not, and switch to the opposite of that.
		$ignored = !ET::SQL()
			->select("ignored")
			->from("member_member")
			->where("memberId1", ET::$session->userId)
			->where("memberId2", (int)$memberId)
			->exec()
			->result();

		// Write to the database.
		ET::memberModel()->setStatus(ET::$session->userId, $memberId, array("ignored" => $ignored));

		// Redirect back to the member profile.
		$controller->redirect(URL("member/".$memberId));
	}

	protected function getIgnored()
	{
		// Get a list of all the members that the user has ignored.
		$result = ET::SQL()
			->select("memberId2")
			->from("member_member")
			->where("memberId1", ET::$session->userId)
			->where("ignored", 1)
			->exec();
		$mutedIds = array_keys($result->allRows("memberId2"));

		return $mutedIds;
	}

	public function handler_postModel_getPostsAfter($sender, &$posts)
	{
		$ignoredIds = $this->getIgnored();

		foreach ($posts as &$post) {
			if (in_array($post["memberId"], $ignoredIds)) $post["ignored"] = true;
		}
	}

	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->getResource("ignore.css"));
		$sender->addJSFile($this->getResource("ignore.js"));
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		if ($post["deleteMemberId"]) return;

		if (!empty($post["ignored"])) {
			$formatted["class"][] = "ignored";
			$formatted["info"][] = "<span class='ignoredInfo'><i class='icon-eye-close'></i> Post from ".memberLink($post["memberId"], $post["username"])." hidden. <a href='#' class='ignoredShow'>Show</a></span>";
		}
	}

	public function handler_searchModel_afterGetResults($sender, &$results)
	{
		$ignoredIds = $this->getIgnored();

		foreach ($results as &$result) {
			if (in_array($result["lastPostMemberId"], $ignoredIds) and $result["unread"] == 1) $result["unread"] = 0; 
		}
	}

}
