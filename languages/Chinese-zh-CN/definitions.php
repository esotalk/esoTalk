<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

ET::$languageInfo["Chinese-zh-CN"] = array(
	"locale" => "zh-CN",
	"name" => "Chinese-zh-CN",
	"description" => "中文语言包",
	"version" => ESOTALK_VERSION,
	"author" => "iugo",
	"authorEmail" => "iugogogo@gmail.com",
	"authorURL" => "http://vows.cn",
	"license" => "GPLv2"
);

// Define the character set that this language uses.
$definitions["charset"] = "utf-8";

$definitions["date.full"] = "j M Y, g:ia \G\M\TO"; // see http://au.php.net/manual/en/function.date.php for details

$definitions["%d day ago"] = "昨天";
$definitions["%d days ago"] = "%d 天前";
$definitions["%d hour ago"] = "1 小时前";
$definitions["%d hours ago"] = "%d 小时前";
$definitions["%d minute ago"] = "1 分钟前";
$definitions["%d minutes ago"] = "%d 分钟前";
$definitions["%d month ago"] = "1 月前";
$definitions["%d months ago"] = "%d 月前";
$definitions["%d second ago"] = "1 秒前";
$definitions["%d seconds ago"] = "%d 秒前";
$definitions["%d week ago"] = "1 星期前";
$definitions["%d weeks ago"] = "%d 周前";
$definitions["%d year ago"] = "去年";
$definitions["%d years ago"] = "%d 年前";

$definitions["%s and %s"] = "%s 和 %s";
$definitions["%s can view this conversation."] = "%s 可以浏览该会话。";
$definitions["%s changed %s's group to %s."] = "%s changed %s's group to %s.";
$definitions["%s changed your group to %s."] = "%s changed your group to %s.";
$definitions["%s conversation"] = "%s 会话";
$definitions["%s conversations"] = "%s 会话";
$definitions["%s invited you to %s."] = "%s invited you to %s.";
$definitions["%s joined the forum."] = "%s 加入了论坛.";
$definitions["%s post"] = "%s post";
$definitions["%s posted %s"] = "%s 发表于 %s";
$definitions["%s posted in %s."] = "%s 发表在 %s.";
$definitions["%s posts"] = "%s 讨论";
$definitions["%s reply"] = "%s 回复";
$definitions["%s replies"] = "%s 回复";
$definitions["%s Settings"] = "%s Settings";
$definitions["%s started the conversation %s."] = "%s 创建了会话 %s.";
$definitions["%s tagged you in a post."] = "%s 在一个帖子中提到了你.";
$definitions["%s will be able to view this conversation."] = "%s 可以看到这则对话。";
$definitions["%s will be able to:"] = "%s will be able to:";

$definitions["A new version of esoTalk (%s) is available."] = "A new version of esoTalk (%s) is available.";
$definitions["a private conversation"] = "a private conversation";
$definitions["Access the administrator control panel."] = "Access the administrator control panel.";
$definitions["Account type"] = "Account type";
$definitions["Activate"] = "Activate";
$definitions["Activity"] = "近况";
$definitions["Add"] = "增加";
$definitions["Administration"] = "管理";
$definitions["Administrator email"] = "管理员邮箱";
$definitions["Administrator password"] = "管理员密码";
$definitions["Administrator username"] = "管理员用户名";
$definitions["Advanced options"] = "高级选项";
$definitions["All Channels"] = "所有频道";
$definitions["Already have an account? <a href='%s' class='link-login'>Log in!</a>"] = "已经注册过了？ <a href='%s' class='link-login'>请登入</a>";
$definitions["Appearance"] = "外观";
$definitions["Automatically star conversations that I reply to"] = "自动关注我参与的会话。";
$definitions["Avatar"] = "头像";

$definitions["Back to channels"] = "返回到频道";
$definitions["Back to conversation"] = "返回到会话";
$definitions["Back to member"] = "Back to member";
$definitions["Back to members"] = "返回成员列表";
$definitions["Back to search"] = "返回";
$definitions["Base URL"] = "Base URL";
$definitions["Bold"] = "加粗";
$definitions["By %s"] = "By %s";

