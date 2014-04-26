<?php
// Copyright 2014 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Bookmarks"] = array(
	"name" => "Bookmarks",
	"description" => "Allows users to bookmark conversations.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Bookmarks extends ETPlugin {

	// Setup: add a 'bookmarked' column to the member_conversation table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_conversation")
			->column("bookmarked", "bool", 0)
			->exec(false);

		return true;
	}

	// Add some default language definitions.
	public function init()
	{
		ET::define("gambit.bookmarked", "bookmarked");
		ET::define("label.bookmarked", "Bookmarked");

		ET::conversationModel();
		ETConversationModel::addLabel("bookmarked", "IF(s.bookmarked=1,1,0)", "icon-bookmark");
	}

	/**
	 * Add an event handler to the initialization of the conversation controller to add CSS and JavaScript
	 * resources.
	 *
	 * @return void
	 */
	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addJSFile($this->resource("bookmarks.js"));
	}

	public function handler_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("bookmarks.css"));
	}

	public function handler_conversationController_conversationIndexDefault($sender, $conversation, $controls, $replyForm, $replyControls)
	{
	    if (ET::$session->user) {
			$controls->add("bookmark", "<a href='".URL("conversation/bookmark/".$conversation["conversationId"]."/?token=".ET::$session->token."&return=".urlencode($sender->selfURL))."' id='control-bookmark'><i class='icon-bookmark'></i> <span>".T($conversation["bookmarked"] ? "Unbookmark" : "Bookmark")."</span></a>", 0);
		}
	}

	/**
	 * Toggle the muted flag of a conversation for the current user.
	 *
	 * @param int $conversationId The ID of the conversation.
	 * @return void
	 */
	public function action_conversationController_bookmark($controller, $conversationId = false)
	{
		if (!ET::$session->user or !$controller->validateToken()) return;

		// Get the conversation.
		if (!($conversation = $controller->getConversation($conversationId))) return;

		// Bookmark/unbookmark the conversation.
		$bookmarked = !$conversation["bookmarked"];
		$this->setBookmarked($conversation, ET::$session->userId, $bookmarked);

		$controller->json("bookmarked", $bookmarked);

		// Redirect back to the conversation.
		if ($controller->responseType === RESPONSE_TYPE_DEFAULT) {
			redirect(URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))));
		}

		// If it's an AJAX request, return the contents of the labels view.
		elseif ($controller->responseType === RESPONSE_TYPE_AJAX)
			$controller->json("labels", $controller->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));

		$controller->render();
	}

	/**
	 * Set a member's bookmarked flag for a conversation.
	 *
	 * @param array $conversation The conversation to set the flag on. The conversation array's labels
	 * 		and bookmarked attribute will be updated.
	 * @param int $memberId The member to set the flag for.
	 * @param bool $bookmarked Whether or not to set the conversation to bookmarked.
	 * @return void
	 */
	public function setBookmarked(&$conversation, $memberId, $bookmarked)
	{
		$bookmarked = (bool)$bookmarked;

		$model = ET::conversationModel();
		$model->setStatus($conversation["conversationId"], $memberId, array("bookmarked" => $bookmarked));

		$model->addOrRemoveLabel($conversation, "bookmarked", $bookmarked);
		$conversation["bookmarked"] = $bookmarked;
	}

	// Register the "bookmarked" gambit with the search model.
	public function handler_conversationsController_init($sender)
	{
		ET::searchModel(); // Load the search model so we can add this gambit.
		ETSearchModel::addGambit('return $term == strtolower(T("gambit.bookmarked"));', array($this, "gambitBookmarked"));
	}

	// Register a menu item for the "order by views" gambit.
	public function handler_conversationsController_constructGambitsMenu($sender, &$gambits)
	{
		addToArrayString($gambits["main"], T("gambit.bookmarked"), array("gambit-bookmarked", "icon-bookmark"));
	}

	/**
	 * The "bookmarked" gambit callback. Applies a filter to fetch only bookmarked conversations.
	 *
	 * @see ETSearchModel::gambitUnread for parameter descriptions.
	 */
	public static function gambitBookmarked(&$search, $term, $negate)
	{
		if (!ET::$session->user or $negate) return;

		$sql = ET::SQL()
			->select("DISTINCT conversationId")
			->from("member_conversation")
			->where("type='member'")
			->where("id=:memberId")
			->where("bookmarked=1")
			->bind(":memberId", ET::$session->userId);

		$search->addIDFilter($sql);
	}

}
