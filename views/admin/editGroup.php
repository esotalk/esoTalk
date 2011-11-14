<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a form to edit a group's details, or create a new one.
 * 
 * @package esoTalk
 */

$form = $data["form"];
$group = $data["group"];
?>
<div class='sheet' id='editGroupSheet'>
<div class='sheetContent'>

<?php echo $form->open(); ?>

<h3><?php echo T($group ? "Edit Group" : "Create Group"); ?></h3>

<div class='section' id='editGroupForm'>

<ul class='form'>

<li>
<label><?php echo T("Group name"); ?></label>
<?php echo $form->input("name"); ?>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Global permissions"); ?></label>
<div class='checkboxGroup'>
<label class='checkbox'><?php echo $form->checkbox("canSuspend"); ?> <?php echo T("Can suspend/unsuspend members"); ?></label>
<?php if (!$group): ?><label class='checkbox'><?php echo $form->checkbox("giveModeratePermission"); ?> <?php echo T("Give this group the 'moderate' permission on all existing channels"); ?></label><?php endif; ?>
</div>
<small><?php echo T("You can manage channel-specific permissions on the channels page."); ?></small>
</li>

</ul>

</div>

<div class='buttons'>
<?php
echo $form->saveButton();
echo $form->cancelButton();
?>
</div>

<?php echo $form->close(); ?>

</div>
</div>