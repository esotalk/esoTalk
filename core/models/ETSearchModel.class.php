<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * A model which provides functions to perform searches for conversations. Handles the implementation
 * of gambits, and does search optimization.
 *
 * Searches are performed by the following steps:
 * 1. Call getConversationIDs with a list of channel IDs to show results from and a search string.
 * 2. The search string is parsed and split into terms. When a term is matched to a gambit, the
 *    gambit's callback function is called.
 * 3. Callback functions add conversation ID filters to narrow the range of conversations being
 *    searched, or may alter other parts of the search query.
 * 4. Using the applied ID filters, a final list of conversation IDs is retrieved and returned.
 * 5. Call getResults with this list, and full details are retireved for each of the conversations.
 *
 * @package esoTalk
 */
class ETSearchModel extends ETModel {


/**
 * An array of functional gambits. Each gambit is an array(callback, condition)
 * @var array
 * @see addGambit
 */
protected static $gambits = array();


/**
 * An array of aliases. An alias is a string of text which is just shorthand for a more complex
 * gambit. Each alias is an array(term, replacement term)
 * @var array
 * @see addAlias
 */
protected static $aliases = array();


/**
 * Whether or not there are more results for the most recent search than what was returned.
 * @var bool
 */
protected $areMoreResults = false;


/**
 * The SQL query object used to construct a query that retrieves a list of matching conversation IDs.
 * @var ETSQLQuery
 */
public $sql;


/**
 * An array of converastion ID filters that should be run before querying the conversations table
 * for a final list of conversation IDs.
 * @var array
 * @see addIDFilter
 */
protected $idFilters = array();


/**
 * An array of fields to order the conversation IDs by.
 * @var array
 */
protected $orderBy = array();


/**
 * Whether or not the direction in the $orderBy fields should be reversed.
 * @var bool
 */
public $orderReverse = false;


/**
 * Whether or not the direction in the $orderBy fields should be reversed.
 * @var bool
 */
public $limit = false;


/**
 * Whether or not to include muted conversations in the results.
 * @var bool
 */
public $includeMuted = false;


/**
 * An array of fulltext keywords to filter the results by.
 * @var array
 */
public $fulltext = array();


/**
 * Class constructor. Sets up the inherited model functions to handle data in the search table
 * (used for logging search activity -> flood control.)
 *
 * @return void
 */
public function __construct()
{
	parent::__construct("search");
}


/**
 * Add a gambit to the collection. When a search term is matched to a gambit, the specified
 * callback function will be called. A match is determined by the return value of running
 * $condition through eval().
 *
 * @param string $condition The condition to run through eval() to determine a match.
 * 		$term represents the search term, in lowercase, in the eval() context. The condition
 * 		should return a boolean value: true means a match, false means no match.
 * 		Example: return $term == "sticky";
 * @param array $function The function to call if the gambit is matched. Function will be called
 * 		with parameters callback($sender, $term, $negate).
 * @return void
 */
public static function addGambit($condition, $function)
{
	self::$gambits[] = array($condition, $function);
}


/**
 * Add an alias for another gambit to the collection. When a search term is matched
 * to an alias, it will be interpreted as $realTerm.
 *
 * @param string $term The alias term.
 * @param string $realTerm The replacement term.
 * @return void
 */
public static function addAlias($term, $realTerm)
{
	self::$aliases[$term] = $realTerm;
}


/**
 * Add an SQL query to be run before the conversations table is queried for the final list of
 * conversation IDs. The query should return a list of conversation IDs; the results then will be
 * limited to conversations matching this list of IDs.
 *
 * See some of the default gambits for examples.
 *
 * @param ETSQLQuery $sql The SQL query that will return a list of matching conversation IDs.
 * @param bool $negate If set to true, the returned conversation IDs will be blacklisted.
 * @return void
 */
public function addIDFilter($sql, $negate = false)
{
	$this->idFilters[] = array($sql, $negate);
}


/**
 * Add a term to include in a fulltext search.
 *
 * @param string $term The term.
 * @return void
 */
public function fulltext($term)
{
	$this->fulltext[] = $term;
}


/**
 * Apply an order to the search results. This function will ensure that a direction (ASC|DESC) is
 * at the end.
 *
 * @param string $order The field to order the results by.
 * @return void
 */
public function orderBy($order)
{
	$direction = substr($order, strrpos($order, " ") + 1);
	if ($direction != "ASC" and $direction != "DESC") $order .= " ASC";
	$this->orderBy[] = $order;
}


/**
 * Apply a custom limit to the number of search results returned.
 *
 * @param int $limit The limit.
 * @return void
 */
public function limit($limit)
{
	$this->limit = $limit;
}


/**
 * Reset instance variables.
 *
 * @return void
 */
protected function reset()
{
	$this->resultCount = 0;
	$this->areMoreResults = false;
	$this->sql = null;
	$this->idFilters = array();
	$this->orderBy = array();
	$this->orderReverse = false;
	$this->limit = false;
	$this->includeMuted = false;
	$this->fulltext = array();
}


/**
 * Determines whether or not the user is "flooding" the search system, based on the number of searches
 * they have performed in the last minute.
 *
 * @return bool|int If the user is not flooding, returns false, but if they are, returned the number
 * 		of seconds until they can perform another search.
 */
public function isFlooding()
{
	if (C("esoTalk.search.searchesPerMinute") <= 0) return false;
	$time = time();
	$period = 60;

	// If we have a record of their searches in the session, check how many searches they've performed in the last minute.
	$searches = ET::$session->get("searches");
	if (!empty($searches)) {

		// Clean anything older than $period seconds out of the searches array.
		foreach ($searches as $k => $v) {
			if ($v < $time - $period) unset($searches[$k]);
		}

		// Have they performed >= [searchesPerMinute] searches in the last minute? If so, they are flooding.
		if (count($searches) >= C("esoTalk.search.searchesPerMinute"))
			return $period - $time + min($searches);
	}

	// However, if we don't have a record in the session, query the database searches table.
	else {

		// Get the user's IP address.
		$ip = (int)ip2long(ET::$session->ip);

		// Have they performed >= $config["searchesPerMinute"] searches in the last minute?
		$sql = ET::SQL()
			->select("COUNT(ip)")
			->from("search")
			->where("type='conversations'")
			->where("ip=:ip")->bind(":ip", $ip)
			->where("time>:time")->bind(":time", $time - $period);

		if ($sql->exec()->result() >= C("esoTalk.search.searchesPerMinute"))
			return $period;

		// Log this search in the searches table.
		ET::SQL()->insert("search")->set("type", "conversations")->set("ip", $ip)->set("time", $time)->exec();

		// Proactively clean the searches table of searches older than $period seconds.
		ET::SQL()->delete()->from("search")->where("type", "conversations")->where("time<:time")->bind(":time", $time - $period)->exec();
	}

	// Log this search in the session array.
	$searches[] = $time;
	ET::$session->store("searches", $searches);

	return false;
}


/**
 * Deconstruct a search string and return a list of conversation IDs that fulfill it.
 *
 * @param array $channelIDs A list of channel IDs to include results from.
 * @param string $searchString The search string to deconstruct and find matching conversations.
 * @param bool $orderBySticky Whether or not to put stickied conversations at the top.
 * @return array|bool An array of matching conversation IDs, or false if there are none.
 */
public function getConversationIDs($channelIDs = array(), $searchString = "", $orderBySticky = false)
{
	$this->reset();

	$this->trigger("getConversationIDsBefore", array(&$channelIDs, &$searchString, &$orderBySticky));

	if ($searchString and ($seconds = $this->isFlooding())) {
		$this->error("search", sprintf(T("message.waitToSearch"), $seconds));
		return false;
	}

	// Initialize the SQL query that will return the resulting conversation IDs.
	$this->sql = ET::SQL()->select("c.conversationId")->from("conversation c");

	// Only get conversations in the specified channels.
	if ($channelIDs) {
		$this->sql->where("c.channelId IN (:channelIds)")->bind(":channelIds", $channelIDs);
	}

	// Process the search string into individial terms. Replace all "-" signs with "+!", and then
	// split the string by "+". Negated terms will then be prefixed with "!". Only keep the first
	// 5 terms, just to keep the load on the database down!
	$terms = !empty($searchString) ? explode("+", strtolower(str_replace("-", "+!", trim($searchString, " +-")))) : array();
	$terms = array_slice(array_filter($terms), 0, 5);

	// Take each term, match it with a gambit, and execute the gambit's function.
	foreach ($terms as $term) {

		// Are we dealing with a negated search term, ie. prefixed with a "!"?
		$term = trim($term);
		if ($negate = ($term[0] == "!")) $term = trim($term, "! ");

		if ($term[0] == "#") {
			$term = ltrim($term, "#");

			// If the term is an alias, translate it into the appropriate gambit.
			if (array_key_exists($term, self::$aliases)) $term = self::$aliases[$term];

			// Find a matching gambit by evaluating each gambit's condition, and run its callback function.
			foreach (self::$gambits as $gambit) {
				list($condition, $function) = $gambit;
				if (eval($condition)) {
					call_user_func_array($function, array(&$this, $term, $negate));
					continue 2;
				}
			}
		}

		// If we didn't find a gambit, use this term as a fulltext term.
		if ($negate) $term = "-".str_replace(" ", " -", $term);
		$this->fulltext($term);
	}

	// If an order for the search results has not been specified, apply a default.
	// Order by sticky and then last post time.
	if (!count($this->orderBy)) {
		if ($orderBySticky) $this->orderBy("c.sticky DESC");
		$this->orderBy("c.lastPostTime DESC");
	}

	// If we're not including muted conversations, add a where predicate to the query to exclude them.
	if (!$this->includeMuted and ET::$session->user) {
		$q = ET::SQL()->select("conversationId")->from("member_conversation")->where("type='member'")->where("id=:memberIdMuted")->where("muted=1")->get();
		$this->sql->where("conversationId NOT IN ($q)")->bind(":memberIdMuted", ET::$session->userId);
	}

	// Now we need to loop through the ID filters and run them one-by-one. When a query returns a selection
	// of conversation IDs, subsequent queries are restricted to filtering those conversation IDs,
	// and so on, until we have a list of IDs to pass to the final query.
	$goodConversationIDs = array();
	$badConversationIDs = array();
	$idCondition = "";
	foreach ($this->idFilters as $v) {
		list($sql, $negate) = $v;

		// Apply the list of good IDs to the query.
		$sql->where($idCondition);

		// Get the list of conversation IDs so that the next condition can use it in its query.
		$result = $sql->exec();
		$ids = array();
		while ($row = $result->nextRow()) $ids[] = (int)reset($row);

		// If this condition is negated, then add the IDs to the list of bad conversations.
		// If the condition is not negated, set the list of good conversations to the IDs, provided there are some.
		if ($negate) $badConversationIDs = array_merge($badConversationIDs, $ids);
		elseif (count($ids)) $goodConversationIDs = $ids;
		else return false;

		// Strip bad conversation IDs from the list of good conversation IDs.
		if (count($goodConversationIDs)) {
			$goodConversationIds = array_diff($goodConversationIDs, $badConversationIDs);
			if (!count($goodConversationIDs)) return false;
		}

		// This will be the condition for the next query that restricts or eliminates conversation IDs.
		if (count($goodConversationIDs))
			$idCondition = "conversationId IN (".implode(",", $goodConversationIDs).")";
		elseif (count($badConversationIDs))
			$idCondition = "conversationId NOT IN (".implode(",", $badConversationIDs).")";
	}

	// Reverse the order if necessary - swap DESC and ASC.
	if ($this->orderReverse) {
		foreach ($this->orderBy as $k => $v)
			$this->orderBy[$k] = strtr($this->orderBy[$k], array("DESC" => "ASC", "ASC" => "DESC"));
	}

	// Now check if there are any fulltext keywords to filter by.
	if (count($this->fulltext)) {

		// Run a query against the posts table to get matching conversation IDs.
		$fulltextString = implode(" ", $this->fulltext);
		$result = ET::SQL()
			->select("DISTINCT conversationId")
			->from("post")
			->where("MATCH (title, content) AGAINST (:fulltext IN BOOLEAN MODE)")
			->where($idCondition)
			->orderBy("MATCH (title, content) AGAINST (:fulltextOrder) DESC")
			->bind(":fulltext", $fulltextString)
			->bind(":fulltextOrder", $fulltextString)
			->exec();
		$ids = array();
		while ($row = $result->nextRow()) $ids[] = reset($row);

		// Change the ID condition to this list of matching IDs, and order by relevance.
		if (count($ids)) $idCondition = "conversationId IN (".implode(",", $ids).")";
		else return false;
		$this->orderBy = array("FIELD(c.conversationId,".implode(",", $ids).")");
	}

	// Set a default limit if none has previously been set.
	if (!$this->limit) $this->limit = C("esoTalk.search.limit");

	// Finish constructing the final query using the ID whitelist/blacklist we've come up with.
	// Get one more result than we'll actually need so we can see if there are "more results."
	if ($idCondition) $this->sql->where($idCondition);
	$this->sql->orderBy($this->orderBy)->limit($this->limit + 1);

	// Make sure conversations that the user isn't allowed to see are filtered out.
	ET::conversationModel()->addAllowedPredicate($this->sql);

	// Execute the query, and collect the final set of conversation IDs.
	$result = $this->sql->exec();
	$conversationIDs = array();
	while ($row = $result->nextRow()) $conversationIDs[] = reset($row);

	// If there's one more result than we actually need, indicate that there are "more results."
	if (count($conversationIDs) == $this->limit + 1) {
		array_pop($conversationIDs);
		if ($this->limit < C("esoTalk.search.limitMax")) $this->areMoreResults = true;
	}

	return count($conversationIDs) ? $conversationIDs : false;
}


/**
 * Get a full list of conversation details for a list of conversation IDs.
 *
 * @param array $conversationIDs The list of conversation IDs to fetch details for.
 * @param bool $checkForPermission Whether or not to add a check onto the query to make sure the
 * 		user has permission to view all of the conversations.
 */
public function getResults($conversationIDs, $checkForPermission = false)
{
	// Construct a query to get details for all of the specified conversations.
	$sql = ET::SQL()
		->select("s.*") // Select the status fields first so that the conversation fields take precedence.
		->select("c.*")
		->select("sm.username", "startMember")
		->select("sm.avatarFormat", "startMemberAvatarFormat")
		->select("lpm.username", "lastPostMember")
		->select("lpm.email", "lastPostMemberEmail")
		->select("lpm.avatarFormat", "lastPostMemberAvatarFormat")
		->select("IF((IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>:markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.countPosts)),(c.countPosts - IF(s.lastRead IS NULL,0,s.lastRead)),0)", "unread")
		->from("conversation c")
		->from("member_conversation s", "s.conversationId=c.conversationId AND s.type='member' AND s.id=:memberId", "left")
		->from("member sm", "c.startMemberId=sm.memberId", "left")
		->from("member lpm", "c.lastPostMemberId=lpm.memberId", "left")
		->from("channel ch", "c.channelId=ch.channelId", "left")
		->bind(":markedAsRead", ET::$session->preference("markedAllConversationsAsRead"))
		->bind(":memberId", ET::$session->userId);

	// If we need to, filter out all conversations that the user isn't allowed to see.
	if ($checkForPermission) ET::conversationModel()->addAllowedPredicate($sql);

	// Add a labels column to the query.
	ET::conversationModel()->addLabels($sql);

	// Limit the results to the specified conversation IDs
	$sql->where("c.conversationId IN (:conversationIds)")->orderBy("FIELD(c.conversationId,:conversationIdsOrder)");
	$sql->bind(":conversationIds", $conversationIDs, PDO::PARAM_INT);
	$sql->bind(":conversationIdsOrder", $conversationIDs, PDO::PARAM_INT);

	$this->trigger("beforeGetResults", array(&$sql));

	// Execute the query and put the details of the conversations into an array.
	$result = $sql->exec();
	$results = array();
	$model = ET::conversationModel();

	while ($row = $result->nextRow()) {

		// Expand the comma-separated label flags into a workable array of active labels.
		$row["labels"] = $model->expandLabels($row["labels"]);

		$row["replies"] = max(0, $row["countPosts"] - 1);
		$results[] = $row;

	}

	$this->trigger("afterGetResults", array(&$results));

	return $results;
}


/**
 * Returns whether or not there are more results for the most recent search than were returned.
 *
 * @return bool
 */
public function areMoreResults()
{
	return $this->areMoreResults;
}


/**
 * Strip a gambit from a search string. This is useful when constructing the 'view more' link in 
 * the results, where we need to remove the existing #limit gambit and add a new one.
 *
 * @param string $searchString The search string.
 * @param string $condition The condition to run through eval() to determine a match.
 * 		$term represents the search term, in lowercase, in the eval() context. The condition
 * 		should return a boolean value: true means a match, false means no match.
 * 		Example: return $term == "sticky";
 * @return string The new search string.
 */
public function removeGambit($searchString, $condition)
{
	// Process the search string into individial terms. Replace all "-" signs with "+!", and then
	// split the string by "+". Negated terms will then be prefixed with "!".
	$terms = !empty($searchString) ? explode("+", strtolower(str_replace("-", "+!", trim($searchString, " +-")))) : array();

	// Take each term, match it with a gambit, and execute the gambit's function.
	foreach ($terms as $k => $term) {

		$term = $terms[$k] = trim($term);

		if ($term[0] == "#") {
			$term = ltrim($term, "#");

			// If the term is an alias, translate it into the appropriate gambit.
			if (array_key_exists($term, self::$aliases)) $term = self::$aliases[$term];

			// Find a matching gambit by evaluating each gambit's condition, and run its callback function.
			if (eval($condition)) {
				unset($terms[$k]);
				continue;
			}
		}
	}

	return implode(" + ", $terms);
}


/**
 * The "unread" gambit callback. Applies a filter to fetch only unread conversations.
 *
 * @param ETSearchModel $search The search model.
 * @param string $term The gambit term (in this case, will simply be "unread").
 * @param bool $negate Whether or not the gambit is negated.
 * @return void
 *
 * @todo Make negation work on this gambit. Probably requires some kind of "OR" functionality, so that
 * 		we can get conversations which:
 * 		- are NOT in conversationIds with a lastRead status less than the number of posts in the conversation
 * 		- OR which have a lastPostTime less than the markedAsRead time.
 */
public static function gambitUnread(&$search, $term, $negate)
{
	if (!ET::$session->user) return false;

	$q = ET::SQL()
		->select("c2.conversationId")
		->from("conversation c2")
		->from("member_conversation s2", "c2.conversationId=s2.conversationId AND s2.type='member' AND s2.id=:gambitUnread_memberId", "left")
		->where("s2.lastRead>=c2.countPosts")
		->get();

	$search->sql
		->where("c.conversationId NOT IN ($q)")
		->where("c.lastPostTime>=:gambitUnread_markedAsRead")
		->bind(":gambitUnread_memberId", ET::$session->userId)
		->bind(":gambitUnread_markedAsRead", ET::$session->preference("markedAllConversationsAsRead"));
}


/**
 * The "starred" gambit callback. Applies a filter to fetch only starred conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitStarred(&$search, $term, $negate)
{
	if (!ET::$session->user) return;

	$sql = ET::SQL()
		->select("DISTINCT conversationId")
		->from("member_conversation")
		->where("type='member'")
		->where("id=:memberId")
		->where("starred=1")
		->bind(":memberId", ET::$session->userId);

	$search->addIDFilter($sql, $negate);
}


/**
 * The "private" gambit callback. Applies a filter to fetch only private conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitPrivate(&$search, $term, $negate)
{
	$search->sql->where("c.private=".($negate ? "0" : "1"));
}


/**
 * The "muted" gambit callback. Applies a filter to fetch only muted conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitMuted(&$search, $term, $negate)
{
	if (!ET::$session->user or $negate) return;
	$search->includeMuted = true;

	$sql = ET::SQL()
		->select("DISTINCT conversationId")
		->from("member_conversation")
		->where("type='member'")
		->where("id=:memberId")
		->where("muted=1")
		->bind(":memberId", ET::$session->userId);

	$search->addIDFilter($sql);
}


/**
 * The "draft" gambit callback. Applies a filter to fetch only conversations which the user has a
 * draft in.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitDraft(&$search, $term, $negate)
{
	if (!ET::$session->user) return;
	$sql = ET::SQL()
		->select("DISTINCT conversationId")
		->from("member_conversation")
		->where("type='member'")
		->where("id=:memberId")
		->where("draft IS NOT NULL")
		->bind(":memberId", ET::$session->userId);

	$search->addIDFilter($sql, $negate);
}


/**
 * The "active" gambit callback. Applies a filter to fetch only conversations which have been active
 * in a certain period of time.
 *
 * @see gambitUnread for parameter descriptions.
 */
public function gambitActive(&$search, $term, $negate)
{
	// Multiply the "amount" part (b) of the regular expression matches by the value of the "unit" part (c).
	$search->matches["b"] = (int)$search->matches["b"];
	switch ($search->matches["c"]) {
		case T("gambit.minute"): $search->matches["b"] *= 60; break;
		case T("gambit.hour"): $search->matches["b"] *= 3600; break;
		case T("gambit.day"): $search->matches["b"] *= 86400; break;
		case T("gambit.week"): $search->matches["b"] *= 604800; break;
		case T("gambit.month"): $search->matches["b"] *= 2626560; break;
		case T("gambit.year"): $search->matches["b"] *= 31536000;
	}

	// Set the "quantifier" part (a); default to <= (i.e. "last").
	$search->matches["a"] = (!$search->matches["a"] or $search->matches["a"] == T("gambit.last")) ? "<=" : $search->matches["a"];

	// If the gambit is negated, use the inverse of the selected quantifier.
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<";
		}
	}

	// Apply the condition and force use of an index.
	$search->sql->where("UNIX_TIMESTAMP() - {$search->matches["b"]} {$search->matches["a"]} c.lastPostTime");
	$search->sql->useIndex("conversation_lastPostTime");
}


