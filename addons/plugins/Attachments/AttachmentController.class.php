<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

class AttachmentController extends ETController {

	protected function getAttachment($attachmentId)
	{
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

		return $attachment;
	}

	// View an attachment.
	public function action_index($attachmentId = false)
	{
		$attachmentId = explode("_", $attachmentId);
		$attachmentId = $attachmentId[0];

		if (!($attachment = $this->getAttachment($attachmentId))) return;

		$model = ET::getInstance("attachmentModel");

		// Serve up the file.
		$path = $model->path().$attachmentId.$attachment["secret"];
		$file = @fopen($path, 'rb');
		$speed = 1024;

		if (is_resource($file) === true) {
			$size = sprintf('%u', filesize($path));

			set_time_limit(0);

			// Close the session so the user can still make other requests while this is happening.
			if (strlen(session_id()) > 0) session_write_close();

			$range = array(0, $size - 1);

			// Don't really understand how this code works, tbh.
			// From: http://stackoverflow.com/a/7591130
			if (array_key_exists('HTTP_RANGE', $_SERVER) === true) {
				$range = array_map('intval', explode('-', preg_replace('~.*=([^,]*).*~', '$1', $_SERVER['HTTP_RANGE'])));
				if (empty($range[1]) === true) $range[1] = $size - 1;
				foreach ($range as $key => $value) $range[$key] = max(0, min($value, $size - 1));
				if (($range[0] > 0) || ($range[1] < ($size - 1)))
					header(sprintf('%s %03u %s', 'HTTP/1.1', 206, 'Partial Content'), true, 206);
			}

			header('Accept-Ranges: bytes');
			header('Content-Range: bytes ' . sprintf('%u-%u/%u', $range[0], $range[1], $size));

			header('Pragma: public');
			header('Cache-Control: public, no-cache');
			header('Content-Type: '.$model->mime($attachment["filename"]));
			header('Content-Length: ' . sprintf('%u', $range[1] - $range[0] + 1));
			header('Content-Disposition: inline; filename="' . basename($attachment["filename"]) . '"');
			header('Content-Transfer-Encoding: binary');

			if ($range[0] > 0) fseek($file, $range[0]);

			while ((feof($file) !== true) && (connection_status() === CONNECTION_NORMAL))
				echo fread($file, round($speed * 1024)); flush(); sleep(1);

			fclose($file);
			exit;
		}

		else {
			$this->render404(T("message.attachmentNotFound"), true);
			return false;
		}
	}

	// Generate/view a thumbnail of an image attachment.
	public function action_thumb($attachmentId = false)
	{
		if (!($attachment = $this->getAttachment($attachmentId))) return;

		$model = ET::getInstance("attachmentModel");
		$path = $model->path().$attachmentId.$attachment["secret"];
		$thumb = $path."_thumb";

		if (!file_exists($thumb)) {
			try {
				$uploader = ET::uploader();
				$thumb = $uploader->saveAsImage($path, $thumb, 200, 150, "crop");
				$newThumb = substr($thumb, 0, strrpos($thumb, "."));
				rename($thumb, $newThumb);
				$thumb = $newThumb;
			} catch (Exception $e) {
				return;
			}
		}

		header('Content-Type: '.$model->mime($attachment["filename"]));
		echo file_get_contents($thumb);
	}

	// Upload an attachment.
	public function action_upload()
	{
		require_once 'qqFileUploader.php';
		$uploader = new qqFileUploader();
		$uploader->inputName = 'qqfile';

		// Set the allowed file types based on config.
		$allowedFileTypes = C("plugin.Attachments.allowedFileTypes");
		if (!empty($allowedFileTypes))
			$uploader->allowedExtensions = $allowedFileTypes;

		// Set the max file size based on config.
		if ($size = C("plugin.Attachments.maxFileSize"))
			$uploader->sizeLimit = $size;

		// Generate a unique ID and secret for this attachment.
		$attachmentId = uniqid();
		$secret = generateRandomString(13, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890");
		$name = $uploader->getName();

		// Save it to the filesystem.
		$model = ET::getInstance("attachmentModel");
		$result = $uploader->handleUpload($model->path(), $attachmentId.$secret);

		if (!empty($result["success"])) {

			$result['uploadName'] = $uploader->getUploadName();
			$result['attachmentId'] = $attachmentId;

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

	// Remove an attachment.
	public function action_remove($attachmentId)
	{
		if (!$this->validateToken()) return;

		$session = (array)ET::$session->get("attachments");
		if (isset($session[$attachmentId])) {
			unset($session[$attachmentId]);
			ET::$session->store("attachments", $session);
		}

		else {
			$model = ET::getInstance("attachmentModel");
			$attachment = $model->getById($attachmentId);

			// Make sure the user has permission to edit this post.
			$permission = false;
			if (!empty($attachment["postId"])) {
				$post = ET::postModel()->getById($attachment["postId"]);
				$conversation = ET::conversationModel()->getById($post["conversationId"]);
				$permission = ET::postModel()->canEditPost($post, $conversation);
			}
			else {
				$permission = ET::$session->userId == $attachment["draftMemberId"];
			}

			if (!$permission) {
				$this->renderMessage(T("Error"), T("message.noPermission"));
				return false;
			}

			// Remove the attachment from the database and filesystem.
			$model->deleteById($attachmentId);
			@unlink($model->path().$attachmentId.$attachment["secret"]);
			@unlink($model->path().$attachmentId.$attachment["secret"]."_thumb");
		}
	}

}
