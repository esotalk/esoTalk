<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ChannelNotifications"] = array(
	"name" => "Channel Notifications",
	"description" => "Allows users to follow channels and get notified about new posts within them.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_ChannelNotifications extends ETPlugin {

	function init()
	{
		// Add the postChannel activity type.
		ET::activityModel();
		ETActivityModel::addType("postChannel", array(
			"notification" => array(__CLASS__, "postChannelNotification"),
			"email" => array(__CLASS__, "postChannelEmail")
		));

		ET::define("email.postChannel.body", "%1\$s has posted in a conversation in a channel which you followed: '%2\$s'.\n\nTo view the new activity, check out the following link:\n%3\$s");
		ET::define("email.postChannel.subject", "There is a new post in '%1\$s'");
	}

	/**
	 * Returns a formatted notification item for the "postChannel" activity type. For example, '[member]
	 * posted in [*channel] [title]'.
	 *
	 * @param array $item The activity item's details.
	 * @return array 0 => notification body, 1 => notification link
	 */
	public static function postChannelNotification(&$item)
	{
		return array(
			sprintf(T("%s posted in %s."), $item["fromMemberName"], "<span class='channel channel-".$item["data"]["channelId"]."'><span class='star starOn'>*</span> ".$item["data"]["channelTitle"]."</span> <strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
			URL(postURL($item["postId"]))
		);
	}

	/**
	 * Returns a formatted email subject+body for the "postChannel" activity type.
	 *
	 * @see mentionEmail() for parameter and return information.
	 */
	public static function postChannelEmail($item, $member)
	{
		return array(
			sprintf(T("email.postChannel.subject"), sanitizeHTML($item["data"]["channelTitle"])),
			sprintf(T("email.postChannel.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"])."/unread", true))
		);
	}

	function handler_channelsController_renderChannelControls($sender, $channel)
	{
		if (!ET::$session->user or @$channel["unsubscribed"]) return;

		$starred = @$channel["follow"];
		
		$url = URL("channels/follow/".$channel["channelId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL));
		echo "<a href='$url' class='button' title='".T("Follow to receive notifications")."' data-id='{$channel["channelId"]}'><span class='star".($starred ? " starOn" : "")."'></span> <span>".($starred ? T("Following") : T("Follow"))."</span></a>";
	}

	/**
	 * Toggle the user's subscription to a channel.
	 *
	 * @param int $channelId The ID of the channel to toggle subscription to.
	 * @return void
	 */
	public function channelsController_follow($controller, $channelId = "")
	{
		if (!ET::$session->user or !$controller->validateToken()) return;

		// If we don't have permission to view this channel, don't proceed.
		if (!ET::channelModel()->hasPermission((int)$channelId, "view")) return;

		// Work out if we're already followed or not, and switch to the opposite of that.
		$followed = !ET::SQL()
			->select("follow")
			->from("member_channel")
			->where("memberId", ET::$session->userId)
			->where("channelId", (int)$channelId)
			->exec()
			->result();

		// Write to the database.
		ET::channelModel()->setStatus($channelId, ET::$session->userId, array("follow" => $followed));

		// Normally, redirect back to the channel list.
		if ($controller->responseType === RESPONSE_TYPE_DEFAULT) redirect(URL("channels"));

		// Otherwise, set a JSON var.
		$controller->json("follow", $followed);
		$controller->render();
	}

	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		// Send out notifications to people who have starred the channel that this conversation is in.
		// We get all members who have starred the channel and have no unread posts in the conversation.
		$sql = ET::SQL()
			->from("member_channel ch", "ch.channelId=:channelId AND ch.memberId=m.memberId AND ch.follow=1 AND ch.memberId!=:userId", "inner")
			->from("member_conversation co", "co.conversationId=:conversationId AND co.type='member' AND co.id=m.memberId", "left")
			->where("co.lastRead IS NULL OR co.lastRead>=:posts")
			->bind(":channelId", $conversation["channelId"])
			->bind(":conversationId", $conversation["conversationId"])
			->bind(":posts", $conversation["countPosts"] - 1)
			->bind(":userId", ET::$session->userId);
		$members = ET::memberModel()->getWithSQL($sql);

		$data = array(
			"conversationId" => $conversation["conversationId"],
			"postId" => $postId,
			"title" => $conversation["title"],
			"channelId" => $conversation["channelId"],
			"channelTitle" => $conversation["channelTitle"]
		);

		foreach ($members as $member) {
			ET::activityModel()->create("postChannel", $member, ET::$session->user, $data);
		}
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		// Add the "email me when someone replies to a conversation in a channel I have followed" field.
		$form->setValue("postChannel", ET::$session->preference("email.postChannel"));
		$form->addField("notifications", "postChannel", array(__CLASS__, "fieldEmailPostChannel"), array($sender, "saveEmailPreference"), array("after" => "post"));
	}

	public static function fieldEmailPostChannel($form)
	{
		return "<label class='checkbox'>".$form->checkbox("postChannel")." <span class='star starOn'>*</span> ".T("Email me when someone posts in a channel I have followed")."</label>";
	}

	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_channel")
			->column("follow", "bool", 0)
			->exec(false);

		return true;
	}

}
