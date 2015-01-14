<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _user_preferences.form.php 7878 2014-12-23 11:54:05Z yura $
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


// Default params:
$default_params = array(
		'skin_form_params' => array(),
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'userprefs'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_text_title = T_( 'Edit preferences' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'userprefs', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'userprefs' );
	$Form->hidden( 'preferences_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );
	if( isset( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}

	/***************  Preferences  **************/

$Form->begin_fieldset( $is_admin ? T_('Other preferences').get_manual_link('user_preferences') : '', array( 'class'=>'fieldset clear' ) );

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
				T_('Check this if you want to be able to log in from different computers/browsers at the same time. Otherwise, logging in from a new computer/browser will automatically disconnect you on the previous one.'),
				'', 1, $multiple_sessions_field_disabled );
	}

	// Session time out for the current user
	$timeout_sessions = $UserSettings->get( 'timeout_sessions', $edited_User->ID );
	$def_timeout_session = $Settings->get( 'timeout_sessions' );

	if( empty( $timeout_sessions ) )
	{
		$timeout_sessions_selected = 'default';
		$timeout_sessions = $def_timeout_session;
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
						'label'   => T_('Use default duration.'),
						'note'    => duration_format( $def_timeout_session ),
						'onclick' => 'jQuery("[id$=timeout_sessions]").hide();' ),
					array(
						'value'   => 'custom',
						'label'   => T_('Use custom duration...'),
						'onclick' => 'jQuery("[id$=timeout_sessions]").show();' ),
				), T_('Session timeout'), array( 'lines' => true ) );

		// Note: jQuery is not used below ( display:none is used instead ),
		// Note: because using jQuery leads to 'timeout_sessions_container' flash for 'default duration' on page load.
		$fieldstart = $Form->fieldstart;
		if( $timeout_sessions_selected == 'default' )
		{
			$Form->fieldstart = str_replace( '>', ' style="display:none">', $Form->fieldstart );
		}
		$Form->duration_input( 'timeout_sessions', $timeout_sessions, T_('Custom duration'), 'months', 'seconds', array( 'minutes_step' => 1 ) );
		$Form->fieldstart = $fieldstart;
	}
	else
	{
		$Form->info( T_('Session timeout'), $timeout_sessions_selected );
	}

	$Form->checkbox( 'edited_user_showonline', $UserSettings->get( 'show_online', $edited_User->ID ), T_('Show online'), T_('Check this to be displayed as online when visiting the site.') );
}
else
{ // display only
	$Form->info( T_('Preferred locale'), $edited_User->get('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
	$Form->info( T_('Show online'), ( $UserSettings->get( 'show_online', $edited_User->ID ) ) ? T_('yes') : T_('no') );
}

$Form->end_fieldset();

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$action_buttons = array( array( '', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}

if( $Settings->get( 'account_close_enabled' ) && isset( $Blog ) &&
    ( $current_User->ID == $edited_User->ID ) && ! $current_User->check_perm( 'users', 'edit', false ) )
{ // Display a linkt to close account
  // Admins cannot close own accounts from front office
	$Form->info( '', '<a href="'.url_add_param( $Blog->gen_blogurl(), 'disp=closeaccount' ).'">'.T_( 'I want to close my account...' ).'</a>' );
}

$Form->end_form();

?>