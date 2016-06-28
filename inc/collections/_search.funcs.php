<?php
/**
 * This file implements misc functions that handle search for posts, comments, categories, etc.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

		if( $word_match_count = preg_match_all( '/\b'.preg_quote( $word, '/' ).'\b/i', $text, $matches ) )
		{ // Every word match gives one more score
			$scores_map['whole_word_match'][$word] = $score_weight;
			$score += $scores_map['whole_word_match'][$word];
		}
		else
		{
			$all_whole_word_match = false;
		}

		if( $any_match_count = preg_match_all( '/'.preg_quote( $word, '/' ).'/i', $text, $matches ) )
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
 * Return a search score for the given text and search keywords
 *
 * @param string Text to score
 * @param string The search keywords to score by
 * @param integer Score multiplier
 * @return integer Result score
 */
function score_tags( $tag_name, $search_term, $score_weight = 4 )
{
	$score = 0.0;
	$scores_map = array();

	if( $tag_name == utf8_trim( $search_term ) )
	{	// We use only EXACT match for post tags:
		$score = $score_weight;
		$scores_map['tags'] = $score;
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
			$searched_parts = array( 'title', 'content', 'tags', 'excerpt', 'titletag' );
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
	global $DB, $Blog;

	// Prepare filters:
	$search_ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), '', 'ItemCache', 'search_item' );
	$search_ItemList->set_filters( array(
			'keywords'      => $search_term,
			'keyword_scope' => 'title,content,tags,excerpt,titletag', // TODO: add more fields
			'phrase'        => 'OR',
			'itemtype_usage'=> '-sidebar', // Exclude from search: 'sidebar' item types
			'orderby'       => 'datemodified',
			'order'         => 'DESC',
			'posts'         => 1000
		) );

	// Generate query from filters above and count results:
	$search_ItemList->query_init();

	// Make a custom search query:
	$search_query = 'SELECT DISTINCT post_ID, post_datemodified, post_title, post_content,'
		.' user_login as creator_login, tag_name, post_excerpt, post_titletag'
		.$search_ItemList->ItemQuery->get_from()
		.' LEFT JOIN T_users ON post_creator_user_ID = user_ID'
		.$search_ItemList->ItemQuery->get_where()
		.$search_ItemList->ItemQuery->get_group_by()
		.$search_ItemList->ItemQuery->get_order_by()
		.$search_ItemList->ItemQuery->get_limit();

	// Run query:
	$query_result = $DB->get_results( $search_query, OBJECT, 'Search items query' );

	// Compute scores:
	$search_result = array();
	foreach( $query_result as $row )
	{
		$scores_map = array();

		$scores_map['title'] = score_text( $row->post_title, $search_term, $keywords, $quoted_parts, /* multiplier: */ 5 );
		$scores_map['content'] = score_text( $row->post_content, $search_term, $keywords, $quoted_parts );
		$scores_map['tags'] = score_tags( $row->tag_name, $search_term, /* multiplier: */ 4 );
		$scores_map['excerpt'] = score_text( $row->post_excerpt, $search_term, $keywords, $quoted_parts );
		$scores_map['titletag'] = score_text( $row->post_titletag, $search_term, $keywords, $quoted_parts, 4 );
		if( !empty( $search_term ) && !empty( $row->creator_login ) && strpos( $row->creator_login, $search_term ) !== false )
		{
			$scores_map['creator_login'] = 5;
		}
		$scores_map['last_mod_date'] = score_date( $row->post_datemodified );

		$final_score = $scores_map['title']['score']
			+ $scores_map['content']['score']
			+ $scores_map['tags']['score']
			+ $scores_map['excerpt']['score']
			+ $scores_map['titletag']['score']
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


/**
 * Search and score chapters
 *
 * @param string original search term
 * @param array all separated words from the search term
 * @param array all quoted parts from the search term
 * @param number max possible score
 */
function search_and_score_chapters( $search_term, $keywords, $quoted_parts )
{
	global $DB, $Blog;

	// Init result array:
	$search_result = array();

	// Set query conditions:
	$or = '';
	$cat_where_condition = '';
	foreach( $keywords as $keyword )
	{
		$keyword = $DB->escape( $keyword );
		$cat_where_condition .= $or.' ( cat_name LIKE \'%'.$keyword.'%\' OR cat_description LIKE \'%'.$keyword.'%\' )';
		$or = ' OR';
	}

	// Search between chapters:
	$ChapterCache = & get_ChapterCache();
	$ChapterCache->clear();
	$cat_where_condition = '( cat_blog_ID = '.$DB->quote( $Blog->ID ).' ) AND ( '.$cat_where_condition.' )';
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
			'type'       => 'category',
			'score'      => $final_score,
			'ID'         => $iterator_Chapter->ID,
			'scores_map' => $scores_map
		);
	}

	return $search_result;
}


