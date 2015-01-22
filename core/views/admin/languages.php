<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of installed languages.
 *
 * @package esoTalk
 */
?>
<script>
$(function() {
	ETAdminLanguages.init();
});
</script>


<?php // If there are installed languages to display...
if (count($data["languages"])): ?>

<div class='area' id='languages'>
<h3><?php echo T("Installed Languages"); ?></h3>

<ul id='languageList'>

<?php // Loop through each language and output its information.
foreach ($data["languages"] as $k => $language): ?>
<li id='language-<?php echo $k; ?>' class='language thing'>

<ul class='controls' id='languageControls-<?php echo $k; ?>'>
<li><span><?php printf(T("By %s"), "<a href='{$language["authorURL"]}'>{$language["author"]}</a>"); ?></span></li>
<li class='sep'></li>
<li><a href='<?php echo URL("admin/languages/uninstall/$k?token=".ET::$session->token); ?>'><?php echo T("Uninstall"); ?></a></li>
</ul>

<?php if (file_exists(PATH_LANGUAGES."/$k/icon.png")): ?><img src='<?php echo getWebPath(PATH_LANGUAGES."/$k/icon.png"); ?>' alt=''/><?php endif; ?>
<strong><?php echo $language["name"]; ?></strong>
<small class='version'><?php echo $language["version"]; ?></small>
<small class='description'><?php echo $language["description"]; ?></small>

</li>
<?php endforeach; ?>

</ul>
</div>

<?php // Otherwise if there are no languages installed, show a message.
else: ?>
<p class='help'><?php echo T("message.noLanguagesInstalled"); ?></p>
<?php endif; ?>