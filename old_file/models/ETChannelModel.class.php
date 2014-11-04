<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The channel model provides functions for retrieving and managing channel data.
 *
 * @package esoTalk
 */
class ETChannelModel extends ETModel {

const CACHE_KEY = "channels";


/**
 * A local cache of all channels and their details.
 * @var array
 */
protected $channels;


/**
 * Class constructor; sets up the base model functions to use the channel table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("channel");
}


/**
 * Get a full list of channels with all permission details and cache it.
 *
 * @return array An array of channel information indexed by the channel IDs.
 */
public function getAll()
{
	if (!$this->channels) {

		// If we don't have a local cache of channels, attempt to retrieve the data from the global cache.
		$channels = ET::$cache->get(self::CACHE_KEY);

		if (!$channels) {

			// Still no luck? Let's get all of the channels and their details + permissions from the db.
			// We'll construct the query with a comma-separated column of group IDs, and comma-separated
			// columns for the corresponding permissions.
			$sql = ET::SQL()
				->select("c.*")
				->select("GROUP_CONCAT(g.groupId)", "groupId")
				->from("channel c")
				->from("channel_group g", "c.channelId=g.channelId", "left")
				->groupBy("c.channelId")
				->orderBy("c.lft ASC");

			// Define the permission columns that we need to get.
			$permissionColumns = array("view", "reply", "start", "moderate");
			foreach ($permissionColumns as $column)
				$sql->select("GROUP_CONCAT(g.$column)", $column);

			// Get the channels, indexed by channel ID.
			$channels = $sql->exec()->allRows("channelId");

			// Loop through the channels and expand that comma-separated permission columns into nice arrays
			// of groups that do have the specified permission. eg. "view" => array(1, 2, 3)
			foreach ($channels as &$channel) {

				// Expand the channel's attributes.
				$channel["attributes"] = unserialize($channel["attributes"]);

				// Expand the group IDs.
				$groupIds = explode(",", $channel["groupId"]);
				unset($channel["groupId"]);

				// For each permission type, expand the comma-separated bool values.
				$permissions = array();
				$channel["permissions"] = array();
				foreach ($permissionColumns as $column) {
					$channel["permissions"][$column] = array();
					$permissions[$column] = explode(",", $channel[$column]);
					unset($channel[$column]);
				}

				// Now, for each group ID, and then for each permission type, add the group ID to that
				// permission type's array if it does have permission.
				foreach ($groupIds as $i => $id) {
					foreach ($permissionColumns as $column) {
						if ($permissions[$column][$i]) $channel["permissions"][$column][] = $id;
					}
				}

			}

			// Store the result in the global cache.
			ET::$cache->store(self::CACHE_KEY, $channels);
		}

		// Store the result in the local cache.
		$this->channels = $channels;
	}

	return $this->channels;
}


/**
 * Get a list of channels which the user has the specified permission for.
 *
 * @param string $permission The name of the permission to filter channels by.
 * @return array An array of channel information indexed by the channel IDs.
 */
public function get($permission = "view")
{
	$channels = $this->getAll();

	// Go through each of the channels and remove ones that the user doesn't have this permission for.
	$groupModel = ET::groupModel();
	$groupIds = ET::$session->getGroupIds();
	foreach ($channels as $k => $channel) {
		if (!$groupModel->groupIdsAllowedInGroupIds($groupIds, $channel["permissions"][$permission], true))
			unset($channels[$k]);
	}

	// Add user data (eg. unsubscribed) into the channel array.
	$this->joinUserData($channels);

	return $channels;
}


/**
 * Add user-channel-specific data (from the member_channel table) into an array of channel data.
 *
 * @param array $channels The array of channels to add the user data onto.
 * @return void
 */
public function joinUserData(&$channels)
{
	// If there's no user logged in, we don't need to add anything.
	if (!ET::$session->userId) {
		foreach ($channels as &$channel) {
			if ($channel["attributes"]["defaultUnsubscribed"])
				$channel["unsubscribed"] = true;
		}
		return;
	}

	// Get the user data from the database for all channel IDs in the array.
	$result = ET::SQL()
		->select("*")
		->from("member_channel")
		->where("memberId=:memberId")
		->where("channelId IN (:channelIds)")
		->bind(":memberId", ET::$session->userId)
		->bind(":channelIds", array_keys($channels))
		->exec();

	// For each row, merge the row into the respective row in the channels array.
	foreach ($result->allRows() as $row) {
		unset($row["memberId"]);
		$channels[$row["channelId"]] = array_merge($channels[$row["channelId"]], $row);
	}
}


/**
 * Returns whether or not the current user has the specified permission for $channelId.
 *
 * @param int $channelId The channel ID.
 * @param string $permission The name of the permission to check.
 * @return bool
 */
public function hasPermission($channelId, $permission = "view")
{
	$sql = ET::SQL()
		->select("COUNT(1)")
		->from("channel c")
		->where("channelId=:channelId")
		->bind(":channelId", (int)$channelId);

	$this->addPermissionPredicate($sql, $permission);

	return (bool)$sql->exec()->result();
}


/**
 * Add a WHERE predicate to an SQL query which makes sure only rows for which the user has the specified
 * permission are returned.
 *
 * @param ETSQLQuery $sql The SQL query to add the predicate to.
 * @param string $field The name of the permission to check for.
 * @param array $member The member to filter out channels for. If not specified, the currently
 * 		logged-in user will be used.
 * @param string $table The channel table alias used in the SQL query.
 * @return void
 */
public function addPermissionPredicate(&$sql, $field = "view", $member = false, $table = "c")
{
	// If no member was specified, use the current user.
	if (!$member) $member = ET::$session->user;

	// Get an array of group IDs for this member.
	$groups = ET::groupModel()->getGroupIds($member["account"], array_keys((array)$member["groups"]));

	// If the user is an administrator, don't add any SQL, as admins can do anything!
	if (in_array(GROUP_ID_ADMINISTRATOR, $groups)) return;

	// Construct a query that will fetch all channelIds for which this member has the specified permission.
	$query = ET::SQL()
		->select("channelId")
		->from("channel_group")
		->where("groupId IN (:groups)")
		->where("$field=1")
		->get();

	// Add this as a where clause to the SQL query.
	$sql->where("$table.channelId IN ($query)")
		->bind(":groups", $groups, PDO::PARAM_INT);
}


/**
 * Generates a unqique slug for a channel, given the title of the channel.
 *
 * @param string $title The title of the channel.
 * @return string The suggested slug.
 */
public function generateSlug($title)
{
	$channels = $this->getAll();

	// Keep increasing a number on the end of the slug until we find one that isn't taken.
	$i = 0;
	while (true) {
		$slug = slug($title.($i ? " $i" : ""));
		$i++;

		// Loop through all the channels. If we find a channel with this slug, continue to the next iteration
		// of the 'while' loop.
		foreach ($channels as $channel) {
			if ($channel["slug"] == $slug) continue 2;
		}
		break;
	}

	return $slug;
}


/**
 * Create a channel.
 *
 * @param array $values An array of fields and their values to insert.
 * @return bool|int The new channel ID, or false if there are errors.
 */
public function create($values)
{
	// Check that a channel title has been entered.
	if (!isset($values["title"])) $values["title"] = "";
	$this->validate("title", $values["title"], array($this, "validateTitle"));

	// Check that a channel slug has been entered and isn't already in use.
	if (!isset($values["slug"])) $values["slug"] = "";
	$this->validate("slug", $values["slug"], array($this, "validateSlug"));

	// Add the channel at the end at the root level.
	$right = ET::SQL()->select("MAX(rgt)")->from("channel")->exec()->result();
	$values["lft"] = ++$right;
	$values["rgt"] = ++$right;

	// Collapse the attributes.
	if (isset($values["attributes"])) $values["attributes"] = serialize($value["attributes"]);

	if ($this->errorCount()) return false;

	$channelId = parent::create($values);

	// Reset channels in the global cache.
	ET::$cache->remove(self::CACHE_KEY);

	return $channelId;
}


/**
 * Update a channel's details.
 *
 * @param array $values An array of fields to update and their values.
 * @param array $wheres An array of WHERE conditions.
 * @return bool|ETSQLResult
 */
public function update($values, $wheres = array())
{
	if (isset($values["title"]))
		$this->validate("title", $values["title"], array($this, "validateTitle"));

	if (isset($values["slug"]))
		$this->validate("slug", $values["slug"], array($this, "validateSlug"));

	// Collapse the attributes.
	if (isset($values["attributes"])) $values["attributes"] = serialize($values["attributes"]);

	if ($this->errorCount()) return false;

	// Reset channels in the global cache.
	ET::$cache->remove(self::CACHE_KEY);

	return parent::update($values, $wheres);
}


/**
 * Set permissions for a channel.
 *
 * @param int $channelId The ID of the channel to set permissions for.
 * @param array $permissions An array of permissions to set.
 */
public function setPermissions($channelId, $permissions)
{
	// Delete already-existing permissions for this channel.
	ET::SQL()
		->delete()
		->from("channel_group")
		->where("channelId=:channelId")
		->bind(":channelId", $channelId, PDO::PARAM_INT)
		->exec();

	// Go through each group ID and set its permission types.
	foreach ($permissions as $groupId => $types) {
		$set = array();
		foreach ($types as $type => $v) {
			if ($v) $set[$type] = 1;
		}
		ET::SQL()
			->insert("channel_group")
			->set("channelId", $channelId)
			->set("groupId", $groupId)
			->set($set)
			->exec();
	}

	// Reset channels in the global cache.
	ET::$cache->remove(self::CACHE_KEY);
}


/**
 * Set a member's status entry for a channel (their record in the member_channel table.)
 *
 * @param int $channelId The ID of the channel to set the member's status for.
 * @param int $memberId The ID of the member to set the status for.
 * @param array $data An array of key => value data to save to the database.
 * @return void
 */
public function setStatus($channelId, $memberId, $data)
{
	$keys = array(
		"memberId" => $memberId,
		"channelId" => $channelId
	);
	ET::SQL()->insert("member_channel")->set($keys + $data)->setOnDuplicateKey($data)->exec();
}


/**
 * Delete a channel and its conversations (or optionally move its conversations to another channel.)
 *
 * @param int $channelId The ID of the channel to delete.
 * @param bool|int $moveToChannelId The ID of the channel to move conversations to, or false to delete them.
 * @return bool true on success, false on error.
 */
public function deleteById($channelId, $moveToChannelId = false)
{
	$channelId = (int)$channelId;

	// Do we want to move the conversations to another channel?
	if ($moveToChannelId !== false) {

		// If the channel does exist, move all the conversation over to it.
		if (array_key_exists((int)$moveToChannelId, $this->getAll())) {
			ET::SQL()
				->update("conversation")
				->set("channelId", (int)$moveToChannelId)
				->where("channelId=:channelId")
				->bind(":channelId", $channelId)
				->exec();
		}

		// But if it doesn't, set an error.
		else $this->error("moveToChannelId", "invalidChannel");

	}

	// Or do we want to simply delete the conversations?
	else ET::conversationModel()->delete(array("channelId" => $channelId));

	if ($this->errorCount()) return false;

	$result = parent::deleteById($channelId);

	// Reset channels in the global cache.
	ET::$cache->remove(self::CACHE_KEY);

	return $result;
}


/**
 * Validate a channel title.
 *
 * @param string $title The channel title.
 * @return string|null An error code, or null if there were no errors.
 */
public function validateTitle($title)
{
	if (!strlen($title)) return "empty";
}


/**
 * Validate a channel slug.
 *
 * @param string $slug The channel slug.
 * @return string|null An error code, or null if there were no errors.
 */
public function validateSlug($slug)
{
	if (!strlen($slug)) return "empty";
	if (ET::SQL()
		->select("COUNT(channelId)")
		->from("channel")
		->where("slug=:slug")
		->bind(":slug", $slug)
		->exec()
		->result() > 0)
		return "channelSlugTaken";
}

}