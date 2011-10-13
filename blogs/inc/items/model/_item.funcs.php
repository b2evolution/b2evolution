<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
	global $preview;
	global $disp;
	global $postIDlist, $postIDarray;

	$MainList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $items_nb_limit );	// COPY (FUNC)

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
 * Prepare the 'In-skin editing'.
 * 
 */
function init_inskin_editing()
{
	global $Blog, $edited_Item, $action, $form_action;
	global $item_tags, $item_title, $item_content;
	global $admin_url, $redirect_to, $advanced_edit_link;

	if( ! $Blog->get_setting( 'in_skin_editing' ) )
	{	// Redirect to the Back-office editing (setting is OFF)
		header_redirect( $admin_url.'?ctrl=items&action=new&blog='.$Blog->ID );
	}

	// Post ID, go from $_GET when we edit post from Front-office
	$post_ID = param( 'p', 'integer', 0 );

	if( $post_ID > 0 )
	{	// Edit post
		global $post_extracats;
		$action = 'edit';

		$ItemCache = & get_ItemCache ();
		$edited_Item = $ItemCache->get_by_ID ( $post_ID );

		check_categories_nosave ( $post_category, $post_extracats );
		$post_extracats = postcats_get_byID( $post_ID );

		$redirect_to = url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' );
	}
	else if( empty( $action ) )
	{	// Create new post (from Front-office)
		$action = 'new';

		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();
		$edited_Item->set( 'status', 'published' );
		$edited_Item->set( 'hideteaser', 0 );
		check_categories_nosave ( $post_category, $post_extracats );

		$redirect_to = url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID, '&' );
	}

	// Used in the edit form
	$item_title = $edited_Item->title;
	$item_content = $edited_Item->content;
	$item_tags = implode( ', ', $edited_Item->get_tags() );

	// Get an url for a link 'Go to advanced edit screen'
	$mode_editing = param( 'mode_editing', 'string', 'simple' );
	$entries = get_item_edit_modes( $Blog->ID, $action, $admin_url, 'blog='.$Blog->ID );
	$advanced_edit_link = $entries[$mode_editing];

	$form_action = get_samedomain_htsrv_url().'item_edit.php';
}

/**
 * Return an Item if an Intro or a Featured item is available for display in current disp.
 *
 * @return Item
 */
