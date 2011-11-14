<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the online members sheet.
 *
 * @package esoTalk
 */
?>
<div class='sheet' id='onlineSheet'>
<div class='sheetContent'>

<h3><?php echo T("Members Online"); ?><?php if (count($data["members"])) echo " (".count($data["members"]).")"; ?></h3>

<?php
// If there are members online, list them.
if (count($data["members"])): ?>

<div class='section' id='onlineList'>

<ul class='list'>
<?php foreach ($data["members"] as $member): ?>
<li>
<span class='action'>
<?php echo avatar($member["memberId"], $member["avatarFormat"], "thumb"); ?>
<?php echo memberLink($member["memberId"], $member["username"]); ?>
<?php
$action = ET::memberModel()->getLastActionInfo($member["lastActionTime"], $member["lastActionDetail"]);
if ($action[0]) printf(T("is %s"), (!empty($action[1]) ? "<a href='{$action[1]}'>" : "").lcfirst(sanitizeHTML($action[0])).(!empty($action[1]) ? "</a>" : ""));
?>
</span>
</li>
<?php endforeach; ?>
</ul>

</div>

<?php
// Otherwise, display a 'no members online' message.
else: ?>

<div class='section'>
<div class='noResults help'>
<?php echo T("message.noMembersOnline"); ?>
</div>
</div>

<?php endif; ?>

</div>
</div>