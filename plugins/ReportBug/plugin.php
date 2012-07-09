<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ReportBug"] = array(
	"name" => "Report A Bug",
	"description" => "Adds a 'Report a bug' link to the footer.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);


/**
 * Debug Plugin
 *
 * Shows useful debugging information, such as SQL queries, to administrators.
 */
class ETPlugin_ReportBug extends ETPlugin {

/**
 * On all controller initializations, add the debug CSS file to the page.
 *
 * @return void
 */
public function handler_init($sender)
{
	$sender->addToMenu("meta", "reportBug", "<a href='http://github.com/esotalk/esoTalk/issues/' target='_blank'>".T("Report a bug")."</a>");
}

}