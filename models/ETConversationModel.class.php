<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The conversation model provides functions for retrieving and managing conversation data. It also provides
 * methods to handle conversation "labels".
 *
 * @package esoTalk
 */
class ETConversationModel extends ETModel {


/**
 * An array of conversation "labels". A label is a flag that can apply to a conversation (sticky,
 * private, draft, etc.) The array is in the form labelName => SQL expression (eg. IF(c.sticky,1,0))
 *
 * @var array
 */
public static $labels = array();


/**
 * Class constructor; sets up the base model functions to use the conversation table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("conversation");
}


/**
 * Adds a label to the collection.
 *
 * @param string $label The name of the label.
 * @param string $expression The SQL expression that will determine whether or not the label is active.
 * @return void
 */
public static function addLabel($label, $expression)
{
	self::$labels[$label] = $expression;
}


/**
 * Adds a SELECT field to an SQL query which will get the active state of conversation labels.
 *
 * We add one field, which we name 'labels', which contains a comma-separated list of
 * label expressions defined by addLabel(). This field can then be expanded using expandLabels().
 *
 * @param ETSQLQuery The SQL query to add the SELECT component to.
 * @return void
 */
public static function addLabels(&$sql)
{
	if (count(self::$labels)) $sql->select("CONCAT_WS(',',".implode(",", self::$labels).")", "labels");
	else $sql->select("NULL", "labels");
}


/**
 * Expands the value of a label field, added by addLabels(), to an array of active labels.
 *
 * @param string $labels The value of the 'label' field.
 * @return array An array of active labels.
 */
public static function expandLabels($labels)
{
	$active = array();
	if (count(self::$labels)) {
		$labels = explode(",", $labels);
		$i = 0;
		foreach (self::$labels as $k => $v) {
			if (!empty($labels[$i])) $active[] = $k;
			$i++;
		}
	}
	return $active;
}


/**
 * Add a WHERE predicate to an SQL query which will filter out conversations that the user is not
 * allowed to see.
 *
 * @param ETSQLQuery $sql The SQL query to add the WHERE predicate to.
 * @param array $member The member to filter out conversations for. If not specified, the currently
 * 		logged-in user will be used.
 * @param string $table The conversation table alias used in the SQL query.
 * @return void
 */
// Get a WHERE clause that makes sure the currently logged in user is allowed to view a conversation.
public function addAllowedPredicate(&$sql, $member = false, $table = "c")
{
	// If no member was specified, use the current user.
	if (!$member) $member = ET::$session->user;

	// If the user is a guest, they can only see conversations that are not drafts and that are not private.
	if (!$member) $sql->where("$table.countPosts>0")->where("$table.private=0");

	// If the user is logged in...
	else {

		// Construct a query to get a list of conversationIds that the user is explicitly allowed in.
		$allowedQuery = ET::SQL()
			->select("conversationId")
			->from("member_conversation")
			->where("(type='member' AND id=:allowedMemberId) OR (type='group' AND id IN (:allowedGroupIds))")
			->where("allowed=1")
			->get();

		// They must be the start member, or the conversation mustn't be a draft or private. If it is private, they must be allowed, using the query above.
		$sql->where("($table.startMemberId=:startMemberId OR ($table.countPosts>0 AND ($table.private=0 OR $table.conversationId IN ($allowedQuery))))")
			->bind(":allowedMemberId", $member["memberId"])
			->bind(":allowedGroupIds", ET::groupModel()->getGroupIds($member["account"], array_keys($member["groups"])))
			->bind(":startMemberId", $member["memberId"]);

	}

	// Additionally, the user must be allowed to view the channel that the conversation is in.
	ET::channelModel()->addPermissionPredicate($sql, "view", $member, $table);
}


/**
 * Get a single conversation's details.
 *
 * This function returns an array of fields which is that "standard" for conversation data structure
 * within this model.
 *
 * @param array $wheres An array of WHERE conditions. Regardless of how many conversations match, only
 * 		the first will be returned.
 * @return array The conversation details array.
 */
public function get($wheres = array())
{
	$sql = ET::SQL()
		->select("s.*")
		->select("c.*")
		->select("sm.username", "startMember")
		->select("sm.avatarFormat", "startMemberAvatarFormat")
		->select("ch.title", "channelTitle")
		->select("ch.description", "channelDescription")
		->select("ch.slug", "channelSlug")

		// Get the groups that are allowed to view this channel, and the names of those groups.
		->select("GROUP_CONCAT(pv.groupId)", "channelPermissionView")
		->select("GROUP_CONCAT(IF(pvg.name IS NOT NULL, pvg.name, ''))", "channelPermissionViewNames")

		// Join the appropriate tables.
		->from("conversation c")
		->from("channel ch", "c.channelId=ch.channelId", "left")
		->from("channel_group pv", "c.channelId=pv.channelId AND pv.view=1", "left")
		->from("group pvg", "pv.groupId=pvg.groupId", "left")
		->from("member_conversation s", "s.conversationId=c.conversationId AND type='member' AND s.id=:userId", "left")->bind(":userId", ET::$session->userId)
		->from("member sm", "sm.memberId=c.startMemberId", "inner")

		->where($wheres)
		->groupBy("c.channelId")
		->limit(1);

	// Fetch the labels field as well.
	$this->addLabels($sql);

	// Make sure the user is allowed to view this conversation.
	$this->addAllowedPredicate($sql);

	// Fetch the user's reply and moderate permissions for this conversation.
	if (!ET::$session->isAdmin()) {
		$sql->select("BIT_OR(p.reply)", "canReply")
			->select("BIT_OR(p.moderate)", "canModerate")
			->from("channel_group p", "c.channelId=p.channelId AND p.groupId IN (:groupIds)", "left")
			->bind(":groupIds", ET::$session->getGroupIds());
	}
	// If the user is an administrator, they can always reply and moderate.
	else {
		$sql->select("1", "canReply")
			->select("1", "canModerate");
	}

	// Execute the query.
	$result = $sql->exec();
	if (!$result->numRows()) return false;

	// Get all the details from the result into an array.
	$conversation = $result->firstRow();

	// Expand the labels field into a simple array of active labels.
	$conversation["labels"] = $this->expandLabels($conversation["labels"]);

	// Convert the separate groups who have permission to view this channel ID/name fields into one.
	$conversation["channelPermissionView"] = $this->formatGroupsAllowed($conversation["channelPermissionView"], $conversation["channelPermissionViewNames"]);

	// If the conversation is locked and the user can't moderate, then they can't reply.
	if ($conversation["locked"] and !$conversation["canModerate"]) $conversation["canReply"] = false;

	return $conversation;
}


/**
 * Get the conversation that the specified $postId is contained within.
 *
 * @param int $postId The ID of the post.
 * @return array The conversation.
 * @see get()
 */
public function getByPostId($postId)
{
	$subquery = ET::SQL()
		->select("conversationId")
		->from("post")
		->where("postId=:postId")
		->bind(":postId", (int)$postId)
		->get();
	return $this->get("c.conversationId=($subquery)");
}


/**
 * Get conversation data for the specified conversation ID.
 *
 * @param int $id The ID of the conversation.
 * @return array The conversation.
 * @see get()
 */
public function getById($id)
{
	return $this->get(array("c.conversationId" => (int)$id));
}


/**
 * Get an empty conversation details array for a non-existent conversation.
 *
 * @return array The conversation details array.
 */
public function getEmptyConversation()
{
	$conversation = array(
		"conversationId" => null,
		"title" => "",
		"startMemberId" => ET::$session->userId,
		"startMemberName" => ET::$session->user["username"],
		"startMemberAvatarFormat" => ET::$session->user["avatarFormat"],
		"countPosts" => 0,
		"lastRead" => 0,
		"draft" => "",
		"private" => false,
		"starred" => false,
		"muted" => false,
		"locked" => false,
		"channelId" => ET::$session->get("channelId"),
		"channelTitle" => "",
		"channelDescription" => "",
		"channelSlug" => "",
		"channelPermissionView" => array(),
		"labels" => array(),
		"canModerate" => true,
		"canReply" => true
	);
	// Add the private label if there are entities in the membersAllowed session store.
	if (ET::$session->get("membersAllowed")) {
		$conversation["private"] = true;
		$conversation["labels"][] = "private";
	}

	// Get the channel info.
	$result = ET::SQL()
		->select("c.title")
		->select("c.description")
		->select("c.slug")
		->select("GROUP_CONCAT(pv.groupId)", "channelPermissionView")
		->select("GROUP_CONCAT(IF(pvg.name IS NOT NULL, pvg.name, ''))", "channelPermissionViewNames")
		->from("channel c")
		->from("channel_group pv", "pv.channelId=c.channelId", "left")
		->from("group pvg", "pv.groupId=pvg.groupId", "left")
		->where("c.channelId=:channelId")
		->bind(":channelId", $conversation["channelId"])
		->where("pv.view=1")
		->groupBy("pv.channelId")
		->limit(1)
		->exec();
	list($conversation["channelTitle"], $conversation["channelDescription"], $conversation["channelSlug"], $conversation["channelPermissionView"], $channelPermissionViewNames) = array_values($result->firstRow());

	// Convert the separate groups who have permission to view this channel ID/name fields into one.
	$conversation["channelPermissionView"] = $this->formatGroupsAllowed($conversation["channelPermissionView"], $channelPermissionViewNames);

	return $conversation;
}


/**
 * Combines two separate strings of group IDs and names into one (id => name).
 *
 * When we fetch conversation details in get() and getNew(), we select a field which contains a
 * comma-separated list of group IDs which are allowed to view the conversation's channel, and a field
 * with the names of those groups. This function combines those two fields into one nice array.
 *
 * @param string $permissionView The comma-separated list of group IDs.
 * @param string $permissionViewNames The comma-separated list of respective group names.
 * @return array A nice array of groupId => names.
 */
private function formatGroupsAllowed($permissionView, $permissionViewNames)
{
	// Get a list of group IDs that are allowed to view the channel.
	$permissionView = array_combine(explode(",", $permissionView), explode(",", $permissionViewNames));
	if (isset($permissionView[GROUP_ID_GUEST])) $permissionView[GROUP_ID_GUEST] = ACCOUNT_GUEST;
	if (isset($permissionView[GROUP_ID_MEMBER])) $permissionView[GROUP_ID_MEMBER] = ACCOUNT_MEMBER;

	// Add in administrators if they're not already in there, because they can always see every channel.
	$permissionView[GROUP_ID_ADMINISTRATOR] = ACCOUNT_ADMINISTRATOR;
	$permissionView = array_filter($permissionView);

	return $permissionView;
}



/**
 * Get a list of members who are explicitly allowed to view the given conversation.
 * Only members who have been explicitly added to the members allowed list will be returned;
 * this function returns an empty array for non-private conversations.
 *
 * @see getMembersAllowedSummary() for an effective list of members/groups who are allowed to view
 * 		a conversation (which takes channel permissions into consideration.)
 * @param array The conversation details.
 * @return array An array of entities allowed. Each entry is an array with the following elements:
 * 		type: can be either 'member' or 'group'
 * 		id: the ID of the entity (memberId or groupId)
 * 		name: the name of the entity
 * 		avatarFormat: the member's avatarFormat field (not relevant for groups)
 * 		groups: an array of groups which the member is in (not relevant for groups)
 */
public function getMembersAllowed($conversation)
{
	$membersAllowed = array();

	// If the conversation is not private, then everyone can view it - return an empty array.
	if (!$conversation["private"] and $conversation["conversationId"]) return $membersAllowed;

	// Construct separate queries for getting a list of the members and groups allowed in a conversation.
	// We will marry these later on.
	$qMembers = ET::SQL()
		->select("'member'", "type")
		->select("CAST(".($conversation["conversationId"] ? "s.id" : "m.memberId")." AS SIGNED)")
		->select("m.username")
		->select("m.avatarFormat")
		->select("m.account")
		->select("GROUP_CONCAT(g.groupId)")
		->groupBy("m.memberId");

	$qGroups = ET::SQL()
		->select("'group'", "type")
		->select("s.id", "id")
		->select("g.name")
		->select("NULL")
		->select("NULL")
		->select("NULL");

	// If the conversation doesn't exist, the members allowed are in stored in the session.
	// We'll have to get details from the database using the IDs stored in the session.
	if (!$conversation["conversationId"]) {

		$groups = $members = array();
		$sessionMembers = (array)ET::$session->get("membersAllowed");
		foreach ($sessionMembers as $member) {
			if ($member["type"] == "group") {

				// The adminisrtator/member groups aren't really groups, so we can't query the database
				// for their information. Instead, add them to the members allowed array manually.
				if ($member["id"] == GROUP_ID_ADMINISTRATOR or $member["id"] == GROUP_ID_MEMBER) {
					if ($member["id"] == GROUP_ID_ADMINISTRATOR) $name = ACCOUNT_ADMINISTRATOR;
					elseif ($member["id"] == GROUP_ID_MEMBER) $name = ACCOUNT_MEMBER;
					$membersAllowed[] = array("type" => "group", "id" => $member["id"], "name" => $name, "avatarFormat" => null, "groups" => null);
				}

				else $groups[] = $member["id"];
			}
			else $members[] = $member["id"];
		}

		if (!count($members)) $members[] = null;
		if (!count($groups)) $groups[] = null;

		// Get member details directly from the members table, and the group details directly from the groups table.
		$qMembers->from("member m")->where("m.memberId IN (:memberIds)")->bind(":memberIds", $members);
		$qGroups->select("g.groupId", "id")->from("group g")->where("g.groupId IN (:groupIds)")->bind(":groupIds", $groups);

	}

	// If the conversation does exist, we'll get the members allowed from the database.
	else {

		$qMembers->from("member_conversation s")
			->from("member m", "s.id=m.memberId", "left")
			->where("s.conversationId=:conversationId")->bind(":conversationId", $conversation["conversationId"])
			->where("s.allowed=1")
			->where("s.type='member'");
		$qGroups->from("member_conversation s")
			->from("group g", "s.id=g.groupId", "left")
			->where("s.conversationId=:conversationId")->bind(":conversationId", $conversation["conversationId"])
			->where("s.allowed=1")
			->where("s.type='group'");

	}

	// Any objections?
	$qMembers->from("member_group g", "m.memberId=g.memberId", "left");

	// You may now kiss the bride.
	$result = ET::SQL("(".$qMembers->get().") UNION (".$qGroups->get().")");

	// Go through the results and construct our final "members allowed" array.
	while ($entity = $result->nextRow()) {
		list($type, $id, $name, $avatarFormat, $account, $groups) = array_values($entity);
		$groups = ET::groupModel()->getGroupIds($account, explode(",", $groups));
		if ($type == "group") {
			if ($id == GROUP_ID_ADMINISTRATOR) $name = ACCOUNT_ADMINISTRATOR;
			elseif ($id == GROUP_ID_MEMBER) $name = ACCOUNT_MEMBER;
		}
		$membersAllowed[] = array("type" => $type, "id" => $id, "name" => $name, "avatarFormat" => $avatarFormat, "groups" => $groups);
	}

	// Sort the entities by name.
	$membersAllowed = sort2d($membersAllowed, "name", "asc", true, false);

	return $membersAllowed;
}


/**
 * Get a list of members who are effectively allowed to view the given conversation.
 * This function will take into account both the members explicitly allowed to view a conversation
 * and who has permission to view the conversation's channel.
 *
 * @see getMembersAllowed()
 * @param array The conversation details.
 * @param array An array of members explicitly allowed in the conversation, from getMembersAllowed().
 * @return array An array of entities allowed in the same format as the return value of getMembersAllowed().
 */
public function getMembersAllowedSummary($conversation, $membersAllowed = array())
{
	$groups = array();
	$members = array();

	$channelGroupIds = array_keys($conversation["channelPermissionView"]);

	// If the conversation ISN'T private...
	if (!$conversation["private"]) {

		// If guests aren't allowed to view this channel (i.e. not everyone), then we need to
		// explicitly show who can view the channel.
		if (!in_array(GROUP_ID_GUEST, $channelGroupIds)) {

			// If members can view the channel, that covers everyone.
			if (in_array(GROUP_ID_MEMBER, $channelGroupIds)) $groups[GROUP_ID_MEMBER] = ACCOUNT_MEMBER;

			// Otherwise, go through each of the groups who can view the channel and add them to the groups array for later.
			else {
				foreach ($channelGroupIds as $id) $groups[$id] = $conversation["channelPermissionView"][$id];
			}

		}
	}

	// If the conversation IS private...
	else {

		// Sort the members.
		$count = count($membersAllowed);

		// Loop through the members allowed and filter out all the groups and members into separate arrays.
		foreach ($membersAllowed as $k => $member) {

			if ($member["type"] == "group") {
				// Only add the group to the final list if it is allowed to view the channel.
				if (!ET::groupModel()->groupIdsAllowedInGroupIds($member["id"], $channelGroupIds)) continue;
				$groups[$member["id"]] = $member["name"];
			}
			else {
				// Only add the member to the final list if they are allowed to view the channel.
				if (!ET::groupModel()->groupIdsAllowedInGroupIds($member["groups"], $channelGroupIds)) continue;
				$members[] = $member;
			}

		}

	}

	// Now, create a final list of members/groups who can view this conversation.
	$membersAllowedSummary = array();

	// If members are allowed to view this conversation, just show that (as members covers all members.)
	if (isset($groups[GROUP_ID_MEMBER])) {
		$membersAllowedSummary[] = array("type" => "group", "id" => GROUP_ID_MEMBER, "name" => ACCOUNT_MEMBER);
	}

	else {

		// Loop through the groups allowed and add them to the summary.
		foreach ($groups as $id => $name) {
			$membersAllowedSummary[] = array("type" => "group", "id" => $id, "name" => $name);
		}

		// Loop through the members allowed and add them to the summary.
		$groupIds = array_keys($groups);
		foreach ($members as $member) {

			// If the member is already covered by one of the groups being displayed, don't show them.
			if (ET::groupModel()->groupIdsAllowedInGroupIds($member["groups"], $groupIds) or !$member["name"]) continue;

			$membersAllowedSummary[] = $member;

		}

	}

	// Whew! All done. Hopefully that wasn't too confusing.
	return $membersAllowedSummary;
}


/**
 * Start a new converastion. Assumes the creator is the currently logged in user.
 *
 * @param array $data An array of the conversation's details: title, channelId, content.
 * @param array $membersAllowed An array of entities allowed to view the conversation, in the same format
 * 		as the return value of getMembersAllowed()
 * @param bool $isDraft Whether or not the conversation is a draft.
 * @return bool|array An array containing the new conversation ID and the new post ID, or false if
 * 		there was an error.
 */
public function create($data, $membersAllowed = array(), $isDraft = false)
{
	// We can't do this if we're not logged in.
	if (!ET::$session->user) return false;

	// If the title is blank but the user is only saving a draft, call it "Untitled conversation."
	if ($isDraft and !$data["title"]) $data["title"] = T("Untitled conversation");

	// Check for errors; validate the title and the post content.
	$this->validate("title", $data["title"], array($this, "validateTitle"));
	$this->validate("content", $data["content"], array(ET::postModel(), "validateContent"));
	$content = $data["content"];
	unset($data["content"]);

	// Flood control!
	if (ET::$session->isFlooding()) $this->error("flooding", "waitToReply");

	// Make sure that we have permission to post in this channel.
	$data["channelId"] = (int)$data["channelId"];
	if (!ET::channelModel()->hasPermission($data["channelId"], "start"))
		$this->error("channelId", "invalidChannel");

	// Did we encounter any errors? Don't continue.
	if ($this->errorCount()) return false;

	// Add some more data fields to insert into the database.
	$time = time();
	$data["startMemberId"] = ET::$session->userId;
	$data["startTime"] = $time;
	$data["lastPostMemberId"] = ET::$session->userId;
	$data["lastPostTime"] = $time;
	$data["private"] = !empty($membersAllowed);
	$data["countPosts"] = $isDraft ? 0 : 1;

	// Insert the conversation into the database.
	$conversationId = parent::create($data);

	// Update the member's conversation count.
	ET::SQL()
		->update("member")
		->set("countConversations", "countConversations + 1", false)
		->where("memberId", ET::$session->userId)
		->exec();

	// Update the channel's converastion count.
	ET::SQL()
		->update("channel")
		->set("countConversations", "countConversations + 1", false)
		->where("channelId", $data["channelId"])
		->exec();

	// Whip up a little array fo conversation details for this model's functions to work with.
	$conversation = array(
		"conversationId" => $conversationId,
		"title" => $data["title"],
		"canReply" => true,
		"labels" => array()
	);

	// Add the first post or save the draft.
	$postId = null;
	if ($isDraft) {
		$this->setDraft($conversation, ET::$session->userId, $content);
	}
	else {
		$postId = ET::postModel()->create($conversationId, ET::$session->userId, $content);

		// If the conversation is private, send out notifications to the allowed members.
		if (!empty($membersAllowed)) {
			$memberIds = array();
			foreach ($membersAllowed as $member) {
				if ($member["type"] == "member") $memberIds[] = $member["id"];
			}
			ET::conversationModel()->privateAddNotification($conversation, $memberIds, true);
		}
	}

	// If the conversation is private, add the allowed members to the database.
	if (!empty($membersAllowed)) {
		$inserts = array();
		foreach ($membersAllowed as $member) $inserts[] = array($conversationId, $member["type"], $member["id"], 1);
		ET::SQL()
			->insert("member_conversation")
			->setMultiple(array("conversationId", "type", "id", "allowed"), $inserts)
			->setOnDuplicateKey("allowed", 1)
			->exec();
	}

	return array($conversationId, $postId);
}


/**
 * Add a reply to an existing conversation. Assumes the creator is the currently logged in user.
 *
 * @param array $conversation The conversation to add the reply to. The conversation's details will
 * 		be updated (post count, last post time, etc.)
 * @param string $content The post content.
 * @return int|bool The new post's ID, or false if there was an error.
 */
public function addReply(&$conversation, $content)
{
	// We can't do this if we're not logged in.
	if (!ET::$session->user) return false;

	// Flood control!
	if (ET::$session->isFlooding()) {
		$this->error("flooding", sprintf(T("message.waitToReply"), C("esoTalk.conversation.timeBetweenPosts")));
		return false;
	}

	// Start a notification group. This means that for all notifications sent out until endNotifcationGroup
	// is called, each individual user will receive a maximum of one.
	ET::activityModel()->startNotificationGroup();

	// Create the post. If there were validation errors, get them from the post model and add them to this model.
	$postModel = ET::postModel();
	$postId = $postModel->create($conversation["conversationId"], ET::$session->userId, $content);
	if (!$postId) $this->error($postModel->errors());

	// Did we encounter any errors? Don't continue.
	if ($this->errorCount()) return false;

	// Update the conversations table with the new post count, last post/action times, and last post member.
	$time = time();
	$update = array(
		"countPosts" => $conversation["countPosts"] + 1,
		"lastPostMemberId" => ET::$session->userId,
		"lastPostTime" => $time,
	);
	// Also update the conversation's start time if this is the first post.
	if ($conversation["countPosts"] == 0) $update["startTime"] = $time;

	$this->updateById($conversation["conversationId"], $update);

	// If the user had a draft saved in this conversation before adding this reply, erase it now.
	// Also, if the user has the "star on reply" option checked, star the conversation.
	$update = array();
	if ($conversation["draft"]) $update["draft"] = null;
	if (ET::$session->preference("starOnReply")) $update["starred"] = true;
	if (count($update)) {
		$this->setStatus($conversation, ET::$session->userId, $update);
	}

	// Send out notifications to people who have starred this conversation.
	// We get all members who have starred the conversation and have no unread posts in it.
	$sql = ET::SQL()
		->from("member_conversation s", "s.conversationId=:conversationId AND s.type='member' AND s.id=m.memberId AND s.starred=1 AND s.lastRead>=:posts AND s.id!=:userId", "inner")
		->bind(":conversationId", $conversation["conversationId"])
		->bind(":posts", $conversation["countPosts"])
		->bind(":userId", ET::$session->userId);
	$members = ET::memberModel()->getWithSQL($sql);

	$data = array(
		"conversationId" => $conversation["conversationId"],
		"postId" => $postId,
		"title" => $conversation["title"]
	);

	foreach ($members as $member) {
		ET::activityModel()->create("post", $member, ET::$session->user, $data);
	}

	// Update the conversation post count.
	$conversation["countPosts"]++;

	// If this is the first reply (ie. the conversation was a draft and now it isn't), send notifications to
	// members who are in the membersAllowed list.
	if ($conversation["countPosts"] == 1 and !empty($conversation["membersAllowed"])) {
		$memberIds = array();
		foreach ($conversation["membersAllowed"] as $member) {
			if ($member["type"] == "member") $memberIds[] = $member["id"];
		}
		$this->privateAddNotification($conversation, $memberIds, true);
	}

	ET::activityModel()->endNotificationGroup();

	return $postId;
}


/**
 * Delete a conversation, and all its posts and other associations.
 *
 * @param array $wheres An array of WHERE predicates.
 * @return bool true on success, false on error.
 */
public function delete($wheres = array())
{
	// Get conversation IDs that match these WHERE conditions.
	$ids = array();
	$result = ET::SQL()->select("conversationId")->from("conversation c")->where($wheres)->exec();
	while ($row = $result->nextRow()) $ids[] = $row["conversationId"];

	// Delete the conversation, posts, member_conversation, and activity rows.
	ET::SQL()
		->delete("c, m, p")
		->from("conversation c")
		->from("member_conversation m", "m.conversationId=c.conversationId", "left")
		->from("post p", "p.conversationId=c.conversationId", "left")
		->from("activity a", "a.conversationId=c.conversationId", "left")
		->where("c.conversationId IN (:conversationIds)")
		->bind(":conversationIds", $ids)
		->exec();

	return true;
}


/**
 * Delete an existing record in the model's table with a particular ID.
 *
 * @param mixed $id The ID of the record to delete.
 * @return ETSQLResult
 */
public function deleteById($id)
{
	return $this->delete(array("c.conversationId" => $id));
}


/**
 * Set a member's status entry for a conversation (their record in the member_conversation table.)
 * This should not be used directly for setting a draft or 'muted'. setDraft and setMuted should be
 * used for that.
 *
 * @param array $conversation The conversation to set the member's status for.
 * @param int $memberId The member to set the status for.
 * @param array $data An array of key => value data to save to the database.
 * @param string $type The entity type (group or member).
 * @return void
 */
public function setStatus(&$conversation, $memberId, $data, $type = "member")
{
	$keys = array(
		"type" => $type,
		"id" => $memberId,
		"conversationId" => $conversation["conversationId"]
	);
	ET::SQL()->insert("member_conversation")->set($keys + $data)->setOnDuplicateKey($data)->exec();
}


/**
 * Set a member's draft for a conversation.
 *
 * @param array $conversation The conversation to set the draft on. The conversation array's labels
 * 		and draft attribute will be updated.
 * @param int $memberId The member to set the status for.
 * @param string $draft The draft content.
 * @return bool Returns true on success, or false if there is an error.
 */
public function setDraft(&$conversation, $memberId, $draft = null)
{
	// Validate the post content if applicable.
	if ($draft !== null) $this->validate("content", $draft, array(ET::postModel(), "validateContent"));

	if ($this->errorCount()) return false;

	// Save the draft.
	$this->setStatus($conversation, $memberId, array("draft" => $draft));

	// Add or remove the draft label.
	$this->addOrRemoveLabel($conversation, "draft", $draft !== null);
	$conversation["draft"] = $draft;

	return true;
}


/**
 * Set a member's last read position for a conversation.
 *
 * @param array $conversation The conversation to set the last read position on. The conversation array's
 * 	lastRead attribute will be updated.
 * @param int $memberId The member to set the status for.
 * @param int $lastRead The position of the post that was last read.
 * @return bool Returns true on success, or false if there is an error.
 */
public function setLastRead(&$conversation, $memberId, $lastRead)
{
	$lastRead = min($lastRead, $conversation["countPosts"]);
	if ($lastRead <= $conversation["lastRead"]) return true;

	// Set the last read status.
	$this->setStatus($conversation, $memberId, array("lastRead" => $lastRead));

	$conversation["lastRead"] = $lastRead;

	return true;
}


/**
 * Set a member's muted flag for a conversation.
 *
 * @param array $conversation The conversation to set the draft on. The conversation array's labels
 * 		and muted attribute will be updated.
 * @param int $memberId The member to set the flag for.
 * @param bool $muted Whether or not to set the conversation to muted.
 * @return void
 */
public function setMuted(&$conversation, $memberId, $muted)
{
	$muted = (bool)$muted;

	$this->setStatus($conversation, $memberId, array("muted" => $muted));

	$this->addOrRemoveLabel($conversation, "muted", $muted);
	$conversation["muted"] = $muted;
}


/**
 * Set the sticky flag of a conversation.
 *
 * @param array $conversation The conversation to set the draft on. The conversation array's labels
 * 		and sticky attribute will be updated.
 * @param bool $sticky Whether or not the conversation is stickied.
 * @return void
 */
public function setSticky(&$conversation, $sticky)
{
	$sticky = (bool)$sticky;

	$this->updateById($conversation["conversationId"], array(
		"sticky" => $sticky
	));

	$this->addOrRemoveLabel($conversation, "sticky", $sticky);
	$conversation["sticky"] = $sticky;
}


/**
 * Set the locked flag of a conversation.
 *
 * @param array $conversation The conversation to set the draft on. The conversation array's labels
 * 		and locked attribute will be updated.
 * @param bool $locked Whether or not the conversation is locked.
 * @return void
 */
public function setLocked(&$conversation, $locked)
{
	$locked = (bool)$locked;

	$this->updateById($conversation["conversationId"], array(
		"locked" => $locked
	));

	$this->addOrRemoveLabel($conversation, "locked", $locked);
	$conversation["locked"] = $locked;
}


/**
 * Convenience method to add or remove a certain label from a conversation's labels array.
 *
 * @param array $conversation The conversation to add/remove the label from.
 * @param string $label The name of the label.
 * @param bool $add true to add the label, false to remove it.
 * @return void
 */
protected function addOrRemoveLabel(&$conversation, $label, $add = true)
{
	if ($add and !in_array($label, $conversation["labels"]))
		$conversation["labels"][] = $label;
	elseif (!$add and ($k = array_search($label, $conversation["labels"])) !== false)
		unset($conversation["labels"][$k]);
}


/**
 * Set the title of a conversation.
 *
 * @param array $conversation The conversation to set the title of. The conversation array's title
 * 		attribute will be updated.
 * @param string $title The new title of the conversation.
 * @return bool Returns true on success, or false if there is an error.
 */
public function setTitle(&$conversation, $title)
{
	$this->validate("title", $title, array($this, "validateTitle"));
	if ($this->errorCount()) return false;

	$this->updateById($conversation["conversationId"], array(
		"title" => $title
	));

	// Update the title column in the posts table as well (which is used for fulltext searching).
	ET::postModel()->update(array("title" => $title), array("conversationId" => $conversation["conversationId"]));

	$conversation["title"] = $title;

	return true;
}


/**
 * Validate the title of a conversation.
 *
 * @param string $title The conversation title.
 * @return bool|string Returns an error string or false if there are no errors.
 */
public function validateTitle($title)
{
	if (!strlen($title)) return "emptyTitle";
}


/**
 * Set the channel of a conversation.
 *
 * @param array $conversation The conversation to set the channel for. The conversation array's channelId
 * 		attribute will be updated.
 * @param int $channelId Whether or not the conversation is locked.
 * @return bool Returns true on success, or false if there is an error.
 */
public function setChannel(&$conversation, $channelId)
{
	if (!ET::channelModel()->hasPermission($channelId, "start")) $this->error("channelId", T("message.noPermission"));

	if ($this->errorCount()) return false;

	// Decrease the conversation/post count of the old channel.
	ET::SQL()->update("channel")
		->set("countConversations", "countConversations - 1", false)
		->set("countPosts", "countPosts - :posts", false)
		->bind(":posts", $conversation["countPosts"])
		->where("channelId=:channelId")
		->bind(":channelId", $conversation["channelId"])
		->exec();

	$this->updateById($conversation["conversationId"], array(
		"channelId" => $channelId
	));

	// Increase the conversation/post count of the new channel.
	ET::SQL()->update("channel")
		->set("countConversations", "countConversations + 1", false)
		->set("countPosts", "countPosts + :posts", false)
		->bind(":posts", $conversation["countPosts"])
		->where("channelId=:channelId")
		->bind(":channelId", $channelId)
		->exec();

	$conversation["channelId"] = $channelId;

	return true;
}


/**
 * Given a name (intended to be the input of the "add members allowed" form), this function finds a matching
 * group or member and returns an array of its details to be used in addMember().
 *
 * @param string $name The input.
 * @return bool|array Returns an array of the entity's details (in the same format as getMembersAllowed()),
 * 		or false if no entity was found.
 */
public function getMemberFromName($name)
{
	$memberId = $memberName = false;

	// Get a list of all member groups, and add administrators + members to it.
	$groups = ET::groupModel()->getAll();
	$groups[GROUP_ID_ADMINISTRATOR] = array("name" => ACCOUNT_ADMINISTRATOR);
	$groups[GROUP_ID_MEMBER] = array("name" => ACCOUNT_MEMBER);

	// Go through each of the groups and see if one of them matches the name. If so, return its details.
	$lowerName = strtolower($name);
	foreach ($groups as $id => $group) {
		$group = $group["name"];
		if ($lowerName == strtolower(T("group.$group.plural", $group)) or $lowerName == strtolower($group)) {
			return array("type" => "group", "id" => $id, "name" => $group);
		}
	}

	// Otherwise, search for a member in the database with a matching name.
	$name = str_replace("%", "", $name);
	$result = ET::SQL()
		->select("m.memberId")
		->select("m.username")
		->select("m.avatarFormat")
		->select("m.account")
		->select("GROUP_CONCAT(g.groupId)", "groups")
		->from("member m")
		->from("member_group g", "m.memberId=g.memberId", "left")
		->where("m.username=:name OR m.username LIKE :nameLike")
		->bind(":name", $name)
		->bind(":nameLike", $name."%")
		->groupBy("m.memberId")
		->orderBy("m.username=:nameOrder DESC")
		->bind(":nameOrder", $name)
		->limit(1)
		->exec();

	if (!$result->numRows()) return false;

	// Get the result and return it as an array.
	$row = $result->firstRow();
	$row["groups"] = ET::groupModel()->getGroupIds($row["account"], explode(",", $row["groups"]));
	return array("type" => "member", "id" => $row["memberId"], "name" => $row["username"], "avatarFormat" => $row["avatarFormat"], "groups" => $row["groups"]);
}


/**
 * Add a member to a conversation, i.e. give them permission to view it and make the conversation private.
 *
 * @param array $conversation The conversation to add the member to. The conversation array's membersAllowed
 * 		and private attributes will be updated.
 * @param array $member The entity to add. This can be from getMemberFromName().
 * @return void
 */
public function addMember(&$conversation, $member)
{
	// If the conversation exists, add this member to the database as allowed.
	if ($conversation["conversationId"]) {

		// Email the member(s) - we have to do this before we put them in the db because it will only email them if they
		// don't already have a record for this conversation in the status table.
		if ($conversation["countPosts"] > 0 and $member["type"] == "member") $this->privateAddNotification($conversation, $member["id"]);

		// Set the conversation's private field to true and update the last action time.
		if (!$conversation["private"]) {
			$this->updateById($conversation["conversationId"], array("private" => true));
			$conversation["private"] = true;
		}

		// Allow the member to view the conversation in the status table.
		$this->setStatus($conversation, $member["id"], array("allowed" => true), $member["type"]);

		// Make sure the the owner of the conversation is allowed to view it.
		$this->setStatus($conversation, $conversation["startMemberId"], array("allowed" => true));

	}

	// If the conversation doesn't exist, add this member to the session members allowed store.
	else {
		$membersAllowed = ET::$session->get("membersAllowed", array());

		$member = array("type" => $member["type"], "id" => $member["id"]);
		if (!in_array($member, $membersAllowed)) $membersAllowed[] = $member;

		// Make sure the the owner of the conversation is allowed to view it.
		$member = array("type" => "member", "id" => $conversation["startMemberId"]);
		if (!in_array($member, $membersAllowed)) $membersAllowed[] = $member;

		ET::$session->store("membersAllowed", $membersAllowed);
	}

	// Add the private label to the conversation.
	$this->addOrRemoveLabel($conversation, "private", true);
	$conversation["private"] = true;
}


/**
 * Remove a member from a conversation, i.e. revoke their permission to view it and make the conversation
 * not private if there are no members left.
 *
 * @param array $conversation The conversation to remove the member from. The conversation array's membersAllowed
 * 		and private attributes will be updated.
 * @param array $member The entity to remove. This should have two elements: type and id.
 * @return void
 */
public function removeMember(&$conversation, $member)
{
	// If the conversation exists, remove the member from the database.
	if ($conversation["conversationId"]) {

		// Disallow the member to view the conversation in the status table.
		// Also unstar the conversation so they will no longer receive email notifications.
		$this->setStatus($conversation, $member["id"], array("allowed" => false, "starred" => false), $member["type"]);

	}

	// Otherwise remove it from the session.
	else {
		$membersAllowed = ET::$session->get("membersAllowed", array());
		foreach ($membersAllowed as $k => $m) {
			if ($m["type"] == $member["type"] and $m["id"] == $member["id"]) unset($membersAllowed[$k]);
		}
		ET::$session->store("membersAllowed", $membersAllowed);
	}

	// Update the conversation's membersAllowed array.
	foreach ($conversation["membersAllowed"] as $k => $m) {
		if ($m["type"] == $member["type"] and $m["id"] == $member["id"]) unset($conversation["membersAllowed"][$k]);
	}

	// If there are no members left allowed in the conversation, then unmark the conversation as private.
	if (empty($conversation["membersAllowed"])) {
		$conversation["membersAllowed"] = array();
		$conversation["private"] = false;
		$this->addOrRemoveLabel($conversation, "private", false);

		// Turn off conversation's private field in the database.
		if ($conversation["conversationId"])
			$this->updateById($conversation["conversationId"], array("private" => false));
	}
}


/**
 * Send private conversation invitation notifications to a list of members. A notification will only
 * be sent if this is the first time a member has been added to the conversation, to prevent intentional
 * email spamming.
 *
 * @param array $conversation The conversation to that we're sending out notifications for.
 * @param array $memberIds A list of member IDs to send the notifications to.
 * @param bool $notifyAll If set to true, all members will be notified regardless of if they have been
 * 		added to this conversation before.
 * @return void
 */
protected function privateAddNotification($conversation, $memberIds, $notifyAll = false)
{
	$memberIds = (array)$memberIds;

	// Remove the currently logged in user from the list of member IDs.
	if (($k = array_search(ET::$session->userId, $memberIds)) !== false) unset($memberIds[$k]);

	if (!count($memberIds)) return;

	// Get the member details for this list of member IDs.
	$sql = ET::SQL()
		->from("member_conversation s", "s.conversationId=:conversationId AND s.type='member' AND s.id=m.memberId", "left")
		->bind(":conversationId", $conversation["conversationId"])
		->where("m.memberId IN (:memberIds)")
		->bind(":memberIds", $memberIds);

	// Only get members where the member_conversation row doesn't exist (implying that this is the first time
	// they've been added to the conversation.)
	if (!$notifyAll) $sql->where("s.id IS NULL");

	$members = ET::memberModel()->getWithSQL($sql);

	$data = array(
		"conversationId" => $conversation["conversationId"],
		"title" => $conversation["title"]
	);

	foreach ($members as $member) {
		ET::activityModel()->create("privateAdd", $member, ET::$session->user, $data);
	}
}

}


// Add default labels.
ETConversationModel::addLabel("sticky", "IF(c.sticky=1,1,0)");
ETConversationModel::addLabel("private", "IF(c.private=1,1,0)");
ETConversationModel::addLabel("locked", "IF(c.locked=1,1,0)");
ETConversationModel::addLabel("draft", "IF(s.draft IS NOT NULL,1,0)");
ETConversationModel::addLabel("muted", "IF(s.muted=1,1,0)");
