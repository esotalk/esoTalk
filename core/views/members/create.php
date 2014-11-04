<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet containing a form to create a new member account.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<div class='sheet' id='createMemberSheet'>
<div class='sheetContent'>

<h3><?php echo T("Create Member"); ?></h3>

<?php echo $form->open(); ?>

<div class='sheetBody'>

<div class='section'>

<ul class='form'>

<li><label><?php echo T("Username"); ?></label> <?php echo $form->input("username"); ?></li>

<li><label><?php echo T("Email"); ?></label> <?php echo $form->input("email"); ?></li>

<li class='sep'></li>

<li><label><?php echo T("Password"); ?></label> <?php echo $form->input("password", "password"); ?><small><?php printf(T("Choose a secure password of at least %s characters"), C("esoTalk.minPasswordLength")); ?></small></li>

<li><label><?php echo T("Confirm password"); ?></label> <?php echo $form->input("confirm", "password"); ?></li>

</ul>

</div>

</div>

<div class='buttons'>
<?php echo $form->button("submit", T("Create Member"), array("class" => "big submit")); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>
