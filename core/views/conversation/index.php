<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays conversation information, a list of posts, the timeline, and a reply area for a single conversation.
 *
 * @package esoTalk
 */

// Just to make things a bit easier.
global $conversation;
$conversation = $data["conversation"];

// Shortcut function to construct a URL to a position within the conversation, optionally with a search string.
function makeURL($startFrom = 0, $searchString = "")
{
	global $conversation;
	$urlParts = array(conversationURL($conversation["conversationId"], $conversation["title"]));

	if ($startFrom > 0 or $startFrom[0] == "p" or $startFrom == "last" or $startFrom == "unread" or $searchString) $urlParts[] = $startFrom;
	if ($searchString) $urlParts[] = "?search=$searchString";

	return implode("/", $urlParts);
}

// Work out what general class names to apply to the conversation wrapper.
$classes = array("channel-".$conversation["channelId"]);
if ($conversation["starred"]) $classes[] = "starred";
if ($conversation["startMemberId"] == ET::$session->userId) $classes[] = "mine";
?>
<div id='conversation' class='<?php echo implode(" ", $classes); ?>'>

<!-- Conversation header -->
<div id='conversationHeader' class='bodyHeader'>

<?php

// Title ?>
<h1 id='conversationTitle'><?php
if ($conversation["canModerate"] or $conversation["startMemberId"] == ET::$session->userId): ?><a href='<?php echo URL("conversation/edit/".$conversation["conversationId"]); ?>'><?php echo sanitizeHTML($conversation["title"]); ?></a><?php
else: echo sanitizeHTML($conversation["title"]);
endif;
?></h1>
<?php

// Channel
$this->renderView("conversation/channelPath", array("conversation" => $conversation));

// Labels ?>
<span class='labels'>
<?php $this->renderView("conversation/labels", array("labels" => $conversation["labels"])); ?>
</span>

<?php
// Search within conversation form ?>
<form class='search' id='searchWithinConversation' action='<?php echo URL(conversationURL($conversation["conversationId"], $conversation["title"])); ?>' method='get'>
<fieldset>
<input name='search' type='text' class='text' value='<?php echo sanitizeHTML($data["searchString"]); ?>' placeholder='<?php echo T("Search within this conversation..."); ?>'/>
<?php if ($data["searchString"]): ?><a href='<?php echo URL(conversationURL($conversation["conversationId"], $conversation["title"])); ?>' class='control-reset'><i class='icon-remove'></i></a><?php endif; ?>
</fieldset>
</form>

</div>

<?php
// Controls
if ($data["controlsMenu"]->count()): ?>
<ul id='conversationControls' class='controls'>
<?php echo $data["controlsMenu"]->getContents(); ?>
</ul>
<?php endif; ?>

<?php
// Members allowed list (only if conversation is private or editable)
if (count($conversation["membersAllowedSummary"]) or $conversation["startMemberId"] == ET::$session->userId or $conversation["canModerate"]): ?>
<div id='conversationPrivacy' class='area'>
<span class='allowedList action'><?php $this->renderView("conversation/membersAllowedSummary", $data); ?></span>
<?php if ($conversation["startMemberId"] == ET::$session->userId): ?><a href='<?php echo URL("conversation/edit/".$conversation["conversationId"]); ?>' id='control-changeMembersAllowed'><i class='icon-pencil'></i> <?php echo T("Change"); ?></a><?php endif; ?>
</div>
<?php endif; ?>

<div id='conversationBody' class='hasScrubber'>

<?php // If we're searching but there are no search results, show an error.
if ($data["searchString"] and !$conversation["countPosts"]): ?>
<div class='area noResults help'>
<h4><?php echo T("message.noSearchResultsPosts"); ?></h4>
<ul>
<li><?php echo T("message.fulltextKeywordWarning"); ?></li>
<li><?php echo T("message.searchAllConversations"); ?></li>
</ul>
</div>
<?php else: ?>

<div class='scrubberColumn'>
<div class='scrubberContent'>

<?php $this->trigger("renderControlsBefore", array($data)); ?>

<?php
// Star
echo starButton($conversation["conversationId"], $conversation["starred"])."\n";
?>

<a href='#reply' class='button big<?php if (!$conversation["canReply"] and ET::$session->user): ?> disabled<?php endif; ?>' id='jumpToReply'><i class='icon-plus-sign'></i> <?php echo T("Post a Reply"); ?></a>

<?php $this->trigger("renderScrubberBefore", array($data)); ?>

<?php if (!$data["searchString"]): ?>
<!-- Timeline scrubber -->
<ul class='scrubber timelineScrubber'>
<?php
// Construct the timeline scrubber.

// Get the years/months of today, the last post in the conversation, and the first post in the conversation.
$currentYear = date("Y");
$currentMonth = date("n");
$latestYear = date("Y", $conversation["lastPostTime"]);
$latestMonth = date("n", $conversation["lastPostTime"]);
$oldestYear = date("Y", $conversation["startTime"]);
$oldestMonth = date("n", $conversation["startTime"]);

// Output the "original post" item. ?>
<li class='scrubber-op<?php if ($data["startFrom"] == 0 and empty($data["year"])): ?> selected<?php endif; ?>' data-index='first'><a href='<?php echo URL(makeURL()); ?>'><?php echo T("Original Post"); ?></a></li>
<?php

