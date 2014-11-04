<?php
// OMG FIRST COMMENT!!!11!
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// 2014年11月4日12:30:55 iugo
// 虽然更新到了新版本, 但是 Toby 少了许多注释, 我都不太明白了. 

define("IN_ESOTALK", 1);

define("PAGE_START_TIME", microtime(true));

define("PATH_ROOT", dirname(__FILE__));
define("PATH_CORE", PATH_ROOT."/core");
define("PATH_CACHE", PATH_ROOT."/cache");
define("PATH_CONFIG", PATH_ROOT."/config");
define("PATH_LANGUAGES", PATH_ROOT."/addons/languages");
define("PATH_PLUGINS", PATH_ROOT."/addons/plugins");
define("PATH_SKINS", PATH_ROOT."/addons/skins");
define("PATH_UPLOADS", PATH_ROOT."/uploads");

require PATH_CORE."/bootstrap.php";