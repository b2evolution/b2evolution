<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'items/model/_itemlight.class.php', 'ItemLight' );
load_class( 'items/model/_itemlist.class.php', 'ItemList2' );

/**
 * Prepare the MainList object for displaying skins.
 *
 * @param integer max # of posts on the page
 */
function init_MainList( $items_nb_limit )
{
	global $MainList;
	global $Blog;
	global $timestamp_min, $timestamp_max;
	global $preview;
	global $disp;
	global $postIDlist, $postIDarray;

	$MainList = new ItemList2( $Blog, $timestamp_min, $timestamp_max, $items_nb_limit );	// COPY (FUNC)

	if( ! $preview )
	{
		if( $disp == 'page' )
		{	// Get  pages:
			$MainList->set_default_filters( array(
					'types' => '1000',		// pages
					// 'types' => '1000,1500,1520,1530,1570',		// pages and intros (intros should normally never be called)
				) );
		}
		// else: we are either in single or in posts mode

		// pre_dump( $MainList->default_filters );
		$MainList->load_from_Request( false );
		// pre_dump( $MainList->filters );
		// echo '<br/>'.( $MainList->is_filtered() ? 'filtered' : 'NOT filtered' );
		// $MainList->dump_active_filters();

		// Run the query:
		$MainList->query();

		// Old style globals for category.funcs:
		$postIDlist = $MainList->get_page_ID_list();
		$postIDarray = $MainList->get_page_ID_array();
	}
	else
	{	// We want to preview a single post, we are going to fake a lot of things...
		$MainList->preview_from_request();

		// Legacy for the category display
		$cat_array = array();
	}

	param( 'more', 'integer', 0, true );
	param( 'page', 'integer', 1, true ); // Post page to show
	param( 'c',    'integer', 0, true ); // Display comments?
	param( 'tb',   'integer', 0, true ); // Display trackbacks?
	param( 'pb',   'integer', 0, true ); // Display pingbacks?
}


/**
 * Return an Item if an Intro or a Featured item is available for display in current disp.
 *
 * @return Item
 */
function & get_featured_Item()
{
	global $Blog;
	global $timestamp_min, $timestamp_max;
	global $disp, $disp_detail, $MainList;
	global $featured_displayed_item_ID;

	if( $disp != 'posts' || !isset($MainList) )
	{	// If we're not displaying postS, don't display a feature post on top!
		$Item = NULL;
		return $Item;
	}

	$FeaturedList = new ItemList2( $Blog, $timestamp_min, $timestamp_max, 1 );

	$FeaturedList->set_default_filters( $MainList->filters );

	if( ! $MainList->is_filtered() )
	{	// Restrict to 'main' and 'all' intros:
		$restrict_to_types = '1500,1600';
	}
	else
	{	// Filtered...
		// echo $disp_detail;
		switch( $disp_detail )
		{
			case 'posts-cat':
				$restrict_to_types = '1520,1570,1600';
				break;
			case 'posts-tag':
				$restrict_to_types = '1530,1570,1600';
				break;
			default:
				$restrict_to_types = '1570,1600';
		}
	}

	$FeaturedList->set_filters( array(
			'types' => $restrict_to_types,
		), false /* Do NOT memorize!! */ );
	// pre_dump( $FeaturedList->filters );
	// Run the query:
	$FeaturedList->query();

	if( $FeaturedList->result_num_rows == 0 )
	{ // No Intro page was found, try to find a featured post instead:

		$FeaturedList->reset();

		$FeaturedList->set_filters( array(
				'featured' => 1,  // Featured posts only (TODO!)
				// Types will already be reset to defaults here
			), false /* Do NOT memorize!! */ );

		// Run the query:
		$FeaturedList->query();
	}

	$Item = $FeaturedList->get_item();

	// Memorize that ID so that it can later be filtered out normal display:
	$featured_displayed_item_ID = $Item ? $Item->ID : NULL;

	return $Item;
}


