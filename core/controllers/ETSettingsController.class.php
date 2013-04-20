<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The settings controller handles a user's settings page and all the separate panes that are on it.
 *
 * @package esoTalk
 */
class ETSettingsController extends ETController {


/**
 * Initialize the settings controller; redirect if the user isn't logged in.
 *
 * @return void
 */
public function init()
{
	parent::init();
	if (!ET::$session->userId) $this->redirect(URL(""));
}


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
 * View the settings page. Default to the general pane.
 *
 * @return void
 */
public function index()
{
	$this->general();
}


/**
 * Set up data and menus that are needed to render the member profile view.
 *
 * @param string $pane The name of the active pane.
 * @return array The member details.
 */
protected function profile($pane = "")
{
	// Set the page title.
	$this->title = T("Settings");

	// Make a list of default member panes, and highlight the currently active one.
	$panes = ETFactory::make("menu");
	$panes->add("general", "<a href='".URL("settings/general")."'>".T("Settings")."</a>");
	$panes->add("password", "<a href='".URL("settings/password")."'>".T("Change Password or Email")."</a>");
	$panes->add("notifications", "<a href='".URL("settings/notifications")."'>".T("Notifications")."</a>");
	$panes->highlight($pane);

	// Set the member to the current user.
	$member = ET::$session->user;

	// Set up the controls and actions menus (although they will mostly be empty.)
	$controls = ETFactory::make("menu");
	$actions = ETFactory::make("menu");

	// Add a link to go back to the user's member profile.
	$actions->add("viewProfile", "<a href='".URL("member/me")."'>".T("View your profile")."</a>");

	$this->trigger("init", array($panes, $controls, $actions));

	// Pass along these menus to the view.
	$this->data("member", $member);
	$this->data("panes", $panes);
	$this->data("controls", $controls);
	$this->data("actions", $actions);

	return $member;
}



/**
 * Show the settings page with the general settings pane.
 *
 * @return void
 */
public function general()
{
	$member = $this->profile("general");

	// Construct the general settings form.
	$form = ETFactory::make("form");

	// Add the avatar section and field to the form.
	$form->addSection("avatar", T("Avatar"));
	$form->addField("avatar", "avatar", array($this, "fieldAvatar"), array($this, "saveAvatar"));

	// If there's more than 1 language installed, add the language section and field to the form.
	if (count(ET::getLanguages()) > 1) {
		$form->addSection("language", T("Forum language"));

		$form->setValue("language", ET::$session->preference("language"));
		$form->addField("language", "language", array($this, "fieldLanguage"), array($this, "saveLanguage"));
	}

	$form->addSection("notifications", T("Notifications"));

	// Add the "email me when I'm added to a private conversation" field.
	$form->setValue("privateAdd", ET::$session->preference("email.privateAdd"));
	$form->addField("notifications", "privateAdd", array($this, "fieldEmailPrivateAdd"), array($this, "saveEmailPreference"));

	// Add the "email me when someone replies to a conversation I have starred" field.
	$form->setValue("post", ET::$session->preference("email.post"));
	$form->addField("notifications", "post", array($this, "fieldEmailReplyToStarred"), array($this, "saveEmailPreference"));

	// Add the "email me when mentions me in a post" field.
	$form->setValue("mention", ET::$session->preference("email.mention"));
	$form->addField("notifications", "mention", array($this, "fieldEmailMention"), array($this, "saveEmailPreference"));

	// Add the "automatically star conversations I reply to" field.
	$form->setValue("starOnReply", ET::$session->preference("starOnReply"));
	$form->addField("notifications", "starOnReply", array($this, "fieldStarOnReply"), array($this, "saveBoolPreference"));

	// Add the "automatically star private conversations that I'm added to" field.
	$form->setValue("starPrivate", ET::$session->preference("starPrivate"));
	$form->addField("notifications", "starPrivate", array($this, "fieldStarPrivate"), array($this, "saveBoolPreference"));

	$form->addSection("privacy", T("Privacy"));

	// Add the "Don't allow other users to see when I am online" field.
	$form->setValue("hideOnline", ET::$session->preference("hideOnline"));
	$form->addField("privacy", "hideOnline", array($this, "fieldHideOnline"), array($this, "saveBoolPreference"));

	$this->trigger("initGeneral", array($form));

	// If the save button was clicked...
	if ($form->validPostBack("save")) {

		// Create an array of preferences to write to the database and run the form field callbacks on it.
		$preferences = array();
		$form->runFieldCallbacks($preferences);

		// If no errors occurred, we can write the preferences to the database.
		if (!$form->errorCount()) {

			if (count($preferences)) ET::$session->setPreferences($preferences);

			$this->message(T("message.changesSaved"), "success");
			$this->redirect(URL("settings/general"));

		}
	}

	// If the "remove avatar" button was clicked...
	elseif ($form->validPostBack("removeAvatar")) {

		// Delete the avatar file and set the member's avatarFormat to null.
		@unlink(PATH_UPLOADS."/avatars/".$member["memberId"].".".$member["avatarFormat"]);
		ET::memberModel()->updateById($member["memberId"], array("avatarFormat" => null));

		$this->message(T("message.changesSaved"), "success");
		$this->redirect(URL("settings/general"));

	}

	$this->data("form", $form);
	$this->renderProfile("settings/general");
}


/**
 * Return the HTML to render the avatar field in the general settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldAvatar($form)
{
	return "<div class='avatarChooser'>".
		avatar(ET::$session->user).
		$form->input("avatar", "file").
		"<small>".sprintf(T("Maximum size of %s. %s."), (ET::uploader()->maxUploadSize() / (1024*1024))." MB", "JPG, GIF, PNG")."</small>".
		(ET::$session->user["avatarFormat"] ? $form->button("removeAvatar", T("Remove avatar")) : "").
		"</div>";
}


/**
 * Return the HTML to render the language field in the general settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldLanguage($form)
{
	$options = array();
	foreach (ET::getLanguages() as $language) $options[$language] = ET::$languageInfo[$language]["name"];
	return $form->select("language", $options);
}


/**
 * Save the contents of the language field when the general settings form is submitted.
 *
 * @param string $key The name of the field that was submitted.
 * @param ETForm $form The form object.
 * @param array $preferences An array of preferences to write to the database.
 * @return string
 */
public function saveLanguage($key, $form, &$preferences)
{
	$language = $form->getValue($key);
	if (!in_array($language, ET::getLanguages()) or $language == C("esoTalk.language")) $language = null;
	$preferences["language"] = $language;
}


/**
 * Return the HTML to render the "email me when I'm addded to a private conversation" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldEmailPrivateAdd($form)
{
	return "<label class='checkbox'>".$form->checkbox("privateAdd")." <span class='label label-private'>".T("label.private")."</span> ".T("Email me when I'm added to a private conversation")."</label>";
}


/**
 * Return the HTML to render the "email me when someone posts in a conversation I have starred" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldEmailReplyToStarred($form)
{
	return "<label class='checkbox'>".$form->checkbox("post")." <span class='star starOn'>*</span> ".T("Email me when someone posts in a conversation I have followed")."</label>";
}


/**
 * Return the HTML to render the "email me when someone mentions me in a post" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldEmailMention($form)
{
	return "<label class='checkbox'>".$form->checkbox("mention")." ".T("Email me when someone mentions me in a post")."</label>";
}


/**
 * Return the HTML to render the "automatically star conversations that I reply to" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldStarOnReply($form)
{
	return "<label class='checkbox'>".$form->checkbox("starOnReply")." ".T("Automatically follow conversations that I reply to")."</label>";
}


/**
 * Return the HTML to render the "automatically star private conversations that I'm added to'" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldStarPrivate($form)
{
	return "<label class='checkbox'>".$form->checkbox("starPrivate")." ".T("Automatically follow private conversations that I'm added to")."</label>";
}


/**
 * Return the HTML to render the "don't allow other users to see when I am online" field in the general
 * settings form.
 *
 * @param ETForm $form The form object.
 * @return string
 */
public function fieldHideOnline($form)
{
	return "<label class='checkbox'>".$form->checkbox("hideOnline")." ".T("Don't allow other users to see when I am online")."</label>";
}

/**
 * Save the contents of an "email me when ..." field when the general settings form is submitted.
 *
 * @param string $key The name of the field that was submitted.
 * @param ETForm $form The form object.
 * @param array $preferences An array of preferences to write to the database.
 * @return string
 */
public function saveEmailPreference($key, $form, &$preferences)
{
	$preferences["email.".$key] = (bool)$form->getValue($key);
}


/**
 * Save the contents of a preference when the general settings form is submitted.
 *
 * @param string $key The name of the field that was submitted.
 * @param ETForm $form The form object.
 * @param array $preferences An array of preferences to write to the database.
 * @return string
 */
public function savePreference($key, $form, &$preferences)
{
	$preferences[$key] = $form->getValue($key);
}


/**
 * Save the contents of a simple checkbox field when the general settings form is submitted.
 *
 * @param string $key The name of the field that was submitted.
 * @param ETForm $form The form object.
 * @param array $preferences An array of preferences to write to the database.
 * @return string
 */
public function saveBoolPreference($key, $form, &$preferences)
{
	$preferences[$key] = (bool)$form->getValue($key);
}


/**
 * Save the contents of the avatar field when the general settings form is submitted.
 *
 * @param string $key The name of the field that was submitted.
 * @param ETForm $form The form object.
 * @param array $preferences An array of preferences to write to the database.
 * @return string
 */
public function saveAvatar($key, $form, &$preferences)
{
	if (empty($_FILES[$key]["tmp_name"])) return;

	$uploader = ET::uploader();

	try {

		// Validate and get the uploaded file from this field.
		$file = $uploader->getUploadedFile($key);

		// Save it as an image, cropping it to the configured avatar size.
		$avatar = $uploader->saveAsImage($file, PATH_UPLOADS."/avatars/".ET::$session->userId, C("esoTalk.avatars.width"), C("esoTalk.avatars.height"), "crop");

		// Update the member's avatarFormat field to the avatar file's extension.
		ET::memberModel()->updateById(ET::$session->userId, array("avatarFormat" => pathinfo($avatar, PATHINFO_EXTENSION)));

	} catch (Exception $e) {

		// If something went wrong up there, add the error message to the form.
		$form->error($key, $e->getMessage());

	}
}


/**
 * Show the settings page with the notifications pane.
 *
 * @param $popup bool Whether or not we are getting the contents of the notifications popup.
 * @return void
 */
public function notifications($popup = false)
{
	$member = $this->profile("notifications");

	// If we're getting the popup, we only want 5 notifications. Otherwise, 20.
	$limit = $popup ? 5 : 20;
	$this->data("showViewAll", $popup);

	// Get the notifications.
	$this->data("notifications", ET::activityModel()->getNotifications($limit));

	// Mark all notifications as read.
	ET::activityModel()->markNotificationsAsRead();

	$this->renderProfile("settings/notifications");
}


/**
 *
 */
public function notificationCheck()
{
	$this->responseType = RESPONSE_TYPE_AJAX;

	$notifications = ET::activityModel()->getNotifications(-1);

	$this->json("count", count($notifications));
	$this->notificationMessages($notifications);

	$this->render();
}


/**
 * Show the settings page with the change password or email pane.
 *
 * @return void
 */
public function password()
{
	$member = $this->profile("password");

	// Construct the form.
	$form = ETFactory::make("form");
	$form->action = URL("settings/password");

	// If the form was submitted...
	if ($form->validPostBack("save")) {

		$update = array();

		// Are we setting a new password?
		if ($password = $form->getValue("password")) {

			// Do the passwords entered match?
			if ($password != $form->getValue("confirm"))
				$form->error("confirm", T("message.passwordsDontMatch"));

			// The password stuff is good. Add the new password to be updated.
			else $update["password"] = $password;

		}

		// Are we setting a new email?
		if ($email = $form->getValue("email"))
			$update["email"] = $email;

		// Did they enter the correct "current password"?
		if (!ET::memberModel()->checkPassword($form->getValue("currentPassword"), ET::$session->user["password"]))
			$form->error("currentPassword", T("message.incorrectPassword"));

		// If no preliminary errors occurred, and we have stuff to update, we can go ahead and call the model.
		if (!$form->errorCount() and count($update)) {

			// Update the stuff we need to with the model.
			$model = ET::memberModel();
			$model->updateById(ET::$session->userId, $update);

			// If the model encountered errors, pass them along to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			// Otherwise, show a message and redirect.
			else {
				$this->message(T("message.changesSaved"), "success");
				$this->redirect(URL("settings"));
			}

		}

	}

	$this->data("form", $form);
	$this->renderProfile("settings/password");
}

}