<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

$form = $data["wordFilterSettingsForm"];
?>
<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label>Word filters</label>
<?php echo $form->input("filters", "textarea", array("style" => "height:200px; width:350px")); ?>
<small>Enter each word on a new line. Optionally specify a replacement after a vertical bar (|) character; otherwise, the word will be replaced with asterisks (*). Words are case-insensitive. Regular expressions are allowed.</small>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton("wordFilterSave"); ?>
</div>

<?php echo $form->close(); ?>
