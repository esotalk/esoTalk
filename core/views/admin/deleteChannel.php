<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet to delete a channel.
 *
 * @package esoTalk
 */

$channel = $data["channel"];
$form = $data["form"];
?>
<div class='sheet' id='deleteChannelSheet'>
<div class='sheetContent'>

<h3><?php echo T("Delete Channel"); ?>: <?php echo sanitizeHTML($channel["title"]); ?></h3>

<?php echo $form->open(); ?>

<div class='section form'>

<p class='help'><?php echo T("message.deleteChannelHelp"); ?></p>

<p class='radio'>
<label><?php echo $form->radio("method", "move"); ?> <?php echo T("<strong>Move</strong> conversations to the following channel:"); ?></label>
<?php
$moveOptions = array();
foreach ($data["channels"] as $id => $ch) {
	if ($id == $channel["channelId"]) continue;
	$moveOptions[$id] = str_repeat("&nbsp;", $ch["depth"] * 5).$ch["title"];
}
echo $form->select("moveToChannelId", $moveOptions);
?>
</p>

<p class='radio'>
<label><?php echo $form->radio("method", "delete"); ?> <?php echo T("<strong>Delete</strong> all conversations forever."); ?></label>
</p>

</div>

<div class='buttons'>
<?php echo $form->button("delete", T("Delete Channel"), array("class" => "big")); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>