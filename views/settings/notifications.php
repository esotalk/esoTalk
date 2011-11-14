<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of notifications, for use in the notifications settings pane and also in the notifications
 * popup.
 * 
 * @package esoTalk
 */
?>

<?php if (!empty($data["notifications"])): ?>

<ul class='list notificationsList'>

<?php
// Loop through the notifications and output them!
foreach ($data["notifications"] as $k => $notification): ?>
<li class='notification-<?php echo $notification["type"]; ?><?php if ($notification["unread"]): ?> unread<?php endif; ?>'>
<a href='<?php echo @$notification["link"]; ?>'>
<?php echo avatar($notification["fromMemberId"], $notification["avatarFormat"], "thumb"); ?>
<small class='time'><?php echo ucfirst(relativeTime($notification["time"], true)); ?></small>
<div class='action'><?php echo $notification["body"]; ?></div>
</a>
</li>
<?php endforeach; ?>

<?php if (!empty($this->data["showViewAll"])): ?>
<li id='viewAllNotifications'><a href='<?php echo URL("settings/notifications"); ?>'><?php echo T("View all notifications"); ?> &raquo;</a></li>
<?php endif; ?>
</ul>

<?php else: ?>
<p class='help'><?php echo T("message.noNotifications"); ?></p>
<?php endif; ?>