<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author tswicegood: Travis SWICEGOOD.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false,
															$dbprefix = 'post_', $dbIDname = 'post_ID', $dbtable = 'T_posts' )
{
	global $DB;

	$urltitle = trim( $urltitle );

	if( empty( $urltitle ) ) $urltitle = $title;
	if( empty( $urltitle ) ) $urltitle = 'title';

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
	$sql = 'SELECT '.$dbprefix.'urltitle
					FROM '.$dbtable.'
					WHERE '.$dbprefix."urltitle REGEXP '^".$urlbase."(_[0-9]+)?$'";
	if( $post_ID )
		$sql .= " AND $dbIDname <> $post_ID";
	$exact_match = false;
	$highest_number = 0;
	foreach( $DB->get_results( $sql, ARRAY_A ) as $row )
	{
		$existing_urltitle = $row[$dbprefix.'urltitle'];
		// echo "existing = $existing_urltitle <br />";
		if( $existing_urltitle == $urltitle )
		{ // We have an exact match, we'll have to change the number.
			$exact_match = true;
		}
		if( preg_match( '/_([0-9]+)$/', $existing_urltitle, $matches ) )
		{ // This one has a number, we extract it:
			$existing_number = (integer) $matches[1];
			if( $existing_number > $highest_number )
			{ // This is th enew high
				$highest_number = $existing_number;
			}
		}
	}
	// echo "highest existing number = $highest_number <br />";

	if( $exact_match && !$query_only )
	{ // We got an exact match, we need to change the number:
		$urltitle = $urlbase.'_'.($highest_number + 1);
	}

	// echo "using = $urltitle <br />";

	return $urltitle;
}


/**
 * get_postdata(-)
 *
 * if global $postdata was not set it will be
 */
