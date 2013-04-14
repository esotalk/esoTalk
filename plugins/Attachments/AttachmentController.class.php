<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * 
 *
 * @package esoTalk/Attachments
 */
class AttachmentController extends ETController {


public function index($attachmentId = false)
{
	$attachmentId = explode("_", $attachmentId)[0];

	// Find the attachment in the database.
	$model = ET::getInstance("attachmentModel");
	$attachment = $model->getById($attachmentId);

	// Does this attachment exist?
	if (!$attachment or empty($attachment["postId"])) {
		$this->render404(T("message.attachmentNotFound"), true);
		return false;
	}

	// Get the post/conversation that this attachment is in, and make sure the user has permission to view it.
	$post = ET::postModel()->getById($attachment["postId"]);
	$conversation = ET::conversationModel()->getById($post["conversationId"]);

	if (!$conversation) {
		$this->render404(T("message.attachmentNotFound"), true);
		return false;
	}

	// Serve up the file with the correct mime type.
	$path = $model->path().$attachmentId.$attachment["secret"];
	$file = @fopen($path, 'rb');
	$speed = 1024;

	if (is_resource($file) === true) {
		$size = sprintf('%u', filesize($path));

		set_time_limit(0);

		// Close the session so the user can still make other requests while this is happening.
		if (strlen(session_id()) > 0) session_write_close();

		$range = array(0, $size - 1);

		if (array_key_exists('HTTP_RANGE', $_SERVER) === true)
		{
			$range = array_map('intval', explode('-', preg_replace('~.*=([^,]*).*~', '$1', $_SERVER['HTTP_RANGE'])));

			if (empty($range[1]) === true)
			{
				$range[1] = $size - 1;
			}

			foreach ($range as $key => $value)
			{
				$range[$key] = max(0, min($value, $size - 1));
			}

			if (($range[0] > 0) || ($range[1] < ($size - 1)))
			{
				header(sprintf('%s %03u %s', 'HTTP/1.1', 206, 'Partial Content'), true, 206);
			}
		}

		header('Accept-Ranges: bytes');
		header('Content-Range: bytes ' . sprintf('%u-%u/%u', $range[0], $range[1], $size));

		header('Pragma: public');
		header('Cache-Control: public, no-cache');
		header('Content-Type: '.$model->mime($attachment["filename"]));
		header('Content-Length: ' . sprintf('%u', $range[1] - $range[0] + 1));
		header('Content-Disposition: inline; filename="' . basename($attachment["filename"]) . '"');
		header('Content-Transfer-Encoding: binary');

		if ($range[0] > 0)
		{
			fseek($file, $range[0]);
		}

		while ((feof($file) !== true) && (connection_status() === CONNECTION_NORMAL))
		{
			echo fread($file, round($speed * 1024)); flush(); sleep(1);
		}

		fclose($file);
		exit;
	}

	else {
		$this->render404(T("message.attachmentNotFound"), true);
		return false;
	}
}


public function upload()
{
	require_once 'qqFileUploader.php';
	$uploader = new qqFileUploader();

	$allowedFileTypes = C("plugin.Attachments.allowedFileTypes");
	if (!empty($allowedFileTypes))
		$uploader->allowedExtensions = $allowedFileTypes;

	if ($size = C("plugin.Attachments.maxFileSize"))
		$uploader->sizeLimit = $size;

	$uploader->inputName = 'qqfile';

	// Generate a unique ID for this attachment.
	$attachmentId = uniqid();
	$secret = generateRandomString(13, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890");
	$name = $uploader->getName();

	// Save it to a temporary folder.
	$model = ET::getInstance("attachmentModel");
	$result = $uploader->handleUpload($model->path(), $attachmentId.$secret);

	if (!empty($result["success"])) {

		$result['uploadName'] = $uploader->getUploadName();

		// Save attachment information to the session.
		$session = (array)ET::$session->get("attachments");
		$session[$attachmentId] = array(
			"postId" => R("postId"),
			"name" => $name,
			"secret" => $secret
		);
		ET::$session->store("attachments", $session);
		
	}

	header("Content-Type: text/plain");
	echo json_encode($result);
}


public function remove($attachmentId)
{
	// TODO: add a token check here

	// Check whether this attachment is being stored in the session or in the database.
	$session = (array)ET::$session->get("attachments");
	if (isset($session[$attachmentId])) {
		$attachment = &$session[$attachmentId];


	}
	else {
		$model = ET::getInstance("attachmentModel");
		$attachment = $model->getById($attachmentId);

		// make sure the user has permission to edit this post.
		$permission = false;
		if (!empty($attachment["postId"])) {
			$post = ET::postModel()->getById($attachment["postId"]);
			$conversation = ET::conversationModel()->getById($post["conversationId"]);
			$permission = $this->canEditPost($post, $conversation);
		}
		else {
			$permission = ET::$session->userId == $attachment["draftMemberId"];
		}

		if (!$permission) {
			$this->renderMessage(T("Error"), T("message.noPermission"));
			return false;
		}

		// remove the attachment from the database.
		$model->deleteById($attachmentId);

		// delete the physical file
		@unlink($model->path().$attachmentId.$attachment["secret"]);

	}
}


/**
 * Returns whether or not a user has permission to edit a post, based on its details and context.
 *
 * @param array $post The post array.
 * @param array $conversation The details of the conversation which the post is in.
 * @return bool Whether or not the user can edit the post.
 */
private function canEditPost($post, $conversation)
{
	// If the user can moderate the conversation, they can always edit any post.
	if ($conversation["canModerate"]) return true;

	if (!$conversation["locked"] // If the conversation isn't locked...
		and !ET::$session->isSuspended() // And the user isn't suspended...
		and $post["memberId"] == ET::$session->userId // And this post is authored by the current user...
		and (!$post["deleteMemberId"] or $post["deleteMemberId"] == ET::$session->userId)) // And the post hasn't been deleted, or was deleted by the current user...
		return true; // Then they can edit!

	return false;
}

}