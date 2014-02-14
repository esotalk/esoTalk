<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a list of member fields and controls to edit, delete, or create them.
 *
 * @package esoTalk
 */

$fields = $data["fields"];
?>
<script>
$(function() {
	AdminProfiles.init();
});
</script>

<div class='sheet' id='profilesSheet'>
<div class='sheetContent' id='adminProfiles'>

<h3><?php echo T("Manage Profile Fields"); ?></h3>

<div class='sheetBody'>

<div class='section'>

<ul class='list'>
<?php foreach ($fields as $field): ?>
<li data-id='<?php echo $field["fieldId"]; ?>' class='hasControls'>
<div class='controls'><a href='<?php echo URL("admin/profiles/edit/".$field["fieldId"]); ?>' class='control-edit' title='<?php echo T("Edit"); ?>'><i class='icon-edit'></i></a> <a href='<?php echo URL("admin/profiles/delete/".$field["fieldId"]."?token=".ET::$session->token); ?>' class='control-delete' title='<?php echo T("Delete"); ?>'><i class='icon-remove'></i></a></div>
<strong><?php echo $field["name"]; ?></strong>
</li>
<?php endforeach; ?>
</ul>

<a href='<?php echo URL("admin/profiles/create"); ?>' class='button' id='addFieldButton'><i class='icon-plus-sign'></i> <?php echo T("Create Field"); ?></a>

</div>

</div>

</div>
</div>
