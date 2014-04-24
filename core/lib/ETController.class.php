<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * A class that defines a controller, and provides methods and properties to dispatch requests and render
 * the page. A controller takes user input from a request, handles it, and responds by rendering content/data.
 *
 * @package esoTalk
 */
class ETController extends ETPluggable {


/**
 * The master view that will be used to render the page. The master view is a "wrapper" that contains all
 * the common elements of a page (header, footer, etc.) and that will render actual view within it.
 * @var string
 */
public $masterView = "default.master";


/**
 * An array of data that will be passed to the view as a local variable when the page is rendered. This
 * should be used as the primary transport of data between a controller and a view.
 * @var array
 */
public $data = array();


/**
 * The title of the page. The default master view will use this in the <title> tag.
 * @var string
 */
public $title = "";


/**
 * An array of JavaScript files to be included in the head of the page.
 * @var array
 */
private $jsFiles = array("global" => array(), "local" => array());


/**
 * An array of CSS files to be included in the head of the page.
 * @var array
 */
private $cssFiles = array("global" => array(), "local" => array());


/**
 * An array of language definition codes to make accessible to JavaScript.
 * @var array
 */
private $jsLanguage = array();


/**
 * An array of data to make accessible to JavaScript.
 * @var array
 */
private $jsData = array();


/**
 * A string of HTML to append before the </head> tag. This could contain <meta> tags or other code.
 * @var string
 */
private $head = "";


/**
 * An array of ETMenu objects for common menus (such as "user", "main", and "meta".)
 * @var array
 */
public $menus = array();


/**
 * An array of data to output in a JSON response.
 * @var array
 */
public $json = array();


/**
 * The content type to use for the response.
 * @var string
 */
public $contentType = "text/html";


/**
 * The response type. This can be any of the RESPONSE_TYPE_* constants (see config.defaults.php.)
 * @var string
 */
public $responseType = RESPONSE_TYPE_DEFAULT;


/**
 * Class(es) to apply to the <body> tag.
 * @var string
 */
public $bodyClass = "";


/**
 * When $this->pushNavigation() is called, we store the navigation ID in here so that we can use it when we
 * later call ET::$session->getNavigation().
 * @var string
 */
protected $navigationId = false;


/**
 * The URL to the request for the current page.
 * @var string
 */
public $selfURL = "";


/**
 * The canonical URL for the current page. This should be set by a controller method.
 * @var string
 */
public $canonicalURL = "";


/**
 * An array of messages to show on the page.
 * @var string
 */
private $messages = array();


/**
 * Class constructor.
 *
 * @return void
 */
public function __construct()
{
	// Pull any messages stored into the session into the controller's $messages property.
	$messages = ET::$session->get("messages");
	if (is_array($messages)) $this->messages = $messages;

	if (ET::$session->errorCount()) $this->messages(ET::$session->errors(), "warning");
}


/**
 * Dispatch a request to $method, passing along $arguments.
 *
 * @param string $method The name of the controller method.
 * @param array $arguments An array of arguments to pass to the method.
 * @return void
 */
public function dispatch($method, $arguments)
{
	// Create an array of arguments where the first item is $this.
	$eventArguments = array_merge(array(&$this), $arguments);
	$eventName = $this->className."_".$method;

	// Trigger a "before" event for this method.
	ET::trigger($eventName."_before", $eventArguments);

	// Go through plugins and look for a handler for this controller/method.
	$called = false;
	foreach (ET::$plugins as $plugin) {
		$actionName = "action_".$eventName;
		if (method_exists($plugin, $actionName)) {
			call_user_func_array(array($plugin, $actionName), $eventArguments);
			$called = true;
			break;
		}
	}

	// If one wasn't found, call the method on $this.
	if (!$called) call_user_func_array(array($this, "action_".$method), $arguments);

	// Trigger an "after" event for this method.
	ET::trigger($eventName."_after", $eventArguments);
}


/**
 * Add a message to be displayed on the page. The messages will also be stored in the session so that if the
 * controller redirects instead of rendering, they will be displayed on the next response.
 *
 * @param string $message The message text.
 * @param mixed $options An array of options. Possible keys include:
 * 		id: a unique ID for the message. If specified, this message will overwrite any previous messages with
 * 			the same ID.
 * 		className: the CSS class to apply to the message.
 * 		callback: a JavaScript function to run when the message is dismissed.
 * 		If $options is a string, it will be used as the className.
 * @return void
 */
public function message($message, $options = "")
{
	if (!is_array($options)) $options = array("className" => $options);
	$options["message"] = $message;
	if (!empty($options["id"])) $this->messages[$options["id"]] = $options;
	else $this->messages[] = $options;
	ET::$session->store("messages", $this->messages);
}


/**
 * Add an array of messages to be displayed on the page. This is the same as looping through an array and
 * calling message() for each item.
 *
 * @param array $messages An array of messages. Any non-numeric keys will be used as the ID for their message.
 * @param mixed $options An array of options; see message() for a full description. These options will be used
 * 		for all of the messages.
 * @return void
 */
public function messages($messages, $options = "")
{
	if (!is_array($options)) $options = array("className" => $options);
	foreach ($messages as $id => $message) {
		$options["id"] = !is_numeric($id) ? $id : null;
		$this->message(T("message.$message", $message), $options);
	}
}


/**
 * Given an array of notifications, add messages to the controller to display the notifications in the
 * messages area.
 *
 * @param array $notifications An array of notifications, typically from ETActivityModel::getNotifications(-1).
 * @return void
 */
public function notificationMessages($notifications)
{
	foreach ($notifications as $notification) {

		// If we've already shown this notification as a message before, don't show it again.
		if ($notification["time"] <= ET::$session->preference("notificationCheckTime")) continue;

		$avatar = avatar(array(
			"memberId" => $notification["fromMemberId"],
			"avatarFormat" => $notification["avatarFormat"],
			"email" => $notification["email"]
		), "thumb");
		$this->message("<a href='".$notification["link"]."' class='messageLink'><span class='action'>".$avatar.$notification["body"]."</span></a>", "popup notificationMessage autoDismiss hasSprite");
	}

	// Update the user's "notificationCheckTime" preference so these notifications won't be shown again.
	ET::$session->setPreferences(array("notificationCheckTime" => time()));
}


/**
 * Common initialization for all controllers, called on every page load. This will add basic user links to
 * the "user" menu, and add core JS files and language definitions.
 *
 * If this is overridden, parent::init() should be called to maintain consistency between controllers.
 *
 * @return void
 */
public function init()
{
	// Check for updates to the esoTalk software, but only if we're the root admin and we haven't checked in
	// a while.
	if (ET::$session->userId == C("esoTalk.rootAdmin") and C("esoTalk.admin.lastUpdateCheckTime") + C("esoTalk.updateCheckInterval") < time())
		ET::upgradeModel()->checkForUpdates();

	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

		// If the user IS NOT logged in, add the 'login' and 'sign up' links to the bar.
		if (!ET::$session->user) {
			$this->addToMenu("user", "join", "<a href='".URL("user/join?return=".urlencode($this->selfURL))."' class='link-join'>".T("Sign Up")."</a>");
			$this->addToMenu("user", "login", "<a href='".URL("user/login?return=".urlencode($this->selfURL))."' class='link-login'>".T("Log In")."</a>");
		}

		// If the user IS logged in, we want to display their name and appropriate links.
		else {
			$this->addToMenu("user", "user", "<a href='".URL("member/me")."'>".avatar(ET::$session->user, "thumb").name(ET::$session->user["username"])."</a>");

			$this->addToMenu("user", "settings", "<a href='".URL("settings")."' class='link-settings'>".T("Settings")."</a>");

			if (ET::$session->isAdmin())
				$this->addToMenu("user", "administration", "<a href='".URL("admin")."' class='link-administration'>".T("Administration")."</a>");

                $this->addToMenu("user", "logout", "<a href='".URL("user/logout?return=".urlencode($this->selfURL))."' class='link-logout'>".T("Log Out")."</a>");
		}

		// Get the number of members currently online and add it as a statistic.
		if (C("esoTalk.members.visibleToGuests") or ET::$session->user) {
			$online = ET::SQL()
				->select("COUNT(*)")
				->from("member")
				->where("UNIX_TIMESTAMP()-:seconds<lastActionTime")
				->bind(":seconds", C("esoTalk.userOnlineExpire"))
				->exec()
				->result();
			$stat = Ts("statistic.online", "statistic.online.plural", number_format($online));
			$stat = "<a href='".URL("members/online")."' class='link-membersOnline'>$stat</a>";
			$this->addToMenu("statistics", "statistic-online", $stat);
		}

		$this->addToMenu("meta", "copyright", "<a href='http://esotalk.org/' target='_blank'>Powered by esoTalk".(ET::$session->isAdmin() ? " ".ESOTALK_VERSION : "")."</a>");

		// Set up some default JavaScript files and language definitions.
		$this->addJSFile("core/js/lib/jquery.js", true);
		$this->addJSFile("core/js/lib/jquery.misc.js", true);
		$this->addJSFile("core/js/lib/jquery.history.js", true);
		$this->addJSFile("core/js/lib/jquery.scrollTo.js", true);
		$this->addJSFile("core/js/global.js", true);
		$this->addJSLanguage("message.ajaxRequestPending", "message.ajaxDisconnected", "Loading...", "Notifications");
		$this->addJSVar("notificationCheckInterval", C("esoTalk.notificationCheckInterval"));

		// If config/custom.css contains something, add it to be included in the page.
		if (file_exists($file = PATH_CONFIG."/custom.css") and filesize($file) > 0) $this->addCSSFile("config/custom.css", true);

	}

