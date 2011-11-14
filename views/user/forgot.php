<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the "forgot password" - a form where the user can enter their email address in order to reset 
 * their password.
 * 
 * @package esoTalk
 */

$form = $data["form"];
?>
<div class='sheet' id='forgotSheet'>
<div class='sheetContent'>

<h3><?php echo T("Forgot Password"); ?></h3>

<?php echo $form->open(); ?>

<div class='section'>
<p class='help'><?php echo T("message.forgotPasswordHelp"); ?></p>
<ul class='form'>
<li><label><?php echo T("Email"); ?></label> <?php echo $form->input("email"); ?></li>
</ul>
</div>

<div class='buttons'>
<?php echo $form->button("submit", T("Recover Password"), array("class" => "big")); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>