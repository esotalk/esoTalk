<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The conversations controller displays a list of conversations, and allows filtering by channels
 * and gambits. It also handles marking all conversations as read, and has a method which provides
 * auto-refresh results for the conversations view.
 *
 * @package esoTalk
 */
class ETConversationsController extends ETController {


/**
 * Display a list of conversations, optionally filtered by channel(s) and a search string.
 *
 * @return void
 */
public function action_index($channelSlug = false)
{
	if (!$this->allowed()) return;
	
	list($channelInfo, $currentChannels, $channelIds, $includeDescendants) = $this->getSelectedChannels($channelSlug);

	// Now we need to construct some arrays to determine which channel "tabs" to show in the view.
	// $channels is a list of channels with the same parent as the current selected channel(s).
	// $path is a breadcrumb trail to the depth of the currently selected channel(s).
	$channels = array();
	$path = array();

	// Work out what channel we will use as the "parent" channel. This will be the last item in $path,
	// and its children will be in $channels.
	$curChannel = false;

	// If channels have been selected, use the first of them.
	if (count($currentChannels)) $curChannel = $channelInfo[$currentChannels[0]];

	// If the currently selected channel has no children, or if we're not including descendants, use
	// its parent as the parent channel.
	if (($curChannel and $curChannel["lft"] >= $curChannel["rgt"] - 1) or !$includeDescendants)
		$curChannel = @$channelInfo[$curChannel["parentId"]];

	// If no channel is selected, make a faux parent channel.
	if (!$curChannel) $curChannel = array("lft" => 0, "rgt" => PHP_INT_MAX, "depth" => -1);

	// Now, finally, go through all the channels and add ancestors of the "parent" channel to the $path,
	// and direct children to the list of $channels. Make sure we don't include any channels which
	// the user has unsubscribed to.
	foreach ($channelInfo as $channel) {
		if ($channel["lft"] > $curChannel["lft"] and $channel["rgt"] < $curChannel["rgt"] and $channel["depth"] == $curChannel["depth"] + 1 and empty($channel["unsubscribed"]))
			$channels[] = $channel;
		elseif ($channel["lft"] <= $curChannel["lft"] and $channel["rgt"] >= $curChannel["rgt"])
			$path[] = $channel;
	}

	// Store the currently selected channel in the session, so that it can be automatically selected
	// if "New conversation" is clicked.
	if (!empty($currentChannels)) ET::$session->store("searchChannelId", $currentChannels[0]);

	// Get the search string request value.
	$searchString = R("search");

	// Last, but definitely not least... perform the search!
	$search = ET::searchModel();
	$conversationIDs = $search->getConversationIDs($channelIds, $searchString, count($currentChannels) or !ET::$session->userId);

	// If this page was originally accessed at conversations/markAsRead/all?search=whatever (the
	// markAsRead method simply calls the index method), then mark the results as read.
	if ($this->controllerMethod == "markasread" and ET::$session->userId) {
		ET::conversationModel()->markAsRead($conversationIDs, ET::$session->userId);
	}

	$results = $search->getResults($conversationIDs);

	// Were there any errors? Show them as messages.
	if ($search->errorCount()) {
		$this->messages($search->errors(), "warning dismissable");
	}

	// Add fulltext keywords to be highlighted. Make sure we keep ones "in quotes" together.
	else $this->highlight($search->fulltext);

	// Pass on a bunch of data to the view.
	$this->data("results", $results);
	$this->data("limit", $search->limit);
	$this->data("showViewMoreLink", $search->areMoreResults());
	$this->data("channelPath", $path);
	$this->data("channelTabs", $channels);
	$this->data("currentChannels", $currentChannels);
	$this->data("channelInfo", $channelInfo);
	$this->data("channelSlug", $channelSlug = $channelSlug ? $channelSlug : "all");
	$this->data("searchString", $searchString);
	$this->data("fulltextString", implode(" ", $search->fulltext));

	// Construct a canonical URL and add to the breadcrumb stack.
	$slugs = array();
	foreach ($currentChannels as $channel) $slugs[] = $channelInfo[$channel]["slug"];
	$url = "conversations/".urlencode(($k = implode(" ", $slugs)) ? $k : "all").($searchString ? "?search=".urlencode($searchString) : "");
	$this->pushNavigation("conversations", "search", URL($url));
	$this->canonicalURL = URL($url, true);

	// If we're loading the page in full...
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

		// Update the user's last action.
		ET::memberModel()->updateLastAction("search");

		// Add a link to the RSS feed in the bar.
		// $this->addToMenu("meta", "feed", "<a href='".URL(str_replace("conversations/", "conversations/index.atom/", $url))."' id='feed'>".T("Feed")."</a>");

		$controls = ETFactory::make("menu");

		// Mark as read controls
		if (ET::$session->user) {
			$controls->add("markAllAsRead", "<a href='".URL("conversations/markAllAsRead/?token=".ET::$session->token."' id='control-markAllAsRead'><i class='icon-check'></i> ".T("Mark all as read")."</a>"));
			$controls->add("markListedAsRead", "<a href='".URL("conversations/$channelSlug/?search=".urlencode($searchString)."&markAsRead=1&token=".ET::$session->token."' id='control-markListedAsRead'><i class='icon-list'></i> ".T("Mark listed as read")."</a>"));
		}

		// Add the default gambits to the gambit cloud: gambit text => css class to apply.
		$gambits = array(
			"main" => array(
				T("gambit.sticky") => array("gambit-sticky", "icon-pushpin"),
			),
			"time" => array(
				T("gambit.order by newest") => array("gambit-orderByNewest", "icon-list-ol"),
				T("gambit.active last ? hours") => array("gambit-activeLastHours", "icon-time"),
				T("gambit.active last ? days") => array("gambit-activeLastDays", "icon-calendar"),
				T("gambit.active today") => array("gambit-activeToday", "icon-asterisk"),
				T("gambit.dead") => array("gambit-dead", "icon-remove"),
				T("gambit.locked") => array("gambit-locked", "icon-lock"),
			),
			"member" => array(
				T("gambit.author:").T("gambit.member") => array("gambit-author", "icon-user"),
				T("gambit.contributor:").T("gambit.member") => array("gambit-contributor", "icon-user"),
			),
			"replies" => array(
				T("gambit.has replies") => array("gambit-hasReplies", "icon-comment"),
				T("gambit.has >10 replies") => array("gambit-replies", "icon-comments"),
				T("gambit.order by replies") => array("gambit-orderByReplies", "icon-list-ol"),
			),
			"misc" => array(
				T("gambit.random") => array("gambit-random", "icon-random"),
				T("gambit.reverse") => array("gambit-reverse", "icon-exchange"),
			)
		);

		// Add some more personal gambits if there is a user logged in.
		if (ET::$session->user) {
			addToArrayString($gambits["main"], T("gambit.private"), array("gambit-private", "icon-envelope-alt"), 1);
			addToArrayString($gambits["main"], T("gambit.starred"), array("gambit-starred", "icon-star"), 2);
			addToArrayString($gambits["main"], T("gambit.draft"), array("gambit-draft", "icon-pencil"), 3);
			addToArrayString($gambits["main"], T("gambit.muted"), array("gambit-muted", "icon-eye-close"), 4);
			addToArrayString($gambits["time"], T("gambit.unread"), array("gambit-unread", "icon-inbox"), 0);
			addToArrayString($gambits["member"], T("gambit.author:").T("gambit.myself"), array("gambit-authorMyself", "icon-smile"), 0);
			addToArrayString($gambits["member"], T("gambit.contributor:").T("gambit.myself"), array("gambit-contributorMyself", "icon-smile"), 2);
		}

		$this->trigger("constructGambitsMenu", array(&$gambits));

		// Construct the gambits menu based on the above arrays.
		$gambitsMenu = ETFactory::make("menu");
		$linkPrefix = "conversations/".$channelSlug."/?search=".urlencode(((!empty($searchString) ? $searchString." + " : "")));

		foreach ($gambits as $section => $items) {
			foreach ($items as $gambit => $classes) {
				$gambitsMenu->add($classes[0], "<a href='".URL($linkPrefix.urlencode("#".$gambit))."' class='{$classes[0]}' data-gambit='$gambit'>".(!empty($classes[1]) ? "<i class='{$classes[1]}'></i> " : "")."$gambit</a>");
			}
			end($gambits);
			if ($section !== key($gambits)) $gambitsMenu->separator();
		}

		$this->data("controlsMenu", $controls);
		$this->data("gambitsMenu", $gambitsMenu);

		// Construct a list of keywords to use in the meta tags.
		$keywords = array();
		foreach ($channelInfo as $c) {
			if ($c["depth"] == 0) $keywords[] = strtolower($c["title"]);
		}

		// Add meta tags to the header.
		$this->addToHead("<meta name='keywords' content='".sanitizeHTML(($k = C("esoTalk.meta.keywords")) ? $k : implode(",", $keywords))."'>");
		list($lastKeyword) = array_splice($keywords, count($keywords) - 1, 1);
		$this->addToHead("<meta name='description' content='".sanitizeHTML(($d = C("esoTalk.meta.description")) ? $d
			: sprintf(T("forumDescription"), C("esoTalk.forumTitle"), implode(", ", $keywords), $lastKeyword))."'>");

		// If this is not technically the homepage (if it's a search page) the we don't want it to be indexed.
		if ($searchString) $this->addToHead("<meta name='robots' content='noindex, noarchive'>");

		// Add JavaScript language definitions and variables.
		$this->addJSLanguage("Starred", "Unstarred", "gambit.member", "gambit.more results", "Filter conversations", "Jump to last");
		$this->addJSVar("searchUpdateInterval", C("esoTalk.search.updateInterval"));
		$this->addJSVar("currentSearch", $searchString);
		$this->addJSVar("currentChannels", $currentChannels);
		$this->addJSFile("core/js/lib/jquery.cookie.js");
		$this->addJSFile("core/js/autocomplete.js");
		$this->addJSFile("core/js/search.js");

		// Add an array of channels in the form slug => id for the JavaScript to use.
		$channels = array();
		foreach ($channelInfo as $id => $c) $channels[$id] = $c["slug"];
		$this->addJSVar("channels", $channels);

		// Get a bunch of statistics...
		$queries = array(
			"post" => ET::SQL()->select("COUNT(*)")->from("post")->get(),
			"conversation" => ET::SQL()->select("COUNT(*)")->from("conversation")->get(),
			"member" => ET::SQL()->select("COUNT(*)")->from("member")->get()
		);
		$sql = ET::SQL();
		foreach ($queries as $k => $query) $sql->select("($query) AS $k");
		$stats = $sql->exec()->firstRow();

		// ...and show them in the footer.
		foreach ($stats as $k => $v) {
			$stat = Ts("statistic.$k", "statistic.$k.plural", number_format($v));
			if ($k == "member" and (C("esoTalk.members.visibleToGuests") or ET::$session->user)) $stat = "<a href='".URL("members")."'>$stat</a>";
			$this->addToMenu("statistics", "statistic-$k", $stat, array("before" => "statistic-online"));
		}

		$this->render("conversations/index");

	}

