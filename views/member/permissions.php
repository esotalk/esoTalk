<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a form to change a member's permissions (their account type and the groups which
 * they are in.)
 *
 * @package esoTalk
 */

$member = $data["member"];
$form = $data["form"];
?>
<div class='sheet' id='permissionsSheet'>
<div class='sheetContent'>

<?php echo $form->open(); ?>

<h3><?php printf(T("Change %s's Permissions"), sanitizeHTML($member["username"])); ?></h3>

<div class='section' id='permissionForm'>
<ul class='form'>

<li><label><?php echo T("Account type"); ?></label> <?php
$options = array();
foreach ($data["accounts"] as $account) $options[$account] = groupName($account);
echo $form->select("account", $options);
?></li>

<li id='permissionGroups'><label><?php echo T("Groups"); ?></label>
<div class='checkboxGroup'>
<?php foreach ($data["groups"] as $group): ?>
<label class='checkbox'>
<?php echo $form->checkbox("groups[]", array("value" => $group["groupId"]) + (isset($member["groups"][$group["groupId"]]) ? array("checked" => "checked") : array())); ?>
<?php echo groupName($group["name"]); ?>
</label>
<?php endforeach; ?>
</div>
</li>

</ul>
</div>

<div class='section' id='permissionInfo'>
<?php $this->renderView("member/permissionInfo", $data); ?>
</div>

<div class='buttons'>
<small><a href='<?php echo URL("admin/groups"); ?>'><?php echo T("Edit member groups"); ?></a></small>
<?php echo $form->saveButton(); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>