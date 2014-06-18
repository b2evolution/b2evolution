<?php
/**
 * This file implements the UI controller for phpBB importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: phpbbimport.ctrl.php 74 2011-10-26 13:49:38Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'tools/model/_phpbb.funcs.php' );

param( 'action', 'string' );

if( !empty( $action ) )
{	// Try to obtain some serious time to do some serious processing (15 minutes)
	set_max_execution_time( 900 );
	// Turn off the output buffering to do the correct work of the function flush()
	@ini_set( 'output_buffering', 'off' );
}

/**
 * @var step
 *
 * values:
 * 1) 'config'
 * 2) 'groups'
 * 3) 'users'
 * 4) 'forums' -> categories
 * 5) 'topics' -> posts
 * 6) 'replies' -> comments
 * 7) 'messages'
 */
$step = param( 'step', 'string', 'config' );


// Initialize the config variables for phpBB
phpbb_init_config();

switch( $action )
{
	case 'database':	// Action for Step 1
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'phpbb' );

		$phpbb_db_host = param( 'db_host', 'string', true );
		$phpbb_db_name = param( 'db_name', 'string', true );
		$phpbb_db_user = param( 'db_user', 'string', true );
		$phpbb_db_pass = param( 'db_pass', 'string', true );
		$phpbb_db_prefix = param( 'db_prefix', 'string', '' );
		$phpbb_path_avatars = param( 'path_avatars', 'string', '' );
		$forum_blog_ID = param( 'forum_blog_ID', 'integer', 0 );

		param_check_not_empty( 'db_host', T_('Please enter a database host!') );
		param_check_not_empty( 'db_name', T_('Please enter a database name!') );
		param_check_not_empty( 'db_user', T_('Please enter a username!') );
		param_check_not_empty( 'db_pass', T_('Please enter a password!') );
		param_check_not_empty( 'forum_blog_ID', T_('Please select a blog!') );

		if( param_errors_detected() )
		{
			break;
		}

		$phpbb_db_config = array(
				'user'     => $phpbb_db_user,
				'password' => $phpbb_db_pass,
				'name'     => $phpbb_db_name,
				'host'     => $phpbb_db_host,
				'prefix'   => $phpbb_db_prefix,
				'aliases'  => phpbb_tables_aliases( $phpbb_db_prefix ),
				'use_transactions'   => $db_config['use_transactions'],
				'table_options'      => $db_config['table_options'],
				'connection_charset' => $DB->connection_charset,		// Use same charset as main DB connection so that charsets match!
				'halt_on_error' => false,
				'show_errors'   => false,
				'new_link'      => true,

				'log_queries' => true,
				'debug_dump_rows' => 20,
			);

		// Test connect to DB:
		$phpbb_DB = new DB( $phpbb_db_config );
		unset( $phpbb_db_config['aliases'] );

		if( $phpbb_DB->error )
		{
			$Messages->add( $phpbb_DB->last_error, 'error' );
			break;
		}

		// Save DB config of phpBB in the session
		phpbb_set_var( 'db_config', $phpbb_db_config );
		phpbb_set_var( 'blog_ID', $forum_blog_ID );
		phpbb_set_var( 'path_avatars', $phpbb_path_avatars );

		$step = 'groups';
		break;

	case "users":	// Action for Step 2
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'phpbb' );

		$phpbb_ranks = param( 'phpbb_ranks', 'array/integer', array() );
		$phpbb_group_default = param( 'phpbb_group_default', 'integer' );
		$phpbb_group_invalid = param( 'phpbb_group_invalid', 'integer' );

		param_check_not_empty( 'phpbb_group_default', T_('Please select a default group!') );

		phpbb_set_var( 'ranks', $phpbb_ranks );
		phpbb_set_var( 'group_default', $phpbb_group_default );
		phpbb_set_var( 'group_invalid', $phpbb_group_invalid );

		$phpbb_categories = param( 'phpbb_categories', 'array/integer', array() );
		$phpbb_forums = param( 'phpbb_forums', 'array/integer', array() );
		phpbb_set_var( 'import_categories', $phpbb_categories );
		phpbb_set_var( 'import_forums', $phpbb_forums );

		if( empty( $phpbb_categories ) && empty( $phpbb_forums ) )
		{
			$Messages->add( T_('Please select at least one forum to import!') );
		}

		if( param_errors_detected() )
		{
			$step = 'groups';
			break;
		}

		// Set this action to complete all processes in the form
		$flush_action = 'users';

		$step = 'users';
		break;

	case 'forums':	// Action for Step 3
	case 'topics':	// Action for Step 4
	case 'replies':	// Action for Step 5
	case 'messages':	// Action for Step 6
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'phpbb' );

		// Set this action to complete all processes in the form
		$flush_action = $action;

		$step = $action;
		break;

	case 'finish':	// Action for last step
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'phpbb' );

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );

		phpbb_clear_temporary_data();

		// Redirect to Blog
		header_redirect( $Blog->get( 'url' ) );
		break;
}


// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', 'import' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), '?ctrl=tools' );
$AdminUI->breadcrumbpath_add( T_('Import'), '?ctrl=tools&amp;tab3=import' );
$AdminUI->breadcrumbpath_add( T_('phpBB Importer'), '?ctrl=phpbbimport' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $step )
{
	case 'groups':	// Step 2
		phpbb_unset_var( 'current_step' );
		$AdminUI->disp_view( 'tools/views/_phpbb_groups.form.php' );
		// $phpbb_DB->dump_queries();
		break;

	case 'users':	// Step 3
		$AdminUI->disp_view( 'tools/views/_phpbb_users.form.php' );
		break;

	case 'forums':	// Step 4
		$AdminUI->disp_view( 'tools/views/_phpbb_forums.form.php' );
		break;

	case 'topics':	// Step 5
		$AdminUI->disp_view( 'tools/views/_phpbb_topics.form.php' );
		break;

	case 'replies':	// Step 6
		$AdminUI->disp_view( 'tools/views/_phpbb_replies.form.php' );
		break;

	case 'messages':	// Step 7
		$AdminUI->disp_view( 'tools/views/_phpbb_messages.form.php' );
		break;

	case 'config':	// Step 1
	default:
		phpbb_unset_var( 'current_step' );
		$AdminUI->disp_view( 'tools/views/_phpbb_config.form.php' );
		break;
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>