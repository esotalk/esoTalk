<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a single conversation row in the context of a list of results.
 *
 * @package esoTalk
 */

$conversation = $data["conversation"];

// Work out the class name to apply to the row.
$className = "channel-".$conversation["channelId"];
if ($conversation["starred"]) $className .= " starred";
if ($conversation["unread"] and ET::$session->user) $className .= " unread";
if ($conversation["startMemberId"] == ET::$session->user) $className .= " mine";

?>
<li id='c<?php echo $conversation["conversationId"]; ?>' class='<?php echo $className; ?>'>
<?php if (ET::$session->user): ?>
<div class='col-star'><?php echo star($conversation["conversationId"], $conversation["starred"]); ?></div>
<?php endif; ?>
<div class='col-conversation'><?php
$conversationURL = conversationURL($conversation["conversationId"], $conversation["title"]);

// Output the conversation title, highlighting search keywords.
echo "<strong class='title'><a href='".URL($conversationURL)."'>".highlight(sanitizeHTML($conversation["title"]), ET::$session->get("highlight"))."</a></strong> ";

// Output the conversation's labels.
echo "<span class='labels'>";
foreach ($conversation["labels"] as $label) {
	echo "<span class='label label-$label'>".T("label.$label")."</span> ";
}
echo "</span> ";

// Output an "unread indicator", showing the number of unread posts.
if (ET::$session->user and $conversation["unread"])
	echo "<a href='".URL("conversation/markAsRead/".$conversation["conversationId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL))."' class='unreadIndicator' title='".T("Mark as read")."'>".$conversation["unread"]."</a> ";

// Output controls which apply to this conversation.
echo "<span class='controls'>";

// A Jump to last/unread link, depending on the user and the unread state.
if (ET::$session->user and $conversation["unread"])
	echo "<a href='".URL($conversationURL."/unread")."' class='jumpToUnread'>".T("Jump to unread")."</a>";
else
	echo "<a href='".URL($conversationURL."/last")."' class='jumpToLast'>".T("Jump to last")."</a>";

// If we're highlighting search terms (i.e. if we did a fulltext search), then output a "show matching posts" link.
if (ET::$session->get("highlight"))
	echo " <a href='".URL($conversationURL."/?search=".urlencode($data["fulltextString"]))."' class='showMatchingPosts'>".T("Show matching posts")."</a>";

echo "</span>";
?></div>
<div class='col-channel'><?php
$channel = $data["channelInfo"][$conversation["channelId"]];
echo "<a href='".URL(searchURL("", $channel["slug"]))."' class='channel channel-{$conversation["channelId"]}' data-channel='{$channel["slug"]}'>{$channel["title"]}</a>";
?></div>
<div class='col-lastPost'><?php
echo "<span class='action'>".avatar(array(
		"memberId" => $conversation["lastPostMemberId"],
		"avatarFormat" => $conversation["lastPostMemberAvatarFormat"],
		"email" => $conversation["lastPostMemberEmail"]
	), "thumb"), " ",
	sprintf(T("%s posted %s"),
		"<span class='lastPostMember name'>".memberLink($conversation["lastPostMemberId"], $conversation["lastPostMember"])."</span>",
		"<span class='lastPostTime'>".relativeTime($conversation["lastPostTime"], true)."</span>"),
	"</span>";
?></div>
<div class='col-replies'><?php
echo "<span>".Ts("%s reply", "%s replies", $conversation["replies"])."</span>";
?></div>
</li>