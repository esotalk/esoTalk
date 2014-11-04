<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of members in the context of the member list.
 *
 * @package esoTalk
 */
?>
<?php
// Loop through the conversations and output a table row for each one.
foreach ($data["members"] as $member):
$this->renderView("members/member", $data + array("member" => $member));
endforeach;

?>