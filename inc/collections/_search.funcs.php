<?php
/**
 * This file implements misc functions that handle search for posts, comments, categories, etc.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Return a search score for the given text and search keywords
 *
 * @param string the text to score
 * @param string the search keywords to score by
 * @return integer the result score
 */
function score_text( $text, $search_keywords )
{
	$score = 0;

	if( !empty( $search_keywords ) && strpos( $text, $search_keywords ) !== false )
	{
		$score += 10;
	}

	$words_list = explode( ' ', $search_keywords );
	$all_found = true;
	foreach( $words_list as $word )
	{
		$count = empty( $word ) ? 0 : substr_count( $text, $word );
		if( $count == 0 )
		{
			$all_found = false;
		}
		else
		{
			$score += ( ( $count > 1 ) ? 2 : 1 );
		}
	}
	if( $all_found )
	{
		$score += 8;
	}

	return $score;
}


/**
 * Return a search score for the given date. Recent dates get higher scores.
 *
 * @param string the date to score
 * @return integer the result score
 */
function score_date( $date )
{
	global $localtimenow;

	$day_diff = floor( ($localtimenow - strtotime($date)) / (60 * 60 * 24) );
	if( $day_diff < 0 )
	{
		return 0;
	}

	if( $day_diff <= 7 )
	{
		return ( ( $day_diff < 5 ) ? ( 8 - $day_diff ) : 3 );
	}

	if( $day_diff < 15 )
	{
		return 2;
	}

	return ( ( $day_diff < 31 ) ? 1 : 0 );
}


/**
 * Create search result and give a score for each object
 *
 * @param string the search keywords
 * @return array scored search result, each element is an array( type, ID, score )
 */
function score_search_result( $search_keywords )
{
	global $Blog, $DB, $posttypes_perms;

	$keywords = preg_replace( '/, +/', '', $search_keywords );
	$keywords = str_replace( ',', ' ', $keywords );
	$keywords = str_replace( '"', ' ', $keywords );
	$keywords = trim( $keywords );
	$keywords = explode( ' ', $keywords );

	// Exclude search from 'sidebar' type posts and from reserved type with ID 5000
	$filter_post_types = isset( $posttypes_perms['sidebar'] ) ? $posttypes_perms['sidebar'] : array();
	$filter_post_types = array_merge( $filter_post_types, array( 5000 ) );

	// Search between posts
	$search_ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), '', 'ItemCache', 'search_item' );
	$search_ItemList->set_filters( array(
			'keywords' => $search_keywords,
			'phrase' => 'OR',
			'types'  => '-'.implode( ',', $filter_post_types ),
		) );
	$search_ItemList->query();

	$search_result = array();
	$score_result = array();
	while( $Item = & $search_ItemList->get_next() )
	{
		$score = score_text( $Item->get( 'title' ), $search_keywords );
		$score += score_text( $Item->content, $search_keywords );
		$item_creator_login = $Item->get_creator_login();
		if( !empty( $search_keywords ) && !empty( $item_creator_login ) && strpos( $item_creator_login, $search_keywords ) !== false )
		{
			$score += 5;
		}
		$score += score_date( $Item->get_mod_date() );

		$search_result[] = array( 'type' => 'item', 'score' => $score, 'ID' => $Item->ID );
		$score_result[] = $score;
	}

	// Search between comments
	$search_CommentList = new CommentList2( $Blog, '', 'CommentCache', 'search_comment' );
	$search_CommentList->set_filters( array( 'keywords' => $search_keywords, 'phrase' => 'OR', 'order_by' => 'date' ) );
	$search_CommentList->query();

	while( $Comment = & $search_CommentList->get_next() )
	{
		$comment_Item = & $Comment->get_Item();
		$score = score_text( $comment_Item->get( 'title' ), $search_keywords );
		$score += score_text( $Comment->get( 'content' ), $search_keywords );
		$comment_author_name = $Comment->get_author_name();
		if( !empty( $comment_author_name ) && !empty( $search_keywords ) && strpos( $comment_author_name, $search_keywords ) !== false )
		{
			$score += 5;
		}
		$score += score_date( $Comment->date );

		$search_result[] = array( 'type' => 'comment', 'score' => $score, 'ID' => $Comment->ID );
		$score_result[] = $score;
	}

	$or = '';
	$cat_where_condition = '';
	$tag_where_condition = '';
	foreach( $keywords as $keyword )
	{
		$keyword = $DB->escape( $keyword );
		$cat_where_condition .= $or.' ( cat_name LIKE \'%'.$keyword.'%\' ) OR ( cat_description LIKE \'%'.$keyword.'%\' )';
		$tag_where_condition .= $or.' ( tag_name LIKE \'%'.$keyword.'%\' )';
		$or = ' OR';
	}

	// Search between categories
	$ChapterCache = & get_ChapterCache();
	$cat_where_condition = '( cat_blog_ID = '.$DB->quote( $Blog->ID ).' ) AND ('.$cat_where_condition.' )';
	$ChapterCache->load_where( $cat_where_condition );
	while( ( $iterator_Chapter = & $ChapterCache->get_next() ) != NULL )
	{
		$score = score_text( $iterator_Chapter->get( 'name' ), $search_keywords );
		$score += score_text( $iterator_Chapter->get( 'description' ), $search_keywords );
		$post_count = get_postcount_in_category( $iterator_Chapter->ID, $Blog->ID );
		$post_score = intval( $post_count / 3 );
		$post_score = ( $post_score > 10 ) ? 10 : $post_score;
		$score += $post_score;
		$comment_count = get_commentcount_in_category( $iterator_Chapter->ID, $Blog->ID );
		$comment_score = intval( $comment_count / 6 );
		$comment_score = ( $comment_score > 10 ) ? 10 : $comment_score;
		$score += $comment_score;

		$search_result[] = array( 'type' => 'category', 'score' => $score, 'ID' => $iterator_Chapter->ID );
		$score_result[] = $score;
	}

	// Search between tags
	$sql = 'SELECT tag_name, COUNT(DISTINCT itag_itm_ID) as post_count
					FROM T_items__tag INNER JOIN T_items__itemtag ON itag_tag_ID = tag_ID
						INNER JOIN T_postcats ON itag_itm_ID = postcat_post_ID
						INNER JOIN T_categories ON postcat_cat_ID = cat_ID
					WHERE cat_blog_ID = '.$DB->quote( $Blog->ID ).' AND '.$tag_where_condition.'
					GROUP BY tag_name';
	$tags = $DB->get_assoc( $sql, 'Get tags matching to the search keywords' );
	foreach( $tags as $tag_name => $post_count )
	{
		if( $post_count == 0 )
		{
			continue;
		}
		$score = score_text( $tag_name, $search_keywords );
		$score = ( $score * $post_count );
		$search_result[] = array( 'type' => 'tag', 'score' => $score, 'ID' => $tag_name.':'.$post_count );
		$score_result[] = $score;
	}

	array_multisort( $score_result, SORT_DESC, $search_result );
	return $search_result;
}


