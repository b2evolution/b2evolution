<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: tools.ctrl.php 7516 2014-10-27 05:56:16Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs('plugins/_plugin.funcs.php');
load_funcs('tools/model/_dbmaintenance.funcs.php');
load_funcs('tools/model/_tool.funcs.php');

// load item class
load_class( 'items/model/_item.class.php', 'Item' );

param( 'tab', 'string', '', true );
param( 'tab3', 'string', 'tools', true );

$tab_Plugin = NULL;
$tab_plugin_ID = false;

if( ! empty($tab) )
{	// We have requested a tab which is handled by a plugin:
	if( preg_match( '~^plug_ID_(\d+)$~', $tab, $match ) )
	{ // Instanciate the invoked plugin:
		$tab_plugin_ID = $match[1];
		$tab_Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $tab_Plugin )
		{ // Plugin does not exist
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $tab_plugin_ID ), 'error' );
			$tab_plugin_ID = false;
			$tab_Plugin = false;
			$tab = '';
		}
		else
		{
			$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabAction', $params = array() );
		}
	}
	else
	{
		$tab = '';
		$Messages->add( 'Invalid sub-menu!' ); // Should need no translation, prevented by GUI
	}
}

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', !empty( $tab ) ? $tab : $tab3 );


if( empty($tab) )
{	// "Main tab" actions:
	if( param( 'action', 'string', '' ) )
	{
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tools' );

		// fp> TODO: have an option to only PRUNE files older than for example 30 days
		$current_User->check_perm('options', 'edit', true);
	}

	set_max_execution_time(0);

	$Plugins->trigger_event( 'AdminToolAction' );

	switch( $action )
	{
		case 'del_itemprecache':
			// Clear pre-renderered item cache (DB)
			dbm_delete_itemprecache();
			break;

		case 'del_commentprecache':
			// Clear pre-renderered comment cache (DB)
			$DB->query('DELETE FROM T_comments__prerendering WHERE 1=1');

			$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
			break;

		case 'del_pagecache':
			// Delete the page cache /blogs/cache
			dbm_delete_pagecache();
			break;

		case 'del_filecache':
			// delete the thumbnail cahces .evocache
			dbm_delete_filecache();
			break;

		case 'repair_cache':
			// Repair cache
			dbm_repair_cache();
			break;

		case 'optimize_tables': // Optimize MyISAM & InnoDB tables
		case 'check_tables':    // Check ALL database tables
		case 'analyze_tables':  // Analize ALL database tables
			$template_action = $action;
			break;

		case 'del_broken_posts':
			// Delete all broken posts that have no matching category
			$template_action = $action;
			break;

		case 'del_broken_slugs':
			// Find all broken slugs that have no matching target post
			dbm_delete_broken_slugs();
			break;

		case 'delete_orphan_comments':
			// delete orphan orphan comments with no matching Item
			dbm_delete_orphan_comments();
			break;

		case 'delete_orphan_comment_uploads':
			// delete orphan comment upload, older than 24 hours
			dbm_delete_orphan_comment_uploads();
			break;

		case 'delete_orphan_files':
			// delete orphan File objects with no matching file on disk
		case 'delete_orphan_file_roots':
			// delete orphan file roots with no matching Blog or User entry in the database
			$template_action = $action;
			break;

		case 'prune_hits_sessions':
			// Prune old hits & sessions
			load_class( 'sessions/model/_hitlist.class.php', 'Hitlist' );
			Hitlist::dbprune(); // will prune once per day, according to Settings
			break;

		case 'create_sample_comments':
			$blog_ID = param( 'blog_ID', 'string', 0 );
			$num_comments = param( 'num_comments', 'string', 0 );
			$num_posts = param( 'num_posts', 'string', 0 );

			if( ! ( param_check_number( 'blog_ID', T_('Blog ID must be a number'), true ) &&
				param_check_number( 'num_comments', T_('Comments per post must be a number'), true ) &&
				param_check_number( 'num_posts', T_('"How many posts" field must be a number'), true ) ) )
			{ // param errors
				$action = 'show_create_comments';
				break;
			}

			// check blog_ID
			$BlogCache = & get_BlogCache();
			$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
			if( $selected_Blog == NULL )
			{
				$Messages->add( T_( 'Blog ID must be a valid Blog ID!' ), 'error' );
				$action = 'show_create_comments';
				break;
			}

			// Execute a creating of comments inside template in order to see a process
			$template_action = 'create_sample_comments';
			break;

		case 'create_sample_posts':
			$blog_ID = param( 'blog_ID', 'string', 0 );
			$num_posts = param( 'num_posts', 'string', 0 );

			if( ! ( param_check_number( 'blog_ID', T_('Blog ID must be a number'), true ) &&
				param_check_number( 'num_posts', T_('"How many posts" field must be a number'), true ) ) )
			{ // param errors
				$action = 'show_create_posts';
				break;
			}

			// check blog_ID
			$BlogCache = & get_BlogCache();
			$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
			if( $selected_Blog == NULL )
			{
				$Messages->add( T_( 'Blog ID must be a valid Blog ID!' ), 'error' );
				$action = 'show_create_posts';
				break;
			}

			// Execute a creating of posts inside template in order to see a process
			$template_action = 'create_sample_posts';
			break;

		case 'create_sample_users':
			$num_users = param( 'num_users', 'string', 0 );
			$group_ID = param( 'group_ID', 'string', 0 );

			if( ! param_check_number( 'num_users', T_('"How many users" field must be a number'), true ) )
			{ // param errors
				$action = 'show_create_users';
				break;
			}

			// Execute a creating of users inside template in order to see a process
			$template_action = 'create_sample_users';
			break;

		case 'recreate_itemslugs':
			// Recreate all item slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but redirect to the new one.
			dbm_recreate_itemslugs();
			break;

		case 'recreate_autogenerated_excerpts':
			// Recreating of autogenerated excerpts
		case 'convert_item_content_separators':
			// Convert item content separators to new format
			// Execute these actions inside template in order to see the process
			$template_action = $action;
			break;

		case 'del_obsolete_tags':
			$DB->query('
				DELETE T_items__tag FROM T_items__tag
				  LEFT JOIN T_items__itemtag ON tag_ID = itag_tag_ID
				 WHERE itag_itm_ID IS NULL');
			$Messages->add( sprintf(T_('Removed %d obsolete tag entries.'), $DB->rows_affected), 'success' );
			break;

		case 'view_phpinfo':
			// Display PHP info and exit
			headers_content_mightcache('text/html');
			phpinfo();
			exit();
			break;

		case 'create_sample_hits':

			$days = param( 'days', 'integer', 0 );
			$min_interval = param( 'min_interval', 'integer', 0 );
			$max_interval = param( 'max_interval', 'integer', 0 );

			if( $days < 1 )
			{
				param_error( 'days', 'Please enter how many days of stats to generate' );
				$action = 'show_create_hits';
				break;
			}

			if( ( $min_interval > $max_interval ) || ( $min_interval < 0 ) || ( $max_interval <= 0 ) )
			{
				param_error( 'min_interval', 'Please enter correct interval values' );
				param_error( 'max_interval', 'Please enter correct interval values' );
				$action = 'show_create_hits';
				break;
			}

			// Execute a creating of hits inside template in order to see a process
			$template_action = 'create_sample_hits';
			break;

		case 'create_sample_messages':
			$num_loops = param( 'num_loops', 'string', 0 );
			$num_messages = param( 'num_messages', 'string', 0 );
			$num_words = param( 'num_words', 'string', 0 );
			$max_users = param( 'max_users', 'string', 0 );

			if( ! ( param_check_number( 'num_loops', T_('"How many loops" field must be a number'), true ) &&
				param_check_number( 'num_messages', T_('"How many messages in each conversation" field must be a number'), true ) &&
				param_check_number( 'num_words', T_('"How many words in each message" field must be a number'), true ) &&
				param_check_number( 'max_users', T_('"Max # of participants in a conversation" field must be a number'), true ) ) )
			{ // param errors
				$action = 'show_create_messages';
				break;
			}

			// Execute a creating of messages inside template in order to see a process
			$template_action = 'create_sample_messages';
			break;

		case 'test_flush':
			// Execute a testing of flush inside template in order to see a process
			$template_action = 'test_flush';
			$template_title = T_('Log of test flush').get_manual_link( 'test-flush-tool' );
			break;

		case 'utf8upgrade':
			// Upgrade DB to UTF-8
			load_funcs('_core/model/db/_upgrade.funcs.php');
			$template_action = $action;
			break;
	}
}
$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), '?ctrl=tools' );
switch( $tab3 )
{
	case 'import':
		$AdminUI->breadcrumbpath_add( T_('Import'), '?ctrl=tools&amp;tab3=import' );
		break;

	case 'test':
		$AdminUI->breadcrumbpath_add( T_('Testing'), '?ctrl=tools&amp;tab3=import' );
		break;

	case 'tools':
	default:
		$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=tools' );
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


if( empty($tab) )
{
	switch( $action )
	{
		case 'find_broken_posts':
			$AdminUI->disp_view( 'tools/views/_broken_posts.view.php' );
			break;

		case 'find_broken_slugs':
			$AdminUI->disp_view( 'tools/views/_broken_slugs.view.php' );
			break;

		case 'show_create_comments':
			$AdminUI->disp_view( 'tools/views/_create_comments.form.php' );
			break;

		case 'show_create_posts':
			$AdminUI->disp_view( 'tools/views/_create_posts.form.php' );
			break;
			break;

		case 'show_create_users':
			$AdminUI->disp_view( 'tools/views/_create_users.form.php' );
			break;

		case 'show_create_hits':
			$AdminUI->disp_view( 'tools/views/_create_test_hit.form.php' );
			break;

		case 'show_create_messages':
			// Get count users
			$SQL = new SQL();
			$SQL->SELECT( 'COUNT( user_ID )' );
			$SQL->FROM( 'T_users' );
			$users_count = $DB->get_var( $SQL->get() );
			$threads_count = $users_count * $users_count - $users_count + 1;

			$AdminUI->disp_view( 'tools/views/_create_messages.form.php' );
			break;


		default:
			switch( $tab3 )
			{
				case 'import':
					$AdminUI->disp_view( 'tools/views/_misc_import.view.php' );
					break;

				case 'test':
					$AdminUI->disp_view( 'tools/views/_misc_test.view.php' );
					break;

				case 'tools':
				default:
					$AdminUI->disp_view( 'tools/views/_misc_tools.view.php' );
					break;
			}
			break;
	}
}
elseif( $tab_Plugin )
{ // Plugin tab

	// Icons:
	?>

	<div class="right_icons">

	<?php
	echo $tab_Plugin->get_edit_settings_link()
		.' '.$tab_Plugin->get_help_link('$help_url');
	?>

	</div>

	<?php
	$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabPayload', $params = array() );
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>