/**
 * Validate URL title (slug) / Also used for category slugs
 *
 * Using title as a source if url title is empty.
 * We allow up to 200 chars (which is ridiculously long) for WP import compatibility.
 *
 * @param string url title to validate
 * @param string real title to use as a source if $urltitle is empty (encoded in $evo_charset)
 * @param integer ID of post
 * @param boolean Query the DB, but don't modify the URL title if the title already exists (Useful if you only want to alert the pro user without making changes for him)
 * @param string The prefix of the database column names (e. g. "post_" for post_urltitle)
 * @param string The name of the post ID column
 * @param string The name of the DB table to use
 * @param NULL|string The post locale or NULL if there is no specific locale.
 * @return string validated url title
 */
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false,
									$dbSlugFieldName = 'post_urltitle', $dbIDname = 'post_ID',
									$dbtable = 'T_items__item', $post_locale = NULL )
{
	global $DB, $Messages;

	$urltitle = trim( $urltitle );
	$orig_title = $urltitle;

	if( empty( $urltitle ) )
	{
		if( ! empty($title) )
			$urltitle = $title;
		else
			$urltitle = 'title';
	}

	// echo 'starting with: '.$urltitle.'<br />';
	 
	// Replace special chars/umlauts, if we can convert charsets:
	load_funcs('locales/_charset.funcs.php');
	$urltitle = replace_special_chars($urltitle, $post_locale);

	// Make everything lowercase
	$urltitle = strtolower( $urltitle );

	// leave only first 5 words
	$title_words = array();
	$title_words = explode('-', $urltitle);
	if(count($title_words) > 5)
	{
		$count_of_words = 5;
		$urltitle = '';
		for($i = 0; $i < $count_of_words; $i++)
		{
			$urltitle .= $title_words[$i].'-';
		}
		//delete last '-'
		$urltitle = substr($urltitle, 0, strlen($urltitle) - 1);
	}
	
	// echo 'leaving 5 words: '.$urltitle.'<br />';
	
	// Normalize to 200 chars + a number
	preg_match( '/^(.*?)((-|_)+([0-9]+))?$/', $urltitle, $matches );
	$urlbase = substr( $matches[1], 0, 200 );
	// strip a possible dash at the end of the URL title:
	$urlbase = rtrim( $urlbase, '-' );
	$urltitle = $urlbase;
	if( ! empty( $matches[4] ) )
	{
		$urltitle .= '-'.$matches[4];
	}


	// CHECK FOR UNIQUENESS:
	// Find all occurrences of urltitle-number in the DB:
	$sql = 'SELECT '.$dbSlugFieldName.'
					  FROM '.$dbtable.'
					 WHERE '.$dbSlugFieldName." REGEXP '^".$urlbase."(-[0-9]+)?$'";
	if( $post_ID )
	{	// Ignore current post
		$sql .= ' AND '.$dbIDname.' <> '.$post_ID;
	}
	$exact_match = false;
	$highest_number = 0;
	foreach( $DB->get_results( $sql, ARRAY_A ) as $row )
	{
		$existing_urltitle = $row[$dbSlugFieldName];
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

	if( !empty($orig_title) && $urltitle != $orig_title )
	{
		$Messages->add( sprintf(T_('Warning: the URL slug has been changed to &laquo;%s&raquo;.'), $urltitle ), 'error' );
	}

	return $urltitle;
}


/**
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
	$sql .= '	FROM T_items__item
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





// @@@ These aren't template tags, do not edit them


/**
 * Returns the number of the words in a string, sans HTML
 *
 * @todo dh> Test if http://de3.php.net/manual/en/function.str-word-count.php#85579 works better/faster
 *           (only one preg_* call and no loop).
 *
 * @param string The string.
 * @return integer Number of words.
 *
 * @internal PHP's str_word_count() is not accurate. Inaccuracy reported
 *           by sam2kb: http://forums.b2evolution.net/viewtopic.php?t=16596
 */
function bpost_count_words( $str )
{
	global $evo_charset;

	$str = trim( strip_tags( $str ) );

	// Note: The \p escape sequence is available since PHP 4.4.0 and 5.1.0.
	if( @preg_match( '|\pL|u', 'foo' ) === false )
	{
		return str_word_count( $str );
	}

	$count = 0;

	foreach( preg_split( '#\s+#', convert_charset( $str, 'UTF-8', $evo_charset ), -1,
							PREG_SPLIT_NO_EMPTY ) as $word )
	{
		if( preg_match( '#\pL#u', $word ) )
		{
			++$count;
		}
	}

	return $count;
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
			$where .= $or.' ( '.$dbprefix."status = 'private' AND ".$dbprefix.'creator_user_ID = '.$current_User->ID.' ) ';
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
 * Compose screen: display attachment iframe
 *
 * @param Form
 * @param boolean
 * @param Item
 * @param Blog
 */
function attachment_iframe( & $Form, $creating, & $edited_Item, & $Blog )
{
	global $admin_url, $dispatcher;
	global $current_User;
	global $Settings;

	$fieldset_title = T_('Images &amp; Attachments').get_manual_link('post_attachments_fieldset');

	if( $creating )
	{	// Creating new post
		$fieldset_title .= ' - <a id="title_file_add" href="#" >'.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';
		
		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_createlinks' ) );
		$Form->hidden( 'is_attachments', 'false' );
		
		echo '<table cellspacing="0" cellpadding="0"><tr><td>';
   	$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & start attaching files'), 'SaveEditButton' ) );
		echo '</td></tr></table>';

		$Form->end_fieldset();
	}
	else
	{ // Editing post

		$iframe_name = 'attach_'.generate_random_key( 16 );

		$fieldset_title .= ' - <a href="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;item_ID='.$edited_Item->ID
					.'" target="'.$iframe_name.'">'.get_icon( 'refresh', 'imgtag' ).' '.T_('Refresh').'</a>';

		if( $current_User->check_perm( 'files', 'view', false, $Blog->ID )
			&& $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:

			$fieldset_title .= ' - <a href="'.$dispatcher.'?ctrl=files&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID
						.'" onclick="return pop_up_window( \''.$dispatcher.'?ctrl=files&amp;mode=upload&amp;iframe_name='
						.$iframe_name.'&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID.'\', \'fileman_upload\', 1000 )">'
						.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';
		}

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_links' ) );

		echo '<iframe src="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;item_ID='.$edited_Item->ID
					.'" name="'.$iframe_name.'" width="100%" marginwidth="0" height="160" marginheight="0" align="top" scrolling="auto" frameborder="0" id="attachmentframe"></iframe>';

		$Form->end_fieldset();
	}
}

/**
 * Creates a link to new category, with properties icon
 * @return string link url 
 */
function get_newcategory_link()
{
	global $dispatcher, $blog;
	$new_url = $dispatcher.'?ctrl=chapters&amp;action=new&amp;blog='.$blog;
	$link = ' <span class="floatright">'.action_icon( T_('Add new category'), 'properties', $new_url, '', 5, 1, array( 'target' => '_blank' ) ).'</span>';
	return $link;
}

/**
 * Allow recursive category selection.
 *
 * @todo Allow to use a dropdown (select) to switch between blogs ( CSS / JS onchange - no submit.. )
 *
 * @param Form
 * @param boolean true: use form fields, false: display only
 */
function cat_select( $Form, $form_fields = true )
{
	global $allow_cross_posting, $cache_categories,
					$blog, $current_blog_ID, $current_User, $edited_Item, $cat_select_form_fields;
	global $cat_sel_total_count, $dispatcher;

	$Form->begin_fieldset( get_newcategory_link().T_('Categories').get_manual_link('item_categories_fieldset'), array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

	$cat_sel_total_count = 0;
	$r ='';

	$cat_select_form_fields = $form_fields;

	cat_load_cache(); // make sure the caches are loaded

	$r .= '<table cellspacing="0" class="catselect">';
	$r .= cat_select_header();

	if( $allow_cross_posting >= 2 )
	{ // If BLOG cross posting enabled, go through all blogs with cats:
		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();

		/**
		 * @var Blog
		 */
		for( $l_Blog = & $BlogCache->get_first(); !is_null($l_Blog); $l_Blog = & $BlogCache->get_next() )
		{ // run recursively through the cats
			if( ! blog_has_cats( $l_Blog->ID ) )
				continue;

			if( ! $current_User->check_perm( 'blog_post_statuses', 'edit', false, $l_Blog->ID ) )
				continue;

			$r .= '<tr class="group"><td colspan="3">'.$l_Blog->dget('name')."</td></tr>\n";
			$cat_sel_total_count++; // the header uses 1 line

			$current_blog_ID = $l_Blog->ID;	// Global needed in callbacks
			$r .= cat_children( $cache_categories, $l_Blog->ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
		}
	}
	else
	{ // BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
									'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );

	}

	$r .= cat_select_new();

	$r .= '</table>';

	echo $r;

	$Form->end_fieldset();
}

/**
 * Header for {@link cat_select()}
 */
function cat_select_header()
{
	global $current_blog_ID, $blog, $allow_cross_posting;

	$r = '<thead><tr><th class="selector catsel_main" title="'.T_('Main category').'">'.T_('Main').'</th>';
	if( $allow_cross_posting >= 1 )
	{ // This is current blog or we allow moving posts accross blogs
		$r .= '<th class="selector catsel_extra" title="'.T_('Additional category').'">'.T_('Extra').'</th>';
	}
	$r .= '<th class="catsel_name">'.T_('Category').'</th><th width="1"><!-- for IE7 --></th></tr></thead>';
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
	global $current_blog_ID, $blog, $post_extracats, $edited_Item;
	global $creating, $allow_cross_posting, $cat_select_level, $cat_select_form_fields;
	global $cat_sel_total_count;

	$cat_sel_total_count++;

	$ChapterCache = & get_ChapterCache();
	$thisChapter = $ChapterCache->get_by_ID($cat_ID);
	$r = "\n".'<tr class="'.( $total_count%2 ? 'odd' : 'even' ).'">';

	// RADIO for main cat:
	if( ($current_blog_ID == $blog) || ($allow_cross_posting > 2) )
	{ // This is current blog or we allow moving posts accross blogs
		if( $cat_select_form_fields )
		{	// We want a form field:
			$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
						.T_('Select as MAIN category').'" value="'.$cat_ID.'"';
			if( $cat_ID == $edited_Item->main_cat_ID )
			{ // main cat of the Item or set as default main cat above
				$r .= ' checked="checked"';
			}
			$r .= ' id="sel_maincat_'.$cat_ID.'"';
			$r .= ' onclick="check_extracat(this);" /></td>';
		}
		else
		{	// We just want info:
			$r .= '<td class="selector catsel_main">'.bullet( $cat_ID == $edited_Item->main_cat_ID ).'</td>';
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
			// if( ($cat_ID == $edited_Item->main_cat_ID) || (in_array( $cat_ID, $post_extracats )) )  <--- We don't want to precheck the default cat because it will stay checked if we change the default main. On edit, the checkbox will always be in the array.
			if( (in_array( $cat_ID, $post_extracats )) )
			{
				$r .= ' checked="checked"';
			}
			$r .= ' id="sel_extracat_'.$cat_ID.'"';
			$r .= ' /></td>';
		}
		else
		{	// We just want info:
			$r .= '<td class="selector catsel_main">'.bullet( ($cat_ID == $edited_Item->main_cat_ID) || (in_array( $cat_ID, $post_extracats )) ).'</td>';
		}
	}

	$r .= '<td class="catsel_name"><label'
				.' for="'.( $allow_cross_posting
					? 'sel_extracat_'.$cat_ID
					: 'sel_maincat_'.$cat_ID ).'"'
				.' style="padding-left:'.($level-1).'em;">'
				.htmlspecialchars($thisChapter->name).'</label>'
				.' <a href="'.htmlspecialchars($thisChapter->get_permanent_url()).'" title="'.htmlspecialchars(T_('View category in blog.')).'">'
				.'&nbsp;&raquo;&nbsp; ' // TODO: dh> provide an icon instead? // fp> maybe the A(dmin)/B(log) icon from the toolbar? And also use it for permalinks to posts?
				.'</a></td>'
				.'<td width="1"><!-- for IE7 --></td></tr>'
				."\n";

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

/**
 * new category line for catselect table
 * @return string new row code
 */
function cat_select_new()
{
	global $cat_sel_total_count;
	$cat_sel_total_count++;
	$r = "\n".'<tr class="'.( $cat_sel_total_count%2 ? 'odd' : 'even' ).'">';
	// RADIO for new main cat:
	$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
						.T_('Select as MAIN category').'" value="0"';
	$r .= ' id="sel_maincat_new"';
	$r .= ' onclick="check_extracat(this);"/></td>';

	// CHECKBOX
	$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
						.T_('Select as an additional category').'" value="0" id="sel_extracat_new"/></td>';

	// INPUT TEXT for new category name
	$r .= '<td class="catsel_name">'
				.'<input maxlength="255" style="width: 100%;" value="" size="20" type="text" name="category_name" id="new_category_name" />'
				."</td>";
	$r .= '<td width="1">&nbsp<!-- for IE7 -->';
	$r .= "</td></tr>";
	return $r;
}


/**
 * Used by the items & the comments controllers
 */
function attach_browse_tabs()
{
	global $AdminUI, $Blog, $current_User, $dispatcher, $ItemTypeCache;
	$AdminUI->add_menu_entries(
			'items',
			array(
					'full' => array(
						'text' => T_('All'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=full&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'list' => array(
						'text' => T_('Posts'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=list&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'pages' => array(
						'text' => T_('Pages'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=pages&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'intros' => array(
						'text' => T_('Intros'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=intros&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'podcasts' => array(
						'text' => T_('Podcasts'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=podcasts&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'links' => array(
						'text' => T_('Sidebar links'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=links&amp;filter=restore&amp;blog='.$Blog->ID,
						),
				)
		);

	/* fp> Custom types should be variations of normal posts by default
	  I am ok with giving extra tabs to SOME of them but then the
		posttype list has to be transformed into a normal CREATE/UPDATE/DELETE (CRUD)
		(see the stats>goals CRUD for an example of a clean CRUD)
		and each post type must have a checkbox like "use separate tab"
		Note: you can also make a select list "group with: posts|pages|etc...|other|own tab"

		$ItemTypeCache = & get_ItemTypeCache();
		$default_post_types = array(1,1000,1500,1520,1530,1570,1600,2000,3000);
		$items_types = array_values( array_keys( $ItemTypeCache->get_option_array() ) );
		// a tab for custom types
		if ( array_diff($items_types,$default_post_types) )
		{
			$AdminUI->add_menu_entries(
					'items',
					array(
						'custom' => array(
							'text' => T_('Custom Types'),
							'href' => $dispatcher.'?ctrl=items&amp;tab=custom&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					)
			);
		}
	*/

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$AdminUI->add_menu_entries(
				'items',
				array(
						'tracker' => array(
							'text' => T_('Tracker'),
							'href' => $dispatcher.'?ctrl=items&amp;tab=tracker&amp;filter=restore&amp;blog='.$Blog->ID,
							),
					)
			);
	}

	if( $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
	{
		$AdminUI->add_menu_entries(
				'items',
				array(
						'comments' => array(
							'text' => T_('Comments'),
							'href' => $dispatcher.'?ctrl=comments&amp;blog='.$Blog->ID,
							),
					)
			);
	}


	// What perms do we have?
	$coll_settings_perm = $current_User->check_perm( 'blog_properties', 'any', false, $Blog->ID );
	$settings_url = '?ctrl=itemtypes&amp;tab=settings&amp;tab3=types';
	if( $coll_chapters_perm = $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
	{
		$settings_url = '?ctrl=chapters&amp;blog='.$Blog->ID;
	}

	if( $coll_settings_perm || $coll_chapters_perm )
	{
		$AdminUI->add_menu_entries(
			'items',
			array(
				'settings' => array(
					'text' => T_('Content settings'),
					'href' => $settings_url,
					)
				)
			);

		if( $coll_chapters_perm )
		{
			$AdminUI->add_menu_entries( array('items','settings'), array(
				'chapters' => array(
					'text' => T_('Categories'),
					'href' => '?ctrl=chapters&amp;blog='.$Blog->ID
					),
				)
			);
		}

		if( $coll_settings_perm )
		{
			$AdminUI->add_menu_entries( array('items','settings'), array(
				'types' => array(
					'text' => T_('Post types'),
					'title' => T_('Post types management'),
					'href' => '?ctrl=itemtypes&amp;tab=settings&amp;tab3=types'
					),
				'statuses' => array(
					'text' => T_('Post statuses'),
					'title' => T_('Post statuses management'),
					'href' => '?ctrl=itemstatuses&amp;tab=settings&amp;tab3=statuses'
					),
				)
			);
		}
	}
}



/**
 * Allow to select status/visibility
 */
function visibility_select( & $Form, $post_status, $mass_create = false )
{
	global $current_User, $Blog;

	$sharing_options = array();

	if( $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'published', T_('Published').' <span class="notes">'.T_('(Public)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!protected', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'protected', T_('Protected').' <span class="notes">'.T_('(Members only)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!private', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'private', T_('Private').' <span class="notes">'.T_('(You only)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'draft', T_('Draft').' <span class="notes">'.T_('(Not published!)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'deprecated', T_('Deprecated').' <span class="notes">'.T_('(Not published!)').'</span>' );
	}

	if( !$mass_create && $current_User->check_perm( 'blog_post!redirected', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'redirected', T_('Redirected').' <span class="notes">(301)</span>' );
	}

	$Form->radio( 'post_status', $post_status, $sharing_options, '', true );
}


/**
 * Selection of the issue date
 *
 * @todo dh> should display erroneous values (e.g. when giving invalid date) as current (form) value, too.
 * @param Form
 */
function issue_date_control( $Form, $break = false )
{
	global $edited_Item;

	echo T_('Issue date').':<br />';

	echo '<label><input type="radio" name="item_dateset" id="set_issue_date_now" value="0" '
				.( ($edited_Item->dateset == 0) ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Update to NOW').'</strong></label>';

	if( $break )
	{
		echo '<br />';
	}

	echo '<label><input type="radio" name="item_dateset" id="set_issue_date_to" value="1" '
				.( ($edited_Item->dateset == 1) ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Set to').':</strong></label>';
	$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), '' );
	echo ' '; // allow wrapping!
	$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '', 'hh:mm:ss', '' );
	echo ' '; // allow wrapping!

	// Autoselect "change date" is the date is changed.
	?>
	<script>
	jQuery( function()
			{
				jQuery('#item_issue_date, #item_issue_time').change(function()
				{
					jQuery('#set_issue_date_to').attr("checked", "checked")
				})
			}
		)
	</script>
	<?php

}


/**
 * Template tag: Link to an item identified by its url title / slug / name
 *
 * Note: this will query the database. Thus, in most situations it will make more sense
 * to use a hardcoded link. This tag can be useful for prototyping location independant
 * sites.
 */
function item_link_by_urltitle( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'urltitle'    => NULL,  // MUST BE SPECIFIED
			'before'      => ' ',
			'after'       => ' ',
			'text'        => '#',
		), $params );

  /**
	 * @var ItemCache
	 */
	$ItemCache = & get_ItemCache();

  /**
	 * @var Item
	 */
	$Item = & $ItemCache->get_by_urltitle( $params['urltitle'], false );

	if( empty($Item) )
	{
		return false;
	}

	$Item->permanent_link( $params );
}


function echo_publish_buttons( $Form, $creating, $edited_Item )
{
	global $Blog, $current_User;
	global $next_action; // needs to be passed out for echo_publishnowbutton_js( $action )

	// ---------- PREVIEW ----------
	$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl
	$Form->button( array( 'button', '', T_('Preview'), 'PreviewButton', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );

	// ---------- SAVE ----------
	$next_action = ($creating ? 'create' : 'update');
	$Form->submit( array( 'actionArray['.$next_action.'_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & edit'), 'SaveEditButton' ) );
	$Form->submit( array( 'actionArray['.$next_action.']', /* TRANS: This is the value of an input submit button */ T_('Save'), 'SaveButton' ) );

	if( $edited_Item->status == 'draft'
			&& $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID )	// TODO: if we actually set the primary cat to another blog, we may still get an ugly perm die
			&& $current_User->check_perm( 'edit_timestamp', 'edit', false ) )
	{	// Only allow publishing if in draft mode. Other modes are too special to run the risk of 1 click publication.
		$publish_style = 'display: inline';
	}
	else
	{
		$publish_style = 'display: none';
	}
	$Form->submit( array(
		'actionArray['.$next_action.'_publish]',
		/* TRANS: This is the value of an input submit button */ T_('Publish!'),
		'SaveButton',
		'',
		$publish_style
	) );
}

/**
 * Output JavaScript code to dynamically show popup files attachment window
 */
function echo_attaching_files_button_js()
{
	global $dispatcher;
	global $edited_Item;
	$iframe_name = 'attach_'.generate_random_key( 16 );
	?>
	<script type="text/javascript">
			pop_up_window( '<?php echo $dispatcher; ?>?ctrl=files&amp;mode=upload&amp;iframe_name=<?php echo $iframe_name ?>&amp;fm_mode=link_item&amp;item_ID=<?php echo $edited_Item->ID?>', 'fileman_upload', 1000 );
	</script>
	<?php
}

/**
 * Output JavaScript code to set hidden filed is_attachments
 * which indicate that we must show attachments files popup window
 */
function echo_set_is_attachments()
{
	?>
	<script type="text/javascript">
			jQuery( '#itemform_createlinks td input' ).click( function()
			{
				jQuery( 'input[name=is_attachments]' ).attr("value", "true");
			} );
	</script>
	<?php
}

/**
 * Output JavaScript code for "Add/Link files" link
 */
function echo_link_files_js()
{
	?>
	<script type="text/javascript">
			jQuery( '#title_file_add' ).click( function()
			{
				jQuery( 'input[name=is_attachments]' ).attr("value", "true");
				jQuery( '#itemform_createlinks input[name=actionArray[create_edit]]' ).click();
			} );
	</script>
	<?php
}

/**
 * Output JavaScript code to dynamically show or hide the "Publish NOW!"
 * button depending on the selected post status.
 *
 * This function is used by the simple and expert write screens.
 */
function echo_publishnowbutton_js()
{
	global $next_action;
	?>
	<script type="text/javascript">
		jQuery( '#itemform_visibility input[type=radio]' ).click( function()
		{
			var publishnow_btn = jQuery( '.edit_actions input[name=actionArray[<?php echo $next_action; ?>_publish]]' );

			if( this.value != 'draft' )
			{	// Hide the "Publish NOW !" button:
				publishnow_btn.css( 'display', 'none' );
			}
			else
			{	// Show the button:
				publishnow_btn.css( 'display', 'inline' );
			}
		} );
	</script>
	<?php
}

/**
 * Assert that the supplied post type can be used by the current user in
 * the post's extra categories' context.
 *
 * @param array The extra cats of the post.
 */
function check_perm_posttype( $post_extracats )
{
	global $posttypes_perms, $item_typ_ID, $current_User;

	static $posttype2perm = NULL;
	if( $posttype2perm === NULL )
	{	// "Reverse" the $posttypes_perms array:
		// Tblue> Possibly bloat; this function usually is invoked only
		//        once, thus it *may* be better to simply iterate through
		//        the $posttypes_perms array every time and look for the
		//        post type ID.
		foreach( $posttypes_perms as $l_permname => $l_posttypes )
		{
			foreach( $l_posttypes as $ll_posttype )
			{
				$posttype2perm[$ll_posttype] = $l_permname;
			}
		}
	}

	// Tblue> Usually, when this function is invoked, item_typ_ID is not
	//        loaded yet... If it is, it doesn't get loaded again anyway.
	//        Item::load_from_Request() uses param() again, in case this
	//        function wasn't called yet when load_from_Request() gets
	//        called (does this happen?).
	param( 'item_typ_ID', 'integer', true /* require input */ );
	if( ! isset( $posttype2perm[$item_typ_ID] ) )
	{	// Allow usage:
		return;
	}

	// Check permission:
	$current_User->check_perm( 'cats_'.$posttype2perm[$item_typ_ID], 'edit', true /* assert */, $post_extracats );
}


/**
 * Mass create.
 *
 * Create multiple posts from one post.
 *
 * @param object Instance of Item class (by reference).
 * @param boolean true if create paragraphs at each line break
 * @return array The posts, by reference.
 */
function & create_multiple_posts( & $Item, $linebreak = false )
{
	$Items = array();

	// Parse text into titles and contents:
	$current_title = '';
	$current_data  = '';

	// Append a newline to the end of the original contents to make sure
	// that the last item gets created - this saves a second loop.
	foreach( explode( "\n", $Item->content."\n" ) as $line )
	{
		$line = trim( strip_tags( $line ) );

		if( $current_title === '' && $line !== '' )
		{	// We got a new title:
			$current_title = $line;
		}
		elseif( $current_title !== '' )
		{
			if( $line !== '' )
			{	// We got a new paragraph for this post:
				if( $linebreak )
				{
					$current_data .= '<p>'.$line.'</p>';
				}
				else
				{
					$current_data .= $line.' ';
				}
			}
			else
			{	// End of this post:
				$new_Item = duplicate( $Item );

				$new_Item->set_param( 'title', 'string', $current_title );

				if( !$linebreak )
				{
					$current_data = trim( $current_data );
				}
				$new_Item->set_param( 'content', 'string', $current_data );

				$Items[] = $new_Item;

				$current_title = '';
				$current_data  = '';
			}
		}
	}

	return $Items;
}


function check_categories( & $post_category, & $post_extracats )
{
	$post_category = param( 'post_category', 'integer', true );
	$post_extracats = param( 'post_extracats', 'array', array() );
	global $Messages, $blog;

	if( ! $post_category || in_array(0, $post_extracats ) )
	{
		load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
		$GenericCategoryCache = & get_ChapterCache();

		$category_name = param( 'category_name', 'string', true );
		$new_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $blog );
		$new_GenericCategory->set( 'name', $category_name );
		if( $new_GenericCategory->dbinsert() !== false )
		{
			$Messages->add( T_('New category created.'), 'success' );
			if( ! $post_category )
			{
				$post_category = $new_GenericCategory->ID;
			}

			if( ( $extracat_key = array_search( '0', $post_extracats ) ) || $post_extracats[0] == '0' )
			{
				if( $extracat_key )
				{
					unset($post_extracats[$extracat_key]);
				}
				else
				{
					unset($post_extracats[0]);
				}
				$post_extracats[] = $new_GenericCategory->ID;
			}

			$GenericCategoryCache->add($new_GenericCategory);
		}
		else
		{
			$Messages->add( T_('New category creation failed.'), 'error' );
			return false;
		}
	}

	// make sure main cat is in extracat list and there are no duplicates
	$post_extracats[] = $post_category;
	$post_extracats = array_unique( $post_extracats );

	return true;
}


function echo_onchange_newcat()
{
?>
	<script type="text/javascript">
		jQuery( '#new_category_name' ).keypress( function()
		{
			var newcategory_radio = jQuery( '#sel_maincat_new' );
			if( ! newcategory_radio.attr('checked') )
			{
				newcategory_radio.attr('checked', true);
				jQuery( '#sel_extracat_new' ).attr('checked', true);
			}
		} );
	</script>
<?php
}

/**
 * Make the slug field automatically update when a title is typed.
 * Variable slug_changed hold true if slug was manually changed 
 * (we already do not need autocomplete) and false in other case.
 * 
 */
function echo_slug_filler()
{
?>
	<script type="text/javascript">
		var slug_changed = false;
		jQuery( '#post_title' ).keyup( function()
		{
			if(!slug_changed)
			{
				jQuery( '#post_urltitle' ).val( jQuery( '#post_title' ).val() );
			}
		} );

		jQuery( '#post_urltitle' ).change( function()
		{
			slug_changed = true;
		} );
	</script>
<?php
}

/*
 * $Log$
 * Revision 1.90  2010/02/13 16:22:30  efy-yury
 * slug field autofill
 *
 * Revision 1.89  2010/02/08 17:53:10  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.88  2010/02/05 09:51:34  efy-asimo
 * create categories on the fly
 *
 * Revision 1.87  2010/02/04 16:41:11  efy-yury
 * add "Add/Link files" link
 *
 * Revision 1.86  2010/02/02 21:17:17  efy-yury
 * update: attachments popup now opens when pushed the button 'Save and start attaching files'
 *
 * Revision 1.85  2010/01/30 18:55:30  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.84  2010/01/17 16:15:22  sam2kb
 * Localization clean-up
 *
 * Revision 1.83  2010/01/03 18:52:57  fplanque
 * crumbs...
 *
 * Revision 1.82  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.81  2009/12/08 20:16:12  fplanque
 * Better handling of the publish! button on post forms
 *
 * Revision 1.80  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.79  2009/11/19 17:25:09  tblue246
 * Make evo_iconv_transliterate() aware of the post locale
 *
 * Revision 1.78  2009/10/26 17:58:57  efy-maxim
 * mass create fix and design improvement
 *
 * Revision 1.77  2009/10/19 13:28:15  efy-maxim
 * paragraphs at each line break or separate posts with a blank line
 *
 * Revision 1.76  2009/10/18 11:29:42  efy-maxim
 * 1. mass create in 'All' tab; 2. "Text Renderers" and "Comments"
 *
 * Revision 1.75  2009/10/15 20:54:25  tblue246
 * create_multiple_posts(): Code improvements, e. g. removed second loop.
 *
 * Revision 1.74  2009/10/12 11:59:44  efy-maxim
 * Mass create
 *
 * Revision 1.73  2009/10/07 00:52:00  sam2kb
 * Titles in cat_select_header()
 *
 * Revision 1.72  2009/10/01 18:50:12  tblue246
 * convert_charset(): Trying to remove unreliable charset detection and modify all calls accordingly -- needs testing to ensure all charset conversions work as expected.
 *
 * Revision 1.71  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.70  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.69  2009/09/24 13:50:31  efy-sergey
 * Moved the Global Settings>Post types & Post statuses tabs to "Posts / Comments > Settings > Post types & Post statuses"
 *
 * Revision 1.68  2009/09/20 21:44:01  blueyed
 * whitespace
 *
 * Revision 1.67  2009/09/20 18:13:20  fplanque
 * doc
 *
 * Revision 1.66  2009/09/20 13:59:13  waltercruz
 * Adding a tab to show custom types (will be displayed only if you have custom types)
 *
 * Revision 1.65  2009/09/15 19:31:54  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.64  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.63  2009/09/13 21:51:01  blueyed
 * Fix bpost_count_words to use unicode. Fixes the russian test.
 *
 * Revision 1.62  2009/09/13 21:29:21  blueyed
 * MySQL query cache optimization: remove information about seconds from post_datestart and item_issue_date.
 *
 * Revision 1.61  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.60  2009/08/23 20:08:27  tblue246
 * - Check extra categories when validating post type permissions.
 * - Removed User::check_perm_catusers() + Group::check_perm_catgroups() and modified User::check_perm() to perform the task previously covered by these two methods, fixing a redundant check of blog group permissions and a malfunction introduced by the usage of Group::check_perm_catgroups().
 *
 * Revision 1.59  2009/08/23 13:42:48  tblue246
 * Doc. Please read.
 *
 * Revision 1.58  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.57  2009/08/22 17:07:08  tblue246
 * Minor/coding style
 *
 * Revision 1.56  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.55  2009/07/06 22:49:11  fplanque
 * made some small changes on "publish now" handling.
 * Basically only display it for drafts everywhere.
 *
 * Revision 1.54  2009/07/06 16:04:08  tblue246
 * Moved echo_publishnowbutton_js() to _item.funcs.php
 *
 * Revision 1.53  2009/07/04 19:19:08  tblue246
 * Bugfix
 *
 * Revision 1.52  2009/07/04 19:14:30  tblue246
 * bpost_count_words(): Use str_word_count if the \p escape sequence is not supported. This is the case for PHP versions before 4.4.0 and 5.1.0.
 *
 * Revision 1.51  2009/07/01 23:54:05  fplanque
 * doc
 *
 * Revision 1.50  2009/06/28 20:02:43  fplanque
 * ROLLBACK: b2evo requires PHP 4.3 as a minimum version.This is already a very old version. Code to accomodate older versions is bloat.
 *
 * Revision 1.49  2009/06/12 22:02:17  blueyed
 * cat_select: do not link the category name, but add an extra link: cat name is useful for selecting.
 *
 * Revision 1.48  2009/06/12 21:39:46  blueyed
 * cat_select: link each category name to the permanent url
 *
 * Revision 1.47  2009/06/01 17:42:47  tblue246
 * bpost_count_words(): Better PCRE test
 *
 * Revision 1.46  2009/06/01 16:56:27  tblue246
 * Bugfix
 *
 * Revision 1.45  2009/06/01 16:36:54  tblue246
 * Make bpost_count_words() work with extreme old PHP versions and versions not supporting the PCRE escape \p
 *
 * Revision 1.44  2009/05/25 19:47:45  fplanque
 * better linking of files
 *
 * Revision 1.43  2009/04/14 22:30:05  blueyed
 * TODO for bpost_count_words
 *
 * Revision 1.42  2009/04/14 14:57:48  tblue246
 * Trying to fix bpost_count_words()
 *
 * Revision 1.41  2009/04/13 22:33:23  tblue246
 * Doc
 *
 * Revision 1.40  2009/03/15 18:46:37  fplanque
 * please don't do whitespace edits
 *
 * Revision 1.39  2009/03/15 12:33:00  tblue246
 * minor
 *
 * Revision 1.38  2009/03/13 00:53:13  fplanque
 * super nasty sneaky bug
 *
 * Revision 1.36  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.35  2009/03/03 21:21:09  blueyed
 * Deprecate get_the_category_by_ID and replace its usage with ChapterCache
 * in core.
 *
 * Revision 1.34  2009/02/22 23:14:29  fplanque
 * partial rollback of stuff that can't be right...
 *
 * Revision 1.33  2009/02/21 23:10:43  fplanque
 * Minor
 *
 * Revision 1.32  2009/02/02 00:04:28  tblue246
 * Fixing doc
 *
 * Revision 1.31  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 * Revision 1.30  2009/01/23 21:34:52  fplanque
 * fixed UGLY bug on page 2,3,4
 *
 * Revision 1.29  2009/01/23 17:23:09  fplanque
 * doc/minor
 *
 * Revision 1.28  2009/01/22 18:44:56  blueyed
 * Fix E_NOTICE if there is no featured item. Add TODO about this assignment.
 *
 * Revision 1.27  2009/01/21 22:26:26  fplanque
 * Added tabs to post browsing admin screen All/Posts/Pages/Intros/Podcasts/Comments
 *
 * Revision 1.26  2009/01/21 20:33:49  fplanque
 * different display between featured and intro posts
 *
 * Revision 1.25  2009/01/21 18:23:26  fplanque
 * Featured posts and Intro posts
 *
 * Revision 1.24  2009/01/19 21:40:59  fplanque
 * Featured post proof of concept
 *
 * Revision 1.23  2008/12/28 23:35:51  fplanque
 * Autogeneration of category/chapter slugs(url names)
 *
 * Revision 1.22  2008/12/27 21:09:28  fplanque
 * minor
 *
 * Revision 1.21  2008/12/23 02:23:05  tblue246
 * Make bpost_count_words() work more accurate. Inaccuracy reported by sam2kb ( http://forums.b2evolution.net/viewtopic.php?t=16596 ).
 *
 * Revision 1.20  2008/12/22 01:56:54  fplanque
 * minor
 *
 * Revision 1.19  2008/12/09 21:57:37  tblue246
 * PHPDoc; strip a possible dash (-) at the end of an URL title which could prevent access to the post when a trailing dash is used to identify tag page URLs (see http://forums.b2evolution.net/viewtopic.php?p=84288 ).
 *
 * Revision 1.18  2008/09/23 07:56:47  fplanque
 * Demo blog now uses shared files folder for demo media + more images in demo posts
 *
 * Revision 1.17  2008/09/23 05:26:38  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.16  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.15  2008/04/14 16:24:39  fplanque
 * use ActionArray[] to make action handlign more robust
 *
 * Revision 1.14  2008/04/13 20:40:06  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.13  2008/04/03 19:37:37  fplanque
 * category selector will be smaller if less than 11 cats
 *
 * Revision 1.12  2008/04/03 19:33:27  fplanque
 * category selector will be smaller if less than 11 cats
 *
 * Revision 1.11  2008/04/03 14:54:34  fplanque
 * date fixes
 *
 * Revision 1.10  2008/03/22 19:32:22  fplanque
 * minor
 *
 * Revision 1.9  2008/03/22 15:20:19  fplanque
 * better issue time control
 *
 * Revision 1.8  2008/03/21 16:07:03  fplanque
 * longer post slugs
 *
 * Revision 1.7  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/12/21 21:52:51  fplanque
 * added tag
 *
 * Revision 1.5  2007/09/23 18:57:15  fplanque
 * filter handling fixes
 *
 * Revision 1.4  2007/09/07 20:11:18  fplanque
 * Better category selector
 *
 * Revision 1.3  2007/09/03 20:01:53  blueyed
 * Fixed "Add a new category »" link (blog param)
 *
 * Revision 1.2  2007/09/03 16:44:28  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 11:00:25  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.52  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.51  2007/05/14 02:43:05  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.50  2007/05/13 20:44:11  fplanque
 * url fix
 *
 * Revision 1.49  2007/05/09 01:01:32  fplanque
 * permissions cleanup
 *
 * Revision 1.48  2007/05/07 18:03:28  fplanque
 * cleaned up skin code a little
 *
 * Revision 1.47  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.46  2007/03/26 14:21:30  fplanque
 * better defaults for pages implementation
 *
 * Revision 1.45  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.44  2007/03/18 00:31:18  fplanque
 * Delegated MainList init to skin *pages* which need it.
 *
 * Revision 1.43  2007/03/11 23:56:02  fplanque
 * fixed some post editing oddities / variable cleanup (more could be done)
 *
 * Revision 1.42  2007/03/03 01:14:12  fplanque
 * new methods for navigating through posts in single item display mode
 *
 * Revision 1.41  2007/02/12 15:42:40  fplanque
 * public interface for looping over a cache
 *
 * Revision 1.40  2007/02/06 13:34:20  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.39  2006/12/23 23:37:35  fplanque
 * refactoring / Blog::get_default_cat_ID()
 *
 * Revision 1.38  2006/12/16 17:05:55  blueyed
 * todo
 *
 * Revision 1.37  2006/12/15 23:31:21  fplanque
 * reauthorized _ in urltitles.
 * No breaking of legacy permalinks.
 * - remains the default placeholder though.
 *
 * Revision 1.36  2006/12/12 23:23:30  fplanque
 * finished post editing v2.0
 *
 * Revision 1.35  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
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
 */
?>
