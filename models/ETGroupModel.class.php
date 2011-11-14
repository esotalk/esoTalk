<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The group model provides functions for retrieving and managing member group data, and also provides some
 * utility functions regarding groups and permissions.
 *
 * @package esoTalk
 */
class ETGroupModel extends ETModel {

const CACHE_KEY = "groups";


/**
 * A local cache of all groups and their details.
 * @var array
 */
protected $groups;


/**
 * Class constructor; sets up the base model functions to use the group table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("group");
}


// TODO: OVERRIDE ALL PARENT METHODS TO ADD PERMISSION CHECK FOR ADMIN


/**
 * Get a full list of member groups and cache it.
 *
 * @return array An array of group information indexed by the group IDs.
 */
public function getAll()
{
	if (!$this->groups) {

		// If we don't have a local cache of groups, attempt to retrieve the data from the global cache.
		$groups = ET::$cache->get(self::CACHE_KEY);

		if (!$groups) {

			// Still no luck? Let's get all of the groups and their details from the db.
			$sql = ET::SQL()
				->select("g.*")
				->from("group g");

			$groups = $sql->exec()->allRows("groupId");

			// Store the result in the global cache.
			ET::$cache->store(self::CACHE_KEY, $groups);
		}

		// Store the result in the local cache.
		$this->groups = $groups;
	}

	return $this->groups;
}


/**
 * Delete a group, and all its associations.
 *
 * @param array $wheres An array of WHERE predicates.
 * @return bool true on success, false on error.
 */
public function delete($wheres = array())
{
	ET::SQL()
		->delete("g")
		->delete("m")
		->delete("c")
		->from("group g")
		->from("member_group m", "m.groupId=g.groupId", "left")
		->from("channel_group c", "c.groupId=g.groupId", "left")
		->where($wheres)
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
	return $this->delete(array("g.groupId" => $id));
}


/**
 * Check if any of a collection of group IDs are allowed in a collection of group IDs which define a
 * certain permission.
 *
 * For example, if a member has group IDs $groupIds, and a conversation is viewable by
 * $allowedGroupIds, this function can be used to check if the member is allowed to view the conversation.
 *
 * @param array $groupIds The group IDs to check.
 * @param array $allowedGroupIds The group IDs that are "allowed".
 * @param bool $adminAlwaysAllowed Whether or not administrators are always "allowed", regardless of if the
 * 		administrator group is in $allowedGroupIds.
 * @return bool Whether or not the group IDs are "allowed".
 */
public function groupIdsAllowedInGroupIds($groupIds, $allowedGroupIds, $adminAlwaysAllowed = false)
{
	// If the group IDs contains the administrator group, then we may not need to go any further.
	if (in_array(GROUP_ID_ADMINISTRATOR, (array)$groupIds) and $adminAlwaysAllowed) return true;

	// If guests are allowed, then everyone is allowed!
	if (in_array(GROUP_ID_GUEST, $allowedGroupIds)) return true;

	// Return whether or not any of the group IDs in each array match.
	return (bool)count(array_intersect($groupIds, $allowedGroupIds));
}


/**
 * Get an applied list of group IDs which apply to a member, given the member's account and the IDs of groups
 * they are in.
 *
 * @param string $account The member's account type.
 * @param array $groupIds An array of groups that the member is in.
 * @return array An array of applied group IDs for use with permission checking.
 */
public function getGroupIds($account, $groupIds)
{
	// If the user is a guest, or is suspended, they're just in the guest group.
	if (!$account or $account == ACCOUNT_SUSPENDED) return array(GROUP_ID_GUEST);

	$groupIds = array_filter($groupIds);

	// If the user is an admin, add that group to their groups.
	if ($account == ACCOUNT_ADMINISTRATOR) $groupIds[] = GROUP_ID_ADMINISTRATOR;

	// Add the member and guest groups, and return all of them.
	$groupIds[] = GROUP_ID_MEMBER;
	$groupIds[] = GROUP_ID_GUEST;

	return $groupIds;
}

}