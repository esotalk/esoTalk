<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The upgrade model provides methods to install and upgrade esoTalk's database structure and data.
 *
 * @package esoTalk
 */
class ETUpgradeModel extends ETModel {


/**
 * Check for updates to the esoTalk software. If there's a new version, and this is the first time we've heard
 * of it, create a notifcation for the current user.
 *
 * @return void
 */
public function checkForUpdates()
{
	// Save the last update check time so we won't do it again for a while.
	ET::writeConfig(array("esoTalk.admin.lastUpdateCheckTime" => time()));

	// If the latest version is different to what it was last time we checked...
	$info = C("esoTalk.admin.lastUpdateCheckInfo", array("version" => ESOTALK_VERSION));
	if (($package = ET::checkForUpdates()) and $package["version"] != $info["version"]) {

		// Create a notification.
		ET::activityModel()->create("updateAvailable", ET::$session->userId, null, $package);

		// Write the latest checked version to the config file.
		ET::writeConfig(array("esoTalk.admin.lastUpdateCheckInfo" => $package));
	}
}


/**
 * Define esoTalk's table structure, using the database structure class to create tables or make alterations
 * to existing tables as necessary.
 *
 * @param bool $drop Whether or not to drop existing tables before recreating them.
 * @return void
 */
protected function structure($drop = false)
{
	$structure = ET::$database->structure();

	// Activity table.
	$structure
		->table("activity")
		->column("activityId", "int(11) unsigned", false)
		->column("type", "varchar(255)")
		->column("memberId", "int(11) unsigned", false)
		->column("fromMemberId", "int(11) unsigned")
		->column("data", "tinyblob")
		->column("conversationId", "int(11) unsigned")
		->column("postId", "int(11) unsigned")
		->column("time", "int(11) unsigned")
		->column("read", "tinyint(1)", 0)
		->key("activityId", "primary")
		->key("memberId")
		->key("time")
		->key("type")
		->key("conversationId")
		->key("postId")
		->key("read")
		->exec($drop);

	// Channel table.
	$structure
		->table("channel")
		->column("channelId", "int(11) unsigned", false)
		->column("title", "varchar(31)", false)
		->column("slug", "varchar(31)", false)
		->column("description", "varchar(255)")
		->column("parentId", "int(11)")
		->column("lft", "int(11)", 0)
		->column("rgt", "int(11)", 0)
		->column("depth", "int(11)", 0)
		->column("countConversations", "int(11)", 0)
		->column("countPosts", "int(11)", 0)
		->column("attributes", "mediumblob")
		->key("channelId", "primary")
		->key("slug", "unique")
		->exec($drop);

	// Channel-group table.
	$structure
		->table("channel_group")
		->column("channelId", "int(11) unsigned", false)
		->column("groupId", "int(11)", false)
		->column("view", "tinyint(1)", 0)
		->column("reply", "tinyint(1)", 0)
		->column("start", "tinyint(1)", 0)
		->column("moderate", "tinyint(1)", 0)
		->key(array("channelId", "groupId"), "primary")
		->exec($drop);

	// Conversation table.
	$structure
		->table("conversation", "MyISAM")
		->column("conversationId", "int(11) unsigned", false)
		->column("title", "varchar(100)")
		->column("channelId", "int(11) unsigned")
		->column("private", "tinyint(1)", 0)
		->column("sticky", "tinyint(1)", 0)
		->column("locked", "tinyint(1)", 0)
		->column("countPosts", "smallint(5)", 0)
		->column("startMemberId", "int(11) unsigned", false)
		->column("startTime", "int(11) unsigned", false)
		->column("lastPostMemberId", "int(11) unsigned")
		->column("lastPostTime", "int(11) unsigned")
		->column("attributes", "mediumblob")
		->key("conversationId", "primary")
		->key(array("sticky", "lastPostTime")) // for the ordering of results
		->key("lastPostTime") // also for the ordering of results, and the last post gambit
		->key("countPosts") // for the posts gambit
		->key("startTime") // for the "order by newest" gambit
		->key("locked") // for the locked gambit
		->key("private") // for the private gambit
		->key("startMemberId") // for the author gambit
		->key("channelId") // for filtering by channel
		->exec($drop);

	// Group table.
	$structure
		->table("group")
		->column("groupId", "int(11) unsigned", false)
		->column("name", "varchar(31)", "")
		->column("canSuspend", "tinyint(1)", 0)
		->column("private", "tinyint(1)", 0)
		->key("groupId", "primary")
		->exec($drop);

	// Member table.
	$structure
		->table("member", "MyISAM")
		->column("memberId", "int(11) unsigned", false)
		->column("username", "varchar(31)", "")
		->column("email", "varchar(63)", false)
		->column("account", "enum('administrator','member','suspended')", "member")
		->column("confirmedEmail", "tinyint(1)", 0)
		->column("password", "char(64)", "")
		->column("resetPassword", "char(32)")
		->column("joinTime", "int(11) unsigned", false)
		->column("lastActionTime", "int(11) unsigned")
		->column("lastActionDetail", "tinyblob")
		->column("avatarFormat", "enum('jpg','png','gif')")
		->column("preferences", "mediumblob")
		->column("countPosts", "int(11) unsigned", 0)
		->column("countConversations", "int(11) unsigned", 0)
		->key("memberId", "primary")
		->key("username", "unique")
		->key("email", "unique")
		->key("lastActionTime")
		->key("account")
		->key("countPosts")
		->key("resetPassword")
		->exec($drop);

	// Member-channel table.
	$structure
		->table("member_channel")
		->column("memberId", "int(11) unsigned", false)
		->column("channelId", "int(11) unsigned", false)
		->column("unsubscribed", "tinyint(1)", 0)
		->key(array("memberId", "channelId"), "primary")
		->exec($drop);

	// Member-conversation table.
	$structure
		->table("member_conversation")
		->column("conversationId", "int(11) unsigned", false)
		->column("type", "enum('member','group')", "member")
		->column("id", "int(11)", false)
		->column("allowed", "tinyint(1)", 0)
		->column("starred", "tinyint(1)", 0)
		->column("lastRead", "smallint(5)", 0)
		->column("draft", "text")
		->column("muted", "tinyint(1)", 0)
		->key(array("conversationId", "type", "id"), "primary")
		->key(array("type", "id"))
		->exec($drop);

	// Member-group table.
	$structure
		->table("member_group")
		->column("memberId", "int(11) unsigned", false)
		->column("groupId", "int(11) unsigned", false)
		->key(array("memberId", "groupId"), "primary")
		->exec($drop);

	// Member-user table.
	$structure
		->table("member_member")
		->column("memberId1", "int(11) unsigned", false)
		->column("memberId2", "int(11) unsigned", false)
		->key(array("memberId1", "memberId2"), "primary")
		->exec($drop);

	// Post table.
	$structure
		->table("post", "MyISAM")
		->column("postId", "int(11) unsigned", false)
		->column("conversationId", "int(11) unsigned", false)
		->column("memberId", "int(11) unsigned", false)
		->column("time", "int(11) unsigned", false)
		->column("editMemberId", "int(11) unsigned")
		->column("editTime", "int(11) unsigned")
		->column("deleteMemberId", "int(11) unsigned")
		->column("deleteTime", "int(11) unsigned")
		->column("title", "varchar(63)", false)
		->column("content", "text", false)
		->column("attributes", "mediumblob")
		->key("postId", "primary")
		->key("memberId")
		->key(array("conversationId", "time"))
		->key(array("title", "content"), "fulltext")
		->exec($drop);

	// Search table.
	$structure
		->table("search")
		->column("type", "enum('conversations')", "conversations")
		->column("ip", "int(11) unsigned", false)
		->column("time", "int(11) unsigned", false)
		->key(array("type", "ip"))
		->exec($drop);

	// Cookie table.
	$structure
		->table("cookie")
		->column("memberId", "int(11) unsigned", false)
		->column("series", "char(32)", false)
		->column("token", "char(32)", false)
		->key(array("memberId", "series"), "primary")
		->exec($drop);
}


/**
 * Perform a fresh installation of the esoTalk database. Create the table structure and insert default data.
 *
 * @param array $info An array of information gathered from the installation form.
 * @return void
 */
public function install($info)
{
	// Create the table structure.
	$this->structure(true);

	// Create the administrator member.
	$member = array(
		"username" => $info["adminUser"],
		"email" => $info["adminEmail"],
		"password" => $info["adminPass"],
		"account" => "Administrator",
		"confirmedEmail" => true
	);
	ET::memberModel()->create($member);

	// Set the session's userId and user information variables to the administrator, so that all entities
	// created below will be created by the administrator user.
	ET::$session->userId = 1;
	ET::$session->user = ET::memberModel()->getById(1);

	// Create the moderator group.
	ET::groupModel()->create(array(
		"name" => "Moderator",
		"canSuspend" => true
	));

	// Create the General Discussion channel.
	$id = ET::channelModel()->create(array(
		"title" => "General Discussion",
		"slug" => slug("General Discussion")
	));
	ET::channelModel()->setPermissions($id, array(
		GROUP_ID_GUEST => array("view" => true),
		GROUP_ID_MEMBER => array("view" => true, "reply" => true, "start" => true),
		1 => array("view" => true, "reply" => true, "start" => true, "moderate" => true)
	));

	// Create the Staff Only channel.
	$id = ET::channelModel()->create(array(
		"title" => "Staff Only",
		"slug" => slug("Staff Only")
	));
	ET::channelModel()->setPermissions($id, array(
		1 => array("view" => true, "reply" => true, "start" => true, "moderate" => true)
	));

	// Set the flood control config setting to zero so that we can create two conversations in a row.
	ET::$config["esoTalk.conversation.timeBetweenPosts"] = 0;

	// Create a welcome conversation.
	ET::conversationModel()->create(array(
		"title" => "Welcome to ".$info["forumTitle"]."!",
		"content" => "[b]Welcome to ".$info["forumTitle"]."![/b]\n\n".$info["forumTitle"]." is powered by [url=http://esotalk.org]esoTalk[/url], the simple, fast, free web-forum.\n\nFeel free to edit or delete this conversation. Otherwise, it's time to get posting!\n\nAnyway, good luck, and we hope you enjoy using esoTalk.",
		"channelId" => 1
	));

	// Create a helpful private conversation with the administrator.
	ET::conversationModel()->create(array(
		"title" => "Pssst! Want a few tips?",
		"content" => "Hey {$info["adminUser"]}, congrats on getting esoTalk installed!\n\nCool! Your forum is now good-to-go, but you might want to customize it with your own logo, design, and settings—so here's how.\n\n[h]Changing the Logo[/h]\n\n1. Go to the [url=".C("esoTalk.baseURL")."admin/settings]Forum Settings[/url] section of your administration panel.\n2. Select 'Show an image in the header' for the 'Forum header' setting.\n3. Find and select the image file you wish to use.\n4. Click 'Save Changes'. The logo will automatically be resized so it fits nicely in the header.\n\n[h]Changing the Appearance[/h]\n\n1. Go to the [url=".C("esoTalk.baseURL")."admin/appearance]Appearance[/url] section of your administration panel.\n2. Choose colors for the header, page background, or select a background image. (More skins will be available soon.)\n3. Click 'Save Changes', and your forum's appearance will be updated!\n\n[h]Managing Channels[/h]\n\n'Channels' are a way to categorize conversations in your forum. You can create as many or as few channels as you like, nest them, and give them custom permissions.\n\n1. Go to the [url=".C("esoTalk.baseURL")."admin/channels]Channels[/url] section of your administration panel.\n2. Click 'Create Channel' and fill out a title, description, and select permissions to add a new channel.\n3. Drag and drop channels to rearrange and nest them.\n\n[h]Getting Help[/h]\n\nIf you need help, come and give us a yell at the [url=http://esotalk.org/forum]esoTalk Support Forum[/url]. Don't worry—we don't bite!",
		"channelId" => 1
	), array(array("type" => "member", "id" => 1)));

	// All done!
}


/**
 * Perform an upgrade to ensure that the database is up-to-date.
 *
 * @param string $currentVersion The version we are upgrading from.
 * @return void
 */
public function upgrade($currentVersion = "")
{
	// Make sure the application's table structure is up-to-date.
	$this->structure(false);

	// Perform any custom upgrade procedures, from $currentVersion to ESOTALK_VERSION, here.
}

}