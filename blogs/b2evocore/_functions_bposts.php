<?php
/**
 * Post handling functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Create a new post
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * {@internal bpost_create(-)}}
 */
function bpost_create(
	$author_user_ID,              // Author
	$post_title,
	$post_content,
	$post_timestamp,              // 'Y-m-d H:i:s'
	$main_cat_ID = 1,             // Main cat ID
	$extra_cat_IDs = array(),     // Table of extra cats
	$post_status = 'published',
	$post_locale = '#',
	$post_trackbacks = '',
	$autobr = 0,                  // No AutoBR has been used by default
	$pingsdone = true,
	$post_urltitle = '',
	$post_url = '',
	$post_comments = 'open',
	$post_renderers = array('default') )
{
	global $DB, $query;
	global $localtimenow, $default_locale;

	if( $post_locale == '#' ) $post_locale = $default_locale;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';

	// make sure main cat is in extracat list and there are no duplicates
	$extra_cat_IDs[] = $main_cat_ID;
	$extra_cat_IDs = array_unique( $extra_cat_IDs );

	// TODO: START TRANSACTION

	// validate url title
	$post_urltitle = urltitle_validate( $post_urltitle, $post_title, 0 );

	// echo 'INSERTING NEW POST ';

	$query = "INSERT INTO T_posts( post_author, post_title, post_urltitle, post_content,
														post_issue_date, post_mod_date, post_category,  post_status, post_locale,
														post_url, post_autobr, post_flags, post_wordcount,
														post_comments, post_renderers )
						VALUES( $author_user_ID, '".$DB->escape($post_title)."',
										'".$DB->escape($post_urltitle)."',
										'".$DB->escape($post_content)."',
										'".$DB->escape($post_timestamp)."',
										'".date('Y-m-d H:i:s',$localtimenow)."',
										$main_cat_ID,
										'".$DB->escape($post_status)."',
										'".$DB->escape($post_locale)."',
										'".$DB->escape($post_url)."',
										'".(integer)$autobr."',
										'".$DB->escape(implode(',',$post_flags))."',
										".bpost_count_words($post_content).",
										'".$DB->escape($post_comments)."',
										'".$DB->escape(implode('.',$post_renderers))."' )";
	if( ! $DB->query( $query, 'Insert New Post' ) ) return 0;
	$post_ID = $DB->insert_id;
	// echo "post ID:".$post_ID;

	// insert new extracats
	$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
	foreach( $extra_cat_IDs as $extra_cat_ID )
	{
		// echo "extracat: $extra_cat_ID <br />";
		$query .= "( $post_ID, $extra_cat_ID ),";
	}
	$query = substr( $query, 0, strlen( $query ) - 1 );
	if( ! $DB->query( $query, 'Associate new post with extra categories' ) ) return 0;

	// TODO: END TRANSACTION

	return $post_ID;
}


/**
 * Update a post
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * {@internal bpost_update(-)}}
 */
function bpost_update(
	$post_ID,
	$post_title,
	$post_content,
	$post_timestamp = '',         // 'Y-m-d H:i:s'
	$main_cat_ID = 1,             // Main cat ID
	$extra_cat_IDs = array(),     // Table of extra cats
	$post_status = 'published',
	$post_locale = '#',
	$post_trackbacks = '',
	$autobr = 0,                  // No AutoBR has been used by default
	$pingsdone = true,
	$post_urltitle = '',
	$post_url = '',
	$post_comments = 'open',
	$post_renderers = array() )
{
	global $DB, $query, $querycount;
	global $localtimenow, $default_locale;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';

	// make sure main cat is in extracat list and there are no duplicates
	$extra_cat_IDs[] = $main_cat_ID;
	$extra_cat_IDs = array_unique( $extra_cat_IDs );

	// TODO: START TRANSACTION

	// validate url title
	$post_urltitle = urltitle_validate( $post_urltitle, $post_title, $post_ID );

	$query = "UPDATE T_posts
						SET post_title = '".$DB->escape($post_title)."',
								post_urltitle = '".$DB->escape($post_urltitle)."',
								post_url = '".$DB->escape($post_url)."',
								post_content = '".$DB->escape($post_content)."',
								post_mod_date = '".date('Y-m-d H:i:s',$localtimenow)."',
								post_category = $main_cat_ID,
								post_status = '".$DB->escape($post_status)."',
								post_autobr = $autobr,
								post_flags = '".$DB->escape(implode(',',$post_flags))."',
								post_wordcount = ".bpost_count_words($post_content).",
								post_comments = '".$DB->escape($post_comments)."',
								post_renderers = '".$DB->escape(implode('.',$post_renderers))."'";
								if( $post_locale != '#' )
								{ // only update if it was changed
									$query .= ",
								post_locale = '".$DB->escape($post_locale)."'";
								}

	if( !empty($post_timestamp) )	$query .= ", post_issue_date = '$post_timestamp' ";
	$query .= "WHERE ID = $post_ID";
	if( ! $DB->query( $query ) ) return 0;

	// delete previous extracats
	$query = "DELETE FROM T_postcats WHERE postcat_post_ID = $post_ID";
	if( ! $DB->query( $query ) ) return 0;

	// insert new extracats
	$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
	foreach( $extra_cat_IDs as $extra_cat_ID )
	{
		//echo "extracat: $extracat_ID <br />";
		$query .= "( $post_ID, $extra_cat_ID ),";
	}
	$query = substr( $query, 0, strlen( $query ) - 1 );
	if( ! $DB->query( $query ) ) return 0;

	// TODO: END TRANSACTION

	return 1;	// success
}

