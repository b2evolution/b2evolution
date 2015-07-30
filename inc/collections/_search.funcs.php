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
function score_text( $text, $search_term, & $percentage_map, $max_score, $words = array(), $quoted_terms = array() )
{
	global $Debuglog;

	$score = 0;
	$scores_map = array();

	if( !empty( $search_term ) && strpos( $text, $search_term ) !== false )
	{
		// the max score what it may received in this methods
		$score += $max_score + count( $words ) + count( $quoted_terms ) * 2;
		$scores_map['whole_term'] = $max_score;
		$percentage_map['whole_term'] = 1;
		return array( 'score' => $score, 'map' => $scores_map, 'percentage_map' => $percentage_map );
	}

	$all_found = true;
	foreach( $quoted_terms as $quoted_term )
	{ // find quoted keywords
		$count = empty( $quoted_term ) ? 0 : substr_count( $text, $quoted_term );
		if( $count == 0 )
		{
			$all_found = false;
		}
		else
		{
			$score += ( ( $count > 1 ) ? 8 : 6 );
//			if( ! isset( $percentage_map['quoted_term'][$quoted_term] ) || $percentage_map['quoted_term'][$quoted_term] < 6 )
//			{
//				$percentage_map['quoted_term'][$quoted_term] = 6;
//			}
			$percentage_map['quoted_parts'][$quoted_term] = 1;
			$scores_map['quoted_term'][$quoted_term] = ( $count > 1 ) ? 8 : 6;
		}
	}
	if( $all_found && count( $quoted_terms ) > 0 )
	{
		$score += 4;
		$scores_map['quoted_term_all'] = 4;
	}

	$all_case_sensitive_match = true;
	$all_whole_word_match = true;
	foreach( $words as $word )
	{
		$count = empty( $word ) ? 0 : substr_count( $text, $word );
		if( $count == 0 )
		{
			$all_case_sensitive_match = false;
		}
		else
		{
			$score += ( ( $count > 1 ) ? 2 : 1 );
//			if( ! isset( $percentage_map['word_case_sensitive_match'][$word] ) )
//			{
//				$percentage_map['word_case_sensitive_match'][$word] = 1;
//			}
//			$percentage_map['keywords'][$word] = 1;
			$scores_map['word_case_sensitive_match'][$word] = ( $count > 1 ) ? 2 : 1;
		}

		if( $word_match_count = preg_match_all( '/\b'.$word.'\b/i', $text, $matches ) )
		{ // Every word match gives one more score
			// $Debuglog->add( sprintf('Word match: [%s]', $word), 'info' );
			$score += ( ( $word_match_count > 1 ) ? 2 : 1 );
//			if( ! isset( $percentage_map['whole_word_match'][$word] ) )
//			{
//				$percentage_map['whole_word_match'][$word] = 1;
//			}
//			$percentage_map['keywords'][$word] = 1;
			$scores_map['whole_word_match'][$word] = ( ( $word_match_count > 1 ) ? 2 : 1 );
		}
		else
		{
			// $Debuglog->add( 'All word match set to false', 'info' );
			$all_whole_word_match = false;
		}

		if( $any_match_count = preg_match_all( '/'.$word.'/i', $text, $matches ) )
		{ // Every word match gives one more score
			// $Debuglog->add( sprintf('Word match: [%s]', $word), 'info' );
//			if( ! isset( $percentage_map['word_case_insensitive_match'][$word] ) )
//			{
//				$percentage_map['word_case_insensitive_match'][$word] = 1;
//			}
			$percentage_map['keywords'][$word] = 1;
			$scores_map['word_case_insensitive_match'][$word] = ( ( $any_match_count > 1 ) ? 2 : 1 );
			$score += $scores_map['word_case_insensitive_match'][$word];
		}
	}

	if( $all_case_sensitive_match )
	{
		$score += 4;
		$scores_map['all_case_sensitive'] = 4;
	}

	if( $all_whole_word_match )
	{
		$score += 4;
		$scores_map['all_whole_words'] = 4;
	}

	return array( 'score' => $score, 'map' => $scores_map, 'percentage_map' => $percentage_map );
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
 * Get percentage scores from the calculated map
 *
 * @param array percentage map
 * @return number percentage score
 */
function get_percentage_score( $percentage_map, $max_score )
{
	$score = 0;
	if( isset( $percentage_map['whole_term'] ) )
	{
		return $max_score;
	}

	if( isset( $percentage_map['quoted_parts'] ) )
	{
		foreach( $percentage_map['quoted_parts'] as $found )
		{
			if( $found )
			{
				$score += 6;
			}
		}
	}

	foreach( $percentage_map['keywords'] as $found )
	{
		if( $found )
		{
			$score += 3;
		}
	}

	return $score;
}


/**
 * Search and score items
 *
 * @param string original search term
 * @param array all separated words from the search term
 * @param array all quoted parts from the search term
 * @param number max possible score
 */
function search_and_score_items( $search_term, $keywords, $quoted_parts, $percentage_map, $max_score )
{
	global $Blog, $posttypes_perms;

	// Exclude search from 'sidebar' type posts and from reserved type with ID 5000
	$filter_post_types = isset( $posttypes_perms['sidebar'] ) ? $posttypes_perms['sidebar'] : array();
	$filter_post_types = array_merge( $filter_post_types, array( 5000 ) );

	// Search between posts
	$search_ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), '', 'ItemCache', 'search_item' );
	$search_ItemList->set_filters( array(
			'keywords' => $search_term,
			'phrase' => 'OR',
			'types'  => '-'.implode( ',', $filter_post_types ),
		) );
	$search_ItemList->query();

	$search_result = array();
	while( $Item = & $search_ItemList->get_next() )
	{
		$scores_map = array();
		$item_percentage_map = $percentage_map;

		$scores_map['title'] = score_text( $Item->get( 'title' ), $search_term, $item_percentage_map, $max_score, $keywords, $quoted_parts );
		$scores_map['content'] = score_text( $Item->content, $search_term, $item_percentage_map, $max_score, $keywords, $quoted_parts );
		$item_creator_login = $Item->get_creator_login();
		if( !empty( $search_term ) && !empty( $item_creator_login ) && strpos( $item_creator_login, $search_term ) !== false )
		{
			$scores_map['creator_login'] = 5;
		}
		$scores_map['last_mod_date'] = score_date( $Item->get_mod_date() );

		$final_score = $scores_map['title']['score']
			+ $scores_map['content']['score']
			+ ( isset( $scores_map['creator_login'] ) ? $scores_map['creator_login'] : 0 )
			+ $scores_map['last_mod_date'];
		$percentage_score = get_percentage_score( $item_percentage_map, $max_score );

		$search_result[] = array(
			'type' => 'item',
			'score' => $final_score,
			'ID' => $Item->ID,
			'scores_map' => $scores_map,
			'percentage' => round( $percentage_score * 100 / $max_score ),
		);
	}

