<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of warnings that have occurred within the esoTalk installer.
 *
 * @package esoTalk
 */
?>
<h1><?php echo T("Warning"); ?></h1>

<h2>
	<?php if (empty($data["fatal"])): echo T("message.preInstallWarnings"); ?>
	<?php else: echo T("message.preInstallErrors"); endif; ?>
</h2>

<hr>

<div class='details'>

	<?php foreach ($data["errors"] as $error) echo "<p class='warning'>$error</p><hr>"; ?>

</div>

<br>

<p><a href='<?php echo URL("install/info"); ?>' class='button submit'><?php echo empty($data["fatal"]) ? T("Next Step")." &#155;" : T("Try Again"); ?></a></p>
