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
protected $path;


/**
 * Class constructor, which sets up the plugin so it knows which folder it's in.
 *
 * @param string $path The path to the directory, from the esoTalk root, which this plugin resides in.
 * @return void
 */
public function __construct($path)
{
	$this->path = $path;
}


/**
 * This function is called on each page load directly after the plugin is instantiated.
 * It should be used to perform bootstrapping tasks (e.g. register/override factory classes.)
 */
public function boot()
{
}


/**
 * This function is called on each page load before the controller is initialized and dispatched.
 * It should be used to initialize plugin-specific things such as language definitions.
 */
public function init()
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


public function file($file, $absolute = false)
{
	return ($absolute ? PATH_ROOT."/" : "").$this->path."/".$file;
}


/**
 * Get the relative path to a resource contained within the plugin's resources folder.
 *
 * @param string $file The name of the resource file.
 * @return string The path to the resource, relative to the esoTalk root.
 */
public function resource($file)
{
	return $this->file("resources/".$file);
}


/**
 * Get the full path to a view contained within the plugin folder.
 *
 * @param string $view The name of the view.
 * @return string The absolute path to the view.
 */
public function view($file)
{
	return $this->file("views/".$file, true);
}

}
