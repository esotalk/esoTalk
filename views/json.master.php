<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * JSON master view. Displays messages and data collected in the controller as a JSON object.
 * 
 * @package esoTalk
 */

$this->json("messages", $this->getMessages());

echo json_encode($this->json);
?>