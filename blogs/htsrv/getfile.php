<?php
/**
 * This file implements the File view.
 *
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 *
 * @version
 *
 */


/**
 * Load config, init and get the {@link $mode mode param}.
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';


// Check permission:
if( ! isset($current_User) )
{
	debug_die( 'No permission to get file (not logged in)!' );
}
$current_User->check_perm( 'files', 'view', true );

// Load params:
param( 'root', 'string', true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', true );

// Load fileroot info:
$FileRoot = & $FileRootCache->get_by_ID( $root );

// Create file object
$File = & new File( $FileRoot->type , $FileRoot->in_type_ID, $path );

// Headers to display the file directly in the browser
header('Content-type: '.$File->Filetype->mimetype );
header('Content-Length: '.filesize( $File->get_full_path() ) );

if( $File->Filetype->viewtype == 'download' )
{
	header('Content-disposition: attachment; filename="'.$File->get_name().'"' );
}

// Display the content of the file
readfile( $File->get_full_path() );

/*
 * $Log$
 * Revision 1.6  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>