<?php
/**
 * Copyright 2015 Toby Zerner, Simon Zerner
 * This file is part of esoTalk. 
 * Please see the included license file for usage information.
 */

define('IN_ESOTALK', 1);

define('PAGE_START_TIME', microtime(true));

define('PATH_ROOT', dirname(__FILE__));

define('PATH_CORE', PATH_ROOT.'/core');
define('PATH_CACHE', PATH_ROOT.'/cache');
define('PATH_CONFIG', PATH_ROOT.'/config');
define('PATH_LANGUAGES', PATH_ROOT.'/addons/languages');
define('PATH_PLUGINS', PATH_ROOT.'/addons/plugins');
define('PATH_SKINS', PATH_ROOT.'/addons/skins');
define('PATH_UPLOADS', PATH_ROOT.'/uploads');

require_once PATH_CORE.'/bootstrap.php';
