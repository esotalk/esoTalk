<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The member model provides functions for retrieving and managing member data. It also provides methods to
 * handle "last action" types.
 *
 * @package esoTalk
 */
class ETMemberModel extends ETModel {


/**
 * Reserved user names which cannot be used.
 * @var array
 */
protected static $reservedNames = array("members", "moderators", "administrators");


/**
 * An array of last action types => their callback functions.
 * @var array
 **/
protected static $lastActionTypes = array();


/**
 * Class constructor; sets up the base model functions to use the member table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("member");
}


/**
 * Create a member.
 *
 * @param array $values An array of fields and their values to insert.
 * @return bool|int The new member ID, or false if there were errors.
 */
public function create(&$values)
{
	// Validate the username, email, and password.
	$this->validate("username", $values["username"], array($this, "validateUsername"));
	$this->validate("email", $values["email"], array($this, "validateEmail"));
	$this->validate("password", $values["password"], array($this, "validatePassword"));

	// Hash the password and set the join time.
	$values["password"] = $this->hashPassword($values["password"]);
	$values["joinTime"] = time();

	// MD5 the "reset password" hash for storage (for extra safety).
	$oldHash = isset($values["resetPassword"]) ? $values["resetPassword"] : null;
	if (isset($values["resetPassword"])) $values["resetPassword"] = md5($values["resetPassword"]);

	// Set default preferences.
	if (empty($values["preferences"])) {
		$preferences = array("email.privateAdd", "email.post", "starOnReply");
		foreach ($preferences as $p) {
			$values["preferences"][$p] = C("esoTalk.preferences.".$p);
		}
	}
	$values["preferences"] = serialize($values["preferences"]);

	if ($this->errorCount()) return false;

	$memberId = parent::create($values);
	$values["memberId"] = $memberId;

	// Create "join" activity for this member.
	ET::activityModel()->create("join", $values);

	// Go through the list of channels and unsubscribe from any ones that have that attribute set.
	$channels = ET::channelModel()->getAll();
	$inserts = array();
	foreach ($channels as $channel) {
		if (!empty($channel["attributes"]["defaultUnsubscribed"]))
			$inserts[] = array($memberId, $channel["channelId"], 1);
	}
	if (count($inserts)) {
		ET::SQL()
			->insert("member_channel")
			->setMultiple(array("memberId", "channelId", "unsubscribed"), $inserts)
			->exec();
	}

	// Revert the "reset password" hash to what it was before we MD5'd it.
	$values["resetPassword"] = $oldHash;

	return $memberId;
}


/**
 * Update a member's details.
 *
 * @param array $values An array of fields to update and their values.
 * @param array $wheres An array of WHERE conditions.
 * @return bool|ETSQLResult
 */
public function update($values, $wheres = array())
{
	if (isset($values["username"]))
		$this->validate("username", $values["username"], array($this, "validateUsername"));

	if (isset($values["email"]))
		$this->validate("email", $values["email"], array($this, "validateEmail"));

	if (isset($values["password"])) {
		$this->validate("password", $values["password"], array($this, "validatePassword"));
		$values["password"] = $this->hashPassword($values["password"]);
	}

	// Serialize preferences.
	if (isset($values["preferences"])) $values["preferences"] = serialize($values["preferences"]);

	// MD5 the "reset password" hash for storage (for extra safety).
	if (isset($values["resetPassword"])) $values["resetPassword"] = md5($values["resetPassword"]);

	if ($this->errorCount()) return false;

	return parent::update($values, $wheres);
}


/**
 * Get standardized member data given an SQL query (which can specify WHERE conditions, for example.)
 *
 * @param ETSQLQuery $sql The SQL query to use as a basis.
 * @return array An array of members and their details.
 */
public function getWithSQL($sql)
{
	$sql->select("m.*")
		->select("GROUP_CONCAT(g.groupId) AS groups")
		->select("GROUP_CONCAT(g.name) AS groupNames")
		->select("BIT_OR(g.canSuspend) AS canSuspend")
		->from("member m")
		->from("member_group mg", "mg.memberId=m.memberId", "left")
		->from("group g", "g.groupId=mg.groupId", "left")
		->groupBy("m.memberId");

	if (ET::$session and ET::$session->user) {
		$sql->select("mm.*")
			->from("member_member mm", "mm.memberId2=m.memberId AND mm.memberId1=:userId", "left")
			->bind(":userId", ET::$session->userId);
	}

	$members = $sql->exec()->allRows();

	// Expand the member data.
	foreach ($members as &$member) $this->expand($member);

	return $members;
}


/**
 * Get member data for the specified post ID.
 *
 * @param int $memberId The ID of the member.
 * @return array An array of the member's details.
 */
public function getById($memberId)
{
	return reset($this->get(array("m.memberId" => $memberId)));
}


/**
 * Get member data for the specified post IDs, in the same order.
 *
 * @param array $ids The IDs of the members to fetch.
 * @return array An array of member details, ordered by the order of the IDs.
 */
public function getByIds($ids)
{
	$sql = ET::SQL()
		->where("m.memberId IN (:memberIds)")
		->orderBy("FIELD(m.memberId,:memberIdsOrder)")
		->bind(":memberIds", $ids, PDO::PARAM_INT)
		->bind(":memberIdsOrder", $ids, PDO::PARAM_INT);

	return $this->getWithSQL($sql);
}


/**
 * Expand raw member data into more readable values.
 *
 * @param array $member The member to expand data for.
 * @return void
 */
public function expand(&$member)
{
	// Make the groups into an array of groupId => names. (Possibly consider using ETGroupModel::getAll()
	// instead of featching the groupNames in getWithSQL()?)
	if (array_key_exists("groups", $member) and array_key_exists("groupNames", $member))
		$member["groups"] = array_combine(explode(",", $member["groups"]), explode(",", $member["groupNames"]));

	// Unserialize the member's preferences.
	if (isset($member["preferences"]))
		$member["preferences"] = unserialize($member["preferences"]);
}


/**
 * Generate a password hash using phpass.
 *
 * @param string $password The plain-text password.
 * @return string The hashed password.
 */
public function hashPassword($password)
{
	require_once PATH_LIBRARY."/vendor/phpass/PasswordHash.php";
	$hasher = new PasswordHash(8, FALSE);
	return $hasher->HashPassword($password);
}


/**
 * Check if a plain-text password matches an encrypted password.
 *
 * @param string $password The plain-text password to check.
 * @param string $hash The password hash to check against.
 * @return bool Whether or not the password is correct.
 */
public function checkPassword($password, $hash)
{
	require_once PATH_LIBRARY."/vendor/phpass/PasswordHash.php";
	$hasher = new PasswordHash(8, FALSE);
	return $hasher->CheckPassword($password, $hash);
}


/**
 * Validate a username.
 *
 * @param string $username The username to validate.
 * @param bool $checkForDuplicate Whether or not to check if a member with this username already exists.
 * @return null|string An error code, or null if there were no errors.
 */
public function validateUsername($username, $checkForDuplicate = true)
{
	// Make sure the name isn't a reserved word.
	if (in_array(strtolower($username), self::$reservedNames)) return "nameTaken";

	// Make sure the username is not too small or large.
	if (strlen($username) < 3 or strlen($username) > 20) return "invalidUsername";

	// Make sure there's no other member with the same username.
	if ($checkForDuplicate and ET::SQL()->select("1")->from("member")->where("username=:username")->bind(":username", $username)->exec()->numRows())
		return "nameTaken";
}


/**
 * Validate an email.
 *
 * @param string $email The email to validate.
 * @param bool $checkForDuplicate Whether or not to check if a member with this email already exists.
 * @return null|string An error code, or null if there were no errors.
 */
public function validateEmail($email, $checkForDuplicate = true)
{
	// Check it against a regular expression to make sure it's a valid email address.
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "invalidEmail";

	// Make sure there's no other member with the same email.
	if ($checkForDuplicate and ET::SQL()->select("1")->from("member")->where("email=:email")->bind(":email", $email)->exec()->numRows())
		return "emailTaken";
}


/**
 * Validate a password.
 *
 * @param string $password The password to validate.
 * @return null|string An error code, or null if there were no errors.
 */
public function validatePassword($password)
{
	// Make sure the password isn't too short.
	if (strlen($password) < C("esoTalk.minPasswordLength")) return "passwordTooShort";
}


/**
 * Returns whether or not the current user can rename a member.
 *
 * @return bool
 */
public function canRename($member)
{
	// The user must be an administrator.
	return ET::$session->isAdmin();
}


/**
 * Returns whether or not the current user can delete a member.
 *
 * @return bool
 */
public function canDelete($member)
{
	return $this->canChangePermissions($member);
}


/**
 * Returns whether or not the current user can change a member's permissions.
 *
 * @return bool
 */
public function canChangePermissions($member)
{
	// The user must be an administrator, and the root admin's permissions can't be changed. A user also
	// cannot change their own permissions.
	return ET::$session->isAdmin() and $member["memberId"] != C("esoTalk.rootAdmin") and $member["memberId"] != ET::$session->userId;
}


/**
 * Returns whether or not the current user can suspend/unsuspend a member.
 *
 * @return bool
 */
public function canSuspend($member)
{
	// The user must be an administrator, or they must have the "canSuspend" permission and the member's
	// account be either "member" or "suspended". A user cannot suspend or unsuspend themselves, and the root
	// admin cannot be suspended.
	return
	(
		ET::$session->isAdmin()
		or (ET::$session->user["canSuspend"] and ($member["account"] == ACCOUNT_MEMBER or $member["account"] == ACCOUNT_SUSPENDED))
	)
	and $member["memberId"] != C("esoTalk.rootAdmin") and $member["memberId"] != ET::$session->userId;
}


/**
 * Set a member's account and groups.
 *
 * @param array $member The details of the member to set the account/groups for.
 * @param string $account The new account.
 * @param array $groups The new group IDs.
 * @return bool true on success, false on error.
 */
public function setGroups($member, $account, $groups = array())
{
	// Make sure the account is valid.
	if (!in_array($account, array(ACCOUNT_MEMBER, ACCOUNT_ADMINISTRATOR, ACCOUNT_SUSPENDED, ACCOUNT_PENDING)))
		$this->error("account", "invalidAccount");

	if ($this->errorCount()) return false;

	// Set the member's new account.
	$this->updateById($member["memberId"], array("account" => $account));

	// Delete all of the member's existing group associations.
	ET::SQL()
		->delete()
		->from("member_group")
		->where("memberId", $member["memberId"])
		->exec();

	// Insert new member-group associations.
	$inserts = array();
	foreach ($groups as $id) $inserts[] = array($member["memberId"], $id);
	if (count($inserts))
		ET::SQL()
			->insert("member_group")
			->setMultiple(array("memberId", "groupId"), $inserts)
			->exec();

	// Now we need to create a new activity item, and to do that we need the names of the member's groups.
	$groupData = ET::groupModel()->getAll();
	$groupNames = array();
	foreach ($groups as $id) $groupNames[$id] = $groupData[$id]["name"];

	ET::activityModel()->create("groupChange", $member, ET::$session->user, array("account" => $account, "groups" => $groupNames));

	return true;
}


/**
 * Set a member's preferences.
 *
 * @param array $member An array of the member's details.
 * @param array $preferences A key => value array of preferences to set.
 * @return array The member's new preferences array.
 */
public function setPreferences($member, $preferences)
{
	// Merge the member's old preferences with the new ones, giving preference to the new ones. Geddit?!
	$preferences = array_merge((array)$member["preferences"], $preferences);

	$this->updateById($member["memberId"], array(
		"preferences" => $preferences
	));

	return $preferences;
}


/**
 * Set a member's status entry for another member (their record in the member_member table.)
 *
 * @param int $memberId1 The ID of the primary member (usually the currently-logged-in user).
 * @param int $memberId2 The ID of the other member to set the status about.
 * @param array $data An array of key => value data to save to the database.
 * @return void
 */
public function setStatus($memberId1, $memberId2, $data)
{
	$keys = array(
		"memberId1" => $memberId1,
		"memberId2" => $memberId2
	);
	ET::SQL()->insert("member_member")->set($keys + $data)->setOnDuplicateKey($data)->exec();
}


/**
 * Delete a member with the specified ID, along with all of their associated records.
 *
 * @param int $memberId The ID of the member to delete.
 * @param bool $deletePosts Whether or not to mark the member's posts as deleted.
 * @return void
 */
public function deleteById($memberId, $deletePosts = false)
{
	// Delete the member's posts if necessary.
	if ($deletePosts) {
		ET::SQL()
			->update("post")
			->set("deleteMemberId", ET::$session->userId)
			->set("deleteTime", time())
			->where("memberId", $memberId)
			->exec();
	}

	// Delete member and other records associated with the member.
	ET::SQL()
		->delete()
		->from("member")
		->where("memberId", $memberId)
		->exec();

	ET::SQL()
		->delete()
		->from("member_channel")
		->where("memberId", $memberId)
		->exec();

	ET::SQL()
		->delete()
		->from("member_conversation")
		->where("id", $memberId)
		->where("type", "member")
		->exec();

	ET::SQL()
		->delete()
		->from("member_group")
		->where("memberId", $memberId)
		->exec();
}


/**
 * Update the user's last action.
 *
 * @todo Probably move the serialize part into update().
 * @param string $type The type of last action.
 * @param array $data An array of custom data that can be used by the last action type callback function.
 * @return bool|ETSQLResult
 */
public function updateLastAction($type, $data = array())
{
	if (!ET::$session->user) return false;

	$data["type"] = $type;
	ET::$session->updateUser("lastActionTime", time());
	ET::$session->updateUser("lastActionDetail", $data);

	return $this->updateById(ET::$session->userId, array(
		"lastActionTime" => time(),
		"lastActionDetail" => serialize($data)
	));
}


/**
 * Register a type of "last action".
 *
 * @param string $type The name of the last action type.
 * @param mixed The callback function that will be called to format the last action for display. The function
 * 		should return an array:
 * 			0 => the last action description (eg. Viewing [title])
 * 			1 => an optional associated URL (eg. a conversation URL)
 * @return void
 */
public static function addLastActionType($type, $callback)
{
	self::$lastActionTypes[$type] = $callback;
}


/**
 * Get formatted last action info for a member, given their lastActionTime and lastActionDetail fields.
 *
 * @todo Probably move the serialize part into expand().
 * @param int $time The member's lastActionTime field.
 * @param string $action The member's lastActionDetail field.
 */
public static function getLastActionInfo($time, $action)
{
	// If there is no action, or the time passed since the user was last seen is too great, then return no info.
	if (!$action or $time < time() - C("esoTalk.userOnlineExpire"))
		return false;

	$data = unserialize($action);
	if (!isset($data["type"])) return false;

	// If there's a callback for this last action type, return its output.
	if (isset(self::$lastActionTypes[$data["type"]]))
		return call_user_func(self::$lastActionTypes[$data["type"]], $data) + array(null, null);

	// Otherwise, return an empty array.
	else return array(null, null);
}


/**
 * Return a formatted last action array for the "viewingConversation" type.
 *
 * @param array $data An array of data associated with the last action.
 * @return array 0 => last action description, 1 => URL
 */
public static function lastActionViewingConversation($data)
{
	if (empty($data["conversationId"])) return array(sprintf(T("Viewing %s"), T("a private conversation")));
	return array(
		sprintf(T("Viewing: %s"), $data["title"]),
		URL(conversationURL($data["conversationId"], $data["title"]))
	);
}


/**
 * Return a formatted last action array for the "startingConversation" type.
 *
 * @param array $data An array of data associated with the last action.
 * @return array
 */
public static function lastActionStartingConversation($action)
{
	return array(T("Starting a conversation"));
}

}

// Add default last action types.
ETMemberModel::addLastActionType("viewingConversation", array("ETMemberModel", "lastActionViewingConversation"));
ETMemberModel::addLastActionType("startingConversation", array("ETMemberModel", "lastActionStartingConversation"));
