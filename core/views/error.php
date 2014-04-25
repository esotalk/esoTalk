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

<h2><?php printf(T("message.fatalError"), "http://esotalk.org/docs/debug"); ?></h2>

<?php if (C("esoTalk.debug", true)): ?>
<div class='details'>

	<div class='code'>
		<?php echo $message; ?>
	</div>

	<?php

	// Output an excerpt from the file that the error was on.
	if (is_array($errorLines) and $line > -1) {
		echo "<h3>The error occurred near $file:$line</h3>";
		echo "<div class='code'>";
		$padding = strlen($line + 5);
		$start = max(0, $line - 2);
		$end = min(count($errorLines), $line + 3);
		for ($i = $start; $i < $end; $i++) {
			echo "<pre", ($line == $i ? " class='highlight'" : ""), "><em>", str_pad($i, $padding, " ", STR_PAD_LEFT).":</em> ".htmlentities($errorLines[$i - 1], ENT_COMPAT, "UTF-8"), "</pre>";
		}
		echo "</div>";
	}

	// Output a backtrace.
	if (is_array($backtrace)) {
		echo "<h3>Backtrace</h3>";
		echo "<div class='code'>";
		foreach ($backtrace as $k => $v) {
			echo "<pre>";
			echo isset($v["file"]) ? "<em>[".$v["file"].":".$v["line"]."]</em> " : "",
				"<strong>",
				isset($v["class"]) ? $v["class"] : "",
				isset($v["type"]) ? $v["type"] : "::",
				$v["function"],"();</strong>";
			echo "</pre>";
		}
	    echo "</div>";
	}

	// Output some additional information (server info, etc.)
	echo "<h3>Additional Information</h3>
		<ul class='list'>
		<li><label>esoTalk Version (code)</label> ".ESOTALK_VERSION." 
		<li><label>esoTalk Version (config)</label> ".C("esoTalk.version")." 
		<li><label>PHP Version</label> ".PHP_VERSION." 
		<li><label>Operating System</label> ".PHP_OS." \n";

	if (array_key_exists('SERVER_SOFTWARE', $_SERVER))
		echo '<li><label>Server Software</label> '.$_SERVER['SERVER_SOFTWARE']." \n";

	if (array_key_exists('HTTP_REFERER', $_SERVER))
		echo '<li><label>Referer</label> '.$_SERVER['HTTP_REFERER']." \n";

	if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
		echo '<li><label>User Agent</label> '.$_SERVER['HTTP_USER_AGENT']." \n";

	if (array_key_exists('REQUEST_URI', $_SERVER))
		echo '<li><label>Request URI</label> '.$_SERVER['REQUEST_URI']." \n";

	echo "</ul>

</div>";

endif;
