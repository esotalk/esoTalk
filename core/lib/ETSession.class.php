<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The Session model represents the current session and the current user. It provides functions for manipluating
 * and managing the session and user, such as storing data, logging in and out, and validating tokens.
 *
 * @package esoTalk
 */
class ETSession extends ETModel {


/**
 * An array of the current user's details, or null if they're not logged in.
 * @var array
 */
public $user;


/**
 * The current user's member ID, or null if they're not logged in.
 * @var int
 */
public $userId;


/**
 * The current valid token.
 * @var string
 */
public $token;


/**
 * The IP address of the current user.
 * @var string
 */
public $ip;


/**
 * Class constructor: starts the session and initializes class properties (ip, token, user, etc.)
 *
 * @return void
 */
public function __construct()
{
	// Start a session.
	session_name(C("esoTalk.cookie.name")."_session");
	session_start();
	if (empty($_SESSION["token"])) $this->regenerateToken();

	// Complicate session highjacking - check the current user agent against the one that initiated the session.
	if (md5($_SERVER["HTTP_USER_AGENT"]) != $_SESSION["userAgent"]) session_destroy();

	// Set the class properties to reference session variables.
	$this->token = &$_SESSION["token"];
	$this->ip = $_SERVER["REMOTE_ADDR"];
	$this->userId = &$_SESSION["userId"];

	// If a persistent login cookie is set, attempt to log in.
	if (!C("esoTalk.disablePersistenceCookies") and !$this->userId and ($cookie = $this->getCookie("persistent"))) {

		// Get the token and member ID from the cookie.
		$token = substr($cookie, -32);
		$memberId = (int)substr($cookie, 0, -32);

		// Find a user with this memberId and token.
		$member = ET::memberModel()->get(array(
			"m.memberId" => $memberId,
			"rememberToken" => $token
		));

		// If we found them, log them in.
		if ($member) {
			$this->loginWithMemberId($memberId);
		}
	}

	// If there's a user logged in, get their user data.
	if ($this->userId and C("esoTalk.installed")) $this->refreshUserData();
}


/**
 * Pulls fresh user data from the database into the $user property.
 *
 * @return void
 */
public function refreshUserData()
{
	if (!$this->userId) return;
	$this->user = ET::memberModel()->getById($this->userId);
}


/**
 * Get the value of a specific preference for the currently logged in user.
 *
 * @return mixed
 */
public function preference($key, $default = false)
{
	return isset($this->user["preferences"][$key]) ? $this->user["preferences"][$key] : $default;
}


/**
 * Set preferences for the current user.
 *
 * @param array $values An array of preferences to set.
 * @return void
 */
public function setPreferences($values)
{
	if (!$this->userId) return;
	$this->user["preferences"] = ET::memberModel()->setPreferences($this->user, $values);
}


/**
 * Set up the session to be logged in with the given member.
 *
 * @param array $member The details of the member to log in with.
 * @return bool true on success, false on error.
 */
protected function processLogin($member)
{
	// If registrations require confirmation but the user's account hasn't been confirmed, return a message.
	if (!$member["confirmed"] and ($type = C("esoTalk.registration.requireConfirmation"))) {
		if ($type == "email") $this->error("emailNotYetConfirmed");
		elseif ($type == "approval") $this->error("accountNotYetApproved");
		return false;
	}

	// Assign the user ID to a SESSION variable.
	$_SESSION["userId"] = $member["memberId"];
	$this->user = $member;

	// Regenerate the session ID and token to prevent session fixation.
	$this->regenerateToken();

	return true;
}


/**
 * Log in the member with the specified ID.
 *
 * @param int $memberId The member ID.
 * @return bool true on success, false on failure.
 */
public function loginWithMemberId($memberId)
{
	$member = ET::memberModel()->getById($memberId);
	return $this->processLogin($member);
}


/**
 * Log in the member with the specified username and password, and optionally set a persistent login cookie.
 *
 * @param string $username The username.
 * @param string $password The password.
 * @param bool $remember Whether or not to set a persistent login cookie.
 * @return bool true on success, false on failure.
 */
public function login($name, $password, $remember = false)
{
	$return = $this->trigger("login", array($name, $password, $remember));
	if (count($return)) return reset($return);

	// Get the member with this username or email.
	$sql = ET::SQL()
		->where("m.username=:username OR m.email=:email")
		->bind(":username", $name)
		->bind(":email", $name);
	$member = reset(ET::memberModel()->getWithSQL($sql));

	// Check that the password is correct.
	if (!$member or !ET::memberModel()->checkPassword($password, $member["password"])) {
		$this->error("password", "incorrectLogin");
		return false;
	}

	// Process the login.
	$return = $this->processLogin($member);

	// Set a persistent login "remember me" cookie?
	if (!C("esoTalk.disablePersistenceCookies") and $return === true and $remember) {
		$this->setRememberCookie($this->userId);
	}

	return $return;
}


/**
 * Get the rememberToken for a member. If none exists, a new one will be generated.
 *
 * @param int $memberId The ID of the member to get the rememberToken for.
 * @return string The rememberToken.
 */
protected function getRememberToken($memberId)
{
	$member = ET::memberModel()->getById($memberId);

	if (!empty($member["rememberToken"])) {
		$token = $member["rememberToken"];
	} else {
		$token = generateRandomString(32);
		ET::memberModel()->updateById($memberId, array("rememberToken" => $token));
	}

	return $token;
}


/**
 * Clear the rememberToken for a user, effectively invalidating all persistence cookies.
 *
 * @param int $memberId The ID of the member to clear the rememberToken for.
 * @return void
 */
protected function clearRememberToken($memberId)
{
	ET::memberModel()->updateById($memberId, array("rememberToken" => null));

	// Eat the persistent login cookie. OM NOM NOM
	if ($this->getCookie("persistent")) $this->setCookie("persistent", false, -1);
}


/**
 * Set a cookie with a standardized name prefix.
 *
 * @param string $name The name of the cookie.
 * @param string $value The value of the cookie.
 * @param int $expire The time before the cookie will expire.
 */
public function setCookie($name, $value, $expire = 0)
{
	return setcookie(C("esoTalk.cookie.name")."_".$name, $value, $expire, C("esoTalk.cookie.path", getWebPath('')), C("esoTalk.cookie.domain"), C("esoTalk.https"), true);
}


/**
 * Set a cookie to remember a user.
 *
 * @param int $userId The ID of the user to remember.
 */
public function setRememberCookie($userId)
{
	$token = $this->getRememberToken($userId);

	$this->setCookie("persistent", $userId.$token, time() + C("esoTalk.cookie.expire"));
}


/**
 * Get the value of a cookie set by $this->setCookie().
 *
 * @param string $name The name of the cookie.
 * @param string $default The value to return if the cookie is not set.
 * @return string
 */
public function getCookie($name, $default = null)
{
	$name = C("esoTalk.cookie.name")."_".$name;
	return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
}


/**
 * Log the current user out.
 *
 * @return void
 */
public function logout()
{
	// Clear the rememberToken for this user, effectively invalidating all persistence cookies.
	$this->clearRememberToken($_SESSION["userId"]);

	// Destroy session data and regenerate the unique token to prevent session fixation.
	unset($_SESSION["userId"]);
	$this->regenerateToken();

	$this->trigger("logout");
}


/**
 * Update the current session's local user data.
 *
 * @param string $key The key to set.
 * @param mixed $value The value to set.
 * @return void
 */
public function updateUser($key, $value)
{
	$this->user[$key] = $value;
}


/**
 * Check a token against the current valid token.
 *
 * @param string $token The token to check.
 * @return bool Whether or not the token is valid.
 */
public function validateToken($token)
{
	return $token == $this->token;
}


/**
 * Regenerate the session ID, token, and store the user's agent.
 *
 * @return void
 */
public function regenerateToken()
{
	session_regenerate_id(true);
	$_SESSION["token"] = substr(md5(uniqid(rand())), 0, 13);
	$_SESSION["userAgent"] = md5($_SERVER["HTTP_USER_AGENT"]);
}


/**
 * Push an item onto the top of the navigation breadcrumb stack.
 *
 * When adding an item to the navigation breadcrumb stack, we first go through all the items in the stack and
 * check if there's an item with the same ID. If it is found, we go back to that point in the breadcrumb,
 * discarding everything afterwards.
 *
 * @param string $id The navigation ID (a unique ID for this item in the breadcrumb.)
 * @param string $type The type of page this is (search/conversation/etc - will be used in the "back to [type]" text.)
 * @param string $url The URL to this page.
 * @return void
 */
public function pushNavigation($id, $type, $url)
{
	$navigation = $this->get("navigation");
	if (!is_array($navigation)) $navigation = array();

	// Look for an item with this $id that might already by in the navigation. If found, delete everything after it.
	foreach ($navigation as $k => $item) {
		if ($item["id"] == $id) {
			array_splice($navigation, $k);
			break;
		}
	}
	$navigation[] = array("id" => $id, "type" => $type, "url" => $url);

	$this->store("navigation", $navigation);
}


/**
 * Get the item that is on top of the navigation stack. The navigation ID of the current page will be used to
 * make sure the item returned isn't the item for the current page.
 *
 * @param string $currentId The unqiue navigation ID of the current page.
 * @return bool|array The navigation item, or false if there is none (if the current page is the top.)
 */
public function getNavigation($currentId)
{
	$navigation = $this->get("navigation");
	if (!empty($navigation)) {
		$return = end($navigation);
		if ($return["id"] == $currentId) $return = prev($navigation);
		return $return;
	}
	else return false;
}


/**
 * Return whether or not the current user is an administrator.
 *
 * @return bool
 */
public function isAdmin()
{
	return $this->user["account"] == ACCOUNT_ADMINISTRATOR or $this->userId == C("esoTalk.rootAdmin");
}


/**
 * Return whether or not the current user is suspended.
 *
 * @return bool
 */
public function isSuspended()
{
	return $this->user["account"] == ACCOUNT_SUSPENDED;
}


/**
 * Return whether or not the current user is flooding.
 *
 * @return bool
 */
public function isFlooding()
{
	// If there's no wait time between posting configured, they're not flooding.
	if (C("esoTalk.conversation.timeBetweenPosts") <= 0) return false;

	// Otherwise, make sure the time of their most recent conversation/post is more than the time limit ago.
	$time = time() - C("esoTalk.conversation.timeBetweenPosts");
	$recentConversation = (bool)ET::SQL()
		->select("MAX(startTime)>$time")
		->from("conversation")
		->where("startMemberId", $this->userId)
		->exec()
		->result();
	$recentPost = (bool)ET::SQL()
		->select("MAX(time)>$time")
		->from("post p")
		->where("memberId", $this->userId)
		->exec()
		->result();

	return $recentConversation or $recentPost;
}


/**
 * Get a list of group IDs which the current user is in.
 *
 * @return array
 */
public function getGroupIds()
{
	if ($this->user) return ET::groupModel()->getGroupIds($this->user["account"], array_keys($this->user["groups"]));
	else return ET::groupModel()->getGroupIds(false, false);
}


/**
 * Store a value in the session data store.
 *
 * @return void
 */
public function store($key, $value)
{
	$_SESSION[$key] = $value;
}


/**
 * Retrieve a value from the session data store.
 *
 * @return mixed
 */
public function get($key, $default = null)
{
	return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}


/**
 * Remove a value from the session data store.
 *
 * @return void
 */
public function remove($key)
{
	unset($_SESSION[$key]);
}

}