/**
 * Update a post's status
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * {@internal bpost_update_status(-)}}
 */
function bpost_update_status(
	$post_ID,
	$post_status = 'published',
	$pingsdone = true,
	$post_timestamp = '' )
{
	global $DB, $localtimenow, $query;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';

	$query = "UPDATE T_posts SET ";
	if( !empty($post_timestamp) )	$query .= "post_issue_date = '$post_timestamp', ";
	$query .= "post_mod_date = '".date('Y-m-d H:i:s',$localtimenow)."', ";
	$query .= "post_status = '$post_status', ";
	$query .= "post_flags = '".implode(',',$post_flags)."' ";
	$query .= "WHERE ID = $post_ID";

	return $DB->query( $query );
}


/**
 * Delete a post
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * {@internal bpost_delete(-)}}
 */
function bpost_delete( $post_ID )
{
	global $DB;

	// TODO: START TRANSACTION


	// delete extracats
	$query = "DELETE FROM T_postcats WHERE postcat_post_ID = $post_ID";
	if( $DB->query( $query ) === false ) return 0;

	// delete comments
	$query = "DELETE FROM T_comments WHERE comment_post_ID = $post_ID";
	if( $DB->query( $query ) === false ) return 0;

	// delete post
	$query = "DELETE FROM T_posts WHERE ID = $post_ID";
	if( $DB->query( $query ) === false ) return 0;


	// TODO: END TRANSACTION

	return 1;	// success

}



/**
 * {@internal get_lastpostdate(-)}}
 */
function get_lastpostdate(
		$blog = 1,
		$show_statuses = array(),
		$cat = '',
		$catsel = array(),
		$timestamp_min = '',								// Do not show posts before this timestamp
		$timestamp_max = 'now'							// Do not show posts after this timestamp
 )
{
	global $localtimenow, $postdata;

	// echo 'getting last post date';
	$LastPostList = & new ItemList( $blog, $show_statuses, '', '', '', $cat, $catsel, '', 'DESC', 'issue_date', 1, '','', '', '', '', '', '', 1, 'posts', $timestamp_min, $timestamp_max );

	if( $LastItem = $LastPostList->get_item() )
	{
		// echo 'we have a last item';
		$last_postdata = $LastPostList->get_postdata();	// will set $postdata;
		$lastpostdate = $postdata['Date'];
	}
	else
	{
		// echo 'we have no last item';
		$lastpostdate = date("Y-m-d H:i:s", $localtimenow);
	}
	// echo $lastpostdate;
	return($lastpostdate);
}


/**
 * Validate URL title
 *
 * Using title as a source if url title is empty
 *
 * {@internal urltitle_validate(-) }}
 *
 * @param string url title to validate
 * @param string real title to use as a source if $urltitle is empty
 * @param integer ID of post
 * @return string validated url title
 */
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false )
{
	global $DB;

	$urltitle = trim( $urltitle );

	if( empty( $urltitle )  ) $urltitle = $title;
	if( empty( $urltitle )  ) $urltitle = 'title';

	// echo 'staring with: ', $urltitle, '<br />';

	// Replace HTML entities
	$urltitle = htmlentities( $urltitle, ENT_NOQUOTES );
	// Keep only one char in emtities!
	$urltitle = preg_replace( '/&(.).+?;/', '$1', $urltitle );
	// Remove non acceptable chars
	$urltitle = preg_replace( '/[^A-Za-z0-9]+/', '_', $urltitle );
	$urltitle = preg_replace( '/^_+/', '', $urltitle );
	$urltitle = preg_replace( '/_+$/', '', $urltitle );
	// Uppercase the first character of each word in a string
	$urltitle = strtolower( $urltitle );

	preg_match( '/^(.*?)(_[0-9]+)?$/', $urltitle, $matches );

	$urlbase = substr( $matches[1], 0, 40 );
	$urltitle = $urlbase;
	if( isset( $matches[2] ) )
	{
		$urltitle = $urlbase . $matches[2];
	}


	// Find all occurrences of urltitle+number in the DB:
	$sql = "SELECT post_urltitle
					FROM T_posts
					WHERE post_urltitle REGEXP '^".$urlbase."(_[0-9]+)?$'
					  AND ID <> $post_ID";
	$rows = $DB->get_results( $sql, ARRAY_A );
	$exact_match = false;
	$highest_number = 0;
	if( count( $rows ) ) foreach( $rows as $row )
	{
		$existing_urltitle = $row['post_urltitle'];
		// echo "existing = $existing_urltitle <br />";
		if( $existing_urltitle == $urltitle )
		{ // We have an exact match, we'll have to change the number.
			$exact_match = true;
		}
		if( preg_match( '/_([0-9]+)$/', $existing_urltitle, $matches ) )
		{	// This one has a number, we extract it:
			$existing_number = (integer) $matches[1];
			if( $existing_number > $highest_number )
			{ // This is th enew high
				$highest_number = $existing_number;
			}
		}
	}
	// echo "highest existing number = $highest_number <br />";

	if( $exact_match && !$query_only )
	{	// We got an exact match, we need to change the number:
		$urltitle = $urlbase.'_'.($highest_number + 1);
	}

	// echo "using = $urltitle <br />";

	return $urltitle;
}