	$this->trigger("init");
}


/**
 * Redirect to another location.
 *
 * If the response type is AJAX or JSON, this function will render the page with a "redirect" key set in the
 * response data. The esoTalk JavaScript will set window.location upon receiving this data.
 *
 * @param string $url The URL to redirect to.
 * @param int $code The HTTP response code to respond with. This will usually be either 302 (temporary) or
 * 		301 (permanent).
 */
public function redirect($url, $code = 302)
{
	if ($this->responseType === RESPONSE_TYPE_AJAX or $this->responseType === RESPONSE_TYPE_JSON or $this->responseType === RESPONSE_TYPE_VIEW) {
		if ($this->responseType === RESPONSE_TYPE_VIEW) $this->responseType = RESPONSE_TYPE_AJAX;
		$this->json("redirect", $url);
		$this->render();
		exit;
	}
	else redirect($url, $code);
}


/**
 * Push an item onto the top of the navigation (breadcrumb) stack.
 *
 * This is simply a layer on top of ETSession::pushNavigation() which stores the navigation ID. Later in the
 * controller's life, the navigation ID is used to create a "back" button with ETSession::getNavigation().
 *
 * @see ETSession::pushNavigation()
 * @param string $id The navigation ID.
 * @param string $type The type of page this is.
 * @param string $url The URL to this page.
 * @return void
 */
