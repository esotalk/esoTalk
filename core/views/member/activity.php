<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the activity pane in a member's profile, which contains a list of activity items and a "view more"
 * link if there are more results.
 *
 * @package esoTalk
 */

$member = $data["member"];
$activity = $data["activity"];

// If there is activity, output it in a list.
if (!empty($activity)): ?>
<ol id='memberActivity' class='activityList'>
<?php
foreach ($activity as $k => $item):

// Get the relative time of this post.
$thisPostTime = relativeTime($item["time"], false); ?>

<li>
<?php
// If the post before this one has a different relative time to this one, output a time marker.
if (!isset($activity[$k - 1]["time"]) or relativeTime($activity[$k - 1]["time"], false, true) != $thisPostTime): ?>
<div class='timeMarker'><?php echo $thisPostTime; ?></div>
<?php endif; ?>
<?php $this->renderView("member/activityItem", array("activity" => $item) + $data); ?>
</li>

<?php endforeach; ?>
</ol>

<?php if ($data["showViewMoreLink"]):
echo "<a href='".URL(memberURL($member["memberId"], $member["username"], "activity")."/".($data["page"] + 2))."' class='button' id='viewMoreActivity'>".T("View more")."</a>";
endif; ?>

<?php
// Otherwise, output a "no activity" message.
else: ?>
<p class='help'><?php printf(T("message.noActivity"), $member["username"]); ?></p>
<?php endif; ?>