function get_postdata($postid)
{
	global $DB, $postdata, $show_statuses;

	if( !empty($postdata) && $postdata['ID'] == $postid )
	{ // We are asking for postdata of current post in memory! (we're in the b2 loop)
		// Already in memory! This will be the case when generating permalink at display
		// (but not when sending trackbacks!)
		// echo "*** Accessing post data in memory! ***<br />\n";
		return($postdata);
	}

	// echo "*** Loading post data! ***<br>\n";
	// We have to load the post
	$sql = 'SELECT post_ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_locale, post_content, post_title,
											post_url, post_main_cat_ID, post_flags, post_wordcount, post_comments, post_views, cat_blog_ID
					FROM T_posts
					INNER JOIN T_categories ON post_main_cat_ID = cat_ID
					WHERE post_ID = '.$postid;
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
			'ID' => $myrow->post_ID,
			'Author_ID' => $myrow->post_creator_user_ID,
			'Date' => $myrow->post_datestart,
			'Status' => $myrow->post_status,
			'Locale' => $myrow->post_locale,
			'Content' => $myrow->post_content,
			'Title' => $myrow->post_title,
			'Url' => $myrow->post_url,
			'Category' => $myrow->post_main_cat_ID,
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


/*
 * single_post_title(-)
 *
 * @movedTo _obsolete092.php
 */



/**
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


/**
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
			$sqlcat = " AND post_main_cat_ID = $current_category ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_main_cat_ID <> $category";
			}
		}

		$limitprev--;
		$sql = "SELECT post_ID, post_title
						FROM T_posts
						WHERE post_datestart < '$current_post_date'
							$sqlcat
							$sql_exclude_cats
						ORDER BY post_datestart DESC
						LIMIT $limitprev, 1";

		if( $p_info = $DB->get_row( $sql ) )
		{
			$p_title = $p_info->post_title;
			$p_id = $p_info->post_ID;
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


/**
 * next_post(-)
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
			$sqlcat = " AND post_main_cat_ID = $current_category ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_main_cat_ID != $category";
			}
		}

		$now = date('Y-m-d H:i:s', $localtimenow );

		$limitnext--;
		$sql = "SELECT post_ID, post_title
						FROM T_posts
						WHERE post_datestart > '$current_post_date'
							AND post_datestart < '$now'
							$sqlcat
							$sql_exclude_cats
						ORDER BY post_datestart ASC
						LIMIT $limitnext, 1";

		if( $p_info = $DB->get_row( $sql ) )
		{
			$p_title = $p_info->post_title;
			$p_id = $p_info->post_ID;
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


/**
 * Display a link to next page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function next_posts($max_page = 0, $page='' )
{
	global $p, $paged, $Settings, $edited_Blog, $generating_static;

	if( empty($p) )
	{
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (!$max_page || $max_page >= $nextpage)
		{
			if( !isset($generating_static) )
			{ // We are not generating a static page here:
				echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
			}
			elseif( isset($edited_Blog) )
			{ // We are generating a static page
				echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage );
			}
			// else...should not happen
		}
	}
}


/**
 * Display a link to previous page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function previous_posts( $page='' )
{
	global $p, $paged, $Settings, $edited_Blog, $generating_static;

	if( empty($p) )
	{ 
		$nextpage = intval($paged) - 1;
		if ($nextpage < 1) $nextpage = 1;
		if( !isset($generating_static) )
		{ // We are not generating a static page here:
			echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
		}
		elseif( isset($edited_Blog) )
		{ // We are generating a static page
			echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage );
		}
		// else...should not happen
	}
}


/**
 * Display a link to next page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function next_posts_link($label='#', $max_page=0, $page='')
{
	global $p, $paged, $result, $Settings, $MainList;

	if( $label == '#' ) $label = T_('Next Page').' >>';

	if (!$max_page) $max_page = $MainList->get_max_paged();
	if (!$paged) $paged = 1;
	$nextpage = intval($paged) + 1;
	if (empty($p) && (empty($paged) || $nextpage <= $max_page))
	{
		echo '<a href="';
		// M.H.
		echo 'http://' . $_SERVER['HTTP_HOST'];
		// ================
		echo next_posts($max_page, $page);
		echo '">'. htmlspecialchars($label) .'</a>';
	}
}


/**
 * Display a link to previous page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function previous_posts_link($label='#', $page='')
{
	global $Settings, $p, $paged;

	if( $label == '#' ) $label = '<< '.T_('Previous Page');

	if( empty($p) && ($paged > 1) )
	{
		echo '<a href="';
		// M.H.
		echo 'http://' . $_SERVER['HTTP_HOST'];
		// ================
		echo previous_posts( $page );
		echo '">'.htmlspecialchars($label).'</a>';
	}
}


/**
 * Links to previous/next page
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function posts_nav_link($sep=' :: ', $prelabel='#', $nxtlabel='#', $page='')
{
	global $p;
	global $Settings, $MainList;

	if( !empty( $MainList->sql ) && empty($p) )
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
 * Date/Time tags
 *****/

/**
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


/**
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
	{ // We reference by Query: Dirty but explicit permalinks

		switch($use_destination)
		{
			case 'monthly':
				$permalink = url_add_param( $file, 'm='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).'#'.$anchor );
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']])))
				{
					$cacheweekly[$post_date] = $DB->get_var( 'SELECT '.$DB->week( $post_date, locale_startofweek() ) );
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
	{ // We reference by path (CLEAN permalinks!)
		switch($use_destination)
		{
			case 'monthly':
				$permalink = $file.mysql2date("/Y/m/", $postdata['Date']).'#'.$anchor;
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']])))
				{
					$cacheweekly[$post_date] = $DB->get_var( 'SELECT '.$DB->week( $post_date, locale_startofweek() ) );
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
 * @deprecated deprecated by {@link (Item::permalink())} but still used by _archives.plugin.php
 */
function permalink_link($file='', $mode = 'id', $post_ID = '' )		// id or title
{
	global $id;
	if( empty($post_ID) ) $post_ID = $id;
	if( empty($file) ) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $post_ID, $mode );
}


// @@@ These aren't template tags, do not edit them


/**
 * Returns the number of the words in a string, sans HTML
 *
 * {@internal bpost_count_words(-)}}
 *
 * @param string
 * @return integer
 */