//	// Giving percentage values
//	foreach( $search_result as $index => $result_value )
//	{
//		$scores_map = $result_value['scores_map'];
//		if( isset( $scores_map['whole_term'] ) )
//		{
//			$search_result[$index]['percentage'] = 100;
//		}
//		else
//		{
//			$matched_quoted_parts = 0;
//			foreach( $quoted_parts as $quoted_part )
//			{
//				if( isset( $scores_map['title']['map']['quoted_term'][$quoted_part] )
//					|| isset( $scores_map['content']['map']['quoted_term'][$quoted_part]) )
//				{
//					$matched_quoted_parts++;
//				}
//			}
//			$percentage_score = $matched_quoted_parts * 6/* + ( ( $matched_quoted_parts == count( $quoted_parts ) ) ? 4 : 0 )*/;
//
//			$matched_keywords = 0;
//			foreach( $keywords as $keyword )
//			{
//				if( isset( $scores_map['title']['map']['word_case_sensitive_match'][$keyword] )
//					|| isset( $scores_map['content']['map']['word_case_sensitive_match'][$keyword]) )
//				{
//					$matched_keywords++;
//				}
//				if( isset( $scores_map['title']['map']['whole_word_match'][$keyword] )
//					|| isset( $scores_map['content']['map']['whole_word_match'][$keyword]) )
//				{
//					$matched_keywords++;
//				}
//				if( isset( $scores_map['title']['map']['word_case_insensitive_match'][$keyword] )
//					|| isset( $scores_map['content']['map']['word_case_insensitive_match'][$keyword]) )
//				{
//					$matched_keywords++;
//				}
//			}
//
//			$percentage_score += $matched_keywords /*+ ( ( count( $keywords ) == $matched_keywords / 3 ) ? 8 : 0 )*/;
//			// $search_result[$index]['percentage'] = ( $percentage_score * 100 ) / $max_score;
//			$final_score = $search_result[$index]['score'];
//			$search_result[$index]['percentage'] = ( $final_score >= $max_score ) ? 100 : round( ( $final_score * 100 ) / $max_score );
//			$search_result[$index]['percentage_score'] = $percentage_score;
//		}
//	}

	return $search_result;
}


