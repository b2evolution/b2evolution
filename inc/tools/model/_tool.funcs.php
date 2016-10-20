<?php
/**
 * This file implements functions to work with tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Create sample collections and display a process of creating
 *
 * @param integer Number of collections
 * @param array Kind of setting "Permission management": 'simple', 'advanced'
 * @param array Kind of setting "Allow access to": 'public', 'users', 'members'
 */
function tool_create_sample_collections( $num_collections, $perm_management, $allow_access )
{
	global $DB, $Debuglog, $Plugins, $Messages;

	echo T_('Creating of the sample collections...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	$count = 1;
	$perm_management_max_index = count( $perm_management ) - 1;
	$allow_access_max_index = count( $allow_access ) - 1;
	for( $i = 0; $i < $num_collections; $i++ )
	{
		// Create and save a new collection:
		$new_Blog = new Blog( NULL );
		$shortname = generate_random_passwd();
		$new_Blog->set( 'owner_user_ID', 1 );
		$new_Blog->set( 'shortname', $shortname );
		$new_Blog->set( 'name', $shortname );
		$new_Blog->set( 'urlname', urltitle_validate( strtolower( $shortname ), $shortname, $new_Blog->ID, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
		// Set random of "Permission management" from the selected options:
		$new_Blog->set( 'advanced_perms', $perm_management[ rand( 0, $perm_management_max_index ) ] == 'advanced' ? 1 : 0 );
		// Set random of "Allow access to" from the selected options:
		$new_Blog->set_setting( 'allow_access', $allow_access[ rand( 0, $allow_access_max_index ) ] );;

		// Define collection settings by its kind:
		$Plugins->trigger_event( 'InitCollectionKinds', array(
				'Blog' => & $new_Blog,
				'kind' => 'std',
			) );

		// Do a creating new collection:
		$new_Blog->create();

		// Don't show a sample collection on top menu in back-office:
		// TODO: In another branch Erwin has implemented a rule similar to "only enable first 10 collections". This will be merged here at some point.
		$new_Blog->favorite( NULL, 0 );

		// Clear the messages because we have at least 4 messages after each $new_Blog->create() which are the same:
		$Messages->clear();

		$count++;

		if( $count % 100 == 0 )
		{	// Display a process of creating by one dot for 100 comments:
			echo ' .';
			evo_flush();
		}

		// Clear all debug messages, To avoid an error about full memory:
		$Debuglog->clear( 'all' );
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d collections.'), $count - 1 ), 'success' );
}


/**
 * Create sample comments and display a process of creating
 *
 * @param integer Blog ID
 * @param integer Number of comments
 * @param integer Number of posts
 */
function tool_create_sample_comments( $blog_ID, $num_comments, $num_posts )
{
	global $DB, $localtimenow, $Hit, $Messages, $Debuglog;

	$BlogCache = & get_BlogCache();
	$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
	if( $selected_Blog == NULL )
	{ // Incorrect blog ID, Exit here
		return;
	}

	echo T_('Creating of the sample comments...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

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
	$SQL = new SQL();
	$SQL->SELECT( 'post_ID' );
	$SQL->FROM( 'T_items__item' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
	$SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog_ID ) );
	$SQL->WHERE_and( 'post_status = '.$DB->quote( 'published' ) );
	// Set condition to not create sample comments for special posts
	$SQL->WHERE_and( 'post_ityp_ID IS NULL OR ityp_usage = "post"' );
	$SQL->ORDER_BY( $curr_orderby.' '.$curr_orderdir.', post_ID '.$curr_orderdir );
	$SQL->LIMIT( $num_posts );
	$items_result = $DB->get_results( $SQL->get(), ARRAY_A, 'Find the x latest posts in blog' );

	$count = 1;
	$fix_content = 'This is an auto generated comment for testing the moderation features.
					http://www.test.com/test_comment_';
	// go through on selected items
	foreach( $items_result as $row )
	{
		$item_ID = $row['post_ID'];

		$ItemCache = & get_ItemCache();
		$commented_Item = & $ItemCache->get_by_ID( $item_ID );

		// create $num_comments comments for each item
		for( $i = 0; $i < $num_comments; $i++ )
		{
			$author = 'Test '.$count;
			$email = 'test_'.$count.'@test.com';
			$url = 'http://www.test-'.rand(1,3).'.com/test_comment_'.$count;

			$content = $fix_content.$count;
			for( $j = 0; $j < 50; $j++ )
			{ // create 50 random word
				$length = rand(1, 15);
				$word = generate_random_key( $length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
				$content = $content.' '.$word;
			}

			// create and save a new comment
			$Comment = new Comment();
			$Comment->set_Item( $commented_Item );
			$Comment->set( 'status', 'draft' );
			$Comment->set( 'author', $author );
			$Comment->set( 'author_email', $email );
			$Comment->set( 'author_url', $url );
			$Comment->set( 'content', $content );
			$Comment->set( 'date', date( 'Y-m-d H:i:s', $localtimenow ) );
			$Comment->set( 'author_IP', $Hit->IP );
			$Comment->dbsave();
			$count++;

			if( $count % 100 == 0 )
			{ // Display a process of creating by one dot for 100 comments
				echo ' .';
				evo_flush();
			}

			// Clear all debug messages, To avoid an error about full memory
			$Debuglog->clear( 'all' );
		}
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d comments.'), $count - 1 ), 'success' );
}


/**
 * Create sample posts and display a process of creating
 *
 * @param integer Blog ID
 * @param integer Number of posts
 */
function tool_create_sample_posts( $blog_ID, $num_posts )
{
	global $Messages, $DB, $Debuglog;

	$BlogCache = & get_BlogCache();
	$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
	if( $selected_Blog == NULL )
	{ // Incorrect blog ID, Exit here
		return;
	}

	echo T_('Creating of the sample posts...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	$count = 1;
	$num_posts_created = 0;
	$content = T_( 'This is an auto generated post for testing moderation.' );
	for( $i = 1; $i <= $num_posts; $i++ )
	{
		// Spaces and line breaks make generated string look like real text
		$length = rand(300, 500);
		$word = generate_random_key( $length, "\n     abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" );
		$post_content = $content.' '.$word;
		$urltitle = strtolower( str_replace( array("\n", ' ', '-'), '', substr($word, 50, 20) ) );
		$urltitle = trim( $urltitle, '-' );

		$Item = new Item();
		$Item->set( 'title', 'Generated post '.$i );
		$Item->set( 'content', $post_content );
		$Item->set( 'status', 'published' );
		$Item->set( 'dateset', 1 );
		// Set post main cat ID, from selected blog
		$Item->set( 'main_cat_ID', $selected_Blog->get_default_cat_ID() );
		// Random post url slug
		$Item->set( 'urltitle', $urltitle );
		if( $Item->dbinsert_test() )
		{
			$num_posts_created++;
		}
		$count++;

		if( $count % 100 == 0 )
		{ // Display a process of creating by one dot for 100 posts
			echo ' .';
			//pre_dump( memory_get_usage() );
			evo_flush();
		}

		// Clear all debug messages, To avoid an error about full memory
		$Debuglog->clear( 'all' );
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d posts.'), $num_posts_created ), 'success' );
	if( $num_posts > $num_posts_created )
	{ // Some post creation failed because of concurtent modification error
		// Note: This message should not appear offten, so it doesn't need translation
		$Messages->add( sprintf( 'Creation of %d post(s) failed becuase of concurrent modification error.', $num_posts - $num_posts_created ), 'note' );
	}
}


/**
 * Create sample users and display a process of creating
 *
 * @param array Group IDs
 * @param integer Number of users per group
 * @param array Kind of advanced user perms
 */
function tool_create_sample_users( $user_groups, $num_users, $advanced_user_perms )
{
	global $Messages, $DB, $Debuglog;

	echo T_('Creating of the sample users...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	// Check if we should assign at least one advanced permission:
	$array_intersect = array_intersect( $advanced_user_perms, array( 'member', 'moderator', 'admin' ) );
	$assign_adv_user_perms = ! empty( $array_intersect );

	if( $assign_adv_user_perms )
	{ // Get all collections with advanced perms:
		$coll_SQL = new SQL();
		$coll_SQL->SELECT( 'blog_ID' );
		$coll_SQL->FROM( 'T_blogs' );
		$coll_SQL->WHERE( 'blog_advanced_perms = 1' );
		$adv_perm_coll_IDs = $DB->get_col( $coll_SQL->get(), 0, 'Get all collections with advanced perms for tool "Create sample users"' );
	}

	// Load all selected groups in cache:
	$GroupCache = & get_GroupCache();
	$GroupCache->load_list( $user_groups );

	$count = 1;
	$user_groups_max_index = count( $user_groups ) - 1;
	$advanced_user_perms_max_index = count( $advanced_user_perms ) - 1;
	for( $i = 1; $i <= $num_users; $i++ )
	{
		$login = generate_random_passwd();
		while( user_exists( $login ) )
		{ // Generate new unique login
			$login = generate_random_passwd();
		}

		$User = new User();
		// Create out of range hashes for better security
		$User->set( 'pass', generate_random_passwd() );
		$User->set( 'login', $login );
		$User->set( 'email', 'test_'.$i.'@test.com' );
		$User->set( 'firstname', 'Test user '.$i );
		$User->set( 'url', 'http://www.test-'.rand(1,3).'.com/test_user_'.$i );
		if( $rand_Group = & $GroupCache->get_by_ID( $user_groups[ rand( 0, $user_groups_max_index) ], false, false ) )
		{	// Set a random group from checked options if a group really exists in DB:
			$User->set_Group( $rand_Group );
		}

		if( $User->dbinsert() )
		{	// If user has been created successfully
			$count++;

			if( $assign_adv_user_perms && ! empty( $adv_perm_coll_IDs ) )
			{	// Grant advanced user perms on existing collections with advanced perms:
				$adv_perm_coll_insert_values = array();
				foreach( $adv_perm_coll_IDs as $adv_perm_coll_ID )
				{	// Select random kind of advanced permissions:
					switch( $advanced_user_perms[ rand( 0, $advanced_user_perms_max_index ) ] )
					{
						case 'member':
							// Set member permissions:
							$adv_perm_coll_insert_values[] = implode( ',', array(
									'blog_ID'              => $adv_perm_coll_ID,
									'user_ID'              => $User->ID,
									'ismember'             => 1,
									'can_be_assignee'      => 0,
									'perm_poststatuses'    => '""',
									'perm_item_type'       => '"standard"',
									'perm_edit'            => '"no"',
									'perm_delpost'         => 0,
									'perm_edit_ts'         => 0,
									'perm_delcmts'         => 0,
									'perm_recycle_owncmts' => 0,
									'perm_vote_spam_cmts'  => 0,
									'perm_cmtstatuses'     => '""',
									'perm_edit_cmt'        => '"no"',
									'perm_cats'            => 0,
									'perm_properties'      => 0,
									'perm_admin'           => 0,
									'perm_media_upload'    => 0,
									'perm_media_browse'    => 0,
									'perm_media_change'    => 0,
									'perm_analytics'       => 0,
								) );
							break;

						case 'moderator':
							// Set moderator permissions:
							$adv_perm_coll_insert_values[] = implode( ',', array(
									'blog_ID'              => $adv_perm_coll_ID,
									'user_ID'              => $User->ID,
									'ismember'             => 1,
									'can_be_assignee'      => 1,
									'perm_poststatuses'    => '"published,community,protected,review,private,draft,deprecated"',
									'perm_item_type'       => '"restricted"',
									'perm_edit'            => '"le"',
									'perm_delpost'         => 1,
									'perm_edit_ts'         => 1,
									'perm_delcmts'         => 1,
									'perm_recycle_owncmts' => 1,
									'perm_vote_spam_cmts'  => 1,
									'perm_cmtstatuses'     => '"published,community,protected,review,private,draft,deprecated"',
									'perm_edit_cmt'        => '"le"',
									'perm_cats'            => 0,
									'perm_properties'      => 0,
									'perm_admin'           => 0,
									'perm_media_upload'    => 1,
									'perm_media_browse'    => 1,
									'perm_media_change'    => 1,
									'perm_analytics'       => 0,
								) );
							break;

						case 'admin':
							// Set administrator permissions:
							$adv_perm_coll_insert_values[] = implode( ',', array(
									'blog_ID'              => $adv_perm_coll_ID,
									'user_ID'              => $User->ID,
									'ismember'             => 1,
									'can_be_assignee'      => 1,
									'perm_poststatuses'    => '"published,community,protected,review,private,draft,deprecated,redirected"',
									'perm_item_type'       => '"admin"',
									'perm_edit'            => '"all"',
									'perm_delpost'         => 1,
									'perm_edit_ts'         => 1,
									'perm_delcmts'         => 1,
									'perm_recycle_owncmts' => 1,
									'perm_vote_spam_cmts'  => 1,
									'perm_cmtstatuses'     => '"published,community,protected,review,private,draft,deprecated"',
									'perm_edit_cmt'        => '"all"',
									'perm_cats'            => 1,
									'perm_properties'      => 1,
									'perm_admin'           => 1,
									'perm_media_upload'    => 1,
									'perm_media_browse'    => 1,
									'perm_media_change'    => 1,
									'perm_analytics'       => 1,
								) );
							break;
					}
				}
				if( ! empty( $adv_perm_coll_insert_values ) )
				{	// Insert advanced user perms for new created user in single query for all collections with advanced perms:
					$DB->query( 'INSERT INTO T_coll_user_perms ( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember, bloguser_can_be_assignee,
							bloguser_perm_poststatuses, bloguser_perm_item_type, bloguser_perm_edit, bloguser_perm_delpost, bloguser_perm_edit_ts,
							bloguser_perm_delcmts, bloguser_perm_recycle_owncmts, bloguser_perm_vote_spam_cmts, bloguser_perm_cmtstatuses,
							bloguser_perm_edit_cmt, bloguser_perm_cats, bloguser_perm_properties, bloguser_perm_admin, bloguser_perm_media_upload,
							bloguser_perm_media_browse, bloguser_perm_media_change, bloguser_perm_analytics )
						VALUES ( '.implode( '), (', $adv_perm_coll_insert_values ).' )' );
				}
			}
		}

		// Clear the messages because we have at least 4 messages after each $new_Blog->create() on user creating with group that has an appropriate setting:
		// (Those messages are the duplicated, so they fill a screen very large)
		$Messages->clear();

		if( $count % 20 == 0 )
		{	// Display a process of creating by one dot for 20 users:
			echo ' .';
			evo_flush();
		}

		// Clear all debug messages, To avoid an error about full memory:
		$Debuglog->clear( 'all' );
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d users.'), $count - 1 ), 'success' );
}


/**
 * Create sample hits and display a process of creating
 *
 * @param integer Days of stats to generate
 * @param integer Minimal interval between 2 consecutive hits (sec)
 * @param integer Maximal interval between 2 consecutive hits (sec)
 */
function tool_create_sample_hits( $days, $min_interval, $max_interval )
{
	global $Messages, $DB;

	load_funcs('sessions/model/_hitlog.funcs.php');

	echo T_('Creating of the sample hits...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	$insert_data_count = generate_hit_stat( $days, $min_interval, $max_interval, true );

	echo ' OK.';

	$Messages->add( sprintf( '%d test data hits are added.', $insert_data_count ), 'success' );
}


/**
 * Create sample messages and display a process of creating
 *
 * @param integer Number of loops
 * @param integer Number of messages in each conversation
 * @param integer Number of words in each message
 * @param integer Max # of participants in a conversation
 */
function tool_create_sample_messages( $num_loops, $num_messages, $num_words, $max_users )
{
	global $Messages, $DB;

	echo T_('Creating of the sample messages...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	// Get all users
	$SQL = new SQL();
	$SQL->SELECT( 'user_ID' );
	$SQL->FROM( 'T_users' );
	$users = $DB->get_col( $SQL->get() );

	if( count( $users ) < 2 )
	{	// No users
		$Messages->add( T_('At least two users must exist in DB to create the messages'), 'error' );
		return;
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
					$DB->query( 'INSERT INTO T_messaging__message ( msg_author_user_ID , msg_datetime, msg_thread_ID, msg_text, msg_renderers )
						VALUES ( '.$DB->quote( $message_user_ID ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).', '.$DB->quote( $thread_ID ).', '.$DB->quote( $msg_text ).', \'default\' )' );
					$count_messages++;

					if( $count_messages % 100 == 0 )
					{ // Display a process of creating by one dot for 100 users
						echo ' .';
						evo_flush();
					}
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
			$DB->query( 'INSERT INTO T_messaging__message ( msg_author_user_ID , msg_datetime, msg_thread_ID, msg_text, msg_renderers )
				VALUES ( '.$DB->quote( $users[ $user_number ] ).', '.$DB->quote( date( 'Y-m-d H:i:s' ) ).', '.$DB->quote( $thread_ID ).', '.$DB->quote( $msg_text ).', \'default\' )' );
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

	echo ' OK.';

	$Messages->add( sprintf( T_('%d threads and %d messages have been created.'), $count_threads, $count_messages ), 'success' );
}


/**
 * Test a flush function
 */
function tool_test_flush()
{
	for( $i = 1; $i <= 30; $i++ )
	{
		echo T_('Sleeping for 1 second...').'<br />';
		evo_flush();
		sleep( 1 );
	}
}
?>