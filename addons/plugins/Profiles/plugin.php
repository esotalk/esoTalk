<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Profiles"] = array(
	"name" => "Profiles",
	"description" => "Allows custom fields to be added to user profiles.",
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Profiles extends ETPlugin {

	public function setup($oldVersion = "")
	{
		// For the Profiles plugin, we need two tables.
		// The first, profile_field, stores information about the custom profile fields that
		// the administrator has defined.
		$structure = ET::$database->structure();
		$structure->table("profile_field")
			->column("fieldId", "int(11) unsigned", false)
			->column("name", "varchar(31)", false)
			->column("description", "varchar(255)")
			->column("type", "enum('text','textarea','select')", "text")
			->column("showOnPosts", "tinyint(1)", 0)
			->column("hideFromGuests", "tinyint(1)", 0)
			->column("position", "int(11)", 0)
			->key("fieldId", "primary")
			->exec(false);

		// The second, profile_data, stores the actual values of these fields that users
		// have entered in their profiles.
		$structure
			->table("profile_data")
			->column("memberId", "int(11) unsigned", false)
			->column("fieldId", "int(11) unsigned", false)
			->column("data", "text")
			->key(array("memberId", "fieldId"), "primary")
			->exec(false);

		// If this is the first installation of the Profiles plugin (e.g. on a fresh
		// esoTalk installation,) set up some default fields for the administrator.
		if (!$oldVersion) $this->createDefaultFields();

		// Version 1.0.0g4 of this plugin changed the way that profile data is stored
		// (previously it was just stored in the user preferences blob.) If we're
		// upgrading from an old version, we'll need to convert to the new format.
		elseif (version_compare($oldVersion, "1.0.0g4", "<")) {

			$this->createDefaultFields();
			$model = ET::getInstance("profileFieldModel");

			$result = ET::SQL()->select("memberId, preferences")->from("member")->exec();
			while ($row = $result->nextRow()) {
				ET::memberModel()->expand($row);
				if (!empty($row["preferences"]["about"])) $model->setData($row["memberId"], 1, $row["preferences"]["about"]);
				if (!empty($row["preferences"]["location"])) $model->setData($row["memberId"], 2, $row["preferences"]["location"]);
			}

		}

		return true;
	}

	protected function createDefaultFields()
	{
		$model = ET::getInstance("profileFieldModel");
		$model->create(array(
			"fieldId"     => 1,
			"name"        => "About",
			"description" => "Write something about yourself.",
			"type"        => "textarea"
		));
		$model->create(array(
			"fieldId"     => 2,
			"name"        => "Location",
			"type"        => "text",
			"showOnPosts" => true
		));
	}

	public function __construct($rootDirectory)
	{
		parent::__construct($rootDirectory);

		// Register the profile_field model which provides convenient methods to
		// manage profile data.
		ETFactory::register("profileFieldModel", "ProfileFieldModel", dirname(__FILE__)."/ProfileFieldModel.class.php");

		// Register the profiles admin controller which provides an interface for
		// administrators to manage custom profile fields.
		ETFactory::registerAdminController("profiles", "ProfilesAdminController", dirname(__FILE__)."/ProfilesAdminController.class.php");
	}

	// When initializing any admin controller, add a link to the Profiles admin page.
	public function handler_initAdmin($sender, $menu)
	{
		$menu->add("profiles", "<a href='".URL("admin/profiles")."'><i class='icon-smile'></i> ".T("Profiles")."</a>");
	}

	//
	public function handler_memberController_initProfile($sender, $member, $panes, $controls, $actions)
	{
		$panes->add("about", "<a href='".URL(memberURL($member["memberId"], $member["username"], "about"))."'>".T("About")."</a>", 0);
	}

	public function memberController_index($sender, $member = "")
	{
		$this->memberController_about($sender, $member);
	}

	public function memberController_about($sender, $member = "")
	{
		if (!($member = $sender->profile($member, "about"))) return;

		$model = ET::getInstance("profileFieldModel");
		$fields = $model->getData($member["memberId"]);

		foreach ($fields as $k => &$field) {
			if ($field["hideFromGuests"] and !ET::$session->user) unset($fields[$k]);

			switch ($field["type"]) {

				case "textarea":
					$field["data"] = ET::formatter()->init($field["data"])->format()->get();
					break;

				default:
					$field["data"] = sanitizeHTML($field["data"]);
			}
		}

		$sender->data("fields", $fields);

		$sender->renderProfile($this->getView("about"));
	}

	public function handler_postModel_getPostsAfter($sender, &$posts)
	{
		$postsById = array();
		foreach ($posts as &$post) {
			$postsById[$post["postId"]] = &$post;
			$post["fields"] = array();
		}

		if (!count($postsById)) return;

		$result = ET::SQL()
			->select("p.postId, f.fieldId, f.name, d.data")
			->from("post p")
			->from("profile_data d", "d.memberId=p.memberId", "left")
			->from("profile_field f", "d.fieldId=f.fieldId", "left")
			->where("p.postId IN (:ids)")
			->where("f.showOnPosts")
			->orderBy("f.position ASC")
			->bind(":ids", array_keys($postsById))
			->exec();

		while ($row = $result->nextRow()) {
			$postsById[$row["postId"]]["fields"][$row["fieldId"]] = array("name" => $row["name"], "data" => $row["data"]);
		}
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		if ($post["deleteMemberId"]) return;

		foreach ($post["fields"] as $fieldId => $field) {

			if ($field["hideFromGuests"] and !ET::$session->user) continue;

			if (strlen($field["data"]) > 30) $field["data"] = substr($field["data"], 0, 30)."...";

			$formatted["info"][] = "<span class='profile-".$fieldId."'>".sanitizeHTML($field["data"])."</span>";

		}
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		$model = ET::getInstance("profileFieldModel");
		$fields = $model->getData(ET::$session->userId);

		foreach ($fields as $field) {
			$key = "profile_".$field["fieldId"];
			$form->addSection($key, $field["name"]);
			$form->setValue($key, $field["data"]);
			$form->addField($key, $key, array($this, "field", $field), array($this, "saveField"));
		}
	}

	public function saveField($form, $key, &$preferences)
	{
		$model = ET::getInstance("profileFieldModel");
		$model->setData(ET::$session->userId, substr($key, 8), $form->getValue($key));
	}

	public function field($form, $field)
	{
		$key = "profile_".$field["fieldId"];
		switch ($field["type"]) {
			case "textarea":
				$input = $form->input($key, "textarea", array("rows" => 3, "style" => "width:500px"));
				break;
			default:
				$input = $form->input($key, "text");
		}
		return $input.($field["description"] ? "<br><small>".$field["description"]."</small>" : "");
	}

}
