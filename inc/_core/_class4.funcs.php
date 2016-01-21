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
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

/**
 * Rebuilds a url using provided components
 */
function rebuild_url( $url, $comp = array() )
{
	$u = '';
	$url = parse_url( $url );
	
	foreach( $comp as $c => $val )
	{
		$url[$c] = $val;
	}

	$u = ( ( isset( $url['scheme'] ) ) ? $url['scheme'] . '://' : 'http://')
			.( ( isset( $url['user'] ) ) ? $url['user'] . ( ( isset( $url['pass'] ) ) ? ':' . $url['pass'] : '' ) .'@' : '' )
			.( ( isset( $url['host'] ) ) ? $url['host'] : '' )
			.( ( isset( $url['port'] ) ) ? ':' . $url['port'] : '' )
			.( ( isset( $url['path'] ) ) ? $url['path'] : '' )
			.( ( isset( $url['query'] ) ) ? '?' . $url['query'] : '' )
			.( ( isset( $url['fragment'] ) ) ? '#' . $url['fragment'] : '' );
	
	return $u;	
}

?>