/*
 * get_postdata(-)
 *
 * if global $postdata was not set it will be
 */
function get_postdata($postid)
{
	global $DB, $postdata, $show_statuses;

	if( !empty($postdata) && $postdata['ID'] == $postid )
	{	// We are asking for postdata of current post in memory! (we're in the b2 loop)
		// Already in memory! This will be the case when generating permalink at display
		// (but not when sending trackbacks!)
		// echo "*** Accessing post data in memory! ***<br />\n";
		return($postdata);
	}

	// echo "*** Loading post data! ***<br>\n";
	// We have to load the post
	$sql = "SELECT ID, post_author, post_issue_date, post_mod_date, post_status, post_locale, post_content, post_title, post_url, post_category, post_autobr, post_flags, post_wordcount, post_comments, post_views, cat_blog_ID
					FROM T_posts
					INNER JOIN T_categories ON post_category = cat_ID
					WHERE ID = $postid";
	// Restrict to the statuses we want to show:
	// echo $show_statuses;
	// fplanque: 2004-04-04: this should not be needed here. (and is indeed problematic when we want to
	// get a post before even knowning which blog it belongs to. We can think of putting a security check
	// back into the Item class)
	// $sql .= ' AND '.statuses_where_clause( $show_statuses );

	// echo $sql;

	if( $myrow = $DB->get_row( $sql ) )
	{
		$mypostdata = array (
			'ID' => $myrow->ID,
			'Author_ID' => $myrow->post_author,
			'Date' => $myrow->post_issue_date,
			'Status' => $myrow->post_status,
			'Locale' =>  $myrow->post_locale,
			'Content' => $myrow->post_content,
			'Title' => $myrow->post_title,
			'Url' => $myrow->post_url,
			'Category' => $myrow->post_category,
			'AutoBR' => $myrow->post_autobr,
			'Flags' => explode( ',', $myrow->post_flags ),
			'Wordcount' => $myrow->post_wordcount,
			'views' => $myrow->post_views,
			'comments' => $myrow->post_comments,
			'Blog' => $myrow->cat_blog_ID,
			);

		// Caching is particularly useful when displaying a single post and you call single_post_title several times
		if( !isset( $postdata ) ) $postdata = $mypostdata;	// Will save time, next time :)

		return($mypostdata);
	}

	return false;
}


/**
 * Get an Item by its ID
 *
 * {@internal Item_get_by_ID(-) }}
 *
 * @todo cacheing?
 *
 * @param integer post ID
 * @return Item requested object or false
 */
function Item_get_by_ID( $post_ID )
{
	global $DB, $postdata, $show_statuses;

	// We have to load the post
	$sql = "SELECT ID, post_author, post_issue_date, post_mod_date, post_status, post_locale,
									post_content, post_title, post_urltitle, post_url, post_category,
									post_autobr, post_flags, post_wordcount, post_comments,
									post_renderers, post_views, cat_blog_ID
					FROM T_posts INNER JOIN T_categories ON post_category = cat_ID
					WHERE ID = $post_ID";
	// Restrict to the statuses we want to show:
	// echo $show_statuses;
	// fplanque: 2004-04-04: this should not be needed here. (and is indeed problematic when we want to
	// get a post before even knowning which blog it belongs to. We can think of putting a security check
	// back into the Item class)
	// $sql .= ' AND '.statuses_where_clause( $show_statuses );

	// echo $sql;

	if( ! ($row = $DB->get_row( $sql )) )
		return false;

	return new Item( $row );	// COPY !
}

/**
 * Get an Item by its urltitle
 *
 * {@internal Item_get_by_title(-) }}
 *
 * @todo cacheing?
 *
 * @param string url title of Item
 * @return Item requested object or false
 */
function Item_get_by_title( $urltitle )
{
	global $DB, $postdata, $show_statuses;

	// We have to load the post
	$sql = "SELECT ID, post_author, post_issue_date, post_mod_date, post_status, post_locale,
									post_content, post_title, post_urltitle, post_url, post_category,
									post_autobr, post_flags, post_wordcount, post_comments,
									post_renderers, post_views, cat_blog_ID
					FROM T_posts INNER JOIN T_categories ON post_category = cat_ID
					WHERE post_urltitle = ".$DB->quote($urltitle);

	if( ! ($row = $DB->get_row( $sql )) )
		return false;

	return new Item( $row );	// COPY !
}


/**
 * get_the_title(-)
 *
 * @deprecated
 */
function get_the_title()
{
	global $id,$postdata;
	$output = trim( $postdata['Title'] );
	return($output);
}




/*
 * TEMPLATE FUNCTIONS
 */


/*****
 * Post tags
 *****/



/*
 * the_ID(-)
 *
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 *
 */
