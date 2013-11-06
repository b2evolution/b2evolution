<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
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

		case 'find_broken_posts':
			// Find all broken posts that have no matching category
			dbm_find_broken_posts();
			break;

		case 'find_broken_slugs':
			// Find all broken slugs that have no matching target post
			dbm_find_broken_slugs();
			break;

		case 'delete_orphan_comment_uploads':
			// delete orphan comment upload, older than 24 hours
			dbm_delete_orphan_comment_uploads();
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

			if ( ! ( param_check_number( 'blog_ID', T_('Blog ID must be a number'), true ) &&
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

			if ( ! ( param_check_number( 'blog_ID', T_('Blog ID must be a number'), true ) &&
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

			$time = ( $localtimenow );
			$content = T_( 'This is an auto generated post for testing moderation.' );
			for( $i = 1; $i <= $num_posts; $i++ )
			{
				for( $j = 0; $j < 50; $j++ )
				{ // create 50 random word
					$length = rand(1, 15);
					$word = generate_random_key( $length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
					$content = $content.' '.$word;
				}
				$Item = new Item();
				$Item->set( 'title', 'Generated post '.$i );
				$Item->set( 'content', $content );
				$Item->set( 'status', 'published' );
				$Item->set( 'dateset', 1 );
				// set post main cat ID, from selected blog
				$Item->set( 'main_cat_ID', $selected_Blog->get_default_cat_ID() );
				$Item->set( 'datestart', date( 'Y-m-d H:i:s', $time ) );
				$Item->set( 'datecreated', $time );
				$Item->dbinsert();
			}
			$Messages->add( sprintf( T_('Created %d posts.'), $num_posts ), 'success' );
			break;

		case 'create_sample_users':
			$num_users = param( 'num_users', 'string', 0 );
			$group_ID = param( 'group_ID', 'string', 0 );

			if( ! param_check_number( 'num_users', T_('"How many users" field must be a number'), true ) )
			{	// param errors
				$action = 'show_create_users';
				break;
			}

			$content = T_( 'This is an auto generated post for testing moderation.' );
			for( $i = 1; $i <= $num_users; $i++ )
			{
				$login = generate_random_key( rand(3, 20), 'abcdefghijklmnopqrstuvwxyz1234567890' );
				while( user_exists($login) )
				{	// Generate new unique login
					$login = generate_random_key( rand(3, 20), 'abcdefghijklmnopqrstuvwxyz1234567890' );
				}

				$User = new User();
				// Create out of range hashes for better security
				$User->set( 'pass', generate_random_key( 32, 'abcdefghijklmnopqrstuvwxyz1234567890' ) );
				$User->set( 'login', $login );
				$User->set( 'email', 'test_'.$i.'@test.com' );
				$User->set( 'firstname', 'Test user '.$i );
				$User->set( 'url', 'http://www.test-'.rand(1,3).'.com/test_user_'.$i );
				$User->set( 'group_ID', $group_ID );
				$User->dbinsert();
			}
			$Messages->add( sprintf( T_('Created %d users.'), $num_users ), 'success' );
			break;

		case 'recreate_itemslugs':
			// Recreate all item slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but redirect to the new one.
			dbm_recreate_itemslugs();
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

		case 'create_test_hit':

			$days = param( 'days', 'integer', 0 );
			$min_interval = param( 'min_interval', 'integer', 0 );
			$max_interval = param( 'max_interval', 'integer', 0 );

			if ($days < 1)
			{
				param_error( 'days', 'Please enter how many days of stats to generate' );
				$action = 'show_create_test_hit';
				break;
			}

			if (($min_interval > $max_interval) || ($min_interval < 0) || ($max_interval <= 0))
			{
				param_error( 'min_interval', 'Please enter correct interval values' );
				param_error( 'max_interval', 'Please enter correct interval values' );
				$action = 'show_create_test_hit';
				break;
			}

			load_funcs('sessions/model/_hitlog.funcs.php');

			$insert_data_count = generate_hit_stat($days, $min_interval, $max_interval);

			$Messages->add( sprintf( '%d test data items are added.', $insert_data_count ), 'success' );
			break;

		case 'create_sample_messages':
			$num_loops = param( 'num_loops', 'string', 0 );
			$num_messages = param( 'num_messages', 'string', 0 );
			$num_words = param( 'num_words', 'string', 0 );
			$max_users = param( 'max_users', 'string', 0 );

			if ( ! ( param_check_number( 'num_loops', T_('"How many loops" field must be a number'), true ) &&
				param_check_number( 'num_messages', T_('"How many messages in each conversation" field must be a number'), true ) &&
				param_check_number( 'num_words', T_('"How many words in each message" field must be a number'), true ) &&
				param_check_number( 'max_users', T_('"Max # of participants in a conversation" field must be a number'), true ) ) )
			{	// param errors
				$action = 'show_create_messages';
				break;
			}

			// Get all users
			$SQL = new SQL();
			$SQL->SELECT( 'user_ID' );
			$SQL->FROM( 'T_users' );
			$users = $DB->get_col( $SQL->get() );

			if( count( $users ) < 2 )
			{	// No users
				$Messages->add( T_('At least two users must exist in DB to create the messages'), 'error' );
				$action = 'show_create_messages';
				break;
			}

			$count_threads = 0;
			$count_messages = 0;
			for( $l = 0; $l < $num_loops; $l++ )
			{
				$user_links = array();
				foreach( $users as $from_user_ID )
				{
					foreach( $users as $to_user_ID )
					{
						if( $from_user_ID == $to_user_ID || isset( $user_links[ (string) $from_user_ID.'-'.$to_user_ID ] ) )
						{
							continue;
						}

						$user_links[ $from_user_ID.'-'.$to_user_ID ] = 1;
						// Insert thread
						$DB->query( 'INSERT INTO T_messaging__thread ( thrd_title, thrd_datemodified )
							VALUES ( '.$DB->quote( generate_random_key( 16 ) ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).' )' );

						$thread_ID = $DB->insert_id;
						$count_threads++;

						for( $m = 0; $m < $num_messages; $m++ )
						{
							$msg_text = '';
							for( $w = 0; $w < $num_words; $w++ )
							{
								$msg_text .= generate_random_key( 8 ).' ';
							}
							$message_user_ID = $m % 2 == 0 ? $from_user_ID : $to_user_ID;
							// Insert message
							$DB->query( 'INSERT INTO T_messaging__message ( msg_author_user_ID , msg_datetime, msg_thread_ID, msg_text )
								VALUES ( '.$DB->quote( $message_user_ID ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).', '.$DB->quote( $thread_ID ).', '.$DB->quote( $msg_text ).' )' );
							$count_messages++;
						}

						// Insert link for thread & user
						$DB->query( 'INSERT INTO T_messaging__threadstatus ( tsta_thread_ID , tsta_user_ID, tsta_first_unread_msg_ID )
							VALUES ( '.$DB->quote( $thread_ID ).', '.$DB->quote( $from_user_ID ).', NULL ),
								     ( '.$DB->quote( $thread_ID ).', '.$DB->quote( $to_user_ID ).', NULL )' );
					}
				}

				/** Create one conversation between all users ( limit by $max_users ) **/

				// Insert thread
				$DB->query( 'INSERT INTO T_messaging__thread ( thrd_title, thrd_datemodified )
					VALUES ( '.$DB->quote( generate_random_key( 16 ) ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).' )' );

				$thread_ID = $DB->insert_id;
				$count_threads++;

				$user_number = 0;
				for( $m = 0; $m < $num_messages; $m++ )
				{
					$msg_text = '';
					for( $w = 0; $w < $num_words; $w++ )
					{
						$msg_text .= generate_random_key( 8 ).' ';
					}
					// Insert message
					$DB->query( 'INSERT INTO T_messaging__message ( msg_author_user_ID , msg_datetime, msg_thread_ID, msg_text )
						VALUES ( '.$DB->quote( $users[ $user_number ] ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).', '.$DB->quote( $thread_ID ).', '.$DB->quote( $msg_text ).' )' );
					$count_messages++;
					$user_number++;
					if( $user_number == count( $users ) || $user_number == $max_users - 1 )
					{	// Reset user number to start of the list
						$user_number = 0;
					}
				}

				// Insert the links between thread & users
				$threadstatuses = array();
				foreach( $users as $u => $user_ID )
				{
					$threadstatuses[] = '( '.$DB->quote( $thread_ID ).', '.$DB->quote( $user_ID ).', NULL )';
					if( $u == $max_users - 1 )
					{	// limit by max users in one thread
						break;
					}
				}
				$DB->query( 'INSERT INTO T_messaging__threadstatus ( tsta_thread_ID , tsta_user_ID, tsta_first_unread_msg_ID )
					VALUES '.implode( ', ', $threadstatuses ) );
			}

			$Messages->add( sprintf( T_('%d threads and %d messages have been created.'), $count_threads, $count_messages ), 'success' );
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

		case 'show_create_test_hit':
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

/*
 * $Log$
 * Revision 1.58  2013/11/06 08:04:54  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>