<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The member controller handles all actions to do with viewing/managing a single member. This includes
 * viewing their profile (and individual profile panes), changing their permissions, and deleting or renaming
 * them.
 *
 * @package esoTalk
 */
class ETMemberController extends ETController {


/**
 * A render function that will render the specified view inside of the main member "profile" view. (Except
 * on AJAX/view response types.)
 *
 * @param string $view The name of the view to render.
 * @return void
 */
public function renderProfile($view = "")
{
	if (!in_array($this->responseType, array(RESPONSE_TYPE_VIEW, RESPONSE_TYPE_AJAX))) {
		$this->data("view", $view);
		parent::render("member/profile");
	}

	else parent::render($view);
}


/**
 * Redirect to the profile for the member with the specified username.
 *
 * @param string $name The name of the member.
 * @return void
 */
public function name($name = "")
{
	$result = ET::SQL()
		->select("memberId, username")
		->from("member")
		->where("username", $name)
		->exec();

	if ($row = $result->firstRow())
		$this->redirect(URL(memberURL($row["memberId"], $row["username"])));

	// If we didn't find the member, run the index function with a false argument (which will in turn show
	// a not found error.)
	$this->index(false);
}


/**
 * View a member's profile. Default to the activity pane.
 *
 * @param string $member The ID of the member.
 * @return void
 */
public function index($member = "")
{
	$this->activity($member);
}


/**
 * Fetch the specified member's details, or render a not found error if the member doesn't exist.
 *
 * @param string $member The member's ID.
 * @return array An array of the member's details, or false if they weren't found.
 */
protected function getMember($memberId)
{
	if (!$memberId or !($member = ET::memberModel()->getById((int)$memberId))) {
		$this->render404(T("message.memberNotFound"));
		return false;
	}

	return $member;
}


/**
 * Set up data and menus that are needed to render the member profile view.
 *
 * @param string $member The ID of the member.
 * @param string $pane The name of the active pane.
 * @return array The member details, or false if the member was not found.
 */
protected function profile($member, $pane = "")
{
	// Translate "me" to the currently logged in user. Otherwise, use the member ID provided.
	if ($member == "me") $memberId = ET::$session->userId;
	else $memberId = (int)$member;

	if (!($member = $this->getMember($memberId))) return false;

	// Set the title and include relevant JavaScript.
	$this->title = $member["username"];
	$this->addJSFile("core/js/member.js");
	$this->addJSVar("memberId", $member["memberId"]);

	// Sort out what the canonical URL for this page is.
	$url = memberURL($member["memberId"], $member["username"], $pane);
	$this->pushNavigation("member/".$member["memberId"], "member", URL($url));
	$this->canonicalURL = URL($url, true);

	// Make a list of default member panes, and highlight the currently active one.
	$panes = ETFactory::make("menu");
	$panes->add("activity", "<a href='".URL(memberURL($member["memberId"], $member["username"], "activity"))."'>".T("Activity")."</a>");
	$panes->add("statistics", "<a href='".URL(memberURL($member["memberId"], $member["username"], "statistics"))."'>".T("Statistics")."</a>");
	$panes->highlight($pane);

	// Set up the controls menu (things that can be changed on the member.)
	$model = ET::memberModel();
	$controls = ETFactory::make("menu");

	// Add the suspend/unsuspend control, and the "remove avatar" control.
	if ($model->canSuspend($member)) {
	 	$controls->add("suspend", "<a href='".URL("member/suspend/".$member["memberId"])."' id='suspendLink'>".T($member["account"] == ACCOUNT_SUSPENDED ? "Unsuspend member" : "Suspend member")."</a>");
	 	if ($member["avatarFormat"]) $controls->add("removeAvatar", "<a href='".URL("member/removeAvatar/".$member["memberId"]."?token=".ET::$session->token)."' id='removeAvatarLink'>".T("Remove avatar")."</a>");
	 	$controls->separator();
	}

	// Add the change permissions control (provided the member is not suspended.)
	if ($member["account"] != ACCOUNT_SUSPENDED and $model->canChangePermissions($member))
	 	$controls->add("permissions", "<a href='".URL("member/permissions/".$member["memberId"])."' id='editPermissionsLink'>".T("Change permissions")."</a>");

	// Add the rename control.
	if ($model->canRename($member))
	 	$controls->add("rename", "<a href='".URL("member/rename/".$member["memberId"])."' id='renameLink'>".T("Change username")."</a>");

	// Add the delete control.
	if ($model->canDelete($member)) {
		$controls->separator();
	 	$controls->add("delete", "<a href='".URL("member/delete/".$member["memberId"])."' id='deleteLink'>".T("Delete member")."</a>");
	}

	// Set up the actions menu (things that can be done in relation to the member.)
	$actions = ETFactory::make("menu");

	// If this is the logged-in user's profile, show a link to their settings page.
	if ($member["memberId"] == ET::$session->userId)
		$actions->add("settings", "<a href='".URL("settings")."'>".T("Edit your profile")."</a>");

	// Otherwise, show links to do with the user's private conversations with this member.
	elseif (ET::$session->userId) {
		$actions->add("privateConversations", "<a href='".URL(searchURL("#private + #contributor:".$member["username"]))."'>".sprintf(T("See the private conversations I've had with %s"), $member["username"])."</a>");
		$actions->add("privateStart", "<a href='".URL("conversation/start/".urlencode($member["username"])."?token=".ET::$session->token)."'>".sprintf(T("Start a private conversation with %s"), $member["username"])."</a>");
	}

	$this->trigger("initProfile", array(&$member, $panes, $controls, $actions));

	// Pass along these menus to the view.
	$this->data("member", $member);
	$this->data("panes", $panes);
	$this->data("controls", $controls);
	$this->data("actions", $actions);

	return $member;
}


/**
 * Show a member profile with the activity pane.
 *
 * @param string $member The member ID.
 * @param int $page The activity page number.
 * @return void
 */
public function activity($member = "", $page = "")
{
	// Set up the member profile page.
	if (!($member = $this->profile($member, "activity"))) return;

	// Work out the page number we're viewing and fetch the activity.
	$page = max(0, (int)$page - 1);
	$activity = ET::activityModel()->getActivity($member, $page * 10, 11);

	// We fetch 11 items so we can tell if there are more items after this page.
	$showViewMoreLink = false;
	if (count($activity) == 11) {
		array_pop($activity);
		$showViewMoreLink = true;
	}

	// Pass along necessary data to the view.
	$this->data("activity", $activity);
	$this->data("page", $page);
	$this->data("showViewMoreLink", $showViewMoreLink);

	$this->addJSLanguage("message.confirmDelete");

	$this->renderProfile("member/activity");
}


/**
 * Delete an activity item.
 *
 * @param int $activityId The ID of the activity item.
 * @return void
 */
public function deleteActivity($activityId = "")
{
	if (!$this->validateToken()) return;

	// Delete the activity, making sure it is owned by the currently logged in user.
	ET::SQL()
		->delete()
		->from("activity")
		->where("activityId", (int)$activityId)
		->where("memberId=:memberId OR fromMemberId=:fromMemberId")
		->bind(":memberId", ET::$session->userId)
		->bind(":fromMemberId", ET::$session->userId)
		->exec();

	// Redirect back to the member's profile.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT)
		$this->redirect(URL(R("return", "member/activity/me")));

	$this->render();
}


/**
 * Show a member profile with the statistics pane.
 *
 * @param string $member The member ID.
 * @return void
 */
public function statistics($member = "")
{
	// Set up the member profile page.
	if (!($member = $this->profile($member, "statistics"))) return;

	// Fetch statistics about the member's posts (when they first posted, and how many different conversations
	// they've participated in.)
	$statistics = ET::SQL()
		->select("MIN(time)", "firstPosted")
		->select("COUNT(DISTINCT conversationId)", "conversationsParticipated")
		->from("post")
		->where("memberId", $member["memberId"])
		->exec()
		->firstRow();

	// Add a few more statistics (their post count, conversation count, and join time.)
	$statistics["postCount"] = $member["countPosts"];
	$statistics["conversationsStarted"] = $member["countConversations"];
	$statistics["joinTime"] = $member["joinTime"];

	// Send it off to the view.
	$this->data("statistics", $statistics);

	$this->renderProfile("member/statistics");
}


/**
 * Show a sheet to edit a member's permissions by changing their account type and groups.
 *
 * @param int $memberId The member's ID.
 * @return void
 */
public function permissions($memberId = "")
{
	if (!($member = $this->getMember($memberId))) return;

	// If we don't have permission to change the member's permissions, throw an error.
	if (!ET::memberModel()->canChangePermissions($member)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
	 	return;
	}

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("member/permissions/".$member["memberId"]);

	// Get a list of all possible account types, groups, and permission types.
	$accounts = array(ACCOUNT_ADMINISTRATOR, ACCOUNT_MEMBER);
	$groups = ET::groupModel()->getAll();
	$permissions = array("view" => T("View"), "reply" => T("Reply"), "start" => T("Start"), "moderate" => T("Moderate"));

	// Set the value of the account field in the form to the member's current account.
	$form->setValue("account", $member["account"]);

	// Get the currently selected account from the form input.
	$currentAccount = $form->getValue("account", $member["account"]);
	if (!in_array($currentAccount, $accounts)) $currentAccount = ACCOUNT_MEMBER;

	// Get the currently selected groups from the form input, and a list of collective group IDs.
	$currentGroups = (array)$form->getValue("groups", array_keys($member["groups"]));
	$groupIds = ET::groupModel()->getGroupIds($currentAccount, $currentGroups);

	// Get a list of all channels and their permissions, which we can use to construct a permissions grid.
	$channels = ET::channelModel()->getAll();

	// Create a list of "extra" permissions (eg. being able to access the admin CP, or suspend members.)
	$extraPermissions = array();
	if ($currentAccount == ACCOUNT_ADMINISTRATOR)
		$extraPermissions[] = T("Access the administrator control panel.");

	// Work out if they will be able to suspend members by going through each group and checking its permissions.
	$canSuspend = false;
	if ($currentAccount == ACCOUNT_ADMINISTRATOR) $canSuspend = true;
	else {
		foreach ($currentGroups as $group) {
			if (!empty($groups[$group]["canSuspend"])) {
				$canSuspend = true;
				break;
			}
		}
	}
	if ($canSuspend) $extraPermissions[] = T("Suspend members.");

	// Handle a post back from the form.
	$redirectURL = URL(memberURL($member["memberId"], $member["username"]));
	if ($form->isPostBack("cancel")) $this->redirect($redirectURL);

	if ($form->validPostBack("save")) {

		// Save the new account and groups.
		ET::memberModel()->setGroups($member, $currentAccount, $currentGroups);

		// Show a message and redirect.
		$this->message(T("message.changesSaved"), "success");
		$this->redirect($redirectURL);
	}

	// Transport data to the view.
	$this->data("form", $form);
	$this->data("accounts", $accounts);
	$this->data("groups", $groups);
	$this->data("groupIds", $groupIds);
	$this->data("permissions", $permissions);
	$this->data("channels", $channels);
	$this->data("member", $member);
	$this->data("extraPermissions", $extraPermissions);

	// For an ajax request, show permission info.
	if ($this->responseType === RESPONSE_TYPE_AJAX)
		$this->render("member/permissionInfo");

	// Otherwise show the permission sheet.
	else $this->render("member/permissions");
}


/**
 * Remove a member's avatar and redirect back to their profile.
 *
 * @param int $memberId The member's ID.
 * @return void
 */
public function removeAvatar($memberId = "")
{
	if (!$this->validateToken()) return;

	if (!($member = $this->getMember($memberId))) return;

	// If we don't have permission to remove the avatar, throw an error.
	if (!ET::memberModel()->canSuspend($member)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
	 	return;
	}

	// Remove the avatar file.
	@unlink(PATH_UPLOADS."/avatars/".$member["memberId"].".".$member["avatarFormat"]);

	// Clear the member's avatar format field.
	ET::memberModel()->updateById($member["memberId"], array("avatarFormat" => null));

	$url = R("return", memberURL($member["memberId"], $member["username"]));
	$this->redirect(URL($url));
}


/**
 * Show a sheet to toggle suspension of a member.
 *
 * @param int $memberId The member's ID.
 * @return void
 */
public function suspend($memberId = "")
{
	if (!($member = $this->getMember($memberId))) return;

	// If we don't have permission to suspend the member, throw an error.
	if (!ET::memberModel()->canSuspend($member)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
	 	return;
	}

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("member/suspend/".$member["memberId"]);

	$redirectURL = URL(memberURL($member["memberId"], $member["username"]));
	if ($form->isPostBack("cancel")) $this->redirect($redirectURL);

	// Suspend the member?
	if ($form->validPostBack("suspend") and $member["account"] != ACCOUNT_SUSPENDED) {
		ET::memberModel()->setGroups($member, ACCOUNT_SUSPENDED);
		$this->message(T("message.changesSaved"), "success");
		$this->redirect($redirectURL);
	}

	// Or unsuspend the member?
	elseif ($form->validPostBack("unsuspend") and $member["account"] == ACCOUNT_SUSPENDED) {
		ET::memberModel()->setGroups($member, ACCOUNT_MEMBER);
		$this->message(T("message.changesSaved"), "success");
		$this->redirect($redirectURL);
	}

	$this->data("member", $member);
	$this->data("form", $form);

	$this->render("member/suspend");
}


/**
 * Show a sheet to change a member's username.
 *
 * @param int $memberId The member's ID.
 * @return void
 */
public function rename($memberId = "")
{
	if (!($member = $this->getMember($memberId))) return;

	// If we don't have permission to rename the member, throw an error.
	if (!ET::memberModel()->canRename($member)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
	 	return;
	}

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("member/rename/".$member["memberId"]);

	$redirectURL = URL(memberURL($member["memberId"], $member["username"]));
	if ($form->isPostBack("cancel")) $this->redirect($redirectURL);

	// If the form was submitted, change the member's username.
	if ($form->validPostBack("save")) {

		// Update the username.
		$model = ET::memberModel();
		$model->updateById($member["memberId"], array("username" => $form->getValue("username")));

		// Check for errors - if there are none, show a message and redirect.
		if ($model->errorCount()) $form->errors($model->errors());
		else {
			$this->message(T("message.changesSaved"), "success");
			$this->redirect($redirectURL);
		}
	}

	$this->data("member", $member);
	$this->data("form", $form);

	$this->render("member/rename");
}


/**
 * Show a sheet to delete a member.
 *
 * @param int $memberId The member's ID.
 * @return void
 */
public function delete($memberId = "")
{
	if (!($member = $this->getMember($memberId))) return;

	// If we don't have permission to delete the member, throw an error.
	if (!ET::memberModel()->canDelete($member)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
	 	return;
	}

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("member/delete/".$member["memberId"]);

	$redirectURL = URL(memberURL($member["memberId"], $member["username"]));
	if ($form->isPostBack("cancel")) $this->redirect($redirectURL);

	// If the form was submitted, delete the member and take the appropriate action upon all their posts.
	if ($form->validPostBack("delete")) {
		ET::memberModel()->deleteById($member["memberId"], $form->getValue("deletePosts"));
		$this->message(T("message.changesSaved"), "success");
		$this->redirect(URL("members"));
	}

	$this->data("member", $member);
	$this->data("form", $form);

	$this->render("member/delete");
}

}