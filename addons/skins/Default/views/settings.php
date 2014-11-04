<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Default skin.
 *
 * @package esoTalk
 */

$form = $data["skinSettingsForm"];
?>

<?php echo $form->open(); ?>

<ul class='form'>

<li class='sep'></li>

<li id='primaryColor'>
<label><?php echo T("Primary color"); ?></label>
<?php echo $form->input("primaryColor", "text", array("class" => "color")); ?> <a href='#' class='reset'><?php echo T("Reset"); ?></a>
</li>

<li class='sep'></li>

<li><?php echo $form->saveButton(); ?></li>
</ul>

<?php echo $form->close(); ?>

<script>
$(function() {

	// Turn the "primary color" field into a color picker.
	colorPicker("primaryColor");

});
</script>