public function pushNavigation($id, $type, $url)
{
	$this->navigationId = $id;
	ET::$session->pushNavigation($id, $type, $url);
}


/**
 * Add a piece of data to be rendered in a JSON response.
 *
 * @param string $key The JSON key.
 * @param mixed $value The value.
 * @return void
 */
public function json($key, $value)
{
	$this->json[$key] = $value;
}


/**
 * Add a piece of data to be transported to the view when it is rendered.
 *
 * @param string $key The data key.
 * @param mixed $value The data value.
 * @return void
 */
public function data($key, $value)
{
	$this->data[$key] = $value;
}


/**
 * Render the specified view, in the format according to the controller's set response type.
 *
 * @param string $view The view to render. This can be left blank if we know the response type is one that
 * 		doesn't require a view, such as JSON or ATOM.
 * @return void
 */
public function render($view = "")
{
	$this->trigger("renderBefore");

	if ($this->responseType == RESPONSE_TYPE_DEFAULT and ET::$session->user) {

		// Fetch all unread notifications so we have a count for the notifications button.
		$notifications = ET::activityModel()->getNotifications(-1);
		$count = count($notifications);
		$this->addToMenu("user", "notifications", "<a href='".URL("settings/notifications")."' id='notifications' class='button popupButton ".($count ? "new" : "")."'><span>$count</span></a>");

		// Show messages with these notifications.
		$this->notificationMessages($notifications);

	}

	// Set up the master view, content type, and other stuff depending on the response type.
	switch ($this->responseType) {

		// For an ATOM response, set the master view and the content type.
		case RESPONSE_TYPE_ATOM:
			$this->masterView = "atom.master";
			$this->contentType = "application/atom+xml";
			break;

		// For an AJAX or JSON response, set the master view and the content type.
		// If it's an AJAX response, set one of the JSON parameters to the specified view's contents.
		case RESPONSE_TYPE_AJAX:
			if ($view) $this->json("view", $this->getViewContents($view, $this->data));

		case RESPONSE_TYPE_JSON:
			$this->masterView = "json.master";
			$this->contentType = "application/json";

	}

	// Set a content-type header.
	header("Content-type: ".$this->contentType."; charset=".T("charset", "utf-8"));

	// If we're just outputting the view on its own, do that now.
	if ($this->responseType === RESPONSE_TYPE_VIEW) {
		$this->renderView($view, $this->data);
	}

	// Otherwise, set up the master view and render it.
	else {

		// Make a new data array for the master view.
		$data = array();

		// For any master views but the JSON and ATOM ones, give the view some data that will be useful in
		// rendering a HTML page.
		if ($this->masterView != "json.master" and $this->masterView != "atom.master") {

			// Fetch the content of the view, passing the data collected in the controller.
			if ($view) $data["content"] = $this->getViewContents($view, $this->data);

			// Add the <head> contents and the page title.
			$data["head"] = $this->head();
			$data["pageTitle"] = ($this->title ? $this->title." - " : "").C("esoTalk.forumTitle");

			// Add the forum title, or logo if the forum has one.
			$logo = C("esoTalk.forumLogo");
			$title = C("esoTalk.forumTitle");
			if ($logo) $size = getimagesize($logo);
			$data["forumTitle"] = $logo ? "<img src='".getWebPath($logo)."' {$size[3]} alt='$title'/>" : $title;

			// Add the details for the "back" button.
			$data["backButton"] = ET::$session->getNavigation($this->navigationId);

			// Get common menu items.
			foreach ($this->menus as $menu => $items)
				$data[$menu."MenuItems"] = $items->getContents();

			// Add the body class.
			$data["bodyClass"] = $this->bodyClass;

			// Get messages.
			$data["messages"] = $this->getMessages();

		}

		$this->renderView($this->masterView, $data);

	}

	$this->trigger("renderAfter");
}


