<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a full list of members allowed to view a conversation.
 * 
 * @package esoTalk
 */

$conversation = $data["conversation"];

$names = array();
$count = count($conversation["membersAllowed"]);

// Go through the list of members/groups allowed and construct an array of formatted names.
foreach ($conversation["membersAllowed"] as $member) {

	// Format the entity's name depending on its type.
	if ($member["type"] == "group") {
		$member["avatarFormat"] = null;
		$member["name"] = groupName($member["name"], true);
	}
	else {
		$member["name"] = name($member["name"]);
	}

	// Add the avatar.
	$name = "<span class='name'>".avatar($member["id"], $member["avatarFormat"], "thumb");

	// If we're able to remove entities from the list, wrap the name in links that will remove them.
	if (!empty($data["editable"])) {

		// Make the entity for the owner of the conversation non-removable unless it's the last name left.
		if ($count == 1 or $member["id"] != $conversation["startMemberId"] or $member["type"] != "member")
			$name .= "<a href='".URL("conversation/removeMember/{$conversation["conversationId"]}?{$member["type"]}={$member["id"]}&token=".ET::$session->token)."' class='deleteLink' data-type='{$member["type"]}' data-id='{$member["id"]}'>{$member["name"]}</a>";
		else $name .= $member["name"];

	}

	// Otherwise, wrap the names in links that go to their profile page.
	else $name .= $member["type"] == "member" ? memberLink($member["id"], $member["name"]) : groupLink($member["name"]);

	$name .= "</span>";
	$names[] = $name;
}

// Output the list of names.
if (count($names))
	echo implode(" ", $names);

else
	printf(T("%s ".($conversation["countPosts"] > 0 ? "can" : "will be able to")." view this conversation."), T("Everyone"));

?>