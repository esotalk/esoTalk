<?php
// Copyright 2013 Joshua Rüsweg
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["StopForumSpam"] = array(
	"name" => "StopForumSpam",
	"description" => "Suspends a member if the member is listed in StopForumSpam",
	"version" => ESOTALK_VERSION,
	"author" => "Joshua Rüsweg",
	"authorEmail" => "josh@joshsboard.de",
	"authorURL" => "http://esotalk.org/forum/member/928-josh",
	"license" => "GPLv2"
);

class ETPlugin_StopForumSpam extends ETPlugin {
    
	const APIDOMAIN = 'http://www.stopforumspam.com/api';
	
	public function handler_memberModel_createAfter($sender, $values)
	{
		// check wheater the user is a spammer
		$email = $values['email'];
		$ip = $_SERVER['REMOTE_ADDR']; 
		
		$query_string = "ip=".$ip."&email=".trim($email)."&f=json";
		
		$result = $this->request($query_string);
		
		$result = json_decode($result, true);
		
		if (is_array($result) && isset($result['success']) && $result['success'] == 1) {
			unset($result['success']);
			
			foreach ($result AS $value) {
				if (isset($value['appears']) && $value['appears'] > 0) {
					// suspend
					ET::memberModel()->setGroups(ET::memberModel()->getById($values['memberId']), ACCOUNT_SUSPENDED);
					
					return; 
				}
			}
		}
	}
	
	public function request($query_string) {
		$curl = curl_init(self::APIDOMAIN."?".$query_string); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, 'CURLOPT_USERAGEN', "CURL (StopForumSpam; EsoTalk/".ESOTALK_VERSION.")");
		$res = curl_exec($curl);
		curl_close($curl); 
		
		return $res; 
	}
}
