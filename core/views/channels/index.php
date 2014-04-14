<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the channel list page.
 *
 * @package esoTalk
 */
?>

<div id='conversationsFilter' class='bodyHeader'>

<form class='search big' id='search' action='<?php echo URL("conversations/all"); ?>' method='get'>
<fieldset>
<i class='icon-search'></i>
<input name='search' type='text' class='text' value='' spellcheck='false' placeholder='<?php echo T("Search conversations..."); ?>'/>
</fieldset>
</form>

<ul id='channels' class='channels tabs'>
<li class='channelListItem selected'><a href='<?php echo URL("channels"); ?>' class='channel-list' data-channel='list' title='<?php echo T("Channel List"); ?>'><i class="icon-list"></i></a></li>
<?php $this->renderView("channels/tabs", $data); ?>
</ul>

</div>

<div id='channelList'>
<?php $this->renderView("channels/list", $data); ?>
</div>
