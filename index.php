<?php
// OMG FIRST COMMENT!!!11!
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// 2014年11月4日12:30:55 iugo
// 虽然更新到了新版本, 但是 Toby 少了许多注释. 可能是为了减小这个主文件的大小. 

define("IN_ESOTALK", 1);

define("PAGE_START_TIME", microtime(true));

define("PATH_ROOT", dirname(__FILE__)); // 定义了整个程序包的路径. 下面的所有路径均来自这个常量.
define("PATH_CORE", PATH_ROOT."/core"); // 定义了程序包内核心文件的路径.
define("PATH_CACHE", PATH_ROOT."/cache"); // 定义了缓存文件夹的路径.
define("PATH_CONFIG", PATH_ROOT."/config"); // 定义了配置文件夹的路径.
define("PATH_LANGUAGES", PATH_ROOT."/addons/languages"); //定义了多语言扩展的路径.
define("PATH_PLUGINS", PATH_ROOT."/addons/plugins"); // 定义了插件扩展的路径.
define("PATH_SKINS", PATH_ROOT."/addons/skins"); // 定义了外观扩展的路径.
define("PATH_UPLOADS", PATH_ROOT."/uploads"); // 定义了上传文件夹的路径.

require PATH_CORE."/bootstrap.php"; // 看来这行是整个文件中比较核心的东西.