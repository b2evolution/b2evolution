<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
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
 * @todo Use configurable char as seperator (see tracker); replace umlauts
 *
 * @param string url title to validate
 * @param string real title to use as a source if $urltitle is empty (encoded in $evo_charset)
 * @param integer ID of post
 * @return string validated url title
 */
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false,
															$dbprefix = 'post_', $dbIDname = 'post_ID', $dbtable = 'T_posts' )
{
	global $DB;

	$urltitle = trim( $urltitle );

	if( empty( $urltitle ) )
	{
		if( ! empty($title) )
			$urltitle = $title;
		else
			$urltitle = 'title';
	}

	// echo 'starting with: '.$urltitle.'<br />';

	// Replace special chars/umlauts, if we can convert charsets:
	load_funcs('MODEL/settings/_charset.funcs.php');
	$urltitle = replace_special_chars($urltitle);

	// Make everything lowercase
	$urltitle = strtolower( $urltitle );

	// Normalize to 40 chars + a number
	preg_match( '/^(.*?)(-[0-9]+)?$/', $urltitle, $matches );
	$urlbase = substr( $matches[1], 0, 40 );
	$urltitle = $urlbase;
	if( ! empty( $matches[2] ) )
	{
		$urltitle = $urlbase.$matches[2];
	}


	// CHECK FOR UNIQUENESS:
	// Find all occurrences of urltitle-number in the DB:
	$sql = 'SELECT '.$dbprefix.'urltitle
					  FROM '.$dbtable.'
					 WHERE '.$dbprefix.'urltitle REGEXP "^'.$urlbase.'(-[0-9]+)?$"';
	if( $post_ID )
	{	// Ignore current post
		$sql .= ' AND '.$dbIDname.' <> '.$post_ID;
	}
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
		if( preg_match( '/-([0-9]+)$/', $existing_urltitle, $matches ) )
		{ // This one has a number, we extract it:
			$existing_number = (integer) $matches[1];
			if( $existing_number > $highest_number )
			{ // This is the new high
				$highest_number = $existing_number;
			}
		}
	}
	// echo "highest existing number = $highest_number <br />";

	if( $exact_match && !$query_only )
	{ // We got an exact match, we need to change the number:
		$urltitle = $urlbase.'-'.($highest_number + 1);
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
	$sql = 'SELECT post_ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_content, post_title,
											post_main_cat_ID, cat_blog_ID ';
	$sql .= ', post_locale, post_url, post_wordcount, post_comment_status, post_views ';
	$sql .= '	FROM T_posts
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
			'Content' => $myrow->post_content,
			'Title' => $myrow->post_title,
			'Category' => $myrow->post_main_cat_ID,
			'Locale' => $myrow->post_locale,
			'Url' => $myrow->post_url,
			'Wordcount' => $myrow->post_wordcount,
			'views' => $myrow->post_views,
			'comment_status' => $myrow->post_comment_status,
			'Blog' => $myrow->cat_blog_ID,
			);

		// Caching is particularly useful when displaying a single post and you call single_post_title several times
		if( !isset( $postdata ) ) $postdata = $mypostdata;	// Will save time, next time :)

		return($mypostdata);
	}

	return false;
}




/**
 * previous_post(-)
 * @todo Move to ItemList
 * @todo dh> see WP's previous_post_link() for param ideas (using $link instead of $previous and $title). Also, use booleans of course!
 *       $in_same_blog would also be useful!
 */