	// For a view, just render the results.
	elseif ($this->responseType === RESPONSE_TYPE_VIEW) {
		$this->render("conversations/results");
	}

	// For ajax, render the results, and also pass along the channels view.
	elseif ($this->responseType === RESPONSE_TYPE_AJAX) {
		$this->json("channels", $this->getViewContents("channels/tabs", $this->data));
		$this->render("conversations/results");
	}

	// For json, output the results as a json object.
	elseif ($this->responseType === RESPONSE_TYPE_JSON) {
		$this->json("results", $results);
		$this->render();
	}
}


/**
 * Given the channel slug from a request, work out which channels are selected, whether or not to include
 * descendant channels in the results, and construct a full list of channel IDs to consider when getting the
 * list a conversations.
 *
 * @param string $channelSlug The channel slug from the request.
 * @return array An array containing:
 * 		0 => a full list of channel information.
 * 		1 => the list of currently selected channel IDs.
 * 		2 => the full list of channel IDs to consider (including descendant channels of selected channels.)
 * 		3 => whether or not descendant channels are being included.
 */
protected function getSelectedChannels($channelSlug = "")
{
	// Get a list of all viewable channels.
	$channelInfo = ET::channelModel()->get();

	// Get a list of the currently selected channels.
	$currentChannels = array();
	$includeDescendants = true;

	if (!empty($channelSlug)) {
		$channels = explode(" ", $channelSlug);

		// If the first channel is empty (ie. the URL is conversations/+channel-slug), set a flag
		// to turn off the inclusion of descendant channels when considering conversations.
		if ($channels[0] == "") {
			$includeDescendants = false;
			array_shift($channels);
		}

		// Go through the channels and add their IDs to the list of current channels.
		foreach ($channels as $channel) {
			foreach ($channelInfo as $id => $c) {
				if ($c["slug"] == $channel) {
					$currentChannels[] = $id;
					break;
				}
			}
		}
	}

	// Get an array of channel IDs to consider when getting the list of conversations.
	// If we're not including descendants, this is the same as the list of current channels.
	if (!$includeDescendants) {
		$channelIds = $currentChannels;
	}

	// Otherwise, loop through all the channels and add IDs of descendants. Make sure we don't include
	// any channels which the user has unsubscribed to.
	else {
		$channelIds = array();
		foreach ($currentChannels as $id) {
			$channelIds[] = $id;
			$rootUnsubscribed = !empty($channelInfo[$id]["unsubscribed"]);
			foreach ($channelInfo as $channel) {
				if ($channel["lft"] > $channelInfo[$id]["lft"] and $channel["rgt"] < $channelInfo[$id]["rgt"] and (empty($channel["unsubscribed"]) or $rootUnsubscribed))
					$channelIds[] = $channel["channelId"];
			}
		}
	}

	// If by now we don't have any channel IDs, we must be viewing "all channels." In this case,
	// add all the channels.
	if (empty($channelIds)) {
		foreach ($channelInfo as $id => $channel) {
			if (empty($channel["unsubscribed"])) $channelIds[] = $id;
		}
	}

	return array($channelInfo, $currentChannels, $channelIds, $includeDescendants);
}


