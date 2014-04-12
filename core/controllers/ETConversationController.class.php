<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The conversation controller handles all actions to do with viewing/managing a single conversation.
 *
 * @package esoTalk
 */
class ETConversationController extends ETController {


/**
 * Show a full conversation.
 *
 * @param string $conversationId The conversation ID, suffixed with the conversation's slug.
 * @param mixed $year Can be in one of three formats:
 * 		YYYY/MM: start viewing posts from a certain year/month combination
 * 		pX: start viewing posts from page X
 * 		X: start viewing posts from position X
 * @param int $month If specified, the YYYY/MM combination will be used.
 * @return void
 */
public function index($conversationId = false, $year = false, $month = false)
{
	if (!$this->allowed()) return;

	// Get the conversation.
	$conversation = ET::conversationModel()->getById((int)$conversationId);

	// Stop here with a 404 header if the conversation wasn't found.
	if (!$conversation) {
		$this->render404(T("message.conversationNotFound"), true);
		return false;
	}

	// Are we searching within the conversation? If so, set the searchString and set the number of results as the post count.
	$searchString = R("search");
	if ($searchString) {

		$conversation["countPosts"] = ET::postModel()->getSearchResultsCount($conversation["conversationId"], $searchString);
		$conversation["searching"] = true;

		// Add the keywords in $this->searchString to be highlighted. Make sure we keep ones "in quotes" together.
		$words = array();
		$term = $searchString;
		if (preg_match_all('/"(.+?)"/', $term, $matches)) {
			$words[] = $matches[1];
			$term = preg_replace('/".+?"/', '', $term);
		}
		$words = array_unique(array_merge($words, explode(" ", $term)));
		ET::$session->store("highlight", $words);

	}
	// If we're not searching, clear the highlighted words.
	else {
		ET::$session->remove("highlight");
	}

	// Work out which post we are viewing from.
	$startFrom = 0;
	if ($year) {

		// Redirect to the user's oldest unread post.
		if ($year == "unread") {

			// Fetch the post ID of the user's oldest unread post (according to $conversation["lastRead"].)
			$id = ET::SQL()
				->select("postId")
				->from("post")
				->where("conversationId=:conversationId")->bind(":conversationId", $conversation["conversationId"])
				->orderBy("time ASC")
				->offset((int)$conversation["lastRead"])
				->limit(1)
				->exec()
				->result();

			// If a post ID was found, redirect to its position within the conversation.
			$startFrom = max(0, min($conversation["lastRead"], $conversation["countPosts"] - C("esoTalk.conversation.postsPerPage")));
			if ($id) $this->redirect(URL(conversationURL($conversation["conversationId"], $conversation["title"])."/$startFrom#p$id"));

		}

		// Redirect to the last post in the conversation.
		if ($year == "unread" or $year == "last") {

			// Fetch the post ID of the last post in the conversation.
			$id = ET::SQL()
				->select("postId")
				->from("post")
				->where("conversationId=:conversationId")->bind(":conversationId", $conversation["conversationId"])
				->orderBy("time DESC")
				->limit(1)
				->exec()
				->result();

			// Redirect there.
			$startFrom = max(0, $conversation["countPosts"] - C("esoTalk.conversation.postsPerPage"));
			$this->redirect(URL(conversationURL($conversation["conversationId"], $conversation["title"])."/$startFrom#p$id"));

		}

		// If a month was specified, interpret the arguments as year/month.
		elseif ($month and !$searchString) {
			$year = (int)$year;
			$month = (int)$month;

			// Bit of a hacky way of loading posts from the last page.
			if ($year == 9999 and $month == 99) $timestamp = PHP_INT_MAX;

			// Make a timestamp out of this date.
			else $timestamp = mktime(0, 0, 0, min($month, 12), 1, min($year, 2038));

			// Find the closest post that's after this timestamp, and find its position within the conversation.
			$position = ET::SQL()
				->select("COUNT(postId)", "position")
				->from("post")
				->where("time < :time")->bind(":time", $timestamp)
				->where("conversationId = :conversationId")->bind(":conversationId", $conversation["conversationId"])
				->exec()
				->result();

			$startFrom = min($conversation["countPosts"] - C("esoTalk.conversation.postsPerPage"), $position);

			$this->data("month", $month);
			$this->data("year", $year);
		}

		// Otherwise, interpret it is a plain page number, or position.
		else {
			if ($year[0] == "p") $startFrom = ((int)ltrim($year, "p") - 1) * C("esoTalk.conversation.postsPerPage");
			else $startFrom = (int)$year;
		}
	}

	// Make sure the startFrom number is within range.
	$startFrom = max(0, $startFrom);
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) $startFrom = min($startFrom, $conversation["countPosts"] - 1);

	if (ET::$session->userId) {

		// Update the user's last read.
		ET::conversationModel()->setLastRead($conversation, ET::$session->userId, $startFrom + C("esoTalk.conversation.postsPerPage"));

		// If we're on the last page, mark any notifications related to this conversation as read.
		if ($startFrom + C("esoTalk.conversation.postsPerPage") >= $conversation["countPosts"]) {
			ET::activityModel()->markNotificationsAsRead(null, $conversation["conversationId"]);
		}

		// Update the user's last action.
		ET::memberModel()->updateLastAction("viewingConversation", $conversation["private"] ? null : array(
			"conversationId" => $conversation["conversationId"],
			"title" => $conversation["title"]
		));

	}

	// Get the posts in the conversation.
	$options = array(
		"startFrom" => $startFrom,
		"limit" => C("esoTalk.conversation.postsPerPage")
	);
	if ($searchString) $options["search"] = $searchString;
	if ($startFrom < $conversation["countPosts"]) $posts = ET::postModel()->getByConversation($conversation["conversationId"], $options);
	else $posts = array();

	$this->trigger("conversationIndex", array(&$conversation, &$posts, &$startFrom, &$searchString));

	// Transport some data to the view.
	$this->data("conversation", $conversation);
	$this->data("posts", $posts);
	$this->data("startFrom", $startFrom);
	$this->data("searchString", $searchString);

	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

		// Construct a canonical URL to this page.
		$url = conversationURL($conversation["conversationId"], $conversation["title"])."/$startFrom".($searchString ? "?search=".urlencode($searchString) : "");
		$this->canonicalURL = URL($url, true);

