<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id: msg_settings.ctrl.php 7564 2014-11-03 13:12:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $DB, $current_User, $Settings;

// Check minimum permission:
$current_User->check_perm( 'options', 'edit', true );

// Set options path:
$AdminUI->set_path( 'messaging', 'msgsettings' );

// Get action parameter from request:
param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'msgsettings' );

		$Settings->set( 'messages_link_to', param( 'messages_link_to', 'string', true ) );
		$Settings->set( 'allow_html_message', param( 'allow_html_message', 'integer', 0 ) );

		$UserCache = & get_UserCache();

		// Welcome message after account activation
		$User = $UserCache->get_by_login( param( 'welcomepm_from', 'string', true ) );
		if( ! $User )
		{ // Use login of the current user if user login is incorrect
			$User = $current_User;
		}
		$Settings->set( 'welcomepm_enabled', param( 'welcomepm_enabled', 'integer', 0 ) );
		$Settings->set( 'welcomepm_from', $User->login );
		$Settings->set( 'welcomepm_title', param( 'welcomepm_title', 'string', true ) );
		$Settings->set( 'welcomepm_message', param( 'welcomepm_message', 'text', true ) );

		// Info message to reporters after account deletion
		$User = $UserCache->get_by_login( param( 'reportpm_from', 'string', true ) );
		if( ! $User )
		{ // Use login of the current user if user login is incorrect
			$User = $current_User;
		}
		$Settings->set( 'reportpm_enabled', param( 'reportpm_enabled', 'integer', 0 ) );
		$Settings->set( 'reportpm_from', $User->login );
		$Settings->set( 'reportpm_title', param( 'reportpm_title', 'string', true ) );
		$Settings->set( 'reportpm_message', param( 'reportpm_message', 'text', true ) );

		$Settings->dbupdate();

		// Update Plugin params/Settings
		load_funcs('plugins/_plugin.funcs.php');

		$Plugins->restart();
		while( $loop_Plugin = & $Plugins->get_next() )
		{
			$pluginsettings = $loop_Plugin->get_msg_setting_definitions( $tmp_params = array( 'for_editing' => true ) );
			if( empty( $pluginsettings ) )
			{
				continue;
			}

			// Loop through settings for this plugin:
			foreach( $pluginsettings as $set_name => $set_meta )
			{
				autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'Settings' );
			}

			// Let the plugin handle custom fields:
			// We use call_method to keep track of this call, although calling the plugins PluginSettingsUpdateAction method directly _might_ work, too.
			$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginSettingsUpdateAction', $tmp_params = array() );

			if( $ok_to_update === false )
			{ // Rollback settings: the plugin has said they should not get updated.
				$loop_Plugin->Settings->reset();
			}
			else
			{ // Update message settings of the Plugin
				$loop_Plugin->Settings->dbupdate();
			}
		}

		$Messages->add( T_( 'Settings were updated.' ), 'success' );
		break;
}

// Init JS to autcomplete the user logins
init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=msgsettings' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

// Display messaging settings:
$AdminUI->disp_view( 'messaging/views/_msg_settings.form.php' );

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>