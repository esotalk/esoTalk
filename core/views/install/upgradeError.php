<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a fatal error that has occurred in the esoTalk installer.
 *
 * @package esoTalk
 */
?>
<h1><?php echo T("Fatal Error"); ?></h1>

<h2><?php printf(T("message.fatalError"), "http://esotalk.org/docs/debug"); ?></h2>

<div class='details'>
	<div class='code'>
		<?php echo $data["error"]; ?>
	</div>
</div>

<br>

<p>
<a href='<?php echo URL("upgrade"); ?>' class='button submit'><?php echo T("Try Again"); ?></a>
</p>
