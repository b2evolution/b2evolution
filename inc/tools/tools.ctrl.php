<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $deferred_AdminToolActions;

// Check permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'view', true );

load_funcs( 'plugins/_plugin.funcs.php' );
load_funcs( 'tools/model/_maintenance.funcs.php' );
load_funcs( 'tools/model/_tool.funcs.php' );
load_funcs( 'tools/model/_system.funcs.php' );
//load_funcs( '_core/model/db/_upgrade.funcs.php' );

// load item class
load_class( 'items/model/_item.class.php', 'Item' );

if( $current_User->check_perm( 'options', 'edit' ) &&
    ( $action != 'utf8check' && $action != 'utf8upgrade' ) &&
    system_check_charset_update() )
{ // DB charset is required to update
	$Messages->add( sprintf( T_('WARNING: Some of your tables have different charsets/collations than the expected. It is strongly recommended to upgrade your database charset by running the tool <a %s>Check/Convert/Normalize the charsets/collations used by the DB (UTF-8 / ASCII)</a>.'), 'href="'.$admin_url.'?ctrl=tools&amp;action=utf8check&amp;'.url_crumb( 'tools' ).'"' ) );
}

param( 'tab', 'string', '', true );
param( 'tab3', 'string', 'tools', true );

if( $tab3 == 'import' )
{	// Check permission for import pages:
	$current_User->check_perm( 'options', 'edit', true );
}

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
			$plugin_params = array();
			$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabAction', $plugin_params );
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


