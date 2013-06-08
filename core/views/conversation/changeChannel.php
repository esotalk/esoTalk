<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a list of channels to choose from.
 *
 * @package esoTalk
 */

$form = $data["form"];
$conversation = $data["conversation"];
?>
<div class='sheet' id='changeChannelSheet'>
<div class='sheetContent'>

<h3><?php echo $conversation["conversationId"] ? T("Change Channel").": ".sanitizeHTML($conversation["title"]) : T("Choose a Channel"); ?></h3>

<?php echo $form->open(); ?>

<div class='sheetBody'>

<div class='section'>

<ul class='list channelList changeChannelList'>
<?php foreach ($data["channels"] as $channel): ?>

<li class='depth<?php echo $channel["depth"]; ?><?php if (!$channel["start"]): ?> disabled<?php endif; ?>'<?php if (!$channel["start"]): ?> title='<?php echo T("You do not have permission to start conversations in this channel."); ?>'<?php endif; ?>>
<label class='radio'>
<input type='radio' name='channel' value='<?php echo $channel["channelId"]; ?>'<?php if ($conversation["channelId"] == $channel["channelId"]): ?> checked='checked'<?php endif; ?><?php if (!$channel["start"]): ?> disabled='disabled'<?php endif; ?>/>
<span class='channel channel-<?php echo $channel["channelId"]; ?>'><?php echo $channel["title"]; ?></span>
<?php if (!empty($channel["description"])): ?><span class='description'><?php echo $channel["description"]; ?></span><?php endif; ?>
</label>
</li>

<?php endforeach; ?>
</ul>

</div>

</div>

<div class='buttons'>
<?php echo $form->saveButton(); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>