		// If the slug in the URL is not the same as the actual slug, redirect.
		$slug = slug($conversation["title"]);
		if ($slug and (strpos($conversationId, "-") === false or substr($conversationId, strpos($conversationId, "-") + 1) != $slug)) {
			redirect(URL($url), 301);
		}

		// Push onto the top of the naviagation stack.
		$this->pushNavigation("conversation/".$conversation["conversationId"], "conversation", URL($url));

		// Set the title of the page.
		$this->title = $conversation["title"];

		// Get a list of the members allowed in this conversation.
		$conversation["membersAllowed"] = ET::conversationModel()->getMembersAllowed($conversation);
		$conversation["membersAllowedSummary"] = ET::conversationModel()->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);

		// Get the channel path of this conversation.
		$conversation["channelPath"] = ET::conversationModel()->getChannelPath($conversation);

		// Add essential variables and language definitions to be accessible through JavaScript.
		if ($conversation["canModerate"]) {
			$this->addJSLanguage("Lock", "Unlock", "Sticky", "Unsticky", "message.confirmDelete");
		}
		if (ET::$session->user) {
			$this->addJSLanguage("Starred", "Unstarred", "message.confirmLeave", "message.confirmDiscardReply",
				"Mute conversation", "Unmute conversation", "Controls", "Follow", "Following");
		}

		$this->addJSVar("postsPerPage", C("esoTalk.conversation.postsPerPage"));
		$this->addJSVar("conversationUpdateIntervalStart", C("esoTalk.conversation.updateIntervalStart"));
		$this->addJSVar("conversationUpdateIntervalMultiplier", C("esoTalk.conversation.updateIntervalMultiplier"));
		$this->addJSVar("conversationUpdateIntervalLimit", C("esoTalk.conversation.updateIntervalLimit"));
		$this->addJSVar("mentions", C("esoTalk.format.mentions"));
		$this->addJSVar("time", time());
		$this->addJSFile("core/js/lib/jquery.autogrow.js");
		$this->addJSFile("core/js/scrubber.js");
		$this->addJSFile("core/js/autocomplete.js");
		$this->addJSFile("core/js/conversation.js");

		// Add the RSS feed button.
		// $this->addToMenu("meta", "feed", "<a href='".URL("conversation/index.atom/".$url)."' id='feed'>".T("Feed")."</a>");

		$controls = ETFactory::make("menu");

		// Mute conversation control
		if (ET::$session->user) {
			$controls->add("mute", "<a href='".URL("conversation/mute/".$conversation["conversationId"]."/?token=".ET::$session->token."&return=".urlencode($this->selfURL))."' id='control-mute'><i class='icon-eye-close'></i> <span>".T($conversation["muted"] ? "Unmute conversation" : "Mute conversation")."</span></a>");
		}

		// Mark as unread/read control
		if (ET::$session->user) {
			$controls->add("read", "<a href='".URL("conversation/read/".$conversation["conversationId"]."/?token=".ET::$session->token)."' id='control-read'><i class='icon-circle'></i> <span>".T($conversation["lastRead"] >= $conversation["countPosts"] ? "Mark as unread" : "Mark as read")."</span></a>");
		}

		if ($conversation["canModerate"] or $conversation["startMemberId"] == ET::$session->userId) {
			$controls->separator();

			// Add the change channel control.
			$controls->add("changeChannel", "<a href='".URL("conversation/changeChannel/".$conversation["conversationId"]."/?return=".urlencode($this->selfURL))."' id='control-changeChannel'><i class='icon-tag'></i> <span>".T("Change channel")."</span></a>");
		}

		// If the user has permission to moderate this conversation...
		if ($conversation["canModerate"]) {

			// Add the sticky/unsticky control.
			$controls->add("sticky", "<a href='".URL("conversation/sticky/".$conversation["conversationId"]."/?token=".ET::$session->token."&return=".urlencode($this->selfURL))."' id='control-sticky'><i class='icon-pushpin'></i> <span>".T($conversation["sticky"] ? "Unsticky" : "Sticky")."</span></a>");

			// Add the lock/unlock control.
			$controls->add("lock", "<a href='".URL("conversation/lock/".$conversation["conversationId"]."/?token=".ET::$session->token."&return=".urlencode($this->selfURL))."' id='control-lock'><i class='icon-lock'></i> <span>".T($conversation["locked"] ? "Unlock" : "Lock")."</span></a>");

			// Add the delete conversation control.
			$controls->separator();
			$controls->add("delete", "<a href='".URL("conversation/delete/".$conversation["conversationId"]."/?token=".ET::$session->token)."' id='control-delete'><i class='icon-remove'></i> <span>".T("Delete conversation")."</span></a>");
		}

		// Add the meta description tag to the head. It will contain an excerpt from the first post's content.
		if ($conversation["countPosts"] > 0) {
			$description = ET::SQL()
				->select("LEFT(content, 156)")
				->from("post")
				->where("conversationId=:conversationId")
				->bind(":conversationId", $conversation["conversationId"])
				->orderBy("time ASC")
				->limit(1)
				->exec()
				->result();
			if (strlen($description) > 155) $description = substr($description, 0, strrpos($description, " ")) . " ...";
			$description = str_replace(array("\n\n", "\n"), " ", $description);
			$this->addToHead("<meta name='description' content='".sanitizeHTML($description)."'>");
		}

		// Add JavaScript variables which contain conversation information.
		$this->addJSVar("conversation", array(
			"conversationId" => (int)$conversation["conversationId"],
			"slug" => conversationURL($conversation["conversationId"], $conversation["title"]),
			"countPosts" => (int)$conversation["countPosts"],
			"startFrom" => (int)$startFrom,
			"searchString" => $searchString,
			"lastRead" => (ET::$session->user and $conversation["conversationId"])
				? (int)max(0, min($conversation["countPosts"], $conversation["lastRead"]))
				: (int)$conversation["countPosts"],
			// Start the auto-reload interval at the square root of the number of seconds since the last action.
			"updateInterval" => max(C("esoTalk.conversation.updateIntervalStart"), min(round(sqrt(time() - $conversation["lastPostTime"])), C("esoTalk.conversation.updateIntervalLimit"))),
			"channelId" => (int)$conversation["channelId"],
		));

		// Quote a post: get the post details (id, name, content) and then set the value of the reply textarea appropriately.
		if ($postId = (int)R("quote")) {
			$post = $this->getPostForQuoting($postId, $conversation["conversationId"]);
			if ($post) $conversation["draft"] = "[quote=$postId:".$post["username"]."]".ET::formatter()->init($post["content"])->removeQuotes()->get()."[/quote]";
		}

		// Set up the reply form.
		$replyForm = ETFactory::make("form");
		$replyForm->action = URL("conversation/reply/".$conversation["conversationId"]);
		$replyForm->setValue("content", $conversation["draft"]);

		$this->trigger("conversationIndexDefault", array(&$conversation, &$controls, &$replyForm, &$replyControls));

		$this->data("replyForm", $replyForm);
		$this->data("replyControls", $this->getEditControls("reply"));
		$this->data("conversation", $conversation);
		$this->data("controlsMenu", $controls);

		$this->render("conversation/index");

	}

	elseif ($this->responseType === RESPONSE_TYPE_AJAX) {

		$this->json("countPosts", $conversation["countPosts"]);
		$this->json("startFrom", $startFrom);

		$this->render("conversation/posts");

	}

	elseif ($this->responseType === RESPONSE_TYPE_VIEW) {

		$this->render("conversation/posts");

	}
}


