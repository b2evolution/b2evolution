<?php
/**
 * This is the template that displays the user profile form. It gets POSTed to /htsrv/profile_update.php.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=profile
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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


if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}
// --- //
$redirect_to = param( 'redirect_to', 'string', '' );


/**
 * @var Form form to update the profile
 */
$ProfileForm = & new Form( $htsrv_url_sensible.'profile_update.php', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );
$ProfileForm->hidden( 'checkuser_id', $current_User->ID );
$ProfileForm->hidden( 'redirect_to', $redirect_to );

$ProfileForm->info( T_('Login'), $current_User->get('login'), T_('ID').': '.$current_User->ID );
$ProfileForm->info( T_('Level'), $current_User->get('level') );
$ProfileForm->info( T_('Posts'), $current_User->get('num_posts') );

$ProfileForm->text_input( 'newuser_firstname', $current_User->get( 'firstname' ), 40, T_('First name'), array( 'maxlength' => 50, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_lastname', $current_User->get( 'lastname' ), 40, T_('Last name'), array( 'maxlength' => 50, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_nickname', $current_User->get( 'nickname' ), 40, T_('Nickname'), array( 'maxlength' => 50, 'class' => 'bComment' ) );


$ProfileForm->select( 'newuser_idmode', $current_User->get('idmode'), array( &$current_User, 'callback_optionsForIdMode' ), T_('Identity shown'), '', 'bComment' );

$ProfileForm->checkbox( 'newuser_showonline', $current_User->get( 'showonline' ), T_('Online'), T_('Check this to be displayed as online when visiting the site.') );
$ProfileForm->select( 'newuser_locale', $current_User->get( 'locale' ), 'locale_options_return', T_('Locale'), '', 'bComment' );
$ProfileForm->text_input( 'newuser_email', $current_User->get( 'email' ), 40, T_('Email'), array( 'maxlength' => 100, 'class' => 'bComment' ) );
$ProfileForm->checkbox( 'newuser_allow_msgform', $current_User->get('allow_msgform'), T_('Message form'), T_('Check this to allow receiving emails through a message form.') );
$ProfileForm->checkbox( 'newuser_notify', $current_User->get( 'notify' ), T_('Notifications'), T_('Check this to receive a notification whenever one of <strong>your</strong> posts receives comments, trackbacks, etc.') );
$ProfileForm->text_input( 'newuser_url', $current_User->get( 'url' ), 40, T_('URL'), array( 'maxlength' => 100, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_icq', $current_User->get( 'icq' ), 40, T_('ICQ'), array( 'maxlength' => 10, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_aim', $current_User->get( 'aim' ), 40, T_('AOL I.M.'), array( 'maxlength' => 50, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_msn', $current_User->get( 'msn' ), 40, T_('MSN I.M.'), array( 'maxlength' => 100, 'class' => 'bComment' ) );
$ProfileForm->text_input( 'newuser_yim', $current_User->get( 'yim' ), 40, T_('Yahoo I.M.'), array( 'maxlength' => 50, 'class' => 'bComment' ) );
$ProfileForm->password_input( 'pass1', '', 16, T_('New pass'), array( 'note' => T_('Leave blank to leave the password unchanged.'), 'maxlength' => 50, 'class' => 'bComment' ) );
$ProfileForm->password_input( 'pass2', '', 16, T_('Confirm'), array( 'note' => T_('Confirm new password by typing it again.'), 'maxlength' => 50, 'class' => 'bComment' ) );

$ProfileForm->buttons( array( array( '', '', T_('Update'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$ProfileForm->end_form();


/*
 * $Log$
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