<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the member list page, including the filter bar, the letter scrubber, and the list.
 *
 * @package esoTalk
 */

global $orderBy;
$orderBy = $data["orderBy"];

// Shortcut function to construct a URL to a member list page, while retaining the same orderBy.
function makeURL($startFrom = 0, $searchString = "")
{
	global $orderBy;
	$urlParts = array("members", $orderBy);

	if ($startFrom > 0 or $startFrom[0] == "p" or $searchString) $urlParts[] = $startFrom;
	if ($searchString) $urlParts[] = "?search=$searchString";

	return implode("/", $urlParts);
}

?>
<!-- Member List Filter -->
<div id='memberListFilter' class='bodyHeader'>

<h1><?php echo T("Member List"); ?></h1>

<ul id='memberListOrderBy' class='tabs'>
<li><span><?php echo T("Order By:"); ?></span></li>
<?php foreach ($data["orders"] as $k => $v): ?>
<li<?php if ($data["orderBy"] == $k): ?> class='selected'<?php endif; ?>><a href='<?php echo URL("members/$k/".($data["searchString"] ? "?search=".$data["searchString"] : "")); ?>'><?php echo T($v[0]); ?></a></li>
<?php endforeach; ?>
</ul>

<?php if (ET::$session->isAdmin()): ?>
<a href='<?php echo URL("members/create"); ?>' class='button' id='createMemberLink'><span class='icon-add'></span> <?php echo T("Create Member"); ?></a>
<?php endif; ?>

<form class='search big' id='memberSearch' action='<?php echo URL(makeURL()); ?>' method='get'>
<fieldset>
<input name='search' type='text' class='text' value='<?php echo $data["searchString"]; ?>' spellcheck='false' placeholder='<?php echo T("Filter by name or group..."); ?>'/>
<?php if ($data["searchString"]): ?><a class='control-reset' href='<?php echo URL(makeURL()); ?>'>x</a><?php endif; ?>
</fieldset>
</form>

</div>

<!-- Member List Body -->
<div id='memberListBody' class='hasScrubber clearfix'>

<?php // If we're searching but there are no search results, show an error.
if ($data["searchString"] and !count($data["members"])): ?>
<div class='area noResults help'>
<h4><?php echo T("message.noSearchResultsMembers"); ?></h4>
</div>
<?php else: ?>

<div class='scrubberColumn'>
<div class='scrubberContent'>

<?php if (!$data["searchString"] and $data["orderBy"] == "name"): ?>
<!-- Letter scrubber -->
<ul class='scrubber letterScrubber'>

<?php

// Construct an array of letters, and "#" as the item for special characters and numbers.
$letters = range("a", "z");
array_unshift($letters, "#");

// Work out what letter we are currently viewing by looking at the name of the first member in the results.
$currentLetter = strtolower($data["members"][0]["username"][0]);
if (!in_array($currentLetter, $letters)) $currentLetter = "#";

// Output the letter scrubber items.
foreach ($letters as $letter) {
	$selected = ($currentLetter == $letter) ? " selected" : "";
	$id = $letter == "#" ? 0 : $letter;
	echo "<li class='scrubber-$id$selected' data-index='$id'><a href='".URL("members/name/$id")."'>".strtoupper($letter)."</a></li>";
}

?>
</ul>

<?php endif; ?>

</div>
</div>

<!-- Members -->
<ul id='memberList' class='list memberList'>

<?php if ($data["startFrom"] > 0): ?>
<li class='scrubberMore scrubberPrevious'><a href='<?php echo URL(makeURL("p".(ceil($data["startFrom"] / C("esoTalk.members.membersPerPage") + 1) - 1), $data["searchString"])); ?>'>&lsaquo; <?php echo T("Previous"); ?></a></li>
<?php endif; ?>

<?php $this->renderView("members/list", $data); ?>

<?php if ($data["startFrom"] + C("esoTalk.members.membersPerPage") < $data["countMembers"]): ?>
<li class='scrubberMore scrubberNext'><a href='<?php echo URL(makeURL("p".(floor($data["startFrom"] / C("esoTalk.members.membersPerPage") + 1) + 1), $data["searchString"])); ?>'><?php echo T("Next"); ?> &rsaquo;</a></li>
<?php endif; ?>

</ul>

<?php endif; ?>

</div>