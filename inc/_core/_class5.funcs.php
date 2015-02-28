<?php
/**
 * Function for handling Classes in PHP 5.
 *
 * In PHP4, _class4.funcs.php should be used instead.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2010-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Dynamic list of class mapping.
 *
 * Controllers should call load_class() to register classes they may need to have autoloaded when they use them.
 *
 * @var marray
 */
$map_class_path = array();

/**
 * Autoload the required .class.php file when a class is accessed but not defined yet.
 * This gets hooked into spl_autoload_register (preferred) or called through __autoload.
 * Requires PHP5.
 */
function evocms_autoload_class( $classname )
{
	global $map_class_path;

	$classname = strtolower($classname);
	if( isset($map_class_path[$classname]) )
	{
		require_once $map_class_path[$classname];
	}
}


/*
 * Use spl_autoload_register mechanism, if available (PHP>=5.1.2).
 * This way a stacked set of autoload functions can be used.
 */
if( function_exists('spl_autoload_register') )
{
	// spl_autoload_register( 'var_dump' );
	spl_autoload_register( 'evocms_autoload_class' );
}
else
{
	// PHP<5.1.2: Use the fallback method.
	function __autoload( $classname )
	{
		return evocms_autoload_class($classname);
	}
}


/**
 * In PHP4, this really loads the class. In PHP5, it's smarter than that:
 * It only registers the class & file name so that __autoload() can later
 * load the class IF and ONLY IF the class is actually needed during execution.
 */
function load_class( $class_path, $classname )
{
	global $map_class_path, $inc_path;
	if( !is_null($classname) )
	{
		$map_class_path[strtolower($classname)] = $inc_path.$class_path;
	}
	return true;
}


/**
 * Create a copy of an object (abstraction from PHP4 vs PHP5)
 */
function duplicate( $Obj )
{
	$Copy = clone $Obj;
	return $Copy;
}

?>