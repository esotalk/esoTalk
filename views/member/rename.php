<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet to choose a new name for a member.
 * 
 * @package esoTalk
 */

$member = $data["member"];
$form = $data["form"];
?>
<div class='sheet' id='renameMemberSheet'>
<div class='sheetContent'>

<?php echo $form->open(); ?>

<h3><?php echo T("Rename Member"); ?>: <?php echo $member["username"]; ?></h3>

<div class='section'>
<ul class='form'>
<li><label><?php echo T("New username"); ?></label> <?php echo $form->input("username"); ?></li>
</ul>
</div>

<div class='buttons'>
<?php echo $form->saveButton(); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>

</div>