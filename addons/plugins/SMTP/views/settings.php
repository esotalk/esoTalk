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
<label>Server</label>
<?php echo $form->input("server", "text"); ?>
</li>

<li>
<label>Username</label>
<?php echo $form->input("username", "text"); ?>
</li>


<li>
<label>Password</label>
<?php echo $form->input("password", "password"); ?>
</li>

<li>
<label>Port</label>
<?php echo $form->input("port", "text"); ?>
</li>

<li>
<label>Authentication</label>
<div class='checkboxGroup'>
	<label class='radio'><?php echo $form->radio("auth", "false"); ?> Normal</label>
	<label class='radio'><?php echo $form->radio("auth", "tls"); ?> TLS</label>
	<label class='radio'><?php echo $form->radio("auth", "ssl"); ?> SSL</label>
</div>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton("smtpSave"); ?>
</div>

<?php echo $form->close(); ?>
