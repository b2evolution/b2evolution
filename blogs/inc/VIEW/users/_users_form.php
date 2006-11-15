<?php
/**
 * This file implements the UI view for the user properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
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

/**
 * @var User
 */
global $current_User;
/**
 * @var AdminUI_general
 */
global $AdminUI;
/**
 * @var User
 */
global $edited_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var Plugins
 */
global $Plugins;

global $action, $user_profile_only;

// Begin payload block:
$this->disp_payload_begin();


$Form = & new Form( NULL, 'user_checkchanges' );

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

$Form->hidden_ctrl();
$Form->hidden( 'user_ID', $edited_User->ID );


$Form->begin_fieldset( T_('User rights'), array( 'class'=>'fieldset clear' ) );

$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );
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
	$edited_User->get_Group();
	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->get('ID');
	$GroupCache = & get_Cache( 'GroupCache' );
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
}
else
{
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );
}

$Form->end_fieldset();


$Form->begin_fieldset( T_('User') );

$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'play', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
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
	$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated'), T_('Has the user been validated (through email)?') );
	$Form->checkbox( 'edited_user_allow_msgform', $edited_User->get('allow_msgform'), T_('Message form'), T_('Check this to allow receiving emails through a message form.') );
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
	$Form->info( T_('Login'), $edited_User->get('login') );
	$Form->info( T_('First name'), $edited_User->get('firstname') );
	$Form->info( T_('Last name'), $edited_User->get('lastname') );
	$Form->info( T_('Nickname'), $edited_User->get('nickname') );
	$Form->info( T_('Identity shown'), $edited_User->get('preferredname') );
	$Form->info( T_('Show Online'), ($edited_User->get('showonline')) ? T_('yes') : T_('no') );
	$Form->info( T_('Locale'), $edited_User->get('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
	$Form->info( T_('Email'), $edited_User->get('email'), $email_fieldnote );
	$Form->info( T_('Validated'), ( $edited_User->get('validated') ? T_('yes') : T_('no') ), T_('Has the user been validated (through email)?') );
	$Form->info( T_('Message form'), ($edited_User->get('allow_msgform') ? T_('yes') : T_('no')) );
	$Form->info( T_('Notifications'), ($edited_User->get('notify') ? T_('yes') : T_('no')) );
	$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
	$Form->info( T_('ICQ'), $edited_User->get('icq', 'formvalue'), $icq_fieldnote );
	$Form->info( T_('AIM'), $edited_User->get('aim'), $aim_fieldnote );
	$Form->info( T_('MSN IM'), $edited_User->get('msn') );
	$Form->info( T_('YahooIM'), $edited_User->get('yim') );
}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Features') );
	$value_admin_skin = get_param('edited_user_admin_skin');
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
  // fp> TODO: We gotta have something like $edited_User->UserSettings->get('legend');
	// Icon/text thresholds:
	$Form->text( 'edited_user_action_icon_threshold', $UserSettings->get( 'action_icon_threshold', $edited_User->ID), 1, T_('Action icon display'), T_('1:more icons ... 5:less icons') );
	$Form->text( 'edited_user_action_word_threshold', $UserSettings->get( 'action_word_threshold', $edited_User->ID), 1, T_('Action word display'), T_('1:more action words ... 5:less action words') );

	// To display or hide icon legend:
	$Form->checkbox( 'edited_user_legend', $UserSettings->get( 'display_icon_legend', $edited_User->ID ), T_('Display icon legend'), T_('Display a legend at the bottom of every page including all action icons used on that page.') );

	// To activate or deactivate bozo validator:
	$Form->checkbox( 'edited_user_bozo', $UserSettings->get( 'control_form_abortions', $edited_User->ID ), T_('Control form closing'), T_('This will alert you if you fill in data into a form and try to leave the form before submitting the data.') );

	// To activate focus on first form input text
	$Form->checkbox( 'edited_user_focusonfirst', $UserSettings->get( 'focus_on_first_input', $edited_User->ID ), T_('Focus on first input text field'), T_('The focus will automatically go to the first input text field.') );


$Form->end_fieldset();


// PluginUserSettings
$Plugins->restart();
while( $loop_Plugin = & $Plugins->get_next() )
{
	if( ! $loop_Plugin->UserSettings ) // NOTE: this triggers autoloading in PHP5, which is needed for the "hackish" isset($this->UserSettings)-method to see if the settings are queried for editing (required before 1.9)
	{
		continue;
	}

	global $inc_path;
	require_once $inc_path.'_misc/_plugin.funcs.php';

	$Form->begin_fieldset( $loop_Plugin->name );

	foreach( $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) ) as $l_name => $l_meta )
	{
		display_plugin_settings_fieldset_field( $l_name, $l_meta, $loop_Plugin, $Form, 'UserSettings', $edited_User );
	}

	$Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsEditDisplayAfter', $tmp_params = array( 'Form' => & $Form ) );

	$Form->end_fieldset();
}


