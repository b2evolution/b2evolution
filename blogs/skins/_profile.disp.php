<?php
/**
 * This is the template that displays the user profile form. It gets POSTed to /htsrv/profile_update.php.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=profile
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI grants Francois PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _profile.disp.php 6411 2014-04-07 15:17:33Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Blog, $Session, $Messages, $inc_path;
global $action, $user_profile_only, $edited_User, $form_action;

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$form_action = get_secure_htsrv_url().'profile_update.php';

// set params
if( !isset( $params ) )
{
	$params = array();
}

$params = array_merge( array(
	'profile_tabs' => array(
			'block_start'         => '<div class="tabs">',
			'item_start'          => '<div class="option">',
			'item_end'            => '</div>',
			'item_selected_start' => '<div class="selected">',
			'item_selected_end'   => '</div>',
			'block_end'           => '</div><div class="clear"></div>',
		),
	), $params );


$user_profile_only = true;
// check if there is unsaved User object stored in Session
$edited_User = $Session->get( 'core.unsaved_User' );
if( $edited_User == NULL )
{ // edited_User is the current_User
	$edited_User = $current_User;
}
else
{ // unsaved user exists, delete it from Session
	$Session->delete( 'core.unsaved_User' );
	if( $edited_User->ID != $current_User->ID )
	{ // edited user ID must be the same as current User
		debug_die( 'Inconsistent state, you are allowed to edit only your profile' );
	}
}

// Display tabs
echo $params['profile_tabs']['block_start'];
$entries = get_user_sub_entries( false, NULL );
foreach( $entries as $entry => $entry_data )
{
	if( $entry == $disp )
	{
		echo $params['profile_tabs']['item_selected_start'];
	}
	else
	{
		echo $params['profile_tabs']['item_start'];
	}
	echo '<a href='.$entry_data['href'].'>'.$entry_data['text'].'</a>';
	if( $entry == $disp )
	{
		echo $params['profile_tabs']['item_selected_end'];
	}
	else
	{
		echo $params['profile_tabs']['item_end'];
	}
}
echo $params['profile_tabs']['block_end'];

// Display form
switch( $disp )
{
	case 'profile':
		require $inc_path.'users/views/_user_identity.form.php';
		break;
	case 'avatar':
		require $inc_path.'users/views/_user_avatar.form.php';
		break;
	case 'pwdchange':
		require $inc_path.'users/views/_user_password.form.php';
		break;
	case 'userprefs':
		require $inc_path.'users/views/_user_preferences.form.php';
		break;
	case 'subs':
		require $inc_path.'users/views/_user_subscriptions.form.php';
		break;
	default:
		debug_die( "Unknown user tab" );
}

?>