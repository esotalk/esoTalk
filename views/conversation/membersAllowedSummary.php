<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a summary of the members allowed in a conversation.
 * For example: Toby, Administrators, and 2 others can view this conversation.
 *
 * @package esoTalk
 */

$conversation = $data["conversation"];

$names = array();
$avatars = array();
$model = ET::memberModel();

// Go through the list of members/groups allowed and construct an array of formatted names.
foreach ($conversation["membersAllowedSummary"] as $member) {

	// If this entity is a member, add their name as a link to their profile. Also add an avatar to be
	// displayed at the start of the list.
	if ($member["type"] == "member") {
		$names[] = "<span class='name'>".memberLink($member["id"], $member["name"])."</span>";
		if (count($avatars) < 3) $avatars[] = avatar($member + array("memberId" => $member["id"]), "thumb");
	}

	// For groups, just display a plain ol' group name.
	else {
		$names[] = "<span class='name'>".groupLink($member["name"])."</span>";
	}

}

// If there are specific names, output the list!
if (count($names)) {

	// Output a few avatars at the start.
	echo "<span class='avatars'>".implode(" ", $avatars)."</span> ";

	// If there's more than one name, construct the list so that it has the word "and" in it.
	if (count($names) > 1) {

		// If there're more than 3 names, chop off everything after the first 3 and replace them with a
		// "x others" link.
		if (count($names) > 3) {
			$otherNames = array_splice($names, 3);
			$lastName = "<a href='#' class='showMore name'>".sprintf(T("%s others"), count($otherNames))."</a>";
		} else {
			$lastName = array_pop($names);
		}

		printf(T("%s ".($conversation["countPosts"] > 0 ? "can" : "will be able to")." view this conversation."), sprintf(T("%s and %s"), implode(", ", $names), $lastName));
	}

	// If there's only one name, we don't need to do anything gramatically fancy.
	else {
		printf(T("%s ".($conversation["countPosts"] > 0 ? "can" : "will be able to")." view this conversation."), $names[0]);
	}

}

// If there are no names, assume that everyone can view the conversation.
else {
	printf(T("%s ".($conversation["countPosts"] > 0 ? "can" : "will be able to")." view this conversation."), T("Everyone"));
}

?>