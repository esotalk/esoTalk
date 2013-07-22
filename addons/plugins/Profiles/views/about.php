<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

$member = $data["member"];
$about = $data["about"];
$location = $data["location"];
?>
<div id='memberAbout'>

	<ul class='form'>

		<li><label><?php echo T("Location"); ?></label> <div><?php echo sanitizeHTML($location); ?></div></li>
		<li><label><?php echo T("About"); ?></label> <div><?php echo $about; ?></div></li>

	</ul>

</div>