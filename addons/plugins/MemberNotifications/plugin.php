<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["MemberNotifications"] = array(
	"name" => "Member Notifications",
	"description" => "Allows users to follow members and get notified about new posts by them.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_MemberNotifications extends ETPlugin {

	// Setup: add a follow column to the member_member table.
	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_member")
			->column("follow", "bool", 0)
			->exec(false);

		return true;
	}

	public function init()
	{
		// Add the postMember activity type.
		ET::activityModel();
		ETActivityModel::addType("postMember", array(
			"notification" => array(__CLASS__, "postMemberNotification"),
			"email" => array(__CLASS__, "postMemberEmail")
		));

		// Define the postMember email language text.
		ET::define("email.postMember.body", "<p><strong>%1\$s</strong> has posted in a conversation: <strong>%2\$s</strong></p><hr>%3\$s<hr><p>To view the new activity, check out the following link:<br>%4\$s</p>");
		ET::define("email.postMember.subject", "There is a new post by %1\$s");
	}

	// Add a follow button to each member's profile.
	public function handler_memberController_initProfile($sender, $member, $panes, $controls, $actions)
	{
		if (!ET::$session->user) return;

		$starred = @$member["follow"];
		
		$url = URL("member/follow/".$member["memberId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL));
		$actions->add("follow", "<a href='$url' class='button' title='".T("Follow to receive notifications")."' data-id='{$member["memberId"]}'><span class='star".($starred ? " starOn" : "")."'></span> <span>".($starred ? T("Following") : T("Follow"))."</span></a>", 0);
	}

	// Add an action to toggle the following status of a member.
	public function memberController_follow($controller, $memberId = "")
	{
		if (!ET::$session->user or !$controller->validateToken()) return;

		// Make sure the member that we're trying to follow exists.
		if (!ET::SQL()->select("memberId")->from("member")->where("memberId", (int)$memberId)->exec()->numRows()) return;

		// Work out if we're already followed or not, and switch to the opposite of that.
		$followed = !ET::SQL()
			->select("follow")
			->from("member_member")
			->where("memberId1", ET::$session->userId)
			->where("memberId2", (int)$memberId)
			->exec()
			->result();

		// Write to the database.
		ET::memberModel()->setStatus(ET::$session->userId, $memberId, array("follow" => $followed));

		// Normally, redirect back to the member profile.
		if ($controller->responseType === RESPONSE_TYPE_DEFAULT) redirect(URL("member/".$memberId));

		// Otherwise, set a JSON var.
		$controller->json("follow", $followed);
		$controller->render();
	}
	
	// Send out notifications to people who have starred the member that made a post.
	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		// Only continue if this is the first post.
		if ($conversation["countPosts"] > 1) return;
		
		// We get all members who have starred the post author and have no unread posts in the conversation.
		$sql = ET::SQL()
			->from("member_member mm2", "mm2.memberId2=:userId AND mm2.memberId1=m.memberId AND mm2.follow=1 AND mm2.memberId1!=:userId", "inner")
			->from("member_conversation co", "co.conversationId=:conversationId AND co.type='member' AND co.id=m.memberId", "left")
			->where("co.lastRead IS NULL OR co.lastRead>=:posts")
			->bind(":conversationId", $conversation["conversationId"])
			->bind(":posts", $conversation["countPosts"] - 1)
			->bind(":userId", ET::$session->userId);
		$members = ET::memberModel()->getWithSQL($sql);

		$data = array(
			"conversationId" => $conversation["conversationId"],
			"postId" => $postId,
			"title" => $conversation["title"]
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

			ET::activityModel()->create("postMember", $member, ET::$session->user, $data, $emailData);
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
		$form->setValue("postMember", ET::$session->preference("email.postMember"));
		$form->addField("notifications", "postMember", array(__CLASS__, "fieldEmailPostMember"), array($sender, "saveEmailPreference"), array("after" => "post"));
	}

	public static function fieldEmailPostMember($form)
	{
		return "<label class='checkbox'>".$form->checkbox("postMember")." <span class='star starOn'>*</span> ".T("Email me when there is a new post by a member I have followed")."</label>";
	}

	// Format the postMember notification.
	public static function postMemberNotification(&$item)
	{
		return array(
			sprintf(T("%s posted in %s."), "<span class='star starOn'>*</span> ".name($item["fromMemberName"]), "<strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
			URL(postURL($item["postId"]))
		);
	}

	// Format the postMember email.
	public static function postMemberEmail($item, $member)
	{
		$content = ET::formatter()->init($item["data"]["content"])->basic(true)->format()->get();
		return array(
			sprintf(T("email.postMember.subject"), name($item["fromMemberName"], false)),
			sprintf(T("email.postMember.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), $content, URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"])."/unread", true))
		);
	}

}
