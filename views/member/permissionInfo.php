<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of specific permissions that a member assigned to a selection of groups would have.
 *
 * @package esoTalk
 */

$member = $data["member"];
?>
<h4><?php printf(T("%s will be able to:"), $member["username"]); ?></h4>

<ul>
<?php foreach ($data["extraPermissions"] as $v): ?>
<li><?php echo $v; ?></li>
<?php endforeach; ?>
</ul>

<table class='permissionsGrid'>
<thead><tr><th>&nbsp;</th><?php foreach ($data["permissions"] as $k => $v): ?><th><?php echo T($v); ?></th><?php endforeach; ?></tr></thead>
<tbody>
<?php foreach ($data["channels"] as $channel): ?>
<tr>
<th class='depth<?php echo $channel["depth"]; ?>'><?php echo $channel["title"]; ?></th>
<?php foreach ($data["permissions"] as $k => $v):
$allowed = ET::groupModel()->groupIdsAllowedInGroupIds($data["groupIds"], $channel["permissions"][$k], true);
?><td class='<?php echo $allowed ? "yes" : "no"; ?>'><?php echo $allowed ? T("Yes") : T("No"); ?></td><?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>