if( empty( $tab ) )
{	// "Main tab" actions:
	if( param( 'action', 'string', '' ) )
	{
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tools' );

		// fp> TODO: have an option to only PRUNE files older than for example 30 days
		$current_User->check_perm('options', 'edit', true);
	}

	set_max_execution_time( 0 );

	$Plugins->trigger_event( 'AdminToolAction' );

	switch( $action )
	{
		case 'del_itemprecache':
			// Clear pre-rendered item cache (DB)
			$template_log_title = T_('Clear pre-rendered item cache (DB)');
			$template_action = $action;
			break;

		case 'del_commentprecache':
			// Clear pre-rendered comment cache (DB)
			$template_log_title = T_('Clear pre-rendered comment cache (DB)');
			$template_action = $action;
			break;

		case 'del_messageprecache':
			// Clear pre-rendered message cache (DB)
			$template_log_title = T_('Clear pre-rendered message cache (DB)');
			$template_action = $action;
			break;

		case 'del_pagecache':
			// Delete the page cache /blogs/cache
			$template_log_title = T_('Clear full page caches (/cache/* directories)');
			$template_action = $action;
			break;

		case 'del_filecache':
			// delete the thumbnail cahces .evocache
			$template_log_title = T_('Clear thumbnail caches (?evocache directories)');
			$template_action = $action;
			break;

		case 'repair_cache':
			// Repair cache
			$template_log_title = T_('Repair /cache/* directory structure');
			$template_action = $action;
			break;

		case 'optimize_tables':
			// Optimize MyISAM & InnoDB tables
			$template_log_title = T_('OPTIMIZE database tables');
			$template_action = $action;
			break;

		case 'check_tables':
			// Check ALL database tables
			$template_log_title = T_('CHECK database tables');
			$template_action = $action;
			break;

		case 'analyze_tables':  // Analize ALL database tables
			$template_log_title = T_('ANALYZE database tables');
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
			$template_log_title = T_('Find and delete all orphan Comments (with no matching Item) - Disk &amp; DB.');
			$template_action = $action;
			break;

		case 'delete_orphan_comment_uploads':
			// delete orphan comment upload, older than 24 hours
			$template_log_title = T_('Find and delete all orphan comment Uploads - Disk &amp; DB.');
			$template_action = $action;
			break;

		case 'delete_orphan_files':
			// delete orphan File objects with no matching file on disk
			$template_log_title = T_('Find and delete all orphan File objects (with no matching file on disk) - DB only.');
			$template_action = $action;
			break;

		case 'delete_orphan_file_roots':
			// delete orphan file roots with no matching Blog or User entry in the database
			$template_log_title = T_('Find and delete all orphan file roots (with no matching Collection or User) and all of their content recursively - Disk &amp; DB.');
			$template_action = $action;
			break;

		case 'prune_hits_sessions':
			// Prune old hits & sessions
			$template_log_title = T_('Prune old hits &amp; sessions (includes OPTIMIZE) - DB only.');
			$template_action = $action;
			break;

		case 'create_sample_collections':
			// Create sample collections:
			$num_collections = param( 'num_collections', 'integer', 0 );

			$perm_management = param( 'perm_management', 'array:string' );
			if( empty( $perm_management ) )
			{	// At least one option must be selected:
				$Messages->add( sprintf( T_('Please selected at least one option of the setting "%s".'), T_('Permission management') ), 'error' );
			}

			$allow_access = param( 'allow_access', 'array:string' );
			if( empty( $allow_access ) )
			{	// At least one option must be selected:
				$Messages->add( sprintf( T_('Please selected at least one option of the setting "%s".'), T_('Allow access to') ), 'error' );
			}

			if( param_errors_detected() )
			{	// If some param errors then stop a creating and display a form to correct:
				$action = 'show_create_collections';
				break;
			}

			// Execute a creating of collections inside template in order to see a process:
			$template_action = 'create_sample_collections';
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

		case 'create_sample_revisions':
			$blog_ID = param( 'blog_ID', 'string', 0 );
			$min_revisions = param( 'min_revisions', 'string', 0 );
			$max_revisions = param( 'max_revisions', 'string', 0 );

			if( ! ( param_check_number( 'blog_ID', T_('Blog ID must be a number'), true ) &&
					param_check_number( 'min_revisions', T_('Minimum number of revisions field must be a number'), true ) &&
					param_check_number( 'max_revisions', T_('Maximum number of revisions filed must be a number'), true ) ) )
			{ // param errors
				$action = 'show_create_revisions';
				break;
			}

			// check blog_ID
			$BlogCache = & get_BlogCache();
			$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
			if( $selected_Blog == NULL )
			{
				$Messages->add( T_('Blog ID must be a valid Blog ID!'), 'error' );
				$action = 'show_create_revisions';
				break;
			}

			// Execute a creating of revisions inside template in order to see the process
			$template_action = 'create_sample_revisions';
			break;

		case 'create_sample_users':
			$num_users = param( 'num_users', 'string', 0 );
			param_check_number( 'num_users', T_('"How many users" field must be a number'), true );

			$user_groups = param( 'user_groups', 'array:integer' );
			if( empty( $user_groups ) )
			{	// At least one option must be selected:
				$Messages->add( sprintf( T_('Please selected at least one option of the setting "%s".'), T_('Create new users in') ), 'error' );
			}

			$advanced_user_perms = param( 'advanced_user_perms', 'array:string' );
			if( empty( $advanced_user_perms ) )
			{	// At least one option must be selected:
				$Messages->add( sprintf( T_('Please selected at least one option of the setting "%s".'), T_('Advanced user perms to grant on existing collections with advanced perms') ), 'error' );
			}

			if( param_errors_detected() )
			{	// If some param errors then stop a creating and display a form to correct:
				$action = 'show_create_users';
				break;
			}

			// Execute a creating of users inside template in order to see a process
			$template_action = 'create_sample_users';
			break;

		case 'recreate_itemslugs':
			// Recreate all item slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but redirect to the new one.
			$template_log_title = T_('Recreate all item Slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but will redirect to the new ones - DB only.');
			$template_action = $action;
			break;

		case 'recreate_autogenerated_excerpts':
			// Recreating of autogenerated excerpts
			$template_log_title = T_('Recreate autogenerated excerpts - DB only.');
			$template_action = $action;
			break;

		case 'convert_item_content_separators':
			// Convert item content separators to new format
			// Execute these actions inside template in order to see the process
			$template_log_title = T_('Convert item content separators to [teaserbreak] and [pagebreak] - DB only.');
			$template_action = $action;
			break;

		case 'delete_item_versions':
			param( 'confirmed', 'integer', 0 );

			if( $confirmed )
			{
				$template_log_title = T_('Remove all Item version - DB only.');
				$template_action = $action;
			}
			else
			{
				$action = 'show_delete_item_versions';
			}
			break;

		case 'resize_all_images':
			param( 'confirmed', 'integer', 0 );

			if( $confirmed )
			{
				$template_log_title = T_('Resize all images in the media directory');
				$template_action = $action;
			}
			else
			{
				$action = 'show_resize_all_images';
			}
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

		case 'create_sample_basedomains':
			// Create sample base domains:
			$num_basedomains = param( 'num_basedomains', 'string', 0 );

			if( ! param_check_number( 'num_basedomains', T_('"How many base domains" field must be a number'), true ) )
			{	// Stop action because of param error:
				$action = 'show_create_basedomains';
				break;
			}

			// Execute a creating of base domains inside template in order to see a process:
			$template_action = 'create_sample_basedomains';
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

		case 'utf8check':
			//$template_log_title = T_('Check/Convert/Normalize the charsets/collations used by the DB (UTF-8 / ASCII)');
			load_funcs('_core/model/db/_upgrade.funcs.php');
			$template_log_title = T_('Normalizing DB charsets...');
			$template_action = $action;
			break;

		case 'utf8upgrade':
			// Check/Upgrade DB to UTF-8
			load_funcs('_core/model/db/_upgrade.funcs.php');
			$template_log_title = T_('Normalizing DB charsets...');
			$template_action = $action;
			break;

		case 'update_tools':
			// UPDATE general settings from tools:

			// Check permission:
			$current_User->check_perm( 'options', 'edit', true );

			// Lock system
			if( $current_User->check_perm( 'users', 'edit' ) )
			{
				$system_lock = param( 'system_lock', 'integer', 0 );
				if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
				{ // System lock was turned off and there was no error, remove the warning about the system lock
					$Messages->clear();
				}
				$Settings->set( 'system_lock', $system_lock );
			}

			if( ! $Messages->has_errors() )
			{
				$Settings->dbupdate();
				$Messages->add( T_('Site settings updated.'), 'success' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=tools', 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;
	}
}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), $admin_url.'?ctrl=tools' );
switch( $tab3 )
{
	case 'import':
		$AdminUI->breadcrumbpath_add( T_('Import'), $admin_url.'?ctrl=tools&amp;tab3=import' );

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'import-tab' );
		break;

	case 'test':
		$AdminUI->breadcrumbpath_add( T_('Testing'), $admin_url.'?ctrl=tools&amp;tab3=import' );

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'testing-tools' );
		break;

	case 'tools':
	default:
		$AdminUI->breadcrumbpath_add( T_('Tools'), $admin_url.'?ctrl=tools' );

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'tools-tab' );
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