/**
 * Render a simple message sheet with an 'OK' button. This can be used to easily display, for example, a
 * "you do not have permission to be here" message.
 *
 * @param string $title The title to use in the message sheet.
 * @param string $message The message text.
 * @return void
 */
public function renderMessage($title, $message)
{
	// Add the title and message to be passed to the view.
	$this->data("title", $title);
	$this->data("message", $message);

	// If the response type is anything other than default, just make it an AJAX response and set a JSON
	// parameter so the esoTalk JavaScript knows to display a modal message sheet.
	if ($this->responseType !== RESPONSE_TYPE_DEFAULT) {
		$this->responseType = RESPONSE_TYPE_AJAX;
		$this->json("modalMessage", true);
	}

	$this->render("message");
}


/**
 * Render a "Page Not Found" message sheet, and send a 404 header with the response. This can be used to
 * easily display, for example, a "this conversation was not found" message.
 *
 * @param string $message The message text.
 * @return void
 */
public function render404($message = "", $showLogin = false)
{
	header("HTTP/1.1 404 Not Found");

	// If the user isn't logged in, we might want to show a login form to them.
	// To do this, we create an ETUserController instance, set a message to display on the login form,
	// and then run the "login" method.
	if (!ET::$session->user and $showLogin) {
		$_GET["return"] = $this->selfURL;
		$controller = ETFactory::make("userController");
		$controller->init();
		$controller->loginMessage = $message;
		$controller->dispatch("login", array());
	}

	// If they are logged in, however, we'll just show a page not found message.
	else {
		$this->renderMessage(T("Page Not Found"), $message);
	}
}


/**
 * Validate an input token. If it's invalid, show a "no permission" message.
 *
 * @param string $token The token to validate. If false, the token will automatically be taken from the
 * 		request input.
 * @return bool true if the token is valid, false if it isn't.
 */
public function validateToken($token = false)
{
	if ($token === false) $token = R("token");

	if (!ET::$session->validateToken($token)) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return false;
	}
	return true;
}


/**
 * Make sure that the user is logged in, or the specified configuration key is true. If not, redirect
 * to the login page.
 *
 * This is generally used to make sure the user is allowed to view the forum (i.e. they are logged in
 * or the forum is visible to guests.)
 *
 * @param string $key The configuration key which determines whether this page is visible to guests.
 * @return bool true if the user is allowed to view this page, false if they are not.
 */
public function allowed($key = "esoTalk.visibleToGuests")
{
	if (ET::$session->user or C($key)) return true;

	$url = ltrim($this->selfURL, "/");
	$this->redirect(URL("user/login".($url ? "?return=$url" : "")));
	return false;
}


/**
 * Renders a view, and captures and returns the output.
 *
 * @param string $view The name of the view to get.
 * @param array $data An array of data to pass to the view.
 * @return string The output of the view.
 */