/**
 * Search and score comments
 *
 * @param string original search term
 * @param array all separated words from the search term
 * @param array all quoted parts from the search term
 * @param number max possible score
 */
function search_and_score_comments( $search_term, $keywords, $quoted_parts, $percentage_map, $max_score )
{
	global $Blog;

	// Search between comments
	$search_CommentList = new CommentList2( $Blog, '', 'CommentCache', 'search_comment' );
	$search_CommentList->set_filters( array( 'keywords' => $search_term, 'phrase' => 'OR', 'order_by' => 'date' ) );
	$search_CommentList->query();

	$search_result = array();
	while( $Comment = & $search_CommentList->get_next() )
	{
		$comment_Item = & $Comment->get_Item();
		$scores_map = array();
		$comment_percentage_map = $percentage_map;

		$scores_map['item_title'] = score_text( $comment_Item->get( 'title' ), $search_term, $comment_percentage_map, $max_score, $keywords, $quoted_parts );
		$scores_map['content'] = score_text( $Comment->get( 'content' ), $search_term, $comment_percentage_map, $max_score, $keywords, $quoted_parts );
		$comment_author_name = $Comment->get_author_name();
		if( !empty( $comment_author_name ) && !empty( $search_term ) && strpos( $comment_author_name, $search_term ) !== false )
		{
			$scores_map['author_name'] = 5;
		}
		$scores_map['creation_date'] = score_date( $Comment->date );

		$final_score = $scores_map['item_title']['score']
			+ $scores_map['content']['score']
			+ ( isset( $scores_map['author_name'] ) ? $scores_map['author_name'] : 0 )
			+ $scores_map['creation_date'];
		$percentage_score = get_percentage_score( $comment_percentage_map, $max_score );

		$search_result[] = array(
			'type' => 'comment',
			'score' => $final_score,
			'percentage' => round( $percentage_score * 100 / $max_score ),
			'ID' => $Comment->ID,
			'scores_map' => $scores_map
		);
	}

	return $search_result;
}