function & get_featured_Item()
{
	global $Blog;
	global $disp, $disp_detail, $MainList;
	global $featured_displayed_item_ID;

	if( $disp != 'posts' || !isset($MainList) )
	{	// If we're not displaying postS, don't display a feature post on top!
		$Item = NULL;
		return $Item;
	}

	$FeaturedList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), 1 );

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
 * New slugs will be cropped to 5 words so the URLs are not too long.
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

	// Make everything lowercase and use trim again after replace_special_chars
	$urltitle = strtolower( trim ( $urltitle ) );

	if( empty( $urltitle ) )
	{
		$urltitle = 'title';
	}

	// Leave only first 5 words in order to get a shorter URL 
	// (which is generally accepted as a better practice)
	// User can manually enter a very long URL if he wants
	$slug_changed = param( 'slug_changed' );
	if( $slug_changed == 0 )
	{ // this should only happen when the slug is auto generated
		$title_words = array();
		$title_words = explode( '-', $urltitle );
		$count_of_words = 5;
		if( count($title_words) > $count_of_words )
		{
			$urltitle = '';
			for( $i = 0; $i < $count_of_words; $i++ )
			{
				$urltitle .= $title_words[$i].'-';
			}
			//delete last '-'
			$urltitle = substr( $urltitle, 0, strlen($urltitle) - 1 );
		}

		// echo 'leaving 5 words: '.$urltitle.'<br />';
	}

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

	if( !$query_only )
	{
		// TODO: dh> this might get used to utilize the SlugCache instead of the processing below.
		#if( $post_ID && $dbtable == 'T_slug' )
		#{
		#	$existing_Slug = get_SlugCache()->get_by_name($urltitle, false, false);
		#	if( $existing_Slug )
		#	{
		#		$slug_field_name = preg_replace('~^slug_~', '', $dbIDname);
		#		if( $existing_Slug->get($slug_field_name) == $urltitle )
		#		{
		#			// OK
		#		}
		#	}
		#}
		// CHECK FOR UNIQUENESS:
		// Find all occurrences of urltitle-number in the DB:
		$sql = 'SELECT '.$dbSlugFieldName.', '.$dbIDname.'
						  FROM '.$dbtable.'
						 WHERE '.$dbSlugFieldName." REGEXP '^".$urlbase."(-[0-9]+)?$'";
		$exact_match = false;
		$highest_number = 0;
		$use_existing_number = NULL;
		foreach( $DB->get_results( $sql, ARRAY_A ) as $row )
		{
			$existing_urltitle = $row[$dbSlugFieldName];
			// echo "existing = $existing_urltitle <br />";
			if( $existing_urltitle == $urltitle && $row[$dbIDname] != $post_ID )
			{ // We have an exact match, we'll have to change the number.
				$exact_match = true;
			}
			if( preg_match( '/-([0-9]+)$/', $existing_urltitle, $matches ) )
			{ // This one has a number, we extract it:
				$existing_number = (int)$matches[1];

				if( ! isset($use_existing_number) && $row[$dbIDname] == $post_ID )
				{ // if there is a numbered entry for the current ID, use this:
					$use_existing_number = $existing_number;
				}

				if( $existing_number > $highest_number )
				{ // This is the new high
					$highest_number = $existing_number;
				}
			}
		}
		// echo "highest existing number = $highest_number <br />";

		if( $exact_match && !$query_only )
		{ // We got an exact (existing) match, we need to change the number:
			$number = $use_existing_number ? $use_existing_number : ($highest_number+1);
			$urltitle = $urlbase.'-'.$number;
		}
	}

	// echo "using = $urltitle <br />";

	if( !empty($orig_title) && $urltitle != $orig_title )
	{
		$Messages->add( sprintf(T_('Warning: the URL slug has been changed to &laquo;%s&raquo;.'), $urltitle ), 'note' );
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
function attachment_iframe( & $Form, $creating, & $edited_Item, & $Blog, $iframe_name = NULL )
{
	global $admin_url, $dispatcher;
	global $current_User;
	global $Settings;

	if( ! isset($GLOBALS['files_Module']) ) 
		return;

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

		if( $iframe_name == NULL )
		{
			$iframe_name = 'attach_'.generate_random_key( 16 );
		}

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
 * Get post category setting
 *
 * @param int blog id
 * @return int setting value
 */
function get_post_cat_setting( $blog )
{
	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( ! $Blog )
	{
		return -1;
	}
	$post_categories = $Blog->get_setting( 'post_categories' );
	switch( $post_categories )
	{
		case 'no_cat_post':
			return 0;
		case 'one_cat_post':
			return 1;
		case 'multiple_cat_post':
			return 2;
		case 'main_extra_cat_post':
			return 3;
	}
}

/**
 * Creates a link to new category, with properties icon
 *
 * @return string link url
 */
function get_newcategory_link()
{
	global $dispatcher, $blog;
	$new_url = $dispatcher.'?ctrl=chapters&amp;action=new&amp;blog='.$blog;
	$link = ' <span class="floatright">'.action_icon( T_('Add new category'), 'new', $new_url, '', 5, 1 ).'</span>';
	return $link;
}

/**
 * Allow recursive category selection.
 *
 * @todo Allow to use a dropdown (select) to switch between blogs ( CSS / JS onchange - no submit.. )
 *
 * @param Form
 * @param boolean true: use form fields, false: display only
 * @param boolean true: show links for add new category & manual
 */
function cat_select( $Form, $form_fields = true, $show_title_links = true )
{
	global $cache_categories, $blog, $current_blog_ID, $current_User, $edited_Item, $cat_select_form_fields;
	global $cat_sel_total_count, $dispatcher;
	global $rsc_url;

	if( get_post_cat_setting( $blog ) < 1 )
	{ // No categories for $blog
		return;
	}

	if( $show_title_links )
	{	// Use in Back-office
		$fieldset_title = get_newcategory_link().T_('Categories').get_manual_link('item_categories_fieldset');
	}
	else
	{
		$fieldset_title = T_('Categories');
	}

	$Form->begin_fieldset( $fieldset_title, array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

	$cat_sel_total_count = 0;
	$r ='';

	$cat_select_form_fields = $form_fields;

	cat_load_cache(); // make sure the caches are loaded

	$r .= '<table cellspacing="0" class="catselect">';
	if( get_post_cat_setting($blog) == 3 )
	{ // Main + Extra cats option is set, display header
		$r .= cat_select_header();
	}

	if( get_allow_cross_posting() >= 2 ||
	  ( isset( $blog) && get_post_cat_setting( $blog ) > 1 && get_allow_cross_posting() == 1 ) )
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

			$r .= '<tr class="group" id="catselect_blog'.$l_Blog->ID.'"><td colspan="3">'.$l_Blog->dget('name')."</td></tr>\n";
			$cat_sel_total_count++; // the header uses 1 line

			$current_blog_ID = $l_Blog->ID;	// Global needed in callbacks
			$r .= cat_children( $cache_categories, $l_Blog->ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			if( $blog == $l_Blog->ID )
			{
				$r .= cat_select_new();
			}
		}
	}
	else
	{ // BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
									'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
		$r .= cat_select_new();
	}

	$r .= '</table>';

	echo $r;

	if( isset($blog) && get_allow_cross_posting() ) 
	{
		echo '<script type="text/javascript">jQuery.getScript("'.$rsc_url.'js/jquery/jquery.scrollto.js", function () {
			jQuery("#itemform_categories").scrollTo( "#catselect_blog'.$blog.'" );
		});</script>';
	}
	$Form->end_fieldset();
}

/**
 * Header for {@link cat_select()}
 */
