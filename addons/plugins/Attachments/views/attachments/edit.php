<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='attachments-edit<?php if (!empty($data["attachments"])): ?> has-attachments<?php endif; ?>'>
	<ul>
		<?php foreach ($data["attachments"] as $attachment): ?>
		<li id='attachment-<?php echo $attachment["attachmentId"]; ?>'>
			<a href='<?php echo URL("attachment/remove/".$attachment["attachmentId"]."?token=".ET::$session->token); ?>' class='control-delete' data-id='<?php echo $attachment["attachmentId"]; ?>'><i class='icon-remove'></i></a>
			<strong><?php echo $attachment["filename"]; ?></strong>
			<span class='attachment-controls'>
				<a href='#' class='control-embed' title='Embed in post' data-id='<?php echo $attachment["attachmentId"]; ?>'><i class='icon-external-link'></i></a>
			</span>
		</li>
		<?php endforeach; ?>
	</ul>

	<a class='attachments-button'><i class="icon-paper-clip"></i> <?php echo T("Attach a file"); ?></a>
</div>

<div class='dropZone'><?php echo T("Drop files to upload"); ?></div>