public function getViewContents($view, $data = array())
{
	ob_start();
	$this->renderView($view, $data);
	$content = ob_get_clean();
	return $content;
}


/**
 * Renders a view.
 *
 * @param string $view The name of the view to render.
 * @param array $data An array of data to pass to the view.
 * @return void
 */
public function renderView($view, $data = array())
{
	ob_start();
	include $this->getViewPath($view);
	$content = ob_get_clean();

	$this->trigger("renderView", array($view, &$content, $data));

	echo $content;
}


/**
 * Gets the full filepath to the specified view.
 *
 * @param string $view The name of the view to get the filepath of.
 * @return string The filepath of the view.
 */
public function getViewPath($view)
{
	// If the view has a file extension, assume it contains the full file path and use it as is.
	if (pathinfo($view, PATHINFO_EXTENSION) == "php") return $view;

	// Check the skin to see if it contains this view.
	if (file_exists($skinView = ET::$skin->view($view))) return $skinView;

	// Check loaded plugins to see if one of them contains the view.
	foreach (ET::$plugins as $k => $v) {
		if (file_exists($pluginView = $v->view($view))) return $pluginView;
	}

	// Otherwise, just return the default view.
	return PATH_VIEWS."/$view.php";
}


/**
 * Get all of the controller's messages, and remove them from the session storage.
 *
 * @return array An array of the controller's messages.
 */
public function getMessages()
{
	ET::$session->remove("messages");

	return $this->messages;
}


/**
 * Set a language definition(s) to be accessible by JavaScript code on the page, as a property of the
 * esoTalk.language object.
 *
 * @param string $key,... Unlimited number of language definition keys to make accessible to JavaScript.
 * @return void
 */
public function addJSLanguage()
{
	$args = func_get_args();
	foreach ($args as $k) $this->jsLanguage[$k] = T($k);
}


/**
 * Set a variable that can be accessed by JavaScript code on the page, as a property of the esoTalk object.
 *
 * @param string $key The key to make $val accessible under.
 * @param mixed $val The value.
 * @return void
 */
public function addJSVar($key, $val)
{
	$this->jsData[$key] = $val;
}


/**
 * Add a JavaScript file to be included in the page header.
 *
 * @param string $file The relative or absolute path to the JavaScript file.
 * @param bool $global Whether or not this file is included globally (on every interface of the application.)
 * 		If true, we will aggregate this with other global files to get consistency, encouraging the browser
 * 		to cache the aggregated file.
 * @return void
 */
public function addJSFile($file, $global = false)
{
	if (strpos($file, "://") !== false) $key = "remote";
	$key = $global ? "global" : "local";
	$this->jsFiles[$key][] = $file;
}


/**
 * Add a CSS file, or files, to be included on the page.
 *
 * @param string $file The relative or absolute path to the CSS file.
 * @param bool $global Whether or not this file is included globally (on every interface of the application.)
 * 		If true, we will aggregate this with other global files to get consistency, encouraging the browser
 * 		to cache the aggregated file.
 * @return void
 */
public function addCSSFile($file, $global = false)
{
	if (strpos($file, "://") !== false) $key = "remote";
	else $key = $global ? "global" : "local";
	$this->cssFiles[$key][] = $file;
}


/**
 * Add a string of HTML to be outputted inside of the <head> tag. This can be used to add things to the page
 * like <meta> tags.
 *
 * @param string $string The string to add.
 * @return void
 */
public function addToHead($string)
{
	$this->head .= "\n$string";
}


/**
 * Take a collection of CSS or JS files and create and return the filename of an aggregation file which
 * contains all of their individual contents.
 *
 * @param array $files An array of files to aggregate.
 * @param string $type The type of files we are aggregating ("css" or "js").
 * @return array An array containing a single element, which is the path to the aggregation file.
 */
