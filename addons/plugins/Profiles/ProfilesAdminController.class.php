<?php
// Copyright 2014 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

class ProfilesAdminController extends ETAdminController {

	protected function model()
	{
		return ET::getInstance("profileFieldModel");
	}

	protected function plugin()
	{
		return ET::$plugins["Profiles"];
	}

	public function action_index()
	{
		$fields = $this->model()->get();

		$this->addCSSFile($this->plugin()->resource("admin.css"));

		$this->addJSFile("core/js/lib/jquery.ui.js");
		$this->addJSFile($this->plugin()->resource("admin.js"));
		$this->addJSLanguage("message.confirmDelete");

		$this->data("fields", $fields);
		$this->render($this->plugin()->view("admin/fields"));
	}

	public function action_edit($fieldId = "")
	{
		// Get this field's details. If it doesn't exist, show an error.
		if (!($field = $this->model()->getById((int)$fieldId))) {
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

			$model = $this->model();
			$model->updateById($field["fieldId"], $data);

			// If there were errors, pass them on to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			// Otherwise, redirect back to the fields page.
			else $this->redirect(URL("admin/profiles"));
		}

		$this->data("form", $form);
		$this->data("field", $field);
		$this->render($this->plugin()->view("admin/editField"));
	}


	public function action_create()
	{
		// Set up the form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/profiles/create");

		// Was the cancel button pressed?
		if ($form->isPostBack("cancel")) $this->redirect(URL("admin/profiles"));

		// Was the save button pressed?
		if ($form->validPostBack("save")) {

			$model = $this->model();

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
		$this->render($this->plugin()->view("admin/editField"));
	}


	public function action_delete($fieldId = "")
	{
		if (!$this->validateToken()) return;

		// Get this field's details. If it doesn't exist, show an error.
		if (!($field = $this->model()->getById((int)$fieldId))) {
			$this->render404();
			return;
		}

		$this->model()->deleteById($field["fieldId"]);

		$this->redirect(URL("admin/profiles"));
	}

	public function action_reorder()
	{
		if (!$this->validateToken()) return;

		$ids = (array)R("ids");

		for ($i = 0; $i < count($ids); $i++) {
			$this->model()->updateById($ids[$i], array("position" => $i));
		}
	}

}