function bpost_count_words($string)
{
	$string = trim(strip_tags($string));
	if( function_exists( 'str_word_count' ) )
	{ // PHP >= 4.3
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
 * @param Array statuses of posts we want to get
 */
function statuses_where_clause( $show_statuses = '', $dbprefix = 'post_', $req_blog = NULL )
{
	global $current_User, $blog;

	if( is_null($req_blog ) )
	{
		global $blog;
		$req_blog = $blog;
	}

	if( empty($show_statuses) )
		$show_statuses = array( 'published', 'protected', 'private' );

	$where = ' ( ';
	$or = '';

	if( ($key = array_search( 'private', $show_statuses )) !== false )
	{ // Special handling for Private status:
		unset( $show_statuses[$key] );
		if( is_logged_in() )
		{ // We need to be logged in to have a chance to see this:
			$where .= $or.' ( '.$dbprefix.'status = "private" AND '.$dbprefix.'creator_user_ID = '.$current_User->ID.' ) ';
			$or = ' OR ';
		}
	}

	if( $key = array_search( 'protected', $show_statuses ) )
	{ // Special handling for Protected status:
		if( (!is_logged_in())
			|| ($req_blog == 0) // No blog specified (ONgsb)
			|| (!$current_User->check_perm( 'blog_ismember', 1, false, $req_blog )) )
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
		$where .= $or.$dbprefix.'status IN ('. $other_statuses .') ';
	}

	$where .= ') ';

	// echo $where;
	return $where;
}


/**
 * Allow recursive category selection.
 *
 * @todo Allow to use a dropdown (select) to switch between blogs ( CSS / JS onchange - no submit.. )
 *
 * @param boolean
 * @param boolean tru: use form fields, false: display only
 */
function cat_select( $display_info = true, $form_fields = true )
{
	global $default_main_cat, $allow_cross_posting, $cache_blogs, $cache_categories,
					$blog, $current_blog_ID, $current_User, $edited_Item, $cat_select_form_fields;

	$r = '<div class="extracats">';

	if( $display_info )
	{
		$r .= '<p class="extracatnote">'
				.T_('Select main category in target blog and optionally check additional categories')
				.'</p>';
	}

	$cat_select_form_fields = $form_fields;
	$default_main_cat = $edited_Item->main_cat_ID;

	cat_query( 'none' ); // make sure the caches are loaded

	if( $allow_cross_posting >= 2 )
	{ // If BLOG cross posting enabled, go through all blogs with cats:
		foreach( $cache_blogs as $i_blog )
		{ // run recursively through the cats
			$current_blog_ID = $i_blog->blog_ID;
			if( ! blog_has_cats( $current_blog_ID ) )
				continue;
			if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $current_blog_ID ) )
				continue;
			$r .= '<h4>'.format_to_output($i_blog->blog_name)."</h4>\n";
			$r .= '<table cellspacing="0" class="catselect">'.cat_select_header();
			$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			$r .= '</table>';
		}

		if( $display_info )
		{
			if( $allow_cross_posting >= 3 )
			{
				$r .= '<p class="extracatnote">'.T_('Note: Moving posts across blogs is enabled. Use with caution.').'</p> ';
			}
			$r .= '<p class="extracatnote">'.T_('Note: Cross posting among multiple blogs is enabled.').'</p>';
		}
	}
	else
	{ // BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$r .= '<table cellspacing="0" class="catselect">'.cat_select_header();
		$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
									'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
		$r .= '</table>';

		if( $display_info )
		{
			$r .= '<p class="extracatnote">';
			if( $allow_cross_posting )
				$r .= T_('Note: Cross posting among multiple blogs is currently disabled.');
			else
				$r .= T_('Note: Cross posting among multiple categories is currently disabled.');
			$r .= '</p>';
		}
	}

	$r .= '</div>';

	return $r;
}

/**
 * Header for {@link cat_select()}
 */
function cat_select_header()
{
	global $current_blog_ID, $blog, $allow_cross_posting;

	$r = '<thead><tr><th class="selector catsel_main">'.T_('Main').'</th>';
	if( $allow_cross_posting >= 1 )
	{ // This is current blog or we allow moving posts accross blogs
		$r .= '<th class="selector catsel_extra">'.T_('Extra').'</th>';
	}
	$r .= '<th class="catsel_name">'.T_('Category').'</th></tr></thead>';
	return $r;
}

/**
 * callback to start sublist
 */
function cat_select_before_first( $parent_cat_ID, $level )
{ // callback to start sublist
	return ''; // "\n<ul>\n";
}

/**
 * callback to display sublist element
 */
