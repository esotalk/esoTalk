<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet to edit the members allowed in a conversation.
 * 
 * @package esoTalk
 */

$form = $data["form"];
?>
<div class='sheet' id='membersAllowedSheet'>
<div class='sheetContent'>

<h3><?php echo T("Members Allowed to View this Conversation"); ?></h3>

<div class='section'>

<div id='addMemberForm'>
<?php echo $form->input("member"); ?>
<?php echo $form->button("addMember", T("Add")); ?>
<span class='help'><?php echo T("Click on a member's name to remove them."); ?></span>
</div>

</div>

<div class='section editing allowedList action'>
<?php $this->renderView("conversation/membersAllowedList", $data + array("editable" => true)); ?>
</div>

</div>
</div>