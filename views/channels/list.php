<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the channel list.
 *
 * @package esoTalk
 */
?>
<ul class='list channelList'>

<?php foreach ($data["channels"] as $channel): ?>

<li class='depth<?php echo $channel["depth"]; ?><?php if ($channel["lft"] + 1 < $channel["rgt"]): ?> hasChildren<?php endif; ?>' id='channel-<?php echo $channel["channelId"]; ?>'>

<?php if (ET::$session->user): ?>
<div class='subscription'>
<a href='<?php echo URL("channels/subscribe/".$channel["channelId"]."?token=".ET::$session->token); ?>' class='button <?php if (!empty($channel["unsubscribed"])): ?>un<?php endif; ?>subscribed' data-id='<?php echo $channel["channelId"]; ?>'><?php echo empty($channel["unsubscribed"]) ? "<span class='icon-tick'></span> ".T("Subscribed") : T("Subscribe"); ?></a>
</div>
<?php endif; ?>

<div class='stats subText'>
<span><?php echo Ts("%s conversation", "%s conversations", $channel["countConversations"]); ?></span>
</div>

<div class='info'>
<a href='<?php echo URL("conversations/".$channel["slug"]); ?>' class='channel channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></a>
<?php if (!empty($channel["description"])): ?><p class='description'><?php echo $channel["description"]; ?></p><?php endif; ?>
</div>
</li>

<?php endforeach; ?>

</ul>