function the_ID()
{
	global $id;
	echo $id;
}

/*
 * the_status(-)
 *
 * Display post status
 *
 * @deprecated deprecated by {@link Item::status()}
 */
function the_status( $raw = true )
{
	global $post_statuses, $postdata;
	$status = $postdata['Status'];
	if( $raw )
		echo $status;
	else
		echo T_($post_statuses[$status]);
}


/*
 * the_lang(-)
 *
 * Display post language code
 *
 * @deprecated deprecated by {@link Item::lang()}
 */
function the_lang()
{
	global $postdata;
	echo $postdata['Locale'];
}

/*
 * the_language(-)
 *
 * Display post language name
 *
 * @deprecated deprecated by {@link Item::language()}
 */
function the_language()
{
	global $postdata, $languages;
	$post_lang = $postdata['Locale'];
	echo $languages[ $post_lang ];
}

/*
 * the_wordcount(-)
 * Display the number of words in the post
 *
 *
 * @deprecated deprecated by {@link Item::wordcount()}
 */
function the_wordcount()
{
	global $postdata;
	echo $postdata['Wordcount'];
}

/*
 * the_title(-)
 *
 * Display post title
 * 03.10.10 - Updated function to allow for silent operations
 *
 * @deprecated deprecated by {@link Item::title()}
 */
function the_title(
	$before='',						// HTML/text to be displayed before title
	$after='', 						// HTML/text to be displayed after title
	$add_link = true, 		// Added link to this title?
	$format = 'htmlbody',	// Format to use (example: "htmlbody" or "xml")
	$disp = true )				// Display output?
{
	global $postdata;

	$title = get_the_title();
	$url = trim($postdata['Url']);

	if( empty($title) && $add_link )
	{
		$title = $url;
	}

	if( empty($title) )
		return;

	if( $add_link && (!empty($url)) )
	{
		$title = $before.'<a href="'.$url.'">'.$title.'</a>'.$after;
	}
	else
	{
		$title = $before.$title.$after;
	}

	//	ADDED: 03.10.08 by Travis S. :Support for silent operation
	$return_str = format_to_output( $title, $format );
	if( $disp == true )
		echo $return_str;
	else
		return $return_str;
}


/*
 * the_link(-)
 *
 * Display post link
 *
 * @deprecated deprecated by {@link Item::url_link()}
 */
function the_link( $before='', $after='', $format = 'htmlbody' )
{
	global $postdata;

	$url = trim($postdata['Url']);

	if( empty($url) )
	{
		return false;
	}

	$link = $before.'<a href="'.$url.'">'.$url.'</a>'.$after;

	echo format_to_output( $link, $format );
}



/**
 * {@internal single_post_title(-)}}
 *
 * @todo use cache
 */
function single_post_title( $prefix = '#', $display = 'htmlhead' )
{
	global $p, $title, $preview;

	$disp_title = '';

	if( $prefix == '#' ) $prefix = ' '.T_('Post details').': ';

	if( $preview )
	{
		if( $prefix == '#' ) $prefix = ' ';
		$disp_title = T_('PREVIEW');
	}
	elseif( intval($p) )
	{
		$Item = Item_get_by_ID( $p );		// TODO: use cache
		$disp_title = $Item->get('title');
	}
	elseif( !empty( $title ) )
	{
		$Item = Item_get_by_title( $title ); // TODO: use cache
		$disp_title = $Item->get('title');
	}

	if( !empty( $disp_title ) )
	{
		if ($display)
		{
			echo $prefix, format_to_output($disp_title, $display );
		}
		else
		{
			return $disp_title;
		}
	}
}

/**
 * {@internal preview_title(-)}}
 */
function preview_title( $string = '#', $before = ' ', $after = ''  )
{
	global $preview;

	if( $preview )
	{
		echo $before;
		echo ($string == '#') ? T_('PREVIEW') : $string;
		echo $after;
	}
}


/**
 * the_content(-)
 *
 * @deprecated deprecated by {@link Item::content()}
 */
