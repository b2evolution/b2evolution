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
require_once dirname(__FILE__).'/../admin/_header.php';

// Check permission:
$current_User->check_perm( 'files', 'view', true );

// Load params:
param( 'root', 'string', true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', true );

// Load fileroot info:
$FileRoot = & $FileRootCache->get_by_ID( $root );

// Create file object
$File = & new File( $FileRoot->type , $FileRoot->in_type_ID, $path );

// Headers to display the file directly in the browser
header('Content-type: '. $File->Filetype->mimetype );
header('Content-Length: '.filesize( $File->get_full_path() ) );

if( $File->Filetype->viewtype == 'download' )
{
	header('Content-disposition: attachment; filename="'.$File->get_name().'"' );
}

// Display the content of the file
readfile( $File->get_full_path() );

?>