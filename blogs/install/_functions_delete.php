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
	global $DB, $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments, $tableblogs,
				$tablepostcats, $tablehitlog, $tableantispam, $tablegroups, $tableblogusers, $tablelocales;

	echo "Dropping Antispam table...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableantispam";
	$DB->query( $query );

	echo "Dropping Hit-Logs...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablehitlog";
	$DB->query( $query );

	echo "Dropping Comments...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablecomments";
	$DB->query( $query );

	echo "Dropping Categories-to-Posts relationships...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablepostcats";
	$DB->query( $query );

	echo "Dropping Categories...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablecategories";
	$DB->query( $query );

	echo "Dropping Posts...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableposts";
	$DB->query( $query );

	echo "Dropping Users...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableusers";
	$DB->query( $query );

	echo "Dropping Groups...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablegroups";
	$DB->query( $query );

	echo "Dropping Blogs...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableblogs";
	$DB->query( $query );

	echo "Dropping Blogusers...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableblogusers";
	$DB->query( $query );

	echo "Dropping Settings...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablesettings";
	$DB->query( $query );

	echo "Dropping Locales...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablelocales";
	$DB->query( $query );
}

?>