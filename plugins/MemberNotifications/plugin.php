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

	function init()
	{
		// Add the postMember activity type.
		ET::activityModel();
		ETActivityModel::addType("postMember", array(
			"notification" => array(__CLASS__, "postMemberNotification"),
			"email" => array(__CLASS__, "postMemberEmail")
		));

		ET::define("email.postMember.body", "%1\$s has posted in a conversation: '%2\$s'.\n\nTo view the new activity, check out the following link:\n%3\$s");
		ET::define("email.postMember.subject", "There is a new post by '%1\$s'");
	}

	/**
	 * Returns a formatted notification item for the "postMember" activity type. For example, '[member]
	 * posted in [*channel] [title]'.
	 *
	 * @param array $item The activity item's details.
	 * @return array 0 => notification body, 1 => notification link
	 */
	public static function postMemberNotification(&$item)
	{
		return array(
			sprintf(T("%s posted in %s."), "<span class='star starOn'>*</span> ".$item["fromMemberName"], "<strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
			URL(postURL($item["postId"]))
		);
	}

	/**
	 * Returns a formatted email subject+body for the "postMember" activity type.
	 *
	 * @see mentionEmail() for parameter and return information.
	 */
	public static function postMemberEmail($item, $member)
	{
		return array(
			sprintf(T("email.postMember.subject"), sanitizeHTML($item["fromMemberName"])),
			sprintf(T("email.postMember.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"])."/unread", true))
		);
	}

	public function handler_memberController_initProfile($sender, $member, $panes, $controls, $actions)
	{
		if (!ET::$session->user) return;

		$starred = @$member["follow"];
		
		$url = URL("member/follow/".$member["memberId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL));
		$actions->add("follow", "<a href='$url' class='button' title='".T("Follow to receive notifications")."' data-id='{$member["memberId"]}'><span class='star".($starred ? " starOn" : "")."'></span> <span>".($starred ? T("Following") : T("Follow"))."</span></a>");
	}

	/**
	 * Toggle the user's subscription to a member.
	 *
	 * @param int $memberId The ID of the member to toggle subscription to.
	 * @return void
	 */
	public function memberController_follow($controller, $memberId = "")
	{
		if (!ET::$session->user or !$controller->validateToken()) return;

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

	public function handler_conversationModel_addReplyAfter($sender, $conversation, $postId, $content)
	{
		// Send out notifications to people who have starred the member that made the post.
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

		foreach ($members as $member) {
			ET::activityModel()->create("postMember", $member, ET::$session->user, $data);
		}
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		// Add the "email me when someone replies to a conversation in a channel I have followed" field.
		$form->setValue("postMember", ET::$session->preference("email.postMember"));
		$form->addField("notifications", "postMember", array(__CLASS__, "fieldEmailpostMember"), array($sender, "saveEmailPreference"), array("after" => "post"));
	}

	public static function fieldEmailpostMember($form)
	{
		return "<label class='checkbox'>".$form->checkbox("postMember")." <span class='star starOn'>*</span> ".T("Email me when there is a new post by a member I have followed")."</label>";
	}

	public function setup($oldVersion = "")
	{
		$structure = ET::$database->structure();
		$structure->table("member_member")
			->column("follow", "bool", 0)
			->exec(false);

		return true;
	}

}
