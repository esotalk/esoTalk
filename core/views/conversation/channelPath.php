<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a breadcrumb of channels and sub-channels that the conversation is in.
 *
 * @package esoTalk
 */

$conversation = $data["conversation"];
?>
<ul class='channels tabs'>
<li class='pathItem selected pathEnd'>
<?php $conversation["channelPath"] = array_reverse($conversation["channelPath"]);
foreach ($conversation["channelPath"] as $channel): ?>
<a href='<?php echo URL("conversations/".$channel["slug"]); ?>' data-channel='<?php echo $channel["slug"]; ?>' title='<?php echo sanitizeHTML(strip_tags($channel["description"])); ?>' class='channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></a>
<?php endforeach; ?>
</li>
</ul>