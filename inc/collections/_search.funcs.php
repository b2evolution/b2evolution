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
function score_text( $text, $search_term, $words = array(), $quoted_terms = array(), $score_weight = 1 )
{
	global $Debuglog;

	$score = 0.0;
	$scores_map = array();

	if( !empty( $search_term ) && strpos( $text, $search_term ) !== false )
	{
		// the max score what it may received in this methods
		$score = ( ( count( $words ) * 4 ) + ( count( $quoted_terms ) * 6 ) ) * $score_weight;
		$scores_map['whole_term'] = $score;
		return array( 'score' => $score, 'map' => $scores_map );
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
		if( $count === 0 )
		{
			$all_case_sensitive_match = false;
		}
		else
		{
			$scores_map['word_case_sensitive_match'][$word] = $score_weight;
			$score += $scores_map['word_case_sensitive_match'][$word];
		}

		if( $word_match_count = preg_match_all( '/\b'.$word.'\b/i', $text, $matches ) )
		{ // Every word match gives one more score
			$scores_map['whole_word_match'][$word] = $score_weight;
			$score += $scores_map['whole_word_match'][$word];
		}
		else
		{
			$all_whole_word_match = false;
		}

		if( $any_match_count = preg_match_all( '/'.$word.'/i', $text, $matches ) )
		{ // Every word match gives one more score
			$scores_map['word_case_insensitive_match'][$word] = $score_weight;
			$score += $scores_map['word_case_insensitive_match'][$word];
			if( $any_match_count > 1 )
			{ // there are multiple occurrences
				$scores_map['word_multiple_occurences'][$word] = score_multiple_occurences( $any_match_count, $score_weight );
				$score += $scores_map['word_multiple_occurences'][$word];
			}
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

	return array( 'score' => $score, 'score_weight' => $score_weight, 'map' => $scores_map );
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
 * Count score of multiple occurrences
 * The score is sum( $score_weight / x ) where x goes from 2 to match count
 *
 * @param integer match count
 * @param integer score weight
 * @return float multiple occurrences score based on the received parameters
 */
function score_multiple_occurences( $match_count, $score_weight )
{
	$result = 0.0;
	for( $i = 2; $i <= $match_count; $i++ )
	{
		$result = $result + floatval($score_weight) / floatval($match_count);
	}
	return $result;
}


/**
 * Get percentage value from the detailed result information
 *
 * @return number the calculated percentage value about result - search term fit
 */
function get_percentage_from_result_map( $type, $scores_map, $quoted_parts, $keywords )
{
	switch( $type )
	{
		case 'item':
			$searched_parts = array( 'title', 'content' );
			break;

		case 'comment':
			$searched_parts = array( 'item_title', 'content' );
			break;

		case 'category':
			$searched_parts = array( 'name', 'description' );
			break;

		case 'tag':
			$searched_parts = array( 'name' );
			break;

		default:
			debug_die( 'Invalid search type received!' );
	}

	// Check whole term match
	foreach( $searched_parts as $searched_part )
	{
		if( isset( $scores_map[$searched_part]['map']['whole_term'] ) )
		{ // The whole search term was found
			return 100;
		}
	}

	// Whole search term was not found, count percentage based on the matched parts
	$matched_quoted_parts = 0;
	foreach( $quoted_parts as $quoted_part )
	{
		foreach( $searched_parts as $searched_part )
		{
			if( isset( $scores_map[$searched_part]['map']['quoted_term'][$quoted_part] ) )
			{
				$matched_quoted_parts++;
				break; // go to the next quoted part
			}
		}
	}

	$matched_keywords = 0;
	foreach( $keywords as $keyword )
	{
		foreach( $searched_parts as $searched_part )
		{
			if( isset( $scores_map[$searched_part]['map']['word_case_insensitive_match'][$keyword] ) )
			{
				$matched_keywords++;
				break; // go to the next quoted part
			}
		}
	}

	// return round( ( $matched_keywords + ( 2 * $matched_quoted_parts ) ) * 100 / ( count( $keywords ) + ( 2 * count( $quoted_parts ) ) ) );
	return round( ( $matched_keywords + $matched_quoted_parts ) * 100 / ( count( $keywords ) + count( $quoted_parts ) ) );
}


/**
 * Search and score items
 *
 * @param string original search term
 * @param array all separated words from the search term
 * @param array all quoted parts from the search term
 * @param number max possible score
 */
function search_and_score_items( $search_term, $keywords, $quoted_parts )
{
	global $DB, $Blog, $posttypes_perms;

	// Exclude search from 'sidebar' type posts and from reserved type with ID 5000
	$filter_post_types = isset( $posttypes_perms['sidebar'] ) ? $posttypes_perms['sidebar'] : array();
	$filter_post_types = array_merge( $filter_post_types, array( 5000 ) );

	// Search between posts
	$search_ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), '', 'ItemCache', 'search_item' );
	$search_ItemList->set_filters( array(
			'keywords' => $search_term,
			'phrase' => 'OR',
			'types'  => '-'.implode( ',', $filter_post_types ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 1000
		) );
	$search_ItemList->query_init();

	$search_query = 'SELECT DISTINCT post_ID, post_datemodified, post_title, post_content, user_login as creator_login'
		.$search_ItemList->ItemQuery->get_from()
		.' LEFT JOIN T_users ON post_creator_user_ID = user_ID'
		.$search_ItemList->ItemQuery->get_where()
		.$search_ItemList->ItemQuery->get_group_by()
		.$search_ItemList->ItemQuery->get_order_by()
		.$search_ItemList->ItemQuery->get_limit();

	$query_result = $DB->get_results( $search_query, OBJECT, 'Search items query' );

	$search_result = array();
	foreach( $query_result as $row )
	{
		$scores_map = array();

		$scores_map['title'] = score_text( $row->post_title, $search_term, $keywords, $quoted_parts, 5 );
		$scores_map['content'] = score_text( $row->post_content, $search_term, $keywords, $quoted_parts );
		if( !empty( $search_term ) && !empty( $row->creator_login ) && strpos( $row->creator_login, $search_term ) !== false )
		{
			$scores_map['creator_login'] = 5;
		}
		$scores_map['last_mod_date'] = score_date( $row->post_datemodified );

		$final_score = $scores_map['title']['score']
			+ $scores_map['content']['score']
			+ ( isset( $scores_map['creator_login'] ) ? $scores_map['creator_login'] : 0 )
			+ $scores_map['last_mod_date'];

		$search_result[] = array(
			'type' => 'item',
			'score' => $final_score,
			'ID' => $row->post_ID,
			'scores_map' => $scores_map,
		);
	}

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
function search_and_score_comments( $search_term, $keywords, $quoted_parts )
{
	global $DB, $Blog;

	// Search between comments
	$search_CommentList = new CommentList2( $Blog, '', 'CommentCache', 'search_comment' );
	$search_CommentList->set_filters( array(
			'keywords' => $search_term,
			'phrase' => 'OR',
			'order_by' => 'date',
			'order' => 'DESC',
			'comments' => 1000
		) );
	$search_CommentList->query_init();

	$search_query = 'SELECT comment_ID, post_title, comment_content, comment_date,
	 	IFNULL(comment_author, user_login) as author'
		.$search_CommentList->CommentQuery->get_from()
		.' LEFT JOIN T_items__item ON comment_item_ID = post_ID'
		.' LEFT JOIN T_users ON post_creator_user_ID = user_ID'
		.$search_CommentList->CommentQuery->get_where()
		.$search_CommentList->CommentQuery->get_group_by()
		.$search_CommentList->CommentQuery->get_order_by()
		.$search_CommentList->CommentQuery->get_limit();

	$query_result = $DB->get_results( $search_query, OBJECT, 'Search comments query' );

	$search_result = array();
	foreach( $query_result as $row )
	{
		$scores_map = array();

		$scores_map['item_title'] = score_text( $row->post_title, $search_term, $keywords, $quoted_parts );
		$scores_map['content'] = score_text( $row->comment_content, $search_term, $keywords, $quoted_parts );
		if( !empty( $row->author ) && !empty( $search_term ) && strpos( $row->author, $search_term ) !== false )
		{
			$scores_map['author_name'] = 5;
		}
		$scores_map['creation_date'] = score_date( $row->comment_date );

		$final_score = $scores_map['item_title']['score']
			+ $scores_map['content']['score']
			+ ( isset( $scores_map['author_name'] ) ? $scores_map['author_name'] : 0 )
			+ $scores_map['creation_date'];

		$search_result[] = array(
			'type' => 'comment',
			'score' => $final_score,
			'ID' => $row->comment_ID,
			'scores_map' => $scores_map
		);
	}

	return $search_result;
}


function search_and_score_chapters_and_tags( $search_term, $keywords, $quoted_parts )
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
	$ChapterCache->clear();
	$cat_where_condition = '( cat_blog_ID = '.$DB->quote( $Blog->ID ).' ) AND ('.$cat_where_condition.' )';
	$ChapterCache->load_where( $cat_where_condition );
	while( ( $iterator_Chapter = & $ChapterCache->get_next() ) != NULL )
	{
		$scores_map = array();
		$scores_map['name'] = score_text( $iterator_Chapter->get( 'name' ), $search_term, $keywords, $quoted_parts, 3 );
		$scores_map['description'] = score_text( $iterator_Chapter->get( 'description' ), $search_term, $keywords, $quoted_parts );

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

		$search_result[] = array(
			'type' => 'category',
			'score' => $final_score,
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
		$scores_map['name'] = score_text( $tag_name, $search_term, $keywords, $quoted_parts, 3 );
		$scores_map['post_count'] = $post_count;
		$final_score = $scores_map['name']['score'] * $post_count;

		$search_result[] = array(
			'type' => 'tag',
			'score' => $final_score,
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
	$keywords = trim( $search_keywords );
	if( empty( $keywords ) )
	{
		return array();
	}

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

	if( isset( $keywords[0] ) && empty( $keywords[0] ) )
	{ // The first item is an empty string when the search keyword contains only irrelevant characters
		unset( $keywords[0] ); // remove empty word
	}
	if( empty( $keywords ) && empty( $quoted_parts ) )
	{ // There is nothing to search for
		return array();
	}

	$search_result = search_and_score_items( $search_keywords, $keywords, $quoted_parts );
	$nr_of_items = count( $search_result );

	$comment_search_result = search_and_score_comments( $search_keywords, $keywords, $quoted_parts );
	$search_result = array_merge( $search_result, $comment_search_result );

	$cats_and_tags_search_result = search_and_score_chapters_and_tags( $search_keywords, $keywords, $quoted_parts );
	$search_result = array_merge( $search_result, $cats_and_tags_search_result );

	$score_result = array();
	foreach( $search_result as $result_item )
	{
		$score_result[] = $result_item['score'];
	}
	array_multisort( $score_result, SORT_DESC, $search_result );

	if( count( $search_result ) > 0 )
	{
		$first_result = $search_result[0];
		$max_percentage = get_percentage_from_result_map( $first_result['type'], $first_result['scores_map'], $quoted_parts, $keywords );
		$search_result[0]['percentage'] = $max_percentage;
		$search_result[0]['nr_of_items'] = $nr_of_items;
		$search_result[0]['nr_of_comments'] = count( $comment_search_result );
		$search_result[0]['nr_of_cats_and_tags'] = count( $cats_and_tags_search_result );
	}

	return $search_result;
}


/*
 * Display the search result block
 *
 * @param array Params
 */
function search_result_block( $params = array() )
{
	global $Blog, $Session, $debug;

	evo_flush(); // flush displayed data before start searching

	$search_keywords = param( 's', 'string', '', true );
	$search_params = $Session->get( 'search_params' );
	$search_result = $Session->get( 'search_result' );
	$search_result_loaded = false;
	if( empty( $search_params ) || ( $search_params['search_keywords'] != $search_keywords )
		|| ( $search_params['search_blog'] != $Blog->ID ) || ( $search_result === NULL ) )
	{ // this is a new search
		$search_params = array( 'search_keywords' => $search_keywords, 'search_blog' => $Blog->ID );
		$search_result = score_search_result( $search_keywords );
		$Session->set( 'search_params', $search_params );
		$Session->set( 'search_result', $search_result );
		$search_result_loaded = true;
	}

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'title_suffix_post'     => ' ('.T_('Post').')',
			'title_suffix_comment'  => ' ('.T_('Comment').')',
			'title_suffix_category' => ' ('.T_('Category').')',
			'title_suffix_tag'      => ' ('.T_('Tag').')',
			'block_start'           => '',
			'block_end'             => '',
			'pagination'            => array(),
			'use_editor'            => false, // Use editor instead of author if it is allowed (only the posts have an editor)
			'author_format'         => 'avatar_name', // @see User::get_identity_link() // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
			'date_format'           => locale_datefmt(),
		), $params );

	$search_result = $Session->get( 'search_result' );
	if( empty( $search_result ) )
	{
		echo '<p class="msg_nothing" style="margin: 2em 0">';
		echo T_('Sorry, we could not find anything matching your request, please try to broaden your search.');
		echo '<p>';
		return;
	}

	if( $debug )
	{
		echo '<div class="muted" style="margin: 2em 0">';
		echo 'Total processed result items by type:';
		echo '<ul><li>'.sprintf( '%d posts', $search_result[0]['nr_of_items'] ).'</li>';
		echo '<li>'.sprintf( '%d comments', $search_result[0]['nr_of_comments'] ).'</li>';
		echo '<li>'.sprintf(  '%d chapters and tags', $search_result[0]['nr_of_cats_and_tags'] ).'</li></ul>';
		echo '</div>';

		// show how many items are processed
		evo_flush();
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
			'total'        => $result_count,
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
			'list_span'    => 11, // Number of visible pages on navigation line
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

	// Get best result values
	$max_percentage = $search_result[0]['percentage'];
	$max_score = $search_result[0]['score'];

	for( $index = $from; $index < $to; $index++ )
	{
		$row = $search_result[ $index ];
		switch( $row['type'] )
		{
			case 'item':
				$Item = $ItemCache->get_by_ID( $row['ID'], false );
				if( empty( $Item ) )
				{ // This Item was deleted, since the search process was executed
					continue 2; // skip from switch and skip to the next item in loop
				}
				$display_params = array(
					'title'   => $Item->get_title( array( 'link_type' => 'permalink' ) ).$params['title_suffix_post'],
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
				$Comment = $CommentCache->get_by_ID( $row['ID'], false );
				if( empty( $Comment ) || ( $Comment->status == 'trash' ) )
				{ // This Comment was deleted, since the search process was executed
					continue 2; // skip from switch and skip to the next item in loop
				}
				$display_params = array(
					'title'   => $Comment->get_permanent_link( '#item#' ).$params['title_suffix_comment'],
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
				$Chapter = $ChapterCache->get_by_ID( $row['ID'], false );
				if( empty( $Chapter ) )
				{ // This Chapter was deleted, since the search process was executed
					continue 2; // skip from switch and skip to the next item in loop
				}
				$display_params = array(
					'title'   => '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get_name().'</a>'.$params['title_suffix_category'],
					'excerpt' => excerpt( $Chapter->get( 'description' ) ),
				);
				break;

			case 'tag':
				list( $tag_name, $post_count ) = explode( ':', $row['ID'] );
				$display_params = array(
					'title'   => '<a href="'.url_add_param( $Blog->gen_blogurl(), 'tag='.$tag_name ).'">'.$tag_name.'</a>'.$params['title_suffix_tag'],
					'excerpt' => sprintf( T_('%d posts are tagged with \'%s\''), $post_count, $tag_name ),
				);

			default: // Other type of result is not implemented
				continue 2;
		}

		$display_params['score'] = $row['score'];
		$display_params['percentage'] = isset( $row['percentage'] ) ? $row['percentage'] : round( $row['score'] * $max_percentage / $max_score );
		$display_params['scores_map'] = $row['scores_map'];
		$display_params['type'] = $row['type'];
		$display_params['best_result'] = $index == 0;
		$display_params['max_score'] = sprintf( ( floor( $max_score ) != $max_score ) ? '%.2f' : '%d', $max_score );
		$display_params['max_percentage'] = $max_percentage;
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

	$page_list_span = $params['list_span'];
	$total_pages = $params['total_pages'];
	$current_page = isset( $params['current_page'] ) ? $params['current_page'] : 1;
	$page_url = regenerate_url( 'page', '' );

	// Initialize a start of pages list:
	if( $current_page <= intval( $page_list_span / 2 ) )
	{ // the current page number is small
		$page_list_start = 1;
	}
	elseif( $current_page > $total_pages - intval( $page_list_span / 2 ) )
	{ // the current page number is big
		$page_list_start = max( 1, $total_pages - $page_list_span+1);
	}
	else
	{ // the current page number can be centered
		$page_list_start = $current_page - intval( $page_list_span / 2 );
	}

	// Initialize an end of pages list:
	if( $current_page > $total_pages - intval( $page_list_span / 2 ) )
	{ //the current page number is big
		$page_list_end = $total_pages;
	}
	else
	{
		$page_list_end = min( $total_pages, $page_list_start + $page_list_span - 1 );
	}

	echo $params['block_start'];

	if( $current_page > 1 )
	{ // A link to previous page:
		echo $params['page_item_before'];
		$prev_attrs = empty( $params['prev_class'] ) ? '' : ' class="'.$params['prev_class'].'"';
		echo '<a href="'.url_add_param( $page_url, 'page='.( $current_page - 1 ) ).'" rel="prev"'.$prev_attrs.'>'.$params['prev_text'].'</a>';
		echo $params['page_item_after'];
	}

	if( $page_list_start > 1 )
	{ // The pages list doesn't contain the first page
		// Display a link to first page:
		echo $params['page_item_before'];
		echo '<a href="'.url_add_param( $page_url, 'page=1' ).'">1</a>';
		echo $params['page_item_after'];

		if( $page_list_start > 2 )
		{ // Display a link to previous pages range:
			$page_no = ceil( $page_list_start / 2 );
			echo $params['page_item_before'];
			echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
			echo $params['page_item_after'];
		}
	}

	$page_prev_i = $current_page - 1;
	$page_next_i = $current_page + 1;
	for( $i = $page_list_start; $i <= $page_list_end; $i++ )
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

	if( $page_list_end < $total_pages )
	{ // The pages list doesn't contain the last page
		if( $page_list_end < $total_pages - 1 )
		{ // Display a link to next pages range:
			$page_no = $page_list_end + floor( ( $total_pages - $page_list_end ) / 2 );
			echo $params['page_item_before'];
			echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
			echo $params['page_item_after'];
		}

		// Display a link to last page:
		echo $params['page_item_before'];
		echo '<a href="'.url_add_param( $page_url, 'page='.$total_pages ).'">'.$total_pages.'</a>';
		echo $params['page_item_after'];
	}

	if( $current_page < $total_pages )
	{ // A link to next page:
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

	echo '<div class="search_result_score dimmed">'.$params['percentage'].'%</div>';

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
		display_score_map( $params );
	}

	echo $params['row_end'];
}


/**
 * Display score map regarding a search result
 *
 * @param array detailed information about the received search scores
 */
function display_score_map( $params )
{
	echo '<ul class="search_score_map dimmed">';
	foreach( $params['scores_map'] as $result_part => $score_map )
	{

		if( ! is_array( $score_map ) )
		{
			if( $score_map > 0 )
			{ // Score received for this field
				echo '<li>'.sprintf( 'Extra points for [%s]', $result_part ).'</li><ul>';
				switch ( $result_part )
				{
					case 'last_mod_date':
					case 'creation_date':
						echo '<li>'.sprintf( '%d points.', $score_map );
						echo ' Rule: The number of points are calculated based on the days passed since creation or last modification';
						echo '<ul><li>days_passed < 5 => ( 8 - days_passed )</li>';
						echo '<li>5 <= days_passed < 8 => 3</li>';
						echo '<li>when days_passed >= 8: ( days_passed < 15 ? 2 : ( days_passed < 30 ? 1 : 0 ) )</li>';
						echo '</ul>';
						break;

					case 'post_count':
						if( $params['type'] == 'category' )
						{
							echo '<li>'.sprintf( '%d points for the amount of posts in this category. Rule: number_of_posts > 30 ? 10 : intval( number_of_posts / 3 )', $score_map ).'</li>';
							break;
						}
						elseif( $params['type'] == 'tag' )
						{
							echo '<li>'.sprintf( '%d posts in this tag. Total points = sum( points ) * number_of_posts.', $score_map ).'</li>';
							break;
						}

					default:
						echo '<li>'.sprintf( '%d points for [%s]', $score_map, $result_part ).'</li>';
						break;
				}
				echo '</ul>';
			}
			continue;
		}
		elseif( isset( $score_map['score_weight'] ) && $score_map['score_weight'] > 1 )
		{ // add note that the score was multiplied because of the importance of the field where it was found
			$note =  sprintf( ' - the match scores are multiplied with %d', $score_map['score_weight'] );
		}
		else
		{ // note is not required
			$note = '';
		}

		echo '<li>'.sprintf( 'Searching in [%s]', $result_part ).$note.'</li><ul>';
		if( $score_map['score'] == 0 )
		{
			echo '</ul>';
			continue;
		}

		$keyword_match = null;
		foreach( $score_map['map'] as $match_type => $scores )
		{
			switch( $match_type )
			{
				case 'whole_term':
					echo '<li>'.sprintf( '%d points for whole term match', $scores ).'</li>';
					continue;

				case 'quoted_term_all':
					echo '<li>'.sprintf( '%d extra points for all quoted term match', $scores ).'</li>';
					continue;

				case 'all_case_sensitive':
					echo '<li>'.sprintf( '%d extra points for all word case sensitive match', $scores ).'</li>';
					continue;

				case 'all_whole_words':
					echo '<li>'.sprintf( '%d extra points for all word complete match', $scores ).'</li>';
					continue;
			}

			if( !is_array( $scores ) )
			{
				continue;
			}

			if( $keyword_match != $match_type && $keyword_match !== null )
			{ // close previously started list
				echo '</ul>';
			}
			if( $keyword_match != $match_type )
			{
				switch( $match_type )
				{
					case 'word_case_sensitive_match':
						echo '<li>Case sensitive mathces</li>';
						break;

					case 'whole_word_match':
						echo '<li>Whole word mathces</li>';
						break;

					case 'word_case_insensitive_match':
						echo '<li>Case insensitive mathces</li>';
						break;

					case 'word_multiple_occurences':
						echo '<li>Extra points for multiple occurrences - Rule: sum( score weight / x ) where x goes from 2 to number of occurrences</li>';
						break;
				}
				$keyword_match = $match_type;
				echo '<ul>';
			}

			foreach( $scores as $word => $score )
			{
				if( is_float( $score ) )
				{
					$points_label = '%.2F points - match on [%s]';
				}
				else
				{
					$points_label = ( $score > 1 ) ? '%d points - match on [%s]' : '%d point - match on [%s]';
				}
				echo '<li>'.sprintf( $points_label, $score, $word ).'</li>';
			}
		}
		if( $keyword_match != null )
		{ // display the end of the specific match type
			echo '</ul>';
		}
		echo '</ul>';
	}
	$total_score_pattern = ( floor( $params['score'] ) != $params['score'] ) ? '%.2f' : '%d';
	echo '<li>'.sprintf( 'Total: '.$total_score_pattern.' points', $params['score'] ).'</li>';
	if( $params['best_result'] )
	{
		echo '<ul><li>This is the best result. Percentage value is calculated based on the number of matching words and mathcing quoted terms.</li>';
		echo '<li>Note: In case of the number of mathcing words even case insensitive partial matches are counted.</li>';
		echo '<li>Percentage = ( Number of matching words + Number of matching quoted terms ) * 100 / ( Number of words in search text + Number of quoted terms in search text )</li></ul>';
	}
	else
	{
		echo '<ul><li>Percentage value is calculated based on the received points compared to the best result</li>';
		echo '<li>'.sprintf( 'Percentage [%d%%] = ( This result total points ['.$total_score_pattern.'] ) * ( Best result percentage [%d] ) / ( Best result total points [%s] )', $params['percentage'], $params['score'], $params['max_percentage'], $params['max_score'] ).'</li></ul>';
	}
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