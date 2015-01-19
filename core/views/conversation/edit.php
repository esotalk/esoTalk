<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a page to edit or start a new conversation.
 *
 * @package esoTalk
 */

$form = $data["form"];
$conversation = $data["conversation"];

echo $form->open(); ?>

<div id='conversation' class='editing'>

<!-- Conversation header -->
<div id='conversationHeader' class='bodyHeader'>

<?php

// Title ?>
<h1 id='conversationTitle'><?php echo $form->input("title", "text", array("placeholder" => T("Enter a conversation title"), "tabindex" => 100, "maxlength" => 100)); ?></h1>
<?php

// Channel 
$this->renderView("conversation/channelPath", array("conversation" => $conversation));
?>

<a href='<?php echo URL("conversation/changeChannel/".$conversation["conversationId"]); ?>' id='control-changeChannel'><i class='icon-tag'></i> <?php echo T("Change channel"); ?></a>

</div>

<?php
// Controls
if ($conversation["conversationId"]): ?>
<?php echo $form->saveButton(); ?> 
<a href='<?php echo URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))); ?>' class='button cancel'><?php echo T("Cancel"); ?></a>
<?php endif; ?>

<?php
// Members allowed list (if starting a conversation)
if (!$conversation["conversationId"]): ?>

<div id='conversationPrivacy' class='area'>
<span class='allowedList action'><?php $this->renderView("conversation/membersAllowedSummary", $data); ?></span>
<a href='#membersAllowedSheet' id='control-changeMembersAllowed'><i class='icon-pencil'></i> <?php echo T("Change"); ?></a>
</div>

<div id='conversationReply'>
<?php
$this->renderView("conversation/reply", array(
	"form" => $form,
	"conversation" => $conversation,
	"controls" => $data["replyControls"]
));
?>
</div>

<?php endif; ?>

<?php echo $form->close(); ?>

</div>

<?php
// Members allowed list (only if conversation is private or editable)
if ($conversation["canEditMembersAllowed"]): ?>
<?php echo $data["membersAllowedForm"]->open(); ?>
<?php $this->renderView("conversation/editMembersAllowed", array("form" => $data["membersAllowedForm"], "conversation" => $conversation)); ?>
<?php echo $data["membersAllowedForm"]->close(); ?>
<?php endif; ?>