$definitions["Can suspend/unsuspend members"] = "Can suspend/unsuspend members";
$definitions["Cancel"] = "取消";
$definitions["Change %s's Permissions"] = "Change %s's Permissions";
$definitions["Change avatar"] = "更换头像";
$definitions["Change channel"] = "更换频道";
$definitions["Change name"] = "修改成员名";
$definitions["Change Password or Email"] = "修改密码或邮箱";
$definitions["Change password"] = "修改密码";
$definitions["Change permissions"] = "Change permissions";
$definitions["Change"] = "改变";
$definitions["Channel description"] = "频道介绍";
$definitions["Channel List"] = "频道列表";
$definitions["Channel title"] = "频道标题";
$definitions["Channel slug"] = "频道URL";
$definitions["Channels"] = "频道";
$definitions["Choose a secure password of at least %s characters"] = "请填写一组至少 %s 位字符的密码";
$definitions["Choose what people will see when they first visit your forum."] = "选择论坛默认使用的语言。";
$definitions["Click on a member's name to remove them."] = "Click on a member's name to remove them.";
$definitions["Close registration"] = "关闭注册";
$definitions["Confirm password"] = "验证密码";
$definitions["Controls"] = "Controls";
$definitions["Conversation"] = "Conversation";
$definitions["Conversations participated in"] = "参与的会话";
$definitions["Conversations started"] = "创建的会话";
$definitions["Conversations"] = "Conversations";
$definitions["Copy permissions from"] = "从这里复制成员权限设置";
$definitions["Create Channel"] = "创建频道";
$definitions["Create Group"] = "创建成员组";
$definitions["Create Member"] = "Create Member";
$definitions["Customize how users can become members of your forum."] = "控制论坛成员注册。";

$definitions["Dashboard"] = "后台控制板";
$definitions["Default forum language"] = "论坛默认语言";
$definitions["<strong>Delete</strong> all conversations forever."] = "<strong>删除</strong> 所有频道（不可恢复）。";
$definitions["Delete Channel"] = "删除频道";
$definitions["Delete conversation"] = "删除会话";
$definitions["Delete member"] = "删除成员";
$definitions["Delete Member"] = "删除成员";
$definitions["<strong>Delete this member's posts.</strong> All of this member's posts will be marked as deleted, but will be able to be restored manually."] = "<strong>Delete this member's posts.</strong> All of this member's posts will be marked as deleted, but will be able to be restored manually.";
$definitions["Delete"] = "删除";
$definitions["Deleted %s by %s"] = "Deleted %s by %s";
$definitions["Disable"] = "关闭";
$definitions["Discard Draft"] = "丢弃草稿";
$definitions["Don't have an account? <a href='%s' class='link-join'>Sign up!</a>"] = "还没有注册为我们的一员吗？<a href='%s' class='link-join'>请点此注册</a>";


$definitions["Edit Channel"] = "编辑频道";
$definitions["Edit Group"] = "Edit Group";
$definitions["Edit member groups"] = "编辑成员组";
$definitions["Edit your profile"] = "编辑您的个人信息";
$definitions["Edit"] = "编辑";
$definitions["Edited %s by %s"] = "%s 被 %s 修改过";
$definitions["Email me when I'm added to a private conversation"] = "当我有新的私人会话时给我发邮件。";
$definitions["Email me when someone posts in a conversation I have starred"] = "当有人在讨论中提到我时给我发邮件。";
$definitions["Email"] = "Email";
$definitions["Enable"] = "打开";
$definitions["Enter a conversation title"] = "请填写会话标题";
$definitions["Error"] = "错误";
$definitions["esoTalk version"] = "esoTalk 版本";
$definitions["Everyone"] = "所有人";

$definitions["Fatal Error"] = "抱歉呀，这是一片未开发之地……";
$definitions["Feed"] = "Feed";
$definitions["Filter by name or group..."] = "Filter by name or group...";
$definitions["Filter conversations..."] = "过滤会话...";
$definitions["Find this post"] = "Find this post";
$definitions["First posted"] = "第一次讨论";
$definitions["Forgot Password"] = "忘记密码";
$definitions["Forum header"] = "论坛页头";
$definitions["Forum language"] = "论坛语言包";
$definitions["Forum Settings"] = "论坛设置";
$definitions["Forum Statistics"] = "Forum Statistics";
$definitions["Forum title"] = "论坛名称";
$definitions["forumDescription"] = "%s is a web-forum discussing %s, and %s.";
$definitions["Forgot?"] = "忘记了？";

$definitions["Give this group the 'moderate' permission on all existing channels"] = "Give this group the 'moderate' permission on all existing channels";
$definitions["Global permissions"] = "全局权限(慎用)";
$definitions["Go to top"] = "回到顶端";
$definitions["Group name"] = "成员组名";
$definitions["group.administrator"] = "管理员";
$definitions["group.administrator.plural"] = "管理员";
$definitions["group.guest"] = "游客";
$definitions["group.guest.plural"] = "游客";
$definitions["group.member"] = "成员";
$definitions["group.member.plural"] = "成员";
$definitions["group.Moderator"] = "版主";
$definitions["group.Moderator.plural"] = "版主";
$definitions["group.suspended"] = "Suspended";
$definitions["Groups can be used to categorize members and give them certain privileges."] = "成员组可以用来对不同成员进行分类。";
$definitions["Groups"] = "成员组";

$definitions["Header"] = "Header";
$definitions["Home page"] = "首页";
$definitions["HTML is allowed."] = "允许使用 HTML 代码.";

