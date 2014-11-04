<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of members in the context of the member list.
 *
 * @package esoTalk
 */
?>
<?php foreach ($data["members"] as $member): ?>
<li data-index='<?php echo ctype_alpha($letter = strtolower($member["username"][0])) ? $letter : "0"; ?>'>

<div class='col-member'>
<?php echo avatar($member["memberId"], $member["avatarFormat"], "thumb"); ?>
<strong><?php echo memberLink($member["memberId"], $member["username"]); ?></strong>

<?php
// Online indicator.
$lastAction = ET::memberModel()->getLastActionInfo($member["lastActionTime"], $member["lastActionDetail"]);
if ($lastAction) echo "<".(!empty($lastAction[1]) ? "a href='{$lastAction[1]}'" : "span")." class='online' title='".T("Online").($lastAction[0] ? " (".sanitizeHTML($lastAction[0]).")" : "")."'>".T("Online")."</".(!empty($lastAction[1]) ? "a" : "span").">";
?>

<span class='group subText'><?php echo memberGroup($member["account"], $member["groups"]); ?></span>

<?php if (ET::$session->user): ?><a href='<?php echo URL("conversation/start/".urlencode($member["username"])."?token=".ET::$session->token); ?>' class='controls label label-private'><?php echo T("label.private"); ?></a><?php endif; ?>
</div>

<div class='col-lastActive'>
<span class='subText'><?php printf(T("Last active %s"), "<span title='".date(T("date.full"), $member["lastActionTime"])."'>".relativeTime($member["lastActionTime"], true)."</span>"); ?></span>
</div>

<div class='col-replies'>
<span class='subText'><?php echo Ts("%s post", "%s posts", $member["countPosts"]); ?></span>
</div>

</li>
<?php endforeach; ?>