if( empty( $tab ) )
{
	switch( $action )
	{
		case 'find_broken_posts':
			$AdminUI->disp_view( 'tools/views/_broken_posts.view.php' );
			break;

		case 'find_broken_slugs':
			$AdminUI->disp_view( 'tools/views/_broken_slugs.view.php' );
			break;

		case 'show_create_collections':
			$AdminUI->disp_view( 'tools/views/_create_collections.form.php' );
			break;

		case 'show_create_comments':
			$AdminUI->disp_view( 'tools/views/_create_comments.form.php' );
			break;

		case 'show_create_posts':
			$AdminUI->disp_view( 'tools/views/_create_posts.form.php' );
			break;

		case 'show_create_revisions':
			$AdminUI->disp_view( 'tools/views/_create_revisions.form.php' );
			break;

		case 'show_create_users':
			$AdminUI->disp_view( 'tools/views/_create_users.form.php' );
			break;

		case 'show_create_hits':
			$AdminUI->disp_view( 'tools/views/_create_test_hit.form.php' );
			break;

		case 'show_create_basedomains':
			$AdminUI->disp_view( 'tools/views/_create_basedomains.form.php' );
			break;

		case 'show_create_messages':
			// Get count users
			$SQL = new SQL( 'Get a count of all users' );
			$SQL->SELECT( 'COUNT( user_ID )' );
			$SQL->FROM( 'T_users' );
			$users_count = $DB->get_var( $SQL );
			$threads_count = $users_count * $users_count - $users_count + 1;

			$AdminUI->disp_view( 'tools/views/_create_messages.form.php' );
			break;

		case 'show_delete_item_versions':
			$AdminUI->disp_view( 'tools/views/_delete_item_versions.form.php' );
			$AdminUI->disp_view( 'tools/views/_misc_tools.view.php' );
			break;

		case 'show_resize_all_images':
			$AdminUI->disp_view( 'tools/views/_image_resize.form.php' );
			$AdminUI->disp_view( 'tools/views/_misc_tools.view.php' );
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
	$plugin_params = array();
	$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabPayload', $plugin_params );
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>