<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * db_delete(-)
 */
function db_delete()
{
	global $DB, $db_config, $tableprefix;

	echo "Disabling foreign key checks...<br />\n";
	$DB->query( 'SET FOREIGN_KEY_CHECKS=0' );

	foreach( $db_config['aliases'] as $alias => $tablename )
	{
		echo "Dropping $tablename table...<br />\n";
		evo_flush();
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}

	// Get remaining tables with the same prefix and delete them as well. Probably these tables are some b2evolution plugins tables.
	$remaining_tables = $DB->get_col( 'SHOW TABLES FROM `'.$db_config['name'].'` LIKE "'.$tableprefix.'%"' );
	foreach( $remaining_tables as $tablename )
	{
		echo "Dropping $tablename table...<br />\n";
		evo_flush();
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}
}

?>