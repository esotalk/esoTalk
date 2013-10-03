<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a list of member groups and controls to edit, delete, or create them.
 *
 * @package esoTalk
 */

$groups = $data["groups"];
?>
<script>
$(function() {
	ETAdminGroups.init();
});
</script>

<div class='sheet' id='groupsSheet'>
<div class='sheetContent' id='adminGroups'>

<h3><?php echo T("Manage Groups"); ?></h3>

<div class='sheetBody'>

<div class='section'>

<ul class='list'>
<?php foreach ($groups as $id => $group): ?>
<li data-id='<?php echo $id; ?>' class='hasControls'>
<div class='controls'><a href='<?php echo URL("admin/groups/edit/$id"); ?>' class='control-edit' title='<?php echo T("Edit"); ?>'><i class='icon-edit'></i></a> <a href='<?php echo URL("admin/groups/delete/$id?token=".ET::$session->token); ?>' class='control-delete' title='<?php echo T("Delete"); ?>'><i class='icon-remove'></i></a></div>
<strong><?php echo groupName($group["name"]); ?></strong>
</li>
<?php endforeach; ?>
</ul>

<a href='<?php echo URL("admin/groups/create"); ?>' class='button' id='addGroupButton'><i class='icon-plus-sign'></i> <?php echo T("Create Group"); ?></a>

</div>

</div>

</div>
</div>