<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of members in the context of the member list.
 *
 * @package esoTalk
 */

$member = $data["member"];
?>
<li data-index='<?php echo ctype_alpha($letter = strtolower($member["username"][0])) ? $letter : "0"; ?>'>

<div class='col-member'>
<?php echo avatar($member, "thumb"); ?>
<strong><?php echo memberLink($member["memberId"], $member["username"]); ?></strong>

<?php
// Online indicator.
if (empty($member["preferences"]["hideOnline"])):
	$lastAction = ET::memberModel()->getLastActionInfo($member["lastActionTime"], $member["lastActionDetail"]);
	if ($lastAction) echo "<".(!empty($lastAction[1]) ? "a href='{$lastAction[1]}'" : "span")." class='online' title='".T("Online").($lastAction[0] ? " (".sanitizeHTML($lastAction[0]).")" : "")."'><i class='icon-circle'></i></".(!empty($lastAction[1]) ? "a" : "span").">";
endif;
?>

<span class='group subText'><?php echo memberGroup($member["account"], $member["groups"]); ?></span>

</div>

<div class='col-lastActive'>
<span class='subText'><?php printf(T("Last active %s"), empty($member["preferences"]["hideOnline"])
	? "<span title='".strftime(T("date.full"), $member["lastActionTime"])."'>".relativeTime($member["lastActionTime"], true)."</span>"
	: "[".T("hidden")."]"); ?></span>
</div>

<div class='col-replies'>
<span class='subText'><?php echo Ts("%s post", "%s posts", $member["countPosts"]); ?></span>
</div>

</li>
