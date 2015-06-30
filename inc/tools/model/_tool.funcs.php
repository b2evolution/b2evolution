<?php
/**
 * This file implements functions to work with tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Create sample comments and display a process of creating
 *
 * @param integer Blog ID
 * @param integer Number of comments
 * @param integer Number of posts
 */
function tool_create_sample_comments( $blog_ID, $num_comments, $num_posts )
{
	global $DB, $posttypes_specialtypes, $localtimenow, $Hit, $Messages, $Debuglog;

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
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog_ID ) );
	$SQL->WHERE_and( 'post_status = '.$DB->quote( 'published' ) );
	// Set condition to not create sample comments for special posts
	$SQL->WHERE_and( 'post_ityp_ID NOT IN ( '.$DB->quote( $posttypes_specialtypes ).' )' );
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
 * @param integer Group ID
 * @param integer Number of users
 */
function tool_create_sample_users( $group_ID, $num_users )
{
	global $Messages, $DB, $Debuglog;

	echo T_('Creating of the sample users...');
	evo_flush();

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	$count = 1;
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
		$User->set( 'grp_ID', $group_ID );
		$User->dbinsert();
		$count++;

		if( $count % 100 == 0 )
		{ // Display a process of creating by one dot for 100 users
			echo ' .';
			evo_flush();
		}

		// Clear all debug messages, To avoid an error about full memory
		$Debuglog->clear( 'all' );
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d users.'), $num_users ), 'success' );
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