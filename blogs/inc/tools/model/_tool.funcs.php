<?php
/**
 * This file implements functions to work with tools.
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
 * Create sample comments and display a process of creating
 *
 * @param integer Blog ID
 * @param integer Number of comments
 * @param integer Number of posts
 */
function tool_create_sample_comments( $blog_ID, $num_comments, $num_posts )
{
	global $DB, $posttypes_specialtypes, $localtimenow, $Hit, $Messages;

	$BlogCache = & get_BlogCache();
	$selected_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );
	if( $selected_Blog == NULL )
	{ // Incorrect blog ID, Exit here
		return;
	}

	echo T_('Creating of the sample comments...');
	flush();

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
	$SQL->WHERE_and( 'post_ptyp_ID NOT IN ( '.$DB->quote( $posttypes_specialtypes ).' )' );
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
				flush();
			}
		}
	}

	echo ' OK.';

	$Messages->add( sprintf( T_('Created %d comments.'), $count - 1 ), 'success' );
}

?>