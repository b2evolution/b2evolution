<?php
/**
 * This file updates the current user's avatar!
 * 
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * 
 * @package htsrv
 * 
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// load classes
load_class( 'files/model/_fileroot.class.php', 'FileRoot' );

global $Session;

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'avatarform' );

// Getting GET or POST parameters:
param( 'checkuser_id', 'integer', '' );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

if( $checkuser_id != $current_User->ID )
{ // Can only edit your own profile
	bad_request_die( 'You are not logged in under the same account you are trying to modify.' );
}

param( 'update_avatar', 'string', '' );
param( 'remove_avatar', 'string', '' );
if( !empty( $update_avatar ) )
{ // update to an existing avatar image
	$current_User->set( 'avatar_file_ID', $update_avatar, true );
	$current_User->dbupdate();
	$Messages->add( T_( 'Avatar has been changed' ), 'success' );
}
elseif ( !empty( $remove_avatar ) )
{ // remove user avatar
	$current_User->set( 'avatar_file_ID', NULL, true );
	$current_User->dbupdate();
	$Messages->add( 'Avatar has been removed', 'success' );
}
else
{ // upload new avatar image
	$root = FileRoot::gen_ID( 'user', $current_User->ID );
	$result = process_upload( $root, 'profile_pictures', true, false, true, false );
	if( $result == NULL )
	{ // unsuccessful upload
		$Messages->add( T_( 'You don\'t have permission to upload!' ) );
	}
	else
	{
		$uploadedFiles = $result['uploadedFiles'];
		if( !empty( $uploadedFiles ) )
		{ // successful upload
			$File = $uploadedFiles[0];
			if( $File->is_image() )
			{ // set uploaded image as avatar
				$current_User->set( 'avatar_file_ID', $File->ID, true );
				$current_User->dbupdate();
				$Messages->add( T_('Avatar has been set.'), 'success' );
			}
			else
			{ // uploaded file is not an image, delete the file
				$Messages->add( T_( 'You can only set an image file to avatar!' ) );
				$File->unlink();
			}
		}
		else
		{
			$failedFiles = $result['failedFiles'];
			if( !empty( $failedFiles ) )
			{ // show error message
				$Messages->add( $failedFiles[0] );
			}
		}
	}
}

// redirect Will save $Messages into Session:
header_redirect();


/**
 * $Log$
 * Revision 1.3  2011/09/05 15:28:39  sam2kb
 * minor
 *
 * Revision 1.2  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/03/04 08:20:45  efy-asimo
 * Simple avatar upload in the front office
 *
 */
?>