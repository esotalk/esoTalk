<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Emoticons"] = array(
	"name" => "Emoticons",
	"description" => "Converts text emoticons to their graphical equivalent.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);

class ETPlugin_Emoticons extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addToHead("<style type='text/css'>.emoticon {display:inline-block; text-indent:-9999px; width:16px; height:16px; background:url(".URL($this->getResource("emoticons.png"))."); background-repeat:no-repeat}</style>");
}
public function handler_memberController_renderBefore($sender)
{
	$this->handler_conversationController_renderBefore($sender);
}

public function handler_format_format($sender)
{
	if ($sender->inline) return;

	$styles = array();
	$styles[":)"] = "background-position:0 0";
	$styles["=)"] = "background-position:0 0";
	$styles[":D"] = "background-position:0 -20px";
	$styles["=D"] = "background-position:0 -20px";
	$styles["^_^"] = "background-position:0 -40px";
	$styles["^^"] = "background-position:0 -40px";
	$styles[":("] = "background-position:0 -60px";
	$styles["=("] = "background-position:0 -60px";
	$styles["-_-"] = "background-position:0 -80px";
	$styles[";)"] = "background-position:0 -100px";
	$styles["^_-"] = "background-position:0 -100px";
	$styles["~_-"] = "background-position:0 -100px";
	$styles["-_^"] = "background-position:0 -100px";
	$styles["-_~"] = "background-position:0 -100px";
	$styles["^_^;"] = "background-position:0 -120px; width:18px";
	$styles["^^;"] = "background-position:0 -120px; width:18px";
	$styles[">_<"] = "background-position:0 -140px";
	$styles[":/"] = "background-position:0 -160px";
	$styles["=/"] = "background-position:0 -160px";
	$styles[":\\"] = "background-position:0 -160px";
	$styles["=\\"] = "background-position:0 -160px";
	$styles[":x"] = "background-position:0 -180px";
	$styles["=x"] = "background-position:0 -180px";
	$styles[":|"] = "background-position:0 -180px";
	$styles["=|"] = "background-position:0 -180px";
	$styles["'_'"] = "background-position:0 -180px";
	$styles["<_<"] = "background-position:0 -200px";
	$styles[">_>"] = "background-position:0 -220px";
	$styles["x_x"] = "background-position:0 -240px";
	$styles["o_O"] = "background-position:0 -260px";
	$styles["O_o"] = "background-position:0 -260px";
	$styles["o_0"] = "background-position:0 -260px";
	$styles["0_o"] = "background-position:0 -260px";
	$styles[";_;"] = "background-position:0 -280px";
	$styles[":'("] = "background-position:0 -280px";
	$styles[":O"] = "background-position:0 -300px";
	$styles["=O"] = "background-position:0 -300px";
	$styles[":o"] = "background-position:0 -300px";
	$styles["=o"] = "background-position:0 -300px";
	$styles[":P"] = "background-position:0 -320px";
	$styles["=P"] = "background-position:0 -320px";
	$styles[";P"] = "background-position:0 -320px";
	$styles[":["] = "background-position:0 -340px";
	$styles["=["] = "background-position:0 -340px";
	$styles[":3"] = "background-position:0 -360px";
	$styles["=3"] = "background-position:0 -360px";
	$styles["._.;"] = "background-position:0 -380px; width:18px";
	$styles["<(^.^)>"] = "background-position:0 -400px; width:19px";
	$styles["(>'.')>"] = "background-position:0 -400px; width:19px";
	$styles["(>^.^)>"] = "background-position:0 -400px; width:19px";
	$styles["-_-;"] = "background-position:0 -420px; width:18px";
	$styles["(o^_^o)"] = "background-position:0 -440px";
	$styles["(^_^)/"] = "background-position:0 -460px; width:19px";
	$styles[">:("] = "background-position:0 -480px";
	$styles[">:["] = "background-position:0 -480px";
	$styles["._."] = "background-position:0 -500px";
	$styles["T_T"] = "background-position:0 -520px";
	// $styles["XD"] = "background-position:0 -540px";
	$styles["('<"] = "background-position:0 -560px";
	// $styles["B)"] = "background-position:0 -580px";
	// $styles["XP"] = "background-position:0 -600px";
	$styles[":S"] = "background-position:0 -620px";
	$styles["=S"] = "background-position:0 -620px";
	$styles[">:)"] = "background-position:0 -640px";
	$styles[">:D"] = "background-position:0 -640px";

	$from = $to = array();
	foreach ($styles as $k => $v) {
		$quoted = preg_quote(sanitizeHTML($k), "/");
		$from[] = "/(?<=^|[\s.,!<>]){$quoted}(?=[\s.,!<>)]|$)/i";
		$to[] = "<span class='emoticon' style='$v'>$k</span>";
	}
	$sender->content = preg_replace($from, $to, $sender->content);
}

}
