<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * ETPluggable defines a class that can be "plugged into", i.e. that can trigger custom
 * events at any time. This class can be extended and trigger can be called to trigger
 * an event.
 * 
 * Events should be named with the global namespace in mind, as any event triggered will
 * be triggered globally as well. For example, all ETControllers call the "init" event when
 * setting themselves up, and this will call any plugin with a handler_init function regardless
 * of what controller is calling it.
 * 
 * @package esoTalk
 */
class ETPluggable {


/**
 * The class name to use as a prefix when triggering local events.
 * 
 * For example, if an instance of ETPluggable calls $this->trigger("eventName"), the
 * event fired will be "ETPluggable_eventName". This is public so that it can be
 * overridden if the object is created through ETFactory.
 * 
 * @var string
 */
public $className;


/**
 * The constructor, which sets up the class name to use as a prefix when triggering events.
 * 
 * If any extending class overrides __construct, it must call parent::__construct().
 */
public function __construct()
{
	$this->className = get_class($this);
}


/**
 * Triggers an event, returning an array of return values from event handlers.
 * 
 * Two events will actually be triggered: one prefixed with the name of this class,
 * one not. For example, if an instance of ETPluggable calls $this->trigger("eventName"),
 * both "ETPluggable_eventName" and "eventName" events will be triggered.
 * 
 * The event handlers are called with $this as the first argument, and optionally any extra
 * $parameters. The return values from each handler are collected and then returned in an array.
 * 
 * @param string $event The name of the event.
 * @param array $parameters An array of extra parameters to pass to the event handlers. 
 */
public function trigger($event, $parameters = array())
{
	// Add the instance of this class to the parameters.
	array_unshift($parameters, $this);

	$return = array();

	// If we have a class name to use, trigger an event with that as the prefix.
	if ($this->className)
		$return = ET::trigger($this->className."_".$event, $parameters);

	// Trigger the event globally.
	$return = array_merge($return, ET::trigger($event, $parameters));

	return $return;
}

}

?>