/*
 * Display the search result block
 *
 * @param array Params
 */
function search_result_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'title_prefix_post'     => T_('Post: '),
			'title_prefix_comment'  => T_('Comment: '),
			'title_prefix_category' => T_('Category: '),
			'title_prefix_tag'      => T_('Tag: '),
			'block_start'           => '',
			'block_end'             => '',
			'pagination'            => array(),
			'use_editor'            => false, // Use editor instead of author if it is allowed (only the posts have an editor)
			'author_format'         => 'avatar_name', // @see User::get_identity_link() // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
			'date_format'           => locale_datefmt(),
		), $params );

	global $Blog, $Session, $search_result_loaded;

	$search_result = $Session->get( 'search_result' );
	if( empty( $search_result ) )
	{
		echo '<p class="msg_nothing" style="margin: 2em 0">';
		echo T_('Sorry, we could not find anything matching your request, please try to broaden your search.');
		echo '<p>';
		return;
	}

	$result_count = count( $search_result );
	$result_per_page = $Blog->get_setting( 'posts_per_page' );
	if( $result_count > $result_per_page )
	{
		$current_page = param( 'page', 'integer', 1 );
		$total_pages = ceil($result_count / $result_per_page);
		if( $current_page > $total_pages )
		{
			$current_page = $total_pages;
		}

		$page_params = array_merge( array(
			'total' => $result_count,
			'result_per_page' => $result_per_page,
			'current_page' => $current_page,
			'total_pages' => $total_pages,
		), $params['pagination'] );
		search_page_links( $page_params );
	}
	else
	{
		$current_page = 1;
		$total_pages = 1;
	}

	// Set current page indexes
	$from = ( ( $current_page -1 ) * $result_per_page );
	$to = ( $current_page < $total_pages ) ? ( $from + $result_per_page ) : ( $result_count );

	// Init caches
	$ItemCache = & get_ItemCache();
	$CommentCache = & get_CommentCache();
	$ChapterCache = & get_ChapterCache();

	if( !$search_result_loaded )
	{ // Search result objects are not loaded into memory yet, load them
		// Group required object ids by type
		$required_ids = array();
		for( $index = $from; $index < $to; $index++ )
		{
			$row = $search_result[ $index ];
			if( isset( $required_ids[ $row['type'] ] ) )
			{
				$required_ids[ $row['type'] ][] = $row['ID'];
			}
			else
			{
				$required_ids[ $row['type'] ] = array( $row['ID'] );
			}
		}

		// Load each required object into the corresponding cache
		foreach( $required_ids as $type => $object_ids )
		{
			switch( $type )
			{
				case 'item':
					$ItemCache->load_list( $object_ids );
					break;
	
				case 'comment':
					$CommentCache->load_list( $object_ids );
					break;
	
				case 'category':
					$ChapterCache->load_list( $object_ids );
					break;
	
				default: // Not handled search result type
					break;
			}
		}
	}

	echo $params['block_start'];

	for( $index = $from; $index < $to; $index++ )
	{
		$row = $search_result[ $index ];
		switch( $row['type'] )
		{
			case 'item':
				$Item = $ItemCache->get_by_ID( $row['ID'] );
				$display_params = array(
					'title'   => $params['title_prefix_post'].$Item->get_title( array( 'link_type' => 'permalink' ) ),
					'excerpt' => $Item->get_excerpt2(),
				);
				if( $params['use_editor'] )
				{ // Get editor info to display
					$lastedit_User = & $Item->get_lastedit_User();
					if( empty( $lastedit_User ) )
					{ // If editor is not defined yet then use author
						$lastedit_User = & $Item->get_creator_User();
					}
					$display_params = array_merge( array(
							'editor'        => $lastedit_User->get_identity_link( array( 'link_text' => $params['author_format'] ) ),
							'lastedit_date' => mysql2date( $params['date_format'], empty( $Item->datemodified ) ? $Item->datecreated : $Item->datemodified ),
						), $display_params );
				}
				else
				{ // Get author info to display
					$creator_User = & $Item->get_creator_User();
					$display_params = array_merge( array(
							'author'        => $creator_User->get_identity_link( array( 'link_text' => $params['author_format'] ) ),
							'creation_date' => mysql2date( $params['date_format'], $Item->datecreated ),
							'lastedit_date' => mysql2date( $params['date_format'], $Item->datemodified ),
						), $display_params );
				}
				break;

			case 'comment':
				$Comment = $CommentCache->get_by_ID( $row['ID'] );
				$display_params = array(
					'title'   => $params['title_prefix_comment'].$Comment->get_permanent_link( '#item#' ),
					'excerpt' => excerpt( $Comment->content ),
					'author'  => $Comment->get_author( array(
							'link_text'   => $params['author_format'],
							'thumb_size'  => 'crop-top-15x15',
							'thumb_class' => 'avatar_before_login'
						) ),
					'creation_date' => mysql2date( $params['date_format'], $Comment->date )
				);
				break;

			case 'category':
				$Chapter = $ChapterCache->get_by_ID( $row['ID'] );
				$display_params = array(
					'title'   => $params['title_prefix_category'].' <a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get_name().'</a>',
					'excerpt' => excerpt( $Chapter->get( 'description' ) ),
				);
				break;

			case 'tag':
				list( $tag_name, $post_count ) = explode( ':', $row['ID'] );
				$display_params = array(
					'title'   => $params['title_prefix_tag'].' <a href="'.url_add_param( $Blog->gen_blogurl(), 'tag='.$tag_name ).'">'.$tag_name.'</a>',
					'excerpt' => sprintf( T_('%d posts are tagged with \'%s\''), $post_count, $tag_name ),
				);

			default: // Other type of result is not implemented
				continue;
		}

		display_search_result( array_merge( $params, $display_params ) );
	}

	echo $params['block_end'];

	if( $result_count > $result_per_page )
	{
		search_page_links( $page_params );
	}
}