function search_and_score_chapters_and_tags( $search_term, $keywords, $quoted_parts, $percentage_map, $max_score )
{
	global $DB, $Blog;

	// Init result array
	$search_result = array();

	// Set query conditions
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
		$chapters_percentage_map = $percentage_map;
		$scores_map = array();
		$scores_map['name'] = score_text( $iterator_Chapter->get( 'name' ), $search_term, $chapters_percentage_map, $max_score, $keywords, $quoted_parts );
		$scores_map['description'] = score_text( $iterator_Chapter->get( 'description' ), $search_term, $chapters_percentage_map, $max_score, $keywords, $quoted_parts );

		$post_count = get_postcount_in_category( $iterator_Chapter->ID, $Blog->ID );
		$post_score = intval( $post_count / 3 );
		$scores_map['post_count'] = ( $post_score > 10 ) ? 10 : $post_score;

		$comment_count = get_commentcount_in_category( $iterator_Chapter->ID, $Blog->ID );
		$comment_score = intval( $comment_count / 6 );
		$scores_map['comment_count'] =  ( $comment_score > 10 ) ? 10 : $comment_score;

		$final_score = $scores_map['name']['score']
			+ $scores_map['description']['score']
			+ $scores_map['post_count']
			+ $scores_map['comment_count'];

		$percentage_score = get_percentage_score( $chapters_percentage_map, $max_score );

		$search_result[] = array(
			'type' => 'category',
			'score' => $final_score,
			'percentage' => round( $percentage_score * 100 / $max_score ),
			'ID' => $iterator_Chapter->ID,
			'scores_map' => $scores_map
		);
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
		{ // Count only those tags which have at least one post linked to it
			continue;
		}

		$scores_map = array();
		$tags_percentage_map = $percentage_map;
		$scores_map['name'] = score_text( $tag_name, $search_term, $tags_percentage_map, $max_score, $keywords, $quoted_parts );
		$scores_map['post_count'] = $post_count;
		$final_score = $scores_map['name']['score'] * $post_count;
		$percentage_score = get_percentage_score( $tags_percentage_map, $max_score );

		$search_result[] = array(
			'type' => 'tag',
			'score' => $final_score,
			'percentage' => round( $percentage_score * 100 / $max_score ),
			'ID' => $tag_name.':'.$post_count,
			'scores_map' => $scores_map
		);
	}

	return $search_result;
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
	global $scores_map, $score_prefix, $score_map_key, $Debuglog;

	// Get quoted parts
	$quoted_parts = array();
	if( preg_match_all( '/(["\'])(?:(?=(\\\\?))\\2.)*?\\1/', $search_keywords, $matches ) )
	{ // There are quoted search terms
		$quoted_parts = $matches[0];
		for( $index = 0; $index < count( $quoted_parts ); $index++ )
		{ // Remove douple quotes around the parts
			$quoted_part_length = utf8_strlen( $quoted_parts[$index] );
			if( $quoted_part_length > 2 )
			{ // The quoted part is not an empty string
				$quoted_parts[$index] = utf8_substr( $quoted_parts[$index], 1, $quoted_part_length - 2 );
			}
		}
		$quoted_parts = array_unique( $quoted_parts );
	};

	$keywords = preg_replace( '/, +/', '', $search_keywords );
	$keywords = str_replace( ',', ' ', $keywords );
	$keywords = str_replace( '"', ' ', $keywords );
	$keywords = trim( $keywords );
	$keywords = preg_split( '/\s+/', $keywords );
	$keywords = array_unique( $keywords );

	$percentage_map = array();
	foreach( $quoted_parts as $quoted_part )
	{
		$percentage_map['quoted_parts'][$quoted_part] = 0;
	}
	foreach( $keywords as $keyword )
	{
		$percentage_map['keywords'][$keyword] = 0;
	}

	$max_score =( count( $quoted_parts ) * 6/* + 4*/ ) + ( count( $keywords ) * 3/* + 8*/ );
	// $max_score =( count( $quoted_parts ) * 6 + 4 ) + ( count( $keywords ) * 3 + 8 );

	$search_result = search_and_score_items( $search_keywords, $keywords, $quoted_parts, $percentage_map, $max_score );

	$comment_search_result = search_and_score_comments( $search_keywords, $keywords, $quoted_parts, $percentage_map, $max_score );
	$search_result = array_merge( $search_result, $comment_search_result );

	$cats_and_tags_search_result = search_and_score_chapters_and_tags( $search_keywords, $keywords, $quoted_parts, $percentage_map, $max_score );
	$search_result = array_merge( $search_result, $cats_and_tags_search_result );

	$score_result = array();
	foreach( $search_result as $result_item )
	{
		$score_result[] = $result_item['score'];
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

		$display_params['score'] = $row['score'];
		$display_params['percentage'] = $row['percentage'];
		$display_params['scores_map'] = $row['scores_map'];
		$display_params['type'] = $row['type'];
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
	global $debug;

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
			'cell_author_start'  => '<div class="search_info dimmed">',
			'cell_author_end'    => '</div>',
			'cell_author_empty'  => false, // false - to display author only when it is defined, use string to print text instead of empty author
			'cell_content_start' => '<div class="result_content">',
			'cell_content_end'   => '</div>',
		), $params );

	echo $params['row_start'];

	echo '<div class="search_result_score">'.$params['percentage'].'%</div>';

	echo '<div class="search_content_wrap">';

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

	echo '</div>';

	if( $debug )
	{
		display_score_map( $params['scores_map'], $params['score'], $params['type'] );
	}

	echo $params['row_end'];
}


/**
 * Display score map regarding a search result
 *
 * @param array detailed information about the received search scores
 * @param integer total score received
 */
