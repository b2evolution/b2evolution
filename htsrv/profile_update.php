<?php
/**
 * This file updates the current user's profile!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 *
 *
 * @todo integrate it into the skins to avoid ugly die() on error and confusing redirect on success.
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// Check if the request exceed the post max size. If it does then the function will a call header_redirect.
check_post_max_size_exceeded();

$action = param_action();
$disp = param( 'user_tab', 'string', '' );
$blog = param( 'blog', 'integer', 0 );

// Activate the blog locale because all params were introduced with that locale
activate_blog_locale( $blog );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{	// must be logged in!
	bad_request_die( T_( 'You are not logged in.' ) );
}

if( $demo_mode && ( $current_User->ID <= 3 ) )
{
	bad_request_die( 'Demo mode: you can\'t edit the admin and demo users profile!<br />[<a href="javascript:history.go(-1)">'
		. T_('Back to profile') . '</a>]' );
}

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'user' );

switch( $action )
{
	case 'add_field':
	case 'update':
	case 'subscribe':
		$current_User->update_from_request();
		break;

	case 'refresh_regional':
		// Refresh a regions, sub-regions & cities (when JavaScript is disabled)
		$current_User->ctry_ID = param( 'edited_user_ctry_ID', 'integer', 0 );
		$current_User->rgn_ID = param( 'edited_user_rgn_ID', 'integer', 0 );
		$current_User->subrg_ID = param( 'edited_user_subrg_ID', 'integer', 0 );
		break;

	case 'update_avatar':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->update_avatar( $file_ID );
		break;

	case 'rotate_avatar_90_left':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->rotate_avatar( $file_ID, 90 );
		break;

	case 'rotate_avatar_180':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->rotate_avatar( $file_ID, 180 );
		break;

	case 'rotate_avatar_90_right':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->rotate_avatar( $file_ID, 270 );
		break;

	case 'remove_avatar':
		$current_User->remove_avatar();
		break;

	case 'delete_avatar':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->delete_avatar( $file_ID );
		break;

	case 'upload_avatar':
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		$current_User->update_avatar_from_upload();
		break;

	case 'redemption':
		// Change status of user email to 'redemption'
		$EmailAddressCache = & get_EmailAddressCache();
		if( $EmailAddress = & $EmailAddressCache->get_by_name( $current_User->get( 'email' ), false, false ) &&
		    in_array( $EmailAddress->get( 'status' ), array( 'warning', 'suspicious1', 'suspicious2', 'suspicious3', 'prmerror' ) ) )
		{ // Change to 'redemption' status only if status is 'warning', 'suspicious1', 'suspicious2', 'suspicious3' or 'prmerror'
			$EmailAddress->set( 'status', 'redemption' );
			$EmailAddress->dbupdate();
		}
		break;

	case 'crop':
		// crop profile picture
		$file_ID = param( 'file_ID', 'integer', NULL );

		// Check data to crop
		$image_crop_data = param( 'image_crop_data', 'string', '' );
		$image_crop_data = empty( $image_crop_data ) ? array() : explode( ':', $image_crop_data );
		foreach( $image_crop_data as $image_crop_value )
		{
			$image_crop_value = (float)$image_crop_value;
			if( $image_crop_value < 0 || $image_crop_value > 100 )
			{ // Wrong data to crop, This value is percent of real size, so restrict it from 0 and to 100
				$action = 'view';
				break 2;
			}
		}
		if( count( $image_crop_data ) < 4 )
		{ // Wrong data to crop
			$action = 'view';
			break;
		}

		$result = $current_User->crop_avatar( $file_ID, $image_crop_data[0], $image_crop_data[1], $image_crop_data[2], $image_crop_data[3] );
		if( $result !== true )
		{ // If error on crop action then redirect to avatar profile page
			header_redirect( $redirect_to );
		}
		break;
}

$Blog = NULL;
if( $blog > 0 )
{	// Get Blog
	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
}

if( empty( $Blog ) )
{	// This case should not happen, $blog must be set
	$Messages->add( T_( 'Unable to find the selected blog' ), 'error' );
	header_redirect( $baseurl );
}

if( param_errors_detected() || $action == 'refresh_regional' )
{	// unable to update, store unsaved user into session
	$Session->set( 'core.unsaved_User', $current_User );
}
elseif( ! param_errors_detected() )
{	// update was successful on user profile
	switch( $action )
	{
		case 'update':
			if( $current_User->has_avatar() )
			{	// Redirect to display user page
				$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=user', '&' );
			}
			else
			{	// Redirect to upload avatar
				$redirect_to = get_user_avatar_url();
			}
			break;
		case 'upload_avatar':
			// Redirect to display user profile form
			$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=profile', '&' );
			break;
	}
	if( !empty( $redirect_to ) )
	{
		header_redirect( $redirect_to );
	}
}


if( ! param_errors_detected() || ! isset( $disp ) )
{	// User data is updated without errors
	// redirect will save $Messages into Session:
	$redirect_to = NULL;
	if( isset( $disp ) )
	{
		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp, '&' );
	}
	// redirect to the corresponding display form
	header_redirect( $redirect_to );
	// EXITED
}
else
{	// Errors exist; Don't redirect; Display a template to save a received data from request
	$SkinCache = & get_SkinCache();
	$Skin = & $SkinCache->get_by_ID( $Blog->get_skin_ID() );
	$skin = $Skin->folder;
	$ads_current_skin_path = $skins_path.$skin.'/';
	require $ads_current_skin_path.'index.main.php';
}

?>