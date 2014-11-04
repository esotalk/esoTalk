<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The channels controller handles the channel list page, and subscribing/unsubscribing to channels.
 *
 * @package esoTalk
 */
class ETChannelsController extends ETController {


/**
 * Show the channel list page.
 *
 * @return void
 */
public function index()
{
	// Set the canonical URL and push onto the navigation stack.
	$url = "channels";
	$this->canonicalURL = URL($url, true);
	$this->pushNavigation("channels", "channels", URL($url));

	// Get all of the channels that we can view.
	$channels = ET::channelModel()->get();

	// Normally, render the channels list page.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		$this->addJSFile("js/channels.js");
		$this->data("channels", $channels);
		$this->render("channels/index");
	}

	// But for JSON, add the channels as a JSON var and render.
	elseif ($this->responseType === RESPONSE_TYPE_JSON) {
		$this->json("channels", $channels);
		$this->render();
	}
}


/**
 * Toggle the user's subscription to a channel.
 *
 * @param int $channelId The ID of the channel to toggle subscription to.
 * @return void
 */
public function subscribe($channelId = "")
{
	if (!ET::$session->user or !$this->validateToken()) return;

	// If we don't have permission to view this channel, don't proceed.
	if (!ET::channelModel()->hasPermission((int)$channelId, "view")) return;

	// Work out if we're already unsubscribed or not, and switch to the opposite of that.
	$unsubscribed = !ET::SQL()
		->select("unsubscribed")
		->from("member_channel")
		->where("memberId", ET::$session->userId)
		->where("channelId", (int)$channelId)
		->exec()
		->result();

	// Write to the database.
	ET::channelModel()->setStatus($channelId, ET::$session->userId, array("unsubscribed" => $unsubscribed));

	// Normally, redirect back to the channel list.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) redirect(URL("channels"));

	// Otherwise, set a JSON var.
	$this->json("unsubscribed", $unsubscribed);
	$this->render();
}

}