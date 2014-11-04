<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The groups admin controller handles the management of member groups.
 *
 * @package esoTalk
 */
class ETGroupsAdminController extends ETAdminController {


/**
 * Show a sheet containing a list of groups. Pretty simple, really!
 *
 * @return void
 */
public function index()
{
	$groups = ET::groupModel()->getAll();

	$this->data("groups", $groups);
	$this->render("admin/groups");
}


/**
 * Show a sheet to edit a member group's details.
 *
 * @param int $groupId The ID of the group to edit.
 * @return void
 */
public function edit($groupId = "")
{
	// Get this group's details. If it doesn't exist, show an error.
	if (!($group = ET::groupModel()->getById((int)$groupId))) {
		$this->render404();
		return;
	}

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/groups/edit/".$group["groupId"]);
	$form->setValues($group);

	// Was the cancel button pressed?
	if ($form->isPostBack("cancel")) $this->redirect(URL("admin/groups"));

	// Was the save button pressed?
	if ($form->validPostBack("save")) {

		$data = array(
			"name" => $form->getValue("name"),
			"canSuspend" => $form->getValue("canSuspend")
		);

		$model = ET::groupModel();
		$model->updateById($group["groupId"], $data);

		// If there were errors, pass them on to the form.
		if ($model->errorCount()) $form->errors($model->errors());

		// Otherwise, redirect back to the groups page.
		else $this->redirect(URL("admin/groups"));
	}

	$this->data("form", $form);
	$this->data("group", $group);
	$this->render("admin/editGroup");
}


/**
 * Show a sheet to create a new group.
 *
 * @return void
 */
public function create()
{
	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/groups/create");

	// Was the cancel button pressed?
	if ($form->isPostBack("cancel")) $this->redirect(URL("admin/groups"));

	// Was the save button pressed?
	if ($form->validPostBack("save")) {

		$data = array(
			"name" => $form->getValue("name"),
			"canSuspend" => $form->getValue("canSuspend")
		);

		$model = ET::groupModel();
		$groupId = $model->create($data);

		// If there were errors, pass them on to the form.
		if ($model->errorCount()) $form->errors($model->errors());

		// Otherwise...
		else {

			// Do we want to give this group the moderate permission on all existing channels?
			if ($form->getValue("giveModeratePermission")) {

				// Go through all the channels and construct an array of rows to insert into the channel_group table.
				$channels = ET::channelModel()->getAll();
				$inserts = array();
				foreach ($channels as $id => $channel) {
					$inserts[] = array($id, $groupId, 1, 1, 1, 1);
				}

				// Insert them!
				ET::SQL()
					->insert("channel_group")
					->setMultiple(array("channelId", "groupId", "view", "reply", "start", "moderate"), $inserts)
					->setOnDuplicateKey("moderate", 1)
					->exec();

			}

			// Redirect back to the groups page.
			$this->redirect(URL("admin/groups"));

		}
	}

	$this->data("form", $form);
	$this->data("group", null);
	$this->render("admin/editGroup");
}


/**
 * Delete a group.
 *
 * @param int $groupId The ID of the group to delete.
 * @return void
 */
public function delete($groupId = "")
{
	if (!$this->validateToken()) return;

	// Get this group's details. If it doesn't exist, show an error.
	if (!($group = ET::groupModel()->getById((int)$groupId))) {
		$this->render404();
		return;
	}

	ET::groupModel()->deleteById($group["groupId"]);

	$this->redirect(URL("admin/groups"));
}

}