function cat_select_before_each( $cat_ID, $level )
{ // callback to display sublist element
	global $current_blog_ID, $blog, $cat, $post_extracats, $default_main_cat, $next_action;
	global $creating, $allow_cross_posting, $cat_select_level, $cat_select_form_fields;
	$this_cat = get_the_category_by_ID( $cat_ID );
	$r = "\n<tr>";

	// RADIO for main cat:
	if( ($current_blog_ID == $blog) || ($allow_cross_posting > 2) )
	{ // This is current blog or we allow moving posts accross blogs
		if( ($default_main_cat == 0)
			&& ($next_action == 'create' /* old school */ || $creating /* new school */ )
			&& ($current_blog_ID == $blog) )
		{ // Assign default cat for new post
			$default_main_cat = $cat_ID;
		}
		if( $cat_select_form_fields )
		{	// We want a form field:
			$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
						.T_('Select as MAIN category').'" value="'.$cat_ID.'"';
			if( $cat_ID == $default_main_cat )
			{ // main cat of the Item or set as default main cat above
				$r .= ' checked="checked"';
			}
			$r .= ' id="sel_maincat_'.$cat_ID.'"';
			$r .= ' onclick="check_extracat(this);" /></td>';
		}
		else
		{	// We just want info:
			$r .= '<td class="selector catsel_main">'.bullet( $cat_ID == $default_main_cat ).'</td>';
		}
	}
	else
	{ // Don't allow to select this cat as a main cat
		$r .= '<td class="selector catsel_main">&nbsp;</td>';
	}

	// CHECKBOX:
	if( $allow_cross_posting )
	{ // We allow cross posting, display checkbox:
		if( $cat_select_form_fields )
		{	// We want a form field:
			$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
						.T_('Select as an additional category').'" value="'.$cat_ID.'"';
			if( ($cat_ID == $default_main_cat) || (in_array( $cat_ID, $post_extracats )) )
			{
				$r .= ' checked="checked"';
			}
			$r .= ' id="sel_extracat_'.$cat_ID.'"';
			$r .= ' /></td>';
		}
		else
		{	// We just want info:
			$r .= '<td class="selector catsel_main">'.bullet( ($cat_ID == $default_main_cat) || (in_array( $cat_ID, $post_extracats )) ).'</td>';
		}
	}

	$r .= '<td class="catsel_name"><label'
				.' for="'.( $allow_cross_posting
											? 'sel_extracat_'.$cat_ID
											: 'sel_maincat_'.$cat_ID ).'"'
				.' style="padding-left:'.($level-1).'em;">'.$this_cat['cat_name'].'</label>'
				."</td></tr>\n";

	return $r;
}

/**
 * callback after each sublist element
 */
function cat_select_after_each( $cat_ID, $level )
{ // callback after each sublist element
	return '';
}

/**
 * callback to end sublist
 */
function cat_select_after_last( $parent_cat_ID, $level )
{ // callback to end sublist
	return ''; // "</ul>\n";
}


/*
 * $Log$
 * Revision 1.30  2005/10/18 11:04:16  marian
 * Added extra functionality to support multi-domain feature.
 *
 * Revision 1.29  2005/10/03 18:10:07  fplanque
 * renamed post_ID field
 *
 * Revision 1.28  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.27  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.26  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 * Revision 1.25  2005/08/24 14:02:33  fplanque
 * minor changes
 *
 * Revision 1.24  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.23  2005/03/09 20:29:39  fplanque
 * added 'unit' param to allow choice between displaying x days or x posts
 * deprecated 'paged' mode (ultimately, everything should be pageable)
 *
 * Revision 1.22  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.21  2005/03/08 20:32:07  fplanque
 * small fixes; slightly enhanced WEEK() handling
 *
 * Revision 1.20  2005/03/02 15:28:14  fplanque
 * minor
 *
 * Revision 1.19  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.18  2005/02/17 19:36:24  fplanque
 * no message
 *
 * Revision 1.17  2005/02/16 15:48:06  fplanque
 * merged with work app :p
 *
 * Revision 1.16  2005/02/15 22:05:08  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.15  2005/02/15 20:05:49  fplanque
 * no message
 *
 * Revision 1.14  2005/02/10 22:57:23  blueyed
 * fixed catselection
 *
 * Revision 1.13  2005/02/08 20:17:45  blueyed
 * removed obsolete $User_ID global
 *
 * Revision 1.12  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.11  2005/02/08 00:59:16  blueyed
 * added @todo
 *
 * Revision 1.10  2005/01/25 14:41:33  fplanque
 * changed echo to return in recursive cat list
 *
 * Revision 1.9  2005/01/20 20:38:58  fplanque
 * refactoring
 *
 * Revision 1.8  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.7  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.6  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.5  2004/12/14 21:01:06  fplanque
 * minor fixes
 *
 * Revision 1.4  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.3  2004/12/10 19:45:55  fplanque
 * refactoring
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.94  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 * Revision 1.31  2004/3/13 11:52:9  vegarg
 * Title in permalinks (idea by kiesow).
 *
 * Revision 1.26  2004/1/28 18:44:57  vegarg
 * Fixed a bug when setting the $more_file variable in link_pages(-). (it used to ignore the setting completely!)
 *
 * Revision 1.19  2003/10/10 15:10:11  tswicegood
 * Changed the_title(-) to allow for silent operation
 */
?>