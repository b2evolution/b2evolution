<?php
/**
 * This file implements functions to work with DB maintenance.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


function print_log( $message, $type = NULL, $params = array()  )
{
	$params = array_merge( array(
			'message_before' => '',
			'message_after' => '<br />',
			'text_style' => 'normal'
		), $params );

	switch( $type )
	{
		case 'success':
			$message_before = '<span class="text-success">';
			$message_after = '</span>';
			break;

		case 'error':
			$message_before = '<span class="text-danger">';
			$message_after = '</span>';
			break;

		case 'warning':
			$message_before = '<span class="text-warning">';
			$message_after = '</span>';
			break;

		case 'note':
			$message_before = '<span class="text-info">';
			$message_after = '</span>';
			break;

		default:
			$message_before = '';
			$message_after = '';
			break;
	}

	switch( $params['text_style'] )
	{
		case 'bold':
			$message = '<b>'.$message.'</b>';
			break;

		case 'italic':
			$message = '<i>'.$message.'</i>';
			break;
	}

	echo $params['message_before'].$message_before.$message.$message_after.$params['message_after'];
	evo_flush();
}


/**
 * Clear pre-rendered item cache (DB)
 */
function dbm_delete_itemprecache()
{
	global $DB, $Messages;

	$DB->query('DELETE FROM T_items__prerendering WHERE 1=1');

	print_log( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
}


/**
 * Clear pre-rendered comment cache (DB)
 */
function dbm_delete_commentprecache()
{
	global $DB, $Messages;

	$DB->query('DELETE FROM T_comments__prerendering WHERE 1=1');

	print_log( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );

}


/**
 * Clear pre-rendered message cache (DB)
 */
function dbm_delete_messageprecache()
{
	global $DB, $Messages;

	$DB->query('DELETE FROM T_messaging__prerendering WHERE 1=1');

	print_log( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
}


/**
 * Clear full page cache (/cache directory)
 *
 * @param boolean Display messages
 */
function dbm_delete_pagecache( $display_details = true )
{
	global $DB, $Messages, $cache_path;

	if( $display_details )
	{	// Display message only when it is required:
		print_log( T_('Clearing page caches:'), 'normal', array( 'text_style' => 'bold' ) );
		evo_flush();

		echo '<ul>';
	}

	// Clear general cache directory:
	$result = cleardir_r( $cache_path.'general' );
	if( $display_details )
	{ // Display message only when it is required:
		if( $result )
		{
			echo '<li>';
			print_log( sprintf( T_('General cache deleted: %s'), $cache_path.'general' ) );
			echo '</li>';
		}
		else
		{
			echo '<li>';
			print_log( sprintf( T_('Could not delete general cache: %s'), $cache_path.'general' ), 'error' );
			echo '</li>';
		}
	}

	$SQL = 'SELECT blog_ID FROM T_blogs
			INNER JOIN T_coll_settings ON ( blog_ID = cset_coll_ID
						AND cset_name = "cache_enabled"
						AND cset_value = "1" )
			WHERE 1=1';

	if( $blog_array = $DB->get_col( $SQL ) )
	{
		foreach( $blog_array as $l_blog )
		{ // Clear blog cache:
			$result = cleardir_r( $cache_path.'c'.$l_blog );
			if( $display_details )
			{ // Display message only when it is required:
				if( $result )
				{
					echo '<li>';
					print_log( sprintf( T_('Blog %d cache deleted: %s'), $l_blog, $cache_path.'c'.$l_blog ) );
					echo '</li>';
				}
				else
				{
					echo '<li>';
					print_log( sprintf( T_('Could not delete blog %d cache: %s'), $l_blog, $cache_path.'c'.$l_blog ), 'error' );
					echo '</li>';
				}
			}
			// Create .htaccess file with deny rules
			create_htaccess_deny( $cache_path );
		}
	}

	if( $display_details )
	{	// Display message only when it is required:
		echo '</ul>';

		echo '<br />';
		print_log( T_('Page caches deleted.'), 'success' );
	}
}


/**
 * Clear thumbnail caches (?evocache directories)
 */
function dbm_delete_filecache()
{
	global $Messages;

	// TODO> handle custom media directories dh> ??
	// Delete any ?evocache folders:
	$deleted_dirs = delete_cachefolders( $Messages );

	print_log( sprintf( T_('Deleted %d directories.'), $deleted_dirs ), 'success' );
}


/**
 * Repair cache
 */
function dbm_repair_cache()
{
	global $Messages;

	load_funcs( 'tools/model/_system.funcs.php' );
	$result = system_check_caches();
	if( empty( $result ) )
	{
		print_log( T_( 'All cache folders are working properly.' ), 'success' );
	}
	else
	{
		$error_message = T_( 'Unable to repair all cache folders becaue of file permissions' ).':<br />';
		print_log( $error_message.implode( '<br />', $result ) );
	}
}


/**
 * Optimize DB tables (MyISAM & InnoDB)
 *
 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
 * @param boolean TRUE - to make optimize query for each table separately
 * @return array Results of the mysql command 'OPTIMIZE'
 */
function dbm_optimize_tables( $display_messages = true, $separate_tables = true )
{
	global $tableprefix, $DB, $Timer, $dbm_tables_count;

	$tables = $DB->get_results( 'SHOW TABLE STATUS FROM `'.$DB->dbname.'` LIKE \''.$tableprefix.'%\'');

	$myisam_tables = array();
	$innodb_tables = array();
	foreach( $tables as $table )
	{
		// Before MySQL 4.1.2, the "Engine" field was labeled as "Type".
		if( ( ( isset( $table->Engine ) && $table->Engine == 'MyISAM' )
				|| ( isset( $table->Type ) && $table->Type == 'MyISAM' ) )
			&& $table->Data_free )
		{	// Optimization needed for MyISAM table
			$myisam_tables[] = $table->Name;
		}
		else if( ( ( isset( $table->Engine ) && $table->Engine == 'InnoDB' )
				|| ( isset( $table->Type ) && $table->Type == 'InnoDB' ) )
			&& $table->Data_free )
		{	// Optimization needed for InnoDB table
			$innodb_tables[] = $table->Name;
		}
	}
	$dbm_tables_count = count( $myisam_tables ) + count( $innodb_tables );

	// Optimize MyISAM tables
	$myisam_results = dbm_optimize_tables_process( $display_messages, $separate_tables, $myisam_tables, 'MyISAM' );
	$results = $myisam_results;

	// Optimize InnoDB tables
	$innodb_results = dbm_optimize_tables_process( $display_messages, $separate_tables, $innodb_tables, 'InnoDB' );
	$results = array_merge( $results, $innodb_results );

	return $results;
}


/**
 * Optimize process DB tables (MyISAM & InnoDB)
 *
 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
 * @param boolean TRUE - to make optimize query for each table separately
 * @param array Tables
 * @param string Table type: 'MyISAM' or 'InnoDB'
 * @return array Results of the mysql command 'OPTIMIZE'
 */
function dbm_optimize_tables_process( $display_messages = true, $separate_tables = true, $tables = array(), $table_type = '' )
{
	global $DB;

	load_class( '_core/model/_timer.class.php', 'Timer' );
	$Timer = new Timer('dbm_optimize');

	$results = array();

	if( $display_messages )
	{	// Display or log messages:
		$log_message = '<b>'.sprintf( T_('Optimize %s tables...'), $table_type ).'</b>';
		if( $display_messages === 'cron_job' )
		{	// Log a message for cron job:
			cron_log_append( $log_message );
		}
		else
		{	// Print out a message:
			echo $log_message.'<br />';
			evo_flush();
		}
	}

	$timer_name = 'optimize_'.strtolower( $table_type );

	$Timer->start( $timer_name );

	if( !empty( $tables ) )
	{ // Optimize tables
		if( $separate_tables )
		{ // Optimize each table in separate query
			foreach( $tables as $table )
			{
				$Timer->start( $timer_name.'_table' );
				$table_results = $DB->get_results( 'OPTIMIZE NO_WRITE_TO_BINLOG TABLE '.$table );
				$Timer->stop( $timer_name.'_table' );
				if( $display_messages )
				{	// Display or log messages:
					dbm_display_result_messages( $table_results, 'optimize', $display_messages );
					$log_message = '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( $timer_name.'_table' ) ).'</b>';
					if( $display_messages === 'cron_job' )
					{	// Log a message for cron job:
						cron_log_append( $log_message."\n" );
					}
					else
					{	// Print out a message:
						echo $log_message.'<br /><br />';
						evo_flush();
					}
				}
				$results = array_merge( $results, $table_results );
			}
		}
		else
		{ // Optimize all table by one query, Used for cron job
			$results = $DB->get_results( 'OPTIMIZE NO_WRITE_TO_BINLOG TABLE '.implode( ', ', $tables ) );
		}
	}

	$Timer->stop( $timer_name );

	if( $display_messages )
	{	// Display or log messages:
		if( !$separate_tables || empty( $tables ) )
		{ // Display full report log for case when the tables were optimized by one query
			dbm_display_result_messages( $results, 'optimize', $display_messages );
		}
		$log_message = '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( $timer_name ) ).'</b>';
		if( $display_messages === 'cron_job' )
		{	// Log a message for cron job:
			cron_log_append( $log_message."\n" );
		}
		else
		{	// Print out a message:
			echo $log_message.'<br /><br />';
			evo_flush();
		}
	}

	return $results;
}


/**
 * Print on screen the messages of 'OPTIMIZE', 'CHECK' OR 'ANALYZE' commands
 *
 * @param array Results of the mysql commands 'OPTIMIZE', 'CHECK' OR 'ANALYZE'
 * @param string mysql command type: optimize, check, analyze
 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
 */
function dbm_display_result_messages( $results, $command_type, $display_messages = true )
{
	switch( $command_type )
	{
		case 'optimize':
			$params = array(
					'message_ok'    => T_('Database table %s is optimized.'),
					'message_error' => T_('Database table %s could not be optimized. Message: %s'),
					'message_done'  => T_('All database tables are already optimized.'),
				);
			break;

		case 'check':
			$params = array(
					'message_ok'    => T_('Database table %s is checked.'),
					'message_error' => T_('Database table %s could not be checked. Message: %s'),
					'message_done'  => T_('All database tables are already checked.'),
				);
			break;

		case 'analyze':
			$params = array(
					'message_ok'    => T_('Database table %s is analyzed.'),
					'message_error' => T_('Database table %s could not be analyzed. Message: %s'),
					'message_done'  => T_('All database tables are already analyzed.'),
				);
			break;

		default:
			// Don't support unknown commants, Exit here
			return;
	}

	if( count( $results ) > 0 )
	{
		foreach( $results as $result )
		{
			$log_type = NULL;
			if( $result->Msg_type == 'status' && $result->Msg_text == 'OK' )
			{ // OK
				$log_message = sprintf( $params['message_ok'], '<code>'.$result->Table.'</code>' );
			}
			elseif( $result->Msg_type == 'note' && $result->Msg_text == 'Table does not support optimize, doing recreate + analyze instead' )
			{ // This warning is comming for every innodb table, but that is normal, Display for info
				$log_message = sprintf( T_('Database table %s does not support optimize, doing recreate + analyze instead'), '<code>'.$result->Table.'</code>' );
			}
			else
			{ // Some errors
				$log_message = sprintf( $params['message_error'], '<code>'.$result->Table.'</code>', '"'.$result->Msg_text.'"' );
				if( $display_messages === 'cron_job' )
				{	// Log a message for cron job:
					$log_type = ( $result->Msg_type == 'status' ? 'warning' : 'error' );
				}
				else
				{	// Print out a message:
					$log_message = '<span class="'.( $result->Msg_type == 'status' ? 'orange' : 'red' ).'">'.$log_message.'</span>';
				}
			}

			if( $display_messages === 'cron_job' )
			{	// Log a message for cron job:
				cron_log_action_end( $log_message, $log_type );
			}
			else
			{	// Print out a message:
				echo $log_message.'<br />';
				evo_flush();
			}
		}
	}
	else
	{ // No tables found to optimize, probably all tables already were optimized
		if( $display_messages === 'cron_job' )
		{	// Log a message for cron job:
			cron_log_append( $params['message_done'] );
		}
		else
		{	// Print out a message:
			echo $params['message_done'].'<br />';
			evo_flush();
		}
	}
}


/**
 * Delete all broken posts that have no matching category
 */
function dbm_delete_broken_posts()
{
	global $DB, $Messages, $current_User;

	$num_deleted = 0;

	echo T_('Removing broken posts that have no matching category... ');
	evo_flush();

	// Delete the posts only by these IDs
	$post_IDs = trim( param( 'posts', '/^[\d,]+$/', true ), ',' );
	$post_IDs = preg_replace( '/(,){2,}/', ',', $post_IDs );

	if( ! empty( $post_IDs ) )
	{
		// select broken items
		$SQL = new SQL( 'Find broken posts' );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_items__item' );
		$SQL->WHERE( 'post_main_cat_ID NOT IN ( SELECT cat_ID FROM T_categories )' );
		$SQL->WHERE_and( 'post_ID IN ( '.$post_IDs.' )' );
		$broken_items = $DB->get_results( $SQL );

		foreach( $broken_items as $r => $row )
		{ // delete broken items
			$broken_Item = new Item( $row );
			if( $broken_Item->dbdelete() )
			{ // Post was deleted successfully
				$num_deleted++;
			}
			else
			{ // Post was NOT deleted
				echo '<p class="red">'.sprintf( T_('Cannot delete post with ID %s'), $broken_Item->ID ).'</p>';
			}
			if( $r % 100 == 0 )
			{ // Display a log dot after each 100 processed posts
				echo '. ';
				evo_flush();
			}
		}
	}

	echo '<p>'.sprintf( T_('Deleted %d posts.'), $num_deleted ).'</p>';
}


/**
 * Delete all broken slugs that have no matching target post
 */
function dbm_delete_broken_slugs()
{
	global $DB, $Messages;

	// Delete the s;ugs only by these IDs
	$slug_IDs = trim( param( 'slugs', '/^[\d,]+$/', true ), ',' );
	$slug_IDs = preg_replace( '/(,){2,}/', ',', $slug_IDs );

	if( ! empty( $slug_IDs ) )
	{
		// delete broken slugs
		$num_deleted = $DB->query( 'DELETE FROM T_slug
			WHERE slug_type = "item"
			  AND slug_itm_ID NOT IN ( SELECT post_ID FROM T_items__item )
			  AND slug_ID IN ( '.$slug_IDs.' )' );
	}
	else
	{
		$num_deleted = 0;
	}

	$Messages->add( sprintf( T_('Deleted %d slugs.'), intval( $num_deleted ) ), 'success' );
}


/**
 * Find and delete orphan comments with no matching Item
 */
function dbm_delete_orphan_comments()
{
	global $Messages, $DB;

	// Get all comment with no matching Item
	$comments_SQL = new SQL();
	$comments_SQL->SELECT( 'comment_ID' );
	$comments_SQL->FROM( 'T_comments' );
	$comments_SQL->FROM_add( 'LEFT JOIN T_items__item ON comment_item_ID = post_ID' );
	$comments_SQL->WHERE( 'post_ID IS NULL' );
	$comments = $DB->get_col( $comments_SQL->get() );

	$num_deleted = 0;
	$CommentCache = & get_CommentCache();
	foreach( $comments as $comment_ID )
	{
		if( ( $broken_Comment = & $CommentCache->get_by_ID( $comment_ID, false, false ) ) !== false )
		{ // Comment object is created
			if( $broken_Comment->dbdelete( true ) )
			{ // Comment is deleted successfully
				$num_deleted++;
			}
		}
		// Clear cache to save memory
		$CommentCache->clear();
	}

	print_log( sprintf( T_('%d comments have been deleted'), $num_deleted ), 'success' );
}


/**
 * Find and delete orphan comment uploads
 */
function dbm_delete_orphan_comment_uploads()
{
	global $Messages;

	$count = remove_orphan_files( NULL, 24 );

	print_log( sprintf( T_('%d files have been deleted'), $count ), 'success' );
}


/**
 * Find and delete orphan File objects with no matching file on disk
 *
 * @param boolean TRUE - to delete the found orphan files, FALSE - only display ALL found files in DB
 * @param boolean TRUE - to delete orphan files even if they are linked to other objects
 */
function dbm_delete_orphan_files( $delete_orphan_files, $delete_orphan_linked_files = false )
{
	global $DB, $admin_url;

	$FileCache = & get_FileCache();
	$FileCache->clear();

	echo T_('Finding of the orphan File objects in the database...');
	echo '<ul>';
	evo_flush();

	$files_SQL = new SQL( 'Find orphan files for deleting' );
	$files_SQL->SELECT( 'file_ID, file_root_type, file_root_ID, file_path, COUNT( link_ID ) AS links_num, GROUP_CONCAT( link_ID ) AS link_IDs' );
	$files_SQL->FROM( 'T_files' );
	$files_SQL->FROM_add( 'LEFT JOIN T_links ON file_ID = link_file_ID' );
	$files_SQL->ORDER_BY( 'file_ID' );
	$files_SQL->GROUP_BY( 'file_ID' );
	// Limit the files by 100 recordsto save memory:
	$files_SQL->LIMIT( '0, 100' );

	$num_valid_files = 0;
	$num_missing_no_linked_files = 0;
	$num_missing_linked_files = 0;
	$num_total_links = 0;
	$num_total_files = 0;

	$skip_files = array();

	if( $delete_orphan_files && $delete_orphan_linked_files )
	{	// Initalize LinkCache once for code below:
		$LinkCache = & get_LinkCache();
	}

	while( $loaded_files = $DB->get_results( $files_SQL, ARRAY_A ) )
	{	// Check the found files:
		$FileCache->load_list( array_column( $loaded_files, 'file_ID' ) );
		foreach( $loaded_files as $file_data )
		{
			$File = & $FileCache->get_by_ID( $file_data['file_ID'], false, false );
			echo '<li>#'.$file_data['file_ID'].' - <code>'.$file_data['file_root_type'].'_'.$file_data['file_root_ID'].':'.$file_data['file_path'].'</code> - ';
			if( $File->exists() )
			{	// File exists on the disk:
				$num_valid_files++;
				echo '<span class="label label-success">'.T_('OK').'</span>';
				// Skip this File on next page searching:
				$skip_files[] = $File->ID;
			}
			else
			{	// File doesn't exist on the disk:
				echo '<span class="label '.( empty( $file_data['links_num'] ) ? 'label-warning' : 'label-danger' ).'">'.T_('MISSING').'</span>';
				echo ' - ('.sprintf( T_('linked %s times'), '<b>'.$file_data['links_num'].'</b>' ).')';
				if( empty( $file_data['links_num'] ) )
				{	// Count number of missing files without links:
					$num_missing_no_linked_files++;
				}
				else
				{	// Count number of missing files with links:
					$num_missing_linked_files++;
					$num_total_links += $file_data['links_num'];
				}
				$file_num_deleted_links = 0;
				if( $delete_orphan_files )
				{	// Try to delete File only if it is requested:
					echo ' - ';
					if( $delete_orphan_linked_files && ! empty( $file_data['link_IDs'] ) )
					{	// Try to delete ALL Link objects of the orphan File before deleting it:
						$deleted_link_IDs = explode( ',', $file_data['link_IDs'] );
						$LinkCache->load_list( $deleted_link_IDs );
						foreach( $deleted_link_IDs as $deleted_link_ID )
						{
							if( $deleted_Link = & $LinkCache->get_by_ID( $deleted_link_ID, false, false ) )
							{
								$deleted_Link->dbdelete();
								$file_num_deleted_links++;
							}
						}
					}
					if( $File->dbdelete() )
					{	// Success deleting:
						if( $file_num_deleted_links )
						{	// Inform about deleted with links:
							echo '<span class="text-danger">'.sprintf( T_('Deleted with %d links'), $file_num_deleted_links ).'.</span>';
						}
						else
						{	// Inform about deleted without links:
							echo '<span class="text-warning">'.T_('Deleted without links').'.</span>';
						}
					}
					else
					{	// Some restrictions, see File::get_delete_restrictions():
						echo '<span class="text-danger">'.T_('Could not be deleted!').'</span>';
						// Skip this File on next page searching:
						$skip_files[] = $File->ID;
					}
				}
				else
				{	// Skip this File on next page searching:
					$skip_files[] = $File->ID;
				}
			}
			$num_total_files++;
			echo '</li>';
			evo_flush();
		}

		// Clear cache after each page to save memory
		$FileCache->clear();

		if( ! empty( $skip_files ) )
		{	// Skip files which have been already processed:
			$files_SQL->WHERE( 'file_ID NOT IN ( '.implode( ',', $skip_files ).' )' );
		}
	}

	echo '</ul>';

	echo '<p>'.T_('Summary').':';
		echo '<ul>';
			echo '<li>'.sprintf( T_('%s valid Files'), '<b class="text-success">'.$num_valid_files.'</b>' ).'</li>';
			echo '<li>'.( $delete_orphan_files && $num_missing_no_linked_files ? '<b>'.T_('DELETED').': </b>' : '' ).sprintf( T_('%s missing File with no links'), '<b class="text-warning">'.$num_missing_no_linked_files.'</b>' ).'</li>';
			echo '<li>'.( $delete_orphan_linked_files && $num_missing_linked_files ? '<b>'.T_('DELETED').': </b>' : '' ).sprintf( T_('%s missing File with links (total number of Links: %s)'), '<b class="text-danger">'.$num_missing_linked_files.'</b>', '<b class="text-danger">'.$num_total_links.'</b>' ).'</li>';
			echo '<li>'.sprintf( T_('Total number of File objects: %s'), '<b>'.$num_total_files.'</b>' ).'</li>';
		echo '</ul>';
	echo '</p>';
}


/**
 * Remove orphan file roots ( with no matching Blog or User entry in the database ) recursively with all of the content
 */
function dbm_delete_orphan_file_roots()
{
	global $DB, $media_path;

	echo T_('Removing orphan file roots recursively with all of their content... ');
	evo_flush();

	// Store all directories that must be deleted
	$delete_dirs = array();

	/* BLOGS */

	// Get the media diretories of all existing blogs
	$BlogCache = & get_BlogCache();
	$BlogCache->load_all();
	$blog_dirs = array();
	foreach( $BlogCache->cache as $Blog )
	{
		$blog_dirs[] = $Blog->get_media_dir();
	}
	$BlogCache->clear();

	$media_path_blogs = $media_path.'blogs/';

	if( ( $media_dir_blogs = @opendir( $media_path_blogs ) ) === false )
	{ // Could not open blogs media dir
		echo '<p class="red">'.sprintf( T_('Cannot open blogs media directory %s'), '<b>'.$media_path_blogs.'</b>' ).'</p>';
	}
	else
	{
		// Find the blog dirs that must be deleted
		while( ( $folder = readdir( $media_dir_blogs ) ) !== false )
		{
			if( $folder == '.' || $folder == '..' || ! is_dir( $media_path_blogs.$folder ) )
			{ // Skip files
				continue;
			}
			if( ! in_array( $media_path_blogs.$folder.'/', $blog_dirs ) )
			{ // This dir must be deleted because it is not media dir of the existing blogs
				$delete_dirs[] = $media_path_blogs.$folder.'/';
			}
		}

		closedir( $media_dir_blogs );
	}

	/* USERS */
	echo '. ';
	evo_flush();

	// Get logins of all existing users
	$SQL = new SQL( 'Get logins of all existing users' );
	$SQL->SELECT( 'user_login' );
	$SQL->FROM( 'T_users' );
	$user_logins = $DB->get_col( $SQL );

	$media_path_users = $media_path.'users/';

	if( ( $media_dir_users = @opendir( $media_path_users ) ) === false )
	{ // Could not open users media dir
		echo '<p class="red">'.sprintf( T_('Cannot open users media directory %s'), '<b>'.$media_path_users.'</b>' ).'</p>';
	}
	else
	{
		// Find the user dirs that must be deleted
		while( ( $folder = readdir( $media_dir_users ) ) !== false )
		{
			if( $folder == '.' || $folder == '..' || ! is_dir( $media_path_users.$folder ) )
			{ // Skip files
				continue;
			}
			if( ! in_array( $folder, $user_logins ) )
			{ // This dir must be deleted because it is not media dir of the existing users
				$delete_dirs[] = $media_path_users.$folder.'/';
			}
		}

		closedir( $media_dir_users );
	}

	/* DELETE broken  file roots */
	echo '. ';
	evo_flush();

	foreach( $delete_dirs as $delete_dir )
	{
		if( rmdir_r( $delete_dir ) )
		{ // Success deleting
			echo '<p class="green">'.sprintf( T_('Invalid file root %s was found and removed with all of its content.'), '<b>'.$delete_dir.'</b>' ).'</p>';
		}
		else
		{ // Failed deleting
			echo '<p class="red">'.sprintf( T_('Cannot delete directory %s. Please check the permissions or delete it manually.'), '<b>'.$delete_dir.'</b>' ).'</p>';
		}
	}

	/* DELETE orphan DB file records of the blogs and the users */
	echo '. ';
	evo_flush();

	$count_files_deleted = $DB->query( 'DELETE f, l, lv FROM T_files AS f
			 LEFT JOIN T_links AS l ON l.link_file_ID = f.file_ID
			 LEFT JOIN T_links__vote AS lv ON l.link_ID = lv.lvot_link_ID
		WHERE ( file_root_type = "collection"
		        AND file_root_ID NOT IN ( SELECT blog_ID FROM T_blogs ) )
		   OR ( file_root_type = "user"
		        AND file_root_ID NOT IN ( SELECT user_ID FROM T_users ) )' );

	echo 'OK.<p>';
	echo sprintf( T_('%d File roots have been removed from the disk.'), count( $delete_dirs ) ).'<br />';
	echo sprintf( T_('%d File objects have been deleted from DB.'), intval( $count_files_deleted ) ).'</p>';
}


/**
 * Recreate all item slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but redirect to the new one.
 */
function dbm_recreate_itemslugs()
{
	global $Messages;

	$ItemCache = get_ItemCache();
	$ItemCache->load_where( '( post_title != "" ) AND ( post_urltitle = "title" OR post_urltitle LIKE "title-%" )');
	$items = $ItemCache->get_ID_array();
	$count_slugs = 0;

	foreach( $items as $item_ID )
	{
		$Item = $ItemCache->get_by_ID($item_ID);

		$prev_urltitle = $Item->get( 'urltitle' );
		$item_title = $Item->get( 'title' );

		// check if post title is not empty and urltitle was auto generated ( equals title or title-[0-9]+ )
		// Note: urltitle will be auto generated on this form (title-[0-9]+), if post title wass empty and, urltitle was not set
		// Note: Even if a post title was set to 'title' on purpose it's possible, that this tool will change the post urltitle
		if( ( ! empty( $item_title ) ) && ( ( $prev_urltitle == 'title' ) || ( preg_match( '#^title-[0-9]+$#', $prev_urltitle ) ) ) )
		{
			// set urltitle empty, so the item update function will regenerate the item slug
			$Item->set( 'urltitle', '' );
			$result = $Item->dbupdate(/* do not autotrack modification */ false, /* update slug */ true, /* do not update excerpt */ false);
			if( ( $result ) && ( $prev_urltitle != $Item->get( 'urltitle' ) ) )
			{ // update was successful, and item urltitle was changed
				$count_slugs++;
			}
		}
	}
	print_log( sprintf( 'Created %d new URL slugs.', $count_slugs ), 'success' );
}


/**
 * Recreate all autogenerated posts excerpts.
 */
function dbm_recreate_autogenerated_excerpts()
{
	global $DB;

	$continue_url = regenerate_url('action,crumb,remove_all_excerpts', 'action=recreate_autogenerated_excerpts&amp;remove_all_excerpts=0&amp;'.url_crumb('tools') );
	$remove_all_excerpts = param( 'remove_all_excerpts', 'boolean', 1 );

	// Display process status
	echo $remove_all_excerpts ? T_('Re-creating of autogenerated excerpts...') : T_('Continue re-creating of autogenerated excerpts...');
	evo_flush();

	recreate_autogenerated_excerpts( $continue_url, $remove_all_excerpts, true );
	$custom_excerpts = $DB->get_var( 'SELECT count(*) FROM T_items__item WHERE post_excerpt_autogenerated = 0' );
	echo '<br />'.sprintf( T_('All autogenerated excerpts were re-created ( %d custom excerpts were left untouched ).'), $custom_excerpts ).'<br />';
}


/**
 * Check DB tables
 *
 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
 * @param boolean TRUE - to make optimize query for each table separately
 * @return array Results of the mysql command 'CHECK'
 */
function dbm_check_tables( $display_messages = true, $separate_tables = true )
{
	global $DB, $tableprefix, $dbm_tables_count;

	load_class( '_core/model/_timer.class.php', 'Timer' );
	$Timer = new Timer('dbm_check');

	// Get all table names from DB
	$tables = $DB->get_results( 'SHOW TABLE STATUS FROM `'.$DB->dbname.'` LIKE \''.$tableprefix.'%\'');
	$tables_names = array();
	foreach( $tables as $table )
	{
		$tables_names[] = $table->Name;
	}
	$dbm_tables_count = count( $tables_names );

	if( $display_messages === 'cron_job' )
	{	// Log a message for cron job:
		cron_log_append( '<b>'.T_('Check tables...').'</b>' );
	}
	elseif( $display_messages )
	{	// Print out a message:
		echo '<b>'.T_('Check tables...').'</b><br />';
		evo_flush();
	}

	$Timer->start( 'check_tables' );
	if( $separate_tables )
	{ // Check each table in separate query
		$check_results = array();
		foreach( $tables_names as $table )
		{
			$Timer->start( 'check_one_table' );
			$table_results = $DB->get_results( 'CHECK TABLE '.$table.' FAST' );
			$Timer->stop( 'check_one_table' );
			if( $display_messages )
			{	// Display or log messages:
				dbm_display_result_messages( $table_results, 'check', $display_messages );
				$log_message = '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( 'check_one_table' ) ).'</b>';
				if( $display_messages === 'cron_job' )
				{	// Log a message for cron job:
					cron_log_append( $log_message."\n" );
				}
				else
				{	// Print out a message:
					echo $log_message.'<br /><br />';
				}
			}
			evo_flush();
			$check_results = array_merge( $check_results, $table_results );
		}
	}
	else
	{ // Check all table by one query, Used for cron job
		$check_results = $DB->get_results( 'CHECK TABLE '.implode( ', ', $tables_names ).' FAST' );
	}
	$Timer->stop( 'check_tables' );

	if( $display_messages )
	{	// Display or log messages:
		if( !$separate_tables )
		{	// Display full report log for case when the tables were checked by one query:
			dbm_display_result_messages( $check_results, 'check', $display_messages );
		}
		$log_message = '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( 'check_tables' ) ).'</b>';
		if( $display_messages === 'cron_job' )
		{	// Log a message for cron job:
			cron_log_append( $log_message."\n" );
		}
		else
		{	// Print out a message:
			echo $log_message.'<br />';
		}
	}

	return $check_results;
}


/**
 * Analyze DB tables
 *
 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
 * @param boolean TRUE - to make optimize query for each table separately
 * @return array Results of the mysql command 'ANALYZE'
 */
function dbm_analyze_tables( $display_messages = true, $separate_tables = true )
{
	global $DB, $tableprefix, $dbm_tables_count;

	load_class( '_core/model/_timer.class.php', 'Timer' );
	$Timer = new Timer('dbm_analyze');

	// Get all table names from DB
	$tables = $DB->get_results( 'SHOW TABLE STATUS FROM `'.$DB->dbname.'` LIKE \''.$tableprefix.'%\'');
	$tables_names = array();
	foreach( $tables as $table )
	{
		$tables_names[] = $table->Name;
	}
	$dbm_tables_count = count( $tables_names );

	if( $display_messages === 'cron_job' )
	{	// Log a message for cron job:
		cron_log_append( '<b>'.T_('Analyze tables...').'</b>' );
	}
	elseif( $display_messages )
	{	// Print out a message:
		echo '<b>'.T_('Analyze tables...').'</b><br />';
		evo_flush();
	}

	$Timer->start( 'analyze_tables' );
	if( $separate_tables )
	{ // Analyze each table in separate query
		$analyze_results = array();
		foreach( $tables_names as $table )
		{
			$Timer->start( 'analyze_one_table' );
			$table_results = $DB->get_results( 'ANALYZE NO_WRITE_TO_BINLOG TABLE '.$table );
			$Timer->stop( 'analyze_one_table' );
			if( $display_messages )
			{ // Display messages
				dbm_display_result_messages( $table_results, 'analyze', $display_messages );
				$log_message = '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( 'analyze_one_table' ) ).'</b>';
				if( $display_messages === 'cron_job' )
				{	// Log a message for cron job:
					cron_log_append( $log_message."\n" );
				}
				else
				{	// Print out a message:
					echo $log_message.'<br /><br />';
					evo_flush();
				}
			}
			$analyze_results = array_merge( $analyze_results, $table_results );
		}
	}
	else
	{ // Analyze all table by one query, Used for cron job
		$analyze_results = $DB->get_results( 'ANALYZE NO_WRITE_TO_BINLOG TABLE '.implode( ', ', $tables_names ) );
	}
	$Timer->stop( 'analyze_tables' );

	if( $display_messages )
	{ // Display messages
		if( !$separate_tables )
		{ // Display full report log for case when the tables were analyzed by one query
			dbm_display_result_messages( $analyze_results, 'analyze', $display_messages );
		}
		$log_message = '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( 'analyze_tables' ) ).'</b>';
		if( $display_messages === 'cron_job' )
		{	// Log a message for cron job:
			cron_log_append( $log_message."\n" );
		}
		else
		{	// Print out a message:
			echo $log_message;
			evo_flush();
		}
	}

	return $analyze_results;
}


/**
 * Update a progress information, display how many is done from all
 *
 * @param string the id of the html element which content must be replaced with the current values
 * @param integer done
 * @param integer all
 */
function echo_progress_log_update( $progress_log_id, $done, $all )
{
	echo '<span class="function_echo_progress_log_update">';
	?>
	<script>
		jQuery('.function_echo_progress_log_update').remove();
		jQuery( '#' + '<?php echo $progress_log_id; ?>' ).html("<?php echo ' '.$done.' / '.$all ?>");
	</script>
	<?php
	echo '</span>';
}


/**
 * Convert item content separators to new format
 */
function dbm_convert_item_content_separators()
{
	global $DB;

	// Display process status
	// TRANS: "more", "teaserbreak", "nextpage" and "pagebreak" must NOT be translated.
	echo T_( 'Convert item content separators from &lt;!--more--&gt; to [teaserbreak] and &lt;!--nextpage--&gt; to [pagebreak]...' );
	evo_flush();

	$DB->query( 'UPDATE T_items__item
		SET post_content = REPLACE( REPLACE( REPLACE( REPLACE( post_content,
			"&lt;!--more--&gt;",     "[teaserbreak]" ),
			"<!--more-->",           "[teaserbreak]" ),
			"&lt;!--nextpage--&gt;", "[pagebreak]" ),
			"<!--nextpage-->",       "[pagebreak]" )' );

	/* test code to return to old separators to see how it works with old separators
	$DB->query( 'UPDATE T_items__item
		SET post_content = REPLACE( REPLACE( post_content,
			"[teaserbreak]", "<!--more-->" ),
			"[pagebreak]",   "<!--nextpage-->" )' );
	*/

	$item_updated_count = intval( $DB->rows_affected );

	if( $item_updated_count > 0 )
	{ // Some separators were updated
		echo ' '.sprintf( T_('%d items have been updated.'), $item_updated_count );

		// To see the changes we should update the pre-rendered item contents
		echo '<br />'.T_( 'Clear pre-rendered item cache (DB)' ).'...';
		dbm_delete_itemprecache();
		echo ' OK.';
	}
	else
	{ // No old separators in DB
		echo ' '.T_('No old separators were found.');
	}

	echo "<br />\n";
}

function dbm_delete_item_versions()
{
	global $DB;

	$count = $DB->get_var( 'SELECT COUNT(*) FROM T_items__version' );
	$DB->query( 'TRUNCATE TABLE T_items__version' );
	if( $count > 0 )
	{
		print_log( sprintf( T_('Cleared %d records from the item versions table.'), $count ), 'success' );
	}
	else
	{
		print_log( T_('Item versions table already empty.'), 'note' );
	}
}

?>