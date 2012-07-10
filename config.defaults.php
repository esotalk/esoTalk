<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

function db_connect($dsn, $username = '', $password = '', $driver_options = array(), $path = './phppdo')
{
    $driver = strtolower(trim(substr($dsn, 0, strpos($dsn, ':'))));
    
    if($driver && class_exists('PDO') && extension_loaded('pdo_' . $driver))
    {
        $class = 'PDO';
    }
    else
    {
        require_once($path . '/phppdo.php');
        $class = 'PHPPDO';
    }
    
    return new $class($dsn, $username, $password, $driver_options);
}

try
{
    $db = db_connect('mysql:dbname=datebesename', 'username', 'password', array(), $_SERVER['DOCUMENT_ROOT'].'/phppdo');
} catch(PDOException $e)
{
    die($e->getMessage());
}

if (!defined("IN_ESOTALK")) exit;

/**
 * Default configuration: This file will get overwritten with every esoTalk update, so do not edit it.
 * If you wish the change a config setting, copy it into config/config.php and change it there.
 *
 * @package esoTalk
 */

// The version of the code.
define("ESOTALK_VERSION", "1.0.0g2");

// Define response type constants.
if (!defined("RESPONSE_TYPE_DEFAULT")) define("RESPONSE_TYPE_DEFAULT", "default");
define("RESPONSE_TYPE_VIEW", "view"); // Renders only the controller's view (without the master view)
define("RESPONSE_TYPE_AJAX", "ajax"); // Renders the controller's json contents and includes the view contents
define("RESPONSE_TYPE_JSON", "json"); // Renders only the contorller's json contents
define("RESPONSE_TYPE_ATOM", "atom"); // Renders the controller's json contents in Atom format

// IDs for storing account permission info in the database.
define("GROUP_ID_GUEST", -1);
define("GROUP_ID_MEMBER", -2);
define("GROUP_ID_ADMINISTRATOR", -3);

// What accounts are listed as in the members table ENUM field.
define("ACCOUNT_MEMBER", "member");
define("ACCOUNT_ADMINISTRATOR", "administrator");
define("ACCOUNT_SUSPENDED", "suspended");
define("ACCOUNT_GUEST", "guest");
define("ACCOUNT_PENDING", "pending");

// Installed version of esoTalk.
$config["esoTalk.installed"] = false;
$config["esoTalk.version"] = "";

// MySQL database details.
$config["esoTalk.database.host"] = "";
$config["esoTalk.database.user"] = "";
$config["esoTalk.database.password"] = "";
$config["esoTalk.database.dbName"] = "";
$config["esoTalk.database.prefix"] = "";
$config["esoTalk.database.characterEncoding"] = "utf8";
$config["esoTalk.database.connectionOptions"] = array(
	PDO::ATTR_PERSISTENT => false,
	1000 => true, // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is missing in some PHP installations
	1002 => "SET NAMES 'utf8'" // PDO::MYSQL_ATTR_INIT_COMMAND is missing in some PHP installations
);

// Basic forum details.
$config["esoTalk.forumTitle"] = "";
$config["esoTalk.forumLogo"] = false; // Path to an image file to replace the title (don't make it too big or it'll stretch the header!)
$config["esoTalk.language"] = "English";
$config["esoTalk.baseURL"] = "";
$config["esoTalk.resourceURL"] = ""; // URL used for all resources (CSS+JS+images, including those from plugins and skins.) If blank, the base URL will be used.
$config["esoTalk.rootAdmin"] = 1; // The member ID of the root administrator.
$config["esoTalk.emailFrom"] = ""; // The email address to send forum emails (notifications etc.) from.
$config["esoTalk.debug"] = false; // Debug mode will show advanced information in errors. Turn this off in production.
$config["esoTalk.aggregateCSS"] = true;
$config["esoTalk.aggregateJS"] = true;
$config["esoTalk.gzipOutput"] = true; // Whether or not to compress the page output with gzip.
$config["esoTalk.cache"] = false; // What type of cache to use.

// Meta information.
$config["esoTalk.meta.keywords"] = null;
$config["esoTalk.meta.description"] = null;

