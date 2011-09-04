<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * $var AdminUI
 */
global $AdminUI;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;

$Form = new Form( $form_action, 'user_checkchanges' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_title = sprintf( T_('Edit preferences for user %s'), $edited_User->dget('fullname').' &laquo;'.$edited_User->dget('login').'&raquo;' );
	$form_class = 'fform';
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'userprefs' );
	$Form->hidden( 'preferences_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );

	/***************  Preferences  **************/

$Form->begin_fieldset( $is_admin ? T_('Preferences').get_manual_link('user_preferences') : '', array( 'class'=>'fieldset clear' ) );

if( $action != 'view' )
{ // We can edit the values:

	$Form->select( 'edited_user_locale', $edited_User->get('locale'), 'locale_options_return', T_('Preferred locale'), T_('Preferred locale for admin interface, notifications, etc.'));

	// Enable/disable multiple sessions for the current user
	$multiple_sessions = $Settings->get( 'multiple_sessions' );
	switch( $multiple_sessions )
	{
		case 'never':
		case 'always':
			$multiple_sessions_field_hidden = true;
			$multiple_sessions_field_disabled = true;
			break;
		default:
			$multiple_sessions_field_hidden = false;
			if( ( $multiple_sessions == 'adminset_default_no' || $multiple_sessions == 'adminset_default_yes' ) && !$current_User->check_perm( 'users', 'edit' ) )
			{
				$multiple_sessions_field_disabled = true;
			}
			else
			{
				$multiple_sessions_field_disabled = false;
			}
	}

	$multiple_sessions_value = $UserSettings->get( 'login_multiple_sessions', $edited_User->ID );

	if( $multiple_sessions_field_hidden )
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', $multiple_sessions_value );
	}
	else
	{
		$Form->checkbox( 'edited_user_set_login_multiple_sessions', $multiple_sessions_value, T_('Multiple sessions'),
				T_('Check this if you want to log in from different computers/browsers at the same time. Otherwise, logging in from a new computer/browser will disconnect you on the previous one.'),
				'', 1, $multiple_sessions_field_disabled );
	}

	// Session time out for the current user
	$timeout_sessions = $UserSettings->get( 'timeout_sessions', $edited_User->ID );

	if( empty( $timeout_sessions ) )
	{
		$timeout_sessions_selected = 'default';
		$timeout_sessions = $Settings->get( 'timeout_sessions', $edited_User->ID );
	}
	else
	{
		$timeout_sessions_selected = 'custom';
	}

	if( ( $current_User->ID == $edited_User->ID ) || ( $current_User->check_perm( 'users', 'edit' ) ) )
	{
		$Form->radio_input( 'edited_user_timeout_sessions', $timeout_sessions_selected, array(
					array(
						'value'   => 'default',
						'label'   => T_('use default duration'),
						'onclick' => 'jQuery("#timeout_sessions_container").hide();' ),
					array(
						'value'   => 'custom',
						'label'   => T_('use custom duration'),
						'onclick' => 'jQuery("#timeout_sessions_container").show();' ),
				), T_('Session timeout'), array( 'lines' => true ) );

		// Note: jQuery is not used below ( display:none is used instead ),
		// Note: because using jQuery leads to 'timeout_sessions_container' flash for 'default duration' on page load.
		if( $timeout_sessions_selected == 'default' )
		{
			echo '<div id="timeout_sessions_container" style="display:none">';
		}
		else
		{
			echo '<div id="timeout_sessions_container">';
		}
		$Form->duration_input( 'timeout_sessions', $timeout_sessions, T_('Custom duration'), 'months', 'seconds', array( 'minutes_step' => 1 ) );
		echo '</div>';
	}
	else
	{
		$Form->info( T_('Session timeout'), $timeout_sessions_selected );
	}
}
else
{ // display only
	$Form->info( T_('Preferred locale'), $edited_User->get('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
}

$Form->end_fieldset();

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$action_buttons = array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}


$Form->end_form();


/*
 * $Log$
 * Revision 1.19  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.18  2011/05/11 07:11:52  efy-asimo
 * User settings update
 *
 * Revision 1.17  2011/04/06 13:30:56  efy-asimo
 * Refactor profile display
 *
 * Revision 1.16  2011/02/23 21:45:18  fplanque
 * minor / cleanup
 *
 * Revision 1.15  2011/02/22 16:00:31  efy-asimo
 * fix deprecated warning
 *
 * Revision 1.14  2010/11/22 13:44:33  efy-asimo
 * Admin skin preferences update
 *
 * Revision 1.13  2010/11/18 13:56:06  efy-asimo
 * admin skin preferences
 *
 * Revision 1.12  2010/10/17 18:53:04  sam2kb
 * Added a link to delete edited user
 *
 * Revision 1.11  2010/08/24 08:20:19  efy-asimo
 * twitter plugin oAuth
 *
 * Revision 1.10  2010/03/01 07:52:51  efy-asimo
 * Set manual links to lowercase
 *
 * Revision 1.9  2010/02/14 14:18:39  efy-asimo
 * insert manual links
 *
 * Revision 1.8  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2010/01/03 17:45:21  fplanque
 * crumbs & stuff
 *
 * Revision 1.6  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2009/11/21 13:39:05  efy-maxim
 * 'Cancel editing' fix
 *
 * Revision 1.4  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.3  2009/10/28 15:11:55  efy-maxim
 * custom duration has been hidden for normal users
 *
 * Revision 1.2  2009/10/28 13:41:58  efy-maxim
 * default multiple sessions settings
 *
 * Revision 1.1  2009/10/28 10:02:42  efy-maxim
 * rename some php files
 *
 */
?>