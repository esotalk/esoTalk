<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a list of members who have not yet been approved and controls to
 * approve or deny them.
 *
 * @package esoTalk
 */

$members = $data["members"];
?>
<div class='sheet' id='unapprovedSheet'>
<div class='sheetContent' id='adminUnapproved'>

<h3><?php echo T("Members Awaiting Approval"); ?></h3>

<div class='sheetBody'>

<div class='section'>

<ul class='list'>
<?php foreach ($members as $member): ?>
<li>
<div class='controls'>
<span class='buttonGroup'>
<a href='<?php echo URL("admin/unapproved/approve/".$member["memberId"]."?token=".ET::$session->token); ?>' class='button'><?php echo T("Approve"); ?></a>
<a href='<?php echo URL("admin/unapproved/deny/".$member["memberId"]."?token=".ET::$session->token); ?>' class='button'><?php echo T("Deny"); ?></a>
</span>
</div>
<strong><?php echo name($member["username"]); ?></strong>
<small><?php echo $member["email"]; ?></small>
</li>
<?php endforeach; ?>
</ul>

</div>

</div>

</div>
</div>
