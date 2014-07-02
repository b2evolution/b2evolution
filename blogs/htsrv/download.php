<?php
/**
 * This file is used to force download any file by link_ID.
 *
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: download.php 6459 2014-04-14 09:57:00Z yura $
 */

/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * HEAVY :(
 */
require_once $inc_path.'_main.inc.php';

/* ------------ Insert a goal hit in DB ------------ */
param( 'key', 'string', '' );
if( ! empty( $key ) )
{ // Try to record a goal hit
	$GoalCache = & get_GoalCache();
	if( $Goal = & $GoalCache->get_by_name( $key, false, false ) )
	{ // Record the goal hit
		$Goal->record_hit();
	}
}

/* ------------ Download file ------------ */
$link_ID = param( 'link_ID', 'integer', 0, true );

$LinkCache = & get_LinkCache();
if( ! (
		( $download_Link = & $LinkCache->get_by_ID( $link_ID, false, false ) ) && // Link exists in DB
		( $download_File = & $download_Link->get_File() ) && // Link has a correct File object
		( $download_File->exists() ) // File exists on the disk
	) )
{ // Bad request, Display "404 not found" page
	load_funcs( 'skins/_skin.funcs.php' );
	require $siteskins_path.'_404_not_found.main.php'; // error & exit
	exit(0);
}

// Set the headers to force download any file
header( 'Content-Description: File Transfer' );
header( 'Content-Type: application/octet-stream' );
header( 'Content-Disposition: attachment; filename='.$download_File->get_name() );
header( 'Expires: 0' );
header( 'Cache-Control: must-revalidate' );
header( 'Pragma: public' );
header( 'Content-Length: '.$download_File->get_size() );
// Print out file content
readfile( $download_File->get_full_path() );
?>