/**
 * Show the start conversation page.
 *
 * @param string $member A member's name to make the conversation private with.
 * @return void
 */
public function start($member = false)
{
	// If the user isn't logged in, redirect them to the login page.
	if (!ET::$session->user) $this->redirect(URL("user/login?return=conversation/start"));

	// If the user is suspended, show an error.
	if (ET::$session->isSuspended()) {
		$this->renderMessage("Error!", T("message.suspended"));
		return;
	}

	// Set up a form.
	$form = ETFactory::make("form");
	$form->action = URL("conversation/start");

	// Get a list of channels so that we can check to make sure a valid channel is selected.
	$channels = ET::channelModel()->get("start");
	$channelId = $form->validPostBack("content") ? ET::$session->get("channelId") : ET::$session->get("searchChannelId");
	ET::$session->store("channelId", isset($channels[$channelId]) ? $channelId : reset(array_keys($channels)));

	// Get an empty conversation.
	$model = ET::conversationModel();
	$conversation = $model->getEmptyConversation();
	$conversation["membersAllowed"] = $model->getMembersAllowed($conversation);
	$conversation["membersAllowedSummary"] = $model->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);
	$conversation["channelPath"] = $model->getChannelPath($conversation);

	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

		$this->title = T("New conversation");

		// Update the user's last action to say that they're "starting a conversation".
		ET::memberModel()->updateLastAction("startingConversation");

		// Add a meta tag to the head to prevent search engines from indexing this page.
		$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");
		$this->addJSFile("core/js/lib/jquery.autogrow.js");
		$this->addJSFile("core/js/scrubber.js");
		$this->addJSFile("core/js/autocomplete.js");
		$this->addJSFile("core/js/conversation.js");
		$this->addJSVar("mentions", C("esoTalk.format.mentions"));
		$this->addJSLanguage("message.confirmLeave", "message.confirmDiscardReply");

		// If there's a member name in the querystring, make the conversation that we're starting private
		// with them and redirect.
		if ($member and ET::$session->validateToken(R("token"))) {
			ET::$session->remove("membersAllowed");
			if (!($member = ET::conversationModel()->getMemberFromName($member))) {
				$this->message(T("message.memberDoesntExist"), "warning");
			}
			else {
				ET::conversationModel()->addMember($conversation, $member);
			}
			$this->redirect(URL("conversation/start"));
		}

	}

	// If the form was submitted (validate the presence of the content field)...
	if ($form->validPostBack("content")) {

		$model = ET::conversationModel();

		$result = $model->create(array(
			"title" => $_POST["title"],
			"channelId" => ET::$session->get("channelId"),
			"content" => $_POST["content"],
		), ET::$session->get("membersAllowed"), $form->isPostBack("saveDraft"));

		if ($model->errorCount()) {
			$this->messages($model->errors(), "warning");
		}

		if ($result) {
			list($conversationId, $postId) = $result;

			ET::$session->remove("membersAllowed");
			ET::$session->remove("channelId");

			if ($this->responseType === RESPONSE_TYPE_JSON) {
				$this->json("url", URL(conversationURL($conversationId, $form->getValue("title"))));
				$this->json("conversationId", $conversationId);
			}
			else $this->redirect(URL(conversationURL($conversationId, $form->getValue("title"))));
		}

	}

	// Make a form to add members allowed.
	$membersAllowedForm = ETFactory::make("form");
	$membersAllowedForm->action = URL("conversation/addMember/");

	$this->data("conversation", $conversation);
	$this->data("form", $form);
	$this->data("membersAllowedForm", $membersAllowedForm);
	$this->data("replyControls", $this->getEditControls("reply"));

	$this->render("conversation/edit");
}


/**
 * Redirect to show a specific post within its conversation.
 *
 * @param int $postId The post ID to show.
 * @return void
 */
public function post($postId = false)
{
	// Construct a subquery that will find the position of a post within its conversation.
	$subquery = ET::SQL()
		->select("COUNT(postId)")
		->from("post p2")
		->where("p2.conversationId=p.conversationId")
		->where("p2.time<=p.time")
		->where("IF(p2.time=p.time,p2.postId<p.postId,1)")
		->get();

	// Construct and run a query that will get the position of the post, the conversation ID, and the title.
	$result = ET::SQL()
		->select("($subquery) AS pos, c.conversationId, c.title")
		->from("post p")
		->from("conversation c", "c.conversationId=p.conversationId", "left")
		->where("p.postId=:postId")
		->bind(":postId", (int)$postId)
		->exec();

	// If the post wasn't found, show a 404.
	if (!$result->numRows()) {
		$this->render404(T("message.postNotFound"));
		return;
	}

	list($pos, $conversationId, $title) = array_values($result->firstRow());

	// Work out which page of the conversation this post is on, and redirect there.
	$page = floor($pos / C("esoTalk.conversation.postsPerPage")) + 1;
	$this->redirect(URL(conversationURL($conversationId, $title)."/p".$page."#p".$postId));
}


