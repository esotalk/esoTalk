<?php
// Copyright 2014 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["GitHubLinks"] = array(
	"name" => "GitHub Links",
	"description" => "Parses posts for issue numbers (#123) and commit hashes (abc1234) and links them to GitHub.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);


class ETPlugin_GitHubLinks extends ETPlugin {


	protected function repositoryURL()
	{
		return ($r = C("GitHubLinks.repository")) ? "https://github.com/".$r : "";
	}

	protected function isolate($regexp)
	{
		return "/(?<=\s|^|>|\()$regexp(?=\)\s|[\s\.,?!>\)])/";
	}

	/**
	 * Add an event handler to the formatter to parse for issue/commit links.
	 *
	 * @return void
	 */
	public function handler_format_format($sender)
	{
		if (!$this->repositoryURL()) return;

		// Commit hashes
		$sender->content = preg_replace_callback($this->isolate("([0-9a-f]{7,40})"), array($this, "commitCallback"), $sender->content);

		// Issues
		$sender->content = preg_replace_callback($this->isolate("#(\d+)"), array($this, "issueCallback"), $sender->content);
	}

	public function commitCallback($matches)
	{
		return ET::formatter()->formatLink($this->repositoryURL()."/commit/".$matches[1], substr($matches[1], 0, 7));
	}

	public function issueCallback($matches)
	{
		return ET::formatter()->formatLink($this->repositoryURL()."/issues/".$matches[1], $matches[0]);
	}

	// Construct and process the settings form.
	public function settings($sender)
	{
		// Set up the settings form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/plugins");
		$form->setValue("repository", C("GitHubLinks.repository"));

		// If the form was submitted...
		if ($form->validPostBack()) {

			// Construct an array of config options to write.
			$config = array();
			$config["GitHubLinks.repository"] = $form->getValue("repository");

			// Write the config file.
			ET::writeConfig($config);

			$sender->message(T("message.changesSaved"), "success autoDismiss");
			$sender->redirect(URL("admin/plugins"));

		}

		$sender->data("gitHubLinksSettingsForm", $form);
		return $this->getView("settings");
	}

}
