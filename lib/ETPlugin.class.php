<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETPlugin class defines a plugin. All plugins should extend this class.
 * 
 * @package esoTalk
 */
abstract class ETPlugin extends ETPluggable {


/**
 * The path to the directory, from the esoTalk root, which this plugin resides in.
 * @var string
 */
protected $rootDirectory;


/**
 * Class constructor, which sets up the plugin so it knows which folder it's in.
 * 
 * @param string $rootDirectory The path to the directory, from the esoTalk root, which this plugin resides in.
 * @return void
 */
public function __construct($rootDirectory)
{
	$this->rootDirectory = $rootDirectory;
}


/**
 * This function is called on each page load before the controller is initialized and dispatched.
 * It should be used to initialize plugin-specific things such as language definitions.
 */
public function init()
{
}


/**
 * This function is called when the ETPluginsAdminController's settings method is called for the plugin.
 * Generally, it should create a form, perform any processing if there is a valid post back, and then return
 * the full path to the plugin's settings view to render (see getView()).
 * 
 * If this function does not return a view path, it is assumed that the plugin has no settings.
 * 
 * @param ETPluginsAdminController $sender
 * @return string
 */
public function settings($sender)
{
}


/**
 * This function is called when the plugin is enabled or upgraded. It should perform any setup tasks such as 
 * upgrading the database structure. If this function returns false, the plugin will be/remain disabled.
 * 
 * @param string $oldVersion The version that the plugin is being upgraded from.
 * @return bool
 */
public function setup($oldVersion = "")
{
	return true;
}


/**
 * This function is called when the plugin is disabled. It should perform any non-destructive cleanup tasks,
 * such as removing temporary caches.
 */
public function disable()
{
}


/**
 * This function is called when the plugin is uninstalled. It should perform any cleanup tasks, such as 
 * removing database tables.
 */
public function uninstall()
{	
}


/**
 * Get the relative path to a resource contained within the plugin's resources folder.
 * 
 * @param string $file The name of the resource file.
 * @param bool $absolutePath Whether or not to get the absolute filepath of the resource.
 * @return void
 */
public function getResource($file, $absolutePath = false)
{
	$path = $this->rootDirectory."/resources/".$file;
	//if (!$absolutePath) $path = getResource($path);
	return $path;
}


/**
 * Get the full path to a view contained within the plugin folder.
 * 
 * @param string $view The name of the view.
 * @return string
 */
public function getView($view)
{
	return PATH_ROOT."/".$this->rootDirectory."/views/".$view.".php";
}

}

?>