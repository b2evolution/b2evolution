<?php
/**
 * This file is used to force download any file by link_ID.
 *
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @author fplanque: Francois PLANQUE.
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

apm_log_custom_param( 'LinkID', $link_ID );

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

apm_log_custom_param( 'FilePath', $download_File->get_full_path() );

if( $download_File->get_ext() == 'zip' )
{ // Redirect to direct url for ZIP files case
	// NOTE: The same hardcoded place is in the file "_link.class.php", function Link->get_download_url(), case 'action'
	header_redirect( $download_File->get_url(), 302 );
}
else
{ // For other files force the downloading
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
}
?>