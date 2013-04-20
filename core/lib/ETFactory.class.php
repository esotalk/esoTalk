<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETFactory class provides a way to create instances of certain types of classes, without knowing what
 * the real class name is. This way, plugins can replace core esoTalk classes with their own variation.
 *
 * For example, a plugin could call register("database", "MyDatabaseClass", "path/to/mydatabaseclass.php")
 * and esoTalk would instantiate the database class with make("database"), without having to worry about what
 * changes plugins have made to the class registry.
 *
 * @package esoTalk
 */
class ETFactory {


/**
 * An array of registered classes and their class names.
 * @var array
 */
public static $classes = array();


/**
 * An array of registered controller classes and their class names.
 * @var array
 */
public static $controllers = array();


/**
 * An array of registered administrator controller classes and their class names.
 * @var array
 */
public static $adminControllers = array();


/**
 * Make and return a new instance of the class registered as $class.
 *
 * @param string $class The registered class identifier.
 * @param mixed $parameter,... Parameters to pass to the class constructor (up to 3.)
 * @return mixed The new instance of the class.
 */
public static function make($class, $parameter1 = null, $parameter2 = null, $parameter3 = null)
{
	$className = false;

	// If we don't have details for this class, see if a class with the same name exists and use that.
	if (!isset(self::$classes[$class])) $className = $class;

	// Otherwise, if we do have details but the file hasn't yet been included, attempt to include it.
	else {
		$className = self::$classes[$class][0];
		if (!class_exists($className)) {
			if (file_exists(self::$classes[$class][1])) require_once self::$classes[$class][1];
			else {
				throw new Exception("ETFactory: The file '".self::$classes[$class][1]."' for the class '$className' does not exist.");
			}
		}
	}

	if (!class_exists($className)) {
		throw new Exception("ETFactory: The class '$className' does not exist.");
	}
	else {
		$object = new $className($parameter1, $parameter2, $parameter3);
		$object->className = $class;
		return $object;
	}
}


/**
 * Register a class with the factory.
 *
 * @param string $class The name to register the class under.
 * @param string $className The real name of the class.
 * @param string $file The file which the class is contained within.
 * @return void
 */
public static function register($class, $className, $file = "")
{
	self::$classes[$class] = array($className, $file);
}


/**
 * Register a controller with the factory.
 *
 * @param string $slug The part of the URL associated with the controller (eg. conversation, member).
 * @param string $className The real name of the class.
 * @param string $file The file which the class is contained within.
 * @return void
 */
public static function registerController($slug, $className, $file)
{
	$newSlug = $slug."Controller";
	self::$controllers[$slug] = $newSlug;
	self::register($newSlug, $className, $file);
}


/**
 * Register an administrator controller with the factory.
 *
 * @param string $slug The part of the URL associated with the controller (eg. dashboard, settings).
 * @param string $className The real name of the class.
 * @param string $file The file which the class is contained within.
 * @return void
 */
public static function registerAdminController($slug, $className, $file)
{
	$newSlug = $slug."AdminController";
	self::$adminControllers[$slug] = $newSlug;
	self::register($newSlug, $className, $file);
}

}