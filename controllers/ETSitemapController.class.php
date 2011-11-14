<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Sitemap: generates necessary sitemap files and outputs sitemap.xml.

define("IN_ESOTALK", 1);

// Include our config files.
require "config.default.php";
@include "config/config.php";
if (!isset($config)) exit;
// Combine config.default.php and config/config.php into $config (the latter will overwrite the former.)
$config = array_merge($defaultConfig, $config);

// Compare the hardcoded version of esoTalk (ESOTALK_VERSION) to the installed one ($versions["esoTalk"]).
// If they're out-of-date, stop page execution.
require "config/versions.php";
if ($versions["esoTalk"] != ESOTALK_VERSION) exit;

require "lib/functions.php";

// If sitemap.xml is recent then we'll just use the cached version.
// Otherwise, we'll regenerate all the sitemap files.
if (!file_exists("sitemap.xml") or filemtime("sitemap.xml") < time() - $config["sitemapCacheTime"] - 200) {

	// If there are lots of conversations, this might take a while...
	set_time_limit(0);

	// Connect to the database.
	require "lib/database.php";
	$db = new Database();
	if (!$db->connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"])) exit;
	
	// Does sitemap.general.xml exist? If not, create it.
	if (!file_exists("sitemap.general.xml")) {
		writeFile("sitemap.general.xml", "<?xml version='1.0' encoding='UTF-8'?><urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'><url><loc>{$config["baseURL"]}</loc><changefreq>hourly</changefreq><priority>1.0</priority></url></urlset>");
	}

	// Initiate the counter and some settings.
	$i = 1;
	define("URLS_PER_SITEMAP", 40000); // 40,000 almost definitely will not exceed the 10MB sitemap limit.
	define("ZLIB", extension_loaded("zlib") ? ".gz" : null);

	// Generate conversation sitemap files until we run out of conversations.
	while (true) {

		// Set the filename with the counter.
		$filename = "sitemap.conversations.$i.xml" . ZLIB;

		// Get the next batch of public conversations from the database.
		$r = mysql_query("SELECT conversationId, slug, posts / ((UNIX_TIMESTAMP() - startTime) / 86400) AS postsPerDay, IF(lastActionTime, lastActionTime, startTime) AS lastUpdated, posts FROM " . config("esoTalk.database.prefix") . "conversations WHERE !private LIMIT " . (($i - 1) * URLS_PER_SITEMAP) . "," . URLS_PER_SITEMAP);

		// If there are no conversations left, break from the loop.
		if (!mysql_num_rows($r)) break;

		// Otherwise let's make the sitemap file.
		else {
			$urlset = "<?xml version='1.0' encoding='UTF-8'?><urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";

			// Create a <url> tag for each conversation in the result set.
			while (list($conversationId, $slug, $postsPerDay, $lastUpdated, $posts) = mysql_fetch_row($r)) {
				
				$urlset .= "<url><loc>{$config["baseURL"]}" . makeLink($conversationId, $slug) . "</loc><lastmod>" . gmdate("Y-m-d\TH:i:s+00:00", $lastUpdated) . "</lastmod><changefreq>";
				
				// How often should we tell them to check for updates?
				if ($postsPerDay < 0.006) $urlset .= "yearly";
				elseif ($postsPerDay < 0.07) $urlset .= "monthly";
				elseif ($postsPerDay < 0.3) $urlset .= "weekly";
				elseif ($postsPerDay < 3) $urlset .= "daily";
				else $urlset .= "hourly";
				$urlset .= "</changefreq>";
				
				// Estimate the conversation's importance based upon the number of posts.
				if ($posts < 50) ; // Default priority is 0.5, so specifying it is redundant.
				elseif ($posts < 100) $urlset .= "<priority>0.6</priority>";
				elseif ($posts < 500) $urlset .= "<priority>0.7</priority>";
				elseif ($posts < 1000) $urlset .= "<priority>0.8</priority>";
				else $urlset .= "<priority>0.9</priority>";
				$urlset .= "</url>";
				
			}

			// [Encode, and] write out the file.
			$urlset .= "</urlset>";
			if (ZLIB) $urlset = gzencode($urlset, 9);
			writeFile($filename, $urlset);

			// And increment the counter for the next cycle.
			$i++;
		}
	}

	// Now we're gonna generate the sitemap index.
	$sitemap = "<?xml version='1.0' encoding='UTF-8'?><sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'><sitemap><loc>{$config["baseURL"]}sitemap.general.xml</loc></sitemap>";
	// For each conversation sitemap that we wrote, up until we break'd, add a <sitemap> entry to the index.
	for ($j = 1; $j < $i; $j++) $sitemap .= "<sitemap><loc>{$config["baseURL"]}sitemap.conversations.$j.xml" . ZLIB . "</loc><lastmod>" . gmdate("Y-m-d\TH:i:s+00:00") . "</lastmod></sitemap>";
	$sitemap .= "</sitemapindex>";
	
	// And write out!
	writeFile("sitemap.xml", $sitemap);
}

// Whether we generated new sitemaps or are using a cached version, let's include the sitemap index and serve it as xml.
header("Content-type: text/xml");
$handle = fopen("sitemap.xml", "r");
echo fread($handle, filesize("sitemap.xml"));
fclose($handle);

?>