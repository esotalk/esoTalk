<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

class AttachmentModel extends ETModel {

	public static $mimes = array(

		'hqx'   => 'application/mac-binhex40',
		'cpt'   => 'application/mac-compactpro',
		'csv'   => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream'),
		'bin'   => 'application/macbinary',
		'dms'   => 'application/octet-stream',
		'lha'   => 'application/octet-stream',
		'lzh'   => 'application/octet-stream',
		'exe'   => array('application/octet-stream', 'application/x-msdownload'),
		'class' => 'application/octet-stream',
		'psd'   => 'application/x-photoshop',
		'so'    => 'application/octet-stream',
		'sea'   => 'application/octet-stream',
		'dll'   => 'application/octet-stream',
		'oda'   => 'application/oda',
		'pdf'   => array('application/pdf', 'application/x-download'),
		'ai'    => 'application/postscript',
		'eps'   => 'application/postscript',
		'ps'    => 'application/postscript',
		'smi'   => 'application/smil',
		'smil'  => 'application/smil',
		'mif'   => 'application/vnd.mif',
		'xls'   => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
		'ppt'   => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
		'wbxml' => 'application/wbxml',
		'wmlc'  => 'application/wmlc',
		'dcr'   => 'application/x-director',
		'dir'   => 'application/x-director',
		'dxr'   => 'application/x-director',
		'dvi'   => 'application/x-dvi',
		'gtar'  => 'application/x-gtar',
		'gz'    => 'application/x-gzip',
		'php'   => array('application/x-httpd-php', 'text/x-php'),
		'php4'  => 'application/x-httpd-php',
		'php3'  => 'application/x-httpd-php',
		'phtml' => 'application/x-httpd-php',
		'phps'  => 'application/x-httpd-php-source',
		'js'    => 'application/x-javascript',
		'swf'   => 'application/x-shockwave-flash',
		'sit'   => 'application/x-stuffit',
		'tar'   => 'application/x-tar',
		'tgz'   => array('application/x-tar', 'application/x-gzip-compressed'),
		'xhtml' => 'application/xhtml+xml',
		'xht'   => 'application/xhtml+xml',
		'zip'   => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
		'mid'   => 'audio/midi',
		'midi'  => 'audio/midi',
		'mpga'  => 'audio/mpeg',
		'mp2'   => 'audio/mpeg',
		'mp3'   => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
		'aif'   => 'audio/x-aiff',
		'aiff'  => 'audio/x-aiff',
		'aifc'  => 'audio/x-aiff',
		'ram'   => 'audio/x-pn-realaudio',
		'rm'    => 'audio/x-pn-realaudio',
		'rpm'   => 'audio/x-pn-realaudio-plugin',
		'ra'    => 'audio/x-realaudio',
		'rv'    => 'video/vnd.rn-realvideo',
		'wav'   => 'audio/x-wav',
		'bmp'   => 'image/bmp',
		'gif'   => 'image/gif',
		'jpeg'  => array('image/jpeg', 'image/pjpeg'),
		'jpg'   => array('image/jpeg', 'image/pjpeg'),
		'jpe'   => array('image/jpeg', 'image/pjpeg'),
		'png'   => 'image/png',
		'tiff'  => 'image/tiff',
		'tif'   => 'image/tiff',
		'css'   => 'text/css',
		'html'  => 'text/html',
		'htm'   => 'text/html',
		'shtml' => 'text/html',
		'txt'   => 'text/plain',
		'text'  => 'text/plain',
		'log'   => array('text/plain', 'text/x-log'),
		'rtx'   => 'text/richtext',
		'rtf'   => 'text/rtf',
		'xml'   => 'text/xml',
		'xsl'   => 'text/xml',
		'mpeg'  => 'video/mpeg',
		'mpg'   => 'video/mpeg',
		'mpe'   => 'video/mpeg',
		'qt'    => 'video/quicktime',
		'mov'   => 'video/quicktime',
		'avi'   => 'video/x-msvideo',
		'movie' => 'video/x-sgi-movie',
		'doc'   => 'application/msword',
		'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'word'  => array('application/msword', 'application/octet-stream'),
		'xl'    => 'application/excel',
		'eml'   => 'message/rfc822',
		'json'  => array('application/json', 'text/json'),

	);

	public function __construct()
	{
		parent::__construct("attachment");
	}

	public function path()
	{
		return PATH_UPLOADS.'/attachments/';
	}

	public function mime($path)
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if ( ! array_key_exists($extension, self::$mimes)) return "application/octet-stream";

		return (is_array(self::$mimes[$extension])) ? self::$mimes[$extension][0] : self::$mimes[$extension];
	}

	// Find attachments for a specific post or conversation that are being stored in the session, remove
	// them from the session, and return them.
	public function extractFromSession($postId)
	{
		$attachments = array();
		$session = (array)ET::$session->get("attachments");
		foreach ($session as $id => $attachment) {
			if ($attachment["postId"] == $postId) {
				$attachments[$id] = $attachment;
				unset($session[$id]);
			}
		}
		ET::$session->store("attachments", $session);

		return $attachments;
	}

	// Insert attachments in the database.
	public function insertAttachments($attachments, $keys)
	{
		$inserts = array();
		foreach ($attachments as $id => $attachment)
			$inserts[] = array_merge(array($id, $attachment["name"], $attachment["secret"]), array_values($keys));

		ET::SQL()
			->insert("attachment")
			->setMultiple(array_merge(array("attachmentId", "filename", "secret"), array_keys($keys)), $inserts)
			->exec();
	}

	public function removeFile($attachment)
	{
		@unlink($this->path().$attachment["attachmentId"].$attachment["secret"]);
		@unlink($this->path().$attachment["attachmentId"].$attachment["secret"]."_thumb");
	}

}
