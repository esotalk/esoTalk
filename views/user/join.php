<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the 'sign up' sheet.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<div id='joinSheet' class='sheet'>
<div class='sheetContent'>

<h3><?php echo T("Sign Up"); ?></h3>

<?php echo $form->open(); ?>

<div class='section'>
<ul class='form'>

<li><label><?php echo T("Username"); ?></label> <?php echo $form->input("username"); ?></li>

<li><label><?php echo T("Email"); ?></label> <?php echo $form->input("email"); ?><small><?php echo T("Used to verify your account and subscribe to conversations"); ?></small></li>

<li><label><?php echo T("Password"); ?></label> <?php echo $form->input("password", "password"); ?><small><?php printf(T("Choose a secure password of at least %s characters"), C("esoTalk.minPasswordLength")); ?></small></li>

<li><label><?php echo T("Confirm password"); ?></label> <?php echo $form->input("confirm", "password"); ?></li>

</ul>
</div>

<div class='buttons'>
<small><?php printf(T("Already have an account? <a href='%s' class='link-login'>Log in!</a>"), URL("user/login")); ?></small>
<?php
echo $form->button("submit", T("Sign Up"), array("class" => "big"));
echo $form->cancelButton();
?>
</div>

<?php echo $form->close(); ?>

</div>
</div>