<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * db_delete(-)
 */
function db_delete()
{
	global $DB, $db_config;

	echo "Disabling foreign key checks...<br />\n";
	$DB->query( 'SET FOREIGN_KEY_CHECKS=0' );

	foreach( $db_config['aliases'] as $alias => $tablename )
	{
		echo "Dropping $tablename table...<br />\n";
		flush();
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}
}

/*
 * $Log$
 * Revision 1.39  2013/11/06 08:05:19  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>