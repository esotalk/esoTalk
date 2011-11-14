<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Feed controller: builds a list of items to be outputted as an RSS feed.

if (!defined("IN_ESOTALK")) exit;

class FeedController extends Controller {

// Feed data variables, outputted in the view.
var $items = array();
var $pubDate = "";
var $title = "";
var $description = "";
var $link = "";

function init()
{
	global $language, $config, $messages;
	
	// Change the root view so that the wrapper is not outputted.
	$this->esoTalk->view = "feed.php";
	header("Content-type: text/xml; charset={$language["charset"]}");
	
	if ($return = $this->fireEvent("init")) return;
	
	// Work out what type of feed we're doing, based on the URL:
	// conversation/[id] -> fetch the posts in conversation [id].
	// default -> fetch the most recent posts over the whole forum.
	switch (@$_GET["q2"]) {
	
		// Fetch the posts in a specific conversation.
		case "conversation":
		
			// Get the conversation details.
			$conversationId = (int)$_GET["q3"];
			if (!$conversationId or !($conversation = $this->esoTalk->db->fetchAssoc("SELECT c.conversationId AS id, c.title AS title, c.slug AS slug, c.private AS private, c.posts AS posts, c.startMember AS startMember, c.lastActionTime AS lastActionTime, GROUP_CONCAT(t.tag ORDER BY t.tag ASC SEPARATOR ', ') AS tags FROM " . config("esoTalk.database.prefix") . "conversations c LEFT JOIN " . config("esoTalk.database.prefix") . "tags t USING (conversationId) WHERE c.conversationId=$conversationId GROUP BY c.conversationId")))
				$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
							
			// Do we need authentication to view this conversation (ie. is it private or a draft)?
			if ($conversation["private"] or $conversation["posts"] == 0) {
				
				// Try to login with provided credentials.
				if (isset($_SERVER["PHP_AUTH_USER"])) $this->esoTalk->login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
				
				// Still not logged in? Ask them again!
				if (!$this->esoTalk->user) {
					header('WWW-Authenticate: Basic realm="esoTalk RSS feed"');
				    header('HTTP/1.0 401 Unauthorized');
					$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
				}
				
				// We're logged in now. So, is this member actually allowed in this conversation?
				if (!($conversation["startMember"] == $this->esoTalk->user["memberId"]
					or ($conversation["posts"] > 0 and (!$conversation["private"] or $this->esoTalk->db->result("SELECT allowed FROM " . config("esoTalk.database.prefix") . "status WHERE conversationId=$conversationId AND (memberId={$this->esoTalk->user["memberId"]} OR memberId='{$this->esoTalk->user["account"]}')", 0))))) {
					// Nuh-uh. Get OUT!!!
					$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
				}
			}
			
			// Past this point, the user is allowed to view the conversation.
			// Set the title, link, description, etc.
			$this->title = "{$conversation["title"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"] . makeLink($conversation["id"], $conversation["slug"]);
			$this->description = $conversation["tags"];
			$this->pubDate = date("D, d M Y H:i:s O", $conversation["lastActionTime"]);
			
			// Fetch the 20 most recent posts in the conversation.
			$result = $this->esoTalk->db->query("SELECT postId, name, content, time FROM " . config("esoTalk.database.prefix") . "posts INNER JOIN " . config("esoTalk.database.prefix") . "members USING (memberId) WHERE conversationId={$conversation["id"]} AND deleteMember IS NULL ORDER BY time DESC LIMIT 20");
			while (list($id, $member, $content, $time) = $this->esoTalk->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => $member,
					"description" => sanitize($this->format($content)),
					"link" => $config["baseURL"] . makeLink("post", $id),
					"date" => date("D, d M Y H:i:s O", $time)
				);
			}
		
			break;
		
		// Fetch the most recent posts over the whole forum.
		default:
		
			// It doesn't matter whether we're logged in or not - just get non-deleted posts from conversations
			// that aren't private!
			$result = $this->esoTalk->db->query("SELECT p.postId, c.title, m.name, p.content, p.time FROM " . config("esoTalk.database.prefix") . "posts p LEFT JOIN " . config("esoTalk.database.prefix") . "conversations c USING (conversationId) INNER JOIN " . config("esoTalk.database.prefix") . "members m ON (m.memberId=p.memberId) WHERE c.private=0 AND c.posts>0 AND p.deleteMember IS NULL ORDER BY p.time DESC LIMIT 20");
			while (list($postId, $title, $member, $content, $time) = $this->esoTalk->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => "$member - $title",
					"description" => sanitize($this->format($content)),
					"link" => $config["baseURL"] . makeLink("post", $postId),
					"date" => date("D, d M Y H:i:s O", $time)
				);
			}
			
			// Set the title, link, description, etc.
			$this->title = "{$language["Recent posts"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"];
			$this->pubDate = !empty($this->items[0]) ? $this->items[0]["date"] : "";
	}
}

// Format post content to be outputted in the feed.
function format($post)
{
	global $config, $language;
	
	$this->fireEvent("formatPost", array(&$post));
	
	// Replace empty post links with "go to this post" links.
	$post = preg_replace("`(<a href='" . str_replace("?", "\?", makeLink("post", "(\d+)")) . "'[^>]*>)<\/a>`", "$1{$language["go to this post"]}</a>", $post);
	
	// Convert relative URLs to absolute URLs.
	$post = preg_replace("/<a([^>]*) href='(?!http|ftp|mailto)([^']*)'/i", "<a$1 href='{$config["baseURL"]}$2'", $post);
	$post = preg_replace("/<img([^>]*) src='(?!http|ftp|mailto)([^']*)'/i", "<img$1 src='{$config["baseURL"]}$2'", $post);
	
	return $post;
}

}

?>