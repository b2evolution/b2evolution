<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs('plugins/_plugin.funcs.php');

// load item class
load_class( 'items/model/_item.class.php', 'Item' );

param( 'tab', 'string', '', true );

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
$AdminUI->set_path( 'tools', $tab );


if( empty($tab) )
{	// "Main tab" actions:
	if( param( 'action', 'string', '' ) )
	{
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tools' );
	
		// fp> TODO: have an option to only PRUNE files older than for example 30 days
		$current_User->check_perm('options', 'edit', true);
	}

	switch( $action )
	{
		case 'del_itemprecache':
			$DB->query('DELETE FROM T_items__prerendering WHERE 1=1');

			$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
			break;

		case 'del_pagecache':
			// Delete the page cache /blogs/cache
			global $cache_path;

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
				}
			}

			$Messages->add( T_('Page cache deleted.'), 'success' );
			break;

		case 'del_filecache':
			// delete the thumbnail cahces .evocache
			// TODO> handle custom media directories dh> ??
			// Delete any ?evocache folders:
			$deleted_dirs = delete_cachefolders($Messages);
			$Messages->add( sprintf( T_('Deleted %d directories.'), $deleted_dirs ), 'success' );
			break;

		case 'repair_cache':
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
			break;

		case 'optimize_tables':
			// Optimize MyISAM tables
			global $tableprefix;

			$db_optimized = false;
			$tables = $DB->get_results( 'SHOW TABLE STATUS FROM `'.$DB->dbname.'` LIKE \''.$tableprefix.'%\'');

			foreach( $tables as $table )
			{
				// Before MySQL 4.1.2, the "Engine" field was labeled as "Type".
				if( ( ( isset( $table->Engine ) && $table->Engine == 'MyISAM' )
					  || ( isset( $table->Type ) && $table->Type == 'MyISAM' ) )
					&& $table->Data_free )
				{	// Optimization needed
					if( !$DB->query( 'OPTIMIZE TABLE '.$table->Name ) )
					{
						$Messages->add( sprintf( T_('Database table %s could not be optimized.'), '<b>'.$table->Name.'</b>' ), 'note' );
					}
					else
					{
						$db_optimized = true;
						$Messages->add( sprintf( T_('Database table %s optimized.'), '<b>'.$table->Name.'</b>' ), 'success' );
					}
				}
			}

			if( !$db_optimized )
			{
				$Messages->add( T_('Database tables are already optimized.'), 'success' );
			}
			break;

		case 'find_broken_posts':
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
			break;

		case 'find_broken_slugs':
			// delete broken slugs
			$r = $DB->query( 'DELETE FROM T_slug
								WHERE slug_type = "item" and slug_itm_ID NOT IN (
									SELECT post_ID FROM T_items__item )' );

			if( $r !== false )
			{
				$Messages->add( sprintf( T_('Deleted %d slugs.'), $r ), 'success' );
			}
			break;

		case 'delete_orphan_comment_uploads':
			// delete orphan comment upload, older than 24 hours
			$count = remove_orphan_files( NULL, 24 );

			$Messages->add( sprintf( T_('%d files have been deleted'), $count ), 'success' );
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

			$curr_orderby = $selected_Blog->get_setting('orderby');
			if( $curr_orderby == 'RAND' )
			{
				$curr_orderby .= '()';
			}
			else
			{
				$curr_orderby = 'post_'.$curr_orderby;
			}
			$curr_orderdir = $selected_Blog->get_setting('orderdir');

			// find the $num_posts latest posts in blog
			$sql = 'SELECT post_ID 
						FROM T_items__item INNER JOIN T_categories ON post_main_cat_ID = cat_ID
					 WHERE cat_blog_ID = '.$blog_ID.' AND post_status = '.$DB->quote( 'published' ).'
					 ORDER BY '.$curr_orderby.' '.$curr_orderdir.', post_ID '.$curr_orderdir.'
					 LIMIT '.$num_posts;
			$items_result = $DB->get_results( $sql, ARRAY_A, 'Find the x latest posts in blog' );

			$count = 1;
			$fix_content = 'This is an auto generated comment for testing the moderation features.
							http://www.test.com/test_comment_';
			// go through on selected items
			foreach( $items_result as $row )
			{
				$item_ID = $row['post_ID'];
				// create $num_comments comments for each item
				for( $i = 0; $i < $num_comments; $i++ )
				{
					$author = 'Test '.$count;
					$email = 'test_'.$count.'@test.com';
					$url = 'http://www.test.com/test_comment_'.$count;

					$content = $fix_content.$count;
					for( $j = 0; $j < 50; $j++ )
					{ // create 50 random word
						$length = rand(1, 15);
						$word = generate_random_key( $length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
						$content = $content.' '.$word;
					}

					// create and save a new comment
					$Comment = new Comment();
					$Comment->set( 'post_ID', $item_ID );
					$Comment->set( 'status', 'draft' );
					$Comment->set( 'author', $author );
					$Comment->set( 'author_email', $email );
					$Comment->set( 'author_url', $url );
					$Comment->set( 'content', $content );
					$Comment->set( 'date', date( 'Y-m-d H:i:s', $localtimenow ) );
					$Comment->set( 'author_IP', $Hit->IP );
					$Comment->dbsave();
					$count++;
				}
			}

			$Messages->add( sprintf( T_('Created %d comments.'), $count - 1 ), 'success' );
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
				$Item->set( 'dateset', '1' );
				// set post main cat ID, from selected blog
				$Item->set( 'main_cat_ID', $selected_Blog->get_default_cat_ID() );
				$Item->set( 'datestart', date( 'Y-m-d H:i:s', $time ) );
				$Item->set( 'datecreated', $time );
				$Item->dbinsert();
			}
			$Messages->add( sprintf( T_('Created %d posts.'), $num_posts ), 'success' );
			break;

		case 'recreate_itemslugs':
			$ItemCache = get_ItemCache();
			$ItemCache->load_where( '( post_title != "" ) AND ( post_urltitle = "title" OR post_urltitle LIKE "title-%" )');
			$items = $ItemCache->get_ID_array();
			$count_slugs = 0;

			set_max_execution_time(0);

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
				param_error( 'days', T_('Please enter how many days of stats to generate') );
				$action = 'show_create_test_hit';
				break;
			}

			if (($min_interval > $max_interval) || ($min_interval < 0) || ($max_interval <= 0))
			{
				param_error( 'min_interval', T_('Please enter correct interval values') );
				param_error( 'max_interval', T_('Please enter correct interval values') );
				$action = 'show_create_test_hit';
				break;
			}
			

			load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );

			$links = array();

			$BlogCache = & get_BlogCache();

			$blogs_id = $BlogCache->load_public();

			foreach ($blogs_id as $blog_id)
			{	// handle all public blogs
				$listBlog = & $BlogCache->get_by_ID($blog_id);
				if( empty($listBlog) )
				{
					continue;
				}

				$ItemList = new ItemListLight($listBlog);
				$filters = array();

				# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
				# Example: $linkblog_cat = '4,6,7';
				$linkblog_cat = '';

				# This is the array if categories to restrict the linkblog to (non recursive)
				# Example: $linkblog_catsel = array( 4, 6, 7 );
				$linkblog_catsel = array(); // $cat_array;

				// Compile cat array stuff:
				$linkblog_cat_array = array();
				$linkblog_cat_modifier = '';

				compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */  $linkblog_cat_modifier, $listBlog->ID );

				$filters['cat_array'] = $linkblog_cat_array;
				$filters['cat_modifier'] = $linkblog_cat_modifier;
				

				$ItemList->set_default_filters($filters);

				// Get the items list of current blog
				$ItemList->query();

				if( ! $ItemList->result_num_rows )
				{	// Nothing to display:
					continue;
				}

				while( $Item = & $ItemList->get_category_group() )
				{
					// Open new cat:
					$Chapter = & $Item->get_main_Chapter();
					while( $Item = & $ItemList->get_item() )
					{	
						$links[] =  array('link' => '/'.$listBlog->siteurl.'/'.$Chapter->get_url_path().$Item->urltitle, // trim($Chapter->get_permanent_url(NULL ,' ')).
										  'blog_id'   => $blog_id);
					}

				}

				// add search links for all blogs

				$links[] =  array('link' => '/'.$listBlog->siteurl.'?s=$keywords$&disp=search&submit=Search',
								  'blog_id'   => $blog_id);


			}

			$links_count = count($links);

			if (empty($links_count))
			{
				$Messages->add('Do not have blog links to generate statistics');
				break;
			}

			// generate users id array

			$users_array = $DB->get_results( '
					SELECT user_ID
					  FROM T_users
					  WHERE user_validated = 1
					  LIMIT 10'
					, 'ARRAY_A');

			$users_count = count($users_array);

			if (empty ($users_count))
			{
				$Messages->add('Do not have valid users to generate statistics');
				break;
			}

			// Calculate the period of testing
			$cur_time = time();
			$past_time = mktime(date("H"),date("i"),date("s") ,date("m"),date("d")-$days,date("Y"));

			$insert_data ='';
			$insert_data_count = 0;

			// create session array for testing
			$sessions = array();
			for ($i = 0; $i <= $users_count - 1; $i++)
			{
				$sessions[] = array('sess_ID'		=> -1,
									'sess_key'      => generate_random_key(32),
									'sess_hitcount' => 1,
									'sess_lastseen' => 0,
									'sess_ipaddress'=> generate_random_ip(),
									'sess_user_ID'  => $users_array[$i]['user_ID']);
			}

			// main cycle of generation
			for ($time_shift = $past_time; $cur_time > $time_shift; $time_shift += mt_rand($min_interval, $max_interval))
			{

				$insert_data_count = $insert_data_count + 1;

				$rand_i = mt_rand(0,$users_count-1);
				$rand_link = mt_rand(0,$links_count-1);
				$cur_seesion =  $sessions[$rand_i];

				$keyp_ID = 'NULL';

				if (strstr($links[$rand_link]['link'],'$keywords$'))
				{	// check if the current search link is selected randomly.
					// If yes, generate search link and add it to DB
						$keywords =  'fake search '. mt_rand(0,9);

						$sql = 'SELECT keyp_ID
								  FROM T_track__keyphrase
								 WHERE keyp_phrase = '.$DB->quote($keywords);
						$keyp_ID = $DB->get_var( $sql, 0, 0, 'Get keyphrase ID' );

						if( empty( $keyp_ID ) )
						{
							$sql = 'INSERT INTO T_track__keyphrase( keyp_phrase )
								VALUES ('.$DB->quote($keywords).')';
							$DB->query( $sql, 'Add new keyphrase' );
							$keyp_ID = $DB->insert_id;
						}

						$links[$rand_link]['link'] = str_replace('$keywords$', urlencode($keywords), $links[$rand_link]['link']);

				}


				if ($cur_seesion['sess_ID'] == -1)
				{	// This session needs initialization:

					$cur_seesion['sess_lastseen'] = $time_shift;

					$DB->query( "
					INSERT INTO T_sessions( sess_key, sess_hitcount, sess_lastseen, sess_ipaddress, sess_user_ID )
					VALUES (
						'".$cur_seesion['sess_key']."',
						".$cur_seesion['sess_hitcount'].",
						'".date( 'Y-m-d H:i:s', $cur_seesion['sess_lastseen'] )."',
						".$DB->quote($cur_seesion['sess_ipaddress']).",
						".$cur_seesion['sess_user_ID']."
					)" );

					$cur_seesion['sess_ID'] = $DB->insert_id;
					$sessions[$rand_i] = $cur_seesion;

					$sql = "INSERT INTO T_hitlog(hit_sess_ID, hit_datetime, hit_uri, hit_referer_dom_ID, hit_referer_type, hit_blog_ID, hit_remote_addr, hit_agent_type , hit_keyphrase_keyp_ID)
						VALUES".
						"({$cur_seesion['sess_ID']}, FROM_UNIXTIME({$cur_seesion['sess_lastseen']}), '". $DB->escape($links[$rand_link]['link']). "' , 1, 'direct', {$links[$rand_link]['blog_id']}, '{$cur_seesion['sess_ipaddress']}' , 'browser', $keyp_ID)";

					$DB->query( $sql, 'Record test hits' );

					//break(2);
				}
				else
				{
					if (($time_shift - $cur_seesion['sess_lastseen'])> 1000)
					{	// This session last updated more than 1000 sec ago. Instead of this session create a new session.

						$cur_seesion = array(	'sess_ID'		=> -1,
												'sess_key'      => generate_random_key(32),
												'sess_hitcount' => 1,
												'sess_lastseen' => 0,
												'sess_ipaddress'=> generate_random_ip(),
												'sess_user_ID'  => $users_array[mt_rand(0,$users_count-1)]['user_ID']);

						$cur_seesion['sess_lastseen'] = $time_shift;

						if (mt_rand(0, 100) > 30)
						{	// Create anonymous user and make double insert into hits.
							$cur_seesion['sess_user_ID'] = -1;
							$DB->query( "
							INSERT INTO T_sessions( sess_key, sess_hitcount, sess_lastseen, sess_ipaddress)
							VALUES (
								'".$cur_seesion['sess_key']."',
								".$cur_seesion['sess_hitcount'].",
								'".date( 'Y-m-d H:i:s', $cur_seesion['sess_lastseen'] )."',
								".$DB->quote($cur_seesion['sess_ipaddress'])."
							)" );
						}

						else
						{
						$DB->query( "
							INSERT INTO T_sessions( sess_key, sess_hitcount, sess_lastseen, sess_ipaddress, sess_user_ID )
							VALUES (
								'".$cur_seesion['sess_key']."',
								".$cur_seesion['sess_hitcount'].",
								'".date( 'Y-m-d H:i:s', $cur_seesion['sess_lastseen'] )."',
								".$DB->quote($cur_seesion['sess_ipaddress']).",
								".$cur_seesion['sess_user_ID']."
							)" );
						}

						$cur_seesion['sess_ID'] = $DB->insert_id;
						$sessions[$rand_i] = $cur_seesion;

						if ($cur_seesion['sess_user_ID'] == -1)
						{
							$sql = "INSERT INTO T_hitlog(hit_sess_ID, hit_datetime, hit_uri, hit_referer_dom_ID, hit_referer_type, hit_blog_ID, hit_remote_addr, hit_agent_type )
							VALUES".
								"({$cur_seesion['sess_ID']}, FROM_UNIXTIME({$cur_seesion['sess_lastseen']}), '". $DB->escape('/htsrv/login.php'). "' , 1, 'direct', NULL, '{$cur_seesion['sess_ipaddress']}' , 'browser'),".
								"({$cur_seesion['sess_ID']}, FROM_UNIXTIME({$cur_seesion['sess_lastseen']} + 3), '". $DB->escape('/htsrv/login.php?redirect_to=fake_stat'). "' , 1, 'self', NULL, '{$cur_seesion['sess_ipaddress']}' , 'browser')";

						}
						else
						{
							$sql = "INSERT INTO T_hitlog(hit_sess_ID, hit_datetime, hit_uri, hit_referer_dom_ID, hit_referer_type, hit_blog_ID, hit_remote_addr, hit_agent_type, hit_keyphrase_keyp_ID  )
							VALUES".
								"({$cur_seesion['sess_ID']}, FROM_UNIXTIME({$cur_seesion['sess_lastseen']}), '". $DB->escape($links[$rand_link]['link']). "' , 1, 'direct', {$links[$rand_link]['blog_id']}, '{$cur_seesion['sess_ipaddress']}' , 'browser', $keyp_ID)";
						}

						$DB->query( $sql, 'Record test hits' );

					}
					else
					{
						// Update session 
						$cur_seesion['sess_lastseen'] = $time_shift;
						$sql = "INSERT INTO T_hitlog(hit_sess_ID, hit_datetime, hit_uri, hit_referer_dom_ID, hit_referer_type, hit_blog_ID, hit_remote_addr, hit_agent_type, hit_keyphrase_keyp_ID )
						VALUES".
							"({$cur_seesion['sess_ID']}, FROM_UNIXTIME({$cur_seesion['sess_lastseen']}), '". $DB->escape($links[$rand_link]['link']). "' , 1, 'self', {$links[$rand_link]['blog_id']}, '{$cur_seesion['sess_ipaddress']}' , 'browser', $keyp_ID)";

						$DB->query( $sql, 'Record test hits' );

						$sql = "UPDATE T_sessions SET
								sess_hitcount = sess_hitcount + 1,
								sess_lastseen = '".date( 'Y-m-d H:i:s', $cur_seesion['sess_lastseen'] )."'
								WHERE sess_ID = {$cur_seesion['sess_ID']}";

						$DB->query( $sql, 'Update session' );
						$sessions[$rand_i] = $cur_seesion;

					}
				}

			}


			$Messages->add( sprintf( '%d test data items are added.', $insert_data_count ), 'success' );
			break;



	}
}
$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=crontab' );
$AdminUI->breadcrumbpath_add( T_('Miscellaneous'), '?ctrl=tools' );


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

		case 'show_create_test_hit':
			$AdminUI->disp_view( 'tools/views/_create_test_hit.form.php' );
			break;


		default:
			$AdminUI->disp_view( 'tools/views/_misc_tools.view.php' );
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
		.' '.$tab_Plugin->get_help_link('$help_url')
		.' '.$tab_Plugin->get_help_link('$readme');
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
 * Revision 1.49  2011/09/29 12:35:58  efy-vitalij
 * add anonymous users and fake search links
 *
 * Revision 1.48  2011/09/29 06:22:36  efy-vitalij
 * add config params to statistic generator form
 *
 * Revision 1.47  2011/09/28 09:05:58  efy-vitalij
 * add session functional to  statistical data generator
 *
 * Revision 1.46  2011/09/27 13:11:29  efy-vitalij
 * generate sample hit data
 *
 * Revision 1.45  2011/09/26 15:38:08  efy-vitalij
 * add test hit information
 *
 * Revision 1.44  2011/09/05 14:17:26  sam2kb
 * Refactor antispam controller
 *
 * Revision 1.43  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.42  2011/09/04 21:32:17  fplanque
 * minor MFB 4-1
 *
 * Revision 1.41  2011/06/15 07:38:13  sam2kb
 * Refactor spam comments and hits removal actions
 *
 * Revision 1.40  2011/06/14 06:05:18  sam2kb
 * Check and remove all comments and hits mathing antispam blacklist
 *
 * Revision 1.39  2011/03/15 09:34:06  efy-asimo
 * have checkboxes for enabling caching in new blogs
 * refactorize cache create/enable/disable
 *
 * Revision 1.38  2011/03/03 12:50:57  efy-asimo
 * tool to find and delete orphan comment attachment files
 *
 * Revision 1.37  2010/12/08 13:55:37  efy-asimo
 * Create Sample comments - fix
 *
 * Revision 1.36  2010/12/06 14:27:57  efy-asimo
 * Generate sample posts tool
 *
 * Revision 1.35  2010/11/12 15:13:31  efy-asimo
 * MFB:
 * Tool 1: "Find all broken posts that have no matching category"
 * Tool 2: "Find all broken slugs that have no matching target post"
 * Tool 3: "Create sample comments for testing moderation"
 *
 * Revision 1.34  2010/11/04 03:16:10  sam2kb
 * Display PHP info in a pop-up window
 *
 * Revision 1.33  2010/07/28 07:58:53  efy-asimo
 * Add where condition to recreate slugs tool query
 *
 * Revision 1.32  2010/07/26 07:24:27  efy-asimo
 * Tools recreate item slugs (change description + fix notice)
 *
 * Revision 1.31  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.30  2010/06/15 21:33:24  blueyed
 * Fix patch failure.
 *
 * Revision 1.29  2010/06/15 21:20:37  blueyed
 * Add tools action to remove obsolete/unused tags.
 *
 * Revision 1.28  2010/05/24 21:27:58  sam2kb
 * Fixed some translated strings
 *
 * Revision 1.27  2010/05/02 00:15:07  blueyed
 * cleanup
 *
 * Revision 1.26  2010/05/02 00:14:07  blueyed
 * Add recreate_itemslugs tool to re-generate slugs for all items.
 *
 * Revision 1.25  2010/03/27 19:57:30  blueyed
 * Add delete_cachefolders function and use it in the Tools Misc actions and with the watermark plugin. The latter will also remove caches when it gets enabled or disabled.
 *
 * Revision 1.24  2010/03/12 10:52:56  efy-asimo
 * Set EvoCache  folder names - task
 *
 * Revision 1.23  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.22  2010/01/14 21:30:31  blueyed
 * Make deleting .evocache folders far less verbose.
 *
 * Revision 1.21  2010/01/03 18:07:37  fplanque
 * crumbs
 *
 * Revision 1.20  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.19  2009/11/25 19:53:58  blueyed
 * Fix 'Optimize database tables' SQL: quote DB name.
 *
 * Revision 1.18  2009/11/25 00:54:26  blueyed
 * todo
 *
 * Revision 1.17  2009/11/12 03:54:17  fplanque
 * wording/doc/cleanup
 *
 * Revision 1.16  2009/10/16 18:41:47  tblue246
 * Cleanup/doc
 *
 * Revision 1.15  2009/10/02 14:17:34  tblue246
 * minor/doc
 *
 * Revision 1.13  2009/10/02 13:28:03  sam2kb
 * Backup b2evo database from Tools > Misc
 *
 * Revision 1.12  2009/10/01 16:19:14  sam2kb
 * minor
 *
 * Revision 1.11  2009/10/01 14:58:44  sam2kb
 * Delete page and thumbnails cache
 *
 * Revision 1.10  2009/10/01 13:06:03  tblue246
 * Fix for backward compatibility with MySQL versions lower than 4.1.2.
 *
 * Revision 1.9  2009/10/01 12:57:18  tblue246
 * Tools -> Optimize DB: Drop substr() check for table prefix and modify the SQL query to only return appropriate tables instead.
 *
 * Revision 1.8  2009/09/30 19:48:38  tblue246
 * Tools -> Optimize tables: Do not use preg_match() to check table prefix but a simple substr().
 *
 * Revision 1.7  2009/09/30 18:00:19  sam2kb
 * Optimize b2evo tables from Tools > Misc
 *
 * Revision 1.6  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.5  2008/07/11 23:10:01  blueyed
 * s/insctructions/instructions/g
 *
 * Revision 1.4  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/10/09 01:18:12  fplanque
 * Hari's WordPress importer
 *
 * Revision 1.2  2007/09/04 14:57:07  fplanque
 * interface cleanup
 *
 * Revision 1.1  2007/06/25 11:01:42  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