if( $action != 'view_user' )
{ // Edit buttons
	$Form->buttons( array(
		array( '', 'actionArray[userupdate]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'SaveButton' ),
	) );
}

if( ! $creating )
{ // We're NOT creating a new user:
	$Form->begin_fieldset( T_('User information') );

	$Form->info_field( T_('ID'), $edited_User->ID );

	// TODO: move this out of the core:
	$Form->info_field( T_('Posts'), ( $action != 'newtemplate' ) ? $edited_User->get_num_posts() : '-' );

	$Form->info_field( T_('Created on'), $edited_User->dget('datecreated') );
	$Form->info_field( T_('From IP'), $edited_User->dget('ip') );
	$Form->info_field( T_('From Domain'), $edited_User->dget('domain') );
	$Form->info_field( T_('With Browser'), $edited_User->dget('browser') );

	$Form->end_fieldset();
}

$Form->end_form();

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.27  2006/11/15 21:14:04  blueyed
 * "Restore defaults" in user profile
 *
 * Revision 1.26  2006/11/14 00:26:28  blueyed
 * Made isset($this->UserSettings)-hack work; fixed call to undefined function
 *
 * Revision 1.25  2006/11/05 20:13:57  fplanque
 * minor
 *
 * Revision 1.24  2006/10/30 19:00:36  blueyed
 * Lazy-loading of Plugin (User)Settings for PHP5 through overloading
 *
 * Revision 1.23  2006/08/20 23:14:07  blueyed
 * doc fix
 *
 * Revision 1.22  2006/08/20 22:25:22  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.21  2006/08/20 20:12:33  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.20  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.19  2006/07/06 23:26:47  blueyed
 * Use 'yes'/'no' instead of 1/0.
 *
 * Revision 1.18  2006/07/06 23:23:48  blueyed
 * Fixed "Output format [Has the user been validated (through email)?] not supported." error.
 *
 * Revision 1.17  2006/07/03 21:04:50  fplanque
 * translation cleanup
 *
 * Revision 1.16  2006/07/02 19:53:58  blueyed
 * Fixed display of user's group
 *
 * Revision 1.15  2006/06/25 21:13:17  fplanque
 * minor
 *
 * Revision 1.14  2006/06/13 21:49:16  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.13.2.1  2006/06/12 20:00:40  fplanque
 * one too many massive syncs...
 *
 * Revision 1.13  2006/04/27 21:50:40  blueyed
 * Allow editing/viewing of "validated" property
 *
 * Revision 1.12  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.11  2006/04/18 17:02:57  fplanque
 * wording
 *
 * Revision 1.10  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.9  2006/04/12 15:16:54  fplanque
 * partial cleanup
 *
 * Revision 1.8  2006/04/04 22:12:33  blueyed
 * Fixed setting usersettings for other users
 *
 * Revision 1.7  2006/03/19 17:54:26  blueyed
 * Opt-out for email through message form.
 *
 * Revision 1.6  2006/03/14 23:35:41  blueyed
 * Fixed "play" icon
 *
 * Revision 1.5  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.4  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.3  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.2  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.80  2006/02/03 21:58:04  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
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
 */
?>