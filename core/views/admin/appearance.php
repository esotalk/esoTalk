<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the appearance settings page.
 *
 * @package esoTalk
 */
?>
<script>
$(function() {
	ETAdminSkins.init();
});
</script>

<?php // If there are installed skins to display...
if (count($data["skins"])): ?>

<div class='area' id='skins'>
<h3><?php echo T("Installed Skins"); ?></h3>

<ul id='skinList'>

<?php // Loop through each skin and output its information.
foreach ($data["skins"] as $k => $skin): ?>
<li id='skin-<?php echo $k; ?>' class='skin<?php if ($skin["selected"]): ?> thing enabled<?php endif; ?>'>

<ul class='controls' id='skinControls-<?php echo $k; ?>'>
<li><span><?php printf(T("By %s"), "<a href='{$skin["info"]["authorURL"]}'>{$skin["info"]["author"]}</a>"); ?></span></li>
<li class='sep'></li>
<?php if (!$skin["selectedMobile"]): ?><li><a href='<?php echo URL("admin/appearance/activateMobile/$k?token=".ET::$session->token); ?>'><?php echo T("Use for mobile"); ?></a></li><?php endif; ?>
<li><a href='<?php echo URL("admin/appearance/uninstall/$k?token=".ET::$session->token); ?>'><?php echo T("Uninstall"); ?></a></li>
</ul>

<div class='preview'>
<?php if (file_exists(PATH_SKINS."/$k/preview.jpg")): ?><img src='<?php echo getResource("addons/skins/$k/preview.jpg"); ?>' alt='<?php echo $k; ?>'/>
<?php else: ?><span><?php echo T("No preview"); ?></span><?php endif; ?>
</div>

<div class='controls skinControls'>
<?php if ($skin["selectedMobile"]): ?><span class='icon-mobile' title='<?php echo T("Mobile skin"); ?>'></span><?php endif; ?>
<?php if (!$skin["selected"]): ?><a href='<?php echo URL("admin/appearance/activate/$k?token=".ET::$session->token); ?>' class='button toggle'><?php echo T("Activate"); ?></a><?php endif; ?>
</div>

<strong><?php echo $skin["info"]["name"]; ?></strong>
<small class='version'><?php echo $skin["info"]["version"]; ?></small>

</li>
<?php endforeach; ?>

</ul>
</div>

<?php // Otherwise if there are no skins installed, show a message.
else: ?>
<?php echo T("message.noSkinsInstalled"); ?>
<?php endif; ?>


<?php if (!empty($data["skin"]["settingsView"])): ?>
<!-- Skin Settings -->
<div class='area' id='skinSettings'>
<h3><?php printf(T("%s Settings"), $data["skin"]["info"]["name"]); ?></h3>

<?php $this->renderView($data["skin"]["settingsView"], $data); ?>

</div>
<?php endif; ?>