/**
 * Show a post's details in JSON format so they can be used to construct a quote. The JSON output will
 * include the postId, member (prefixed with an @ if mentions are enabled), and the content (with inner quotes
 * removed.)
 *
 * @param int $postId The post ID.
 * @return void
 */
public function quotePost($postId = false)
{
	$this->responseType = RESPONSE_TYPE_JSON;

	// Fetch the conversation to make sure the user is allowed to view this conversation.
	$conversation = ET::conversationModel()->getByPostId($postId);

	// Stop here if the conversation doesn't exist, or if the user is not allowed to view it.
	if (!$conversation) {
		$this->render404(T("message.conversationNotFound"));
		return;
	}

	$post = $this->getPostForQuoting($postId, $conversation["conversationId"]);
	if ($post) {
		$this->json("postId", $postId);
		$this->json("member", (C("esoTalk.format.mentions") ? "@" : "").$post["username"]);
		$this->json("content", ET::formatter()->init($post["content"], false)->removeQuotes()->get());
		$this->render();
	}
}


/**
 * Delete a conversation, and redirect to the home page.
 *
 * @param int $conversationId The ID of the conversation to delete.
 * @return void
 */
public function delete($conversationId = false)
{
	if (!$this->validateToken()) return;

	if (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"]) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	// Delete the conversation, then redirect to the index.
	ET::conversationModel()->deleteById($conversation["conversationId"]);
	$this->message(T("message.conversationDeleted"), "success autoDismiss");
	$this->redirect(URL(""));
}


/**
 * Toggle the sticky flag on a conversation.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function sticky($conversationId = false)
{
	$this->toggle($conversationId, "sticky");
}


/**
 * Toggle the locked flag on a conversation.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function lock($conversationId = false)
{
	$this->toggle($conversationId, "locked");
}


/**
 * Toggle a flag on a conversation.
 *
 * @param int $conversationId The ID of the conversation.
 * @param string $type The name of the flag to toggle.
 * @return void
 */
protected function toggle($conversationId, $type)
{
	if (!$this->validateToken()) return;

	if (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"]) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	$function = "set".ucfirst($type);
	ET::conversationModel()->$function($conversation, !$conversation[$type]);

	// For the default response type, redirect back to the conversation.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		$this->redirect(URL(R("return", conversationURL($conversation["id"], $conversation["title"]))));
	}
	// Otherwise, output JSON of the flag's new value.
	else {
		$this->json($type, !$conversation[$type]);
		if ($this->responseType === RESPONSE_TYPE_AJAX)
			$this->json("labels", $this->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));
		$this->render();
	}
}


/**
 * Show a page where a conversation's details (title, members allowed) can be edited.
 *
 * @param int $conversationId The ID of the conversation to edit.
 * @return void
 */
public function edit($conversationId = false)
{
	if (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"] and $conversation["startMemberId"] != ET::$session->userId) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	// Make a form to submit to the save page.
	$form = ETFactory::make("form");
	$form->action = URL("conversation/save/".$conversation["conversationId"]);
	$form->setValue("title", $conversation["title"]);

	// Get a list of the members allowed in this conversation.
	$conversation["membersAllowed"] = ET::conversationModel()->getMembersAllowed($conversation);
	$conversation["membersAllowedSummary"] = ET::conversationModel()->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);
	$conversation["channelPath"] = ET::conversationModel()->getChannelPath($conversation);

	// Make a form to add members allowed.
	$membersAllowedForm = ETFactory::make("form");
	$membersAllowedForm->action = URL("conversation/addMember/".$conversation["conversationId"]);

	// Pass along the data to the view.
	$this->data("conversation", $conversation);
	$this->data("form", $form);
	$this->data("membersAllowedForm", $membersAllowedForm);

	$this->render("conversation/edit");
}


/**
 * Show a page where a conversation's channel can be changed.
 *
 * @param int $conversationId The ID of the conversation to edit.
 * @return void
 */
public function changeChannel($conversationId = "")
{
	// Get the conversation.
	if (!$conversationId) $conversation = ET::conversationModel()->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"] and $conversation["startMemberId"] != ET::$session->userId) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	// Get the channels, and add a "start" permission field to each of them.
	$channels = ET::channelModel()->get();
	$groupModel = ET::groupModel();
	$groupIds = ET::$session->getGroupIds();
	foreach ($channels as $k => &$channel) {
		if (!empty($channel["unsubscribed"])) {
			unset($channels[$k]);
			continue;
		}
		$channel["start"] = $groupModel->groupIdsAllowedInGroupIds($groupIds, $channel["permissions"]["start"], true);
	}

	// Make a form to submit to the save page.
	$form = ETFactory::make("form");
	$form->action = URL("conversation/save/".$conversation["conversationId"]);
	$form->setValue("channel", $conversation["channelId"]);

	// Pass along data to the view.
	$this->data("conversation", $conversation);
	$this->data("channels", $channels);
	$this->data("form", $form);

	$this->render("conversation/changeChannel");
}


/**
 * Save a conversation's details.
 *
 * @param int $conversationId The conversation ID.
 * @return void
 */
