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
		->column("activityId", "integer", false)
		->column("type", "varchar(255)")
		->column("memberId", "integer", false)
		->column("fromMemberId", "integer")
		->column("data", "tinyblob")
		->column("conversationId", "integer")
		->column("postId", "integer")
		->column("time", "integer")
		->column("read", "tinyint(1)", 0)
		->key("activityId", "primary")
		->exec($drop);

	// Channel table.
	$structure
		->table("channel")
		->column("channelId", "integer", false)
		->column("title", "varchar(31)", false)
		->column("slug", "varchar(31)", false)
		->column("description", "varchar(255)")
		->column("parentId", "integer")
		->column("lft", "integer", 0)
		->column("rgt", "integer", 0)
		->column("depth", "integer", 0)
		->column("countConversations", "integer", 0)
		->column("countPosts", "integer", 0)
		->column("attributes", "mediumblob")
		->key("channelId", "primary")
		->key("slug", "unique")
		->exec($drop);

	// Channel-group table.
	$structure
		->table("channel_group")
		->column("channelId", "integer", false)
		->column("groupId", "integer", false)
		->column("view", "tinyint(1)", 0)
		->column("reply", "tinyint(1)", 0)
		->column("start", "tinyint(1)", 0)
		->column("moderate", "tinyint(1)", 0)
		->key(array("channelId", "groupId"), "primary")
		->exec($drop);

	// Conversation table.
	$structure
		->table("conversation")
		->column("conversationId", "integer", false)
		->column("title", "varchar(100)")
		->column("channelId", "integer")
		->column("private", "tinyint(1)", 0)
		->column("sticky", "tinyint(1)", 0)
		->column("locked", "tinyint(1)", 0)
		->column("countPosts", "smallint(5)", 0)
		->column("startMemberId", "integer", false)
		->column("startTime", "integer", false)
		->column("lastPostMemberId", "integer")
		->column("lastPostTime", "integer")
		->column("attributes", "mediumblob")
		->key("conversationId", "primary")
		->exec($drop);

	// Group table.
	$structure
		->table("group")
		->column("groupId", "integer", false)
		->column("name", "varchar(31)", "")
		->column("canSuspend", "tinyint(1)", 0)
		->column("private", "tinyint(1)", 0)
		->key("groupId", "primary")
		->exec($drop);

	// Member table.
	$structure
		->table("member")
		->column("memberId", "integer", false)
		->column("username", "varchar(31)", "")
		->column("email", "varchar(63)", false)
		->column("account", "text", "member")
		->column("confirmed", "tinyint(1)", 0)
		->column("password", "char(64)", "")
		->column("resetPassword", "char(32)")
		->column("joinTime", "integer", false)
		->column("lastActionTime", "integer")
		->column("lastActionDetail", "tinyblob")
		->column("avatarFormat", "text")
		->column("preferences", "mediumblob")
		->column("countPosts", "integer", 0)
		->column("countConversations", "integer", 0)
		->key("memberId", "primary")
		->key("username", "unique")
		->key("email", "unique")
		->exec($drop);

	// Member-channel table.
	$structure
		->table("member_channel")
		->column("memberId", "integer", false)
		->column("channelId", "integer", false)
		->column("unsubscribed", "tinyint(1)", 0)
		->key(array("memberId", "channelId"), "primary")
		->exec($drop);

	// Member-conversation table.
	$structure
		->table("member_conversation")
		->column("conversationId", "integer", false)
		->column("type", "text", "member")
		->column("id", "integer", false)
		->column("allowed", "tinyint(1)", 0)
		->column("starred", "tinyint(1)", 0)
		->column("lastRead", "smallint(5)", 0)
		->column("draft", "text")
		->column("ignored", "tinyint(1)", 0)
		->key(array("conversationId", "type", "id"), "primary")
		->exec($drop);

	// Member-group table.
	$structure
		->table("member_group")
		->column("memberId", "integer", false)
		->column("groupId", "integer", false)
		->key(array("memberId", "groupId"), "primary")
		->exec($drop);

	// Member-user table.
	$structure
		->table("member_member")
		->column("memberId1", "integer", false)
		->column("memberId2", "integer", false)
		->key(array("memberId1", "memberId2"), "primary")
		->exec($drop);

	// Post table.
	$structure
		->table("post", "MyISAM")
		->column("postId", "integer", false)
		->column("conversationId", "integer", false)
		->column("memberId", "integer", false)
		->column("time", "integer", false)
		->column("editMemberId", "integer")
		->column("editTime", "integer")
		->column("deleteMemberId", "integer")
		->column("deleteTime", "integer")
		->column("title", "varchar(100)", false)
		->column("content", "text", false)
		->column("attributes", "mediumblob")
		->key("postId", "primary")
		->exec($drop);

	// Search table.
	$structure
		->table("search")
		->column("type", "text", "conversations")
		->column("ip", "integer", false)
		->column("time", "integer", false)
		->exec($drop);

	// Cookie table.
	$structure
		->table("cookie")
		->column("memberId", "integer", false)
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
		"confirmed" => true
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
	// 1.0.0g4:
	// - Rename the 'confirmedEmail' column on the members table to 'confirmed'
	// - Rename the 'muted' column on the member_conversation table to 'ignored'
	if (version_compare($currentVersion, "1.0.0g4", "<")) {
		ET::$database->structure()->table("member")->renameColumn("confirmedEmail", "confirmed");
		ET::$database->structure()->table("member_conversation")->renameColumn("muted", "ignored");
	}

	// Make sure the application's table structure is up-to-date.
	$this->structure(false);

	// Perform any custom upgrade procedures, from $currentVersion to ESOTALK_VERSION, here.

	// 1.0.0g3:
	/// - Re-calculate all conversation post counts due to a bug which could get them un-synced
	if (version_compare($currentVersion, "1.0.0g3", "<")) {
		ET::SQL()
			->update("conversation c")
			->set("countPosts", "(".ET::SQL()->select("COUNT(*)")->from("post p")->where("p.conversationId=c.conversationId").")", false)
			->exec();
	}
}

}