function display_score_map( $scores_map, $total_score, $result_type )
{
	echo '<ul>';
	foreach( $scores_map as $result_part => $score_map )
	{
		if( ! is_array(  $score_map['map'] ) )
		{
			if( $score_map > 0 )
			{ // Score received for this field
				switch ( $result_part )
				{
					case 'post_count':
						if( $result_type == 'category' )
						{
							echo '<li>'.sprintf( '%d points for the amount of posts in this category. Rule: number_of_posts > 30 ? 10 : intval( number_of_posts / 3 )', $score_map ).'</li>';
							break;
						}
						elseif( $result_type == 'tag' )
						{
							echo '<li>'.sprintf( '%d posts in this tag. Total points = sum( points ) * number_of_posts.', $score_map ).'</li>';
							break;
						}

					default:
						echo '<li>'.sprintf( '%d points for [%s]', $score_map, $result_part ).'</li>';
						break;
				}
			}
			continue;
		}

		if( $score_map['score'] == 0 )
		{
			continue;
		}

		foreach( $score_map['map'] as $match_type => $scores )
		{
			switch( $match_type )
			{
				case 'whole_term':
					echo '<li>'.sprintf( '%d points for whole term match in [%s]', $scores, $result_part ).'</li>';
					continue;

				case 'quoted_term_all':
					echo '<li>'.sprintf( '%d extra points for all quoted term match in [%s]', $scores, $result_part ).'</li>';
					continue;

				case 'all_case_sensitive':
					echo '<li>'.sprintf( '%d extra points for all word case sensitive match in [%s]', $scores, $result_part ).'</li>';
					continue;

				case 'all_whole_words':
					echo '<li>'.sprintf( '%d extra points for all word complete match in [%s]', $scores, $result_part ).'</li>';
					continue;
			}

			if( !is_array( $scores ) )
			{
				continue;
			}

			foreach( $scores as $word => $score )
			{
				switch( $match_type )
				{
					case 'word_case_sensitive_match':
						echo '<li>'.sprintf( '%d points for case sensitive match on [%s] in [%s]', 1, $word, $result_part ).'</li>';
						if( $score > 1 )
						{
							echo '<li>'.sprintf( '%d extra points for multiple case sensitive match on [%s] in [%s]', $score - 1, $word, $result_part ).'</li>';
						}
						break;

					case 'whole_word_match':
						echo '<li>'.sprintf( '%d points for whole word match on [%s] in [%s]', 1, $word, $result_part ).'</li>';
						if( $score > 1 )
						{
							echo '<li>'.sprintf( '%d extra points for multiple whole word match on [%s] in [%s]', $score - 1, $word, $result_part ).'</li>';
						}
						break;

					case 'word_case_insensitive_match':
						echo '<li>'.sprintf( '%d points for case insensitive match on [%s] in [%s]', 1, $word, $result_part ).'</li>';
						if( $score > 1 )
						{
							echo '<li>'.sprintf( '%d extra points for multiple case insensitive match on [%s] in [%s]', $score - 1, $word, $result_part ).'</li>';
						}
						break;
				}
			}
		}
	}
	echo '<li>'.sprintf( 'Total: %d points', $total_score ).'</li>';
	echo '</ul>';
}


/**
 * Add debug information about the search result scores
 *
 * @param array search_result
 */
function display_search_debug_info( $search_result )
{
	global $Debuglog;

	$Debuglog->add( sprintf('Number of search result: [%d]', count( $search_result ) ), 'info' );
	foreach( $search_result as $index => $result_value )
	{
		$score_map_key = $result_value['type'].'_'.$result_value['ID'];
		// Displaying the scores of the result map
		foreach( $result_value['scores_map'] as $search_key => $search_value )
		{
			if( is_array( $search_value ) )
			{
				if( $search_value['score'] == 0 )
				{
					continue;
				}
				foreach( $search_value['map'] as $score_key => $score_value )
				{
					if( is_array( $score_value ) )
					{
						foreach( $score_value as $key => $value  )
						{
							$Debuglog->add( sprintf('Score result: [%s]:[%s]:[%s]:[%s]:[%d]', $score_map_key, $search_key, $score_key, $key, $value), 'info' );
						}
					}
					else
					{
						$Debuglog->add( sprintf('Score result: [%s]:[%s]:[%s]:[%d]', $score_map_key, $search_key, $score_key, $score_value), 'info' );
					}
				}
			}
			else
			{
				$Debuglog->add( sprintf('Score result: [%s]:[%s]:[%d]', $score_map_key, $search_key, $search_value), 'info' );
			}
		}

		$Debuglog->add( sprintf('Result for [%s]: [Percentage:%d%%][Percentage score:%d][Total score:%d]', $score_map_key, $search_result[$index]['percentage'], $search_result[$index]['percentage_score'], $search_result[$index]['score']), 'info' );
	}
}