/**
 * Search and score tags
 *
 * @param string original search term
 * @param array all separated words from the search term
 * @param array all quoted parts from the search term
 * @param number max possible score
 */
function search_and_score_tags( $search_term, $keywords, $quoted_parts )
{
	global $DB, $Blog;

	// Init result array:
	$search_result = array();

	// Set query conditions:
	$or = '';
	$tag_where_condition = '';
	foreach( $keywords as $keyword )
	{
		$tag_where_condition .= $or.'tag_name LIKE \'%'.$DB->escape( $keyword ).'%\'';
		$or = ' OR ';
	}

	// Search between tags:
	$tags_SQL = new SQL();
	$tags_SQL->SELECT( 'tag_ID, tag_name, COUNT( DISTINCT itag_itm_ID ) AS post_count' );
	$tags_SQL->FROM( 'T_items__tag' );
	$tags_SQL->FROM_add( 'INNER JOIN T_items__itemtag ON itag_tag_ID = tag_ID' );
	$tags_SQL->FROM_add( 'INNER JOIN T_postcats ON itag_itm_ID = postcat_post_ID' );
	$tags_SQL->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );
	$tags_SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $Blog->ID ) );
	$tags_SQL->WHERE_and( $tag_where_condition );
	$tags_SQL->GROUP_BY( 'tag_name' );
	$tags = $DB->get_results( $tags_SQL->get(), OBJECT, 'Get tags matching to the search keywords' );

	foreach( $tags as $tag )
	{
		if( $tag->post_count == 0 )
		{	// Count only those tags which have at least one post linked to it, Skip this:
			continue;
		}

		$scores_map = array();
		$scores_map['name'] = score_text( $tag->tag_name, $search_term, $keywords, $quoted_parts, 3 );
		$scores_map['post_count'] = $tag->post_count;
		$final_score = $scores_map['name']['score'] * $tag->post_count;

		$search_result[] = array(
			'type'       => 'tag',
			'score'      => $final_score,
			'ID'         => $tag->tag_ID,
			'name'       => $tag->tag_name.','.$tag->post_count,
			'scores_map' => $scores_map,
		);
	}

	return $search_result;
}


/**
 * Perform a scrored search
 * This searches matching objects and gives a match-quality-score to each found object
 *
 * @param string the search keywords
 * @param string What types search: 'all', 'item', 'comment', 'category', 'tag'
 *               Use ','(comma) as separator to use several kinds, e.g: 'item,comment' or 'tag,comment,category'
 * @return array scored search result, each element is an array( type, ID, score )
 */
