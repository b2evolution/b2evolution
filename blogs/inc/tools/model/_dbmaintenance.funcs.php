<?php
/**
 * This file implements functions to work with DB maintenance.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Clear pre-renderered item cache (DB)
 */
function dbm_delete_itemprecache()
{
	global $DB, $Messages;

	$DB->query('DELETE FROM T_items__prerendering WHERE 1=1');

	$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
}


/**
 * Clear full page cache (/cache directory)
 */
function dbm_delete_pagecache()
{
	global $DB, $Messages, $cache_path;

	// Clear general cache directory
	if( cleardir_r( $cache_path.'general' ) )
	{
		$Messages->add( sprintf( T_('General cache deleted: %s'), $cache_path.'general' ), 'note' );
	}
	else
	{
		$Messages->add( sprintf( T_('Could not delete general cache: %s'), $cache_path.'general' ), 'error' );
	}

	$SQL = 'SELECT blog_ID FROM T_blogs
			INNER JOIN T_coll_settings ON ( blog_ID = cset_coll_ID
						AND cset_name = "cache_enabled"
						AND cset_value = "1" )
			WHERE 1=1';

	if( $blog_array = $DB->get_col( $SQL ) )
	{
		foreach( $blog_array as $l_blog )
		{	// Clear blog cache
			if( cleardir_r( $cache_path.'c'.$l_blog ) )
			{
				$Messages->add( sprintf( T_('Blog %d cache deleted: %s'), $l_blog, $cache_path.'c'.$l_blog ), 'note' );
			}
			else
			{
				$Messages->add( sprintf( T_('Could not delete blog %d cache: %s'), $l_blog, $cache_path.'c'.$l_blog ), 'error' );
			}
			// Create .htaccess file with deny rules
			create_htaccess_deny( $cache_path.'c'.$l_blog.'/' );
		}
	}

	$Messages->add( T_('Page cache deleted.'), 'success' );
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

	$Messages->add( sprintf( T_('Deleted %d directories.'), $deleted_dirs ), 'success' );
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
		$Messages->add( T_( 'All cache folders are working properly.' ), 'success' );
	}
	else
	{
		$error_message = T_( 'Unable to repair all cache folders becaue of file permissions' ).':<br />';
		$Messages->add( $error_message.implode( '<br />', $result ) );
	}
}


/**
 * Optimize DB tables (MyISAM & InnoDB)
 *
 * @param boolean Display messages
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
 * @param boolean Display messages
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
	{ // Display messages
		echo '<b>'.sprintf( T_('Optimize %s tables...'), $table_type ).'</b><br />';
		flush();
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
				{ // Display messages
					dbm_display_result_messages( $table_results, 'optimize' );
					echo '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( $timer_name.'_table' ) ).'</b><br /><br />';
				}
				flush();
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
	{ // Display messages
		if( !$separate_tables || empty( $tables ) )
		{ // Display full report log for case when the tables were optimized by one query
			dbm_display_result_messages( $results, 'optimize' );
		}
		echo '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( $timer_name ) ).'</b><br /><br />';
		flush();
	}

	return $results;
}


/**
 * Print on screen the messages of 'OPTIMIZE', 'CHECK' OR 'ANALYZE' commands
 *
 * @param array Results of the mysql commands 'OPTIMIZE', 'CHECK' OR 'ANALYZE'
 * @param string mysql command type: optimize, check, analyze
 */
