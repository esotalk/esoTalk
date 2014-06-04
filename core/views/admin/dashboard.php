<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the admin dashboard, including forum statistics and an esoTalk news feed. Also initiates a check
 * for updates to the esoTalk software.
 *
 * @package esoTalk
 */
?>
<script>
$(function() {
	ETAdminDashboard.init();
});
</script>

<?php if (!empty($data["showWelcomeSheet"])): ?>
<div class='sheet' id='adminWelcomeSheet'>
<div class='sheetContent'>

<h3><?php echo T("Welcome to esoTalk!"); ?></h3>

<div class='section'>
<p><?php echo T("We've logged you in and taken you straight to your forum's administration panel. You're welcome."); ?></p>
<p><?php echo T("To get started with your forum, you might like to:"); ?></p>
<ul>
<li><a href='<?php echo URL("admin/appearance"); ?>'><?php echo T("Customize your forum's appearance"); ?></a></li>
<li><a href='<?php echo URL("admin/channels"); ?>'><?php echo T("Manage your forum's channels (categories)"); ?></a></li>
<li><a href='<?php echo URL("conversation/start"); ?>'><?php echo T("Start a new conversation"); ?></a></li>
</ul>
</div>

</div>
</div>
<?php endif; ?>

<?php $this->renderView("admin/updateNotification"); ?>

<div class='area' id='adminStatistics'>
<h3><?php echo T("Forum Statistics"); ?></h3>
<ul class='form'>
<?php foreach ($data["statistics"] as $k => $v): ?><li><label><?php echo $k; ?></label> <?php echo $v; ?></li><?php endforeach; ?>
<li class='sep'></li>
<li><label><?php echo T("esoTalk version"); ?></label> <?php echo ESOTALK_VERSION; ?></li>
<li><label><?php echo T("PHP version"); ?></label> <?php echo phpversion(); ?></li>
<li><label><?php echo T("SQLite version"); ?></label> <?php echo ET::SQL("SELECT sqlite_version()")->result(); ?></li>
</ul>
</div>

<div id='adminDashboard'>

<div class='area' id='adminNews'>
<h3><?php echo T("Latest News"); ?></h3>
<div class='loading'></div>
</div>

</div>