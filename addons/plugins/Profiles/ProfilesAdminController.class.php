<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

class ProfilesAdminController extends ETAdminController {

protected function profileFieldModel()
{
	return ET::getInstance("profileFieldModel");
}

public function index()
{
	$fields = $this->profileFieldModel()->get();

	$this->addCSSFile(ET::$plugins["Profiles"]->getResource("admin.css"));

	$this->addJSFile("core/js/lib/jquery.ui.js");
	$this->addJSFile(ET::$plugins["Profiles"]->getResource("admin.js"));
	$this->addJSLanguage("message.confirmDelete");

	$this->data("fields", $fields);
	$this->render(ET::$plugins["Profiles"]->getView("admin/fields"));
}


public function edit($fieldId = "")
{
	// Get this field's details. If it doesn't exist, show an error.
	if (!($field = $this->profileFieldModel()->getById((int)$fieldId))) {
		$this->render404();
		return;
	}

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/profiles/edit/".$field["fieldId"]);
	$form->setValues($field);

	// Was the cancel button pressed?
	if ($form->isPostBack("cancel")) $this->redirect(URL("admin/profiles"));

	// Was the save button pressed?
	if ($form->validPostBack("save")) {

		$data = array(
			"name" => $form->getValue("name"),
			"description" => $form->getValue("description"),
			"type" => $form->getValue("type"),
			"showOnPosts" => (bool)$form->getValue("showOnPosts"),
			"hideFromGuests" => (bool)$form->getValue("hideFromGuests")
		);

		$model = $this->profileFieldModel();
		$model->updateById($field["fieldId"], $data);

		// If there were errors, pass them on to the form.
		if ($model->errorCount()) $form->errors($model->errors());

		// Otherwise, redirect back to the fields page.
		else $this->redirect(URL("admin/profiles"));
	}

	$this->data("form", $form);
	$this->data("field", $field);
	$this->render(ET::$plugins["Profiles"]->getView("admin/editField"));
}


public function create()
{
	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/profiles/create");

	// Was the cancel button pressed?
	if ($form->isPostBack("cancel")) $this->redirect(URL("admin/profiles"));

	// Was the save button pressed?
	if ($form->validPostBack("save")) {

		$model = $this->profileFieldModel();

		$data = array(
			"name" => $form->getValue("name"),
			"description" => $form->getValue("description"),
			"type" => $form->getValue("type"),
			"showOnPosts" => (bool)$form->getValue("showOnPosts"),
			"hideFromGuests" => (bool)$form->getValue("hideFromGuests"),
			"position" => $model->count()
		);

		$model->create($data);

		// If there were errors, pass them on to the form.
		if ($model->errorCount()) $form->errors($model->errors());

		// Otherwise, redirect back to the fields page.
		else $this->redirect(URL("admin/profiles"));
	}

	$this->data("form", $form);
	$this->data("field", null);
	$this->render(ET::$plugins["Profiles"]->getView("admin/editField"));
}


public function delete($fieldId = "")
{
	if (!$this->validateToken()) return;

	// Get this field's details. If it doesn't exist, show an error.
	if (!($field = $this->profileFieldModel()->getById((int)$fieldId))) {
		$this->render404();
		return;
	}

	$this->profileFieldModel()->deleteById($field["fieldId"]);

	$this->redirect(URL("admin/profiles"));
}

public function reorder()
{
	if (!$this->validateToken()) return;

	$ids = (array)R("ids");

	for ($i = 0; $i < count($ids); $i++) {
		$this->profileFieldModel()->updateById($ids[$i], array("position" => $i));
	}
}

}
