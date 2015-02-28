<?php
/**
 * Function for handling Classes in PHP 4.
 *
 * In PHP5, _class5.funcs.php should be used instead.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Load class file
 *
 * @param string
 * @param string used by PHP5
 */
function load_class( $class_path, $ignore )
{
	global $inc_path;
	require_once $inc_path.$class_path;
	return true;
}

/**
 * Create a copy of an object (abstraction from PHP4 vs PHP5)
 */
function duplicate( $Obj )
{
	$Copy = $Obj;
	return $Copy;
}

?>