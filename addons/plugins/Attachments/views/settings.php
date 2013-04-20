<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Attachments plugin.
 *
 * @package esoTalk
 */

$form = $data["attachmentsSettingsForm"];
?>
<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label>Allowed file types</label>
<?php echo $form->input("allowedFileTypes", "text"); ?>
<small><?php echo T("Enter file extensions separated by a space. Leave blank to allow all file types."); ?></small>
</li>

<li>
<label>Max file size</label>
<?php echo $form->input("maxFileSize", "text"); ?>
<small><?php echo T("In bytes. Leave blank for no limit."); ?></small>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton("attachmentsSave"); ?>
</div>

<?php echo $form->close(); ?>
