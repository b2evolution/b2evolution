<?php
/**
 * This file includes upgrade settings for b2evolution.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * WordPress table prefix used exclusively by the upgrade script
 * You can ignore this if you're not planning to upgrade from a WordPress database.
 *
 * @global string
 */
$wp_prefix = 'wp_';


/**#@+
 * Old b2 tables used exclusively by the cafelog upgrade mode of the install script.
 *
 * @global string
 */
$oldtableposts      = 'b2posts';
$oldtableusers      = 'b2users';
$oldtablesettings   = 'b2settings';
$oldtablecategories = 'b2categories';
$oldtablecomments   = 'b2comments';
/**#@-*/