protected function aggregateFiles($files, $type)
{
	// Construct an array of filenames, and get the maximum last modifiction time of all the files.
	$filenames = array();
	$lastModTime = 0;
	foreach ($files as $filename) {
		$filenames[] = str_replace(".", "", pathinfo($filename, PATHINFO_FILENAME));
		$lastModTime = max($lastModTime, filemtime(PATH_ROOT."/".$filename));
	}

	// Construct a filename for the aggregation file based on the individual filenames.
	$file = PATH_ROOT."/cache/$type/".implode(",", $filenames).".$type";

	// If this file doesn't exist, or if it is out of date, generate and write it.
	if (!file_exists($file) or filemtime($file) < $lastModTime) {
		$contents = "";

		// Get the contents of each of the files, fixing up image URL paths for CSS files.
		foreach ($files as $f) {
			$content = file_get_contents(PATH_ROOT."/".$f);
			if ($type == "css") $content = preg_replace("/url\(('?)/i", "url($1".getResource(pathinfo($f, PATHINFO_DIRNAME)."/"), $content);
			$contents .= $content." ";
		}

		// Minify and write the contents.
		file_force_contents($file, $type == "css" ? minifyCSS($contents) : minifyJS($contents));
	}

	return array($file);
}


/**
 * Generate all of the HTML to be outputted inside of the <head> tag.
 *
 * @return string The HTML to go inside <head>.
 */
public function head()
{
	$head = "<!-- This page was generated by esoTalk (http://esotalk.org) -->\n";

	// Add the canonical URL tag.
	if (!empty($this->canonicalURL))
		$head .= "<link rel='canonical' href='$this->canonicalURL'>\n";

	// Add remote stylesheets.
	if (!empty($this->cssFiles["remote"])) {
		foreach ($this->cssFiles["remote"] as $url) {
			$head .= "<link rel='stylesheet' href='$url'>\n";
		}
	}
	unset($this->cssFiles["remote"]);

	// Go through CSS stylesheets and aggregate them, then add appropriate tags to the header.
	// Here we loop through "groups" of CSS files (usually "global" and "local".)
	foreach ($this->cssFiles as $key => $files) {

		// If CSS aggregation is enabled, and there's more than one file in this "group", proceed with aggregation.
		if (count($files) > 1 and C("esoTalk.aggregateCSS") and !(ET::$controller instanceof ETAdminController))
			$files = $this->aggregateFiles($files, "css");

		// Otherwise, we need to prepend the full path to each of the files.
		else foreach ($files as &$file) $file = PATH_ROOT."/".$file;
		unset($file);

		// For each of the files that we need to include in the page, add a <link> tag.
		foreach ($files as $file)
			$head .= "<link rel='stylesheet' href='".getResource($file)."?".@filemtime($file)."'>\n";

	}

	// Add remote JavaScript.
	if (!empty($this->jsFiles["remote"])) {
		foreach ($this->jsFiles["remote"] as $url) {
			$head .= "<script src='$url'></script>\n";
		}
	}
	unset($this->jsFiles["remote"]);

	// Same thing as above, but with JavaScript!
	foreach ($this->jsFiles as $files) {

		// If JS aggregation is enabled, and there's more than one file in this "group", proceed with aggregation.
		if (count($files) > 1 and C("esoTalk.aggregateJS") and !(ET::$controller instanceof ETAdminController))
			$files = $this->aggregateFiles($files, "js");

		// Otherwise, we need to prepend the full path to each of the files.
		else foreach ($files as &$file) $file = PATH_ROOT."/".$file;
		unset($file);

		// For each of the files that we need to include in the page, add a <script> tag.
		foreach ($files as $file)
			$head .= "<script src='".getResource($file)."?".filemtime($file)."'></script>\n";
	}


	// Output all necessary config variables and language definitions, as well as other variables.
	$esoTalkJS = array(
		"webPath" => ET::$webPath.((C("esoTalk.urls.friendly") and !C("esoTalk.urls.rewrite")) ? "/index.php" : ""),
		"userId" => ET::$session->user ? (int)ET::$session->userId : false,
		"token" => ET::$session->token,
		"debug" => C("esoTalk.debug"),
		"language" => $this->jsLanguage
	) + (array)$this->jsData;
	$head .= "<script>var ET=".json_encode($esoTalkJS)."</script>";

	// Finally, append the custom HTML string constructed via $this->addToHead().
	$head .= $this->head;

	$this->trigger("head", array(&$head));

	return $head;
}


/**
 * Add an item to one of the master view's menus.
 *
 * @param string $menu The name of the menu.
 * @param string $id The name of this menu item.
 * @param string $html The content of this menu item.
 * @param mixed $position Where to put this menu item relative to the others.
 * @see addToArray()
 * @return void
 */
public function addToMenu($menu, $id, $html, $position = false)
{
	if (empty($this->menus[$menu])) $this->menus[$menu] = ETFactory::make("menu");
	$this->menus[$menu]->add($id, $html, $position);
}

}
