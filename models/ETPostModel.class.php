<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The post model provides functions for retrieving and managing post data.
 *
 * @package esoTalk
 */
class ETPostModel extends ETModel {


/**
 * Class constructor; sets up the base model functions to use the post table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("post");
}


/**
 * Get standardized post data given an SQL query (which can specify WHERE conditions, for example.)
 *
 * @param ETSQLQuery $sql The SQL query to use as a basis.
 * @return array An array of posts and their details.
 */
public function getWithSQL($sql)
{
	$sql->select("p.*")
		->select("m.memberId", "memberId")
		->select("m.username", "username")
		->select("m.account", "account")
		->select("m.avatarFormat", "avatarFormat")
		->select("em.memberId", "editMemberId")
		->select("em.username", "editMemberName")
		->select("dm.memberId", "deleteMemberId")
		->select("dm.username", "deleteMemberName")
		->select("m.lastActionTime", "lastActionTime")
		->select("m.lastActionDetail", "lastActionDetail")

		->select("GROUP_CONCAT(g.groupId)", "groups")
		->select("GROUP_CONCAT(g.name)", "groupNames")

		->from("post p")
		->from("member m", "m.memberId=p.memberId", "left")
		->from("member em", "em.memberId=p.editMemberId", "left")
		->from("member dm", "dm.memberId=p.deleteMemberId", "left")
		->from("member_group mg", "m.memberId=mg.memberId", "left")
		->from("group g", "g.groupId=mg.groupId", "left")

		->groupBy("p.postId")
		->orderBy("p.time ASC");

	$result = $sql->exec();

	// Loop through the results and compile them into an array of posts.
	$posts = array();
	while ($post = $result->nextRow()) {

		$post["groups"] = array_combine(explode(",", $post["groups"]), explode(",", $post["groupNames"]));
		$posts[] = $post;

	}

	return $posts;
}


/**
 * Get standardized post data.
 *
 * @param array $wheres An array of where conditions.
 * @return array An array of posts and their details.
 */
public function get($wheres = array())
{
	$sql = ET::SQL();
	$sql->where($wheres);

	return $this->getWithSQL($sql);
}


/**
 * Get post data for the specified post ID.
 *
 * @param int $postId The ID of the post.
 * @return array An array of the post's details.
 */
public function getById($postId)
{
	return reset($this->get(array("p.postId" => $postId)));
}


/**
 * Get an array of posts from a certain conversation.
 *
 * @param int $conversationId The ID of the conversation to get posts from.
 * @param array $criteria An array of options to get a more specific range of posts. Can have the following:
 * 		startFrom: the post index to start from
 * 		limit: the number of posts to get
 * 		time: only get posts which were created after this time
 * 		search: only get posts matching this fulltext string
 * @return array An array of resulting posts and their details.
 */
public function getByConversation($conversationId, $criteria = array())
{
	$sql = ET::SQL()
		->where("p.conversationId=:conversationId")
		->bind(":conversationId", $conversationId);

	// If we're getting posts based on the when they were created...
	if (isset($criteria["time"])) {
		$time = (int)$criteria["time"];
		$sql->where("time>:time1")
			->bind(":time1", $time);
	}

	// If we're gettings posts based on a fulltext search...
	if (isset($criteria["search"]))
		$this->whereSearch($sql, $criteria["search"]);

	// Impose an offset/limit if necessary.
	if (isset($criteria["startFrom"])) $sql->offset(abs($criteria["startFrom"]));
	if (isset($criteria["limit"])) $sql->limit(abs($criteria["limit"]));

	// Get the posts!
	$posts = $this->getWithSQL($sql);

	return $posts;
}

/**
 * Get the number of search results in a conversation for a search string.
 *
 * @param int $conversationId The ID of the conversation that's being searched.
 * @param string $search The search string.
 * @return int The number of posts that match the string in a fulltext search.
 */
public function getSearchResultsCount($conversationId, $search)
{
	$sql = ET::SQL()
		->select("COUNT(1)")
		->from("post")
		->where("conversationId=:conversationId")
		->bind(":conversationId", $conversationId);
	$this->whereSearch($sql, $search);
	return $sql->exec()->result();
}


/**
 * Add a fulltext search WHERE predicate to an SQL query.
 *
 * @param ETSQLQuery $sql The SQL query to add the predicate to.
 * @param string $search The search string.
 * @return void
 */
private function whereSearch(&$sql, $search)
{
	$sql->where("MATCH (content) AGAINST (:search IN BOOLEAN MODE)")
		->where("deleteMemberId IS NULL")
		->bind(":search", $search);
}


/**
 * Create a post in the specified conversation.
 *
 * This function will go through the post content and notify any members who are @mentioned.
 *
 * @param int $conversationId The ID of the conversation to create the post in.
 * @param int $memberId The ID of the author of the post.
 * @param string $content The post content.
 * @param string $title The title of the conversation (so it can be added alongside the post, for fulltext purposes.)
 * @return bool|int The new post's ID, or false if there were errors.
 */
public function create($conversationId, $memberId, $content, $title = "")
{
	// Validate the post content.
	$this->validate("content", $content, array($this, "validateContent"));

	if ($this->errorCount()) return false;

	// Prepare the post details for the query.
	$data = array(
		"conversationId" => $conversationId,
		"memberId" => $memberId,
		"time" => time(),
		"content" => $content,
		"title" => $title
	);

	$id = parent::create($data);

	// Update the member's post count.
	ET::SQL()
		->update("member")
		->set("countPosts", "countPosts + 1", false)
		->where("memberId", $memberId)
		->exec();

	// Parse the post content for @mentions, and notify any members who were mentioned.
	if (C("esoTalk.format.mentions")) {

		$names = ET::formatter()->getMentions($content);

		if (count($names)) {

			// Get the member details from the database.
			$sql = ET::SQL()
				->where("m.username IN (:names)")
				->bind(":names", $names)
				->where("m.memberId != :userId")
				->bind(":userId", $memberId);
			$members = ET::memberModel()->getWithSQL($sql);

			$data = array(
				"postId" => (int)$id,
				"title" => $title
			);

			$i = 0;
			foreach ($members as $member) {

				// Only send notifications to the first 10 members who are mentioned to prevent abuse of the system.
				if ($i++ > 10) break;

				// Check if this member is allowed to view this conversation before sending them a notification.
				$sql = ET::SQL()
					->select("conversationId")
					->from("conversation c")
					->where("conversationId", $conversationId);
				ET::conversationModel()->addAllowedPredicate($sql, $member);
				if (!$sql->exec()->numRows()) continue;

				ET::activityModel()->create("mention", $member, ET::$session->user, $data);
			}
		}

	}

	return $id;
}


/**
 * Edit a post's content.
 *
 * @param array $post The post to edit. This array's content, editMember, and editTime attributes will
 * 		be updated.
 * @param string $content The new post content.
 * @return bool true on success, false on error.
 */
public function editPost(&$post, $content)
{
	// Validate the post content.
	$this->validate("content", $content, array($this, "validateContent"));

	if ($this->errorCount()) return false;

	// Update the post.
	$time = time();
	$this->updateById($post["postId"], array(
		"content" => $content,
		"editMemberId" => ET::$session->userId,
		"editTime" => $time
	));

	$post["content"] = $content;
	$post["editMemberId"] = ET::$session->userId;
	$post["editMemberName"] = ET::$session->user["username"];
	$post["editTime"] = $time;

	return true;
}


/**
 * Mark a post as deleted. This does not actually delete the post from the database; it just sets the
 * deleteMemberId and deleteTime fields.
 *
 * @param array $post The post to mark as deleted.
 * @return bool true on success, false on error.
 */
public function deletePost(&$post)
{
	// Update the post.
	$time = time();
	$this->updateById($post["postId"], array(
		"deleteMemberId" => ET::$session->userId,
		"deleteTime" => $time
	));

	$post["deleteMemberId"] = ET::$session->userId;
	$post["deleteMemberName"] = ET::$session->user["username"];
	$post["deleteTime"] = $time;

	return true;
}


/**
 * Unmark a post as deleted.
 *
 * @param array $post The post to unmark as deleted.
 * @return bool true on success, false on error.
 */
public function restorePost(&$post)
{
	// Update the post.
	$time = time();
	$this->updateById($post["postId"], array(
		"deleteMemberId" => null,
		"deleteTime" => null
	));

	$post["deleteMemberId"] = null;
	$post["deleteMemberName"] = null;
	$post["deleteTime"] = null;

	return true;
}


/**
 * Validate a post's content.
 *
 * @param string $content The post content.
 * @return bool|string Returns an error string or false if there are no errors.
 */
public function validateContent($content)
{
	$content = trim($content);

	// Make sure it's not too long but has at least one character.
	if (strlen($content) > C("esoTalk.conversation.maxCharsPerPost")) return "postTooLong";
	if (!strlen($content)) return "emptyPost";
}

}