function previous_post( $format='&lt;&lt; % ', $previous='#', $title='yes', $in_same_cat='no', $limitprev=1, $excluded_categories='', $in_same_blog = true )
{
	global $disp, $posts, $postdata;

	// TODO: $postdata is not set here!! (which is generally good - as of "deprecating those globals", but bad in this context)
	// fp> the real TODO is above: move to ItemList!

	if( empty($postdata) || ($disp != 'single' && $posts != 1) )
	{
		return;
	}

	global $DB;
	global $Blog;

	if( $previous == '#' ) $previous = T_('Previous post') . ': ';

	$from = 'T_posts';

	$current_post_date = $postdata['Date'];
	$current_category = $postdata['Category'];

	if( is_string($in_same_cat) ) { $in_same_cat = ($in_same_cat != 'no'); }

	$sqlcat = '';
	if( $in_same_cat ) {
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

	if( $in_same_blog )
	{
		$from .= ' INNER JOIN T_categories ON post_main_cat_ID = cat_ID';
		$sqlcat .= ' AND cat_blog_ID = '.$postdata['Blog'];
	}

	$limitprev--;
	$sql = "SELECT post_ID, post_title
	          FROM $from
	         WHERE post_datestart < '$current_post_date'
	               $sqlcat
	               $sql_exclude_cats
	           AND ".statuses_where_clause()."
	         ORDER BY post_datestart DESC
	         LIMIT $limitprev, 1";

	if( $p_info = $DB->get_row( $sql, OBJECT, 0, 'previous_post()' ) )
	{
		$ItemCache = & get_Cache( 'ItemCache' );
		$Item = & $ItemCache->get_by_ID($p_info->post_ID);

		$blog_url = $in_same_cat ? $Blog->get('url') : '';
		$string = '<a href="'.$Item->get_permanent_url('', $blog_url).'">'.$previous;
		if( $title == 'yes' ) {
			$string .= $p_info->post_title;
		}
		$string .= '</a>';
		$format = str_replace('%',$string,$format);
		echo $format;
	}
}


/**
 * next_post(-)
 *
 * @todo Move to ItemList
 * @todo dh> see WP's previous_post_link() for param ideas (using $link instead of $previous and $title). Also, use booleans of course!
 *       $in_same_blog would also be useful!
 */
function next_post( $format = '% &gt;&gt; ', $next = '#', $title = 'yes', $in_same_cat = 'no', $limitnext = 1, $excluded_categories = '', $in_same_blog = true )
{
	global $disp, $posts, $postdata;

	// TODO: $postdata is not set here!! (which is generally good - as of "deprecating those globals", but bad in this context)
	// fp> the real TODO is above: move to ItemList!

	if( empty($postdata) || ($disp != 'single' && $posts != 1) )
	{
		return;
	}

	global $localtimenow, $DB;
	global $Blog;

	if( $next == '#' ) $next = T_('Next post') . ': ';

	$from = 'T_posts';

	$current_post_date = $postdata['Date'];
	$current_category = $postdata['Category'];

	if( is_string($in_same_cat) ) { $in_same_cat = ($in_same_cat != 'no'); }

	$sqlcat = '';
	if( $in_same_cat )
	{
		$sqlcat = " AND post_main_cat_ID = $current_category ";
	}

	$sql_exclude_cats = '';
	if (!empty($excluded_categories))
	{
		$blah = explode('and', $excluded_categories);
		foreach($blah as $category)
		{
			$category = intval($category);
			$sql_exclude_cats .= " AND post_main_cat_ID != $category";
		}
	}

	if( $in_same_blog )
	{
		$from .= ' INNER JOIN T_categories ON post_main_cat_ID = cat_ID';
		$sqlcat .= ' AND cat_blog_ID = '.$postdata['Blog'];
	}


	// TODO: fp> This should actually look at the min an dmax timestamp settings
	$now = date('Y-m-d H:i:s', $localtimenow );

	$limitnext--;
	$sql = "SELECT post_ID, post_title
	          FROM $from
	         WHERE post_datestart > '$current_post_date'
	           AND post_datestart < '$now'
	               $sqlcat
	               $sql_exclude_cats
	           AND ".statuses_where_clause()."
	         ORDER BY post_datestart ASC
	         LIMIT $limitnext, 1";

	if( $p_info = $DB->get_row( $sql, OBJECT, 0, 'next_post()' ) )
	{
		$ItemCache = & get_Cache( 'ItemCache' );
		$Item = & $ItemCache->get_by_ID($p_info->post_ID);

		$blog_url = $in_same_cat ? $Blog->get('url') : '';
		$string = '<a href="'.$Item->get_permanent_url('', $blog_url).'">'.$next;
		if ($title=='yes') {
			$string .= $p_info->post_title;
		}
		$string .= '</a>';
		$format = str_replace('%',$string,$format);
		echo $format;
	}
}







// @@@ These aren't template tags, do not edit them


/**
 * Returns the number of the words in a string, sans HTML
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
 * @param boolean true: use form fields, false: display only
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
	if( ! isset($default_main_cat) )
	{
		$default_main_cat = $edited_Item->main_cat_ID;
	}

	cat_load_cache(); // make sure the caches are loaded

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

	if( $current_User->check_perm( 'blog_cats', '', false, $blog ) )
	{
		$r .= '<div><a href="admin.php?ctrl=chapters&amp;action=new">'.T_('Add a new category...').'</a></div>';
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
function cat_select_before_each( $cat_ID, $level, $total_count )
{ // callback to display sublist element
	global $current_blog_ID, $blog, $cat, $post_extracats, $default_main_cat, $next_action;
	global $creating, $allow_cross_posting, $cat_select_level, $cat_select_form_fields;
	$this_cat = get_the_category_by_ID( $cat_ID );
	$r = "\n".'<tr class="'.( $total_count%2 ? 'odd' : 'even' ).'">';

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
			// if( ($cat_ID == $default_main_cat) || (in_array( $cat_ID, $post_extracats )) )  <--- We don't want to precheck the default cat because it will stay checked if we change the default main. On edit, the checkbox will always be in the array.
			if( (in_array( $cat_ID, $post_extracats )) )
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
 * Revision 1.34  2006/12/11 17:26:21  fplanque
 * some cross-linking
 *
 * Revision 1.33  2006/12/04 21:20:27  blueyed
 * Abstracted convert_special_charsets() out of urltitle_validate()
 *
 * Revision 1.32  2006/12/03 22:23:26  fplanque
 * doc
 *
 * Revision 1.31  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.30  2006/11/23 00:37:35  blueyed
 * Added two more replacements in urltitle_validate and pass charset to htmlentities()
 *
 * Revision 1.29  2006/11/17 23:29:54  blueyed
 * Replaced cat_query() calls with cat_load_cache()
 *
 * Revision 1.28  2006/11/14 00:41:58  blueyed
 * Added some more substitutions to special-char-conversation in urltitle_validate(). Should get outsourced IMHO (TODO).
 *
 * Revision 1.27  2006/10/29 21:20:53  blueyed
 * Replace special characters in generated URL titles
 *
 * Revision 1.26  2006/09/26 23:52:06  blueyed
 * Minor things while merging with branches
 *
 * Revision 1.25  2006/09/13 23:38:06  blueyed
 * Fix for next_post()/previous_post(), if there is no $postdata - e.g. on /yyyy/mm/dd/not-found-title
 *
 * Revision 1.24  2006/09/02 00:16:21  blueyed
 * Merge from branches
 *
 * Revision 1.23  2006/08/29 00:26:11  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.22  2006/08/28 18:28:07  fplanque
 * minor
 *
 * Revision 1.21  2006/08/26 20:30:42  fplanque
 * made URL titles Google friendly
 *
 * Revision 1.20  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.19  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.18  2006/08/05 17:59:52  fplanque
 * minor
 *
 * Revision 1.17  2006/08/04 15:24:31  blueyed
 * Respect status in next_post()/previous_post() (Thanks, Austriaco)
 *
 * Revision 1.16  2006/08/03 20:43:39  blueyed
 * Additional fix and cleanup for next_post()/previous_post()
 *
 * Revision 1.15  2006/08/03 18:22:49  blueyed
 * next_post()/prev_post(): only use current blog URL, if "in_same_cat".
 *
 * Revision 1.14  2006/08/01 22:27:34  blueyed
 * Fixed next_post()/previous_post(): use permanent URL, based on current Blog (clean URLs). Also check for disp==single instead of "p", which does not work when a post has been selected by title (param).
 *
 * Revision 1.13  2006/07/23 23:27:07  blueyed
 * cleanup
 *
 * Revision 1.12  2006/07/23 20:18:30  fplanque
 * cleanup
 *
 * Revision 1.11  2006/07/10 18:15:21  blueyed
 * Fix for default main cat, when switching blogs.
 *
 * Revision 1.10  2006/07/08 22:33:43  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.9  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.8  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.7  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 * Revision 1.6  2006/04/06 13:49:50  blueyed
 * Background "striping" for "Categories" fieldset
 *
 * Revision 1.5  2006/04/04 21:46:48  blueyed
 * doc, todo
 *
 * Revision 1.4  2006/03/12 23:46:13  fplanque
 * experimental
 *
 * Revision 1.3  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/09 22:29:59  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.46  2006/02/05 14:07:18  blueyed
 * Fixed 'postbypost' archive mode.
 *
 * Revision 1.45  2006/01/10 20:59:49  fplanque
 * minor / fixed internal sync issues @ progidistri
 *
 * Revision 1.44  2006/01/04 20:35:14  fplanque
 * no message
 *
 * Revision 1.43  2006/01/04 15:03:52  fplanque
 * enhanced list sorting capabilities
 */
?>