/**
 * The "author" gambit callback. Applies a filter to fetch only conversations which were started by
 * a particular member.
 *
 * @see gambitUnread for parameter descriptions.
 * @todo Somehow make the use of this gambit trigger the switching of the "last post" column in the
 * 		results table with a "started by" column.
 */
public static function gambitAuthor(&$search, $term, $negate)
{
	// Get the name of the member.
	$term = trim(str_replace("\xc2\xa0", " ", substr($term, strlen(T("gambit.author:")))));

	// If the user is referring to themselves, then we already have their member ID.
	if ($term == T("gambit.myself")) $q = (int)ET::$session->userId;

	// Otherwise, make a query to find the member ID of the specified member name.
	else {
		$q = ET::SQL()->select("memberId")->from("member")->where("username=:username")->bind(":username", $term)->get();
	}

	// Apply the condition.
	$search->sql->where("c.startMemberId".($negate ? " NOT" : "")." IN ($q)");
}


/**
 * The "contributor" gambit callback. Applies a filter to fetch only conversations which contain posts
 * by a particular member.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitContributor(&$search, $term, $negate)
{
	// Get the name of the member.
	$term = trim(str_replace("\xc2\xa0", " ", substr($term, strlen(T("gambit.contributor:")))));

	// If the user is referring to themselves, then we already have their member ID.
	if ($term == T("gambit.myself")) $q = (int)ET::$session->userId;

	// Otherwise, make a query to find the member ID of the specified member name.
	else {
		$q = ET::SQL()->select("memberId")->from("member")->where("username=:username")->bind(":username", $term)->get();
	}

	// Apply the condition.
	$sql = ET::SQL()
		->select("DISTINCT conversationId")
		->from("post")
		->where("memberId IN ($q)");
	$search->addIDFilter($sql, $negate);
}


/**
 * The "limit" gambit callback. Specifies the number of results to display.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitLimit(&$search, $term, $negate)
{
	if ($negate) return;

	// Get the number of results they want.
	$limit = (int)trim(substr($term, strlen(T("gambit.limit:"))));
	$limit = max(1, $limit);
	if (($max = C("esoTalk.search.limitMax")) > 0) $limit = min($max, $limit);

	$search->limit($limit);
}


/**
 * The "replies" gambit callback. Applies a filter to fetch only conversations which have a certain
 * amount of replies.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitHasNReplies(&$search, $term, $negate)
{
	// Work out which quantifier to use; default to "=".
	$search->matches["a"] = (!$search->matches["a"]) ? "=" : $search->matches["a"];

	// If the gambit is negated, use the inverse of the quantifier.
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<"; break;
			case "=": $search->matches["a"] = "!=";
		}
	}

	// Increase the amount by one as we are checking replies, but the column in the conversations
	// table is a post count (it includes the original post.)
	$search->matches["b"]++;

	// Apply the condition.
	$search->sql->where("countPosts {$search->matches["a"]} {$search->matches["b"]}");
}


/**
 * The "order by replies" gambit callback. Orders the results by the number of replies they have.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitOrderByReplies(&$search, $term, $negate)
{
	$search->orderBy("c.countPosts ".($negate ? "ASC" : "DESC"));
	$search->sql->useIndex("conversation_countPosts");
}


/**
 * The "order by newest" gambit callback. Orders the results by their start time.
 *
 * @see gambitUnread for parameter descriptions.
 * @todo Somehow make the use of this gambit trigger the switching of the "last post" column in the
 * 		results table with a "started by" column.
 */
