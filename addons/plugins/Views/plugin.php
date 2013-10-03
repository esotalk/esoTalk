<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Views"] = array(
	"name" => "Views",
	"description" => "Counts the number of views of each conversation.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Views extends ETPlugin {

	// Setup: add a 'views' column to the conversations table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("conversation")
			->column("views", "int", 0)
			->key("views")
			->exec(false);

		return true;
	}

	// Add some default language definitions.
	public function init()
	{
		ET::define("gambit.order by views", "order by views");
	}

	// When we load the conversation index, increase the conversation's view count.
	public function handler_conversationController_conversationIndexDefault($sender, &$conversation)
	{
		$sender->addCSSFile($this->getResource("views.css"));

		if ($conversation["startMemberId"] == ET::$session->userId) return;

		$conversation["views"]++;

		ET::SQL()
			->update("conversation")
			->set("views", "views + 1", false)
			->where("conversationId", $conversation["conversationId"])
			->exec();
	}

	// Display the conversation's view count above the scrubber.
	public function handler_conversationController_renderScrubberBefore($sender, $data)
	{
		echo "<div class='conversationViews'><i class='icon-eye-open'></i> ".Ts("%s view", "%s views", $data["conversation"]["views"])."</div>";
	}

	// Register the "order by views" gambit with the search model.
	public function handler_conversationsController_init($sender)
	{
		ET::searchModel(); // Load the search model so we can add this gambit.
		ETSearchModel::addGambit('return $term == strtolower(T("gambit.order by views"));', array($this, "gambitOrderByViews"));
	}

	// Register a menu item for the "order by views" gambit.
	public function handler_conversationsController_constructGambitsMenu($sender, &$gambits)
	{
		addToArrayString($gambits["replies"], T("gambit.order by views"), array("gambit-orderByViews", "icon-eye-open"));
	}

	public static function gambitOrderByViews(&$search, $term, $negate)
	{
		$search->orderBy("c.views ".($negate ? "ASC" : "DESC"));
		$search->sql->useIndex("conversation_views");
	}

}
