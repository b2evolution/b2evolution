<?php
/**
 * This file implements the UI view for the user properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->disp_payload_begin();


$Form = & new Form( 'b2users.php', 'user_checkchanges' );

if( !$user_profile_only )
{
	$Form->global_icon( ( $action != 'view_user' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', regenerate_url( 'user_ID,action' ) );
}

if( $edited_User->get('ID') == 0 )
{	// Creating new user:
	$creating = true;
	$Form->begin_form( 'fform', T_('Create new user profile') );
}
else
{	// Editing existing user:
	$creating = false;
	$Form->begin_form( 'fform', T_('Profile for:').' '.$edited_User->dget('fullname').' ['.$edited_User->dget('login').']' );
}

$Form->hidden( 'action', 'userupdate' );
$Form->hidden( 'user_ID', $edited_User->ID );


$Form->begin_fieldset( T_('User rights'), array( 'class'=>'fieldset clear' ) );

$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://b2evolution.net/man/user_levels.html"' );
if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('Level'), array( 'note' => $field_note, 'required' => true ) );
}
else
{
	$Form->info_field( T_('Level'), $edited_User->get('level'), array( 'note' => $field_note ) );
}
if( $edited_User->get('ID') != 1 && $current_User->check_perm( 'users', 'edit' ) )
{	// This is not Admin and we're not restricted: we're allowed to change the user group:
	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->get('ID');
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
}
else
{
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );
}

$Form->end_fieldset();


$Form->begin_fieldset( T_('User') );

$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Send an email').'" class="middle" /></a>';

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Visit the site').'" class="middle" /></a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Search on ICQ.com').'" class="middle" /></a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Instant Message to user').'" class="middle" /></a>';
else
	$aim_fieldnote = '';


if( $action != 'view_user' )
{ // We can edit the values:
	$Form->text_input( 'edited_user_login', $edited_User->login, 20, T_('Login'), array( 'required' => true ) );
	$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), array( 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), array( 'maxlength' => 50, 'force_to' => 'UpperCase' ) );
	$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), array( 'maxlength' => 50, 'required' => true ) );
	$Form->select( 'edited_user_idmode', $edited_User->get( 'idmode' ), array( &$edited_User, 'callback_optionsForIdMode' ), T_('Identity shown') );
	$Form->checkbox( 'edited_user_showonline', $edited_User->get('showonline'), T_('Show Online'), T_('Check this to be displayed as online when visiting the site.') );
	$Form->select( 'edited_user_locale', $edited_User->get('locale'), 'locale_options_return', T_('Preferred locale'), T_('Preferred locale for admin interface, notifications, etc.'));
	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), array( 'note' => $email_fieldnote, 'maxlength' => 100, 'required' => true ) );
	$Form->checkbox( 'edited_user_notify', $edited_User->get('notify'), T_('Notifications'), T_('Check this to receive a notification whenever one of <strong>your</strong> posts receives comments, trackbacks, etc.') );
	$Form->text_input( 'edited_user_url', $edited_User->url, 30, T_('URL'), array( 'note' => $url_fieldnote, 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), array( 'note' => $icq_fieldnote, 'maxlength' => 10 ) );
	$Form->text_input( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), array( 'note' => $aim_fieldnote, 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), array( 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), array( 'maxlength' => 50 ) );
	$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => ( !empty($edited_User->ID) ? T_('Leave empty if you don\'t want to change the password.') : '' ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0) ) );
	$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0) ) );
	$Form->info( '', sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen') ) );
}
else
{ // display only
	$Form->info( T_('Login'), $edited_User->dget('login') );
	$Form->info( T_('First name'), $edited_User->dget('firstname') );
	$Form->info( T_('Last name'), $edited_User->dget('lastname') );
	$Form->info( T_('Nickname'), $edited_User->dget('nickname') );
	$Form->info( T_('Identity shown'), $edited_User->dget('preferredname') );
	$Form->info( T_('Show Online'), ($edited_User->dget('showonline')) ? T_('yes') : T_('no') );
	$Form->info( T_('Locale'), $edited_User->dget('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
	$Form->info( T_('Email'), $edited_User->dget('email'), $email_fieldnote );
	$Form->info( T_('Notifications'), ($edited_User->dget('notify')) ? T_('yes') : T_('no') );
	$Form->info( T_('URL'), $edited_User->dget('url'), $url_fieldnote );
	$Form->info( T_('ICQ'), $edited_User->dget('icq', 'formvalue'), $icq_fieldnote );
	$Form->info( T_('AIM'), $edited_User->dget('aim'), $aim_fieldnote );
	$Form->info( T_('MSN IM'), $edited_User->dget('msn') );
	$Form->info( T_('YahooIM'), $edited_User->dget('yim') );
}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Features') );
	$value_admin_skin = $Request->get('edited_user_admin_skin');
	if( !$value_admin_skin )
	{ // no value supplied through POST/GET
		$value_admin_skin = $UserSettings->get( 'admin_skin', $edited_User->ID );
	}
	if( !$value_admin_skin )
	{ // Nothing set yet for the user, use the default
		$value_admin_skin = $Settings->get('admin_skin');
	}
	if( $action != 'view_user' )
	{
		$Form->select_input_array( 'edited_user_admin_skin', get_admin_skins(), T_('Admin skin'), array( 'value' => $value_admin_skin, 'note' => T_('The skin defines how the backoffice appears to you.') ) );
	}
	else
	{
		$Form->info_field( T_('Admin skin'), $value_admin_skin, array( 'note' => T_('The skin defines how the backoffice appears to you.') ) );
	}
$Form->end_fieldset();


if( $action != 'view_user' )
{ // Edit buttons
	$Form->buttons( array(
		array( '', '', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

if( ! $creating )
{ // We're NOT creating a new user:
	$Form->begin_fieldset( T_('User information') );

	$Form->info_field( T_('ID'), $edited_User->ID );

	if( $app_shortname == 'b2evo' )
	{ // TODO: move this out of the core
		$Form->info_field( T_('Posts'), ( $action != 'newtemplate' ) ? $edited_User->get_num_posts() : '-' );
	}
	$Form->info_field( T_('Created on'), $edited_User->dget('datecreated') );
	$Form->info_field( T_('From IP'), $edited_User->dget('ip') );
	$Form->info_field( T_('From Domain'), $edited_User->dget('domain') );
	$Form->info_field( T_('With Browser'), $edited_User->dget('browser') );

	$Form->end_fieldset();
}

$Form->end_form();

// End payload block:
$AdminUI->disp_payload_end();

/*
 * $Log$
 * Revision 1.79  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.78  2005/12/13 14:32:04  fplanque
 * no need to color confuse the user about mandatory select lists which have non 'none' choice anyway.
 *
 * Revision 1.77  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.76  2005/12/08 22:23:44  blueyed
 * Merged 1-2-3-4 scheme from post-phoenix
 *
 * Revision 1.75  2005/11/25 22:45:37  fplanque
 * no message
 *
 * Revision 1.74  2005/11/25 14:17:21  blueyed
 * Doc; fix users editing themself in demo_mode (if not 'admin' or 'demouser')
 *
 * Revision 1.72  2005/11/24 00:45:39  blueyed
 * demo_mode: allow the user to edit his profile, if not admin or demouser. This should work in post-phoenix already.
 *
 * Revision 1.71  2005/11/04 14:10:48  blueyed
 * Use value from $Request for edited_user_admin_skin to display in the form (in case of errors with other fields)
 *
 * Revision 1.70  2005/11/02 00:42:30  blueyed
 * Added get_admin_skins() and use it to perform additional checks (if there's a _adminUI.class.php file in there). Thinkl "CVS".. :)
 *
 * Revision 1.69  2005/11/01 23:50:55  blueyed
 * UI to set the admin_skin for a user. If the user changes his own profile, we reload the page and save $Messages before, so he gets his "User updated" note.. :)
 *
 * Revision 1.68  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.67  2005/09/29 15:07:29  fplanque
 * spelling
 *
 * Revision 1.66  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.65  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.64  2005/08/11 19:41:10  fplanque
 * no message
 *
 * Revision 1.63  2005/08/10 21:14:34  blueyed
 * Enhanced $demo_mode (user editing); layout fixes; some function names normalized
 *
 * Revision 1.62  2005/07/12 17:10:55  blueyed
 * replaced Form::text() with Form::text_input(), Form::password() with Form::password_input()
 *
 * Revision 1.61  2005/07/11 22:18:07  blueyed
 * Added info about password min length, fixed display of readonly profiles and password note.
 *
 * Revision 1.60  2005/06/20 17:40:13  fplanque
 * minor
 *
 * Revision 1.59  2005/06/10 18:25:42  fplanque
 * refactoring
 *
 * Revision 1.58  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.57  2005/05/24 18:46:26  fplanque
 * implemented blog email subscriptions (part 1)
 *
 * Revision 1.56  2005/04/06 13:33:28  fplanque
 * minor changes
 *
 * Revision 1.55  2005/03/22 16:36:00  fplanque
 * refactoring, standardization
 * fixed group creation bug
 *
 * Revision 1.54  2005/03/21 18:57:22  fplanque
 * user management refactoring (towards new evocore coding guidelines)
 * WARNING: some pre-existing bugs have not been fixed here
 *
 */
?>