public function save($conversationId = false)
{
	if (!$this->validateToken()) return;

	// Get the conversation.
	$model = ET::conversationModel();
	if (!$conversationId) $conversation = $model->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"] and $conversation["startMemberId"] != ET::$session->userId) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	// Set up a form to handle input.
	$form = ETFactory::make("form");

	// If the conversation exists, interact with the conversation model to save data.
	if ($conversation["conversationId"]) {

		// Save the title.
		if ($title = $form->getValue("title"))
			$model->setTitle($conversation, $title);

		// Save the channel.
		if ($channelId = $form->getValue("channel"))
			$model->setChannel($conversation, $channelId);

		// If there are errors, show them.
		if ($model->errorCount())
			$this->messages($model->errors(), "warning");

		// Otherwise, redirect to the conversation.
		elseif ($this->responseType === RESPONSE_TYPE_DEFAULT)
			redirect(URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))));

		// Fetch the new conversation details.
		$conversation = $model->getById($conversation["conversationId"]);
	}

	// If the conversation does not exist (i.e. we're changing the channel when starting a conversation),
	// interact with the session channelId variable.
	else {

		if ($channelId = $form->getValue("channel"))
			ET::$session->store("channelId", (int)$channelId);

		// If there are errors, show them.
		if ($model->errorCount())
			$this->messages($model->errors(), "warning");

		// Otherwise, redirect to the start conversation page.
		elseif ($this->responseType === RESPONSE_TYPE_DEFAULT)
			redirect(URL(R("return", "conversation/start")));

		// Fetch the new conversation details.
		$conversation = $model->getEmptyConversation();

	}

	// As the channel may have been changed, we need to fetch the members allowed summary (as it could vary
	// depending on what groups have permission to view the channel.)
	$conversation["membersAllowed"] = $model->getMembersAllowed($conversation);
	$conversation["membersAllowedSummary"] = $model->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);
	$conversation["channelPath"] = $model->getChannelPath($conversation);
	$this->json("allowedSummary", $this->getViewContents("conversation/membersAllowedSummary", array("conversation" => $conversation)));
	$this->json("channelPath", $this->getViewContents("conversation/channelPath", array("conversation" => $conversation)));

	// Also return the details of the new channel.
	$this->json("channel", array(
		"channelId" => $conversation["channelId"],
		"link" => URL("conversations/".$conversation["channelSlug"]),
		"title" => $conversation["channelTitle"],
		"description" => $conversation["channelDescription"]
	));

	$this->render();
}


/**
 * Show a page where the members allowed in a conversation can be edited.
 *
 * @param int $conversationId The ID of the conversation to edit.
 * @return void
 */
public function membersAllowed($conversationId = false)
{
	// Get the conversation.
	if (!$conversationId) $conversation = ET::conversationModel()->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	// Do we have permission to do this?
	if (!$conversation["canModerate"] and ET::$session->userId != $conversation["startMemberId"]) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	$conversation["membersAllowed"] = ET::conversationModel()->getMembersAllowed($conversation);
	$conversation["membersAllowedSummary"] = ET::conversationModel()->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);

	// Make a form to add members allowed.
	$form = ETFactory::make("form");
	$form->action = URL("conversation/addMember/".$conversation["conversationId"]);

	$this->data("conversation", $conversation);
	$this->data("form", $form);

	$this->render("conversation/editMembersAllowed");
}


/**
 * Show a full list of the members allowed in a conversation. This is used in popups triggered by hovering
 * over a "3 others" link or a private label.
 *
 * @param int $conversationId The ID of the conversation to get members allowed for.
 * @return void
 */
public function membersAllowedList($conversationId = false)
{
	// Get the conversation.
	if (!$conversationId) $conversation = ET::conversationModel()->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	$conversation["membersAllowed"] = ET::conversationModel()->getMembersAllowed($conversation);
	$conversation["membersAllowed"] = ET::conversationModel()->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);

	$this->data("conversation", $conversation);

	$this->render("conversation/membersAllowedList");
}


/**
 * Add a member to the allowed list of a conversation.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function addMember($conversationId = false)
{
	if (!$this->validateToken()) return;

	// Get the conversation.
	$model = ET::conversationModel();
	if (!$conversationId) $conversation = $model->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	if ($name = str_replace("\xc2\xa0", " ", R("member"))) {

		// Get an entity's details by parsing the member name.
		if (!($member = $model->getMemberFromName($name))) {
			$this->message(T("message.memberNotFound"), array("className" => "warning autoDismiss", "id" => "memberNotFound"));
		}

		// Make sure the entity is allowed to view the channel that the conversation is in.
		elseif (!ET::groupModel()->groupIdsAllowedInGroupIds($member["type"] == "group" ? $member["id"] : $member["groups"], array_keys($conversation["channelPermissionView"]))) {
			$this->message(T("message.memberNoPermissionView"), "warning");
		}

		// Good to go? Add the member!
		else {
			$model->addMember($conversation, $member);
		}

	}

	// Fetch the new list of members allowed in the conversation.
	$conversation["membersAllowed"] = $model->getMembersAllowed($conversation);
	$conversation["membersAllowedSummary"] = $model->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);

	// If it's an AJAX request, return the contents of a few views.
	if ($this->responseType === RESPONSE_TYPE_AJAX) {
		$this->json("allowedSummary", $this->getViewContents("conversation/membersAllowedSummary", array("conversation" => $conversation)));
		$this->json("allowedList", $this->getViewContents("conversation/membersAllowedList", array("conversation" => $conversation, "editable" => true)));
		$this->json("labels", $this->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));
		$this->render();
	}

	// JSON?

	// Otherwise, redirect back to the conversation edit page.
	else {
		$this->redirect(URL(R("return", $conversation["conversationId"] ? "conversation/edit/".$conversation["conversationId"] : "conversation/start")));
	}
}


/**
 * Remove a member from the allowed list of a conversation.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function removeMember($conversationId = false)
{
	if (!$this->validateToken()) return;

	// Get the conversation.
	$model = ET::conversationModel();
	if (!$conversationId) $conversation = $model->getEmptyConversation();
	elseif (!($conversation = $this->getConversation($conversationId))) return;

	// Get the members allowed in the conversation.
	$conversation["membersAllowed"] = $model->getMembersAllowed($conversation);

	$member = null;

	// We could be removing a member...
	if ($id = R("member")) {
		$member = array("type" => "member", "id" => $id);
	}
	// Or we could be removing a group.
	elseif ($id = R("group")) {
		$member = array("type" => "group", "id" => $id);
	}

	// If we have a member/group to remove, remove it!
	if ($member) {
		$model->removeMember($conversation, $member);
	}

	// Now grab the new members allowed summary for the conversation.
	$conversation["membersAllowedSummary"] = $model->getMembersAllowedSummary($conversation, $conversation["membersAllowed"]);

	// If it's an AJAX request, return the contents of a few views.
	if ($this->responseType === RESPONSE_TYPE_AJAX) {
		$this->json("allowedSummary", $this->getViewContents("conversation/membersAllowedSummary", array("conversation" => $conversation)));
		$this->json("allowedList", $this->getViewContents("conversation/membersAllowedList", array("conversation" => $conversation, "editable" => true)));
		$this->json("labels", $this->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));
		$this->render();
	}

	// JSON?

	// Otherwise, redirect back to the conversation edit page.
	else {
		$this->redirect(URL(R("return", $conversation["conversationId"] ? "conversation/edit/".$conversation["conversationId"] : "conversation/start")));
	}
}


/**
 * Toggle the starred flag of a conversation for the current user.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function star($conversationId = false)
{
	if (!ET::$session->user or !$this->validateToken()) return;

	// Get the conversation.
	if (!($conversation = $this->getConversation($conversationId))) return;

	// Star/unstar the conversation.
	$starred = !$conversation["starred"];
	ET::conversationModel()->setStatus($conversation["conversationId"], ET::$session->userId, array("starred" => $starred));

	$this->json("starred", $starred);

	// Redirect back to the conversation.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		redirect(URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))));
	}

	$this->render();
}


/**
 * Toggle the muted flag of a conversation for the current user.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function mute($conversationId = false)
{
	if (!ET::$session->user or !$this->validateToken()) return;

	// Get the conversation.
	if (!($conversation = $this->getConversation($conversationId))) return;

	// Mute/unmute the conversation.
	$muted = !$conversation["muted"];
	ET::conversationModel()->setMuted($conversation, ET::$session->userId, $muted);

	$this->json("muted", $muted);

	// Redirect back to the conversation.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		redirect(URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))));
	}

	// If it's an AJAX request, return the contents of the labels view.
	elseif ($this->responseType === RESPONSE_TYPE_AJAX)
		$this->json("labels", $this->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));

	$this->render();
}


/**
 * Toggle the conversation between the read/unread states.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function read($conversationId = false)
{
	if (!ET::$session->user or !$this->validateToken()) return;

	// Get the conversation.
	if (!($conversation = $this->getConversation($conversationId))) return;

	if ($conversation["lastRead"] >= $conversation["countPosts"]) $lastRead = 0;
	else $lastRead = $conversation["countPosts"];

	ET::conversationModel()->setLastRead($conversation, ET::$session->userId, $lastRead, true);

	// Redirect back to the last place we were at.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		$nav = ET::$session->getNavigation("conversation/".$conversation["conversationId"]);
		$return = R("return");
		redirect($return ? URL($return) : $nav["url"]);
	}

	$this->render();
}


/**
 * Reply to a conversation, or save/discard a draft.
 *
 * @param int $conversationId The ID of the conversation.
 * @return void
 */
