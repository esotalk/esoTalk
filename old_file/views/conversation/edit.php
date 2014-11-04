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
<h1 id='conversationTitle'><?php echo $form->input("title", "text", array("placeholder" => T("Enter a conversation title"), "tabindex" => 100)); ?></h1>
<?php

// Channel ?>
<a href='<?php echo URL("conversations/".$conversation["channelSlug"]); ?>' class='channel channel-<?php echo $conversation["channelId"]; ?>' title='<?php echo sanitizeHTML($conversation["channelDescription"]); ?>'><?php echo sanitizeHTML($conversation["channelTitle"]); ?></a>
<a href='<?php echo URL("conversation/changeChannel/".$conversation["conversationId"]); ?>' id='control-changeChannel'><?php echo T("Change channel"); ?></a>

</div>

<?php
// Controls
if ($conversation["conversationId"]): ?>
<?php echo $form->saveButton(); ?>
<a href='<?php echo URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))); ?>' class='cancel'><?php echo T("Cancel"); ?></a>
<?php endif; ?>

<?php
// Members allowed list (if starting a conversation)
if (!$conversation["conversationId"]): ?>

<div id='conversationPrivacy'>
<span class='allowedList action'><?php $this->renderView("conversation/membersAllowedSummary", $data); ?></span>
<a href='#membersAllowedSheet' id='control-changeMembersAllowed'><?php echo T("Change"); ?></a>
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
if ($conversation["startMemberId"] == ET::$session->userId or $conversation["canModerate"]): ?>
<?php echo $data["membersAllowedForm"]->open(); ?>
<?php $this->renderView("conversation/editMembersAllowed", array("form" => $data["membersAllowedForm"], "conversation" => $conversation)); ?>
<?php echo $data["membersAllowedForm"]->close(); ?>
<?php endif; ?>