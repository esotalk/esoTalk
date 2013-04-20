<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='attachments'>
	<h4><span>Attachments</span></h4>
	<ul>
		<?php foreach ($data["attachments"] as $attachment): ?>
		<li>
			<a href='<?php echo URL("attachment/".$attachment["attachmentId"]."_".$attachment["filename"]); ?>' target='_blank'>
				<?php echo $attachment["filename"]; ?>
			</a>
		</li>
		<?php endforeach; ?>
	</ul>
</div>