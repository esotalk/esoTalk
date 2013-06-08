<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the channel breadcrumb tabs in the channel list and the conversations list.
 *
 * Works with a few things set in $data:
 * 		currentChannels: an array of currently selected channel IDs.
 * 		channelTabs: an array of channels which should be displayed as the current depth.
 * 		channelPath: an array of channels leading up to the current depth.
 *
 * @package esoTalk
 */

// Show the path leading up to the current channel depth. ?>
<li class='pathItem<?php if (isset($data["currentChannels"])): ?> selected<?php endif; ?>'>
<a href='<?php echo URL("conversations/all"); ?>' data-channel='all' class='channel-all'><?php echo T("All Channels"); ?></a>

<?php if (!empty($data["channelPath"])):
foreach ($data["channelPath"] as $channel): ?>
<a href='<?php echo URL("conversations/".$channel["slug"]); ?>' data-channel='<?php echo $channel["slug"]; ?>' title='<?php echo sanitizeHTML(strip_tags($channel["description"])); ?>' class='channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></a>
<?php endforeach; ?>
<?php endif; ?>
</li>

<?php
// Show the channels at the current depth.
if (!empty($data["channelTabs"])): ?>
<?php foreach ($data["channelTabs"] as $channel): ?>
<li<?php if (in_array($channel["channelId"], $data["currentChannels"])): ?> class='selected'<?php endif; ?>><a href='<?php echo URL("conversations/".$channel["slug"].(!empty($data["searchString"]) ? "?search=".urlencode($data["searchString"]) : "")); ?>' title='<?php echo sanitizeHTML(strip_tags($channel["description"])); ?>' class='channel-<?php echo $channel["channelId"]; ?><?php if (in_array($channel["channelId"], $data["currentChannels"])): ?> channel<?php endif; ?>' data-channel='<?php echo $channel["slug"]; ?>'><?php echo $channel["title"]; ?></a></li>
<?php endforeach; ?>
<?php endif; ?>