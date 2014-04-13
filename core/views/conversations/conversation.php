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

// Subtract the member name if it is longer then 14 characters to prevent layout braking.
if (strlen($conversation["lastPostMember"]) > 14) $conversation["lastPostMember"] = substr($conversation["lastPostMember"],0,11)."...";

?>
<li id='c<?php echo $conversation["conversationId"]; ?>' class='<?php echo $className; ?>'>
<?php if (ET::$session->user): ?>
<div class='col-star'><?php echo star($conversation["conversationId"], $conversation["starred"]); ?></div>
<?php endif; ?>
<div class='col-conversation'><?php
$conversationURL = conversationURL($conversation["conversationId"], $conversation["title"]);

// Output the conversation's labels.
echo "<span class='labels'>";
foreach ($conversation["labels"] as $label) {
	if ($label == "draft")
		echo "<a href='".URL($conversationURL."#reply")."' class='label label-$label' title='".T("label.$label")."'><i class='".ETConversationModel::$labels[$label][1]."'></i></a> ";
	else
		echo "<span class='label label-$label' title='".T("label.$label")."'><i class='".ETConversationModel::$labels[$label][1]."'></i></span> ";
}
echo "</span> ";

// Output the conversation title, highlighting search keywords.
echo "<strong class='title'><a href='".URL($conversationURL.((ET::$session->user and $conversation["unread"]) ? "/unread" : ""))."'>".highlight(sanitizeHTML($conversation["title"]), ET::$session->get("highlight"))."</a></strong> ";

// If we're highlighting search terms (i.e. if we did a fulltext search), then output a "show matching posts" link.
if (ET::$session->get("highlight"))
	echo "<span class='controls'><a href='".URL($conversationURL."/?search=".urlencode($data["fulltextString"]))."' class='showMatchingPosts'>".T("Show matching posts")."</a></span>";

// If this conversation is stickied, output an excerpt from its first post.
if ($conversation["sticky"])
	echo "<div class='excerpt'>".ET::formatter()->init($conversation["firstPost"])->inline(true)->firstLine()->clip(200)->format()->get()."</div>";

?></div>
<div class='col-channel'><?php
$channel = $data["channelInfo"][$conversation["channelId"]];
echo "<a href='".URL(searchURL("", $channel["slug"]))."' class='channel channel-{$conversation["channelId"]}' data-channel='{$channel["slug"]}'>{$channel["title"]}</a>";
?></div>
<div class='col-replies'>
<i class='icon-comment<?php if (!$conversation["replies"]) echo "-alt"; ?>'></i>
<?php echo "<span>".Ts("%s reply", "%s replies", $conversation["replies"])."</span>";

// Output an "unread indicator", showing the number of unread posts.
if (ET::$session->user and $conversation["unread"])
	echo " <a href='".URL("conversation/markAsRead/".$conversation["conversationId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL))."' class='unreadIndicator' title='".T("Mark as read")."'>".Ts("%s new", "%s new", $conversation["unread"])."</a> ";

?></div>
<div class='col-lastPost'><?php
echo "<span class='action'>".avatar(array(
		"memberId" => $conversation["lastPostMemberId"],
		"avatarFormat" => $conversation["lastPostMemberAvatarFormat"],
		"email" => $conversation["lastPostMemberEmail"]
	), "thumb"), " ",
	sprintf(T("%s posted %s"),
		"<span class='lastPostMember name'>".memberLink($conversation["lastPostMemberId"], $conversation["lastPostMember"])."</span>",
		"<a href='".URL($conversationURL."/unread")."' class='lastPostTime'>".relativeTime($conversation["lastPostTime"], true)."</a>"),
	"</span>";
?></div>
</li>
