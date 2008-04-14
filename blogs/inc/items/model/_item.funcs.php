<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
				) );
		}
		// else: we are either in single or in page mode

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
 * Validate URL title
 *
 * Using title as a source if url title is empty.
 * We allow up to 200 chars (which is ridiculously long) for WP import compatibility.
 *
 * @param string url title to validate
 * @param string real title to use as a source if $urltitle is empty (encoded in $evo_charset)
 * @param integer ID of post
 * @return string validated url title
 */
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false,
															$dbprefix = 'post_', $dbIDname = 'post_ID', $dbtable = 'T_items__item' )
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
	load_funcs('locales/_charset.funcs.php');
	$urltitle = replace_special_chars($urltitle);

	// Make everything lowercase
	$urltitle = strtolower( $urltitle );

	// Normalize to 40 chars + a number
	preg_match( '/^(.*?)((-|_)+([0-9]+))?$/', $urltitle, $matches );
	$urlbase = substr( $matches[1], 0, 200 );
	$urltitle = $urlbase;
	if( ! empty( $matches[4] ) )
	{
		$urltitle = $urlbase.'-'.$matches[4];
	}


	// CHECK FOR UNIQUENESS:
	// Find all occurrences of urltitle-number in the DB:
	$sql = 'SELECT '.$dbprefix.'urltitle
					  FROM '.$dbtable.'
					 WHERE '.$dbprefix."urltitle REGEXP '^".$urlbase."(-[0-9]+)?$'";
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
 */
function attachment_iframe( & $Form, $creating, & $edited_Item, & $Blog )
{
	global $admin_url;
	global $current_User;
	global $Settings;

	if( $creating )
	{	// Creating new post
		$fieldset_title = T_('Attachments');
		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_createlinks' ) );

		echo '<table cellspacing="0" cellpadding="0"><tr><td>';
   	$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & start attaching files'), 'SaveEditButton' ) );
		echo '</td></tr></table>';

		$Form->end_fieldset();
	}
	else
	{ // Editing post
		$fieldset_title = T_('Attachments').get_manual_link('post_attachments_fieldset');

		$fieldset_title .= ' - <a href="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;item_ID='.$edited_Item->ID
					.'" target="attachmentframe">'.get_icon( 'refresh', 'imgtag' ).' '.T_('Refresh').'</a>';

		if( $Settings->get( 'fm_enabled' )
			&& $current_User->check_perm( 'files', 'view' )
			&& $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:

			$fieldset_title .= ' - <a href="'.url_add_param( $Blog->get_filemanager_link(),
						'fm_mode=link_item&amp;item_ID='.$edited_Item->ID ).'" onclick="return pop_up_window( \''
						.url_add_param( $Blog->get_filemanager_link(), 'mode=upload&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID ).'\', \'fileman_upload\', 1000 )">'
						.get_icon( 'folder', 'imgtag' ).' '.T_('Attach files (popup)').'</a>';
		}

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_links' ) );

		echo '<iframe src="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;item_ID='.$edited_Item->ID
					.'" name="attachmentframe" width="100%" marginwidth="0" height="160" marginheight="0" align="top" scrolling="auto" frameborder="0" id="attachmentframe"></iframe>';

		$Form->end_fieldset();
	}
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
	global $cat_sel_total_count;

	$Form->begin_fieldset( T_('Categories').get_manual_link('item_categories_fieldset'), array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

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
		$BlogCache = & get_Cache('BlogCache');

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

	$r .= '</table>';

	if( $current_User->check_perm( 'blog_cats', '', false, $blog ) )
	{
		$r .= '<p class="extracatnote"><a href="admin.php?ctrl=chapters&amp;action=new&amp;blog='.$blog.'">'.T_('Add a new category').' &raquo;</a></p>';
	}

	if( $cat_sel_total_count > 10 )	// Check in IE for adjusting threshhold. IE lines are FAT
	{	// display within a div of constrained height
  	echo '<div class="extracats"><div>';
		echo $r;
		echo '</div></div>';
	}
	else
	{	// Plain display
		echo $r;
	}

	$Form->end_fieldset();
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
	global $current_blog_ID, $blog, $post_extracats, $edited_Item;
	global $creating, $allow_cross_posting, $cat_select_level, $cat_select_form_fields;
	global $cat_sel_total_count;

	$cat_sel_total_count++;

	$this_cat = get_the_category_by_ID( $cat_ID );
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


/**
 * Used by the items & the comments controllers
 */
function attach_browse_tabs()
{
	global $AdminUI, $Blog, $current_User;

	$AdminUI->add_menu_entries(
			'items',
			array(
					'full' => array(
						'text' => T_('Full posts'),
						'href' => 'admin.php?ctrl=items&amp;tab=full&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'list' => array(
						'text' => T_('Post list'),
						'href' => 'admin.php?ctrl=items&amp;tab=list&amp;filter=restore&amp;blog='.$Blog->ID,
						),
				)
		);

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$AdminUI->add_menu_entries(
				'items',
				array(
						'tracker' => array(
							'text' => T_('Tracker'),
							'href' => 'admin.php?ctrl=items&amp;tab=tracker&amp;filter=restore&amp;blog='.$Blog->ID,
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
							'href' => 'admin.php?ctrl=comments&amp;blog='.$Blog->ID,
							),
					)
			);
	}
}



/**
 * Allow to select status/visibility
 */
function visibility_select( & $Form, $post_status )
{
	global $current_User, $Blog;

	$sharing_options = array();

	if( $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'published', T_('Published').' <span class="notes">'.T_('(Public)').'</span>' );

	if( $current_User->check_perm( 'blog_post!protected', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'protected', T_('Protected').' <span class="notes">'.T_('(Members only)').'</span>' );

	if( $current_User->check_perm( 'blog_post!private', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'private', T_('Private').' <span class="notes">'.T_('(You only)').'</span>' );

	if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'draft', T_('Draft').' <span class="notes">'.T_('(Not published!)').'</span>' );

	if( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'deprecated', T_('Deprecated').' <span class="notes">'.T_('(Not published!)').'</span>' );

	if( $current_User->check_perm( 'blog_post!redirected', 'edit', false, $Blog->ID ) )
		$sharing_options[] = array( 'redirected', T_('Redirected').' <span class="notes">'.T_('(301)').'</span>' );

	$Form->radio( 'post_status', $post_status, $sharing_options, '', true );
}


/**
 * Selection of the issue date
 *
 * @param Form
 */
function issue_date_control( $Form, $break = false )
{
	global $edited_Item, $set_issue_date;

	echo '<label><input type="radio" name="set_issue_date" id="set_issue_date_now" value="now" '
				.( ($set_issue_date == 'now') ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Update issue date to NOW').'</strong></label>';

	if( $break )
	{
		echo '<br />';
	}

	echo '<label><input type="radio" name="set_issue_date" id="set_issue_date_to" value="set" '
				.( ($set_issue_date == 'set') ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Set to').':</strong></label>';
	$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), '' );
	echo ' '; // allow wrapping!
	$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '', 'hh:mm:ss', '' );
	echo ' '; // allow wrapping!

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
	$ItemCache = & get_Cache( 'ItemCache' );

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

/*
 * $Log$
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
