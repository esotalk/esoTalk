<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of labels that apply to a conversation.
 * 
 * @package esoTalk
 */

foreach ($data["labels"] as $label) {
	echo "<span class='label label-$label'>".T("label.$label")."</span>\n";
}

?>