$definitions["If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='%s'>esoTalk support forum</a>."] = "If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='%s'>esoTalk 支持论坛</a>.";
$definitions["Install esoTalk"] = "安装 esoTalk";
$definitions["Install My Forum"] = "安装我的论坛";
$definitions["Installed Languages"] = "安装语言包";
$definitions["Installed Plugins"] = "安装扩展";
$definitions["Installed plugins"] = "安装扩展";
$definitions["Installed Skins"] = "安装皮肤";
$definitions["Installed skins"] = "安装皮肤";
$definitions["is %s"] = "is %s";

$definitions["Jump to last"] = "进入最新贴";
$definitions["Jump to unread"] = "进入未读帖";
$definitions["just now"] = "刚才";
$definitions["Joined"] = "加入时间";


$definitions["<strong>Keep this member's posts.</strong> All of this member's posts will remain intact, but will show [deleted] as the author."] = "<strong>Keep this member's posts.</strong> All of this member's posts will remain intact, but will show [deleted] as the author.";
$definitions["Keep me logged in"] = "保持登入状态";

$definitions["label.draft"] = "草稿";
$definitions["label.locked"] = "已关闭";
$definitions["label.muted"] = "已屏蔽";
$definitions["label.private"] = "私人";
$definitions["label.sticky"] = "重要";
$definitions["Labels"] = "标签";
$definitions["Last active %s"] = "最近活动 %s";
$definitions["Last active"] = "最近活动";
$definitions["Latest News"] = "程序新闻";
$definitions["Loading..."] = "载入中...";
$definitions["Lock"] = "关闭";
$definitions["Log In"] = "登入";
$definitions["Log Out"] = "登出";

$definitions["Manage Groups"] = "管理用户组";
$definitions["Manage Languages"] = "管理语言包";
$definitions["Mark as read"] = "标记为已读";
$definitions["Mark all as read"] = "全部标记为已读";
$definitions["Maximum size of %s. %s."] = "文件最大不超过 %s. %s.";
$definitions["Member groups"] = "成员组";
$definitions["Member List"] = "成员列表";
$definitions["Member list"] = "成员列表";
$definitions["Members Allowed to View this Conversation"] = "请选择谁可以查看此回话。";
$definitions["Members Online"] = "在线成员";
$definitions["Members"] = "成员";
$definitions["Mobile skin"] = "移动版皮肤";
$definitions["Moderate"] = "Moderate";
$definitions["<strong>Move</strong> conversations to the following channel:"] = "<strong>Move</strong> conversations to the following channel:";
$definitions["Mute conversation"] = "屏蔽会话";
$definitions["MySQL database"] = "MySQL 数据库名";
$definitions["MySQL host address"] = "MySQL 服务器地址";
$definitions["MySQL password"] = "MySQL 密码";
$definitions["MySQL queries"] = "MySQL queries";
$definitions["MySQL table prefix"] = "MySQL 表名前缀";
$definitions["MySQL username"] = "MySQL 用户名";
$definitions["MySQL version"] = "MySQL 版本";
$definitions["Make member list visible to:"] = "谁可以浏览成员列表:";

$definitions["Name"] = "姓名";
$definitions["never"] = "从未";
$definitions["New Conversation"] = "新会话";
$definitions["New conversation"] = "新会话";
$definitions["New conversations in the past week"] = "上周新会话";
$definitions["New email"] = "新邮箱地址";
$definitions["New members in the past week"] = "New members in the past week";
$definitions["New password"] = "新密码";
$definitions["New posts in the past week"] = "New posts in the past week";
$definitions["New username"] = "新成员名";
$definitions["Next Step"] = "下一步";
$definitions["Next"] = "Next";
$definitions["No preview"] = "No preview";
$definitions["No"] = "No";
$definitions["Notifications"] = "提醒";
$definitions["Now"] = "最近讨论";

$definitions["OK"] = "OK";
$definitions["Online"] = "在线";
$definitions["online"] = "在线";
$definitions["Open registration"] = "允许注册";
$definitions["optional"] = "选填";
$definitions["Order By:"] = "排序:";
$definitions["Original Post"] = "首篇讨论";

$definitions["Page Not Found"] = "Lost.";
$definitions["Password"] = "密码";
$definitions["PHP version"] = "PHP 版本";
$definitions["Plugins"] = "扩展";
$definitions["Post a Reply"] = "回复";
$definitions["Post count"] = "Post count";
$definitions["Posts"] = "讨论";
$definitions["Preview"] = "预览";
$definitions["Previous"] = "Previous";

$definitions["Quote"] = "引用";
$definitions["quote"] = "引用";

$definitions["Read more"] = "浏览更多";
$definitions["Recent posts"] = "Recent posts";
$definitions["Recover Password"] = "重置密码";
$definitions["Registration"] = "成员注册";
$definitions["Remove avatar"] = "移除头像";
$definitions["Rename Member"] = "将该成员重命名";
$definitions["Reply"] = "回复";
$definitions["Require users to confirm their email address"] = "要求验证成员邮箱";
$definitions["restore"] = "恢复";
$definitions["Restore"] = "恢复";
$definitions["Registered members"] = "已注册成员";