/**
 * Display search result page links
 *
 * @param array params
 */
function search_page_links( $params = array() )
{
	if( !isset( $params['total_pages'] ) )
	{ // this param is required
		return;
	}

	$params = array_merge( array(
			'block_start'           => '<p class="center">',
			'block_end'             => '</p>',
			'page_current_template' => '<b>$page_num$</b>',
			'page_item_before'      => ' ',
			'page_item_after'       => '',
			'prev_text'             => '&lt;&lt;',
			'next_text'             => '&gt;&gt;',
			'prev_class'            => '',
			'next_class'            => '',
		), $params );

	$total_pages = $params['total_pages'];
	$current_page = isset( $params['current_page'] ) ? $params['current_page'] : 1;
	$page_url = regenerate_url( 'page', '' );

	echo $params['block_start'];
	if( $current_page > 1 )
	{ // A link to previous page
		echo $params['page_item_before'];
		$prev_attrs = empty( $params['prev_class'] ) ? '' : ' class="'.$params['prev_class'].'"';
		echo '<a href="'.url_add_param( $page_url, 'page='.( $current_page - 1 ) ).'" rel="prev"'.$prev_attrs.'>'.$params['prev_text'].'</a>';
		echo $params['page_item_after'];
	}
	$page_prev_i = $current_page - 1;
	$page_next_i = $current_page + 1;
	for( $i = 1 ; $i <= $total_pages; $i++ )
	{
		echo $params['page_item_before'];
		if( $i == $current_page )
		{ // Current page
			echo str_replace( '$page_num$', $i, $params['page_current_template'] );
		}
		else
		{
			$attr_rel = '';
			if( $page_prev_i == $i )
			{ // Add attribute rel="prev" for previous page
				$attr_rel = ' rel="prev"';
			}
			elseif( $page_next_i == $i )
			{ // Add attribute rel="next" for next page
				$attr_rel = ' rel="next"';
			}
			echo '<a href="'.url_add_param( $page_url, 'page='.$i ).'"'.$attr_rel.'>'.$i.'</a>';
		}
		echo $params['page_item_after'];
	}
	if( $current_page < $total_pages )
	{ // A link to next page
		echo $params['page_item_before'];
		$next_attrs = empty( $params['next_class'] ) ? '' : ' class="'.$params['next_class'].'"';
		echo ' <a href="'.url_add_param( $page_url, 'page='.( $current_page + 1 ) ).'" rel="next"'.$next_attrs.'>'.$params['next_text'].'</a>';
		echo $params['page_item_after'];
	}
	echo $params['block_end'];
}


