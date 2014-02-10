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

	// Setup: add a follow column to the member_channel table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_channel")
			->column("follow", "bool", 0)
			->exec(false);

		return true;
	}

	public function init()
	{
		// Add the postChannel activity type.
		ET::activityModel();
		ETActivityModel::addType("postChannel", array(
			"notification" => array(__CLASS__, "postChannelNotification"),
			"email" => array(__CLASS__, "postChannelEmail")
		));

		ET::define("email.postChannel.body", "<p><strong>%1\$s</strong> has posted in a conversation in a channel which you followed: <strong>%2\$s</strong></p><hr>%3\$s<hr><p>To view the new activity, check out the following link:<br>%4\$s</p>");
		ET::define("email.postChannel.subject", "[%1\$s] %2\$s");
	}

	// Add a follow button to each channel in the channels list.
	public function handler_channelsController_renderChannelControls($sender, $channel)
	{
		if (!ET::$session->user or @$channel["unsubscribed"]) return;

		$starred = @$channel["follow"];
		
		$url = URL("channels/follow/".$channel["channelId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL));
		echo "<a href='$url' class='button' title='".T("Follow to receive notifications")."' data-id='{$channel["channelId"]}'><i class='star icon-star".($starred ? "" : "-empty")."'></i> <span class='text'>".($starred ? T("Following") : T("Follow"))."</span></a>";
	}

	// Add an action to toggle the follow status of a channel.
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

	// Send out notifications to people who have starred the channel that a conversation is in.
	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		// Only continue if this is the first post.
		if ($conversation["countPosts"] > 1) return;

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
		$emailData = array("content" => $content);

		foreach ($members as $member) {

			// Check if this member is allowed to view this conversation before sending them a notification.
			$sql = ET::SQL()
				->select("conversationId")
				->from("conversation c")
				->where("conversationId", $conversation["conversationId"]);
			ET::conversationModel()->addAllowedPredicate($sql, $member);
			if (!$sql->exec()->numRows()) continue;

			ET::activityModel()->create("postChannel", $member, ET::$session->user, $data, $emailData);
		}
	}

	public function handler_conversationModel_createAfter($sender, $conversation, $postId, $content)
	{
		if (!$postId) return; // the conversation is a draft
		$this->handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content);
	}

	// Add the "email me when someone replies to a conversation in a channel I have followed" field to the settings page.
	public function handler_settingsController_initGeneral($sender, $form)
	{
		$form->setValue("postChannel", ET::$session->preference("email.postChannel"));
		$form->addField("notifications", "postChannel", array(__CLASS__, "fieldEmailPostChannel"), array($sender, "saveEmailPreference"), array("after" => "post"));
	}

	public static function fieldEmailPostChannel($form)
	{
		return "<label class='checkbox'>".$form->checkbox("postChannel")." <i class='star icon-star'></i> ".T("Email me when someone posts in a channel I have followed")."</label>";
	}

	// Format the postChannel notification.
	public static function postChannelNotification(&$item)
	{
		return array(
			sprintf(T("%s posted in %s"), name($item["fromMemberName"]), "<span class='channel channel-".$item["data"]["channelId"]."'><i class='star icon-star'></i> ".$item["data"]["channelTitle"]."</span> <strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
			URL(postURL($item["postId"]))
		);
	}

	// Format the postChannel email.
	public static function postChannelEmail($item, $member)
	{
		$content = ET::formatter()->init($item["data"]["content"])->format()->get();
		$url = URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"])."/unread", true);
		return array(
			sprintf(T("email.postChannel.subject"), $item["data"]["channelTitle"], $item["data"]["title"]),
			sprintf(T("email.postChannel.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), $content, "<a href='$url'>$url</a>")
		);
	}

}
