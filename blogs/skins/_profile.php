<?php
/**
 * This is the template that displays the user profile form
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
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * PROGIDISTRI grants François PLANQUE the right to license
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


if( ! is_logged_in() )
{ // must be logged in!
	echo '<p>', T_( 'You are not logged in.' ), '</p>';
	return;
}
// --- //
param( 'redirect_to', 'string', '');


/**
 * @var Form form to update the profile
 */
$ProfileForm = & new Form( $htsrv_url.'profile_update.php', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );
$ProfileForm->hidden( 'checkuser_id', $current_User->ID );
$ProfileForm->hidden( 'redirect_to', $redirect_to );

$ProfileForm->info( T_('Login'), $current_User->get('login'), T_('ID').': '.$current_User->ID );
$ProfileForm->info( T_('Level'), $current_User->get('level') );
$ProfileForm->info( T_('Posts'), $current_User->get('num_posts') );

$ProfileForm->text( 'newuser_firstname', $current_User->get( 'firstname' ), 40, T_('First name'), '', 50, 'bComment' );
$ProfileForm->text( 'newuser_lastname', $current_User->get( 'lastname' ), 40, T_('Last name'), '', 50, 'bComment' );
$ProfileForm->text( 'newuser_nickname', $current_User->get( 'nickname' ), 40, T_('Nickname'), '', 50, 'bComment' );


$ProfileForm->select( 'newuser_idmode', $current_User->get('idmode'), array( &$current_User, 'callback_optionsForIdMode' ), T_('Identity shown'), '', 'bComment' );

$ProfileForm->checkbox( 'newuser_showonline', $current_User->get( 'showonline' ), T_('Online'), T_('Check this to be displayed as online when visiting the site.') );
$ProfileForm->select( 'newuser_locale', $current_User->get( 'locale' ), 'locale_options_return', T_('Locale'), '', 'bComment' );
$ProfileForm->text( 'newuser_email', $current_User->get( 'email' ), 40, T_('Email'), '', 100, 'bComment' );
$ProfileForm->checkbox( 'newuser_notify', $current_User->get( 'notify' ), T_('Notifications'), T_('Check this to receive notification whenever one of your posts receives comments, trackbacks, etc.') );
$ProfileForm->text( 'newuser_url', $current_User->get( 'url' ), 40, T_('URL'), '', 100, 'bComment' );
$ProfileForm->text( 'newuser_icq', $current_User->get( 'icq' ), 40, T_('ICQ'), '', 10, 'bComment' );
$ProfileForm->text( 'newuser_aim', $current_User->get( 'aim' ), 40, T_('AOL I.M.'), '', 50, 'bComment' );
$ProfileForm->text( 'newuser_msn', $current_User->get( 'msn' ), 40, T_('MSN I.M.'), '', 100, 'bComment' );
$ProfileForm->text( 'newuser_yim', $current_User->get( 'yim' ), 40, T_('Yahoo I.M.'), '', 50, 'bComment' );
$ProfileForm->password( 'pass1', '', 16, T_('New pass'), T_('Leave blank to leave the password unchanged.'), 40, 'bComment' );
$ProfileForm->password( 'pass2', '', 16, T_('Confirm'), T_('Confirm new password by typing it again.'), 40, 'bComment' );

$ProfileForm->buttons( array( array( '', '', T_('Update'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$ProfileForm->end_form();
?>
