<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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

 	echo "Dropping Links...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_links' );

 	echo "Dropping Files...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_files' );

	echo "Dropping Posts...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_posts' );

	echo "Dropping Categories...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_categories' );

	echo "Dropping Post Statuses...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_poststatuses' );

	echo "Dropping Post Types...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_posttypes' );

	echo "Dropping User Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_usersettings' );

 	echo "Dropping User sessions...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_sessions' );

	echo "Dropping User permissions on Blogs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_coll_user_perms' );

	echo "Dropping User subscriptions on Blogs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_subscriptions' );

	echo "Dropping Users...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_users' );

	echo "Dropping Group permissions on Blogs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_coll_group_perms' );

	echo "Dropping Groups...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_groups' );

	echo "Dropping Blogs...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_blogs' );

	echo "Dropping Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_settings' );

	echo "Dropping Locales...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_locales' );

	echo "Dropping User Settings...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_usersettings' );

	echo "Dropping Plugins registrations...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_plugins' );

	echo "Dropping base domains...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_basedomains' );

	echo "Dropping user agents...<br />\n";
	$DB->query( 'DROP TABLE IF EXISTS T_useragents' );
}

?>