function dbm_display_result_messages( $results, $command_type )
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
			if( $result->Msg_type == 'status' && $result->Msg_text == 'OK' )
			{ // OK
				echo sprintf( $params['message_ok'], '<b>'.$result->Table.'</b>' ).'<br />';
			}
			elseif( $result->Msg_type == 'note' && $result->Msg_text == 'Table does not support optimize, doing recreate + analyze instead' )
			{ // This warning is comming for every innodb table, but that is normal, Display for info
				echo sprintf( T_('Database table %s does not support optimize, doing recreate + analyze instead'), '<b>'.$result->Table.'</b>' ).'<br />';
			}
			else
			{ // Some errors
				$message_class = $result->Msg_type == 'status' ? 'orange' : 'red';
				echo '<span class="'.$message_class.'">'.sprintf( $params['message_error'], '<b>'.$result->Table.'</b>', '"'.$result->Msg_text.'"' ).'</span><br />';
			}
		}
	}
	else
	{ // No tables found to optimize, probably all tables already were optimized
		echo $params['message_done'].'<br />';
	}
}


/**
 * Find all broken posts that have no matching category
 */
function dbm_find_broken_posts()
{
	global $DB, $Messages;

	// select broken items
	$sql = 'SELECT * FROM T_items__item
				WHERE post_canonical_slug_ID NOT IN (
					SELECT slug_ID FROM T_slug )';
	$broken_items = $DB->get_results( $sql, OBJECT, 'Find broken posts' );

	$num_deleted = 0;
	foreach( $broken_items as $row )
	{ // delete broken items
		$broken_Item = new Item( $row );
		if( $broken_Item->dbdelete() )
		{
			$num_deleted++;
		}
	}

	$Messages->add( sprintf( T_('Deleted %d posts.'), $num_deleted ), 'success' );
}


/**
 * Find all broken slugs that have no matching target post
 */
function dbm_find_broken_slugs()
{
	global $DB, $Messages;

	// delete broken slugs
	$r = $DB->query( 'DELETE FROM T_slug
						WHERE slug_type = "item" and slug_itm_ID NOT IN (
							SELECT post_ID FROM T_items__item )' );

	if( $r !== false )
	{
		$Messages->add( sprintf( T_('Deleted %d slugs.'), $r ), 'success' );
	}
}


/**
 * Find and delete orphan comment uploads
 */
function dbm_delete_orphan_comment_uploads()
{
	global $Messages;

	$count = remove_orphan_files( NULL, 24 );

	$Messages->add( sprintf( T_('%d files have been deleted'), $count ), 'success' );
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
	$Messages->add( sprintf( 'Created %d new URL slugs.', $count_slugs ), 'success' );
}


/**
 * Check DB tables
 *
 * @param boolean Display messages
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

	if( $display_messages )
	{ // Display messages
		echo '<b>'.T_('Check tables...').'</b><br />';
		flush();
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
			{ // Display messages
				dbm_display_result_messages( $table_results, 'check' );
				echo '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( 'check_one_table' ) ).'</b><br /><br />';
			}
			flush();
			$check_results = array_merge( $check_results, $table_results );
		}
	}
	else
	{ // Check all table by one query, Used for cron job
		$check_results = $DB->get_results( 'CHECK TABLE '.implode( ', ', $tables_names ).' FAST' );
	}
	$Timer->stop( 'check_tables' );

	if( $display_messages )
	{ // Display messages
		if( !$separate_tables )
		{ // Display full report log for case when the tables were checked by one query
			dbm_display_result_messages( $check_results, 'check' );
		}
		echo '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( 'check_tables' ) ).'</b><br />';
	}

	return $check_results;
}


/**
 * Analyze DB tables
 *
 * @param boolean Display messages
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

	if( $display_messages )
	{ // Display messages
		echo '<b>'.T_('Analyze tables...').'</b><br />';
		flush();
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
				dbm_display_result_messages( $table_results, 'analyze' );
				echo '<b>'.sprintf( T_('Time: %s seconds'), $Timer->get_duration( 'analyze_one_table' ) ).'</b><br /><br />';
			}
			flush();
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
			dbm_display_result_messages( $analyze_results, 'analyze' );
		}
		echo '<b>'.sprintf( T_('Full execution time: %s seconds'), $Timer->get_duration( 'analyze_tables' ) ).'</b>';
	}

	return $analyze_results;
}
?>