function perform_scored_search( $search_keywords, $search_types = 'all' )
{
	$keywords = trim( $search_keywords );
	if( empty( $keywords ) )
	{
		return array();
	}

	global $Blog, $DB, $debug;
	global $scores_map, $score_prefix, $score_map_key, $Debuglog;

	// Get quoted parts parts of the search query
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

	// Remove word separators:
	$keywords = preg_replace( '/, +/', '', $search_keywords );
	$keywords = str_replace( ',', ' ', $keywords );
	// Remove quotes:
	$keywords = str_replace( '"', ' ', $keywords );
	// Remove extra white space:
	$keywords = trim( $keywords );
	// Split keywords:
	$keywords = preg_split( '/\s+/', $keywords );
	// Remove duplicate keywords:
	$keywords = array_unique( $keywords );

	if( isset( $keywords[0] ) && empty( $keywords[0] ) )
	{ // The first item is an empty string when the search keyword contains only irrelevant characters
		unset( $keywords[0] ); // remove empty word
	}
	if( empty( $keywords ) && empty( $quoted_parts ) )
	{ // There is nothing to search for
		return array();
// TODO: return NULL and display a specific error message like "Please enter some keywords to search."
	}

	if( $search_types == 'all' )
	{	// Search all result types:
		$search_type_item = true;
		$search_type_comment = true;
		$search_type_category = true;
		$search_type_tag = true;
	}
	else
	{	// Check what types should be searched:
		$search_types = explode( ',', $search_types );
		$search_type_item = in_array( 'item', $search_types );
		$search_type_comment = in_array( 'comment', $search_types );
		$search_type_category = in_array( 'category', $search_types );
		$search_type_tag = in_array( 'tag', $search_types );
	}

	$search_result = array();

	if( $search_type_item )
	{	// Perform search on Items:
		$item_search_result = search_and_score_items( $search_keywords, $keywords, $quoted_parts );
		$search_result = $item_search_result;
		if( $debug )
		{
			echo '<p class="text-muted">Just found '.count( $item_search_result ).' Items.</p>';
			evo_flush();
		}
	}

	if( $search_type_comment )
	{	// Perform search on Comments:
		$comment_search_result = search_and_score_comments( $search_keywords, $keywords, $quoted_parts );
		$search_result = array_merge( $search_result, $comment_search_result );
		if( $debug )
		{
			echo '<p class="text-muted">Just found '.count( $comment_search_result ).' Comments.</p>';
			evo_flush();
		}
	}

	if( $search_type_category )
	{	// Perform search on Chapters:
		$cats_search_result = search_and_score_chapters( $search_keywords, $keywords, $quoted_parts );
		$search_result = array_merge( $search_result, $cats_search_result );
		if( $debug )
		{
			echo '<p class="text-muted">Just found '.count( $cats_search_result ).' Catageories.</p>';
			evo_flush();
		}
	}

	if( $search_type_tag )
	{	// Perform search on Tags:
		$tags_search_result = search_and_score_tags( $search_keywords, $keywords, $quoted_parts );
		$search_result = array_merge( $search_result, $tags_search_result );
		if( $debug )
		{
			echo '<p class="text-muted">Just found '.count( $tags_search_result ).' Tags.</p>';
			evo_flush();
		}
	}

	// Sort results by score:
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
		if( $search_type_item )
		{
			$search_result[0]['nr_of_items'] = count( $item_search_result );
		}
		if( $search_type_comment )
		{
			$search_result[0]['nr_of_comments'] = count( $comment_search_result );
		}
		if( $search_type_category )
		{
			$search_result[0]['nr_of_cats'] = count( $cats_search_result );
		}
		if( $search_type_tag )
		{
			$search_result[0]['nr_of_tags'] = count( $tags_search_result );
		}
	}

	return $search_result;
}


/*
 * Perform search (after having displayed the first part of the page) & display results.
 * The search results are cached in the session for faster page by page navigation.
 *
 * @param array Display Params
 */
