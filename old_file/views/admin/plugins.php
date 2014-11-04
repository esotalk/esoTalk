<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of installed plugins.
 *
 * @package esoTalk
 */
?>
<script>
$(function() {
	ETAdminPlugins.init();
});
</script>


<?php // If there are installed plugins to display...
if (count($data["plugins"])): ?>

<div class='area' id='plugins'>
<h3><?php echo T("Installed Plugins"); ?></h3>

<ul id='pluginList'>

<?php // Loop through each plugin and output its information.
foreach ($data["plugins"] as $k => $plugin): ?>
<li id='plugin-<?php echo $k; ?>' class='plugin<?php if ($plugin["loaded"]): ?> thing enabled<?php endif; ?>'>

<ul class='controls' id='pluginControls-<?php echo $k; ?>'>
<li><span><?php printf(T("By %s"), "<a href='{$plugin["info"]["authorURL"]}'>{$plugin["info"]["author"]}</a>"); ?></span></li>
<li class='sep'></li>
<li><a href='<?php echo URL("admin/plugins/uninstall/$k?token=".ET::$session->token); ?>'><?php echo T("Uninstall"); ?></a></li>
</ul>

<div class='controls pluginControls'>
<span class='buttonGroup'>
<?php if ($plugin["settingsView"]): ?><a href='<?php echo URL("admin/plugins/settings/$k"); ?>' class='button pluginSettings' data-plugin='<?php echo $k; ?>'><?php echo T("Settings"); ?></a> <?php endif; ?>
<a href='<?php echo URL("admin/plugins/toggle/$k?token=".ET::$session->token); ?>' class='button toggle'><?php echo $plugin["loaded"] ? "<span class='icon-tick'></span> ".T("Disable") : T("Enable"); ?></a>
</span>
</div>

<?php if (file_exists(PATH_PLUGINS."/$k/icon.png")): ?><img src='<?php echo getResource("plugins/$k/icon.png"); ?>' alt=''/><?php endif; ?>
<strong><?php echo $plugin["info"]["name"]; ?></strong>
<small class='version'><?php echo $plugin["info"]["version"]; ?></small>
<small class='description'><?php echo $plugin["info"]["description"]; ?></small>

</li>
<?php endforeach; ?>

</ul>
</div>

<?php // Otherwise if there are no plugins installed, show a message.
else: ?>
<p class='help'><?php echo T("message.noPluginsInstalled"); ?></p>
<?php endif; ?>