public function reply($conversationId = false)
{
	if (!ET::$session->user or !$this->validateToken()) return;

	// Get the conversation.
	if (!($conversation = $this->getConversation($conversationId))) return;

	// Can the user reply?
	if (!$conversation["canReply"]) {
		$this->renderMessage(T("Error"), T("message.noPermission"));
		return;
	}

	// Set up a form to handle the input.
	$form = ETFactory::make("form");

	// Save or discard a draft.
	if ($form->validPostBack("saveDraft") or $form->validPostBack("discardDraft")) {

		$content = $form->isPostBack("saveDraft") ? $form->getValue("content") : null;
		ET::conversationModel()->setDraft($conversation, ET::$session->userId, $content);

		// If there are no other posts in the conversation, delete the conversation.
		if ($form->isPostBack("discardDraft") and !$conversation["countPosts"]) {
			$this->delete($conversation["conversationId"]);
			return;
		}

		// For an AJAX request, add the conversation labels to the output.
		if ($this->responseType === RESPONSE_TYPE_AJAX) {
			$this->json("labels", $this->getViewContents("conversation/labels", array("labels" => $conversation["labels"])));
			$this->render();
			return;
		}

	}

	// Add a reply.
	else {

		// Fetch the members allowed so that notifications can be sent out in the addReply method if this is
		// the first post.
		$model = ET::conversationModel();
		$conversation["membersAllowed"] = $model->getMembersAllowed($conversation);
		$postId = $model->addReply($conversation, $form->getValue("content"));

		// If there were errors, show them.
		if ($model->errorCount())
			$this->messages($model->errors(), "warning");

		else {

			// Update the user's last read.
			$model->setLastRead($conversation, ET::$session->userId, $conversation["countPosts"]);

			// Return a few bits of information.
			$this->json("postId", $postId);
			$this->json("starOnReply", (bool)ET::$session->preference("starOnReply", false));

			// For an AJAX request, render the new post view.
			if ($this->responseType === RESPONSE_TYPE_AJAX) {
				$this->data("conversation", $conversation);
				$this->data("posts", ET::postModel()->getByConversation($conversation["conversationId"], array("startFrom" => $conversation["countPosts"] - 1, "limit" => 1)));
				$this->render("conversation/posts");
				return;
			}

			// Normally, redirect to the post we just made.
			elseif ($this->responseType === RESPONSE_TYPE_DEFAULT) {
				$this->redirect(URL(R("return", postURL($postId))));
			}

		}

	}

	// Redirect back to the conversation's reply box.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		$this->redirect(URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"])."#reply")));
	}

	$this->render();
}


/**
 * Format a string of content to be previewed when editing a post.
 *
 * @return void
 */
public function preview()
{
	$this->responseType = RESPONSE_TYPE_JSON;
	$this->json("content", $this->displayPost(R("content")));
	$this->render();
}


/**
 * Edit a post.
 *
 * @param int $postId The post ID.
 * @return void
 */
public function editPost($postId = false)
{
	if (!($post = $this->getPostForEditing($postId))) return;

	// Set up a form.
	$form = ETFactory::make("form");
	$form->action = URL("conversation/editPost/".$post["postId"]);
	$form->setValue("content", $post["content"]);

	if ($form->isPostBack("cancel"))
		$this->redirect(URL(R("return", postURL($postId))));

	// Are we saving the post?
	if ($form->validPostBack("save")) {

		ET::postModel()->editPost($post, $form->getValue("content"));

		$this->trigger("editPostAfter", array(&$post));

		// Normally, redirect back to the conversation.
		if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
			redirect(URL(R("return", postURL($postId))));
		}

		// For an AJAX request, render the post view.
		elseif ($this->responseType === RESPONSE_TYPE_AJAX) {
			$this->data("post", $this->formatPostForTemplate($post, $post["conversation"]));
			$this->render("conversation/post");
			return;
		}

		else {
			// JSON?
		}

	}

	$this->data("form", $form);
	$this->data("post", $post);
	$this->data("controls", $this->getEditControls("p".$post["postId"]));
	$this->render("conversation/editPost");
}


