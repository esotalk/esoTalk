<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Conversation results. Displays a message if there are no results, or a conversation list and
 * footer if there are.
 *
 * @package esoTalk
 */

// If there are no conversations, show a message.
if (!$data["results"]): ?>
<div class='area noResults help'>
<h4><?php echo T("message.noSearchResults"); ?></h4>
<ul>
<li><?php echo T("message.reduceNumberOfGambits"); ?></li>
<?php if (!ET::$session->user): ?><li><?php echo T("message.logInToSeeAllConversations"); ?></li><?php endif; ?>
<li><?php echo T("message.fulltextKeywordWarning"); ?></li>
</ul>
</div>

<?php
// If there are conversations, however, show them!
else:
?>
<?php $this->renderView("conversations/list", $data); ?>
<?php endif; ?>