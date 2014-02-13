<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the admin settings page, consisting of a form with various controls.
 *
 * @package esoTalk
 */

$form = $data["form"];
$groups = $data["groups"];
?>
<script>
$(function() {
	ETAdminSettings.init();
});
</script>

<div class='area' id='adminSettings'>

<?php echo $form->open(); ?>

<ul class='form'>

<li>
<label><?php echo T("Forum title"); ?></label>
<?php echo $form->input("forumTitle"); ?>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Default forum language"); ?></label>
<?php echo $form->select("language", $data["languages"]); ?>
<div><a href='<?php echo URL("admin/languages"); ?>'><?php echo T("Manage Languages"); ?></a></div>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Forum header"); ?></label>
<div class='checkboxGroup'>
<label class='radio'><?php echo $form->radio("forumHeader", "title"); ?> <?php echo T("Show the forum title in the header"); ?></label>
<label class='radio'><?php echo $form->radio("forumHeader", "image"); ?> <?php echo T("Show an image in the header"); ?><br/><?php echo $form->input("forumHeaderImage", "file", array("class" => "text")); ?></label>
</div>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Home page"); ?></label>
<div class='subText'><?php echo T("Choose what people will see when they first visit your forum."); ?></div>
<div class='checkboxGroup'>
<label class='radio'><?php echo $form->radio("defaultRoute", "conversations"); ?> <?php echo T("Show the conversation list by default"); ?></label>
<label class='radio'><?php echo $form->radio("defaultRoute", "channels"); ?> <?php echo T("Show the channel list by default"); ?></label>
</div>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Forum privacy"); ?></label>
<div class='subText'><?php echo T("Guests can view the:"); ?></div>
<div class='checkboxGroup'>
<label class='checkbox'><?php echo $form->checkbox("forumVisibleToGuests"); ?> <?php echo T("Forum"); ?></label>
<label class='checkbox'><?php echo $form->checkbox("memberListVisibleToGuests"); ?> <?php echo T("Member list"); ?></label>
</div>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Registration"); ?></label>
<div class='subText'><?php echo T("Customize how users can become members of your forum."); ?></div>
<div class='checkboxGroup'>
<label class='radio'><?php echo $form->radio("registrationOpen", 0); ?> <?php echo T("Close registration"); ?></label>
<label class='radio'><?php echo $form->radio("registrationOpen", 1); ?> <?php echo T("Open registration"); ?></label>
<label class='checkbox indent'><?php echo $form->checkbox("requireEmailConfirmation"); ?> <?php echo T("Require users to confirm their email address"); ?></label>
<label class='checkbox indent'><?php echo $form->checkbox("requireAdminApproval"); ?> <?php echo T("Require administrator approval"); ?></label>
</div>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Member groups"); ?></label>
<div class='subText'><?php echo T("Groups can be used to categorize members and give them certain privileges."); ?></div>
<?php foreach ($groups as $k => $v) $groups[$k] = "<strong class='group-{$v["name"]}'>".groupName($v["name"])."</strong>";
echo implode(", ", $groups); ?><br/>
<a href='<?php echo URL("admin/groups"); ?>' id='manageGroupsLink'><?php echo T("Manage Groups"); ?></a>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Editing permissions"); ?></label>
<div class='subText'><?php echo T("Allow members to edit their own posts:"); ?></div>
<div class='checkboxGroup'>
<label class='radio'><?php echo $form->radio("editPostMode", "forever"); ?> <?php echo T("Forever"); ?></label>
<label class='radio'><?php echo $form->radio("editPostMode", "reply"); ?> <?php echo T("Until someone replies"); ?></label>
<label class='radio'><?php echo $form->radio("editPostMode", "custom"); ?> <?php printf(T("For %s seconds"), $form->input("editPostTimeLimit", "text", array("style" => "width:3em"))); ?></label>
</div>
</li>

<li class='sep'></li>

<li><?php echo $form->saveButton(); ?></li>

</ul>

<?php echo $form->close(); ?>

</div>
