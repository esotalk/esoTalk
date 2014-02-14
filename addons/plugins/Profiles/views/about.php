<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

$fields = $data["fields"];
?>
<div id='memberAbout'>

	<ul class='form'>

		<?php foreach ($fields as $field): ?>
			<li>
				<label><?php echo $field["name"]; ?></label>
				<div><?php echo $field["data"]; ?></div>
			</li>
			<li class='sep'></li>
		<?php endforeach; ?>
		
	</ul>

</div>
