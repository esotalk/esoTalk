<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The activity model provides functions for retrieving and managing activity/notification data. It also
 * provides methods to define new types of activity/notifications, add new activity/notification entries,
 * and send email notifications.
 *
 * In more detail: each piece of activity or notification is of a certain "type" - for example, "mention" for
 * @mentions or "groupChange" for when a user's group is changed. Each activity type can have one or more
 * "projections", or places where it will be projected to. There are three types of projections: "activity"
 * (to be shown on the member's profile), "notification" (to be shown in the member's notifications), and
 * "email" (to be sent out as an email to the member.)
 *
 * For each type, one or more projections can be set to a callback function, which should return an array of
 * information to display (more on that later.)
 *
 * @package esoTalk
 */
class ETActivityModel extends ETModel {


const PROJECTION_ACTIVITY = "activity";
const PROJECTION_NOTIFICATION = "notification";
const PROJECTION_EMAIL = "email";


/**
 * An array of activity "types" and their projections, as described above.
 * @var array
 */
protected static $types = array();


/**
 * An array of member IDs which have been sent email notifications. This is used to prevent multiple emails
 * being sent regarding the same subject. It can be cleared using start/endNotificationGroup().
 * @var array
 */
protected $membersUsed = array();


/**
 * Class constructor; sets up the base model functions to use the activity table.
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("activity");
}


/**
 * Returns an array of activity "types" which have a handler for a certain projection type.
 *
 * @param string $projection One of the PROJECTION_* constants.
 * @return array An array containing a list of activity "types".
 */
public static function getTypesWithProjection($projection)
{
	$types = array();
	foreach (self::$types as $k => $v) {
		if (!empty($v[$projection])) $types[] = $k;
	}
	return $types;
}


/**
 * Add a new activity type to the collection.
 *
 * @param string $type The name of the type.
 * @param array $projections An array of projections that this activity type should handle, and their
 * 		callback functions. The keys should be PROJECTION_* constants.
 * @return void
 */
public static function addType($type, $projections)
{
	self::$types[$type] = $projections;
}


/**
 * Create a new activity entry in the database. If the specified activity type has an email projection
 * handler, this method will also send an email notification where appropriate.
 *
 * @todo Add an unsubscribe link to notification email footers.
 *
 * @param string $type The name of the type of activity to create.
 * @param array $member An array of details for the member to create the activity for.
 * @param array $fromMember An array of details for the member that the activity is from.
 * @param array $data An array of custom data that can be used by the type/projection callback functions.
 * @param array $emailData An array of custom data that can be used only by the EMAIL projection callback function.
 *		(i.e. it is not stored in the database.)
 * @return bool|int The activity ID, or false if there were errors.
 */
public function create($type, $member, $fromMember = null, $data = null, $emailData = null)
{
	// Make sure we have a definition for this type of activity.
	if (empty(self::$types[$type]))
		throw new Exception("Cannot create activity with non-existent type '$type'.");

	// Get the projections that are handled by this type.
	$projections = self::$types[$type];

	// Construct an array of information about the new activity.
	$activity = array(
		"type" => $type,
		"memberId" => $member["memberId"],
		"fromMemberId" => $fromMember ? $fromMember["memberId"] : null,
		"conversationId" => isset($data["conversationId"]) ? $data["conversationId"] : null,
		"postId" => isset($data["postId"]) ? $data["postId"] : null,
		"time" => time()
	);
	$activityId = null;

	// If this activity type has notification or activity projections, we'll need to insert the activity into the database.
	if (!empty($projections[self::PROJECTION_NOTIFICATION]) or !empty($projections[self::PROJECTION_ACTIVITY])) {
		$activityId = parent::create($activity + array("data" => serialize($data)));
	}

	// Set some more information about the activity.
	$activity["data"] = (array)$data + (array)$emailData;
	$activity["fromMemberName"] = $fromMember ? $fromMember["username"] : null;
	$activity["activityId"] = $activityId;

	// If this activity type has an email projection, the member wants to receive an email notification
	// for this type, and we haven't set sent them one in a previous call of this method, then let's send one!
	if (!empty($projections[self::PROJECTION_EMAIL]) and !empty($member["preferences"]["email.$type"]) and !in_array($member["memberId"], $this->membersUsed)) {

		// Log the member as "used", so we don't send them any more email notifications about the same subject.
		$this->membersUsed[] = $member["memberId"];

		// Load the member's language into esoTalk's memory.
		ET::saveLanguageState();
		ET::loadLanguage(@$member["preferences"]["language"]);

		// Get the email content by calling the type's email projection function.
		list($subject, $body) = call_user_func($projections[self::PROJECTION_EMAIL], $activity, $member);

		// Send the email, prepending/appending a common email header/footer.
		sendEmail($member["email"], $subject, sprintf(T("email.header"), $member["username"]).$body.sprintf(T("email.footer"), URL("settings", true)));

		// Revert back to esoTalk's old language definitions.
		ET::revertLanguageState();

	}

	return $activity["activityId"];
}


/**
 * Start a notification "group", or a set of notifications being sent out which regard the same subject.
 * For example, when someone replies to a conversation and it's the first post, email notifications are sent
 * out to all people who are in the "allowed members" list, and also to people who have starred the
 * converastion. Starting a notification group before adding this activity will limit the email notifications
 * to one per member.
 *
 * @return void
 */
public function startNotificationGroup()
{
	$this->membersUsed = array();
}


/**
 * End a notification "group". Same as above, but this should be used AFTER all notification re: a particular
 * subject have been sent.
 *
 * @return void
 */
public function endNotificationGroup()
{
	$this->membersUsed = array();
}


/**
 * Get activity data with the "activity" projection for a certain member (i.e. the activity that should
 * appear on their profile page.)
 *
 * @param array $member The details of the member to get activity for.
 * @param int $offset Offset to start getting results from.
 * @param int $limit Number of results to get.
 * @return array A multi-dimensional array of activity data.
 */
public function getActivity($member, $offset = 0, $limit = 11)
{
	// Construct a query that will get all the activity data from the activity table.
	$activity = ET::SQL()
		->select("activityId")
		->select("IF(fromMemberId IS NOT NULL,fromMemberId,a.memberId)", "fromMemberId")
		->select("m.username", "fromMemberName")
		->select("email")
		->select("avatarFormat")
		->select("type")
		->select("data")
		->select("NULL", "postId")
		->select("NULL", "title")
		->select("NULL", "content")
		->select("NULL", "start")
		->select("time")
		->from("activity a")
		->from("member m", "m.memberId=IF(fromMemberId IS NOT NULL,fromMemberId,a.memberId)", "left")
		->where("a.memberId=:memberId")
		->bind(":memberId", $member["memberId"])
		->where("a.type IN (:types)")
		->bind(":types", $this->getTypesWithProjection(self::PROJECTION_ACTIVITY))
		->orderBy("time DESC")
		->limit($offset + $limit);

	// Construct a query that will get all of the user's most recent posts.
	// All of the posts will be handled through the "post" activity type.
	$posts = ET::SQL()
		->select("NULL", "activityId")
		->select($member["memberId"], "fromMemberId")
		->select(ET::$database->escapeValue($member["username"]), "fromMemberName")
		->select(ET::$database->escapeValue($member["email"]), "email")
		->select(ET::$database->escapeValue($member["avatarFormat"]), "avatarFormat")
		->select("'postActivity'", "type")
		->select("NULL", "data")
		->select("postId")
		->select("c.title", "title")
		->select("content")
		->select("c.startMemberId=p.memberId AND c.startTime=p.time", "start")
		->select("time")
		->from("post p")
		->from("conversation c", "c.conversationId=p.conversationId", "left")
		->where("memberId=:memberId")
		->where("p.deleteTime IS NULL")
		->bind(":memberId", $member["memberId"])
		->where("c.countPosts>0")
		->where("c.private=0")
		->orderBy("time DESC")
		->limit($offset + $limit);
	ET::channelModel()->addPermissionPredicate($posts);

	// Marry these two queries so we get their activity AND their posts in one resultset.
	$result = ET::SQL()
		->union($activity)
		->union($posts)
		->orderBy("time DESC")
		->limit($limit)
		->offset($offset)
		->exec();

	// Now expand the resultset into a proper array of activity items by running activity type/projection
	// callback functions.
	$activity = array();
	while ($item = $result->nextRow()) {

		// If there's no activity type handler for this item and the "activity" projection, discard it.
		if (empty(self::$types[$item["type"]][self::PROJECTION_ACTIVITY])) continue;

		// Expand the activity data.
		$item["data"] = unserialize($item["data"]);

		// Run the type/projection's callback function. The return value is the activity description and body.
		list($item["description"], $item["body"]) = call_user_func_array(self::$types[$item["type"]][self::PROJECTION_ACTIVITY], array(&$item, $member)) + array(null, null);

		$activity[] = $item;
	}

	return $activity;
}


/**
 * Get activity data with the "notification" projection for the current user (i.e. the activity that should
 * appear in their notifications list.)
 *
 * @param int $limit Number of results to get. A value of -1 means get all new notifications (notifications
 * 		that have occurred since the member's notificationReadTime.)
 * @return array A multi-dimensional array of notification data.
 */
public function getNotifications($limit = 5)
{
	if (!ET::$session->user) return null;

	$result = ET::SQL()
		->select("a.fromMemberId")
		->select("m.username", "fromMemberName")
		->select("m.email")
		->select("m.avatarFormat")
		->select("a.time")
		->select("a.data")
		->select("a.type")
		->select("a.postId")
		->select("a.conversationId")
		->select("a.read")
		->from("activity a")
		->from("member m", "m.memberId=a.fromMemberId", "left")
		->from("activity prev", "prev.conversationId=a.conversationId AND prev.activityId>a.activityId", "left")
		->where("prev.activityId IS NULL")
		->where("a.memberId=:userId")
		->bind(":userId", ET::$session->userId)
		->where("a.type IN (:types)")
		->bind(":types", $this->getTypesWithProjection(self::PROJECTION_NOTIFICATION))
		->orderBy("a.time DESC")
		->limit($limit == -1 ? false : $limit);

	// If we're only getting unread notifications...
	if ($limit == -1) {
		$result->where("a.read=0");
	}

	$result = $result->exec();

	// Now expand the resultset into a proper array of activity items by running activity type/projection
	// callback functions.
	$notifications = array();
	while ($item = $result->nextRow()) {

		// If there's no activity type handler for this item and the "notification" projection, discard it.
		if (empty(self::$types[$item["type"]][self::PROJECTION_NOTIFICATION])) continue;

		// Expand the activity data.
		$item["data"] = unserialize($item["data"]);

		// Work out if the notification is unread.
		$item["unread"] = !$item["read"];

		// Run the type/projection's callback function. The return value is the notification body and link.
		list($item["body"], $item["link"]) = call_user_func_array(self::$types[$item["type"]][self::PROJECTION_NOTIFICATION], array(&$item)) + array(null, null);

		$notifications[] = $item;
	}

	return $notifications;
}


public function markNotificationsAsRead($type = null, $conversationId = null)
{
	$query = ET::SQL()
		->update("activity")
		->set("`read`", 1)
		->where("memberId=:memberId")
		->where("`read`=0")
		->bind(":memberId", ET::$session->userId);

	if ($type) {
		$query->where("type=:type")
			->bind(":type", $type);
	}

	if ($conversationId) {
		$query->where("conversationId=:conversationId")
			->bind(":conversationId", $conversationId);
	}

	$query->exec();
}


/**
 * Returns a formatted activity item for the "post" activity type. For example, '[member] posted in [title]'.
 *
 * @param array $item The activity item's details.
 * @param array $member The details of the member this activity is for.
 * @return array 0 => activity title, 1 => activity body
 */
public static function postActivity($item, $member)
{
	return array(
		sprintf(T($item["start"] ? "%s started the conversation %s." : "%s posted in %s."), name($member["username"]), "<a href='".URL(postURL($item["postId"]))."'>".sanitizeHTML($item["title"])."</a>"),
		ET::formatter()->init($item["content"])->format()->get()
	);
}


/**
 * Returns a formatted notification item for the "post" activity type. For example, '[member]
 * posted in [title]'.
 *
 * @param array $item The activity item's details.
 * @return array 0 => notification body, 1 => notification link
 */
public static function postNotification(&$item)
{
	return array(
		"<i class='star icon-star'></i> ".sprintf(T("%s posted in %s."), name($item["fromMemberName"]), "<strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
		URL(postURL($item["postId"]))
	);
}


/**
 * Returns a formatted activity item for the "join" activity type. For example, '[member] joined the forum.'
 *
 * @see postActivity() for parameter and return information.
 */
public static function joinActivity($item, $member)
{
	return array(
		sprintf(T("%s joined the forum."), name($member["username"])),
		false
	);
}


/**
 * Returns a formatted notification item for the "groupChange" activity type. For example, '[member] changed
 * your group to [groups].'
 *
 * @see postNotification() for parameter and return information.
 */
public static function groupChangeNotification($item)
{
	$groups = memberGroup($item["data"]["account"], $item["data"]["groups"], true);
	return array(
		"<i class='icon-user'></i> ".sprintf(T("%s changed your group to %s."), name($item["fromMemberName"]), "<strong>".$groups."</strong>"),
		URL(memberURL("me"))
	);
}


/**
 * Returns a formatted activity item for the "groupChange" activity type. For example, '[fromMember] changed
 * [member]'s group to [groups].'
 *
 * @see postActivity() for parameter and return information.
 */
public static function groupChangeActivity($item, $member)
{
	$groups = memberGroup($item["data"]["account"], $item["data"]["groups"], true);
	return array(
		sprintf(T("%s changed %s's group to %s."), name($item["fromMemberName"]), name($member["username"]), "<strong>".$groups."</strong>"),
		false
	);
}


/**
 * Returns a formatted notification item for the "mention" activity type. For example, '[member] tagged you
 * in a post.'
 *
 * @see postNotification() for parameter and return information.
 */
public static function mentionNotification($item)
{
	return array(
		sprintf("@ ".T("%s mentioned you in %s."), name($item["fromMemberName"]), "<strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
		URL(postURL($item["data"]["postId"]))
	);
}


/**
 * Returns a formatted email subject+body for the "mention" activity type.
 *
 * @param array $item The activity item's details.
 * @param array $member The details of the member this activity is for.
 * @return array 0 => email subject, 1 => email body
 */
public static function mentionEmail($item, $member)
{
	$content = ET::formatter()->init($item["data"]["content"])->format()->get();
	$url = URL(postURL($item["data"]["postId"]), true);
	return array(
		sprintf(T("email.mention.subject"), name($item["fromMemberName"], false), $item["data"]["title"]),
		sprintf(T("email.mention.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), $content, "<a href='$url'>$url</a>")
	);
}


/**
 * Returns a formatted notification item for the "privateAdd" activity type. For example,
 * '[member1] invited you to [title]'.
 *
 * @param array $item The activity item's details.
 * @return array 0 => notification body, 1 => notification link
 */
public static function privateAddNotification(&$item)
{
	return array(
		label("private")." ".sprintf(T("%s invited you to %s."), name($item["fromMemberName"]), "<strong>".sanitizeHTML($item["data"]["title"])."</strong>"),
		URL(conversationURL($item["conversationId"]))
	);
}


/**
 * Returns a formatted email subject+body for the "privateAdd" activity type.
 *
 * @see mentionEmail() for parameter and return information.
 */
public static function privateAddEmail($item, $member)
{
	$content = ET::formatter()->init($item["data"]["content"])->format()->get();
	$url = URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"]), true);
	return array(
		sprintf(T("email.privateAdd.subject"), $item["data"]["title"]),
		sprintf(T("email.privateAdd.body"), sanitizeHTML($item["data"]["title"]), $content, "<a href='$url'>$url</a>")
	);
}


/**
 * Returns a formatted email subject+body for the "post" activity type.
 *
 * @see mentionEmail() for parameter and return information.
 */
public static function postEmail($item, $member)
{
	$content = ET::formatter()->init($item["data"]["content"])->format()->get();
	$url = URL(conversationURL($item["data"]["conversationId"], $item["data"]["title"])."/unread", true);
	return array(
		sprintf(T("email.post.subject"), $item["data"]["title"]),
		sprintf(T("email.post.body"), name($item["fromMemberName"]), sanitizeHTML($item["data"]["title"]), $content, "<a href='$url'>$url</a>")
	);
}


/**
 * Returns a formatted notification item for the "updateAvailable" activity type.
 *
 * @see postNotification() for parameter and return information.
 */
public static function updateAvailableNotification($item)
{
	return array(
		"<i class='icon-wrench'></i> ".sprintf(T("A new version of esoTalk (%s) is available."), "<strong>".$item["data"]["version"]."</strong>"),
		!empty($item["data"]["releaseNotes"]) ? $item["data"]["releaseNotes"] : "http://esotalk.org/"
	);
}


/**
 * Returns a formatted notification item for the "unapproved" activity type.
 *
 * @see postNotification() for parameter and return information.
 */
public static function unapprovedNotification($item)
{
	return array(
		"<i class='icon-user'></i> ".sprintf(T("%s has registered and is awaiting approval."), "<strong>".name($item["data"]["username"])."</strong>"),
		URL("admin/unapproved")
	);
}

}


// Add default activity types.
ETActivityModel::addType("post", array(
	"notification" => array("ETActivityModel", "postNotification"),
	"email" => array("ETActivityModel", "postEmail")
));

ETActivityModel::addType("postActivity", array(
	"activity" => array("ETActivityModel", "postActivity"),
));

ETActivityModel::addType("groupChange", array(
	"activity" => array("ETActivityModel", "groupChangeActivity"),
	"notification" => array("ETActivityModel", "groupChangeNotification")
));

ETActivityModel::addType("mention", array(
	"notification" => array("ETActivityModel", "mentionNotification"),
	"email" => array("ETActivityModel", "mentionEmail")
));

ETActivityModel::addType("join", array(
	"activity" => array("ETActivityModel", "joinActivity")
));

// Define an email to send out when a member is added to a private conversation.
ETActivityModel::addType("privateAdd", array(
	"notification" => array("ETActivityModel", "privateAddNotification"),
	"email" => array("ETActivityModel", "privateAddEmail")
));

// Notification for when an update to the esoTalk software is available.
ETActivityModel::addType("updateAvailable", array(
	"notification" => array("ETActivityModel", "updateAvailableNotification")
));

// Notification for when a new user signs up and needs approval.
ETActivityModel::addType("unapproved", array(
	"notification" => array("ETActivityModel", "unapprovedNotification")
));
