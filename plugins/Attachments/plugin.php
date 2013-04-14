<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Attachments"] = array(
	"name" => "Attachments",
	"description" => "Allows users to attach files to their posts.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Attachments extends ETPlugin {

	// Setup: add a follow column to the member_channel table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("attachment")
			// ->column("follow", "bool", 0)
			->exec(false);

		// Make the uploads/attachments folder, and put in an index.html to prevent directory listing

		return true;
	}

	public function __construct($rootDirectory)
	{
		parent::__construct($rootDirectory);

		ETFactory::register("attachmentModel", "AttachmentModel", dirname(__FILE__)."/AttachmentModel.class.php");
		ETFactory::registerController("attachment", "AttachmentController", dirname(__FILE__)."/AttachmentController.class.php");
	}

	public function init()
	{
		
	}

	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->getResource("fineuploader/fineuploader.css"));
		$sender->addCSSFile($this->getResource("attachments.css"));
		$sender->addJSFile($this->getResource("fineuploader/jquery.fineuploader.min.js"));
        $sender->addJSFile($this->getResource("attachments.js"));
	}

	public function handler_conversationController_renderReplyBox($sender, &$formatted, $conversation)
	{
		// Get "draft" attachments for this member/conversation.
		$sql = ET::SQL()
			->where("draftMemberId", ET::$session->userId)
			->where("draftConversationId", $conversation["conversationId"]);
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);

		$view = $sender->getViewContents("attachments/edit", array("attachments" => $attachments));
		$formatted["body"] = substr_replace($formatted["body"], $view, strpos($formatted["body"], "<div class='editButtons'>"), 0);
	}

	public function handler_conversationController_renderEditBox($sender, &$formatted, $post)
	{
		$view = $sender->getViewContents("attachments/edit", array("attachments" => $post["attachments"]));
		$formatted["body"] = substr_replace($formatted["body"], $view, strpos($formatted["body"], "<div class='editButtons'>"), 0);
	}

	// Hook onto PostModel::getPosts and get attachment data for all posts being displayed and "attach" it to each post array
	public function handler_postModel_getPostsAfter($sender, &$posts)
	{
		// Loop through the array of posts and get post IDs.
		$postIds = array();
		foreach ($posts as &$post) {
			$postIds[] = $post["postId"];
			$post["attachments"] = array();
		}

		if (!$postIds) return;

		// Fetch all the attachments for these posts.
		$sql = ET::SQL()
			->where("postId IN (:postIds)")
			->bind(":postIds", $postIds);
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);

		// Now loop through the attachments and add them to appropriate posts.
		foreach ($attachments as $attachment) {
			$key = array_search($attachment["postId"], $postIds);
			$posts[$key]["attachments"][] = $attachment;
		}
	}

	public function handler_conversationController_editPostAfter($sender, &$post)
	{
		// Fetch all the attachments for this posts.
		$sql = ET::SQL()->where("postId", $post["postId"]);
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);

		// Now loop through the attachments and add them to appropriate posts.
		$post["attachments"] = array();
		foreach ($attachments as $attachment) {
			$post["attachments"][] = $attachment;
		}
	}

	// Hook onto ConversationController::formatPostForTemplate and add the attachment/list view to the bottom of each post
	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		if ($post["deleteMemberId"] or empty($post["attachments"])) return;

		$view = $sender->getViewContents("attachments/list", array("attachments" => $post["attachments"]));

		// Add this before the "likes" plugin. Bit hacky, but there's no way to prioritize event handlers in esoTalk :(
		$pos = strpos($formatted["body"], "<p class='likes");
		if (!$pos) $pos = strlen($formatted["body"]);
		$formatted["body"] = substr_replace($formatted["body"], $view, $pos, 0);
	}

	// Hook onto ConversationModel::addReply and commit attachments from the session to the database+filesystem, remove draft ones
	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		// Go through the session and find all attachments attached to the "reply" post.
		$attachments = array();
		$session = (array)ET::$session->get("attachments");
		foreach ($session as $id => $attachment) {
			if ($attachment["postId"] == "c".$conversation["conversationId"]) {
				$attachments[$id] = $attachment;
				unset($session[$id]);
			}
		}
		ET::$session->store("attachments", $session);

		if (!empty($attachments)) {

			$inserts = array();
			$fields = array("attachmentId", "postId", "filename", "secret");
			foreach ($attachments as $id => $attachment)
				$inserts[] = array($id, $postId, $attachment["name"], $attachment["secret"]);

			ET::SQL()
				->insert("attachment")
				->setMultiple($fields, $inserts)
				->exec();

		}

		// update draft attachment entries from the database
		ET::SQL()
			->update("attachment")
			->set("postId", $postId)
			->set("draftMemberId", null)
			->set("draftConversationId", null)
			->where("draftMemberId", ET::$session->userId)
			->where("draftConversationId", $conversation["conversationId"])
			->exec();
	}

	// Hook onto ConversationModel::create and commit attachments from the session to the database+filesystem, remove draft ones
	public function handler_conversationModel_createAfter($sender, $conversation, $postId, $content)
	{
		// Go through the session and find all attachments attached to the "reply" post.
		$attachments = array();
		$session = (array)ET::$session->get("attachments");
		foreach ($session as $id => $attachment) {
			if ($attachment["postId"] == "c0") {
				$attachments[$id] = $attachment;
				unset($session[$id]);
			}
		}
		ET::$session->store("attachments", $session);

		if (!empty($attachments)) {

			$inserts = array();
			$fields = array("attachmentId", "postId", "filename", "secret");
			foreach ($attachments as $id => $attachment)
				$inserts[] = array($id, $postId, $attachment["name"], $attachment["secret"]);

			ET::SQL()
				->insert("attachment")
				->setMultiple($fields, $inserts)
				->exec();

		}
	}

	// Hook onto ConversationModel::setDraft and commit attachments from the session to the database+filesystem
	public function handler_conversationModel_setDraftAfter($sender, $conversation, $memberId, $draft)
	{
		if ($draft === null) {

			ET::SQL()
				->delete()
				->from("attachment")
				->where("draftMemberId", ET::$session->userId)
				->where("draftConversationId", $conversation["conversationId"])
				->exec();

		}
		else {

			// Go through the session and find all attachments attached to the "reply" post.
			$attachments = array();
			$session = (array)ET::$session->get("attachments");
			foreach ($session as $id => $attachment) {
				if ($attachment["postId"] == (ET::$controller->controllerMethod == "start" ? "c0" : "c".$conversation["conversationId"])) {
					$attachments[$id] = $attachment;
					unset($session[$id]);
				}
			}
			ET::$session->store("attachments", $session);

			if (!empty($attachments)) {

				$inserts = array();
				$fields = array("attachmentId", "draftMemberId", "draftConversationId", "filename", "secret");
				foreach ($attachments as $id => $attachment)
					$inserts[] = array($id, $memberId, $conversation["conversationId"], $attachment["name"], $attachment["secret"]);

				ET::SQL()
					->insert("attachment")
					->setMultiple($fields, $inserts)
					->exec();

			}

		}
	}

	// Hook onto PostModel::editPost and commit attachments from the session to the database+filesystem
	public function handler_postModel_editPostAfter($sender, $post)
	{
		// Go through the session and find all attachments attached to this post.
		$attachments = array();
		$session = (array)ET::$session->get("attachments");
		foreach ($session as $id => $attachment) {
			if ($attachment["postId"] == "p".$post["postId"]) {
				$attachments[$id] = $attachment;
				unset($session[$id]);
			}
		}
		ET::$session->store("attachments", $session);

		if (!empty($attachments)) {

			$inserts = array();
			$fields = array("attachmentId", "postId", "filename", "secret");
			foreach ($attachments as $id => $attachment)
				$inserts[] = array($id, $post["postId"], $attachment["name"], $attachment["secret"]);

			ET::SQL()
				->insert("attachment")
				->setMultiple($fields, $inserts)
				->exec();

		}
	}


	/**
	 * Construct and process the settings form.
	 * 
	 * @param ETController $sender The page controller.
	 * @return string The path to the settings view to render.
	 */
	public function settings($sender)
	{
		// Set up the settings form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/plugins");
		$form->setValue("allowedFileTypes", implode(" ", (array)C("plugin.Attachments.allowedFileTypes")));
		$form->setValue("maxFileSize", C("plugin.Attachments.maxFileSize"));

		// If the form was submitted...
		if ($form->validPostBack("save")) {

			// Construct an array of config options to write.
			$config = array();
			$fileTypes = $form->getValue("allowedFileTypes");
			$config["plugin.Attachments.allowedFileTypes"] = $fileTypes ? explode(" ", $fileTypes) : "";
			$config["plugin.Attachments.maxFileSize"] = $form->getValue("maxFileSize");

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("attachmentsSettingsForm", $form);
		return $this->getView("settings");
	}
}
