<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * db_delete(-)
 */
function db_delete()
{
	global $DB;

	echo "Dropping Antispam table...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_antispam' );

	echo "Dropping Hit-Logs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_hitlog' );

	echo "Dropping Comments...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_comments' );

	echo "Dropping Categories-to-Posts relationships...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_postcats' );

	echo "Dropping Categories...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_categories' );

	echo "Dropping Posts...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_posts' );

	echo "Dropping User Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_usersettings' );

 	echo "Dropping User sessions...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_sessions' );

	echo "Dropping Users...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_users' );

	echo "Dropping Groups...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_groups' );

	echo "Dropping Blogs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_blogs' );

	echo "Dropping Blogusers...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_blogusers' );

	echo "Dropping Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_settings' );

	echo "Dropping Locales...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_locales' );

	echo "Dropping User Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_usersettings' );

	echo "Dropping Plugins registrations...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_plugins' );
}

?>