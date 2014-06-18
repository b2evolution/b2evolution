<?php
/**
 * Function for handling Classes in PHP 4.
 *
 * In PHP5, _class5.funcs.php should be used instead.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * @version $Id: _class4.funcs.php 6135 2014-03-08 07:54:05Z manuel $
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