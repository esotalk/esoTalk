<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controlls handles the management of channels.
 *
 * @package esoTalk
 */
class ETChannelsAdminController extends ETAdminController {


/**
 * Show the list of channels.
 *
 * @return void
 */
public function index()
{
	// Get all of the channels.
	$channels = ET::channelModel()->getAll();

	// Add necessary JavaScript to enable sorting of the channel list.
	$this->addJSFile("js/lib/jquery.ui.js");
	$this->addJSFile("js/lib/jquery.ui.nestedSortable.js");

	$this->title = T("Channels");
	$this->data("channels", $channels);
	$this->render("admin/channels");
}


/**
 * Edit a channel's details.
 *
 * @param int $channelId The ID of the channel to edit.
 * @return void
 */
public function edit($channelId = "")
{
	// Get the channel!
	$channels = ET::channelModel()->getAll();
	if (!isset($channels[$channelId])) return;
	$channel = $channels[$channelId];

	// Set up a form!
 	$form = ETFactory::make("form");
 	$form->action = URL("admin/channels/edit/".$channel["channelId"]);
 	$form->setValues($channel);
 	$form->setValues($channel["attributes"]);

	// Get a list of groups!
 	$groups = ET::groupModel()->getAll();

 	// Make a list of the types of permissions!
	$permissions = array("view" => "View", "reply" => "Reply", "start" => "Start", "moderate" => "Moderate");

 	// Set which permission checkboxes should be checked on the form!
 	foreach ($channel["permissions"] as $type => $groupIds) {
 		foreach ($groupIds as $groupId)
 			$form->setValue("permissions[$groupId][$type]", 1);
 	}

 	// If the form was submitted...
 	if ($form->validPostBack("save")) {

 		// Save the channel's information.
 		$model = ET::channelModel();
 		$model->updateById($channelId, array(
 			"title" => $form->getValue("title"),
 			"slug" => $model->generateSlug($form->getValue("title")),
 			"description" => $form->getValue("description"),
 			"attributes" => array_merge((array)$channel["attributes"], array("defaultUnsubscribed" => $form->getValue("defaultUnsubscribed")))
	 	));

	 	// Set the channel's permissions.
	 	$model->setPermissions($channelId, $form->getValue("permissions"));

	 	// If there were errors, pass them on to the form.
	 	if ($model->errorCount()) $form->errors($model->errors());

	 	// Otherwise, show a message and redirect.
	 	else {
	 		$this->message(T("message.changesSaved"), "success");
	 		$this->redirect(URL("admin/channels"));
	 	}

 	}

 	// Overuse of exclamation marks!
 	$this->data("channels", $channels);
 	$this->data("channel", $channel);
 	$this->data("groups", $groups);
 	$this->data("permissions", $permissions);
 	$this->data("form", $form);
 	$this->render("admin/editChannel");
}


/**
 * Save the tree structure of the channels.
 *
 * @return void
 */
public function reorder()
{
	if (!$this->validateToken()) return;

	// All of the tree information, including depth, parent_id, left, and right for each channel, should be
	// in this input variable.
	$tree = R("tree");

	// Go through each channel in the tree and save its information.
	foreach ($tree as $k => $v) {

		if ($v["depth"] == -1) continue;

		ET::channelModel()->updateById($v["item_id"], array(
			"parentId" => $v["parent_id"],
			"depth" => $v["depth"],
			"lft" => $v["left"] - 1,
			"rgt" => $v["right"] - 1
		));
	}
}


/**
 * Get a channel's permissions. This is used to copy a channel's permissions into the permissions grid.
 *
 * @param int $channelId The ID of the channel.
 * @return void
 */
public function getPermissions($channelId)
{
	$this->responseType = RESPONSE_TYPE_JSON;
	$channels = ET::channelModel()->getAll();
	if (!isset($channels[$channelId])) return;
	else {
		$this->json("permissions", $channels[$channelId]["permissions"]);
		$this->render();
	}
}


/**
 * Create a channel.
 *
 * @return void
 */
public function create()
{
	// Get the channels!
	$channels = ET::channelModel()->getAll();

	// Set up a form!
 	$form = ETFactory::make("form");
 	$form->action = URL("admin/channels/create");

	// Get a list of groups!
 	$groups = ET::groupModel()->getAll();

 	// Make a list of the types of permissions!
	$permissions = array("view" => "View", "reply" => "Reply", "start" => "Start", "moderate" => "Moderate");

 	// Set which permission checkboxes should be checked on the form!
 	$form->setValue("permissions[".GROUP_ID_GUEST."][view]", 1);
 	$form->setValue("permissions[".GROUP_ID_MEMBER."][view]", 1);
 	$form->setValue("permissions[".GROUP_ID_MEMBER."][reply]", 1);
 	$form->setValue("permissions[".GROUP_ID_MEMBER."][start]", 1);

 	// If the form was submitted...
 	if ($form->validPostBack("save")) {

 		// Save the channel's information.
 		$model = ET::channelModel();
 		$channelId = $model->create(array(
 			"title" => $form->getValue("title"),
 			"slug" => $model->generateSlug($form->getValue("title")),
 			"description" => $form->getValue("description"),
 			"attributes" => array("defaultUnsubscribed" => $form->getValue("defaultUnsubscribed"))
	 	));

	 	// If there were errors, pass them on to the form.
	 	if ($model->errorCount()) $form->errors($model->errors());

	 	else {

		 	// Set the channel's permissions.
	 		$model->setPermissions($channelId, $form->getValue("permissions"));

	 		$this->message(T("message.changesSaved"), "success");
	 		$this->redirect(URL("admin/channels"));

	 	}

 	}

 	// Overuse of exclamation marks!
 	$this->data("channels", $channels);
 	$this->data("channel", false);
 	$this->data("groups", $groups);
 	$this->data("permissions", $permissions);
 	$this->data("form", $form);
 	$this->render("admin/editChannel");
}


/**
 * Delete a channel.
 *
 * @param int $channelId The ID of the channel to delete.
 * @return void
 */
public function delete($channelId)
{
	// Get the channel.
	$channels = ET::channelModel()->getAll();
	if (!isset($channels[$channelId])) return;
	$channel = $channels[$channelId];

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/channels/delete/".$channelId);
	$form->setValue("method", "move");

	// If the form was submitted...
	if ($form->validPostBack("delete")) {

		// If this is the last channel, we can't delete it.
		if (count($channels) == 1)
			$this->message(T("message.cannotDeleteLastChannel"), "warning");

		// Otherwise...
		else {

			// Attempt to delete the channel.
			$model = ET::channelModel();
			$model->deleteById($channelId, $form->getValue("method") == "move" ? (int)$form->getValue("moveToChannelId") : false);

			// If there were errors, pass them on to the form.
			if ($model->errorCount())
				$form->errors($model->errors());

			// Otherwise, redirect back to the channels page.
			else {
				$this->message(T("message.changesSaved"), "success");
				$this->redirect(URL("admin/channels"));
			}

		}
	}

	$this->data("channels", $channels);
	$this->data("channel", $channel);
	$this->data("form", $form);
	$this->render("admin/deleteChannel");
}

}