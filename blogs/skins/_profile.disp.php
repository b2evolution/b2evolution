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
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Blog, $Session, $Messages, $inc_path;
global $action, $user_profile_only, $edited_User, $form_action;

$form_action = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp, '&' );

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$user_profile_only = true;
// edited_User is always the current_User
$edited_User = $current_User;

$action = param_action();

if( !empty( $action ) )
{ // Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'user' );
}

switch( $action )
{
	case 'update_avatar':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->update_avatar( $file_ID );
		$Messages->display();
		break;

	case 'remove_avatar':
		$current_User->remove_avatar();
		$Messages->display();
		break;

	case 'update':
	case 'add_field':
		$current_User->update_from_request();
		$Messages->display();
		break;

	case 'upload_avatar':
		$current_User->update_avatar_from_upload();
		$Messages->display();
		break;
}

// Display tabs
echo '<div class="tabs">';
$entries = get_user_sub_entries( false, NULL );
foreach( $entries as $entry => $entry_data )
{
	if( $entry == $disp )
	{
		echo '<div class="selected">';
	}
	else
	{
		echo '<div class="option">';
	}
	echo '<a href='.$entry_data['href'].'>'.$entry_data['text'].'</a>';
	echo '</div>';
}
echo '</div>';

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
	default:
		debug_die( "Unknown user tab" );
}


/*
 * $Log$
 * Revision 1.27  2011/10/20 12:14:55  efy-yurybakh
 * Allow/disabled multiple instances of same field
 *
 * Revision 1.26  2011/10/18 16:20:38  efy-yurybakh
 * Ajax implementation of "add field"
 *
 * Revision 1.25  2011/09/17 02:31:58  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.24  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.23  2011/05/11 07:11:52  efy-asimo
 * User settings update
 *
 * Revision 1.22  2011/05/09 06:38:19  efy-asimo
 * Simple avatar modification update
 *
 * Revision 1.21  2011/04/06 13:30:56  efy-asimo
 * Refactor profile display
 *
 * Revision 1.20  2011/03/04 08:20:45  efy-asimo
 * Simple avatar upload in the front office
 *
 * Revision 1.19  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.18  2010/11/24 16:05:52  efy-asimo
 * User country and gender options modifications
 *
 * Revision 1.17  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.16  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.15  2010/07/19 09:35:03  efy-asimo
 * Fix messaging permission setup
 * Update comments number per page
 *
 * Revision 1.14  2010/02/23 05:07:18  sam2kb
 * New plugin hooks: DisplayProfileFormFieldset and ProfileFormSent
 *
 * Revision 1.13  2010/02/08 17:56:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.12  2010/01/30 18:55:37  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.11  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.10  2009/09/13 12:27:28  tblue246
 * Only display link to change to the avatar if user has appropriate permissions
 *
 * Revision 1.9  2009/03/20 03:38:04  fplanque
 * rollback -- http://forums.b2evolution.net/viewtopic.php?t=18269
 *
 * Revision 1.6  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.5  2009/02/28 23:51:59  blueyed
 * Add autocomplete=off to password fields in user profile, so that FF3 does not prefill the first one (only).
 *
 * Revision 1.4  2008/09/29 08:30:39  fplanque
 * Avatar support
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2008/01/11 19:18:14  fplanque
 * minor
 *
 * Revision 1.1  2007/11/29 19:29:23  fplanque
 * normalized skin filenames
 *
 * Revision 1.39  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.38  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.37  2006/12/16 00:15:51  fplanque
 * reorganized user profile page/form
 *
 * Revision 1.36  2006/12/16 00:12:21  fplanque
 * reorganized user profile page/form
 *
 * Revision 1.35  2006/12/09 01:55:37  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.34  2006/12/07 23:13:14  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.33  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.32  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.31  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.30  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.29  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.28  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>