function the_content(
	$more_link_text='#',
	$stripteaser=0,
	$more_file='',
	$more_anchor='#',
	$before_more_link = '#',
	$after_more_link = '#',
	$format = 'htmlbody',
	$cut = 0,
	$dispmore = '#', 	// 1 to display 'more' text, # for url parameter
	$disppage = '#' ) // page number to display specific page, # for url parameter
{
	global $id, $postdata, $pages, $multipage, $numpages;
	global $preview;

	// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;

	if( $more_link_text == '#' )
	{	// TRANS: this is the default text for the extended post "more" link
		$more_link_text = '=> '.T_('Read more!');
	}

	if( $more_anchor == '#' )
	{	// TRANS: this is the default text displayed once the more link has been activated
		$more_anchor = '['.T_('More:').']';
	}

	if( $before_more_link == '#' )
		$before_more_link = '<p class="bMore">';

	if( $after_more_link == '#' )
		$after_more_link = '</p>';

	if( $dispmore === '#' )
	{
		global $more;
		$dispmore = $more;
	}

	if( $disppage === '#' )
	{
		global $page;
		$disppage = $page;
	}
	if( $disppage > $numpages ) $disppage = $numpages;
	// echo 'Using: dmore=', $dispmore, ' dpage=', $disppage;

	$output = '';
	if ($more_file != '')
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	$content = $pages[$disppage-1];
	$content = explode('<!--more-->', $content);

	if ((preg_match('/<!--noteaser-->/', $postdata['Content']) && ((!$multipage) || ($disppage==1))))
		$stripteaser=1;
	$teaser=$content[0];
	if (($dispmore) && ($stripteaser))
	{	// We don't want to repeat the teaser:
		$teaser='';
	}
	$output .= $teaser;

	if (count($content)>1)
	{
		if ($dispmore)
		{	// Viewer has already asked for more
			if( !empty($more_anchor) ) $output .= $before_more_link;
			$output .= '<a id="more'.$id.'" name="more'.$id.'"></a>'.$more_anchor;
			if( !empty($more_anchor) ) $output .= $after_more_link;
			$output .= $content[1];
		}
		else
		{ // We are offering to read more
			$more_link = gen_permalink( $file, $id, 'id', 'single', 1 );
			$output .= $before_more_link.'<a href="'.$more_link.'#more'.$id.'">'.$more_link_text.'</a>'.$after_more_link;
		}
	}
	if ($preview)
	{ // preview fix for javascript bug with foreign languages
		$output =  preg_replace('/\%u([0-9A-F]{4,4})/e',  "'&#'.base_convert('\\1',16,10).';'", $output);
	}

	$content = format_to_output( $output, $format );

	if( ($format == 'xml') && $cut )
	{	// Let's cut this down...
		$blah = explode(' ', $content);
		if (count($blah) > $cut)
		{
			for ($i=0; $i<$cut; $i++)
			{
				$excerpt .= $blah[$i].' ';
			}
			$content = $excerpt . '...';
		}
	}
	echo $content;
}




/*
 * link_pages(-)
 * vegarg: small bug when using $more_file fixed
 */
function link_pages( $before='#', $after='#', $next_or_number='number', $nextpagelink='#', $previouspagelink='#', $pagelink='%d', $more_file='')
{
	global $id, $page, $numpages, $multipage, $more;

	if( $before == '#' ) $before = '<p>'.T_('Pages:').' ';
	if( $after == '#' ) $after = '</p>';
	if( $nextpagelink == '#' ) $nextpagelink = T_('Next page');
	if( $previouspagelink == '#' ) $previouspagelink = T_('Previous page');

	if ($more_file != '')
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	if( $multipage ) { // && ($more)) {
		echo $before;
		if( $next_or_number == 'number' )
		{
			for ($i = 1; $i < ($numpages+1); $i = $i + 1)
			{
				$j = str_replace('%d', $i, $pagelink);
				echo ' ';
				if( ($i != $page) || ( (!$more) && ($page==1) ))
					echo '<a href="'.url_add_param($file, 'p='.$id.'&amp;more=1&amp;page='.$i).'">';
				echo $j;
				if( ($i != $page) || ( (!$more) && ($page==1) ))
					echo '</a>';
			}
		}
		else
		{
			$i = $page - 1;
			if( $i )
				echo ' <a href="'.url_add_param($file, 'p='.$id.'&amp;page='.$i).'">'.$previouspagelink.'</a>';

			$i = $page+1;

			if( $i <= $numpages )
				echo ' <a href="'.url_add_param($file, 'p='.$id.'&amp;page='.$i).'">'.$nextpagelink.'</a>';
		}
		echo $after;
	}
}


/*
 * previous_post(-)
 *
 *
 */
function previous_post($format='%', $previous='#', $title='yes', $in_same_cat='no', $limitprev=1, $excluded_categories='')
{
	if( $previous == '#' ) $previous = T_('Previous post') . ': ';

	global $DB, $postdata;
	global $p, $posts, $s;

	if(($p) || ($posts==1))
	{

		$current_post_date = $postdata['Date'];
		$current_category = $postdata['Category'];

		$sqlcat = '';
		if ($in_same_cat != 'no') {
			$sqlcat = " AND post_category='$current_category' ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_category != $category";
			}
		}

		$limitprev--;
		$sql = "SELECT ID,post_title
						FROM T_posts
						WHERE post_issue_date < '$current_post_date'
							$sqlcat
							$sql_exclude_cats
						ORDER BY post_issue_date DESC
						LIMIT $limitprev, 1";

		if( $p_info = $DB->get_row( $sql ) )
		{
			$p_title = $p_info->post_title;
			$p_id = $p_info->ID;
			$string = '<a href="'.url_add_param( get_bloginfo('blogurl'), 'p='.$p_id.'&amp;more=1&amp;c=1').'">'.$previous;
			if (!($title!='yes')) {
				$string .= $p_title;
			}
			$string .= '</a>';
			$format = str_replace('%',$string,$format);
			echo $format;
		}
	}
}


/*
 * next_post(-)
 *
 *
 */
