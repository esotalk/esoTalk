<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a collection of posts as list items.
 *
 * @package esoTalk
 */

$prevPost = null;

foreach ($data["posts"] as $k => $post):

// Format the post for the template.
$formattedPost = $this->formatPostForTemplate($post, $data["conversation"]);

// If the post before this one is by the same member as this one, hide the avatar.
if ($prevPost and empty($prevPost["deleteMemberId"]) and $prevPost["memberId"] == $post["memberId"])
	$formattedPost["hideAvatar"] = true;

$thisPostTime = relativeTime($post["time"]);

?>
<li data-index='<?php echo date("Y", $post["time"]).date("m", $post["time"]); ?>'>
<?php
// If the post before this one has a different relative time string to this one, output a 'time marker'.
if (!isset($prevPost["time"]) or relativeTime($prevPost["time"]) != $thisPostTime): ?>
<div class='timeMarker'><?php echo $thisPostTime; ?></div>
<?php endif; ?>
<?php $this->renderView("conversation/post", array("post" => $formattedPost)); ?>
</li>

<?php $prevPost = $post; ?>

<?php endforeach; ?>