$definitions["Save Changes"] = "保存修改";
$definitions["Save Draft"] = "存为草稿";
$definitions["Search conversations..."] = "搜索会话...";
$definitions["Search within this conversation..."] = "在这则会话中搜索...";
$definitions["Search"] = "搜索";
$definitions["See the private conversations I've had with %s"] = "查看我与 %s 之间的私人对话";
$definitions["Settings"] = "设置";
$definitions["Set a New Password"] = "设置一个新密码";
$definitions["Show an image in the header"] = "页头显示图片";
$definitions["Show matching posts"] = "显示搜索匹配到内容";
$definitions["Show the channel list by default"] = "首页显示频道列表";
$definitions["Show the conversation list by default"] = "首页显示会话列表";
$definitions["Show the forum title in the header"] = "页头显示论坛名称";
$definitions["Sign Up"] = "注册";
$definitions["Skins"] = "皮肤";
$definitions["Specify Setup Information"] = "Specify Setup Information";
$definitions["Star to receive notifications"] = "Star to receive notifications";
$definitions["Starred"] = "Starred";
$definitions["Start a conversation"] = "创建新会话";
$definitions["Start a private conversation with %s"] = "与 %s 进行一则私人会话";
$definitions["Start Conversation"] = "发布该回话";
$definitions["Starting a conversation"] = "创建一个新会话";
$definitions["Statistics"] = "统计";
$definitions["statistic.conversation.plural"] = "%s 则会话";
$definitions["statistic.conversation"] = "%s 则会话";
$definitions["statistic.member.plural"] = "%s 位成员";
$definitions["statistic.member"] = "%s 位成员";
$definitions["statistic.online.plural"] = "%s 人在线";
$definitions["statistic.online"] = "%s 人在线";
$definitions["statistic.post.plural"] = "%s 条讨论";
$definitions["statistic.post"] = "%s 条讨论";
$definitions["Sticky"] = "重要";
$definitions["Subscribed"] = "已关注";
$definitions["Subscription"] = "关注";
$definitions["Suspend member"] = "屏蔽成员";
$definitions["Suspend members."] = "屏蔽成员.";
$definitions["Suspend"] = "屏蔽";
$definitions["Success!"] = "成功了";


$definitions["Uninstall"] = "卸载";
$definitions["Unlock"] = "打开";
$definitions["Unmute conversation"] = "取消屏蔽主题";
$definitions["Unstarred"] = "取消星标";
$definitions["Unsticky"] = "取消重要";
$definitions["Unsubscribe new users by default"] = "Unsubscribe new users by default";
$definitions["Unsubscribed"] = "不再关注";
$definitions["Unsuspend member"] = "取消屏蔽成员";
$definitions["Unsuspend"] = "取消屏蔽";
$definitions["Untitled conversation"] = "Untitled conversation";
$definitions["Upgrade esoTalk"] = "Upgrade esoTalk";
$definitions["Use for mobile"] = "Use for mobile";
$definitions["Use friendly URLs"] = "Use friendly URLs";
$definitions["Used to verify your account and subscribe to conversations"] = "您可能需要到邮箱中查收验证邮件并一键验证。";
$definitions["Username"] = "成员名";
$definitions["Username or Email"] = "成员名 或 邮箱";

$definitions["View %s's profile"] = "浏览 %s 的个人页面";
$definitions["View all notifications"] = "查看所有提醒";
$definitions["View more"] = "查看更多";
$definitions["View your profile"] = "查看您的个人页面";
$definitions["View"] = "浏览";
$definitions["Viewing: %s"] = "正在浏览: %s";
$definitions["viewingPosts"] = "<b>%s-%s</b> of %s posts";

$definitions["Warning"] = "Uh oh, something's not right!";
$definitions["Write a reply..."] = "参与讨论...";

$definitions["Yes"] = "Yes";
$definitions["You can manage channel-specific permissions on the channels page."] = "You can manage channel-specific permissions on the channels page.";
$definitions["Your current password"] = "您的现用密码";


