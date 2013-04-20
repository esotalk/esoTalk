<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller displays the dashboard section of the admin CP. Not much to see here!
 *
 * @package esoTalk
 */
class ETDashboardAdminController extends ETAdminController {


/**
 * Show the administrator dashboard view.
 *
 * @return void
 */
public function index()
{
	$this->title = T("Dashboard");

	// Work out a UNIX timestamp of one week ago.
	$oneWeekAgo = time() - 60 * 60 * 24 * 7;

	// Create an array of statistics to show on the dashboard.
	$statistics = array(

		// Number of members.
		"<a href='".URL("members")."'>".T("Members")."</a>" => number_format(ET::SQL()->select("COUNT(*)")->from("member")->exec()->result()),

		// Number of conversations.
		T("Conversations") => number_format(ET::SQL()->select("COUNT(*)")->from("conversation")->exec()->result()),

		// Number of posts.
		T("Posts") => number_format(ET::SQL()->select("COUNT(*)")->from("post")->exec()->result()),

		// Members who've joined in the past week.
		T("New members in the past week") => number_format(ET::SQL()->select("COUNT(*)")->from("member")->where(":time<joinTime")->bind(":time", $oneWeekAgo)->exec()->result()),

		// Conversations which've been started in the past week.
		T("New conversations in the past week") => number_format(ET::SQL()->select("COUNT(*)")->from("conversation")->where(":time<startTime")->bind(":time", $oneWeekAgo)->exec()->result()),

		// Posts which've been made in the past week.
		T("New posts in the past week") => number_format(ET::SQL()->select("COUNT(*)")->from("post")->where(":time<time")->bind(":time", $oneWeekAgo)->exec()->result()),

	);

	// Determine if we should show the welcome sheet.
	if (!C("esoTalk.admin.welcomeShown")) {
		$this->data("showWelcomeSheet", true);
		ET::writeConfig(array("esoTalk.admin.welcomeShown" => true));
	}

	$this->data("statistics", $statistics);
	$this->render("admin/dashboard");
}


/**
 * Get a list of the most recent posts on the esoTalk blog. Also check for updates to the esoTalk software
 * and return the update notification area.
 *
 * @return void
 */
public function news()
{
	// Check for updates and add the update notification view to the response.
	ET::upgradeModel()->checkForUpdates();
	$this->json("updateNotification", $this->getViewContents("admin/updateNotification"));

	// Now fetch the latest posts from the esoTalk blog.
	// Thanks to Brian for this code.
	// (http://stackoverflow.com/questions/250679/best-way-to-parse-rss-atom-feeds-with-php/251102#251102)
	$xmlSource = file_get_contents("http://esotalk.org/blog/index.php/feed/");
	$x = simplexml_load_string($xmlSource);
	$posts = array();

	// Go through each item in the RSS channel...
	foreach ($x->channel->item as $item) {

		$post = array(
			"date" => (string)$item->pubDate,
			"ts" => strtotime($item->pubDate),
			"link" => (string)$item->link,
			"title" => (string)$item->title,
			"text" => (string)$item->description
		);

		// Create summary as a shortened body and remove all tags.
		$summary = strip_tags($post["text"]);
		$maxLen = 200;
		if(strlen($summary) > $maxLen)
			$summary = substr($summary, 0, $maxLen)."...";

		$post["summary"] = $summary;
		$posts[] = $post;

	}

	// Render the news view.
	$this->data("posts", $posts);
	$this->render("admin/news");
}

}