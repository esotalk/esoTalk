<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the list of channels.
 *
 * @package esoTalk
 */
?>
<script>
$(function() {
	ETAdminChannels.init();
});
</script>

<div class='area' id='adminChannels'>

<p class='help'><?php echo T("message.channelsHelp"); ?></p>

<p><a href='<?php echo URL("admin/channels/create"); ?>' class='button' id='createChannelLink'><span class='icon-plus'></span> <?php echo T("Create Channel"); ?></a></p>

<ol class='sortable list channelList'>

<?php

// Output the channels list as nested <ol>s so the order and structure can be manipluated.
$curDepth = 0;
$counter = 0;

// For each of the channels...
foreach ($data["channels"] as $channel):

// If this channel is on the same depth as the last channel, just end the previous channel's <li>.
if ($channel["depth"] == $curDepth) {
	if ($counter > 0) echo "</li>";
}
// If this channel is deeper than the last channel, start a new <ol>.
elseif ($channel["depth"] > $curDepth) {
	echo "<ol>";
	$curDepth = $channel["depth"];
}
// If this channel is shallower than the last channel, end <li> and <ol> tags as necessary.
elseif ($channel["depth"] < $curDepth) {
	echo str_repeat("</li></ol>", $curDepth - $channel["depth"]), "</li>";
	$curDepth = $channel["depth"];
}

// Output a list item for this channel. ?>
<li id='channel_<?php echo $channel["channelId"]; ?>' data-id='<?php echo $channel["channelId"]; ?>'>
<div>
<div class='controls'>
<a href='<?php echo URL("admin/channels/edit/".$channel["channelId"]); ?>' class='control-edit' title='<?php echo T("Edit"); ?>'><i class='icon-edit'></i></a>
<a href='<?php echo URL("admin/channels/delete/".$channel["channelId"]); ?>' class='control-delete' title='<?php echo T("Delete"); ?>'><i class='icon-remove'></i></a>
</div>
<div class='info'>
<span class='channel channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></span>
<?php if (!empty($channel["description"])): ?><p class='description'><?php echo $channel["description"]; ?></p><?php endif; ?>
</div>
</div>

<?php $counter++; ?>

<?php endforeach;

// End as many unclosed <li> and <ol> tags as necessary.
echo str_repeat("</li></ol>", $curDepth), "</li>";
?>

</ol>

</div>
