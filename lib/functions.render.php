<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Render functions. Contains functions that generally return common elements to be rendered to the page, such
 * as avatars, member profile links, member names, group names, and stars.
 *
 * All of the functions in this file are wrapped with function_exists checks, so plugins and skins can override
 * them in their plugin.php or skin.php files if needed.
 *
 * @package esoTalk
 */

if (!function_exists("highlight")) {

/**
 * Find a list of words in a block of text and put spans with class='highlight' around them.
 *
 * @param string $text The text to highlight words in.
 * @param array $words The words to highlight.
 * @return string The highlighted text.
 */
function highlight($text, $words)
{
	if (!is_array($words)) return $text;
	foreach ($words as $word) {
		if (!($word = trim($word))) continue;

		// Make sure we only highlight whole words that would've been matched by a fulltext search.
		$text = preg_replace("/(?<=[\s>]|^)(".preg_quote($word, "/").")(?=[\s<,\.?!:\/-]|$)/iu", "<span class='highlight'>$1</span>", $text);

	}
	return $text;
}

}


if (!function_exists("memberLink")) {

/**
 * Return a member's name wrapped in an anchor tag linking to their profile page.
 *
 * @param int $memberId The ID of the member.
 * @param string $username The username of the member.
 * @return string
 */
function memberLink($memberId, $username = "")
{
	$displayName = name($username);
	if ($username) return "<a href='".URL(memberURL($memberId, $username))."' title='".sprintf(sanitizeHTML(T("View %s's profile")), $displayName)."'>$displayName</a>";
	else return $displayName;
}

}


if (!function_exists("name")) {

/**
 * Return a member's name to be displayed in an HTML context. If the name is blank, it is assumed the member
 * has been deleted, and "[deleted]" is returned.
 *
 * @param string $username The member's username.
 * @return string
 */
function name($username)
{
	if (!$username) $username = "[".T("deleted")."]";
	return sanitizeHTML($username);
}

}


if (!function_exists("avatar")) {

/**
 * Return an image tag containing a member's avatar.
 *
 * @param array $member An array of the member's details. (memberId is required in this implementation.)
 * @param string $avatarFormat The format of the member's avatar (as stored in the database - jpg|gif|png.)
 * @param string $className CSS class names to apply to the avatar.
 */
function avatar($member = array(), $className = "")
{
	// Otherwise, construct the avatar path from the provided information.
	if (!empty($member["memberId"]) and !empty($member["avatarFormat"])) {
		$file = "uploads/avatars/{$member["memberId"]}.{$member["avatarFormat"]}";
		$url = getWebPath($file);
	}

	// If the user doesn't have an avatar, return the skin's default one.
	if (!$avatarFormat) $url = getResource("skins/base/avatar.png");

	return "<img src='$url' alt='' class='avatar $className'/>";
}

}


if (!function_exists("memberGroup")) {

/**
 * Return a comma-separated list of groups that a member is in.
 *
 * @param string $account The member's account type.
 * @param array $groups An array of group names that the member is in.
 * @param bool $showMember Whether or not to show "Member" if the member's account is Member and they aren't
 * 		in any groups, or to just show nothing.
 * @return string
 */
function memberGroup($account, $groups = array(), $showMember = false)
{
	// If the member isn't a Member, groups don't matter - just display their account type.
	if ($account and $account != ACCOUNT_MEMBER) return "<span class='group-$account'>".groupName($account)."</span>";
	else {

		// Otherwise, show a comma-separated list of the groups that they're in.
		$groups = array_filter((array)$groups);
		if (count($groups)) {
			foreach ($groups as $k => $v) $groups[$k] = "<span class='group-$v'>".groupName($v)."</span>";
			return implode(", ", $groups);
		}

		// If they're not in any groups, either show them as a "Member" or just show nothing at all.
		else return $showMember ? groupName(ACCOUNT_MEMBER) : "";

	}
}

}



if (!function_exists("groupName")) {

/**
 * Return a group's name to be displayed in an HTML context.
 *
 * @param string $group The name of the group.
 * @param bool $plural Whether or not a plurala version of the name should be used (if such a translation exists.)
 * @return string
 */
function groupName($group, $plural = false)
{
	return sanitizeHTML(T("group.$group".($plural ? ".plural" : ""), ucfirst($group)));
}

}



if (!function_exists("groupLink")) {

/**
 * Return a group's name wrapped in an anchor linking to a list of members in that group.
 *
 * @param string $group The name of the group.
 * @return string
 */
function groupLink($group)
{
	return "<a href='".URL("members/?search=".urlencode($group))."'>".groupName($group, true)."</a>";
}

}


if (!function_exists("star")) {

/**
 * Return a star for a certain conversation that can be clicked to toggle the starred that of that conversation.
 *
 * @param int $conversationId The ID of the conversation that this star is for.
 * @param bool $starred Whether or not the conversation is currently starred.
 * @return stiring
 */
function star($conversationId, $starred)
{
	// If the user is not logged in, don't return anything.
	if (!ET::$session->user) return "";

	// Otherwise, return a clickable star!
	else {
		$conversationId = (int)$conversationId;
		$url = URL("conversation/star/".$conversationId."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL));
		return "<a href='$url' class='star".($starred ? " starOn" : "")."' title='".T("Star to receive notifications")."' data-id='$conversationId'>".($starred ? T("Starred") : T("Unstarred"))."</a>";
	}
}

}