// Skins and Plugins.
$config["esoTalk.skin"] = "Proto"; // The active skin.
$config["esoTalk.mobileSkin"] = "Proto"; // The active skin for mobile devices.
$config["esoTalk.adminSkin"] = "Proto"; // The active skin for the administrator section.
$config["esoTalk.enabledPlugins"] = array("BBCode", "ReportBug"); // A list of enabled plugins.

// Login and registration settings.
$config["esoTalk.badLoginsPerMinute"] = 10;
$config["esoTalk.registration.open"] = true;
$config["esoTalk.registration.requireEmailConfirmation"] = true;

// Cookie settings.
$config["esoTalk.cookie.name"] = "";
$config["esoTalk.cookie.domain"] = ""; // Set a custom cookie domain. Set it to .yourdomain.com to have the cookie set across all subdomains.
$config["esoTalk.cookie.path"] = null; // Set a custom cookie path.
$config["esoTalk.cookie.expire"] = 2592000; // 30 days

// URL settings.
$config["esoTalk.urls.friendly"] = false; // ex. example.com/index.php/conversation/1
$config["esoTalk.urls.rewrite"] = false; // ex. example.com/conversation/1 (requires mod_rewrite and a .htaccess file!)

// Some features that can be disabled.
$config["esoTalk.enableEmailNotifications"] = true;
$config["esoTalk.notificationCheckInterval"] = 30;

// Search view settings.
$config["esoTalk.search.results"] = 20; // Number of conversations to list for a normal search.
$config["esoTalk.search.moreResults"] = 100; // Total number of conversations to list when 'more results' is clicked.
$config["esoTalk.search.updateInterval"] = 60; // Number of seconds at which to automatically update the unread status, post count, and last post information for currently listed conversations in a search.
$config["esoTalk.search.searchesPerMinute"] = 15; // Users are limited to this many normal searches every minute.
$config["esoTalk.search.disableRandomGambit"] = false; // The "random" gambit can be very slow/intensive on large forums.

// Conversation view settings.
$config["esoTalk.conversation.postsPerPage"] = 20; // The maximum number of posts to display on each page of a conversation.
$config["esoTalk.conversation.searchesPerMinute"] = 15; // Users are limited to this many "within conversation" searches every minute.
$config["esoTalk.conversation.timeBetweenPosts"] = 10; // Posting flood control, in seconds.
$config["esoTalk.conversation.maxCharsPerPost"] = 10000;

// Conversation ajax-updating intervals. Set all of these to 0 to disable ajax-updating.
$config["esoTalk.conversation.updateIntervalStart"] = 10; // The initial number of seconds before checking for new posts on the conversation view.
$config["esoTalk.conversation.updateIntervalMultiplier"] = 1.5; // Each time we check for new posts and there are none, multiply the number of seconds by this.
// ex. after 10 seconds, check for new posts. If there are none: after 10*1.5 = 15 seconds check for new posts. If there are none: after 15*1.5 = 22.5 seconds check for new posts...
$config["esoTalk.conversation.updateIntervalLimit"] = 512; // The maximum number of seconds between checking for new posts.

// Member list settings.
$config["esoTalk.members.visibleToGuests"] = false;
$config["esoTalk.members.membersPerPage"] = 30;

// Post formatting settings.
$config["esoTalk.format.youtube"] = true; // Automatically convert YouTube links to embeds?
$config["esoTalk.format.mentions"] = true; // Allow @mentioning of members?

// Misc. settings.
$config["esoTalk.defaultRoute"] = "conversations";
$config["esoTalk.minPasswordLength"] = 6;
$config["esoTalk.userOnlineExpire"] = 300; // Number of seconds a user's 'last seen time' is before the user 'goes offline'.
$config["esoTalk.sitemapCacheTime"] = 3600; // Keep sitemaps for at least 1 hour.
$config["esoTalk.updateCheckInterval"] = 86400; // How often esoTalk should ping esotalk.org to check for a new version. Default = 1 day. Set to 0 to disable update checking.

// Avatar dimensions (in pixels)
$config["esoTalk.avatars.width"] = 64;
$config["esoTalk.avatars.height"] = 64;
$config["esoTalk.avatars.thumbWidth"] = 20;
$config["esoTalk.avatars.thumbHeight"] = 20;