/**
 * Delete a post.
 *
 * @param int $postId The post ID.
 * @return void
 */
public function deletePost($postId = false)
{
	if (!($post = $this->getPostForEditing($postId)) or !$this->validateToken()) return;

	if (!$post["deleteMemberId"]) {
		ET::postModel()->deletePost($post);
		// Normally, redirect back to the conversation.
		if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
			redirect(URL(R("return", postURL($postId))));
		}
		// For an AJAX request, render the post view.
		elseif ($this->responseType === RESPONSE_TYPE_AJAX) {
			$this->data("post", $this->formatPostForTemplate($post, $post["conversation"]));
			$this->render("conversation/post");
			return;
		}
		return;
	}

    ET::$database->beginTransaction();

	$memberId = $post["memberId"];
	$conversationId = $post["conversationId"];

	$channelId = ET::SQL()
		->select("channelId")
		->from("conversation")
		->where("conversationId", $conversationId)
		->exec()->result();

	if (!$channelId) {
		ET::$database->rollbackTransaction();
		return;
	}

	if (!ET::SQL()
		->update("channel")
		->set("countPosts", "GREATEST(0, CAST(countPosts AS SIGNED) - 1)", false)
		->where("channelId = (".$channelId.")")
		->exec()) {
		ET::$database->rollbackTransaction();
		return;
	}

	if (!ET::SQL()
		->update("conversation")
		->set("countPosts", "countPosts - 1", false)
		->where("conversationid", $conversationId)
		->exec()) {
		ET::$database->rollbackTransaction();
		return;
	}

	if (!ET::postModel()->deleteById($postId)) {
		ET::$database->rollbackTransaction();
		return;
	}

	$rows = ET::SQL()
		->select("attachmentId")
		->from("attachment")
		->where("postId", $postId)
		->exec()
		->allRows();

	$amodel = ET::getInstance("attachmentModel");

	foreach ($rows as $row) {
		$aid = $row["attachmentId"];
		$attachment = $amodel->getById($aid);
		$amodel->deleteById($aid);
		@unlink($amodel->path().$attachmentId.$attachment["secret"]);
	}

	$res = ET::SQL()
		->select("memberId, time")
		->from("post")
		->where("conversationId",$conversationId)
		->orderBy("time DESC")
		->limit(1)
		->exec()
		->firstRow();
	if ($res) {
		$endId = $res["memberId"];
		$endTime = $res["time"];
		if ($endId && $endTime) {
			ET::SQL()
			->update("conversation")
			->set("lastPostMemberId", $endId, false)
			->set("lastPostTime", $endTime, false)
			->where("conversationid", $conversationId)
			->exec();
		}
	}
	ET::$database->commitTransaction();
	return;
}


/**
 * Restore a post.
 *
 * @param int $postId The post ID.
 * @return void
 */
public function restorePost($postId = false)
{
	if (!($post = $this->getPostForEditing($postId)) or !$this->validateToken()) return;

	ET::postModel()->restorePost($post);

	// Normally, redirect back to the conversation.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {
		redirect(URL(R("return", postURL($postId))));
	}

	// For an AJAX request, render the post view.
	elseif ($this->responseType === RESPONSE_TYPE_AJAX) {
		$this->data("post", $this->formatPostForTemplate($post, $post["conversation"]));
		$this->render("conversation/post");
		return;
	}
}


/**
 * Format post data into an array which can be used to display the post template view (conversation/post).
 *
 * @param array $post The post data.
 * @param array $conversation The details of the conversation which the post is in.
 * @return array A formatted array which can be used in the post template view.
 */