// Work out the year/month which we are viewing from and should therefore highlight as "selected".
// If we're not at the start of the conversation, or if a year/month was explicitly specified, set the
// year/month to that of the first post.
if ($data["startFrom"] > 0 or !empty($data["year"])) {
	$startFromYear = date("Y", $data["posts"][0]["time"]);
	$startFromMonth = date("n", $data["posts"][0]["time"]);
}
else {
	$startFromYear = null;
	$startFromMonth = null;
}

// Construct an array of YYYY => array(MM, MM, ...) elements for each month from the conversation's start
// right time through to its end time.
$scrubber = array();
$y = $oldestYear;
$m = $oldestMonth;
while ($y < $latestYear or $m <= $latestMonth) {
	if ($m > 12) {
		$m = 1;
		$y++;
	}
	$scrubber[$y][] = $m;
	$m++;
}

// Take out the last 5 months of today's year. We will display them as their own scrubber items.
$recentMonths = array();
if (!empty($scrubber[$currentYear])) {
	$recentMonths = array_splice($scrubber[$currentYear], -5);
	if (!count($scrubber[$currentYear]))
		unset($scrubber[$currentYear]);
}

// Go through the array we constructed before and output a scrubber item for each year, and a sub-list of
// its months.
foreach ($scrubber as $year => $months) {
	$selected = ($startFromYear == $year and $startFromMonth <= max($months)) ? " selected" : "";
	echo "<li class='scrubber-{$year}01$selected' data-index='{$year}01'><a href='".URL(makeURL("$year/1"))."'>$year</a>";

	// Output a sub-list of months.
	if (!empty($months)) {
		echo "<ul>";
		foreach ($months as $month) {
			$selected = ($startFromYear == $year and $startFromMonth == $month) ? " selected" : "";
			$name = strftime("%B", mktime(0, 0, 0, $month, 1));
			$index = $year.str_pad($month, 2, "0", STR_PAD_LEFT);
			echo "<li class='scrubber-$index$selected' data-index='$index'><a href='".URL(makeURL("$year/$month"))."'>$name</a></li>";
		}
		echo "</ul>";
	}

	echo "</li>";
}

// Now, with the last 5 months we pulled out before, output a scrubber item for each of them.
foreach ($recentMonths as $month) {
	$selected = ($startFromYear == $currentYear and $startFromMonth == $month) ? " selected" : "";
	$name = strftime("%B", mktime(0, 0, 0, $month, 1));
	$index = $currentYear.str_pad($month, 2, "0", STR_PAD_LEFT);
	echo "<li class='scrubber-$index$selected' data-index='$index'><a href='".URL(makeURL("$currentYear/$month"))."'>$name</a>";
	echo "</li>";
}

// If the latest post was in today's month, output a "Now" item. ?>
<li class='scrubber-now' data-index='last'<?php if ($latestYear != $currentYear or $latestMonth != $currentMonth): ?> style='display:none'<?php endif; ?>><a href='<?php echo URL(makeURL("last")); ?>'><?php echo T("Now"); ?></a></li>
</ul>
<?php endif; ?>

</div>
</div>

<!-- Posts -->
<ol id='conversationPosts' class='postList' start='<?php echo $data["startFrom"] + 1; ?>'>

<?php if ($data["startFrom"] > 0): ?>
<li class='scrubberMore scrubberPrevious'><a href='<?php echo URL(makeURL("p".(ceil($data["startFrom"] / C("esoTalk.conversation.postsPerPage") + 1) - 1), $data["searchString"])); ?>'>&lsaquo; <?php echo T("Older"); ?></a></li>
<?php endif; ?>

<?php $this->renderView("conversation/posts", $data); ?>

<?php if ($data["startFrom"] + C("esoTalk.conversation.postsPerPage") < $conversation["countPosts"]): ?>
<li class='scrubberMore scrubberNext'><a href='<?php echo URL(makeURL("p".(floor($data["startFrom"] / C("esoTalk.conversation.postsPerPage") + 1) + 1), $data["searchString"])); ?>'><?php echo T("Newer"); ?> &rsaquo;</a></li>
<?php endif; ?>

</ol>

<?php if (!$data["searchString"]): ?>
<!-- Reply area -->
<div id='conversationReply'>
<?php echo $data["replyForm"]->open(); ?>

<?php
// If we can't reply, we should show some kind of error message.
if (!$conversation["canReply"]) {

	// If the user simply isn't logged in, show a reply box placeholder saying that they need to log in or sign up.
	if (!ET::$session->user) {
		$post = array(
			"id" => "reply",
			"class" => "logInToReply",
			"title" => "",
			"body" => sprintf(T("message.logInToReply"), URL("user/login?return=".urlencode($this->selfURL)), URL("user/join?return=".urlencode($this->selfURL))),
			"avatar" => avatar()
		);

		$this->renderView("conversation/post", array("post" => $post));
	}

	// If the user is suspended, show an informational message.
	elseif (ET::$session->isSuspended()) {
		echo "<p class='help'>".T("message.suspended")."</p>";
	}

	// If the conversation is locked...
	elseif ($conversation["locked"]) {
		echo "<p class='help'>".T("message.locked")."</p>";
	}
}

// If we can reply, show the reply box.
else {
	$this->renderView("conversation/reply", array(
		"form" => $data["replyForm"],
		"conversation" => $conversation,
		"controls" => $data["replyControls"]
	));
}
?>

<?php echo $data["replyForm"]->close(); ?>
</div>
<?php endif; ?>

<?php endif; ?>

</div>

</div>
