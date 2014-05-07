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
	"license" => "GPLv2",
	"priority" => 0
);

class ETPlugin_Attachments extends ETPlugin {

	// Setup: create the attachments table in the database and set up the filesystem for attachment storage.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("attachment")
			->column("attachmentId", "varchar(13)", false)
			->column("filename", "varchar(255)", false)
			->column("secret", "varchar(13)", false)
			->column("postId", "int(11) unsigned")
			->column("draftMemberId", "int(11) unsigned")
			->column("draftConversationId", "int(11) unsigned")
			->key("attachmentId", "primary")
			->exec(false);

		// Make the uploads/attachments folder, and put in an index.html to prevent directory listing
		if ((!file_exists(PATH_UPLOADS."/attachments") and !@mkdir(PATH_UPLOADS."/attachments"))
			or (!is_writable(PATH_UPLOADS."/attachments") and !@chmod(PATH_UPLOADS."/attachments", 0777)))
			return "The uploads/attachments directory does not exist or is not writeable.";

		file_put_contents(PATH_UPLOADS."/attachments/index.html", "");

		return true;
	}

	// Register the attachment model/controller.
	public function __construct($rootDirectory)
	{
		parent::__construct($rootDirectory);

		ETFactory::register("attachmentModel", "AttachmentModel", dirname(__FILE__)."/AttachmentModel.class.php");
		ETFactory::registerController("attachment", "AttachmentController", dirname(__FILE__)."/AttachmentController.class.php");
	}

	// Define some language stuff.
	public function init()
	{
		ET::define("message.attachmentNotFound", "For some reason this attachment cannot be viewed. It may not exist, or you may not have permission to view it.");

		/**
		 * Format an attachment to be outputted on the page, either in the attachment list
		 * at the bottom of the post or embedded inside the post.
		 *
		 * @param array $attachment The attachment details.
		 * @param bool $expanded Whether or not the attachment should be displayed in its
		 * 		full form (i.e. whether or not the attachment is embedded in the post.)
		 * @return string The HTML to output.
		 */
		function formatAttachment($attachment, $expanded = false)
		{
			$extension = pathinfo($attachment["filename"], PATHINFO_EXTENSION);
			$url = URL("attachment/".$attachment["attachmentId"]."_".$attachment["filename"]);
			$filename = sanitizeHTML($attachment["filename"]);

			// For images, either show them directly or show a thumbnail.
			if (in_array($extension, array("jpg", "jpeg", "png", "gif"))) {
				if ($expanded) return "<span class='attachment attachment-image'><img src='".$url."' alt='".$filename."' title='".$filename."'></span>";
				else return "<a href='".$url."' class='attachment attachment-image' target='_blank'><img src='".URL("attachment/thumb/".$attachment["attachmentId"])."' alt='".$filename."' title='".$filename."'><span class='filename'>".$filename."</span></a>";
			}

			// Embed video.
			if (in_array($extension, array("mp4", "mov", "mpg", "avi", "m4v")) and $expanded) {
				return "<video width='400' height='225' controls><source src='".$url."'></video>";
			}

			// Embed audio.
			if (in_array($extension, array("mp3", "mid", "wav")) and $expanded) {
				return "<audio controls><source src='".$url."'></video>";
			}

			$icons = array(
				"pdf" => "file-text-alt",
				"doc" => "file-text-alt",
				"docx" => "file-text-alt",
				"zip" => "archive",
				"rar" => "archive",
				"gz" => "archive"
			);
			$icon = isset($icons[$extension]) ? $icons[$extension] : "file";
			return "<a href='".$url."' class='attachment' target='_blank'><i class='icon-$icon'></i><span class='filename'>".$filename."</span></a>";
		}
	}

	// Add the attachments/fineuploader JS/CSS to the conversation view.
	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("fineuploader/fineuploader.css"));
		$sender->addCSSFile($this->resource("attachments.css"));
		$sender->addJSFile($this->resource("fineuploader/jquery.fineuploader.js"));
        $sender->addJSFile($this->resource("attachments.js"));
		$sender->addJSLanguage("Delete", "Embed in post");
	}

	// When we render the reply box, add the attachments area to the bottom of it.
	public function handler_conversationController_renderReplyBox($sender, &$formatted, $conversation)
	{
		// Get "draft" attachments for this member/conversation.
		$sql = ET::SQL()
			->where("draftMemberId", ET::$session->userId)
			->where("draftConversationId", $conversation["conversationId"]);
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);

		$this->appendEditAttachments($sender, $formatted, $attachments);
	}

	// When we render an edit post box, add the attachments area to the bottom of it.
	public function handler_conversationController_renderEditBox($sender, &$formatted, $post)
	{
		// Clear attachment session data for this post.
		ET::getInstance("attachmentModel")->extractFromSession("p".$post["postId"]);

		$this->appendEditAttachments($sender, $formatted, $post["attachments"]);
	}

	// Get the contents of the "edit attachments" view and append it before the editButtons div.
	protected function appendEditAttachments($sender, &$formatted, $attachments)
	{
		$view = $sender->getViewContents("attachments/edit", array("attachments" => $attachments));
		addToArray($formatted["footer"], $view, 0);
	}

	// Hook onto PostModel::getPosts and get attachment data for all posts being displayed and "attach" it to each post array. (Pun totally intended)
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
			->bind(":postIds", $postIds)
			->orderBy("filename ASC");
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);

		// Now loop through the attachments and add them to appropriate posts.
		foreach ($attachments as $attachment) {
			$key = array_search($attachment["postId"], $postIds);
			$posts[$key]["attachments"][] = $attachment;
		}
	}

	// Hook onto ConversationController::editPost and refresh the attachment data for this post.
	// We must do this because after a post is saved (and attachments with it), we re-render the post so we need up-to-date data.
	public function handler_conversationController_editPostAfter($sender, &$post)
	{
		$sql = ET::SQL()->where("postId", $post["postId"]);
		$attachments = ET::getInstance("attachmentModel")->getWithSQL($sql);
		$post["attachments"] = $attachments;
	}

	// Hook onto ConversationController::formatPostForTemplate and add the attachment/list view to the bottom of each post.
	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		// If the post has been deleted or has no attachments, stop!
		if ($post["deleteMemberId"] or empty($post["attachments"])) return;

		// Go through and replace embedded attachments in the post content.
		$this->attachments = $post["attachments"];
		$formatted["body"] = preg_replace_callback("/\[attachment:(\w+)\]/i", array($this, "attachmentCallback"), $formatted["body"]);

		if (empty($this->attachments)) return;
		$formatted["body"] .= $sender->getViewContents($this->view("attachments/list"), array("attachments" => $this->attachments));
	}

	// A temporary array of attachments that will be listed at the end of a post.
	// As embedded attachments are parsed, they are removed from this array
	// so they are not listed at the end of the post.
	protected $attachments = array();

	// A callback to transform an embedded attachment.
	public function attachmentCallback($matches)
	{
		$id = $matches[1];
		$attachment = null;
		foreach ($this->attachments as $k => $a) {
			if ($a["attachmentId"] == $id) {
				$attachment = $a;
				unset($this->attachments[$k]);
				break;
			}
		}

		if (!$attachment) return;

		return formatAttachment($attachment, true);
	}

	// Hook onto ConversationModel::addReply and commit attachments from the session to the database.
	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		$model = ET::getInstance("attachmentModel");
		$attachments = $model->extractFromSession("c".$conversation["conversationId"]);
		if (!empty($attachments)) $model->insertAttachments($attachments, array("postId" => $postId));

		// Update draft attachment entries in the database and assign them to this post.
		ET::SQL()
			->update("attachment")
			->set("postId", $postId)
			->set("draftMemberId", null)
			->set("draftConversationId", null)
			->where("draftMemberId", ET::$session->userId)
			->where("draftConversationId", $conversation["conversationId"])
			->exec();
	}

	// Hook onto ConversationModel::create and commit attachments from the session to the database.
	public function handler_conversationModel_createAfter($sender, $conversation, $postId, $content)
	{
		$model = ET::getInstance("attachmentModel");
		$attachments = $model->extractFromSession("c0");
		if (!empty($attachments)) $model->insertAttachments($attachments, array("postId" => $postId));
	}

	// Hook onto PostModel::editPost and commit attachments from the session to the database.
	public function handler_postModel_editPostAfter($sender, $post)
	{
		$model = ET::getInstance("attachmentModel");
		$attachments = $model->extractFromSession("p".$post["postId"]);
		if (!empty($attachments)) $model->insertAttachments($attachments, array("postId" => $post["postId"]));
	}

	// Hook onto ConversationModel::setDraft and commit attachments from the session to the database.
	public function handler_conversationModel_setDraftAfter($sender, $conversation, $memberId, $draft)
	{
		$model = ET::getInstance("attachmentModel");

		// Get the attachments for this conversation that are being stored in the session.
		$attachments = $model->extractFromSession(ET::$controller->controllerMethod == "start" ? "c0" : "c".$conversation["conversationId"]);

		// If we're discarding the draft, remove the attachments from the session/filesystem/database.
		if ($draft === null) {

			// Get attachments from the database.
			$dbAttachments = $model->get(array(
				"draftMemberId" => $memberId,
				"draftConversationId" => $conversation["conversationId"]
			));

			// Delete them from the database.
			ET::SQL()
				->delete()
				->from("attachment")
				->where("draftMemberId", $memberId)
				->where("draftConversationId", $conversation["conversationId"])
				->exec();

			// Delete all attachments (session and database) from the filesystem.
			$attachments = array_merge($attachments, $dbAttachments);
			foreach ($attachments as $attachment) {
				$model->removeFile($attachment);
			}
		}

		// If we're saving a draft, commit those attachments from the session to the database.
		elseif (!empty($attachments)) {
			$model->insertAttachments($attachments, array(
				"draftMemberId" => $memberId,
				"draftConversationId" => $conversation["conversationId"]
			));
		}
	}

	// Construct and process the settings form.
	public function settings($sender)
	{
		// Set up the settings form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/plugins/settings/Attachments");
		$form->setValue("allowedFileTypes", implode(" ", (array)C("plugin.Attachments.allowedFileTypes")));
		$form->setValue("maxFileSize", C("plugin.Attachments.maxFileSize"));

		// If the form was submitted...
		if ($form->validPostBack("attachmentsSave")) {

			// Construct an array of config options to write.
			$config = array();
			$fileTypes = $form->getValue("allowedFileTypes");
			$config["plugin.Attachments.allowedFileTypes"] = $fileTypes ? explode(" ", $fileTypes) : "";
			$config["plugin.Attachments.maxFileSize"] = $form->getValue("maxFileSize");

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success autoDismiss");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("attachmentsSettingsForm", $form);
		return $this->view("settings");
	}
}