function next_post($format='%', $next='#', $title='yes', $in_same_cat='no', $limitnext=1, $excluded_categories='')
{
	if( $next == '#' ) $next = T_('Next post') . ': ';

	global $p, $posts, $postdata, $localtimenow, $DB;
	if(($p) || ($posts==1))
	{

		$current_post_date = $postdata['Date'];
		$current_category = $postdata['Category'];
		$sqlcat = '';
		if ($in_same_cat != 'no')
		{
			$sqlcat = " AND post_category='$current_category' ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_category != $category";
			}
		}

		$now = date('Y-m-d H:i:s', $localtimenow );

		$limitnext--;
		$sql = "SELECT ID, post_title
						FROM T_posts
						WHERE post_issue_date > '$current_post_date'
							AND post_issue_date < '$now'
							$sqlcat
							$sql_exclude_cats
						ORDER BY post_issue_date ASC
						LIMIT $limitnext, 1";

		if( $p_info = $DB->get_row( $sql ) )
		{
			$p_title = $p_info->post_title;
			$p_id = $p_info->ID;
			$string = '<a href="'.url_add_param( get_bloginfo('blogurl'), 'p='.$p_id.'&amp;more=1&amp;c=1' ).'">'.$next;
			if ($title=='yes') {
				$string .= $p_title;
			}
			$string .= '</a>';
			$format = str_replace('%',$string,$format);
			echo $format;
		}
	}
}


/*
 * next_posts(-)
 */
function next_posts($max_page = 0, $page='' )
{
	global $p, $paged, $Settings, $edited_Blog, $generating_static;

	if (empty($p) && ($Settings->get('what_to_show') == 'paged'))
	{
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (!$max_page || $max_page >= $nextpage)
		{
			if( !isset($generating_static) )
			{	// We are not generating a static page here:
				echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
			}
			elseif( isset($edited_Blog) )
			{	// We are generating a static page
				echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage  );
			}
			// else...should not happen
		}
	}
}



/*
 * previous_posts(-)
 */
function previous_posts( $page='' )
{
	global $p, $paged, $Settings, $edited_Blog, $generating_static;

	if (empty($p) && ($Settings->get('what_to_show') == 'paged'))
	{
		$nextpage = intval($paged) - 1;
		if ($nextpage < 1) $nextpage = 1;
		if( !isset($generating_static) )
		{	// We are not generating a static page here:
			echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
		}
		elseif( isset($edited_Blog) )
		{	// We are generating a static page
			echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage  );
		}
		// else...should not happen
	}
}


/**
 * next_posts_link(-)
 *
 *
 */
function next_posts_link($label='#', $max_page=0, $page='')
{
	global $p, $paged, $result, $request, $Settings;

	if( $label == '#' ) $label = T_('Next Page').' >>';

	if ($Settings->get('what_to_show') == 'paged')
	{
		global $MainList;
		if (!$max_page)	$max_page = $MainList->get_max_paged();
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (empty($p) && (empty($paged) || $nextpage <= $max_page))
		{
			echo '<a href="';
			echo next_posts($max_page, $page);
			echo '">'. htmlspecialchars($label) .'</a>';
		}
	}
}



/**
 * previous_posts_link(-)
 *
 *
 */
function previous_posts_link($label='#', $page='')
{
	global $Settings;

	if( $label == '#' ) $label = '<< '.T_('Previous Page');

	global $p, $paged;
	if (empty($p)  && ($paged > 1) && ($Settings->get('what_to_show') == 'paged'))
	{
		echo '<a href="';
		echo previous_posts( $page );
		echo '">'.  htmlspecialchars($label) .'</a>';
	}
}



/**
 * Links to previous/next page
 *
 * posts_nav_link(-)
 */
function posts_nav_link($sep=' :: ', $prelabel='#', $nxtlabel='#', $page='')
{
	global $request, $p;
	global $Settings;

	if( !empty( $request ) && empty($p) && ($Settings->get('what_to_show') == 'paged'))
	{
		global $MainList;
		$max_paged = $MainList->get_max_paged();
		if( $max_paged > 1 )
		{
			previous_posts_link( $prelabel, $page );
			echo htmlspecialchars($sep);
			next_posts_link( $nxtlabel, $max_paged, $page );
		}
	}
}

/*****
 * // Post tags
 *****/




/*****
 * Date/Time tags
 *****/

/**
 * the_date(-)
 *
 * @deprecated deprecated by {@link ItemList::date_if_changed()}
 */
function the_date($d='', $before='', $after='', $echo = 1)
{
	global $id, $postdata, $day, $previousday, $newday;
	$the_date = '';
	if ($day != $previousday)
	{
		$the_date .= $before;
		if ($d=='') {
			$the_date .= mysql2date( locale_datefmt(), $postdata['Date']);
		} else {
			$the_date .= mysql2date( $d, $postdata['Date']);
		}
		$the_date .= $after;
		$previousday = $day;
	}
	if ($echo) {
		echo $the_date;
	} else {
		return $the_date;
	}
}

/**
 * the_time(-)
 *
 *
 * @deprecated deprecated by {@link Item::time()} / {@link Item::date()}
 *
 */
function the_time($d='', $echo = 1, $useGM = 0)
{
	global $id,$postdata;
	if ($d=='')
	{
		$the_time = mysql2date( locale_timefmt(), $postdata['Date'], $useGM);
	} else {
		$the_time = mysql2date( $d, $postdata['Date'], $useGM);
	}

	if ($echo)
	{
		echo $the_time;
	} else {
		return $the_time;
	}
}

