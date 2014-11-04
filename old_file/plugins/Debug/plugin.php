<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Debug"] = array(
	"name" => "Debug",
	"description" => "Shows useful debugging information, such as SQL queries, to administrators.",
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
class ETPlugin_Debug extends ETPlugin {


/**
 * The time at which the current query started running.
 * @var int
 */
private $queryStartTime;


/**
 * An application backtrace taken before a query is executed, and used to work out which model/method the
 * query came from.
 * @var array
 */
private $backtrace;


/**
 * An array of queries that have been executed.
 * @var array
 */
private $queries = array();


/**
 * Initialize the plugin: turn esoTalk's debug config variable on.
 *
 * @return void
 */
public function init()
{
	parent::init();

	// Turn debug mode on.
	if (!ET::$session->isAdmin()) return;
	ET::$config["esoTalk.debug"] = true;
}


/**
 * On all controller initializations, add the debug CSS file to the page.
 *
 * @return void
 */
public function handler_init()
{
	if (!ET::$session->isAdmin()) return;
	ET::$controller->addCSSFile($this->getResource("debug.css"), true);
}


/**
 * Store the current time (before a query is executed) so we can work out how long it took when it finishes.
 *
 * @return void
 */
public function handler_database_beforeQuery($sender, $query)
{
	if (is_object(ET::$session) and !ET::$session->isAdmin()) return;

	$this->queryStartTime = microtime(true);
	$this->backtrace = debug_backtrace();
}


/**
 * Work out how long the query took to run and add it to the log.
 *
 * @return void
 */
public function handler_database_afterQuery($sender, $result)
{
	if (is_object(ET::$session) and !ET::$session->isAdmin()) return;

	// The sixth item in the backtrace is typically the model. Screw being reliable.
	$item = $this->backtrace[6];
	$method = isset($item["class"]) ? $item["class"]."::" : "";
	$method .= $item["function"]."()";

	// Store the query in our queries array.
	$this->queries[] = array($result->queryString, round(microtime(true) - $this->queryStartTime, 4), $method);
}


/**
 * Render the debug area at the bottom of the page.
 *
 * @return void
 */
function handler_pageEnd($sender)
{
	// Don't proceed if the user is not permitted to see the debug information!
	if (!ET::$session->isAdmin()) return;

	// Stop the page loading timer.
	$end = microtime(true);
	$time = round($end - PAGE_START_TIME, 4);

	// Output the debug area.
	echo "<div id='debug'>
<div id='debugHdr'><h2>".sprintf(T("Page loaded in %s seconds"), $time)."</h2></div>";

	// Include the geshi library so we can syntax-highlight MySQL queries.
	include "geshi/geshi.php";

	echo "<h3><a href='#' onclick='$(\"#debugQueries\").slideToggle(\"fast\");return false'>".T("MySQL queries")." (<span id='debugQueriesCount'>".count($this->queries)."</span>)</a></h3>
	<div id='debugQueries' class='section'>";
	foreach ($this->queries as $query) {
		$geshi = new GeSHi(trim($query[0]), "mysql");
		$geshi->set_header_type(GESHI_HEADER_PRE);
		echo "<div><strong>".$query[2]."</strong> <span class='queryTime subText".($query[1] > 0.5 ? " warning" : "")."'>".$query[1]."s</span>".$geshi->parse_code()."</div>";
	}
	$this->queries = array();

	// Output POST + GET + FILES information.
	echo "</div>
	<h3><a href='#' onclick='$(\"#debugPostGetFiles\").slideToggle(\"fast\");return false'>".T("POST + GET + FILES information")."</a></h3>
	<div id='debugPostGetFiles' class='section'>
	<p style='white-space:pre' class='fixed' id='debugPost'>\$_POST = ";
	echo sanitizeHTML(print_r($_POST, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugGet'>\$_GET = ";
	echo sanitizeHTML(print_r($_GET, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugFiles'>\$_FILES = ";
	echo sanitizeHTML(print_r($_FILES, true));
	echo "</p>
	</div>";

	// Output SESSION + COOKIE information.
	echo "<h3><a href='#' onclick='$(\"#debugSessionCookie\").slideToggle(\"fast\");return false'>".T("SESSION + COOKIE information")."</a></h3>
	<div id='debugSessionCookie' class='section'><p style='white-space:pre' class='fixed' id='debugSession'>\$_SESSION = ";
	echo sanitizeHTML(print_r($_SESSION, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugCookie'>\$_COOKIE = ";
	echo sanitizeHTML(print_r($_COOKIE, true));
	echo "</p></div>";


	// Hide all panels by default.
	echo "<script>
	$(function() {
		$('#debug .section').hide();
	});
	</script>";
}

}