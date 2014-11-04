<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='sheet' id='onlineSheet'>
<div class='sheetContent'>

<h3><?php echo T("Members Who Liked This Post"); ?><?php if (count($data["members"])) echo " (".count($data["members"]).")"; ?></h3>

<?php
// If there are members online, list them.
if (count($data["members"])): ?>

<div class='section' id='onlineList'>

<ul class='list'>
<?php foreach ($data["members"] as $memberId => $member): ?>
<li>
<span class='action'>
<?php echo avatar($memberId, $member["avatarFormat"], "thumb"), " ", memberLink($memberId, $member["username"]), " "; ?>
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
</div>
</div>

<?php endif; ?>

</div>
</div>