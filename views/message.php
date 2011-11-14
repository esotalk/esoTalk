<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a modal message sheet. Used by ETController::renderMessage().
 * 
 * @package esoTalk
 */
?>
<div class='sheet' id='messageSheet'>
<div class='sheetContent'>

<h3><?php echo $data["title"]; ?></h3>

<div class='section help'><?php echo $data["message"]; ?></div>

<div class='buttons'>
<a href='<?php echo URL(R("return")); ?>' class='button big'><?php echo T("OK"); ?></a>
</div>

</div>
</div>