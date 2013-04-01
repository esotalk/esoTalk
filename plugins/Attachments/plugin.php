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

		ETFactory::registerController("attachment", "AttachmentController", dirname(__FILE__)."/AttachmentController.class.php");
	}

	public function init()
	{
		
	}

	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->getResource("fineuploader/fineuploader.css"));
		$sender->addJSFile($this->getResource("fineuploader/js/header.js"));
		$sender->addJSFile($this->getResource("fineuploader/js/header.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/util.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/button.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/ajax.requester.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/deletefile.ajax.requester.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/handler.base.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/window.receive.message.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/handler.form.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/handler.xhr.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/uploader.basic.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/dnd.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/uploader.js"));
        $sender->addJSFile($this->getResource("fineuploader/js/jquery-plugin.js"));
        $sender->addJSFile($this->getResource("attachments.js"));
	}

	public function handler_conversationController_renderReplyBox($sender, &$post, $conversation)
	{
		$post["body"] .= '<div id="fine-uploader"></div>';
	}

	public function handler_conversationController_renderEditBox($sender, &$post)
	{
		$post["body"] .= '<div id="fine-uploader"></div>';
	}

	// Hook onto PostModel::getPosts and get attachment data for all posts being displayed and "attach" it to each post array

	// Hook onto ConversationController::formatPostForTemplate and add the attachment/list view to the bottom of each post

	// Hook onto ConversationModel::addReply and commit attachments from the session to the database+filesystem, remove draft ones

	// Hook onto ConversationModel::setDraft and commit attachments from the session to the database+filesystem

	// Hook onto PostModel::editPost and commit attachments from the session to the database+filesystem



}
