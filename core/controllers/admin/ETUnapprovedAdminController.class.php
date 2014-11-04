<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The unapproved admin controller allows administrators to approve newly signed up members.
 * It is not accessible unless esoTalk.registration.requireConfirmation == approval.
 *
 * @package esoTalk
 */
class ETUnapprovedAdminController extends ETAdminController {


/**
 * Show a sheet containing a list of groups. Pretty simple, really!
 *
 * @return void
 */
public function action_index()
{
	ET::activityModel()->markNotificationsAsRead('unapproved');
	
	$sql = ET::SQL();
	$sql->where("confirmed", 0);
	$sql->orderBy("m.memberId desc");
	$members = ET::memberModel()->getWithSQL($sql);

	$this->data("members", $members);
	$this->render("admin/unapproved");
}


/**
 * Approve a member.
 *
 * @param int $memberId The ID of the member to approve.
 * @return void
 */
public function action_approve($memberId)
{
	if (!$this->validateToken()) return;

	// Get this member's details. If it doesn't exist or is already approved, show an error.
	if (!($member = ET::memberModel()->getById((int)$memberId)) or $member["confirmed"]) {
		$this->redirect(URL("admin/unapproved"));
		return;
	}

	ET::memberModel()->updateById($memberId, array("confirmed" => true));

	sendEmail($member["email"],
		sprintf(T("email.approved.subject"), $member["username"]),
		sprintf(T("email.header"), $member["username"]).sprintf(T("email.approved.body"), C("esoTalk.forumTitle"), URL("user/login", true))
	);

	$this->message(T("message.changesSaved"), "success autoDismiss");
	$this->redirect(URL("admin/unapproved"));
}


/**
 * Deny a member; delete their account.
 *
 * @param int $memberId The ID of the member to deny.
 * @return void
 */
public function action_deny($memberId)
{
	// Get this member's details. If it doesn't exist or is already approved, show an error.
	if (!($member = ET::memberModel()->getById((int)$memberId)) or $member["confirmed"]) {
		$this->redirect(URL("admin/unapproved"));
		return;
	}

	ET::memberModel()->deleteById($memberId);

	$this->message(T("message.changesSaved"), "success autoDismiss");
	$this->redirect(URL("admin/unapproved"));
}

}
