<?php defined('IN_ESOTALK') or exit;
/**
 * Displays the settings form for the Gravatar plugin.
 * This file is part of esoTalk. Please see the included license file for usage information.
 * 
 * @package esoTalk
 * @copyright 2014 Toby Zerner, Simon Zerner
 */
$form = $data['gravatarSettingsForm'];
?>

<style type='text/css'>
#gravatarDefaults label {
    float: left;
    width: 40%;
    margin-bottom: 20px;
}
#gravatarDefaults img {
    display: block;
}
</style>

<?php echo $form->open() ?>

<div class='section'>
    <ul class='form'>
        <li>
            <label><?php echo T('Default imageset') ?></label>
            <div class='checkboxGroup' id='gravatarDefaults'>
                <label class='radio'>
                    <?php echo $form->radio('default', '') ?> 
                    <img src='<?php echo getResource('core/skin/avatar.png') ?>' class='avatar'> 
                    <?php echo T('esoTalk default') ?>
                </label>
                <label class='radio'>
                    <?php echo $form->radio('default', 'mm') ?> 
                    <img src='http://www.gravatar.com/avatar/0?d=mm' class='avatar'> 
                    <?php echo T('Mystery Man') ?>
                </label>
                <label class='radio'>
                    <?php echo $form->radio('default', 'identicon') ?> 
                    <img src='http://www.gravatar.com/avatar/0?d=identicon' class='avatar'> 
                    <?php echo T('Identicon') ?>
                </label>
                <label class='radio'>
                    <?php echo $form->radio('default', 'monsterid') ?> 
                    <img src='http://www.gravatar.com/avatar/0?d=monsterid' class='avatar'>
                    <?php echo T('MonsterID') ?>
                </label>
                <label class='radio'>
                    <?php echo $form->radio('default', 'wavatar') ?> 
                    <img src='http://www.gravatar.com/avatar/0?d=wavatar' class='avatar'> 
                    <?php echo T('Wavatar') ?>
                </label>
                <label class='radio'>
                    <?php echo $form->radio('default', 'retro') ?> 
                    <img src='http://www.gravatar.com/avatar/0?d=retro' class='avatar'> 
                    <?php echo T('Retro') ?>
                </label>
            </div>
        </li>
    </ul>
</div>

<div class='buttons'>
    <?php echo $form->saveButton() ?>
</div>

<?php echo $form->close() ?>
