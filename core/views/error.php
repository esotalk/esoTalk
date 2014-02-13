<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Fatal error page.
 *
 * @package esoTalk
 */
?>
<h1><?php echo T("Fatal Error"); ?></h1>

<?php printf(T("message.fatalError"), "javascript:window.location.reload()", "http://esotalk.org/docs/debug"); ?>

<?php if (C("esoTalk.debug", true)): ?>
<hr/>
<br/>
<div style='font-weight:bold'><?php echo $message; ?></div>

<?php

// Output an excerpt from the file that the error was on.
if (is_array($errorLines) and $line > -1) {
	echo "<p><strong>The error occurred on or near:</strong> $file:$line</p>";
	echo "<div class='details'>";
	$padding = strlen($line + 5);
	$start = max(0, $line - 5);
	$end = min(count($errorLines), $line + 4);
	for ($i = $start; $i < $end; $i++) {
		echo "<pre", ($line == $i ? " class='highlight'" : ""), ">", str_pad($i, $padding, " ", STR_PAD_LEFT).": ".htmlentities($errorLines[$i - 1], ENT_COMPAT, "UTF-8"), "</pre>";
	}
	echo "</div>";
}

// Output a backtrace.
if (is_array($backtrace)) {
	echo "<p><strong>Backtrace:</strong></p>";
	echo "<div class='details'>";
	foreach ($backtrace as $k => $v) {
		echo "<pre>";
		echo isset($v["file"]) ? "[".$v["file"].":".$v["line"]."] " : "",
			"<strong>",
			isset($v["class"]) ? $v["class"] : "",
			isset($v["type"]) ? $v["type"] : "::",
			$v["function"],"();</strong>";
		echo "</pre>";
	}
    echo "</div>";
}

// Output some additional information (server info, etc.)
echo "<h3>Additional information:</h3>
	<ul>
	<li><strong>esoTalk Version (code):</strong> ".ESOTALK_VERSION."</li>
	<li><strong>esoTalk Version (config):</strong> ".C("esoTalk.version")."</li>
	<li><strong>PHP Version:</strong> ".PHP_VERSION."</li>
	<li><strong>Operating System:</strong> ".PHP_OS."</li>\n";

if (array_key_exists('SERVER_SOFTWARE', $_SERVER))
	echo '<li><strong>Server Software:</strong> '.$_SERVER['SERVER_SOFTWARE']."</li>\n";

if (array_key_exists('HTTP_REFERER', $_SERVER))
	echo '<li><strong>Referer:</strong> '.$_SERVER['HTTP_REFERER']."</li>\n";

if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
	echo '<li><strong>User Agent:</strong> '.$_SERVER['HTTP_USER_AGENT']."</li>\n";

if (array_key_exists('REQUEST_URI', $_SERVER))
	echo '<li><strong>Request URI:</strong> '.$_SERVER['REQUEST_URI']."</li>\n";

echo "</ul>";

endif;
