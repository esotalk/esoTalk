<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='attachments'>
	<ul>
		<?php foreach ($data["attachments"] as $attachment): ?>
		<li>
			<?php echo formatAttachment($attachment); ?>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
