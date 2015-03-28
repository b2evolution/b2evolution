<?php
/**
 * This file implements the UI controller for Remote Publishing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Store/retrieve preferred tab from UserSettings:
$UserSettings->param_Request( 'tab', 'pref_remotepublish_tab', 'string', 'eblog', true /* memorize */, true /* force */ );

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'remotepublish' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		switch( $tab )
		{
			case 'eblog':
				param( 'eblog_enabled', 'boolean', 0 );
				$Settings->set( 'eblog_enabled', $eblog_enabled );

				param( 'eblog_method', 'string', true );
				$Settings->set( 'eblog_method', strtolower($eblog_method) );

				param( 'eblog_encrypt', 'string', true );
				$Settings->set( 'eblog_encrypt', $eblog_encrypt );

				param( 'eblog_novalidatecert', 'boolean', 0 );
				$Settings->set( 'eblog_novalidatecert', $eblog_novalidatecert );

				param( 'eblog_server_host', 'string', true );
				$Settings->set( 'eblog_server_host', utf8_strtolower($eblog_server_host) );

				param( 'eblog_server_port', 'integer', true );
				$Settings->set( 'eblog_server_port', $eblog_server_port );

				param( 'eblog_username', 'string', true );
				$Settings->set( 'eblog_username', $eblog_username );

				param( 'eblog_password', 'string', true );
				$Settings->set( 'eblog_password', $eblog_password );

				param( 'eblog_default_category', 'integer', true );
				$Settings->set( 'eblog_default_category', $eblog_default_category );

				param( 'eblog_default_title', 'string', true );
				$Settings->set( 'eblog_default_title', $eblog_default_title );

				param( 'eblog_subject_prefix', 'string', true );
				$Settings->set( 'eblog_subject_prefix', $eblog_subject_prefix );

				param( 'eblog_body_terminator', 'string', true );
				$Settings->set( 'eblog_body_terminator', $eblog_body_terminator );

				param( 'eblog_test_mode', 'boolean', 0 );
				$Settings->set( 'eblog_test_mode', $eblog_test_mode );

				param( 'eblog_add_imgtag', 'boolean', 0 );
				$Settings->set( 'eblog_add_imgtag', $eblog_add_imgtag );

				param( 'eblog_html_enabled', 'boolean', 0 );
				$Settings->set( 'eblog_html_enabled', $eblog_html_enabled );

				param( 'eblog_html_tag_limit', 'boolean', 0 );
				$Settings->set( 'eblog_html_tag_limit', $eblog_html_tag_limit );

				param( 'eblog_delete_emails', 'boolean', 0 );
				$Settings->set( 'eblog_delete_emails', $eblog_delete_emails );

				if( param( 'renderers_displayed', 'integer', 0 ) )
				{ // use "renderers" value only if it has been displayed (may be empty)
					global $Plugins;
					$autoselect_blog = autoselect_blog( 'blog_post_statuses', 'edit' );
					$BlogCache = & get_BlogCache();
					$setting_Blog = & $BlogCache->get_by_ID( $autoselect_blog );
					$renderer_params = array( 'Blog' => & $setting_Blog, 'setting_name' => 'coll_apply_rendering' );
					$renderers = $Plugins->validate_renderer_list( param( 'eblog_renderers', 'array:string', array() ), $renderer_params );
					$Settings->set( 'eblog_renderers', $renderers );
				}
				break;

			case 'xmlrpc':
				$Settings->set( 'general_xmlrpc', param( 'general_xmlrpc', 'integer', 0 ) );

				param( 'xmlrpc_default_title', 'string', true );
				$Settings->set( 'xmlrpc_default_title', $xmlrpc_default_title );
				break;
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('General settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=remotepublish', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'test_1':
	case 'test_2':
	case 'test_3':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		load_funcs( 'cron/model/_post_by_mail.funcs.php');
		load_class( 'items/model/_itemlist.class.php', 'ItemList' );
		load_class( '_ext/mime_parser/rfc822_addresses.php', 'rfc822_addresses_class' );
		load_class( '_ext/mime_parser/mime_parser.php', 'mime_parser_class' );

		if( isset($GLOBALS['files_Module']) )
		{
			load_funcs( 'files/model/_file.funcs.php');
		}

		global $pbm_messages;

		if( $action == 'test_1' )
		{
			if( $mbox = pbm_connect() )
			{	// Close opened connection
				imap_close( $mbox );
			}
		}
		elseif( $action == 'test_2' || $action == 'test_3' )
		{
			if( $mbox = pbm_connect() )
			{
				// Read messages from server
				pbm_msg('Reading messages from server');
				$imap_obj = imap_check( $mbox );
				pbm_msg('Found '.$imap_obj->Nmsgs.' messages');

				if( $imap_obj->Nmsgs > 0 )
				{
					if( $action == 'test_2' )
					{	// Pretend that we're in test mode. DO NOT save this setting!
						$eblog_saved_test_mode_value = $Settings->get('eblog_test_mode');
						$Settings->set('eblog_test_mode', 1);
					}

					// We will read only 1 message from server in test mode
					pbm_process_messages( $mbox, 1 );
				}
				else
				{
					pbm_msg( T_('There are no messages in the mailbox') );
				}
				imap_close( $mbox );
			}
		}

		$Messages->clear(); // Clear all messages

		if( !empty($pbm_messages) )
		{	// We will display the output in a scrollable fieldset
			$eblog_test_output = implode( "<br />\n", $pbm_messages );
		}
		break;
}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Remote publishing'), $admin_url.'?ctrl=remotepublish' );

switch( $tab )
{
	case 'eblog':
		$AdminUI->breadcrumbpath_add( T_('Post by Email'), $admin_url.'?ctrl=remotepublish&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'post-by-email' );
		break;

	case 'xmlrpc':
		$AdminUI->breadcrumbpath_add( T_('XML-RPC'), $admin_url.'?ctrl=remotepublish&amp;tab='.$tab );
		break;
}

if( !empty($tab) )
{
	$AdminUI->append_path_level( $tab );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
switch( $tab )
{
	case 'eblog':
		$AdminUI->disp_view( 'settings/views/_eblog.form.php' );
		break;

	case 'xmlrpc':
		$AdminUI->disp_view( 'settings/views/_xmlrpc.form.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>