// Messages.
$definitions["message.404"] = "Oh dear - the page you requested could not be found! Try going back and clicking a different link. Or something else.";
$definitions["message.ajaxDisconnected"] = "与服务器连接失败. 别担心，请稍等并 <a href='javascript:jQuery.ETAjax.resumeAfterDisconnection()'>再试一次</a>, 或者 <a href='' onclick='window.location.reload();return false'>重新载入该页面</a>.";
$definitions["message.ajaxRequestPending"] = "Hey! We're still processing some of your stuff! If you navigate away from this page you might lose any recent changes you've made, so wait a few seconds, ok?";
$definitions["message.avatarError"] = "There was a problem uploading your avatar. Make sure you're using a valid image type (like .jpg, .png, or .gif) and the file isn't really really huge.";
$definitions["message.cannotDeleteLastChannel"] = "Hey, wait up, you can't delete the last channel! Where would your conversations go? That's just silly.";
$definitions["message.changesSaved"] = "已保存您的更改。";
$definitions["message.channelsHelp"] = "频道用来对论坛中的会话进行分类. 你可以按照需要创建任意频道并且拖动它们进行排序. ";
$definitions["message.confirmDelete"] = "Are you sure you want to delete this? Seriously, you won't be able to get it back.";
$definitions["message.confirmDiscardReply"] = "You have not saved your reply as a draft. Do you wish to discard it?";
$definitions["message.confirmEmail"] = "在您正式使用此成员号之前，请到您刚才填写的邮箱中查收邮件并一键验证。您可能需要等待一会儿，邮件马上就会投递到您的邮箱中。";
$definitions["message.confirmLeave"] = "Woah, you haven't saved the stuff you are editing! If you leave this page, you'll lose any changes you've made. Is this ok?";
$definitions["message.connectionError"] = "esoTalk could not connect to the MySQL server. The error returned was:<br/>%s";
$definitions["message.conversationDeleted"] = "The conversation was deleted. Didn't that feel good?";
$definitions["message.conversationNotFound"] = "For some reason this conversation cannot be viewed. Maybe it's been deleted? Or maybe it's a private conversation, in which case you might not be logged in or you might not be invited. Oh man, I hope they're not talking about you behind your back!";
$definitions["message.deleteChannelHelp"] = "Woah, hold up there! If you delete this channel, there'll be no way to get it back. Unless you build a time machine. But, uh, there'll be no <em>easy</em> way to get it back. All of the conversations in this channel can be moved to another of your choice.";
$definitions["message.emailConfirmed"] = "太好了, 您的帐户已经被激活. 现在您可以尽情参与到我们的会话讨论中了. 为什么不现在就 <a href='".URL("conversation/start")."'>开始一则新会话</a> 让大家都认识您呢?";
$definitions["message.emailDoesntExist"] = "我们没有找到该邮箱地址的相关信息，您确认输入正确了吗？";
$definitions["message.emailNotYetConfirmed"] = "在使用此成员账户前，您需要先到您的邮箱中查收验证邮件并进行一键验证。<a href='%s'>点此再次发送邮件</a>.";
$definitions["message.emailTaken"] = "Curses, there is already a member with this email!";
$definitions["message.empty"] = "You must fill out this field.";
$definitions["message.emptyPost"] = "Yeah... uh, you should probably type something in your post.";
$definitions["message.emptyTitle"] = "The title of your conversation can't be blank. I mean, how can anyone click on a blank title? Think about it.";
$definitions["message.esoTalkAlreadyInstalled"] = "<strong>esoTalk is already installed.</strong><br/><small>To reinstall esoTalk, you must remove <strong>config/config.php</strong>.</small>";
$definitions["message.esoTalkUpdateAvailable"] = "A new version of esoTalk, %s, is now available.";
$definitions["message.esoTalkUpdateAvailableHelp"] = "It's recommended to always have the latest version of esoTalk installed to reduce security risk. And hey, there might be some cool new features!";
$definitions["message.esoTalkUpToDate"] = "你的 esoTalk 版本需要升级。";
$definitions["message.esoTalkUpToDateHelp"] = "I'm a poor college student who has spent many hundreds of hours developing esoTalk. If you like it, please consider <a href='%s' target='_blank'>donating</a>.";
$definitions["message.fatalError"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li>Go outside, walk the dog, have a coffee... then <strong><a href='%1\$s'>try again</a></strong>!</li>\n<li>If you are the forum administrator, then you can <strong>get help on the <a href='%2\$s'>esoTalk website</a></strong>.</li>\n<li>Try hitting the computer - that sometimes works for me.</li>\n</ul>";
$definitions["message.fatalErrorInstaller"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li><p><strong>Try again.</strong> Everyone makes mistakes - maybe the computer made one this time!</p></li>\n<li><p><strong>Go back and check your settings.</strong> In particular, make sure your database information is correct.</p></li>\n<li><p><strong>Get help.</strong> Go on the <a href='%s'>esoTalk support forum</a> and search to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</p></li>\n</ul>";
$definitions["message.fatalErrorUpgrader"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li><p><strong>Try again.</strong> Everyone makes mistakes - maybe the computer made one this time!</p></li>\n<li><p><strong>Get help.</strong> Go on the <a href='%s'>esoTalk support forum</a> and search to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</p></li>\n</ul>";
$definitions["message.fileUploadFailed"] = "Something went wrong and the file you selected could not be uploaded. Perhaps it's too big, or in the wrong format?";
$definitions["message.fileUploadFailedMove"] = "The file you uploaded could not be copied to its destination. Please contact the forum administrator.";
$definitions["message.fileUploadNotImage"] = "The file you uploaded is not an image in an acceptable format.";
$definitions["message.fileUploadTooBig"] = "The file you selected could not be uploaded because it is too big.";
$definitions["message.forgotPasswordHelp"] = "您忘记密码了？别担心，在这里输入您注册时填写的邮箱地址后，可以申请找回。提交申请后 请查收我们给您发送的邮件，按步骤设置新密码。";
$definitions["message.fulltextKeywordWarning"] = "对不起，只能搜索英文关键词。并且每个单词应至少4个字母。";
$definitions["message.gambitsHelp"] = "您可以利用[智能搜索]来寻找您想看的内容。支持英文全文检索，但不支持中文关键词。";
$definitions["message.gdNotEnabledWarning"] = "The <strong>GD extension</strong> is not enabled.<br/><small>This is required to resize and save avatars. Get your host or administrator to install/enable it.</small>";
$definitions["message.greaterMySQLVersionRequired"] = "You must have <strong>MySQL 4 or greater</strong> installed and the <a href='http://php.net/manual/en/mysql.installation.php' target='_blank'>MySQL extension enabled in PHP</a>.<br/><small>Please install/upgrade both of these requirements or request that your host or administrator install them.</small>";
$definitions["message.greaterPHPVersionRequired"] = "Your server must have <strong>PHP 5.0.0 or greater</strong> installed to run esoTalk.<br/><small>Please upgrade your PHP installation or request that your host or administrator upgrade the server.</small>";
$definitions["message.incorrectLogin"] = "您所填信息有误。请确认已通过邮件验证并核对密码。";
$definitions["message.incorrectPassword"] = "Your current password is incorrect.";
$definitions["message.installerAdminHelp"] = "esoTalk will use the following information to set up your administrator account on your forum.";
$definitions["message.installerFilesNotWritable"] = "esoTalk cannot write to the following files/folders: <strong>%s</strong>.<br/><small>To resolve this, you must navigate to these files/folders in your FTP client and <strong>chmod</strong> them to <strong>777</strong>.</small>";
$definitions["message.installerMySQLHelp"] = "esoTalk needs access to a MySQL database to store all your forum's data in, such as conversations and posts. If you're unsure of any of these details, you may need to ask your hosting provider.";
$definitions["message.installerWelcome"] = "<p>Welcome to the esoTalk installer! We need a few details from you so we can get your forum set up and ready to go.</p>\n<p>If you have any trouble, get help on the <a href='%s'>esoTalk support forum</a>.</p>";
$definitions["message.invalidChannel"] = "You selected an invalid channel!";
$definitions["message.invalidEmail"] = "请输入有效的邮箱地址。";
$definitions["message.invalidUsername"] = "成员名需要在4-20个字符之间，相当于2-7个汉字。请勿输入空格等特殊字符。";
$definitions["message.javascriptRequired"] = "This page requires JavaScript to function properly. Please enable it!";
$definitions["message.languageUninstalled"] = "The language was uninstalled.";
$definitions["message.locked"] = "Hm, looks like this conversation is <strong>locked</strong>, so you can't reply to it.";
$definitions["message.loginToParticipate"] = "To start conversations or reply to posts, please log in.";
$definitions["message.logInToReply"] = "<a href='%1\$s' class='link-login'>登入</a> 后可以参与讨论。—— 或者，请先<a href='%2\$s' class='link-join'>注册</a>。";
$definitions["message.logInToSeeAllConversations"] = "<a href='".URL("user/login")."' class='link-login'>尝试登陆</a> 以浏览一些对游客隐藏的会话。";
$definitions["message.memberNotFound"] = "Hm, there doesn't seem to be a member with that name.";
$definitions["message.memberNoPermissionView"] = "That member can't be added because they don't have permission to view the channel that this conversation is in.";
$definitions["message.nameTaken"] = "The name you have entered is taken or is a reserved word.";
$definitions["message.newSearchResults"] = "该页面可能有一些更新内容, 您可以重新载入页面来查看.";
$definitions["message.noActivity"] = "%s hasn't done anything on this forum yet!";
$definitions["message.noMembersOnline"] = "现在没有成员在线。";
$definitions["message.noNotifications"] = "暂时没有提醒。";
$definitions["message.noPermission"] = "Bad user! You do not have permisssion to perform this action.";
$definitions["message.noPermissionToReplyInChannel"] = "您没有权限参与这个频道的会话.";
$definitions["message.noPluginsInstalled"] = "No plugins are currently installed.";
$definitions["message.noSearchResults"] = "没有找到符合查询条件的会话。";
$definitions["message.noSearchResultsMembers"] = "抱歉，没有找到符合查询条件的成员。或许Ta已改名换姓？";
$definitions["message.noSearchResultsPosts"] = "没有找到您想要找的讨论内容。";
$definitions["message.noSkinsInstalled"] = "No skins are currently installed.";
$definitions["message.notWritable"] = "<code>%s</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within.";
$definitions["message.pageNotFound"] = "抱歉, 您想要访问的页面不存在. 您可以返回主页继续浏览您喜欢的内容. [404] ";
$definitions["message.passwordChanged"] = "您已经完成密码重置, 现在可以使用新密码登入论坛了.";
$definitions["message.passwordEmailSent"] = "我们已经给您发送了一封电子邮件, 请查收. 如果您在几分钟后仍没收到我们的邮件, 请检查您的垃圾邮件箱, 可能您的邮箱误把我们的邮件当作垃圾邮件了. 我们的邮件地址是: do_not_reply@plus.vows.cn";
$definitions["message.passwordsDontMatch"] = "Your passwords do not match.";
$definitions["message.passwordTooShort"] = "密码长度太短了.";
$definitions["message.pluginCannotBeEnabled"] = "The plugin <em>%s</em> cannot be enabled: %s";
$definitions["message.pluginDependencyNotMet"] = "To enable this plugin, you must have %s version %s installed and enabled.";
$definitions["message.pluginUninstalled"] = "The plugin was uninstalled.";
$definitions["message.postNotFound"] = "The post you're looking for could not be found.";
$definitions["message.postTooLong"] = "Your post is really, really long! Too long! The maximum number of characters allowed is %s. That's really long!";
$definitions["message.preInstallErrors"] = "The following errors were found with your esoTalk setup. They must be resolved before you can continue the installation.";
$definitions["message.preInstallWarnings"] = "The following errors were found with your esoTalk setup. You can continue the esoTalk install without resolving them, but some esoTalk functionality may be limited.";
$definitions["message.reduceNumberOfGambits"] = "你确定正确输入了您想要的内容吗？或者您想要搜索 TheLife, TheNatural, TheSocial, TheEngineering 这些常见分类。";
$definitions["message.registerGlobalsWarning"] = "PHP's <strong>register_globals</strong> setting is enabled.<br/><small>While esoTalk can run with this setting on, it is recommended that it be turned off to increase security and to prevent esoTalk from having problems.</small>";
$definitions["message.registrationClosed"] = "Registration on this forum is not open to the public.";
$definitions["message.removeDirectoryWarning"] = "Hey! Looks like you haven't deleted the <code>%s</code> directory like we told you to! You probably should, just to make sure those hackers can't do anything naughty.";
$definitions["message.safeModeWarning"] = "<strong>Safe mode</strong> is enabled.<br/><small>This could potentially cause problems with esoTalk, but you can still proceed if you cannot turn it off.</small>";
$definitions["message.searchAllConversations"] = "请尝试在全论坛查找这些内容。";
$definitions["message.setNewPassword"] = "好了, 现在您可以为您的论坛帐号重新设置一个新的密码.";
$definitions["message.skinUninstalled"] = "The skin was uninstalled.";
$definitions["message.suspended"] = "Ouch! A forum moderator has <strong>suspended</strong> your account. It sucks, but until the suspension is lifted you won't be able to do much around here. Hey, screw them!";
$definitions["message.suspendMemberHelp"] = "Suspending %s will prevent them from replying to conversations, starting conversations, and viewing private conversations. They will effectively have the same permissions as a guest.";
$definitions["message.tablePrefixConflict"] = "The installer has detected that there is another installation of esoTalk in the same MySQL database with the same table prefix.<br/>To overwrite this installation of esoTalk, click 'Install My Forum' again. <strong>All data will be lost.</strong><br/>If you wish to create another esoTalk installation alongside the existing one, <strong>change the table prefix</strong>.";
$definitions["message.unsuspendMemberHelp"] = "Unsuspending %s will enable them to participate in conversations on this forum again.";
$definitions["message.upgradeSuccessful"] = "esoTalk was successfully upgraded.";
$definitions["message.waitToReply"] = "You must wait at least %s seconds between starting or replying to conversations. Take a deep breath and try again.";
$definitions["message.waitToSearch"] = "Woah, slow down! Looks like you're trying to perform a few too many searches. Wait %s seconds and try again.";


// Emails.
$definitions["email.confirmEmail.body"] = "请问您是否使用该邮箱在 '%1\$s' 注册为新成员? 如果您并没有注册, 请忽略该邮件. 如果您持续被该邮件骚扰, 请致信到 iugogogo@gmail.com , 我们将为您解决.\n\n如果您刚注册了我们的论坛, 请访问下面的地址激活您的帐户, 成为我们的一员:\n%2\$s";
$definitions["email.confirmEmail.subject"] = "%1\$s, 请进入邮件验证您的邮箱地址";
$definitions["email.footer"] = "\n\n(If you don't want to receive any emails of this kind again, <a href='%s'>click here</a>.)";
$definitions["email.forgotPassword.body"] = "请问您是否在我们的论坛 '%1\$s' 提交了找回密码的申请? \n\n如果您的确忘记了密码,请通过下面的链接重置密码: \n%2\$s";
$definitions["email.forgotPassword.subject"] = "%1\$s 您好, 忘记了密码, 需要找回密码吗?";
$definitions["email.header"] = "您好, %s!\n\n";
$definitions["email.mention.body"] = "%1\$s tagged you in a post in the conversation '%2\$s'.\n\nTo view the post, check out the following link:\n%3\$s";
$definitions["email.mention.subject"] = "%1\$s tagged you in a post";
$definitions["email.privateAdd.body"] = "You have been added to a private conversation titled '%1\$s'.\n\nTo view this conversation, check out the following link:\n%2\$s";
$definitions["email.privateAdd.subject"] = "You have been added to a private conversation";
$definitions["email.replyToStarred.body"] = "%1\$s has replied to a conversation which you starred: '%2\$s'.\n\nTo view the new activity, check out the following link:\n%3\$s";
$definitions["email.replyToStarred.subject"] = "There is a new reply to '%1\$s'";


// Translating the gambit system can be quite complex, but we'll do our best to get you through it. :)
// Note: Don't use any html entities in these definitions, except for: &lt; &gt; &amp; &#39;

// Simple gambits
// These gambits are pretty much evaluated as-they-are.
// tag:, author:, contributor:, and quoted: are combined with a value after the colon (:).
// For example: tag:video games, author:myself
$definitions["gambit.author:"] = "作者:";
$definitions["gambit.contributor:"] = "参与者:";
$definitions["gambit.member"] = "成员名";
$definitions["gambit.myself"] = "我自己";
$definitions["gambit.draft"] = "草稿";
$definitions["gambit.has attachments"] = "有附件";
$definitions["gambit.locked"] = "已关闭";
$definitions["gambit.order by newest"] = "最新发表";
$definitions["gambit.order by replies"] = "最近回复";
$definitions["gambit.private"] = "私人会话";
$definitions["gambit.random"] = "随机";
$definitions["gambit.reverse"] = "反向排序";
$definitions["gambit.starred"] = "已关注";
$definitions["gambit.muted"] = "已屏蔽";
$definitions["gambit.sticky"] = "重要";
$definitions["gambit.unread"] = "未读";
$definitions["gambit.more results"] = "更多结果";

// Aliases
// These are gambits which tell the gambit system to use another gambit.
// In other words, when you type "active today", the gambit system interprets it as if you typed "active 1 day".
// The first of each pair, the alias, can be anything you want.
// The second, however, must fit with the regular expression pattern defined below (more on that later.)
$definitions["gambit.active today"] = "今日活跃会话"; // what appears in the gambit cloud
$definitions["gambit.active 1 day"] = "本日活跃会话"; // what it actually evaluates to

$definitions["gambit.has replies"] = "有回复";
$definitions["gambit.has >0 replies"] = "有回复";
$definitions["gambit.has >10 replies"] = "至少有十条回复";

$definitions["gambit.has no replies"] = "没有回复";
$definitions["gambit.has 0 replies"] = "没有回复";

$definitions["gambit.dead"] = "被删除";
$definitions["gambit.active >30 day"] = "本月活跃会话";

// Units of time
// These are used in the active gambit.
// ex. "[active] [>|<|>=|<=|last] 180 [second|minute|hour|day|week|month|year]"
$definitions["gambit.second"] = "秒";
$definitions["gambit.minute"] = "分钟";
$definitions["gambit.hour"] = "小时";
$definitions["gambit.day"] = "天";
$definitions["gambit.week"] = "星期";
$definitions["gambit.month"] = "月";
$definitions["gambit.year"] = "年";
$definitions["gambit.last"] = "近"; // as in "active last 180 days"
$definitions["gambit.active"] = "活跃于"; // as in "active last 180 days"

// Now the hard bit. This is a regular expression to test for the "active" gambit.
// The group (?<a> ... ) is the comparison operator (>, <, >=, <=, or last).
// The group (?<b> ... ) is the number (ex. 24).
// The group (?<c> ... ) is the unit of time.
// The languages of "last" and the units of time are defined above.
// However, if you need to reorder the groups, do so carefully, and make sure spaces are written as " *".
$definitions["gambit.gambitActive"] = "/^{$definitions["gambit.active"]} *(?<a>>|<|>=|<=|{$definitions["gambit.last"]})? *(?<b>\d+) *(?<c>{$definitions["gambit.second"]}|{$definitions["gambit.minute"]}|{$definitions["gambit.hour"]}|{$definitions["gambit.day"]}|{$definitions["gambit.week"]}|{$definitions["gambit.month"]}|{$definitions["gambit.year"]})/";

// These appear in the tag cloud. They must fit the regular expression pattern where the ? is a number.
// If the regular expression pattern has been reordered, these gambits must also be reordered (as well as the ones in aliases.)
$definitions["gambit.active last ? hours"] = "{$definitions["gambit.active"]}{$definitions["gambit.last"]} ? {$definitions["gambit.hour"]}";
$definitions["gambit.active last ? days"] = "{$definitions["gambit.active"]}{$definitions["gambit.last"]} ? {$definitions["gambit.day"]}";

// This is similar to the regular expression for the active gambit, but for the "has n reply(s)" gambit.
// Usually you just need to change the "has" and "repl".
$definitions["gambit.gambitHasNReplies"] = "/^has *(?<a>>|<|>=|<=)? *(?<b>\d+) *repl/";