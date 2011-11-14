<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a login sheet/form.
 * 
 * @package esoTalk
 */

$form = $data["form"];
?>

<div id='loginSheet' class='sheet'>
<div class='sheetContent'>

<h3><?php echo T("Log In"); ?></h3>

<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>
<li><label><?php echo T("Username or Email"); ?></label> <?php echo $form->input("username"); ?></li>
<li><label><?php echo T("Password"); ?> <small><a href='<?php echo URL("user/forgot"); ?>' class='link-forgot' tabindex='-1'><?php echo T("Forgot?"); ?></a></small></label> <?php echo $form->input("password", "password"); ?></li>
<li><div class='checkboxGroup'><label class='checkbox'><?php echo $form->checkbox("remember"); ?> <?php echo T("Keep me logged in"); ?></label></div></li>
</ul>

</div>

<div class='buttons'>
<small><?php printf(T("Don't have an account? <a href='%s' class='link-join'>Sign up!</a>"), URL("user/join")); ?></small>
<?php
echo $form->button("login", T("Log In"), array("class" => "big"));
echo $form->cancelButton();
?> 
</div>

<?php echo $form->close(); ?>

</div>
</div>