function cat_select_header()
{
	// main cat header
	$r = '<thead><tr><th class="selector catsel_main" title="'.T_('Main category').'">'.T_('Main').'</th>';

	// extra cat header
	$r .= '<th class="selector catsel_extra" title="'.T_('Additional category').'">'.T_('Extra').'</th>';

	// category header
	$r .= '<th class="catsel_name">'.T_('Category').'</th><!--[if IE 7]><th width="1"><!-- for IE7 --></th><![endif]--></tr></thead>';

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
	global $creating, $cat_select_level, $cat_select_form_fields;
	global $cat_sel_total_count;

	$cat_sel_total_count++;

	$ChapterCache = & get_ChapterCache();
	$thisChapter = $ChapterCache->get_by_ID($cat_ID);
	$r = "\n".'<tr class="'.( $total_count%2 ? 'odd' : 'even' ).'">';

	// RADIO for main cat:
	if( get_post_cat_setting($blog) != 2 )
	{ // if no "Multiple categories per post" option is set display radio
		if( ($current_blog_ID == $blog) || (get_allow_cross_posting( $blog ) >= 2) )
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
	}

	// CHECKBOX:
	if( get_post_cat_setting( $blog ) >= 2 )
	{ // We allow multiple categories or main + extra cat,  display checkbox:
		if( ($current_blog_ID == $blog) || ( get_allow_cross_posting( $blog ) % 2 == 1 )
			|| ( ( get_allow_cross_posting( $blog ) == 2 ) && ( get_post_cat_setting( $blog ) == 2 ) ) )
		{ // This is the current blog or we allow cross posting (select extra cat from another blog)
			if( $cat_select_form_fields )
			{	// We want a form field:
				$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
							.T_('Select as an additional category').'" value="'.$cat_ID.'"';
				// if( ($cat_ID == $edited_Item->main_cat_ID) || (in_array( $cat_ID, $post_extracats )) )  <--- We don't want to precheck the default cat because it will stay checked if we change the default main. On edit, the checkbox will always be in the array.
				if( in_array( $cat_ID, $post_extracats ) )
				{ // This category was selected
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
		else
		{ // Don't allow to select this cat as an extra cat
			$r .= '<td class="selector catsel_main">&nbsp;</td>';
		}
	}

	$BlogCache = & get_BlogCache();
	$r .= '<td class="catsel_name"><label'
				.' for="'.( get_post_cat_setting( $blog ) == 2
					? 'sel_extracat_'.$cat_ID
					: 'sel_maincat_'.$cat_ID ).'"'
				.' style="padding-left:'.($level-1).'em;">'
				.htmlspecialchars($thisChapter->name).'</label>'
				.' <a href="'.htmlspecialchars($thisChapter->get_permanent_url()).'" title="'.htmlspecialchars(T_('View category in blog.')).'">'
				.'&nbsp;&raquo;&nbsp; ' // TODO: dh> provide an icon instead? // fp> maybe the A(dmin)/B(log) icon from the toolbar? And also use it for permalinks to posts?
				.'</a></td>'
				.'<!--[if IE 7]><td width="1"><!-- for IE7 --></td><![endif]--></tr>'
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
	global $blog;
	$new_maincat = param( 'new_maincat', 'boolean', false );
	$new_extracat = param( 'new_extracat', 'boolean', false );
	if( $new_maincat || $new_extracat )
	{
		$category_name = param( 'category_name', 'string', '' );
	}
	else
	{
		$category_name = '';
	}

	global $cat_sel_total_count;
	$cat_sel_total_count++;
	$r = "\n".'<tr class="'.( $cat_sel_total_count%2 ? 'odd' : 'even' ).'">';

	if( get_post_cat_setting( $blog ) != 2 )
	{
		// RADIO for new main cat:
		$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
							.T_('Select as MAIN category').'" value="0"';
		if( $new_maincat )
		{
			$r.= ' checked="checked"';
		}
		$r .= ' id="sel_maincat_new"';
		$r .= ' onclick="check_extracat(this);"';
		$r .= '/></td>';
	}

	if( get_post_cat_setting( $blog ) >= 2 )
	{
		// CHECKBOX
		$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
							.T_('Select as an additional category').'" value="0"';
		if( $new_extracat )
		{
			$r.= ' checked="checked"';
		}
		$r .= 'id="sel_extracat_new"/></td>';
	}

	// INPUT TEXT for new category name
	$r .= '<td class="catsel_name">'
				.'<input maxlength="255" style="width: 100%;" value="'.$category_name.'" size="20" type="text" name="category_name" id="new_category_name" />'
				."</td>";
	$r .= '<!--[if IE 7]><td width="1">&nbsp</td><![endif]-->';
	$r .= "</tr>";
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
	{ // User has permission to edit published, draft or deprecated comments (at least one kind)
		$AdminUI->add_menu_entries(
				'items',
				array(
						'comments' => array(
							'text' => T_('Comments'),
							'href' => $dispatcher.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;filter=restore',
							),
					)
			);

		$AdminUI->add_menu_entries( array('items','comments'), array(
				'fullview' => array(
					'text' => T_('Full text view'),
					'href' => $dispatcher.'?ctrl=comments&amp;tab3=fullview&amp;filter=restore'
					),
				'listview' => array(
					'text' => T_('List view'),
					'href' => $dispatcher.'?ctrl=comments&amp;tab3=listview&amp;filter=restore'
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
			&& $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
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
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
 */
function echo_attaching_files_button_js( & $iframe_name )
{
	global $dispatcher;
	global $edited_Item;
	$iframe_name = 'attach_'.generate_random_key( 16 );
	?>
	<script type="text/javascript">
			pop_up_window( '<?php echo $dispatcher; ?>?ctrl=files&mode=upload&iframe_name=<?php echo $iframe_name ?>&fm_mode=link_item&item_ID=<?php echo $edited_Item->ID?>', 'fileman_upload', 1000 );
	</script>
	<?php
}

/**
 * Output JavaScript code to set hidden field is_attachments
 * which indicates that we must show attachments files popup window
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
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
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
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
 * Output Javascript for tags autocompletion.
 * @todo dh> a more facebook like widget would be: http://plugins.jquery.com/project/facelist
 *           "ListBuilder" is being planned for jQuery UI: http://wiki.jqueryui.com/ListBuilder
 */
function echo_autocomplete_tags()
{
	global $htsrv_url;

	$url_crumb = url_crumb('item');

	echo <<<EOD
	<script type="text/javascript">
	(function($){
		$(function() {
			function split(val) {
				return val.split(/\s*,\s*/);
			}
			function extractLast(term) {
				return split(term).pop();
			}

			$("#item_tags").autocomplete({
				source: function(request, response) {
					$.getJSON("${htsrv_url}async.php?action=get_tags&${url_crumb}", {
						term: extractLast(request.term)
					}, response);
				},
				search: function() {
					// custom minLength
					var term = extractLast(this.value);
					if (term.length < 1) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function(event, ui) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push("");
					this.value = terms.join(", ");
					return false;
				}
			});
		});
	})(jQuery);
	</script>
EOD;
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

/**
 *
 * Check if new category needs to be created or not (after post editing).
 * If the new category radio is checked creates the new category and set it to post category
 * If the new category checkbox is checked creates the new category and set it to post extracat
 *
 * Function is called during post creation or post update
 *
 * @param Object Post category (by reference).
 * @param Array Post extra categories (by reference).
 * @return boolean true - if there is no new category, or new category created succesfull; false if new category creation failed.
 */
function check_categories( & $post_category, & $post_extracats )
{
	$post_category = param( 'post_category', 'integer', -1 );
	$post_extracats = param( 'post_extracats', 'array', array() );
	global $Messages, $Blog, $blog;

	load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
	$GenericCategoryCache = & get_ChapterCache();

	if( $post_category == -1 )
	{ // no main cat select
		if( count( $post_extracats ) == 0 )
		{ // no extra cat select
			$post_category = $Blog->get_default_cat_ID();
		}
		else
		{ // first extracat become main_cat
			if( get_allow_cross_posting() >= 2 )
			{ // allow moving posts between different blogs is enabled, set first selected cat as main cat
				$post_category = $post_extracats[0];
			}
			else
			{ // allow moving posts between different blogs is disabled - we need a main cat from $blog
				foreach( $post_extracats as $cat )
				{
					if( get_catblog( $cat ) != $blog )
					{ // this cat is not from $blog
						continue;
					}
					// set first cat from $blog as main cat
					$post_category = $cat;
					break;
				}
				if( $post_category == -1 )
				{ // wasn't cat selected from $blog select a default as main cat
					$post_category = $Blog->get_default_cat_ID();
				}
			}
		}
		if( $post_category )
		{ // If main cat is not a new category, and has been autoselected
			$GenericCategory = & $GenericCategoryCache->get_by_ID( $post_category );
			$post_category_Blog = $GenericCategory->get_Blog();
			$Messages->add( sprintf( T_('The main category for this post has been automatically set to "%s" (Blog "%s")'),
				$GenericCategory->get_name(), $post_category_Blog->get( 'name') ), 'warning' );
		}
	}

	if( ! $post_category || in_array( 0, $post_extracats ) )	// if category key is 0 => means it is a new category
	{
		$category_name = param( 'category_name', 'string', true );
		if( $category_name == '' )
		{
			$show_error = ! $post_category;	// new main category without name => error message
			check_categories_nosave( $post_category, $post_extracats); // set up the category parameters
			if( $show_error )
			{ // new main category without name
				$Messages->add( T_('Please provide a name for new category.'), 'error' );
				return false;
			}
			return true;
		}

		$new_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $blog );	// create new category object
		$new_GenericCategory->set( 'name', $category_name );
		if( $new_GenericCategory->dbinsert() !== false )
		{
			$Messages->add( T_('New category created.'), 'success' );
			if( ! $post_category ) // if new category is main category
			{
				$post_category = $new_GenericCategory->ID;	// set the new ID
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

	if( get_allow_cross_posting() == 2 )
	{ // Extra cats in different blogs is disabled, check selected extra cats
		$post_category_blog = get_catblog( $post_category );
		$ignored_cats = '';
		foreach( $post_extracats as $key => $cat )
		{
			if( get_catblog( $cat ) != $post_category_blog )
			{ // this cat is not from main category blog, it has to be ingnored
				$GenericCategory = & $GenericCategoryCache->get_by_ID( $cat );
				$ignored_cats = $ignored_cats.$GenericCategory->get_name().', ';
				unset( $post_extracats[$key] );
			}
		}
		$ingnored_length = strlen( $ignored_cats );
		if( $ingnored_length > 2 )
		{ // ingnore list is not empty
			global $current_User, $admin_url;
			if( $current_User->check_perm( 'options', 'view', false ) )
			{
				$cross_posting_text = '<a href="'.$admin_url.'?ctrl=features">'.T_('cross-posting is disabled').'</a>';
			}
			else
			{
				$cross_posting_text = T_('cross-posting is disabled');
			}
			$ignored_cats = substr( $ignored_cats, 0, $ingnored_length - 2 );
			$Messages->add( sprintf( T_('The category selection "%s" was ignored since %s'),
				$ignored_cats, $cross_posting_text ), 'warning' );
		}
	}

	// make sure main cat is in extracat list and there are no duplicates
	$post_extracats[] = $post_category;
	$post_extracats = array_unique( $post_extracats );

	return true;
}

/*
 * Set up params for new category creation
 * Set main category to default category, if the current category does not exist yet
 * Delete non existing category from extracats
 * It is called after simple/expert tab switch, and can be called during post creation or modification
 *
 * @param Object Post category (by reference).
 * @param Array Post extra categories (by reference).
 *
 */
function check_categories_nosave( & $post_category, & $post_extracats )
{
	global $Blog;
	$post_category = param( 'post_category', 'integer', $Blog->get_default_cat_ID() );
	$post_extracats = param( 'post_extracats', 'array', array() );

	if( ! $post_category )	// if category key is 0 => means it is a new category
	{
		$post_category = $Blog->get_default_cat_ID();
		param( 'new_maincat', 'boolean', 1 );
	}

	if( ! empty( $post_extracats) && ( ( $extracat_key = array_search( '0', $post_extracats ) ) || $post_extracats[0] == '0' ) )
	{
		param( 'new_extracat', 'boolean', 1 );
		if( $extracat_key )
		{
			unset($post_extracats[$extracat_key]);
		}
		else
		{
			unset($post_extracats[0]);
		}
	}
}

/*
 * check the new category radio button, if the new category text has changed
 */
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
 * Automatically update the slug field when a title is typed.
 *
 * Variable slug_changed hold true if slug was manually changed
 * (we already do not need autocomplete) and false in other case.
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
			jQuery( '[name=slug_changed]' ).val( 1 );
		} );
	</script>
<?php
}


/**
 * Set slug_changed to 1 for cases when it is not needed trim slug
 */
function echo_set_slug_changed()
{
?>
	<script type="text/javascript">
		jQuery( '[name=slug_changed]' ).val( 1 );
	</script>
<?php
}


/**
 * Handle show_comments radioboxes on item list full view
 */
function echo_show_comments_changed()
{
?>
	<script type="text/javascript">
		jQuery( '[name |= show_comments]' ).change( function()
		{
			var item_id = $('#comments_container').attr('value');
			if( ! isDefined( item_id) )
			{ // if item_id is not defined, we have to show all comments from current blog 
				item_id = -1;
			}
			refresh_item_comments( item_id, 1 );
		} );
	</script>
<?php
}


/**
 * Display CommentList with the given filters
 * 
 * @param int blog
 * @param item item
 * @param array status filters
 * @param int limit
 * @param $comment_IDs
 * @param string comment IDs string to exclude from the list
 */
function echo_item_comments( $blog_ID, $item_ID, $statuses = array( 'draft', 'published', 'deprecated' ),
	$currentpage = 1, $limit = 20, $comment_IDs = array() )
{
	global $inc_path, $status_list, $Blog, $admin_url;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	global $CommentList;
	$CommentList = new CommentList2( $Blog );

	$exlude_ID_list = NULL;
	if( !empty($comment_IDs) )
	{
		$exlude_ID_list = '-'.implode( ",", $comment_IDs );
	}

	// if item_ID == -1 then don't use item filter! display all comments from current blog 
	if( $item_ID == -1 )
	{
		$item_ID = NULL;
	}
	// set redirect_to 
	if( $item_ID != null )
	{ // redirect to the items full view
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog_ID.'&p='.$item_ID, '&' ) );
		param( 'item_id', 'integer', $item_ID );
		param( 'currentpage', 'integer', $currentpage );
		if( count( $statuses ) == 1 )
		{
			$show_comments = $statuses[0];
		}
		else
		{
			$show_comments = 'all';
		}
		param( 'comments_number', 'integer', generic_ctp_number( $item_ID, 'comments', $show_comments ) );
		// Filter list:
		$CommentList->set_filters( array(
			'types' => array( 'comment', 'trackback', 'pingback' ),
			'statuses' => $statuses,
			'comment_ID_list' => $exlude_ID_list,
			'post_ID' => $item_ID,
			'order' => 'ASC',//$order,
			'comments' => $limit,
			'page' => $currentpage,
		) );
	}
	else
	{ // redirect to the comments full view
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=comments&blog='.$blog_ID.'&filter=restore', '&' ) );
		// this is an ajax call we always have to restore the filterst (we can set filters only without ajax call)
		$CommentList->set_filters( array(
			'types' => array( 'comment', 'trackback', 'pingback' ),
		) );
		$CommentList->restore_filterset();
	}

	// Get ready for display (runs the query):
	$CommentList->display_init();

	$CommentList->display_if_empty( array(
		'before'    => '<div class="bComment"><p>',
		'after'     => '</p></div>',
		'msg_empty' => T_('No feedback for this post yet...'),
	) );

	// display comments
	require $inc_path.'comments/views/_comment_list.inc.php';
}


/**
 * Display a comment corresponding the given comment id
 * 
 * @param int comment id
 * @param string where to redirect after comment edit
 * @param boolean true to set the new redirect param, false otherwise
 */
function echo_comment( $comment_ID, $redirect_to = NULL, $save_context = false )
{
	global $current_User;

	$CommentCache = & get_CommentCache();
	$Comment = $CommentCache->get_by_ID( $comment_ID );

	$is_published = ( $Comment->get( 'status' ) == 'published' );

	$Item = & $Comment->get_Item(); 
	$Blog = & $Item->get_Blog();

	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		echo '<div id="c'.$comment_ID.'" class="bComment bComment';
		$Comment->status('raw');
		echo '">';

		echo '<div class="bSmallHead">';
		echo '<div>';

		echo '<div class="bSmallHeadRight">';
		echo T_('Visibility').': ';
		echo '<span class="bStatus">';
		$Comment->status();
		echo '</span>';
		echo '</div>';

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( 'H:i' );
		echo '</span>';

		$Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
		echo ' &middot; <span class="bKarma">';
		$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
		echo '</span>';

		echo '</div>';
		echo '<div style="padding-top:3px">';
		$Comment->author_ip( 'IP: <span class="bIP">', '</span> &middot; ' );
		$Comment->author_url_with_actions( /*$redirect_to*/'', true, true );
		echo '</div>';
		echo '</div>';

		echo '<div class="bCommentContent">';
		echo '<div class="bTitle">';
		echo T_('In response to:')
				.' <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$Item->dget('title').'</a>';
		echo '</div>';
		echo '<div class="bCommentTitle">';
		echo $Comment->get_title();
		echo '</div>';
		echo '<div class="bCommentText">';
		$Comment->rating();
		$Comment->avatar();
		$Comment->content( 'htmlbody', 'true' );
		echo '</div>';
		echo '</div>';

		echo '<div class="CommentActionsArea">';
		$Comment->permanent_link( array(
				'class'    => 'permalink_right'
			) );

		// Display edit button if current user has the rights:
		$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton', '&amp;', $save_context, $redirect_to );

		// Display publish NOW button if current user has the rights:
		$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', $save_context, true, $redirect_to );

		// Display deprecate button if current user has the rights:
		$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', $save_context, true, $redirect_to );

		// Display delete button if current user has the rights:
		$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, '&amp;', $save_context, true );

		// Display Spam Voting system
		$Comment->vote_spam( '', '', '&amp;', $save_context, true );

		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}
	else
	{
		echo '<div id="c'.$comment_ID.'" class="bComment bComment';
		$Comment->status('raw');
		echo '">';

		echo '<div class="bSmallHead">';
		echo '<div>';

		echo '<div class="bSmallHeadRight">';
		echo T_('Visibility').': ';
		echo '<span class="bStatus">';
		$Comment->status();
		echo '</span>';
		echo '</div>';

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( 'H:i' );
		echo '</span>';

		echo '</div>';
		echo '</div>';

		if( $is_published )
		{
			echo '<div class="bCommentContent">';
			echo '<div class="bCommentTitle">';
			echo $Comment->get_title();
			echo '</div>';
			echo '<div class="bCommentText">';
			$Comment->rating();
			$Comment->avatar();
			$Comment->content();
			echo '</div>';
			echo '</div>';
		}

		echo '<div class="clear"></div>';
		echo '</div>';
	}
}


/**
 * Display a page link on item full view
 * 
 * @param integer the item id
 * @param string link text 
 * @param integer the page number
 */
function echo_pagenumber( $item_ID, $text, $value )
{
	echo ' <a href="javascript:startRefreshComments( '.$item_ID.', '.$value.' )">'.$text.'</a>';
}


/**
 * Display page links on item full view
 * 
 * @param integer the item id
 * @param integer current page number
 * @param integer all comments number in the list
 */
function echo_pages( $item_ID, $currentpage, $comments_number )
{
	$comments_per_page = 20;
	if( ( ( $currentpage - 1 ) * $comments_per_page ) >= $comments_number )
	{ // current page number is greater then all page number, set current page to the last existing page
		$currentpage = intval( ( $comments_number - 1 ) / $comments_per_page ) + 1;
	}
	echo '<div id="currentpage" value='.$currentpage.' /></div>';
	echo '<div class="results_nav" id="paging">';
	if( $comments_number > 0 )
	{
		echo '<strong>'.T_('Pages').'</strong>:';
		if( $currentpage > 1 )
		{ // previous link
			echo_pagenumber( $item_ID, T_('Previous'), $currentpage - 1 );
		}
		for( $i = 1; ( ( $i - 1 ) * $comments_per_page ) < $comments_number; $i++ )
		{
			if( $i == $currentpage )
			{
				echo ' <strong>'.$i.'</strong>';
			}
			else
			{
				echo_pagenumber( $item_ID, $i, $i );
			}
		}
		if( ( $currentpage * $comments_per_page ) < $comments_number )
		{ // next link
			echo_pagenumber( $item_ID, T_('Next'), $currentpage + 1 );
		}
	}
	echo '</div>';
}

/**
 * Get item edit modes
 * 
 * @param integer blog ID
 * @param string action
 * @param string path to admin page
 * @param string tab switch params
 * @return array with modes
 */
function get_item_edit_modes( $blog_ID, $action, $dispatcher, $tab_switch_params )
{
	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	$modes = array();
	$modes['simple'] = array(
		'text' => T_('Simple'),
		'href' => $dispatcher.'?ctrl=items&amp;action='.$action.'&amp;tab=simple&amp;'.$tab_switch_params,
		'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'?ctrl=items&amp;tab=simple&amp;blog='.$blog_ID.'\' );',
		// 'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
	);
	$modes['expert'] = array(
		'text' => T_('Expert'),
		'href' => $dispatcher.'?ctrl=items&amp;action='.$action.'&amp;tab=expert&amp;'.$tab_switch_params,
		'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'?ctrl=items&amp;tab=expert&amp;blog='.$blog_ID.'\' );',
		// 'name' => 'switch_to_expert_tab_nocheckchanges', // no bozo check
	);
	if( $Blog->get_setting( 'in_skin_editing' ) )
	{	// Show 'In skin' tab if Blog setting 'In-skin editing' is ON
		$mode_inskin_url = url_add_param( $Blog->get( 'url' ), 'disp=edit' );
		$mode_inskin_action = get_samedomain_htsrv_url().'item_edit.php';
		$modes['inskin'] = array(
			'text' => T_('In skin'),
			'href' => $mode_inskin_url,
			'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$mode_inskin_action.'\' );',
		);
	}

	return $modes;
}


/**
 * Check permission for editing of the item by current user
 * 
 * @param post ID
 */
function check_item_perm_edit( $post_ID )
{
	global $Blog, $Messages, $current_User;

	// The edit module can only be used by logged in users:
	if( ! is_logged_in() )
	{	// Redirect to the login page for anonymous users
		$Messages->add( T_( 'You must log in to create & edit a posts.' ) );
		header_redirect( get_login_url('cannot edit posts'), 302 );
		// will have exited
	}

	$user_can_edit = false;

	if( $post_ID > 0 )
	{	// Check permissions for editing of the current item
		$ItemCache = & get_ItemCache ();
		$edited_Item = $ItemCache->get_by_ID ( $post_ID );
		$user_can_edit = $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item );
	}
	else
	{	// Check permissions for creating of a new item
		// TODO: It seems we don't have a such permisson 'create a new post'. We have to create it.
		$user_can_edit = $current_User->check_perm( 'admin', 'normal' );
	}

	if( ! $user_can_edit )
	{	// Redirect to the blog url for users without messaging permission
		$Messages->add( 'You are not allowed to create & edit a posts!' );
		if( empty( $Blog ) )
		{	// Bad request without blog ID
			global $home_url;
			$redirect_to = $home_url;
		}
		else
		{	// Redirect to the current blog
			$redirect_to = $Blog->gen_blogurl();
		}
		header_redirect( $redirect_to, 302 );
		// will have exited
	}
}


/*
 * $Log$
 * Revision 1.146  2011/10/13 11:40:10  efy-yurybakh
 * In skin posting (permission)
 *
 * Revision 1.145  2011/10/12 13:54:36  efy-yurybakh
 * In skin posting
 *
 * Revision 1.144  2011/10/12 11:23:31  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.143  2011/10/11 18:26:10  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.142  2011/10/06 11:49:47  efy-yurybakh
 * Replace all timestamp_min & timestamp_max with Blog's methods
 *
 * Revision 1.141  2011/09/28 16:15:56  efy-yurybakh
 * "comment was helpful" votes
 *
 * Revision 1.140  2011/09/28 09:22:34  efy-yurybakh
 * "comment is spam" vote (avatar blinks)
 *
 * Revision 1.139  2011/09/25 03:54:21  efy-yurybakh
 * Add spam voting to dashboard
 *
 * Revision 1.138  2011/09/24 13:27:36  efy-yurybakh
 * Change voting buttons
 *
 * Revision 1.137  2011/09/24 05:30:19  efy-yurybakh
 * fp>yura
 *
 * Revision 1.136  2011/09/23 22:37:09  fplanque
 * minor / doc
 *
 * Revision 1.135  2011/09/23 06:25:48  efy-yurybakh
 * "comment is spam" vote
 *
 * Revision 1.134  2011/09/22 16:58:34  efy-yurybakh
 * "comment is spam" vote
 *
 * Revision 1.133  2011/09/22 06:54:24  efy-yurybakh
 * 5 first icons in a single sprite
 *
 * Revision 1.132  2011/09/21 13:01:09  efy-yurybakh
 * feature "Was this comment helpful?"
 *
 * Revision 1.131  2011/09/19 22:15:59  fplanque
 * Minot/i18n
 *
 * Revision 1.130  2011/09/04 22:13:17  fplanque
 * copyright 2011
 *
 * Revision 1.129  2011/09/04 20:29:10  fplanque
 * Rollback. Collapsible filter blocks are ok if: 1) the order stays the same and 2) any block that doesn't use the default params displays open when arriving on the page.
 *
 * Revision 1.128  2011/08/16 07:02:25  efy-asimo
 * Fix attaching files issue on new post create
 *
 * Revision 1.127  2011/08/16 06:02:52  efy-asimo
 * Remove extra category check by default
 *
 * Revision 1.126  2011/06/15 20:30:38  sam2kb
 * Change message type to "note"
 * See http://forums.b2evolution.net/viewtopic.php?t=22334
 *
 * Revision 1.124  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.123  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.122  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.121  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.120  2010/10/19 02:00:53  fplanque
 * MFB
 *
 * Revision 1.119  2010/09/29 14:53:50  efy-asimo
 * Item full view comment list - fix
 *
 * Revision 1.118  2010/09/28 13:03:16  efy-asimo
 * Paged comments on item full view
 *
 * Revision 1.117  2010/09/23 14:21:00  efy-asimo
 * antispam in comment text feature
 *
 * Revision 1.116  2010/08/05 08:04:12  efy-asimo
 * Ajaxify comments on itemList FullView and commentList FullView pages
 *
 * Revision 1.109.2.5  2010/07/06 21:00:34  fplanque
 * doc
 *
 * Revision 1.114  2010/06/30 07:34:42  efy-asimo
 * Cross posting fix - ingore extra cats from different blogs, when cross posting is disabled
 *
 * Revision 1.113  2010/06/24 07:03:11  efy-asimo
 * move the cross posting options to the bottom of teh Features tab & fix error message after moving post
 *
 * Revision 1.112  2010/06/17 17:42:54  blueyed
 * doc
 *
 * Revision 1.111  2010/06/15 20:12:51  blueyed
 * Autocompletion for tags in item edit forms, via echo_autocomplete_tags
 *
 * Revision 1.110  2010/06/01 11:33:20  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.109  2010/05/24 07:18:53  efy-asimo
 * Allow cross posting - fix
 *
 * Revision 1.108  2010/05/22 12:22:49  efy-asimo
 * move $allow_cross_posting in the backoffice
 *
 * Revision 1.107  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.106  2010/05/02 00:13:28  blueyed
 * urltitle_validate: make it use the current urlname, if invoked via empty urltitle, instead of creating a new one over and over again. See tests.
 *
 * Revision 1.105  2010/05/01 18:43:53  blueyed
 * TODO/doc: why limit urlnames to 5 words?
 *
 * Revision 1.104  2010/05/01 16:14:56  blueyed
 * Item categories form: use IE Conditional Comments for IE7 code. I do not know if IE8 should use the same workaround. But Firefox does not and an extra table column looks ugly here.
 *
 * Revision 1.103  2010/04/30 20:35:55  blueyed
 * Item edit form:
 *  - allow_cross_posting=2 related fixes:
 *    - add cat_select_new input box at the end of the main category blog,
 *      not at the end of the list.
 *    - scroll to selected blog in category list DIV (TODO: test with IE),
 *      adds HTML IDs to the table groups.
 *
 * Revision 1.102  2010/04/12 09:41:36  efy-asimo
 * private URL shortener - task
 *
 * Revision 1.101  2010/04/07 08:26:10  efy-asimo
 * Allow multiple slugs per post - update & fix
 *
 * Revision 1.100  2010/03/27 15:37:00  blueyed
 * whitespace
 *
 * Revision 1.99  2010/03/15 20:12:24  efy-yury
 * slug fix
 *
 * Revision 1.98  2010/03/12 09:47:34  efy-asimo
 * New category creation fix
 *
 * Revision 1.97  2010/03/09 11:30:21  efy-asimo
 * create categories on the fly -  fix
 *
 * Revision 1.96  2010/03/08 21:06:36  fplanque
 * minor/doc
 *
 * Revision 1.95  2010/03/07 12:59:50  efy-yury
 * update slugs
 *
 * Revision 1.94  2010/03/06 13:24:38  efy-asimo
 * doc for check_categories function
 *
 * Revision 1.93  2010/03/04 19:36:04  fplanque
 * minor/doc
 *
 * Revision 1.92  2010/03/03 21:04:31  fplanque
 * minor / doc
 *
 * Revision 1.91  2010/02/16 16:52:46  efy-yury
 * slugs
 *
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
 * Fixed "Add a new category»" link (blog param)
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
