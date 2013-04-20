<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller handles the management of plugins.
 *
 * @package esoTalk
 */
class ETLanguagesAdminController extends ETAdminController {


/**
 * Show the list of languages.
 *
 * @return void
 */
public function index()
{
	$languages = ET::getLanguages();
	$languagesNew = array();
	foreach ($languages as $k => $v) $languagesNew[$v] = ET::$languageInfo[$v];

	$this->title = T("Languages");
	$this->data("languages", $languagesNew);
	$this->render("admin/languages");
}


/**
 * Uninstall a language by removing its directory.
 *
 * @param string $language The name of the language.
 * @return void
 */
public function uninstall($language = "")
{
	if (!$this->validateToken()) return;

	// Make sure the language exists.
	$languages = ET::getLanguages();
	if (!$language or !in_array($language, $languages)) return;

	// Attempt to remove the directory. If we couldn't, show a "not writable" message.
	if (!is_writable($file = PATH_LANGUAGES) or !is_writable($file = PATH_LANGUAGES."/$language") or !rrmdir($file))
		$this->message(sprintf(T("message.notWritable"), $file), "warning");

	// Otherwise, show a success message.
	else $this->message(T("message.languageUninstalled"), "success");

	$this->redirect(URL("admin/languages"));
}

}