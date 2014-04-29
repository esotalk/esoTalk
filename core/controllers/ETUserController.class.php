<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The user controller handles session/user-altering actions such as logging in and out, signing up, and
 * resetting a password.
 *
 * @package esoTalk
 */
class ETUserController extends ETController {


/**
 * A message to display on the login form.
 * This is useful to set in the ETController::render404 method where we create a user controller
 * in order to display a login form without redirecting.
 * @var string
 */
public $loginMessage;


/**
 * There's no index method for this controller, so redirect back to the index.
 *
 * @return void
 */
public function action_index()
{
	$this->redirect(URL(""));
}


/**
 * Show the login sheet and handle input from the login form.
 *
 * @return void
 */
public function action_login()
{
	// If we're already logged in, redirect to the forum index.
	if (ET::$session->user) $this->redirect(URL(""));

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/login");
	$form->addHidden("return", R("return"));

	// If the cancel button was pressed, return to where the user was before.
	if ($form->isPostBack("cancel")) $this->redirect(URL(R("return")));

	// If the login form was submitted, attempt to log in.
	if ($form->validPostBack("username")) {

		// If the login was successful, redirect or set a json flag, depending on the response type.
		if (ET::$session->login($form->getValue("username"), $form->getValue("password"), $form->getValue("remember")))
			$this->redirect(URL(R("return")));

		// Otherwise, get the errors that occurred and pass them to the form.
		else {
			$errors = ET::$session->errors();
			if (in_array("emailNotYetConfirmed", $errors)) {
				$this->renderMessage("Error", sprintf(T("message.emailNotYetConfirmed"), URL("user/sendConfirmation/".$form->getValue("username"))));
				return;
			}
			if (in_array("accountNotYetApproved", $errors)) {
				$this->renderMessage("Error", T("message.accountNotYetApproved"));
				return;
			}
			$form->errors($errors);
		}
	}

	$this->data("form", $form);
	$this->data("message", $this->loginMessage);
	$this->render("user/login");
}


/**
 * Log the user out and redirect.
 *
 * @return void
 */
public function action_logout()
{
	ET::$session->remove("messages");
	ET::$session->logout();

	$this->redirect(URL(R("return")));
}


/**
 * Show the sign up sheet and handle input from its form.
 *
 * @return void
 */
public function action_join()
{
	// If we're already logged in, get out of here.
	if (ET::$session->user) $this->redirect(URL(""));

	// If registration is closed, show a message.
	if (!C("esoTalk.registration.open")) {
		$this->renderMessage(T("Registration Closed"), T("message.registrationClosed"));
		return;
	}

	// Set the title and make sure this page isn't indexed.
	$this->title = T("Sign Up");
	$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/join");

	if ($form->isPostBack("cancel")) $this->redirect(URL(R("return")));

	// If the form has been submitted, validate it and add the member into the database.
	if ($form->validPostBack("submit")) {

		// Make sure the passwords match. The model will do the rest of the validation.
		if ($form->getValue("password") != $form->getValue("confirm"))
			$form->error("confirm", T("message.passwordsDontMatch"));

		if (!$form->errorCount()) {

			$data = array(
				"username" => $form->getValue("username"),
				"email" => $form->getValue("email"),
				"password" => $form->getValue("password"),
				"account" => ACCOUNT_MEMBER
			);

			if (!C("esoTalk.registration.requireConfirmation")) $data["confirmed"] = true;
			else $data["resetPassword"] = md5(uniqid(rand()));

			// Create the member.
			$model = ET::memberModel();
			$memberId = $model->create($data);

			// If there were validation errors, pass them to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			else {

				// If we require the user to confirm their email, send them an email and show a message.
				if (C("esoTalk.registration.requireConfirmation") == "email") {
					$this->sendConfirmationEmail($data["email"], $data["username"], $memberId.$data["resetPassword"]);
					$this->renderMessage(T("Success!"), T("message.confirmEmail"));
				}

				// If we require the user account to be approved by an administrator, show a message.
				elseif (C("esoTalk.registration.requireConfirmation") == "approval") {
					$admin = ET::memberModel()->getById(C("esoTalk.rootAdmin"));
					ET::activityModel()->create("unapproved", $admin, null, array("username" => $data["username"]));
					$this->renderMessage(T("Success!"), T("message.waitForApproval"));
				}

				else {
					ET::$session->login($form->getValue("username"), $form->getValue("password"));
					$this->redirect(URL(""));
				}

				return;

			}

		}

	}

	$this->data("form", $form);
	$this->render("user/join");
}


/**
 * Send an email to a member containing a link which will confirm their email address.
 *
 * @param string $email The email of the member.
 * @param string $username The username of the member.
 * @param string $hash The hash stored in the member's resetPassword field, prefixed with the member's ID.
 * @return void
 */
