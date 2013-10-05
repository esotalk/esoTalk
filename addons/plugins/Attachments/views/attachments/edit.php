<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='attachments-edit'>
	<ul>
		<?php foreach ($data["attachments"] as $attachment): ?>
		<li id='attachment-<?php echo $attachment["attachmentId"]; ?>'>
			<a href='<?php echo URL("attachment/remove/".$attachment["attachmentId"]."?token=".ET::$session->token); ?>' class='control-delete' data-id='<?php echo $attachment["attachmentId"]; ?>'><i class='icon-remove'></i></a>
			<strong><?php echo $attachment["filename"]; ?></strong>
		</li>
		<?php endforeach; ?>
	</ul>

	<a class='attachments-button'><?php echo T("Attach a file"); ?></a>
</div>

<div class='dropZone'><?php echo T("Drop files to upload"); ?></div>