<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}
}

/*
 * $Log$
 * Revision 1.38  2011/09/04 22:13:23  fplanque
 * copyright 2011
 *
 * Revision 1.37  2010/02/08 17:55:30  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.36  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.35  2008/01/21 09:35:38  fplanque
 * (c) 2008
 *
 * Revision 1.34  2007/09/22 22:12:10  fplanque
 * automated deletion
 *
 * Revision 1.33  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.32  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.31  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.30  2006/11/25 19:20:26  fplanque
 * MFB 1.9
 *
 * Revision 1.29  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.28  2006/03/01 23:43:30  blueyed
 * T_pluginusersettings
 *
 * Revision 1.27  2006/02/13 20:20:10  fplanque
 * minor / cleanup
 *
 * Revision 1.26  2005/12/30 18:08:24  fplanque
 * no message
 *
 */
?>