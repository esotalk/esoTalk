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
	// Find the attachment in the database.

	// Get the conversation that this attachment is in, and make sure the user has permission to view it.

	// Serve up the file with the correct mime type.
}


public function upload()
{
	// Include the uploader class
	require_once 'qqFileUploader.php';

	$uploader = new qqFileUploader();

	// Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
	$uploader->allowedExtensions = array();

	// Specify max file size in bytes.
	$uploader->sizeLimit = 10 * 1024 * 1024;

	// Specify the input name set in the javascript.
	$uploader->inputName = 'qqfile';

	// If you want to use resume feature for uploader, specify the folder to save parts.
	// $uploader->chunksFolder = 'chunks';

	// To save the upload with a specified name, set the second parameter.
	// ALRIGHT, we actually want to change this to commit the file + info to the SESSION rather than
	// actually save the file to the filesystem.
	$result = $uploader->handleUpload(PATH_UPLOADS, md5(mt_rand()).'_'.$uploader->getName());

	// To return a name used for uploaded file you can use the following line.
	$result['uploadName'] = $uploader->getUploadName();

	header("Content-Type: text/plain");
	echo json_encode($result);

	// return json data and add the attachment to the view with javascript
}


public function remove($attachmentId)
{
	// make sure the user owns this post

	// remove the attachment from the session or database

	// return nothing (remove the attachment from the view with javascript)
}

}