function search_result_block( $params = array() )
{
	global $Blog, $Session, $debug;

	$search_keywords = param( 's', 'string', '', true );

	// Try to load existing search results from Session:
	$search_params = $Session->get( 'search_params' );
	$search_result = $Session->get( 'search_result' );
	$search_result_loaded = false;
	if( empty( $search_params )
		|| ( $search_params['search_keywords'] != $search_keywords )	// We had saved search results but for a different search string
		|| ( $search_params['search_blog'] != $Blog->ID ) 		// We had saved search results but for a different collection
		|| ( $search_result === NULL ) )
	{ // We need to perform a new search:
		if( $debug )
		{
			echo '<p class="text-muted">Starting a new search...</p>';
		}

		// Flush first part of the page before starting search, which can be long...
		evo_flush();

		$search_params = array(
			'search_keywords' => $search_keywords,
			'search_blog' => $Blog->ID
		);

		// Perform new search:
		$search_result = perform_scored_search( $search_keywords );

		// Save results into session:
		$Session->set( 'search_params', $search_params );
		$Session->set( 'search_result', $search_result );
		$search_result_loaded = true;
	}
	else
	{ // We found the desired saved search results in the Session:
		if( $debug )
		{	// Display counts
			echo '<div class="text-muted">';
			echo '<p>We found the desired saved search results in the Session:</p>';
			echo '<ul><li>'.sprintf( '%d posts', empty( $search_result[0]['nr_of_items'] ) ? 0 : $search_result[0]['nr_of_items'] ).'</li>';
			echo '<li>'.sprintf( '%d comments', empty( $search_result[0]['nr_of_comments'] ) ? 0 : $search_result[0]['nr_of_comments'] ).'</li>';
			echo '<li>'.sprintf( '%d chapters', empty( $search_result[0]['nr_of_cats'] ) ? 0 : $search_result[0]['nr_of_cats'] ).'</li>';
			echo '<li>'.sprintf( '%d tags', empty( $search_result[0]['nr_of_tags'] ) ? 0 : $search_result[0]['nr_of_tags'] ).'</li></ul>';
			echo '</div>';
		}

		// Flush first part of the page before starting search, which can be long...
		evo_flush();
	}


	// Make sure we are not missing any display params:
	$params = array_merge( array(
			'no_match_message'      =>  '<p class="alert alert-info msg_nothing" style="margin: 2em 0">'.T_('Sorry, we could not find anything matching your request, please try to broaden your search.').'<p>',
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
		echo $params['no_match_message'];
		return;
	}

	// Prepare pagination:
	$result_count = count( $search_result );
	$result_per_page = $Blog->get_setting( 'search_per_page' );
	if( $result_count > $result_per_page )
	{	// We will have multiple search result pages:
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
	{	// Only one page of results:
		$current_page = 1;
		$total_pages = 1;
	}

	// Set current page indexes:
	$from = ( ( $current_page -1 ) * $result_per_page );
	$to = ( $current_page < $total_pages ) ? ( $from + $result_per_page ) : ( $result_count );


	// Init caches
	$ItemCache = & get_ItemCache();
	$CommentCache = & get_CommentCache();
	$ChapterCache = & get_ChapterCache();


	if( !$search_result_loaded )
	{ // Search result objects are not loaded into memory yet, load them
		// Group required object ids by type:
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

		// Load each required object into the corresponding cache:
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

				// TODO: we'll probably load "tag" objects once we support tag-synonyms.

				default: // Not handled search result type
					break;
			}
		}
	}

	// ----------- Display ------------

	echo $params['block_start'];

	// Memorize best scores:
	$max_percentage = $search_result[0]['percentage'];
	$max_score = $search_result[0]['score'];

	// Display results for current page:
	for( $index = $from; $index < $to; $index++ )
	{
		$row = $search_result[ $index ];
		switch( $row['type'] )
		{
			case 'item':
				// Prepare to display an Item:

				$Item = $ItemCache->get_by_ID( $row['ID'], false );

				if( empty( $Item ) )
				{ // This Item was deleted, since the search process was executed
					continue 2; // skip from switch and skip to the next item in loop
				}

				$display_params = array(
					'title'   => $Item->get_title( array( 'link_type' => 'permalink' ) ).$params['title_suffix_post'],
					'excerpt' => $Item->get_excerpt2(),
					'chapter' => sprintf( T_('In %s'), $Item->get_chapter_links() ),
				);

				if( $params['use_editor'] )
				{ // Get editor info to display:
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
				{ // Get author info to display:
					$creator_User = & $Item->get_creator_User();
					$display_params = array_merge( array(
							'author'        => $creator_User->get_identity_link( array( 'link_text' => $params['author_format'] ) ),
							'creation_date' => mysql2date( $params['date_format'], $Item->datecreated ),
							'lastedit_date' => mysql2date( $params['date_format'], $Item->datemodified ),
						), $display_params );
				}
				break;

			case 'comment':
				// Prepare to display a Comment:

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
				// Prepare to display a Category:

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
				// Prepare to display a Tag:

				list( $tag_name, $post_count ) = explode( ',', $row['name'] );
				$display_params = array(
					'title'   => '<a href="'.url_add_param( $Blog->gen_blogurl(), 'tag='.$tag_name ).'">'.$tag_name.'</a>'.$params['title_suffix_tag'],
					'excerpt' => sprintf( T_('%d posts are tagged with \'%s\''), $post_count, $tag_name ),
				);
				break;

			default:
				// Other type of result is not implemented

				// TODO: maybe find collections (especially in case of aggregation)? users? files?

				continue 2;
		}

		// Common display params for all types:
		$display_params['score'] = $row['score'];
		$display_params['percentage'] = isset( $row['percentage'] ) ? $row['percentage'] : round( $row['score'] * $max_percentage / $max_score );
		$display_params['scores_map'] = $row['scores_map'];
		$display_params['type'] = $row['type'];
		$display_params['best_result'] = $index == 0;
		$display_params['max_score'] = sprintf( ( floor( $max_score ) != $max_score ) ? '%.2f' : '%d', $max_score );
		$display_params['max_percentage'] = $max_percentage;

		// Display one search result:
		display_search_result( array_merge( $params, $display_params ) );
	}

	echo $params['block_end'];

	// Display pagination:
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
			'page_item_current_before' => ' ',
			'page_item_current_after'  => '',
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
		echo add_tag_class( $params['page_item_before'], 'listnav_prev' );
		$prev_attrs = empty( $params['prev_class'] ) ? '' : ' class="'.$params['prev_class'].'"';
		echo '<a href="'.url_add_param( $page_url, 'page='.( $current_page - 1 ) ).'" rel="prev"'.$prev_attrs.'>'.$params['prev_text'].'</a>';
		echo $params['page_item_after'];
	}

	// Display a link to first page:
	if( $current_page == 1 )
	{
		echo add_tag_class( $params['page_item_current_before'], 'listnav_first' );
		echo '<a href="'.url_add_param( $page_url, 'page=1' ).'">1</a>';
		echo $params['page_item_current_after'];
	}
	else
	{
		echo add_tag_class( $params['page_item_before'], 'listnav_first' );
		echo '<a href="'.url_add_param( $page_url, 'page=1' ).'">1</a>';
		echo $params['page_item_after'];
	}

	if( $page_list_start > 2 )
	{ // Display a link to previous pages range:
		$page_no = ceil( $page_list_start / 2 );
		echo add_tag_class( $params['page_item_before'], 'listnav_prev_list' );
		echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
		echo $params['page_item_after'];
	}

	$hidden_active_distances = array( 1, 2 );
	$page_prev_i = $current_page - 1;
	$page_next_i = $current_page + 1;
	$pib = add_tag_class( $params['page_item_before'], '**active_distance_**' );

	// Do not include first page in the page list range
	if( $page_list_start == 1 )
	{
		$page_list_start++;
		if( ( $page_list_end + 1 ) < $total_pages )
		{
			$page_list_end++;
		}
	}

	// Also, do not include last page in the page list range
	if( $page_list_end == $total_pages )
	{
		$page_list_end--;
		if( $page_list_start > 2 )
		{
			$page_list_start--;
		}
	}

	for( $i = $page_list_start; $i <= $page_list_end; $i++ )
	{
		if( $current_page <= 4 )
		{
			$a = ( $i - 4 );
			$active_dist = $a > 0 ? $a : null;
		}
		elseif( $current_page > ( $total_pages - 3 ) )
		{
			if( $i > ( $total_pages - 3 ) )
			{
				$active_dist = null;
			}
			else
			{
				$active_dist = ( ( $total_pages - 3 ) - $i );
			}
		}
		else
		{
			$active_dist = abs( $current_page - $i );
		}

		if( in_array( $active_dist, $hidden_active_distances ) && ( $i < $current_page ) && ( $i > 2 ) && ( $current_page > 4 ) )
		{
			$page_no = ceil( $page_list_start / 2 );
			if( $page_no == 1 )
			{
				$page_no++;
			}
			if( isset( $params['page_item_before'] ) && trim( $params['page_item_before'] ) )
			{
				echo add_tag_class( $params['page_item_before'], 'listnav_distance_'.$active_dist );
				echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
			}
			else
			{
				echo add_tag_class( '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>', 'listnav_distance_'.$active_dist );
			}
			echo $params['page_item_after'];
		}
		if( $i == $current_page )
		{ // Current page
			echo $params['page_item_current_before'];
			echo str_replace( '$page_num$', $i, $params['page_current_template'] );
			echo $params['page_item_current_after'];
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

			if( $active_dist )
			{
				echo str_replace( '**active_distance_**', 'active_distance_'.$active_dist, $pib );
			}
			else
			{
				echo str_replace( '**active_distance_**', '', $pib );
			}
			echo '<a href="'.url_add_param( $page_url, 'page='.$i ).'"'.$attr_rel.'>'.$i.'</a>';
			echo $params['page_item_after'];
		}

		if( in_array( $active_dist, $hidden_active_distances ) && ( $i > $current_page ) && ( $i < ( $total_pages - 1 ) ) )
		{
			$page_no = $page_list_end + floor( ( $total_pages - $page_list_end ) / 2 );
			if( $page_no == $total_pages )
			{
				$page_no--;
			}
			if( isset( $params['page_item_before'] ) && trim( $params['page_item_before'] ) )
			{
				echo add_tag_class( $params['page_item_before'], 'listnav_distance_'.$active_dist );
				echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
			}
			else
			{
				echo add_tag_class( '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>', 'listnav_distance_'.$active_dist );
			}
			echo $params['page_item_after'];
		}
	}

	if( ( $page_list_end < $total_pages ) && ( $page_list_end < $total_pages - 1 ) )
	{ // Display a link to next pages range:
		$page_no = $page_list_end + floor( ( $total_pages - $page_list_end ) / 2 );
		echo add_tag_class( $params['page_item_before'], 'listnav_next_list' );
		echo '<a href="'.url_add_param( $page_url, 'page='.$page_no ).'">...</a>';
		echo $params['page_item_after'];
	}

	// Display a link to last page:
	if( $current_page == $total_pages )
	{
		echo add_tag_class( $params['page_item_current_before'], 'listnav_last' );
		echo '<a href="'.url_add_param( $page_url, 'page='.$total_pages ).'">'.$total_pages.'</a>';
		echo $params['page_item_current_after'];
	}
	else
	{
		echo add_tag_class( $params['page_item_before'], 'listnav_last' );
		echo '<a href="'.url_add_param( $page_url, 'page='.$total_pages ).'">'.$total_pages.'</a>';
		echo $params['page_item_after'];
	}



	if( $current_page < $total_pages )
	{ // A link to next page:
		echo add_tag_class( $params['page_item_before'], 'listnav_next' );
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
			'cell_chapter_start' => '<div class="search_info dimmed">',
			'cell_chapter_end'   => '</div>',
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

	if( ! empty( $params['chapter'] ) )
	{	// Display a chapter info:
		echo $params['cell_chapter_start'];
		echo $params['chapter'];
		echo $params['cell_chapter_end'];
	}

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
					// Example: We searched [several images attached] and we matched it in:
					//  - [This post has several images attached to it]
					// OR we searched [wild] and we matched it in:
					//  - [I was bewildered!]
					//  - [A wild cat!]
					echo '<li>'.sprintf( '%d points for whole term match', $scores ).'</li>';
					continue;

				case 'quoted_term_all':
					// Example: We searched ["images attached" "has several" word] and we matched it in:
					//  - [This post has several images attached to it]
					//  - [The comment has several private images attached]
					echo '<li>'.sprintf( '%d extra points for all quoted term match', $scores ).'</li>';
					continue;

				case 'all_case_sensitive':
					// Example: We searched [several Thi ost mage Each] and we matched it in:
					//  - [This post has several images attached to it. Each one uses a different Attachment Position.]
					echo '<li>'.sprintf( '%d extra points for all word case sensitive match', $scores ).'</li>';
					continue;

				case 'all_whole_words':
					// Example: We searched [several this post images each] and we matched it in:
					//  - [This post has several images attached to it. Each one uses a different Attachment Position.]
					echo '<li>'.sprintf( '%d extra points for all word complete match', $scores ).'</li>';
					continue;

				case 'tags':
					// Example: We searched [photo album] and we matched it if the post has a tag with name:
					//  - [photo album]
					echo '<li>'.sprintf( '%d points for tag term match', $scores ).'</li>';
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
						// If at least one word from requested phrase is case sensitive matched:
						echo '<li>Case sensitive mathces</li>';
						break;

					case 'whole_word_match':
						// If at least one whole word from requested phrase is case insensitive matched:
						echo '<li>Whole word mathces</li>';
						break;

					case 'word_case_insensitive_match':
						// If at least one word from requested phrase is case insensitive matched:
						echo '<li>Case insensitive mathces</li>';
						break;

					case 'word_multiple_occurences':
						// If at least one word from requested phrase is case insensitive matched at least two times in the content:
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