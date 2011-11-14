<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet where the user can reset their password, following on from a link sent to them by the
 * forgot password process.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<div class='sheet' id='forgotSheet'>
<div class='sheetContent'>

<h3><?php echo T("Set a New Password"); ?></h3>

<?php echo $form->open(); ?>

<div class='section'>
<p class='help'><?php echo T("Alright! Now, what do you want your new password to be?"); ?></p>
<ul class='form'>
<li><label><?php echo T("New password"); ?></label> <?php echo $form->input("password", "password"); ?></li>
<li><label><?php echo T("Confirm password"); ?></label> <?php echo $form->input("confirm", "password"); ?></li>
</ul>
</div>

<div class='buttons'>
<?php echo $form->button("submit", T("Change Password"), array("class" => "big")); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>