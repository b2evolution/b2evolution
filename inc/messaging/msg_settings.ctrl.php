<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $DB, $current_User, $Settings;

// Check minimum permission:
$current_User->check_perm( 'options', 'edit', true );

// Selected tab:
$tab = param( 'tab', 'string', 'general' );

// Set options path:
$AdminUI->set_path( 'messaging', 'msgsettings', $tab );

// Get action parameter from request:
param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'msgsettings' );

		switch( $tab )
		{
			case 'templates':
				// Update messaging templates settings:
				$UserCache = & get_UserCache();

				// Welcome message after account activation
				$User = $UserCache->get_by_login( param( 'welcomepm_from', 'string', true ) );
				if( ! $User )
				{	// Use login of the current user if user login is incorrect:
					$User = $current_User;
				}
				$Settings->set( 'welcomepm_enabled', param( 'welcomepm_enabled', 'integer', 0 ) );
				$Settings->set( 'welcomepm_from', $User->login );
				$Settings->set( 'welcomepm_title', param( 'welcomepm_title', 'string', true ) );
				$Settings->set( 'welcomepm_message', param( 'welcomepm_message', 'text', true ) );

				// Info message to reporters after account deletion
				$User = $UserCache->get_by_login( param( 'reportpm_from', 'string', true ) );
				if( ! $User )
				{	// Use login of the current user if user login is incorrect:
					$User = $current_User;
				}
				$Settings->set( 'reportpm_enabled', param( 'reportpm_enabled', 'integer', 0 ) );
				$Settings->set( 'reportpm_from', $User->login );
				$Settings->set( 'reportpm_title', param( 'reportpm_title', 'string', true ) );
				$Settings->set( 'reportpm_message', param( 'reportpm_message', 'text', true ) );

				$Settings->dbupdate();
				break;

			case 'renderers':
				// Update messaging renderers settings:
				load_funcs('plugins/_plugin.funcs.php');

				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$tmp_params = array( 'for_editing' => true );
					$pluginsettings = $loop_Plugin->get_msg_setting_definitions( $tmp_params );
					if( empty( $pluginsettings ) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginsettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'MsgSettings' );
					}

					// Let the plugin handle custom fields:
					// We use call_method to keep track of this call, although calling the plugins PluginSettingsUpdateAction method directly _might_ work, too.
					$tmp_params = array();
					$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginSettingsUpdateAction', $tmp_params );

					if( $ok_to_update === false )
					{	// The plugin has said they should not get updated, Rollback settings:
						$loop_Plugin->Settings->reset();
					}
					else
					{	// Update message settings of the Plugin:
						$loop_Plugin->Settings->dbupdate();
					}
				}
				break;

			default:
				// Update messaging general settings:
				$Settings->set( 'allow_html_message', param( 'allow_html_message', 'integer', 0 ) );

				$Settings->dbupdate();
				break;
		}

		$Messages->add( T_( 'Settings were updated.' ), 'success' );
		break;
}

if( $tab == 'templates' )
{	// Init JS to autcomplete the user logins:
	init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=msgsettings' );
switch( $tab )
{
	case 'templates':
		// Templates settings:
		$AdminUI->breadcrumbpath_add( T_('Templates'), '?ctrl=msgsettings&amp;tab=templates' );
		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'messages-welcome-after-account-activation' );
		break;

	case 'renderers':
		// Renderers settings:
		$AdminUI->breadcrumbpath_add( T_('Renderers'), '?ctrl=msgsettings&amp;tab=renderers' );
		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'messaging-plugin-settings' );
		break;

	default:
		// General settings:
		$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=msgsettings' );
		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'messages-settings' );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

// Display messaging settings:
switch( $tab )
{
	case 'templates':
		// Templates settings:
		$AdminUI->disp_view( 'messaging/views/_msg_settings_templates.form.php' );
		break;

	case 'renderers':
		// Renderers settings:
		$AdminUI->disp_view( 'messaging/views/_msg_settings_renderers.form.php' );
		break;

	default:
		// General settings:
		$AdminUI->disp_view( 'messaging/views/_msg_settings.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>