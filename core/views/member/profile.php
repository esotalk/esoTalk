<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Member profile view. Displays the header area on a member's profile, pane tabs, and the current pane.
 *
 * @package esoTalk
 */

$member = $data["member"];
?>

<div class='bodyHeader clearfix' id='memberProfile'>

<?php echo avatar($member); ?>

<div id='memberInfo'>

<h1 id='memberName'><?php echo name($member["username"]); ?></h1>

<?php
// Online indicator.
if (empty($member["preferences"]["hideOnline"])):
	$lastAction = ET::memberModel()->getLastActionInfo($member["lastActionTime"], $member["lastActionDetail"]);
	if ($lastAction) echo "<".(!empty($lastAction[1]) ? "a href='{$lastAction[1]}'" : "span")." class='online' title='".T("Online").($lastAction[0] ? " (".sanitizeHTML($lastAction[0]).")" : "")."'>".T("Online")."</".(!empty($lastAction[1]) ? "a" : "span").">";
endif;
?>

<?php
// Output the email if the viewer is an admin.
if (ET::$session->isAdmin()): ?><p class='subText'><?php echo sanitizeHTML($member["email"]); ?></p><?php endif; ?>

<p id='memberGroup' class='subText'><?php echo memberGroup($member["account"], $member["groups"], true); ?></p>
<p id='memberLastActive' class='subText'><?php printf(T("Last active %s"), empty($member["preferences"]["hideOnline"])
	? "<span title='".date(T("date.full"), $member["lastActionTime"])."'>".relativeTime($member["lastActionTime"], true)."</span>"
	: "[".T("hidden")."]"); ?></p>

</div>

<?php
// Output the member actions menu.
if ($data["actions"]->count()): ?>
<ul id='memberActions'>
<?php echo $data["actions"]->getContents(); ?>
</ul>
<?php endif; ?>

</div>

<?php
// Output the member controls menu.
if ($data["controls"]->count()): ?>
<ul id='memberControls' class='controls'>
<?php echo $data["controls"]->getContents(); ?>
</ul>
<?php endif; ?>

<ul id='memberPanes' class='area tabs big'>
<?php echo $data["panes"]->getContents(); ?>
</ul>

<div id='memberContent'>
<?php $this->renderView($data["view"], $data); ?>
</div>