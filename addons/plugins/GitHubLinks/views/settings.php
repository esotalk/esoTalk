<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

$form = $data["gitHubLinksSettingsForm"];
?>
<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label>GitHub repository</label>
<?php echo $form->input("repository"); ?>
<small>Format: user/repository. e.g. esotalk/esoTalk</small>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton(); ?>
</div>

<?php echo $form->close(); ?>
