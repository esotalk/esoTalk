<div class='attachments-edit'>
	<ul>
		<?php foreach ($data["attachments"] as $attachment): ?>
		<li id='attachment-<?php echo $attachment["attachmentId"]; ?>'>
			<a href='<?php echo URL("attachment/remove/".$attachment["attachmentId"]); ?>' class='control-delete' data-id='<?php echo $attachment["attachmentId"]; ?>'>Delete</a>
			<strong><?php echo $attachment["filename"]; ?></strong>
		</li>
		<?php endforeach; ?>
	</ul>

	<a href='#' class='attachments-button'>Attach a file</a>
</div>