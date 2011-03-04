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
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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

global $Settings, $admin_url;

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}
// --- //
$redirect_to = param( 'redirect_to', 'string', '' );


/**
 * form to update the profile
 * @var Form
 */
$ProfileForm = new Form( $htsrv_url_sensitive.'profile_update.php', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );

	$ProfileForm->add_crumb( 'profileform' );
	$ProfileForm->hidden( 'checkuser_id', $current_User->ID );
	$ProfileForm->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

$ProfileForm->begin_fieldset( T_('Email communications') );

	$ProfileForm->text_input( 'newuser_email', $current_User->get( 'email' ), 40, T_('Email'), '', array( 'maxlength' => 100, 'class' => 'bComment' ) );
	$Group = & $current_User->get_Group();
	$messaging_options = array(
		array( 'allow_pm', 1, '', $current_User->accepts_pm(), /*true = disabled*/! $Group->check_messaging_perm(), T_( 'Allow others to send me private messages' ) ),
		array( 'allow_email', 2, '',  $current_User->accepts_email(), /*true = disabled*/false, T_( 'Allow others to send me emails through a message form (email address will never be displayed)' ) ) );
	$ProfileForm->checklist( $messaging_options, 'newuser_allow_msgform', T_('Message form') );
	$ProfileForm->checkbox( 'newuser_notify', $current_User->get( 'notify' ), T_('Notifications'), T_('Check this to receive a notification whenever someone else comments on one of <strong>your</strong> posts.') );

$ProfileForm->end_fieldset();

$ProfileForm->begin_fieldset( T_('Identity') );

	if( $Settings->get('allow_avatars') )
	{
		$avatar_tag = $current_User->get_avatar_imgtag();
		if( $Settings->get( 'fm_enable_roots_user' ) )
		{
			$avatar_tag .= ' <a href="'.get_user_avatar_url().'">'.T_('change').' &raquo;</a>';
		}
		$ProfileForm->info( T_('Avatar'), $avatar_tag );
	}

	$ProfileForm->info( T_('Login'), $current_User->get('login') );
	$ProfileForm->text_input( 'newuser_firstname', $current_User->get( 'firstname' ), 40, T_('First name'), '', array( 'maxlength' => 50, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_lastname', $current_User->get( 'lastname' ), 40, T_('Last name'), '', array( 'maxlength' => 50, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_nickname', $current_User->get( 'nickname' ), 40, T_('Nickname'), '', array( 'maxlength' => 50, 'class' => 'bComment' ) );
	$ProfileForm->select( 'newuser_idmode', $current_User->get('idmode'), array( &$current_User, 'callback_optionsForIdMode' ), T_('Identity shown'), '', 'bComment' );

	$require_country = (bool)$Settings->get( 'registration_require_country' );
	$CountryCache = & get_CountryCache();
	$ProfileForm->select_input_object( 'newuser_ctry_ID', $current_User->ctry_ID, $CountryCache, T_( 'Country' ), array( 'allow_none' => !$require_country ) );

	$ProfileForm->checkbox( 'newuser_showonline', $current_User->get( 'showonline' ), T_('Show online'), T_('Check this to be displayed as online when visiting the site.') );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$ProfileForm->radio( 'newuser_gender', $current_User->get('gender'), array(
							array( 'M', T_('Male') ),
							array( 'F', T_('Female') ),
						), T_('Gender') );
	}

$ProfileForm->end_fieldset();

$ProfileForm->begin_fieldset( T_('Password') );

	$ProfileForm->password_input( 'pass1', '', 16, T_('New pass'), array( 'note' => T_('Leave blank to leave the password unchanged.'), 'maxlength' => 50, 'class' => 'bComment', 'autocomplete' => 'off' ) );
	$ProfileForm->password_input( 'pass2', '', 16, T_('Confirm'), array( 'note' => T_('Confirm new password by typing it again.')
		.' '.sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'class' => 'bComment', 'autocomplete' => 'off' ) );

$ProfileForm->end_fieldset();


$ProfileForm->begin_fieldset( T_('Preferences') );

	$ProfileForm->select( 'newuser_locale', $current_User->get( 'locale' ), 'locale_options_return', T_('Preferred locale'), '', 'bComment' );

$ProfileForm->end_fieldset();

$ProfileForm->begin_fieldset( T_('Additional info') );

	$ProfileForm->info( T_('Level'), $current_User->get('level') );
	$ProfileForm->info( T_('Posts'), $current_User->get('num_posts') );
	$ProfileForm->text_input( 'newuser_url', $current_User->get( 'url' ), 40, T_('URL'), '', array( 'maxlength' => 100, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_icq', $current_User->get( 'icq' ), 40, T_('ICQ'), '', array( 'maxlength' => 10, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_aim', $current_User->get( 'aim' ), 40, T_('AOL I.M.'), '', array( 'maxlength' => 50, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_msn', $current_User->get( 'msn' ), 40, T_('MSN I.M.'), '', array( 'maxlength' => 100, 'class' => 'bComment' ) );
	$ProfileForm->text_input( 'newuser_yim', $current_User->get( 'yim' ), 40, T_('Yahoo I.M.'), '', array( 'maxlength' => 50, 'class' => 'bComment' ) );

$ProfileForm->end_fieldset();

$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $ProfileForm, 'User' => & $current_User, 'edit_layout' => 'private' ) );

$ProfileForm->buttons( array( array( '', '', T_('Update'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$ProfileForm->end_form();


/*
 * $Log$
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