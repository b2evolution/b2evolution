<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( substr(basename($_SERVER['SCRIPT_FILENAME']), 0, 1 ) == '_' )
	die( 'Please, do not access this page directly.' );


/**
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $DB, $old_db_version, $new_db_version, $tablesettings;

	echo '<p>'.T_('Checking DB schema version...').' ';
	$DB->query( "SELECT * FROM $tablesettings LIMIT 1" );
	
	if( $DB->get_col_info('name', 0) == 'set_name' )
	{ // we have new table format
		$old_db_version = $DB->get_var( "SELECT set_value FROM $tablesettings WHERE set_name = 'db_version'" );
	}
	else
	{
		$old_db_version = $DB->get_var( "SELECT db_version FROM $tablesettings" );
	}
	
	if( $old_db_version == NULL ) die( T_('NOT FOUND! This is not a b2evolution database.') );
	
	echo $old_db_version, ' : ';
	
	if( $old_db_version < 8000 ) die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) die( T_('This version is too recent! We cannot downgrade to it!') );
	echo "OK.<br />\n";
}

?>