/*
 * the_weekday(-)
 *
 *
 */
function the_weekday()
{
	global $weekday,$id,$postdata;
	$the_weekday = T_($weekday[mysql2date('w', $postdata['Date'])]);
	echo $the_weekday;
}

/*
 * the_weekday_date(-)
 *
 *
 */
function the_weekday_date($before='',$after='')
{
	global $weekday,$id,$postdata,$day,$previousweekday;
	$the_weekday_date = '';
	if ($day != $previousweekday) {
		$the_weekday_date .= $before;
		$the_weekday_date .= T_($weekday[mysql2date('w', $postdata['Date'])]);
		$the_weekday_date .= $after;
		$previousweekday = $day;
	}

	echo $the_weekday_date;
}

/*****
 * // Date/Time tags
 *****/

/*****
 * Author tags
 *****/

/*
 * the_author(-)
 *
 * @deprecated deprecated by {@link User::prefered_name()}
 */
function the_author( $format = 'htmlbody' )
{
	global $authordata;
	switch( $authordata['user_idmode'] )
	{
		case 'nickname':
			$author = $authordata['user_nickname'];
			break;

		case 'login':
			$author = $authordata['user_login'];
			break;

		case 'firstname':
			$author = $authordata['user_firstname'];
			break;

		case 'lastname':
			$author = $authordata['user_lastname'];
			break;

		case 'namefl':
			$author = $authordata['user_firstname'].' '.$authordata['user_lastname'];
			break;

		case 'namelf':
			$author = $authordata['user_lastname'].' '.$authordata['user_firstname'];
			break;

		default:
			$author = $authordata['user_nickname'];
	}

	echo format_to_output( $author, $format );
}


/*
 * the_author_level(-)
 *
 * @deprecated deprecated by {@link User::level()}
 */
function the_author_level()
{
	global $authordata;
	echo $authordata['user_level'];
}

/*
 * the_author_login(-)
 *
 * @deprecated deprecated by {@link User::level()}
 */
function the_author_login( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_login'], $format );
}

/*
 * the_author_firstname(-)
 */
function the_author_firstname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_firstname'], $format );
}

/*
 * the_author_lastname(-)
 */
function the_author_lastname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_lastname'], $format );
}

/*
 * the_author_nickname(-)
 */
function the_author_nickname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_nickname'], $format );
}

/*
 * the_author_ID(-)
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 */
function the_author_ID()
{
	global $authordata;
	echo $authordata['ID'];
}

/*
 * the_author_email(-)
 */
function the_author_email( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( antispambot($authordata['user_email']), $format );
}

/*
 * the_author_url(-)
 *
 * @deprecated deprecated by {@link User::url()}
 */
function the_author_url( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_url'], $format );
}

/*
 * the_author_icq(-)
 */
function the_author_icq( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_icq'], $format );
}

/*
 * the_author_aim(-)
 */
function the_author_aim( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( str_replace(' ', '+', $authordata['user_aim']), $format );
}

/*
 * the_author_yim(-)
 */
function the_author_yim( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_yim'], $format );
}

/*
 * the_author_msn(-)
 */
function the_author_msn( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_msn'], $format );
}

/*
 * the_author_posts(-)
 */
function the_author_posts()
{
	global $postdata;
	$posts = get_usernumposts($postdata['Author_ID']);
	echo $posts;
}

/*****
 * // Author tags
 *****/





/***** Permalink tags *****/

/**
 * permalink_anchor(-)
 *
 * generate anchor for permalinks to refer to
 *
 * TODO: archives modes in clean mode
 *
 * @deprecated deprecated by {@link Item::anchor()}
 */
function permalink_anchor( $mode = 'id' )
{
	global $id, $postdata;
	switch(strtolower($mode))
	{
		case 'title':
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
			echo '<a name="'.$title.'"></a>';
			break;
		case 'id':
		default:
			echo '<a name="'.$id.'"></a>';
			break;
	}
}

/**
 * gen_permalink(-)
 *
 * generate permalink
 *
 * TODO: archives modes in clean mode
 *
 * @deprecated deprecated by {@link Item::gen_permalink(-)}
 */
