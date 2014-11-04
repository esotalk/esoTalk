<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows the "update notification" box at the top of the dashboard.
 *
 * @package esoTalk
 */
?>
<div id='adminUpdateNotification' class='area help'>

<?php
$info = C("esoTalk.admin.lastUpdateCheckInfo", array("version" => ESOTALK_VERSION));
if (version_compare($info["version"], ESOTALK_VERSION, ">")): ?>
<h3><?php printf(T("message.esoTalkUpdateAvailable"), $info["version"]); ?></h3>
<p><?php echo T("message.esoTalkUpdateAvailableHelp"); ?></p>
<p><a href='<?php echo $info["releaseNotes"]; ?>' target='_blank' class='button'><?php echo T("Upgrade Now"); ?></a></p>

<?php else: ?>
<h3><?php echo T("message.esoTalkUpToDate"); ?></h3>
<p><?php printf(T("message.esoTalkUpToDateHelp"), "http://esotalk.org/donate"); ?></p>
<?php endif; ?>

</div>