/**
 * Mark all conversations as read and return to the index page.
 *
 * @return void
 */
public function action_markAllAsRead()
{
	// Update the user's preferences.
	ET::$session->setPreferences(array("markedAllConversationsAsRead" => time()));

	// For a normal response, redirect to the conversations page.
	if ($this->responseType === RESPONSE_TYPE_DEFAULT) $this->redirect(URL("conversations"));

	// For an ajax response, just pretend this is a normal search response.
	$this->index();
}


/**
 * Perform a search and mark the results as read.
 *
 * @return void
 */
public function action_markAsRead($channelSlug = false)
{
	// We simply let the index method handle this, because we want to perform a search like normal
	// but then mark the results as read before we display them. The index method will check if the 
	// original method called on the controller was "markAsRead" and if it is, mark the results as
	// read.
	$this->action_index($channelSlug);
}


/**
 * Return updated HTML for each row in the conversations table, and indicate if there are new results for the
 * specified channel and search query.
 *
 * @param string $channelSlug The channel slug.
 * @param string $query The search query.
 * @return void
 */
public function action_update($channelSlug = "", $query = "")
{
	// This must be done as an AJAX request.
	$this->responseType = RESPONSE_TYPE_AJAX;

	list($channelInfo, $currentChannels, $channelIds, $includeDescendants) = $this->getSelectedChannels($channelSlug);
	$search = ET::searchModel();

	// Work out which conversations we need to get updated details for (according to the input value.)
	$conversationIds = explode(",", R("conversationIds"));

	// Make sure they are all integers.
	foreach ($conversationIds as $k => $v) {
		if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
	}

	if (!count($conversationIds)) return;
	$conversationIds = array_slice((array)$conversationIds, 0, 20);

	// Work out if there are any new results for this channel/search query.

	// If the "random" gambit is in the search string, then don't go any further (because the results will
	// obviously differ!)
	$random = false;
	$terms = $query ? explode("+", strtolower(str_replace("-", "+!", trim($query, " +-")))) : array();
	foreach ($terms as $v) {
		if (trim($v) == T("gambit.random"))	$random = true;
	}

	if (!$random) {

		// TODO: set a #limit gambit for 20 results, because we only check for differences in the first 20

		// Get a list of conversation IDs for the channel/query.
		$newConversationIds = $search->getConversationIDs($channelIds, $query, count($currentChannels) or !ET::$session->userId);
		$newConversationIds = array_slice((array)$newConversationIds, 0, 20);

		// Get the difference of the two sets of conversationId's.
		$diff = array_diff((array)$newConversationIds, (array)$conversationIds);
		if (count($diff)) $this->message(sprintf(T("message.newSearchResults"), "javascript:ETSearch.showNewActivity();void(0)"), array("id" => "newSearchResults"));

	}

	// Add fulltext keywords to be highlighted. Make sure we keep ones "in quotes" together.
	$this->highlight($search->fulltext);
	$fulltextString = implode(" ", $search->fulltext);

	// Get the full result data for these conversations, and construct an array of rendered conversation rows.
	$results = $search->getResults($conversationIds, true);
	$rows = array();
	foreach ($results as $conversation) {
		$rows[$conversation["conversationId"]] = $this->getViewContents("conversations/conversation", array(
			"conversation" => $conversation,
			"channelInfo" => $channelInfo,
			"fulltextString" => $fulltextString
		));
	}

	// Add that to the response.
	$this->json("conversations", $rows);

	$this->render();
}


/**
 * Add fulltext keywords to be highlighted. Make sure we keep ones "in quotes" together.
 * 
 * @param array $terms An array of words to highlight.
 * @return void
 */
protected function highlight($terms)
{
	$words = array();
	foreach ($terms as $term) {
		if (preg_match_all('/"(.+?)"/', $term, $matches)) {
			$words[] = $matches[1];
			$term = preg_replace('/".+?"/', '', $term);
		}
		$words = array_unique(array_merge($words, explode(" ", $term)));
	}
	ET::$session->store("highlight", $words);
}

}