/**
 * Display one search result object
 *
 * @param array result object params
 */
function display_search_result( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'title'              => '',
			'author'             => '',
			'creation_date'      => '',
			'excerpt'            => '',
			'row_start'          => '<div class="search_result">',
			'row_end'            => '</div>',
			'cell_title_start'   => '<div class="search_title">',
			'cell_title_end'     => '</div>',
			'cell_author_start'  => '<div class="search_info">',
			'cell_author_end'    => '</div>',
			'cell_author_empty'  => false, // false - to display author only when it is defined, use string to print text instead of empty author
			'cell_content_start' => '<div class="result_content">',
			'cell_content_end'   => '</div>',
		), $params );

	echo $params['row_start'];

	// Title
	echo $params['cell_title_start'].$params['title'].$params['cell_title_end'];

	// Content
	echo $params['cell_content_start'];
	echo ! empty( $params['excerpt'] ) ? $params['excerpt'] : '&nbsp;';
	echo $params['cell_content_end'];

	if( ! empty( $params['author'] ) || ! empty( $params['editor'] ) || $params['cell_author_empty'] !== false )
	{ // Display author or empty string when no author
		echo $params['cell_author_start'];
		if( ! empty( $params['author'] ) )
		{ // Display author if it is defined
			$lastedit_date = ( isset( $params['lastedit_date'] ) ) ? ', '.T_('last edited on').' '.$params['lastedit_date'] : '';
			printf( T_('Created by %s on %s'), $params['author'], $params['creation_date'] ).$lastedit_date;
		}
		elseif( ! empty( $params['editor'] ) )
		{ // Display editor if it is defined
			echo T_('Last edit by ').$params['editor'].T_(' on ').$params['lastedit_date'];
		}
		else// $params['cell_author_empty'] !== false
		{ // Display empty value if author is not defined
			echo $params['cell_author_empty'];
		}
		echo $params['cell_author_end'];
	}

	echo $params['row_end'];
}