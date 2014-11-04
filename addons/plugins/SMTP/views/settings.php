<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Proto skin.
 *
 * @package esoTalk
 */

$form = $data["smtpSettingsForm"];
?>
<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label><?php echo T("Server"); ?></label>
<?php echo $form->input("server", "text"); ?>
</li>

<li>
<label><?php echo T("Username"); ?></label>
<?php echo $form->input("username", "text"); ?>
</li>


<li>
<label><?php echo T("Password"); ?></label>
<?php echo $form->input("password", "password"); ?>
</li>

<li>
<label><?php echo T("Port"); ?></label>
<?php echo $form->input("port", "text"); ?>
</li>

<li>
<label><?php echo T("Authentication"); ?></label>
<div class='checkboxGroup'>
	<label class='radio'><?php echo $form->radio("auth", "false"); ?> <?php echo T("Normal"); ?></label>
	<label class='radio'><?php echo $form->radio("auth", "tls"); ?> <?php echo T("TLS"); ?></label>
	<label class='radio'><?php echo $form->radio("auth", "ssl"); ?> <?php echo T("SSL"); ?></label>
</div>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton("smtpSave"); ?>
</div>

<?php echo $form->close(); ?>