public function sendConfirmationEmail($email, $username, $hash)
{
	sendEmail($email,
		sprintf(T("email.confirmEmail.subject"), $username),
		sprintf(T("email.header"), $username).sprintf(T("email.confirmEmail.body"), C("esoTalk.forumTitle"), URL("user/confirm/".$hash, true))
	);
}


/**
 * Confirm a member's email address with the provided hash.
 *
 * @param string $hash The hash stored in the member's resetPassword field, prefixed with the member's ID.
 * @return void
 */
public function action_confirm($hash = "")
{
	// If email confirmation is not necessary, get out of here.
	if (C("esoTalk.registration.requireConfirmation") != "email") return;

	// Split the hash into the member ID and hash.
	$memberId = (int)substr($hash, 0, strlen($hash) - 32);
	$hash = substr($hash, -32);

	// See if there is an unconfirmed user with this ID and password hash. If there is, confirm them and log them in.
	$result = ET::SQL()
		->select("1")
		->from("member")
		->where("memberId", $memberId)
		->where("resetPassword", $hash)
		->where("confirmed=0")
		->exec();
	if ($result->numRows()) {

		// Mark the member as confirmed.
		ET::memberModel()->updateById($memberId, array(
			"resetPassword" => null,
			"confirmed" => true
		));

		// Log them in and show a message.
		ET::$session->loginWithMemberId($memberId);
		$this->message(T("message.emailConfirmed"), "success");
	}

	// Redirect to the forum index.
	$this->redirect(URL(""));
}


/**
 * Resend an email confirmation email.
 *
 * @param string $username The username of the member to resend to.
 * @return void
 */
public function action_sendConfirmation($username = "")
{
	// If email confirmation is not necessary, get out of here.
	if (C("esoTalk.registration.requireConfirmation") != "email") return;

	// Get the requested member.
	$member = reset(ET::memberModel()->get(array("m.username" => $username, "confirmedEmail" => false)));
	if ($member) {
		$this->sendConfirmationEmail($member["email"], $member["username"], $member["memberId"].$member["resetPassword"]);
		$this->renderMessage(T("Success!"), T("message.confirmEmail"));
	}
	else $this->redirect(URL(""));
}


/**
 * Show the forgot password sheet, allowing a member to be sent an email containing a link to reset their
 * password.
 *
 * @return void
 */
public function action_forgot()
{
	// If the user is logged in, kick them out.
	if (ET::$session->user) $this->redirect(URL(""));

	// Set the title and make sure the page doesn't get indexed.
	$this->title = T("Forgot Password");
	$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/forgot");

	// If the cancel button was pressed, return to where the user was before.
	if ($form->isPostBack("cancel")) redirect(URL(R("return")));

	// If they've submitted their email to get a password reset link, email one to them!
	if ($form->validPostBack("submit")) {

		// Find the member with this email.
		$member = reset(ET::memberModel()->get(array("email" => $form->getValue("email"))));
		if (!$member)
			$form->error("email", T("message.emailDoesntExist"));

		else {

			// Update their record in the database with a special password reset hash.
			$hash = md5(uniqid(rand()));
			ET::memberModel()->updateById($member["memberId"], array("resetPassword" => $hash));

			// Send them email containing the link, and redirect to the home page.
			sendEmail($member["email"],
				sprintf(T("email.forgotPassword.subject"), $member["username"]),
				sprintf(T("email.header"), $member["username"]).sprintf(T("email.forgotPassword.body"), C("esoTalk.forumTitle"), URL("user/reset/".$member["memberId"].$hash, true))
			);
			$this->renderMessage(T("Success!"), T("message.passwordEmailSent"));
			return;

		}

	}

	$this->data("form", $form);
	$this->render("user/forgot");
}


/**
 * Show a form allowing the user to reset their password, following on from a link sent to them by the forgot
 * password process.
 *
 * @param string $hashString The hash stored in the member's resetPassword field, prefixed by their ID.
 * @return void
 */
public function action_reset($hashString = "")
{
	if (empty($hashString)) return;

	// Split the hash into the member ID and hash.
	$memberId = (int)substr($hashString, 0, strlen($hashString) - 32);
	$hash = substr($hashString, -32);

	// Find the member with this password reset token. If it's an invalid token, take them back to the email form.
	$member = reset(ET::memberModel()->get(array("m.memberId" => $memberId, "resetPassword" => $hash)));
	if (!$member) return;

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/reset/$hashString");

	// If the change password form has been submitted...
	if ($form->validPostBack("submit")) {

		// Make sure the passwords match. The model will do the rest of the validation.
		if ($form->getValue("password") != $form->getValue("confirm"))
			$form->error("confirm", T("message.passwordsDontMatch"));

		if (!$form->errorCount()) {

			$model = ET::memberModel();
			$model->updateById($memberId, array(
				"password" => $form->getValue("password"),
				"resetPassword" => null
			));

			// If there were validation errors, pass them to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			else {
				$this->message(T("message.passwordChanged"));
				$this->redirect(URL(""));
			}

		}

	}

	$this->data("form", $form);
	$this->render("user/setPassword");
}

}
