<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the channel list.
 *
 * @package esoTalk
 */

// If there are no channels, show a message.
if (!$data["channels"]): ?>

<div class='area noResults help'>
<h4><?php echo T("message.noChannels"); ?></h4>
<ul>
<?php if (!ET::$session->user): ?><li><?php echo T("message.logInToSeeAllConversations"); ?></li><?php endif; ?>
</ul>
</div>

<?php
// If there are channels, however, show them!
else:
?>
<ul class='list channelList'>

<?php foreach ($data["channels"] as $channel): ?>

<li class='depth<?php echo $channel["depth"]; ?><?php if ($channel["lft"] + 1 < $channel["rgt"]): ?> hasChildren<?php endif; ?><?php if (!empty($channel["unsubscribed"])): ?> unsubscribed<?php endif; ?>' id='channel-<?php echo $channel["channelId"]; ?>'>

<?php if (ET::$session->user): ?>
<ul class='controls' id='channelControls-<?php echo $channel["channelId"]; ?>'>
<li><a href='<?php echo URL("channels/subscribe/".$channel["channelId"]."?token=".ET::$session->token); ?>' data-id='<?php echo $channel["channelId"]; ?>'><i class='icon-eye-close'></i><?php echo empty($channel["unsubscribed"]) ? T("Hide") : T("Unhide"); ?></a></li>
</ul>

<div class='channelControls'>
<?php $this->trigger("renderChannelControls", array($channel)); ?>
</div>
<?php endif; ?>

<div class='info'>
<a href='<?php echo URL("conversations/".$channel["slug"]); ?>' class='channel channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></a>
<span class='stats'><?php echo Ts("%s conversation", "%s conversations", $channel["countConversations"]); ?></span>
<?php if (!empty($channel["description"])): ?><p class='description'><?php echo $channel["description"]; ?></p><?php endif; ?>
</div>
</li>

<?php endforeach; ?>

</ul>
<?php endif; ?>
