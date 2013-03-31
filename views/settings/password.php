<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the "change password or email" settings form.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<div id='settings-password'>

<?php echo $form->open(); ?>

<ul class='form'>

<li><label><?php echo T("Your current password"); ?></label> <?php echo $form->input("currentPassword", "password"); ?></li>

<li class='sep'></li>

<li><label><?php echo T("New password"); ?> <small>(<?php echo T("optional"); ?>)</small></label> <?php echo $form->input("password", "password"); ?></li>

<li><label><small><?php echo T("Confirm password"); ?></small></label> <?php echo $form->input("confirm", "password"); ?></li>

<li><label><?php echo T("New email"); ?> <small>(<?php echo T("optional"); ?>)</small></label> <?php echo $form->input("email"); ?></li>

<li class='sep'></li>

<li><?php echo $form->saveButton(); ?></li>

</ul>

<?php echo $form->close(); ?>

</div>