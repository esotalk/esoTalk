<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Mobile master view. Displays a simplified HTML template with a header and footer.
 *
 * @package esoTalk
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset='<?php echo T("charset", "utf-8"); ?>'>
<title><?php echo sanitizeHTML($data["pageTitle"]); ?></title>
<?php echo $data["head"]; ?>
<script>
// Turn off JS effects and fixed positions, and disable tooltips.
jQuery.fx.off = true;
ET.disableFixedPositions = true;
ET.mobile = true;
$.fn.tooltip = function() { return this; };
// Make the user menu into a popup, and take notifications out of the user menu.
$(function() {
	$("#forumTitle").before($("#userMenu").popup({alignment: "right", content: "<i class='icon-reorder'></i>"}));
	$("#forumTitle").before($("#notifications").parent())
		.css("webkitTransform", "scale(1)"); // force a redraw to fix a webkit layout bug
});
</script>
</head>

<body class='<?php echo $data["bodyClass"]; ?>'>
<?php $this->trigger("pageStart"); ?>

<div id='messages'>
<?php foreach ($data["messages"] as $message): ?>
<div class='messageWrapper'>
<div class='message <?php echo $message["className"]; ?>' data-id='<?php echo isset($message["id"]) ? $message["id"] : ""; ?>'><?php echo $message["message"]; ?></div>
</div>
<?php endforeach; ?>
</div>

<div id='wrapper'>

<!-- HEADER -->
<div id='hdr'>
<div id='hdr-content'>
<div id='hdr-inner'>

<?php if ($data["backButton"]): ?>
<a href='<?php echo $data["backButton"]["url"]; ?>' id='backButton' title='<?php echo T("Back to {$data["backButton"]["type"]}"); ?>'><i class="icon-chevron-left"></i></a>
<?php endif; ?>

<ul id='userMenu' class='menu'>
<li><a href='<?php echo URL("conversation/start"); ?>' class='link-newConversation'><?php echo T("New Conversation"); ?></a></li>
<li class='sep'></li>
<?php echo $data["userMenuItems"]; ?>
</ul>

<h1 id='forumTitle'><a href='<?php echo URL(""); ?>'><?php echo C("esoTalk.forumTitle"); ?></a></h1>

</div>
</div>
</div>

<!-- BODY -->
<div id='body'>
<div id='body-content'>
<?php echo $data["content"]; ?>
</div>
</div>

<!-- FOOTER -->
<div id='ftr'>
<div id='ftr-content'>
<ul class='menu'>
<?php echo $data["metaMenuItems"]; ?>
</ul>
</div>
</div>
<?php $this->trigger("pageEnd"); ?>

</div>

</body>
</html>
