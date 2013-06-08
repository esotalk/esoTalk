<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with options to delete a member.
 *
 * @package esoTalk
 */

$form = $data["form"];
$member = $data["member"];
?>
<div class='sheet' id='deleteMemberSheet'>
<div class='sheetContent'>

<?php echo $form->open(); ?>

<h3><?php echo T("Delete Member"); ?>: <?php echo $member["username"]; ?></h3>

<div class='sheetBody'>

<div class='section'>

<p class='radio'><label>
<?php echo $form->radio("deletePosts", false); ?>
<?php echo T("<strong>Keep this member's posts.</strong> All of this member's posts will remain intact, but will show [deleted] as the author."); ?>
</label></p>

<p class='radio'><label>
<?php echo $form->radio("deletePosts", true); ?>
<?php echo T("<strong>Delete this member's posts.</strong> All of this member's posts will be marked as deleted, but will be able to be restored manually."); ?>
</label></p>

</div>

</div>

<div class='buttons'>
<?php echo $form->button("delete", T("Delete Member"), array("class" => "big")); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>