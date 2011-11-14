<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the conversation list, including the filter area (search form, gambits, and channel breadcrumb.)
 *
 * @package esoTalk
 */
?>

<div id='conversationsFilter' class='bodyHeader'>

<form class='search big' id='search' action='<?php echo URL("conversations/".$data["channelSlug"]); ?>' method='get'>
<fieldset>
<input name='search' type='text' class='text' value='<?php echo $data["searchString"]; ?>' spellcheck='false' placeholder='<?php echo T("Filter conversations..."); ?>'/>
<a class='control-reset' href='<?php echo URL("conversations/".$data["channelSlug"]); ?>'>x</a>
</fieldset>
</form>

<ul id='channels' class='channels tabs'>
<li><a href='<?php echo URL("channels"); ?>' class='channel-list' data-channel='list' title='<?php echo T("Channel List"); ?>'><?php echo T("Channel List"); ?></a></li>
<?php $this->renderView("channels/tabs", $data); ?>
</ul>

<div id='gambits'>
<p class='help'><?php echo T("message.gambitsHelp"); ?></p>
<?php
$linkPrefix = "conversations/".$data["channelSlug"]."/?search=".urlencode(((!empty($data["searchString"]) ? $data["searchString"]." + " : "")));
ksort($data["gambits"]);
foreach ($data["gambits"] as $k => $v)
	echo "<a href='".URL($linkPrefix.$k)."' class='$v'>$k</a>\n";
?></div>

</div>

<div id='conversations'>
<?php $this->renderView("conversations/results", $data); ?>
</div>