protected function formatPostForTemplate($post, $conversation)
{
	$canEdit = ET::postModel()->canEditPost($post, $conversation);
	$avatar = avatar($post);

	// Construct the post array for use in the post view (conversation/post).
	$formatted = array(
		"id" => "p".$post["postId"],
		"title" => memberLink($post["memberId"], $post["username"]),
		"avatar" => (!$post["deleteMemberId"] and $avatar) ? "<a href='".URL(memberURL($post["memberId"], $post["username"]))."'>$avatar</a>" : false,
		"class" => $post["deleteMemberId"] ? array("deleted") : array(),
		"info" => array(),
		"controls" => array(),
		"body" => !$post["deleteMemberId"] ? $this->displayPost($post["content"]) : false,
		"footer" => array(),

		"data" => array(
			"id" => $post["postId"],
			"memberid" => $post["memberId"]
		)
	);

	$date = smartTime($post["time"], true);

	// Add the date/time to the post info as a permalink.
	$formatted["info"][] = "<a href='".URL(postURL($post["postId"]))."' class='time' title='".strftime(T("date.full"), $post["time"])."'>".(!empty($conversation["searching"]) ? T("Context") : $date)."</a>";

	// If the post isn't deleted, add a lot of stuff!
	if (!$post["deleteMemberId"]) {

		// Add the user's online status / last action next to their name.
		if (empty($post["preferences"]["hideOnline"])) {
			$lastAction = ET::memberModel()->getLastActionInfo($post["lastActionTime"], $post["lastActionDetail"]);
			if ($lastAction[0]) $lastAction[0] = " (".sanitizeHTML($lastAction[0]).")";
			if ($lastAction) array_unshift($formatted["info"], "<".(!empty($lastAction[1]) ? "a href='{$lastAction[1]}'" : "span")." class='online' title='".T("Online")."{$lastAction[0]}'><i class='icon-circle'></i></".(!empty($lastAction[1]) ? "a" : "span").">");
		}

		// Show the user's group type.
		$formatted["info"][] = "<span class='group'>".memberGroup($post["account"], $post["groups"])."</span>";
		$formatted["class"][] = "group-".$post["account"];
		foreach ($post["groups"] as $k => $v) {
			if ($k) $formatted["class"][] = "group-".$k;
		}

		// If the post has been edited, show the time and by whom next to the controls.
		if ($post["editMemberId"]) $formatted["controls"][] = "<span class='editedBy'>".sprintf(T("Edited %s by %s"), "<span title='".strftime(T("date.full"), $post["editTime"])."'>".relativeTime($post["editTime"], true)."</span>", name($post["editMemberName"]))."</span>";

		// If the user can reply, add a quote control.
		if ($conversation["canReply"])
			$formatted["controls"][] = "<a href='".URL(conversationURL($conversation["conversationId"], $conversation["title"])."/?quote=".$post["postId"]."#reply")."' title='".T("Quote")."' class='control-quote'><i class='icon-quote-left'></i></a>";

		// If the user can edit the post, add edit/delete controls.
		if ($canEdit) {
			$formatted["controls"][] = "<a href='".URL("conversation/editPost/".$post["postId"])."' title='".T("Edit")."' class='control-edit'><i class='icon-edit'></i></a>";
			$formatted["controls"][] = "<a href='".URL("conversation/deletePost/".$post["postId"]."?token=".ET::$session->token)."' title='".T("Delete")."' class='control-delete'><i class='icon-remove'></i></a>";
		}

	}

	// But if the post IS deleted...
	else {

		// Add the "deleted by" information.
		if ($post["deleteMemberId"]) $formatted["controls"][] = "<span>".sprintf(T("Deleted %s by %s"), "<span title='".strftime(T("date.full"), $post["deleteTime"])."'>".relativeTime($post["deleteTime"], true)."</span>", name($post["deleteMemberName"]))."</span>";

		// If the user can edit the post, add a restore control.
		if ($canEdit) {
			$formatted["controls"][] = "<a href='".URL("conversation/restorePost/".$post["postId"]."?token=".ET::$session->token)."' title='".T("Restore")."' class='control-restore'><i class='icon-reply'></i></a>";
            if (ET::$session->isAdmin()) {
                $formatted["controls"][] = "<a href='".URL("conversation/deletePost/".$post["postId"]."?token=".ET::$session->token)."' title='".T("Delete")."' class='control-delete'><i class='icon-remove'></i></a>";
            }
        }
	}

	$this->trigger("formatPostForTemplate", array(&$formatted, $post, $conversation));

	return $formatted;
}


/**
 * Format a post's content to be displayed.
 *
 * @param string $content The post content to format.
 * @return string The formatted post content.
 */
protected function displayPost($content)
{
	$words = ET::$session->get("highlight");
	return ET::formatter()->init($content)->highlight($words)->format()->get();
}


/**
 * Get an array of formatting controls to be shown when editing a post.
 *
 * @param string $id The ID of the post area (eg. p# or reply.)
 * @return array The controls.
 */
protected function getEditControls($id)
{
	$controls = array(
		"quote" => "<a href='javascript:ETConversation.quote(\"$id\");void(0)' class='control-quote' title='".T("Quote")."' accesskey='q'><i class='icon-quote-left'></i></a>",
	);

	$this->trigger("getEditControls", array(&$controls, $id));

	if (!empty($controls)) {
		array_unshift($controls, "<span class='formattingButtons'>");
		$controls[] = "</span>";
		$controls[] = "<label class='previewCheckbox'><input type='checkbox' id='$id-previewCheckbox' onclick='ETConversation.togglePreview(\"$id\",this.checked)' accesskey='p'/> ".T("Preview")."</label>";
	}

	return $controls;
}


/**
 * Get post data so it can be used to construct a quote of a post.
 *
 * @param int $postId The ID of the post.
 * @param int $conversationId The ID of the conversation that the post is in.
 * @return array An array containing the username and the post content.
 */
protected function getPostForQuoting($postId, $conversationId)
{
	$result = ET::SQL()
		->select("username, content")
		->from("post p")
		->from("member m", "m.memberId=p.memberId", "inner")
		->where("p.postId=:postId")
		->where("p.conversationId=:conversationId")
		->bind(":postId", $postId)
		->bind(":conversationId", $conversationId)
		->exec();
	if (!$result->numRows()) return false;
	$result = $result->firstRow();

	// Convert spaces in the member name to non-breaking spaces.
	// (Spaces aren't usually allowed in esoTalk usernames, so this is a bit of a "hack" for
	// certain esoTalk installations that do allow them.)
	$result["username"] = str_replace(" ", "\xc2\xa0", $result["username"]);

	return $result;
}


/**
 * Shortcut function to get a conversation and render a 404 page if it cannot be found.
 *
 * @param int $id The ID of the conversation to get, or the post to get the conversation of.
 * @param bool $post Whether or not $id is the conversationId or a postId.
 * @return bool|array An array of the conversation details, or false if it wasn't found.
 */
protected function getConversation($id, $post = false)
{
	$conversation = !$post ? ET::conversationModel()->getById($id) : ET::conversationModel()->getByPostId($id);

	// Stop here if the conversation doesn't exist, or if the user is not allowed to view it.
	if (!$conversation) {
		$this->render404(T("message.conversationNotFound"));
		return false;
	}

	return $conversation;
}


/**
 * Return post data to work with for an editing action (editPost, deletePost, etc.), but only if the post
 * exists and the user has permission to edit it.
 *
 * @param int $postId The post ID.
 * @return bool|array An array of post data, or false if it cannot be edited.
 */
protected function getPostForEditing($postId)
{
	// Get the conversation.
	if (!($conversation = $this->getConversation($postId, true))) return false;

	// Get the post.
	$post = ET::postModel()->getById($postId);

	// Stop here with an error if the user isn't allowed to edit this post.
	if (!ET::postModel()->canEditPost($post, $conversation)) {

		// If users only have permission to edit their posts until someone replies, and someone has replied since...
		if (C("esoTalk.conversation.editPostTimeLimit") === "reply" and ($conversation["lastPostTime"] > $post["time"] or $conversation["lastPostMemberId"] != $post["memberId"])) $msg = T("message.cannotEditSinceReply");

		// Otherwise, show a generic "no permission" message.
		else $msg = T("message.noPermission");

		$this->renderMessage(T("Error"), $msg);
		return false;
	}

	$post["conversation"] = $conversation;

	return $post;
}

}