public static function gambitOrderByNewest(&$search, $term, $negate)
{
	$search->orderBy("c.startTime ".($negate ? "ASC" : "DESC"));
	$search->sql->useIndex("conversation_startTime");
}


/**
 * The "sticky" gambit callback. Applies a filter to fetch only stickied conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitSticky(&$search, $term, $negate)
{
	$search->sql->where("sticky=".($negate ? "0" : "1"));
}


/**
 * The "random" gambit callback. Orders conversations randomly.
 *
 * @see gambitUnread for parameter descriptions.
 * @todo Make this not horrendously slow on large forums. For now there is a config option to disable
 * 		this gambit.
 */
public static function gambitRandom(&$search, $term, $negate)
{
	if (!$negate) $search->orderBy("RAND()");
}


/**
 * The "reverse" gambit callback. Reverses the order of conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitReverse(&$search, $term, $negate)
{
	if (!$negate) $search->orderReverse = true;
}


/**
 * The "locked" gambit callback. Applies a filter to fetch only locked conversations.
 *
 * @see gambitUnread for parameter descriptions.
 */
public static function gambitLocked(&$search, $term, $negate)
{
	$search->sql->where("locked=".($negate ? "0" : "1"));
}


}

// Add default gambits.
ETSearchModel::addGambit('return $term == strtolower(T("gambit.starred"));', array("ETSearchModel", "gambitStarred"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.muted"));', array("ETSearchModel", "gambitMuted"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.draft"));', array("ETSearchModel", "gambitDraft"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.private"));', array("ETSearchModel", "gambitPrivate"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.sticky"));', array("ETSearchModel", "gambitSticky"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.locked"));', array("ETSearchModel", "gambitLocked"));
ETSearchModel::addGambit('return strpos($term, strtolower(T("gambit.author:"))) === 0;', array("ETSearchModel", "gambitAuthor"));
ETSearchModel::addGambit('return strpos($term, strtolower(T("gambit.contributor:"))) === 0;', array("ETSearchModel", "gambitContributor"));
ETSearchModel::addGambit('return preg_match(T("gambit.gambitActive"), $term, $this->matches);', array("ETSearchModel", "gambitActive"));
ETSearchModel::addGambit('return preg_match(T("gambit.gambitHasNReplies"), $term, $this->matches);', array("ETSearchModel", "gambitHasNReplies"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.order by replies"));', array("ETSearchModel", "gambitOrderByReplies"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.order by newest"));', array("ETSearchModel", "gambitOrderByNewest"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.unread"));', array("ETSearchModel", "gambitUnread"));
ETSearchModel::addGambit('return $term == strtolower(T("gambit.reverse"));', array("ETSearchModel", "gambitReverse"));
ETSearchModel::addGambit('return strpos($term, strtolower(T("gambit.limit:"))) === 0;', array("ETSearchModel", "gambitLimit"));

if (!C("esoTalk.search.disableRandomGambit"))
	ETSearchModel::addGambit('return $term == strtolower(T("gambit.random"));', array("ETSearchModel", "gambitRandom"));


// Add default aliases.
ETSearchModel::addAlias(T("gambit.active today"), T("gambit.active 1 day"));
ETSearchModel::addAlias(T("gambit.has replies"), T("gambit.has >0 replies"));
ETSearchModel::addAlias(T("gambit.has no replies"), T("gambit.has 0 replies"));
ETSearchModel::addAlias(T("gambit.dead"), T("gambit.active >30 day"));