function gen_permalink(
	$file,                  // base URL of the blog
	$id,                    // post ID to be linked to
	$use_anchor_mode = '',  // Default to id
	$use_destination = '',  // Default to config
	$use_more = NULL,			  // DEPRECATED
	$use_comments = NULL,   // DEPRECATED
	$use_trackback = NULL,  // DEPRECATED
	$use_pingback = NULL )  // DEPRECATED
{
	global $cacheweekly;
	global $Settings;

	// We're gonna need access to more postdata in several cases:
	$postdata = get_postdata( $id );

	// Defaults:
	if (empty($use_anchor_mode)) $use_anchor_mode = 'id';
	if (empty($use_destination))
			$use_destination = ( strstr( $Settings->get('permalink_type'), 'archive' ) !== false )
					? 'archive' : 'single';
	if ($use_destination=='archive') $use_destination = $Settings->get('archive_mode');

	// Generate anchor
	switch(strtolower($use_anchor_mode))
	{
		case 'title':
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
			$anchor = $title;
			break;

		case 'id':
		default:
			$anchor = $id;
			break;
	}

	if( ! $Settings->get('links_extrapath') )
	{	// We reference by Query: Dirty but explicit permalinks

		switch($use_destination)
		{
			case 'monthly':
				$permalink = url_add_param( $file, 'm='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).'#'.$anchor );
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']])))
				{
					$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
				}
				$permalink = url_add_param( $file, 'm='.substr($postdata['Date'],0,4).'&amp;w='.$cacheweekly[$postdata['Date']].'#'.$anchor );
				break;
			case 'daily':
				$permalink = url_add_param( $file, 'm='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).substr($postdata['Date'],8,2).'#'.$anchor );
				break;
			case 'postbypost':
			case 'single':
			default:
				$permalink = url_add_param( $file, 'p='.$id.'&amp;more=1&amp;c=1&amp;tb=1&amp;pb=1' );
				break;
		}
	}
	else
	{	// We reference by path (CLEAN permalinks!)
		switch($use_destination)
		{
			case 'monthly':
				$permalink = $file.mysql2date("/Y/m/", $postdata['Date']).'#'.$anchor;
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']])))
				{
					$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
				}
				$permalink = $file.mysql2date("/Y/m/", $postdata['Date']).'w'.$cacheweekly[$postdata['Date']].'/#'.$anchor;
				break;
			case 'daily':
				$permalink = $file.mysql2date("/Y/m/d/", $postdata['Date']).'#'.$anchor;
				break;
			case 'postbypost':
			case 'single':
			default:
				// This is THE CLEANEST available: RECOMMENDED!
				$permalink = $file.mysql2date("/Y/m/d/", $postdata['Date']).'p'.$id;
				break;
		}
	}

	return $permalink;
}


/**
 * permalink_link(-)
 *
 * Display permalink
 *
 * @deprecated deprecated by {@link (Item::permalink())} but still used by _archives.php
 */
function permalink_link($file='', $mode = 'id', $post_ID = '' )		// id or title
{
	global $id;
	if( empty($post_ID) ) $post_ID = $id;
	if( empty($file) ) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $post_ID, $mode );
}

/**
 * permalink_single(-)
 *
 * Permalink forced to a single post
 *
 * @deprecated deprecated by {@link Item::permalink()}
 */
function permalink_single($file='')
{
	global $id;
	if (empty($file)) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $id, 'id', 'single' );
}


/**
 * {@internal the_permalink(-) }}
 *
 * @deprecated deprecated by {@link $Item::permalink()}
 */
function the_permalink()
{
	global $Item;
	$Item->permalink();
}


/***** // Permalink tags *****/



// @@@ These aren't template tags, do not edit them


/*
 * is_new_day(-)
 */
function is_new_day()
{
	global $day, $previousday;
	if ($day != $previousday) {
		return(1);
	} else {
		return(0);
	}
}


/*
 * bpost_count_words(-)
 *
 * Returns the number of the words in a string, sans HTML
 */
function bpost_count_words($string)
{
	$string = trim(strip_tags($string));
	if( function_exists( 'str_word_count' ) )
	{	// PHP >= 4.3
		return str_word_count($string);
	}

	/* In case str_word_count() doesn't exist (to accomodate PHP < 4.3).
		(Code adapted from post by "brettNOSPAM at olwm dot NO_SPAM dot com" at
		PHP documentation page for str_word_count(). A better implementation
		probably exists.)
	*/
	if($string == '')
	{
		return 0;
	}

	$pattern = "/[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-\-|:|\&|@)]+/";
	$string = preg_replace($pattern, " ", $string);
	$string = count(explode(" ", $string));

	return $string;
}

/**
 * Construct the where clause to limit retrieved posts on their status
 *
 * {@internal statuses_where_clause(-)}}
 *
 * @param Array statuses of posts we want to get
 */
function statuses_where_clause( $show_statuses = '' )
{
	global $current_User, $blog;

	if( empty($show_statuses) )
		$show_statuses = array( 'published', 'protected', 'private' );

	$where = ' ( ';
	$or = '';

	if( ($key = array_search( 'private', $show_statuses )) !== false )
	{	// Special handling for Private status:
		unset( $show_statuses[$key] );
		if( is_logged_in() )
		{	// We need to be logged in to have a chance to see this:
			global $user_ID;
			$where .= $or." ( post_status = 'private' AND post_author = $user_ID ) ";
			$or = ' OR ';
		}
	}

	if( $key = array_search( 'protected', $show_statuses ) )
	{	// Special handling for Protected status:
		if( (!is_logged_in()) || (!$current_User->check_perm( 'blog_ismember', 1, false, $blog )) )
		{ // we are not allowed to see this if we are not a member of the current blog:
			unset( $show_statuses[$key] );
		}
	}

	// Remaining statuses:
	$other_statuses = '';
	$sep = '';
	foreach( $show_statuses as $other_status )
	{
		$other_statuses .= $sep.'\''.$other_status.'\'';
		$sep = ',';
	}
	if( strlen( $other_statuses ) )
	{
		$where .= $or.'post_status IN ('. $other_statuses .') ';
	}

	$where .= ') ';

	// echo $where;
	return $where;
}

?>
