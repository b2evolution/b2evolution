<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


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
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false, $dbprefix = 'post_', $dbIDname = 'ID' )
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
	$sql = 'SELECT '.$dbprefix.'urltitle
					FROM T_posts
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
	$sql = "SELECT ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_locale, post_content, post_title,
											post_url, post_main_cat_ID, post_flags, post_wordcount, post_comments, post_views, cat_blog_ID
					FROM T_posts
					INNER JOIN T_categories ON post_main_cat_ID = cat_ID
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
			'Author_ID' => $myrow->post_creator_user_ID,
			'Date' => $myrow->post_datestart,
			'Status' => $myrow->post_status,
			'Locale' =>  $myrow->post_locale,
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
 * @todo posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 */
function single_post_title( $prefix = '#', $display = 'htmlhead' )
{
	global $p, $title, $preview, $ItemCache;

	$disp_title = '';

	if( $prefix == '#' ) $prefix = ' '.T_('Post details').': ';

	if( $preview )
	{
		if( $prefix == '#' ) $prefix = ' ';
		$disp_title = T_('PREVIEW');
	}
	elseif( intval($p) )
	{
		if(	$Item = $ItemCache->get_by_ID( $p, false ) )
		{
			$disp_title = $Item->get('title');
		}
	}
	elseif( !empty( $title ) )
	{
		if(	$Item = $ItemCache->get_by_urltitle( $title, false ) )
		{
			$disp_title = $Item->get('title');
		}
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
		$sql = "SELECT ID,post_title
						FROM T_posts
						WHERE post_datestart < '$current_post_date'
							$sqlcat
							$sql_exclude_cats
						ORDER BY post_datestart DESC
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
		$sql = "SELECT ID, post_title
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
function statuses_where_clause( $show_statuses = '', $dbprefix = 'post_' )
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
			$where .= $or.' ( '.$dbprefix."status = 'private' AND ".$dbprefix."creator_user_ID = $user_ID ) ";
			$or = ' OR ';
		}
	}

	if( $key = array_search( 'protected', $show_statuses ) )
	{	// Special handling for Protected status:
		if( (!is_logged_in())
			|| ($blog == 0) // No blog specified (ONgsb)
			|| (!$current_User->check_perm( 'blog_ismember', 1, false, $blog )) )
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
 */
function cat_select( $display_info = true )
{
	global $default_main_cat, $allow_cross_posting, $cache_blogs, $cache_categories,
					$blog, $current_blog_ID, $current_User;

	$r = '<div class="extracats">';

	if( $display_info )
	{
		$r .= '<p class="extracatnote">'
				.T_('Select main category in target blog and optionally check additional categories')
				.'</p>';
	}

	$default_main_cat = 0;

	cat_query( false );	// make sure the caches are loaded

	if( $allow_cross_posting >= 2 )
	{	// If BLOG cross posting enabled, go through all blogs with cats:
		foreach( $cache_blogs as $i_blog )
		{ // run recursively through the cats
			$current_blog_ID = $i_blog->blog_ID;
			if( ! blog_has_cats( $current_blog_ID ) ) continue;
			if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $current_blog_ID ) ) continue;
			$r .= '<h4>'.format_to_output($i_blog->blog_name).'</h4>\n';
			$r .= '<table cellspacing="0">'.cat_select_header();
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
	{	// BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$r .= '<table cellspacing="0">'.cat_select_header();
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


function cat_select_header()
{
	global $current_blog_ID, $blog, $allow_cross_posting;
	$r = '<thead><th class="selector">'.T_('Main').'</th>';
	if( ($current_blog_ID == $blog) || ($allow_cross_posting > 2) )
	{ // This is current blog or we allow moving posts accross blogs
		$r .= '<th class="selector">'.T_('Extra').'</th>';
	}
	$r .= '<th>'.T_('Category').'</th></thead>';
	return $r;
}

/**
 * callback to start sublist
 */
function cat_select_before_first( $parent_cat_ID, $level )
{	// callback to start sublist
	return ''; // "\n<ul>\n";
}

/**
 * callback to display sublist element
 */
function cat_select_before_each( $cat_ID, $level )
{	// callback to display sublist element
	global $current_blog_ID, $blog, $cat, $edited_Item, $post_extracats, $default_main_cat, $next_action, $allow_cross_posting, $cat_select_level;
	$this_cat = get_the_category_by_ID( $cat_ID );
	$r = '<tr>';

	// Radio for main cat:
	if( ($current_blog_ID == $blog) || ($allow_cross_posting > 2) )
	{ // This is current blog or we allow moving posts accross blogs
		if( ($default_main_cat == 0) && ($next_action == 'create') && ($current_blog_ID == $blog) )
		{	// Assign default cat for new post
			$default_main_cat = $cat_ID;
		}
		$r .= '<td class="selector"><input type="radio" name="post_category" class="checkbox" title="'
					.T_('Select as MAIN category').'" value="'.$cat_ID.	'"';
		if( ($cat_ID == $edited_Item->main_cat_ID) || ($cat_ID == $default_main_cat))
			$r .= ' checked="checked"';
		$r .= ' /></td>';
	}
	else
	{	// Don't allow to select this cat as a main cat
  	$r .= '<td class="selector">&nbsp;</td>';
	}

	if( $allow_cross_posting )
	{ // We allow cross posting, display checkbox:
		$r .= '<td class="selector"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
					.T_('Select as an additionnal category').'" value="'.$cat_ID.'"';
		if (($cat_ID == $edited_Item->main_cat_ID) or (in_array( $cat_ID, $post_extracats )))
			$r .= ' checked="checked"';
		$r .= ' /></td>';
	}

	$r .= '<td><span style="padding-left:'.($level-1).'em;">'.$this_cat['cat_name'].'</span></td>';

	return $r;
}

/**
 * callback after each sublist element
 */
function cat_select_after_each( $cat_ID, $level )
{	// callback after each sublist element
	return "</td></tr>\n";
}

/**
 * callback to end sublist
 */
function cat_select_after_last( $parent_cat_ID, $level )
{	// callback to end sublist
	return ''; // "</ul>\n";
}




/*
 * $Log$
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