<?php
/**
 * Function for handling Classes in PHP 5.
 *
 * In PHP4, _class4.funcs.php should be used instead.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2010-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @version $Id: _class5.funcs.php 6135 2014-03-08 07:54:05Z manuel $
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