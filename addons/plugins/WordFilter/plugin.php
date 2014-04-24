<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["WordFilter"] = array(
	"name" => "Word Filter",
	"description" => "Perform find and replace on post content when posts are displayed.",
	"version" => ESOTALK_VERSION,
	"author" => "esoTalk Team",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2"
);


class ETPlugin_WordFilter extends ETPlugin {


	public function handler_format_format($sender)
	{
		$filters = C("plugin.WordFilter.filters", array());

		if (!count($filters)) return;

		// Pass each instance of any filtered word to our callback.
		$words = array_keys($filters);
		$sender->content = preg_replace_callback('#\b('.implode('|', $words).')\b#i', array($this, "filterCallback"), $sender->content);
	}


	public function filterCallback($matches)
	{
		$filters = C("plugin.WordFilter.filters", array());

		// Construct a mapping of lowercase words to their normal case in the filters array.
		$keys = array_keys($filters);
		$map = array();
		foreach ($keys as $key) {
			$map[strtolower($key)] = $key;
		}

		$match = $matches[1];

		// If there's a replacement for this particular casing of the word, use that.
		if (!empty($filters[$match])) $replacement = $filters[$match];

		// If there's a replacement for a lowercased version of this word, use that.
		elseif (!empty($filters[$map[strtolower($match)]])) $replacement = $filters[$map[strtolower($match)]];

		// Otherwise, use asterisks.
		else $replacement = str_repeat("*", strlen($match));

		return $replacement;
	}


	// Construct and process the settings form.
	public function settings($sender)
	{
		// Expand the filters array into a string that will go in the textarea.
		$filters = C("plugin.WordFilter.filters", array());
		$filterText = "";
		foreach ($filters as $word => $replacement) {
			$filterText .= $word.($replacement ? "|$replacement" : "")."\n";
		}
		$filterText = trim($filterText);

		// Set up the settings form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/plugins");
		$form->setValue("filters", $filterText);

		// If the form was submitted...
		if ($form->validPostBack("wordFilterSave")) {

			// Create an array of word filters from the contents of the textarea.
			// Each line is a new element in the array; keys and values are separated by a | character.
			$filters = array();
			$lines = explode("\n", strtr($form->getValue("filters"), array("\r\n" => "\n", "\r" => "\n")));
			foreach ($lines as $line) {
				if (!$line) continue;
				$parts = explode("|", $line, 2);
				if (!$parts[0]) continue;
				$filters[$parts[0]] = @$parts[1];
			}

			// Construct an array of config options to write.
			$config = array();
			$config["plugin.WordFilter.filters"] = $filters;

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success autoDismiss");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("wordFilterSettingsForm", $form);
		return $this->view("settings");
	}


}
