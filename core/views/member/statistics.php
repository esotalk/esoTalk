<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the statistics pane in a member's profile.
 *
 * @package esoTalk
 */

$statistics = $data["statistics"];
$member = $data["member"];
?>
<div id='memberStatistics'>

<ul class='form'>

<li><label><?php echo T("Posts"); ?></label> <div><?php echo number_format($statistics["postCount"]); ?></div></li>

<li><label><?php echo T("Conversations started"); ?></label> <div>
<?php echo number_format($statistics["conversationsStarted"]); ?>
<a href='<?php echo URL(searchURL("#author:".$member["username"])); ?>' class='control-search'><i class='icon-search'></i></a>
</div></li>

<li><label><?php echo T("Conversations participated in"); ?></label> <div>
<?php echo number_format($statistics["conversationsParticipated"]); ?>
<a href='<?php echo URL(searchURL("#contributor:".$member["username"])); ?>' class='control-search'><i class='icon-search'></i></a>
</div></li>

<li><label><?php echo T("First posted"); ?></label> <div><?php echo ucfirst(relativeTime($statistics["firstPosted"])); ?></div></li>

<li><label><?php echo T("Joined"); ?></label> <div><?php echo ucfirst(relativeTime($statistics["joinTime"])); ?></div></li>

</ul>

</div>