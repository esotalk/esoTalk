<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Gravatar plugin.
 *
 * @package esoTalk
 */

$form = $data["gravatarSettingsForm"];
?>
<style>
#gravatarDefaults label {
	float:left;
	width:40%;
	margin-bottom:20px;
}
#gravatarDefaults img {
	display:block;
}
</style>

<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label>Default imageset</label>
<div class='checkboxGroup' id='gravatarDefaults'>
	<label class='radio'><?php echo $form->radio("default", ""); ?> <img src='<?php echo getResource("skins/base/avatar.png"); ?>' class='avatar'> esoTalk default</label>
	<label class='radio'><?php echo $form->radio("default", "mm"); ?> <img src='http://www.gravatar.com/avatar/0?d=mm' class='avatar'> Mystery Man</label>
	<label class='radio'><?php echo $form->radio("default", "identicon"); ?> <img src='http://www.gravatar.com/avatar/0?d=identicon' class='avatar'> Identicon</label>
	<label class='radio'><?php echo $form->radio("default", "monsterid"); ?> <img src='http://www.gravatar.com/avatar/0?d=monsterid' class='avatar'> MonsterID</label>
	<label class='radio'><?php echo $form->radio("default", "wavatar"); ?> <img src='http://www.gravatar.com/avatar/0?d=wavatar' class='avatar'> Wavatar</label>
	<label class='radio'><?php echo $form->radio("default", "retro"); ?> <img src='http://www.gravatar.com/avatar/0?d=retro' class='avatar'> Retro</label>
</div>
</li>

</ul>

</div>

<div class='buttons'>
<?php echo $form->saveButton(); ?>
</div>

<?php echo $form->close(); ?>
