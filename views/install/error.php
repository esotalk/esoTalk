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

<?php printf(T("message.fatalErrorInstaller"), "http://esotalk.org/forum"); ?>

<div class='details'>
<?php echo $data["error"]; ?>
</div>

<hr/>

<p>
<a href='<?php echo URL("install/info"); ?>' class='button big'>&#139; <?php echo T("Go Back"); ?></a>
<a href='<?php echo URL("install/install"); ?>' class='button big'><?php echo T("Try Again"); ?></a>
</p>