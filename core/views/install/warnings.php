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

<p>
<?php if (empty($data["fatal"])): echo T("message.preInstallWarnings"); ?>
<?php else: echo T("message.preInstallErrors"); endif; ?>
</p>

<hr/>

<ul>
<?php foreach ($data["errors"] as $error) echo "<li><p>$error</p></li>"; ?>
</ul>

<hr/>

<p><?php printf(T("If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='%s'>esoTalk support forum</a>."), "http://esotalk.org/forum"); ?></p>

<hr/>

<p><a href='<?php echo URL("install/info"); ?>' class='button big'><?php echo empty($data["fatal"]) ? T("Next Step")." &#155;" : T("Try Again"); ?></a></p>