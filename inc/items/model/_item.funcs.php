<?php
/**
 * This file implements Post handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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
	global $MainList, $Blog, $Plugins, $Skin;
	global $preview;
	global $disp;
	global $postIDlist, $postIDarray;

	// Allow plugins to prepare their own MainList object
	if( ! $Plugins->trigger_event_first_true('InitMainList', array( 'MainList' => &$MainList, 'limit' => $items_nb_limit ) ) )
	{
		$MainList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $items_nb_limit );	// COPY (FUNC)

		// Set additional debug info prefix for SQL queries in order to know what code executes it:
		$MainList->query_title_prefix = '$MainList';

		if( ! $preview )
		{
			if( $disp == 'page' )
			{	// Get pages:
				$MainList->set_default_filters( array(
						'itemtype_usage' => 'page' // pages
					) );
			}

			if( $disp == 'terms' )
			{	// Allow all post types:
				$MainList->set_default_filters( array(
						'itemtype_usage' => 'page,special'
					) );
			}

			// else: we are in posts mode

			// pre_dump( $MainList->default_filters );
			$MainList->load_from_Request( false );
			// pre_dump( $MainList->filters );
			// echo '<br/>'.( $MainList->is_filtered() ? 'filtered' : 'NOT filtered' );
			// $MainList->dump_active_filters();

			if( $disp == 'posts' && ! empty( $Skin ) && $Skin->get_template( 'cat_array_mode' ) == 'parent' )
			{	// Get items ONLY from current category WITHOUT sub-categories:
				global $cat;
				// Get ID of single selected category:
				$single_cat_ID = intval( $cat );

				if( $single_cat_ID )
				{	// Do limit if single category is selected:
					$MainList->set_filters( array(
							'cat_array'    => array( $single_cat_ID ),
							'cat_modifier' => NULL,
						), true, true );
				}
			}

			// Run the query:
			$MainList->query();

			// Load data of items from the current page at once to cache variables:
			$MainList->load_list_data();

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

	$tab_switch_params = 'blog='.$Blog->ID;

	// Post ID, go from $_GET when we edit post from Front-office
	$post_ID = param( 'p', 'integer', 0 );

	// Post ID, go from $_GET when we copy post from Front-office
	$copy_post_ID = param( 'cp', 'integer', 0 );

	if( $post_ID > 0 )
	{	// Edit post
		global $post_extracats;
		$action = 'edit';

		$ItemCache = & get_ItemCache ();
		$edited_Item = $ItemCache->get_by_ID ( $post_ID );

		check_categories_nosave ( $post_category, $post_extracats );
		$post_extracats = postcats_get_byID( $post_ID );

		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit&p='.$post_ID, '&' );
		$tab_switch_params .= '&amp;p='.$edited_Item->ID;
	}
	elseif( $copy_post_ID > 0 )
	{	// Copy post
		global $localtimenow;
		$action = 'new';

		$ItemCache = & get_ItemCache ();
		$edited_Item = $ItemCache->get_by_ID ( $copy_post_ID );

		$edited_Item_Blog = $edited_Item->get_Blog();
		$item_status = $edited_Item_Blog->get_allowed_item_status();

		$edited_Item->set( 'status', $item_status );
		$edited_Item->set( 'dateset', 0 );	// Date not explicitly set yet
		$edited_Item->set( 'issue_date', date( 'Y-m-d H:i:s', $localtimenow ) );

		modules_call_method( 'constructor_item', array( 'Item' => & $edited_Item ) );

		check_categories_nosave( $post_category, $post_extracats );

		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit', '&' );
	}
	elseif( empty( $action ) )
	{	// Create new post (from Front-office)
		$action = 'new';

		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();
		$def_status = get_highest_publish_status( 'post', $Blog->ID, false );
		$edited_Item->set( 'status', $def_status );
		check_categories_nosave( $post_category, $post_extracats );
		$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());

		// Set default locations from current user
		$edited_Item->set_creator_location( 'country' );
		$edited_Item->set_creator_location( 'region' );
		$edited_Item->set_creator_location( 'subregion' );
		$edited_Item->set_creator_location( 'city' );

		// Set object params:
		$edited_Item->load_from_Request( /* editing? */ false, /* creating? */ true );

		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit', '&' );
	}

	// Restrict item status to max allowed by item collection:
	$edited_Item->restrict_status_by_collection();

	// Used in the edit form:

	// We never allow HTML in titles, so we always encode and decode special chars.
	$item_title = htmlspecialchars_decode( $edited_Item->title );

	$item_content = prepare_item_content( $edited_Item->content );

	if( ! $edited_Item->get_type_setting( 'allow_html' ) )
	{ // HTML is disallowed for this post, content is encoded in DB and we need to decode it for editing:
		$item_content = htmlspecialchars_decode( $item_content );
	}

	// Format content for editing, if we were not already in editing...
	$Plugins_admin = & get_Plugins_admin();
	$edited_Item->load_Blog();
	$params = array( 'object_type' => 'Item', 'object_Blog' => & $edited_Item->Blog );
	$Plugins_admin->unfilter_contents( $item_title /* by ref */, $item_content /* by ref */, $edited_Item->get_renderers_validated(), $params );

	$item_tags = implode( ', ', $edited_Item->get_tags() );

	// Get an url for a link 'Go to advanced edit screen'
	$advanced_edit_link = array(
			'href'    => $admin_url.'?ctrl=items&amp;action='.$action.'&amp;'.$tab_switch_params,
			'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'\' );',
		);

	$form_action = get_samedomain_htsrv_url().'item_edit.php';
}

/**
 * If an Intro Post is available, return it. If not, see if a Featured Post is available and return it.
 *
 * Note: this will set the global $FeaturedList which may be used to obtain several featured Items.
 *
 * @param string Name of $disp where we should display it
 * @param string Collection IDs:
 *                 NULL: depend on blog setting "Collections to aggregate"
 *                 empty: current blog only
 *                 "*": all blogs
 *                 "1,2,3":blog IDs separated by comma
 *                 "-": current blog only and exclude the aggregated blogs
 * @return Item
 */
function & get_featured_Item( $restrict_disp = 'posts', $coll_IDs = NULL )
{
	global $Blog, $cat;
	global $disp, $disp_detail, $MainList, $FeaturedList;
	global $featured_displayed_item_IDs;

	if( $disp != $restrict_disp || !isset($MainList) )
	{	// If we're not currently displaying posts, no need to try & display a featured/intro post on top!
		$Item = NULL;
		return $Item;
	}

	if( !isset( $FeaturedList ) )
	{	// Don't repeat if we've done this already -- Initialize the featured list only first time this function is called in a skin:

		// Get ready to obtain 1 post only:
		$FeaturedList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), 1 );

		// Set additional debug info prefix for SQL queries in order to know what code executes it:
		$FeaturedList->query_title_prefix = '$FeaturedList';

		$featured_list_filters = $MainList->filters;
		if( ! empty( $cat ) )
		{	// Get a featured post only of the selected category and don't touch the posts of the child categories:
			$featured_list_filters['cat_array'] = array( $cat );
		}

		// Set default filters for the current page:
		$FeaturedList->set_default_filters( $featured_list_filters );

		// FIRST: Try to find an Intro post:

		if( ! $MainList->is_filtered() )
		{	// This is not a filtered page, so we are on the home page.
			if( $restrict_disp == 'front' )
			{	// Special Front page:
				// Use Intro-Front posts
				$restrict_to_types_usage = 'intro-front';
			}
			else
			{	// Default front page displaying posts:
				// The competing intro-* types are: 'main' and 'all':
				// fplanque> IMPORTANT> nobody changes this without consulting the manual and talking to me first!
				$restrict_to_types_usage = 'intro-main,intro-all';
			}
		}
		else
		{	// We are on a filtered... it means a category page or sth like this...
			// echo $disp_detail;
			switch( $disp_detail )
			{
				case 'posts-cat':
				case 'posts-subcat':
					// The competing intro-* types are: 'cat' and 'all':
					// fplanque> IMPORTANT> nobody changes this without consulting the manual and talking to me first!
					$restrict_to_types_usage = 'intro-cat,intro-all';
					break;

				case 'posts-tag':
					// The competing intro-* types are: 'tag' and 'all':
					// fplanque> IMPORTANT> nobody changes this without consulting the manual and talking to me first!
					$restrict_to_types_usage = 'intro-tag,intro-all';
					break;

				default:
					// The competing intro-* types are: 'sub' and 'all':
					// fplanque> IMPORTANT> nobody changes this without consulting the manual and talking to me first!
					$restrict_to_types_usage = 'intro-sub,intro-all';
			}
		}

		$FeaturedList->set_filters( array(
				'coll_IDs' => $coll_IDs,
				'itemtype_usage' => $restrict_to_types_usage,
			), false /* Do NOT memorize!! */ );
		// pre_dump( $FeaturedList->filters );
		// Run the query:
		$FeaturedList->query();


		// SECOND: If no Intro, try to find an Featured post:

		if( isset($Blog) )

		if( $FeaturedList->result_num_rows == 0 && $restrict_disp != 'front'
			&& isset($Blog) 
			&& $Blog->get_setting('disp_featured_above_list') )
		{ // No Intro page was found, try to find a featured post instead:

			$FeaturedList->reset();

			$FeaturedList->set_filters( array(
					'coll_IDs' => $coll_IDs,
					'featured' => 1,  // Featured posts only
					// Types will already be reset to defaults here
				), false /* Do NOT memorize!! */ );

			// Run the query:
			$FeaturedList->query();
		}
	}

	// Get first Item in the result set.
	$Item = $FeaturedList->get_item();

	if( $Item )
	{	// Memorize that ID so that it can later be filtered out of normal display:
		$featured_displayed_item_IDs[] = $Item->ID;
	}

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
		global $Blog;
		if( isset( $Blog ) )
		{ // Get max length of slug from current blog setting
			$count_of_words = $Blog->get_setting('slug_limit');
		}
		if( empty( $count_of_words ) )
		{ // Use 5 words to limit slug by default
			$count_of_words = 5;
		}

		$title_words = array();
		$title_words = explode( '-', $urltitle );
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
	$sql .= ', post_locale, post_url, post_wordcount, post_comment_status ';
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
 * Get allowed statuses for the current User
 *
 * Note: This function must be called only from the statuses_where_clause()!
 *
 * @param array statuses to filter
 * @param string database status field column prefix
 * @param integer the blog ID where to check allowed statuses
 * @param string the status permission prefix
 * @return string the condition
 */
function get_allowed_statuses_condition( $statuses, $dbprefix, $req_blog, $perm_prefix )
{
	global $current_User;

	// init where clauses array
	$where = array();
	// init allowed statuses array
	$allowed_statuses = array();

	$is_logged_in = is_logged_in( false );
	$creator_coll_name = ( $dbprefix == 'post_' ) ? $dbprefix.'creator_user_ID' : $dbprefix.'author_user_ID';
	// Iterate through all statuses and set allowed to true only if the corresponding status is allowed in case of any post/comments
	// If the status is not allowed to show, but exists further conditions which may allow it, then set the condition.
	foreach( $statuses as $key => $status )
	{
		switch( $status )
		{
			case 'published': // Published post/comments are always allowed
				$allowed = true;
				break;

			case 'community': // It is always allowed for logged in users
				$allowed = $is_logged_in;
				break;

			case 'protected': // It is always allowed for members
				$allowed = ( $is_logged_in && ( $current_User->check_perm( 'blog_ismember', 1, false, $req_blog ) ) );
				break;

			case 'private': // It is allowed for users who has global 'editall' permission
				$allowed = ( $is_logged_in && $current_User->check_perm( 'blogs', 'editall' ) );
				if( ! $allowed && $dbprefix == 'comment_' )
				{	// Allow the private comments for collection owner:
					$allowed = ( $is_logged_in && $current_User->check_perm_blogowner( $req_blog ) );
				}
				if( !$allowed && $is_logged_in && $current_User->check_perm( $perm_prefix.'private', 'create', false, $req_blog ) )
				{ // Own private posts/comments are allowed if user can create private posts/comments
					$where[] = ' ( '.$dbprefix."status = 'private' AND ".$creator_coll_name.' = '.$current_User->ID.' ) ';
				}
				break;

			case 'review': // It is allowed for users who have permission to create comments with 'review' status and have at least 'lt' posts/comments edit perm
				$allowed = ( $is_logged_in && $current_User->check_perm( $perm_prefix.'review', 'moderate', false, $req_blog ) );
				if( !$allowed && $is_logged_in && $current_User->check_perm( $perm_prefix.'review', 'create', false, $req_blog ) )
				{ // Own posts/comments with 'review' status are allowed if user can create posts/comments with 'review' status
					$where[] = ' ( '.$dbprefix."status = 'review' AND ".$creator_coll_name.' = '.$current_User->ID.' ) ';
				}
				break;

			case 'draft': // In back-office it is always allowed for users who may create posts/commetns with 'draft' status
				$allowed = ( is_admin_page() && $current_User->check_perm( $perm_prefix.'draft', 'create', false, $req_blog ) );
				if( !$allowed && $is_logged_in && $current_User->check_perm( $perm_prefix.'draft', 'create', false, $req_blog ) )
				{ // In front-office only authors may see their own draft posts/comments, but only if the have permission to create draft posts/comments
					$where[] = ' ( '.$dbprefix."status = 'draft' AND ".$creator_coll_name.' = '.$current_User->ID.' ) ';
				}
				break;

			case 'deprecated': // In back-office it is always allowed for users who may create posts/comments with 'deprecated' status
				$allowed = ( is_admin_page() && $current_User->check_perm( $perm_prefix.'deprecated', 'create', false, $req_blog ) );
				// In front-office it is never allowed
				break;

			case 'redirected': // In back-office it is always allowed for users who may create posts/comments with 'deprecated' status
				$allowed = ( is_admin_page() && $current_User->check_perm( $perm_prefix.'redirected', 'create', false, $req_blog ) );
				// In front-office it is never allowed
				break;

			case 'trash':
				// Currently only users with global editall permissions are allowed to view/delete recycled comments
				$allowed = ( ( $dbprefix == 'comment_' ) && is_admin_page() && $current_User->check_perm( 'blogs', 'editall' ) );
				// In front-office it is never allowed
				break;

			default: // Allow other statuses are restricted. It is very important to keep this restricted because of SQL injections also, so we never allow a status what we don't know.
				$allowed = false;
		}

		if( $allowed )
		{ // All posts/comments with this status can be displayed in the request blog for the current User ( or for anonymous )
			$allowed_statuses[] = $status;
		}
	}

	if( count( $allowed_statuses ) )
	{ // add allowed statuses condition
		$where[] = $dbprefix.'status IN ( \''.implode( '\',\'', $allowed_statuses ).'\' )';
	}

	// Implode conditions collected in the $where array
	// NOTE: If the array is empty, it means that the user has no permission to the requested statuses, so FALSE must be returned
	$where_condition = count( $where ) > 0 ? ' ( '.implode( ' OR ', $where ).' ) ' : ' FALSE ';
	return $where_condition;
}


/**
 * Construct the where clause to limit retrieved posts/comment on their status
 *
 * TODO: asimo> would be good to move this function to an items and comments common file
 *
 * @param Array statuses of posts/comments we want to get
 * @param string post/comment table db prefix
 * @param integer blog ID
 * @param string permission prefix: 'blog_post!' or 'blog_comment!'
 * @param boolean filter statuses by the current user perm and current page, by default is true. It should be false only e.g. when we have to count comments awaiting moderation.
 * @return string statuses where condition
 */
function statuses_where_clause( $show_statuses = NULL, $dbprefix = 'post_', $req_blog = NULL, $perm_prefix = 'blog_post!', $filter_by_perm = true, $author_filter = NULL )
{
	global $current_User, $blog, $DB;

	// This statuses where clause is required for a post query
	$is_post_query = ( $perm_prefix == 'blog_post!' );

	if( is_null( $req_blog ) )
	{ // try to init request blog if it was not set
		global $blog;
		$req_blog = $blog;
	}

	if( empty( $show_statuses ) )
	{ // use in-skin statuses if show_statuses is empty
		$show_statuses = get_inskin_statuses( $req_blog, $is_post_query ? 'post' : 'comment' );
	}

	// init where clauses array
	$where = array();

	// Check modules item statuses where condition but only in case of the items, and don't need to check in case of comments
	if( $req_blog && $is_post_query )
	{ // If requested blog is set, then set additional "where" clauses from modules method, before we would manipulate the $show_statuses array
		$modules_condition = modules_call_method( 'get_item_statuses_where_clause', array( 'blog_ID' => $req_blog, 'statuses' => $show_statuses ) );
		if( !empty( $modules_condition ) )
		{
			foreach( $modules_condition as $condition )
			{
				if( ! empty( $condition ) )
				{ // condition is not empty
					$where[] = $condition;
				}
			}
		}
	}

	if( is_logged_in( false ) && $filter_by_perm )
	{ // User is logged in and the account was activated
		if( $current_User->check_perm( 'blogs', 'editall', false ) )
		{ // User has permission to all blogs posts and comments, we don't have to check blog specific permissions.
			$allowed_statuses_cond = get_allowed_statuses_condition( $show_statuses, $dbprefix, NULL, $perm_prefix );
			if( ! empty( $allowed_statuses_cond ) )
			{ // condition is not empty
				$where[] = $allowed_statuses_cond;
			}
			$filter_by_perm = false;
			$show_statuses = NULL;
		}
		elseif( !empty( $author_filter ) && ( $author_filter != $current_User->ID ) )
		{ // Author filter is set, but current_User is not the filtered user, then these statuses are not visible for sure
			$show_statuses = array_diff( $show_statuses, array( 'private', 'draft' ) );
		}
	}

	if( ( $req_blog == 0 ) && $filter_by_perm )
	{ // This is a very special case when we must check visibility statuses from each blog separately
		// Note: This case should not be called frequently, it may be really slow!!
		$where_condition = '';
		$condition = '';
		if( in_array( 'published', $show_statuses ) )
		{ // 'published' status is always allowed in case of all blogs, handle this condition separately
			$where_condition = '( '.$dbprefix.'status = "published" )';
			$condition = ' OR ';
		}
		if( !is_logged_in( false ) )
		{ // When user is not logged in only the 'published' status is allowed, don't check more
			return $where_condition;
		}
		if( in_array( 'community', $show_statuses ) )
		{ // 'community' status is always allowed in case of all blogs when a user is logged in, handle this condition separately
			$where_condition .= $condition.'( '.$dbprefix.'status = "community" )';
			$condition = ' OR ';
		}
		// Remove 'published' and 'community' statuses because those were already handled
		$show_statuses = array_diff( $show_statuses, array( 'published', 'community' ) );
		if( empty( $show_statuses ) )
		{ // return if there are no other status
			return $where_condition;
		}
		// Select each blog
		$blog_ids = $DB->get_col( 'SELECT blog_ID FROM T_blogs', 0, 'Get IDs of all collections' );
		$sub_condition = '';
		foreach( $blog_ids as $blog_id )
		{ // create statuses where clause condition for each blog separately
			$status_perm = statuses_where_clause( $show_statuses, $dbprefix, $blog_id, $perm_prefix, $filter_by_perm, $author_filter );
			if( $status_perm )
			{ // User has permission to view some statuses on this blog
				$sub_condition .= '( ( cat_blog_ID = '.$blog_id.' ) AND'.$status_perm.' ) OR ';
			}
		}
		if( $dbprefix == 'post_' )
		{ // Item query condition
			$from_table = 'FROM T_items__item';
			$reference_column = 'post_ID';
		}
		else
		{ // Comment query condition
			$from_table = 'FROM T_comments';
			$reference_column ='comment_item_ID';
		}
		// Select each object ID which corresponds to the statuses condition.
		// Note: This is a very slow query when there is many post/comments. This case should not be used frequently.
		$sub_query = 'SELECT '.$dbprefix.'ID '.$from_table.'
						INNER JOIN T_postcats ON '.$reference_column.' = postcat_post_ID
						INNER JOIN T_categories ON postcat_cat_ID = cat_ID
						WHERE ('.substr( $sub_condition, 0, ( strlen( $sub_condition ) - 3 ) ).')';
		$object_ids = implode( ',', $DB->get_col( $sub_query ) );
		if( $object_ids )
		{ // If thre is at least one post or comment
			$where_condition .= $condition.'( '.$dbprefix.'ID IN ('.$object_ids.') )';
		}
		return $where_condition;
	}

	if( $filter_by_perm )
	{ // filter allowed statuses by the current User perms and by the current page ( front or back office )
		$allowed_statuses_cond = get_allowed_statuses_condition( $show_statuses, $dbprefix, $req_blog, $perm_prefix );
		if( ! empty( $allowed_statuses_cond ) )
		{ // condition is not empty
			$where[] = $allowed_statuses_cond;
		}
	}
	elseif( count( $show_statuses ) )
	{ // we are not filtering so all status are allowed, add allowed statuses condition
		$where[] = $dbprefix.'status IN ( \''.implode( '\',\'', $show_statuses ).'\' )';
	}

	$where = count( $where ) > 0 ? ' ( '.implode( ' OR ', $where ).' ) ' : '';

	// echo $where;
	return $where;
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
 * Recreate posts autogenerated excerpts
 * Only those posts excerpt will be recreated which are visible in the front office
 *
 * Note: This process can be very very slow if there are many items in the database
 *
 * @param string the url to call if the process needs to be paused becuase of the max execution time
 * @param boolean set true to start from the beginning, false otherwise
 * @param boolean set to true to display recreated/all information or leave it on false to display only dots
 */
function recreate_autogenerated_excerpts( $continue_url, $remove_all = true, $detailed_progress_log = false )
{
	global $DB, $localtimenow;

	$start_time = time();
	$max_exec_time = ini_get( 'max_execution_time' );
	$load_limit = 50;
	$progress_log_id = 'progress_log';
	// The timestamp when the process was started
	$process_start_ts = param( 'start_ts', 'string', $localtimenow );

	// Update only those posts which may be visible in the front office
	$where_condition = '( post_excerpt_autogenerated = 1 OR post_excerpt_autogenerated IS NULL ) AND post_status IN ( \''.implode( '\',\' ', get_inskin_statuses( NULL, 'post' ) ).'\' )';
	// Update only those posts which were already created when the recreate process was started
	$where_condition .= ' AND post_datecreated < '.$DB->quote( date2mysql( $process_start_ts ) );

	if( $remove_all )
	{ // We are in the beginning of the process and first we set all autogenerated excerpts values to NULL
		if( $detailed_progress_log )
		{ // Display detailed progess information
			echo '<span id="'.$progress_log_id.'">'.T_('Clearing autogenerated excerpt values').'</span>';
			evo_flush();
		}

		// Set all autogenerated excerpt values to NULL
		$query = 'UPDATE T_items__item SET post_excerpt = NULL WHERE '.$where_condition;
		$DB->query( $query, 'Remove all autogenerated excerpts' );

		// Count all autogenerated excerpt which value is NULL ( Note: Maybe some of them was already NULL before we have started this process )
		$all_excerpt = $DB->get_var( 'SELECT count(*) FROM T_items__item WHERE post_excerpt IS NULL AND '.$where_condition );
		$recreated_excerpts = 0;
	}
	else
	{ // Init params with a previously started process status
		echo '<span id="progress_log"></span>';
		$all_excerpt = param( 'all_excerpt', 'integer', 0 );
		$recreated_excerpts = param( 'recreated_excerpts', 'integer', 0 );
	}

	// Display the current state of the process or a '.' character to indicate the ongoing process
	if( $detailed_progress_log )
	{
		echo_progress_log_update( $progress_log_id, $recreated_excerpts, $all_excerpt );
	}
	else
	{
		echo ' .';
	}
	evo_flush();

	$load_SQL = new SQL();
	$load_SQL->SELECT( '*' );
	$load_SQL->FROM( 'T_items__item' );
	$load_SQL->WHERE( $where_condition.' AND post_excerpt IS NULL' );
	$load_SQL->LIMIT( $load_limit );

	$ItemCache = & get_ItemCache();
	while( count( $ItemCache->load_by_sql( $load_SQL ) ) > 0 )
	{ // New portion of items was loaded
		$processed_items = 0;
		while( ( $iterator_Item = & $ItemCache->get_next() ) != NULL )
		{ // Create new autogenerated excerpt and save it in the database
			$excerpt = $iterator_Item->get_autogenerated_excerpt();
			// Update excerpt without Item->dbupdate() call to make it faster
			$DB->query( 'UPDATE T_items__item SET post_excerpt = '.$DB->quote( $excerpt ).' WHERE post_ID = '.$DB->quote( $iterator_Item->ID ) );
			$processed_items++;
			if( $detailed_progress_log && ( ( $processed_items % 3 ) == 0 ) )
			{ // Update progress info after every 3 recreated excerpt when detailed progress log was requested
				echo_progress_log_update( $progress_log_id, $recreated_excerpts + $processed_items, $all_excerpt );
				evo_flush();
			}
		}

		// Increase the number recreated excerpts
		$recreated_excerpts += $processed_items;

		// Clear already process items from the cache
		$ItemCache->clear();
		// Display progress log
		if( $detailed_progress_log )
		{
			echo_progress_log_update( $progress_log_id, $recreated_excerpts, $all_excerpt );
		}
		else
		{
			echo ' .';
		}
		evo_flush();

		if( ( $max_exec_time != 0 ) && ( $recreated_excerpts < $all_excerpt ) )
		{ // a max execution time limit is set and there are more excerpts to create
			$elapsed_time = time() - $start_time;
			if( $elapsed_time > ( $max_exec_time - 10 ) )
			{ // Increase the time limit in case if less than 10 seconds remained
				$max_exec_time = $max_exec_time + 100;
				if( ! set_max_execution_time( $max_exec_time ) )
				{
					$continue_url = url_add_param( $continue_url, 'all_excerpt='.$all_excerpt.'&amp;recreated_excerpts='.$recreated_excerpts.'&amp;start_ts='.$process_start_ts, '&amp;' );
					echo '<br />'.'We are reaching the time limit for this script. Please click <a href="'.$continue_url.'">continue</a>...';
					evo_flush();
					exit(0);
				}
			}
		}
	}

	// Check if the recreated exceprts number match with the total number of autogenerated excerpts
	if( $detailed_progress_log && ( $recreated_excerpts < $all_excerpt ) )
	{ // This means that we are in the end of the process but some post excerpt was updated outside of this process
		// Here we increase the recreated excerpts value because all excerpts were recreated, however some of them not during this process
		echo_progress_log_update( $progress_log_id, $all_excerpt, $all_excerpt );
		if( $all_excerpt == ( $recreated_excerpts + 1 ) )
		{
			echo '<br />'.T_('Note: One excerpt was re-created in another simultaneous process!');
		}
		else
		{
			echo '<br />'.sprintf( T_('Note: %d excerpts were re-created in another simultaneous process!'), $all_excerpt - $recreated_excerpts );
		}
	}
}


/**
 * Allow recursive category selection.
 *
 * @todo Allow to use a dropdown (select) to switch between blogs ( CSS / JS onchange - no submit.. )
 *
 * @param Form
 * @param boolean true: use form fields, false: display only
 * @param boolean true: show links for add new category & manual
 * @param array Params
 */
function cat_select( $Form, $form_fields = true, $show_title_links = true, $params = array() )
{
	global $blog, $current_blog_ID, $current_User, $edited_Item, $cat_select_form_fields;
	global $admin_url, $rsc_url;

	if( get_post_cat_setting( $blog ) < 1 )
	{ // No categories for $blog
		return;
	}

	$params = array_merge( array(
			'categories_name' => T_('Categories'),
			'fold'            => false,
		), $params );

	$cat = param( 'cat', 'integer', 0 );
	if( empty( $edited_Item->ID ) && !empty( $cat ) )
	{	// If the GET param 'cat' is defined we should preselect the category for new created post
		global $post_extracats;
		$post_extracats = array( $cat );
		$edited_Item->main_cat_ID = $cat;
	}

	if( $show_title_links )
	{ // Use in Back-office
		$fieldset_title = $params['categories_name'].get_manual_link( 'post-categories-panel' )
			.action_icon( T_('Categories'), 'edit', $admin_url.'?ctrl=chapters&amp;blog='.$blog, T_('Categories'), 3, 4, array( 'class' => 'action_icon pull-right' ) );
	}
	else
	{
		$fieldset_title = $params['categories_name'];
	}

	$Form->begin_fieldset( $fieldset_title, array( 'class'=>'extracats', 'id' => 'itemform_categories', 'fold' => $params['fold'] ) );

	$r ='';
	$cat_select_form_fields = $form_fields;
	$ChapterCache = & get_ChapterCache();

	$r .= '<table cellspacing="0" id="cat_sel_group" class="catselect table table-striped table-hover table-condensed">';
	if( get_post_cat_setting($blog) == 3 )
	{ // Main + Extra cats option is set, display header
		$r .= cat_select_header( $params );
	}

	$callbacks = array(
		'before_first' => 'cat_select_before_first',
		'before_each'  => 'cat_select_before_each',
		'after_each'   => 'cat_select_after_each',
		'after_last'   => 'cat_select_after_last'
	);

	// Init cat display param
	$cat_display_params = array( 'total_count' => 0 );

	if( $current_User->check_perm( 'blog_admin', '', false, $blog ) &&
		( get_allow_cross_posting() >= 2 ||
	  ( isset( $blog) && get_post_cat_setting( $blog ) > 1 && get_allow_cross_posting() == 1 ) ) )
	{ // If BLOG cross posting enabled, go through all blogs with cats:
		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();
		$ChapterCache->reveal_children( NULL, true );

		/**
		 * @var Blog
		 */
		for( $l_Blog = & $BlogCache->get_first(); !is_null($l_Blog); $l_Blog = & $BlogCache->get_next() )
		{ // run recursively through the cats
			if( ! blog_has_cats( $l_Blog->ID ) )
				continue;

			// Skip collection if current user do not have the appropriate permissions
			if( ! $current_User->check_perm( 'blog_post_statuses', 'edit', false, $l_Blog->ID ) || ! $current_User->check_perm( 'blog_admin', '', false, $l_Blog->ID ) )
				continue;
			$r .= '<tbody data-toggle="collapse" style="cursor: pointer;" data-target="#cat_sel_'.$l_Blog->ID.'" data-parent="#cat_sel_group">';
			$r .= '<tr class="group'.( $blog == $l_Blog->ID ? ' catselect_blog__current' : '' ).'" id="catselect_blog'.$l_Blog->ID.'">';
			$r .= '<td colspan="3">'.$l_Blog->dget('name')."</td></tr>\n";
			$r .= '</tbody>';
			$r .= '<tbody class="accordion_panel '.( $blog == $l_Blog->ID ? 'collapse in' : 'collapse' ).'" id="cat_sel_'.$l_Blog->ID.'">';

			$current_blog_ID = $l_Blog->ID;	// Global needed in callbacks
			foreach( $ChapterCache->subset_root_cats[$current_blog_ID] as $root_Chapter )
			{
				$r .= cat_select_display( $root_Chapter, $callbacks, $cat_display_params );
			}
			if( $blog == $current_blog_ID )
			{
				$r .= cat_select_new( $cat_display_params );
			}

			$r .= '</tbody>';
		}
	}
	else
	{ // BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$ChapterCache->reveal_children( $current_blog_ID, true );

		foreach( $ChapterCache->subset_root_cats[$blog] as $root_Chapter )
		{
			$r .= cat_select_display( $root_Chapter, $callbacks, $cat_display_params );
		}

		$r .= cat_select_new( $cat_display_params );
	}

	$r .= '</table>';

	echo $r;

	$Form->end_fieldset();

	if( isset($blog) && get_allow_cross_posting() )
	{
		echo '<script type="text/javascript">jQuery.getScript("'.get_require_url( '#scrollto#' ).'", function () {
			jQuery("[id$=itemform_categories]").scrollTo( "#catselect_blog'.$blog.'" );
			var $catSelTable = jQuery("table#cat_sel_group");
			var $accordionPanels = $catSelTable.find("tbody.accordion_panel");
			$accordionPanels.on("show.bs.collapse", function() {
				$catSelTable.find("tbody.collapse.in").collapse("hide");
			});
		});</script>';
	}
}

/**
 * Header for {@link cat_select()}
 *
 * @param array Params
 */
function cat_select_header( $params = array() )
{
	$params = array_merge( array(
			'category_name'        => T_('Category'),
			'category_main_title'  => T_('Main category'),
			'category_extra_title' => T_('Additional category'),
		), $params );

	// main cat header
	$r = '<thead><tr><th class="selector catsel_main" title="'.$params['category_main_title'].'">'.T_('Main').'</th>';

	// extra cat header
	$r .= '<th class="selector catsel_extra" title="'.$params['category_extra_title'].'">'.T_('Extra').'</th>';

	// category header
	$r .= '<th class="catsel_name">'.$params['category_name'].'</th>'
		.'</tr></thead>';

	return $r;
}


/**
 * Get content of a category display recursively with all of its subcats in a cat selection control
 *
 * @param object Chapter
 * @param array callbacks to display a line of category
 * @param array display params
 * @return string content to display
 */
function cat_select_display( $Chapter, $callbacks, & $params = array() )
{
	$params = array_merge( array(
			'level'  => 1,
			'sorted' => true,
			'total_count' => 0,
		), $params );

	$callbacks = array_merge( array(
			'before_first' => NULL,
			'before_each'  => NULL,
			'after_each'   => NULL,
			'after_last'   => NULL
		), $callbacks );

	$params['total_count'] = $params['total_count'] + 1;
	$parent_Chapter = & $Chapter->get_parent_Chapter();

	$r = '';

	if( is_array( $callbacks['before_each'] ) )
	{ // object callback:
		$r .= $callbacks['before_each'][0]->{$callbacks['before_each'][1]}( $Chapter->ID, $params['level'], $params['total_count'] );
	}
	else
	{
		$r .= $callbacks['before_each']( $Chapter->ID, $params['level'], $params['total_count'] );
	}

	if( is_array( $callbacks['after_each'] ) )
	{ // object callback:
		$r .= $callbacks['after_each'][0]->{$callbacks['after_each'][1]}( $Chapter->ID, $params['level'], $params['total_count'] );
	}
	else
	{
		$r .= $callbacks['after_each']( $Chapter->ID, $params['level'], $params['total_count'] );
	}

	if( empty( $Chapter->children ) )
	{
		return $r;
	}

	if( $params['sorted'] )
	{
		$Chapter->sort_children();
	}

	if( is_array( $callbacks['before_first'] ) )
	{ // object callback:
		$r .= $callbacks['before_first'][0]->{$callbacks['before_first'][1]}( $Chapter->parent_ID, $params['level'], $params['total_count'], 1 );
	}
	else
	{
		$r .= $callbacks['before_first']( $Chapter->parent_ID, $params['level'], $params['total_count'], 1 );
	}

	$params['level'] = $params['level'] + 1;
	foreach( $Chapter->children as $child_Chapter )
	{
		$r .= cat_select_display( $child_Chapter, $callbacks, $params );
	}
	$params['level'] = $params['level'] - 1;

	if( is_array( $callbacks['after_last'] ) )
	{ // object callback:
		$r .= $callbacks['after_last'][0]->{$callbacks['after_last'][1]}( $Chapter->parent_ID, $params['level'], $params['total_count'], 1 );
	}
	else
	{
		$r .= $callbacks['after_last']( $Chapter->parent_ID, $params['level'], $params['total_count'], 1 );
	}

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
	global $current_blog_ID, $blog, $post_extracats, $edited_Item, $current_User;
	global $creating, $cat_select_level, $cat_select_form_fields;

	$ChapterCache = & get_ChapterCache();
	$thisChapter = $ChapterCache->get_by_ID($cat_ID);

	if( $thisChapter->lock && !$current_User->check_perm( 'blog_cats', '', false, $current_blog_ID ) )
	{	// This chapter is locked and current user has no permission to edit the categories of this blog
		return;
	}

	$r = "\n".'<tr class="'.( $total_count%2 ? 'odd' : 'even' ).'">';

	// RADIO for main cat:
	if( get_post_cat_setting($blog) != 2 )
	{ // if no "Multiple categories per post" option is set display radio
		if( !$thisChapter->meta
			&& ( ( $current_blog_ID == $blog ) || ( get_allow_cross_posting( $blog ) >= 2 ) ) )
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
		if( !$thisChapter->meta
			&& ( ($current_blog_ID == $blog) || ( get_allow_cross_posting( $blog ) % 2 == 1 )
				|| ( ( get_allow_cross_posting( $blog ) == 2 ) && ( get_post_cat_setting( $blog ) == 2 ) ) ) )
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

	$additional_style = '';

	if( $thisChapter->meta )
	{	// Change style of meta category
		$additional_style .= 'font-weight:bold;';
	}

	$chapter_lock_status = '';
	if( $thisChapter->lock )
	{	// Add icon for locked category
		$chapter_lock_status = '<span style="padding-left:5px">'.get_icon( 'file_not_allowed', 'imgtag', array( 'title' => T_('Locked')) ).'</span>';
	}

	$BlogCache = & get_BlogCache();
	$r .= '<td class="catsel_name"><label'
				.' for="'.( get_post_cat_setting( $blog ) == 2
					? 'sel_extracat_'.$cat_ID
					: 'sel_maincat_'.$cat_ID ).'"'
				.' style="padding-left:'.($level-1).'em;'.$additional_style.'">'
				.$thisChapter->name.'</label>'
				.$chapter_lock_status
				.' <a href="'.htmlspecialchars($thisChapter->get_permanent_url()).'" title="'.htmlspecialchars(T_('View category in blog.')).'">'
				.'&nbsp;&raquo;&nbsp;' // TODO: dh> provide an icon instead? // fp> maybe the A(dmin)/B(log) icon from the toolbar? And also use it for permalinks to posts?
				.'</a></td>'
			.'</tr>'."\n";

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
 *
 * @param array category display params, contains e.g. the total displayed rows
 * @return string new row code
 */
function cat_select_new( & $cat_display_params )
{
	global $blog, $current_User;

	if( ! $current_User->check_perm( 'blog_cats', '', false, $blog ) )
	{	// Current user cannot add/edit a categories for this blog
		return '';
	}

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

	$cat_display_params['total_count'] = $cat_display_params['total_count']  + 1;
	$r = "\n".'<tr class="'.( $cat_display_params['total_count'] % 2 ? 'odd' : 'even' ).'">';

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
				.'</td>'
			.'</tr>';

	return $r;
}


/**
 * Used by the items & the comments controllers
 *
 * @param boolean TRUE - to display third level tabs, FALSE - to hide them(used in the edit mode)
 */
function attach_browse_tabs( $display_tabs3 = true )
{
	global $AdminUI, $Blog, $current_User, $admin_url, $ItemTypeCache;

	if( empty( $Blog ) )
	{ // No blog
		return;
	}

	$menu_entries = array(
		'full' => array(
			'text' => T_('All'),
			'href' => $admin_url.'?ctrl=items&amp;tab=full&amp;filter=restore&amp;blog='.$Blog->ID,
		)
	);

	if( $Blog->get_setting( 'use_workflow' ) && $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
	{ // We want to use workflow properties for this blog:
		$menu_entries['tracker'] = array(
			'text' => T_('Workflow view'),
			'href' => $admin_url.'?ctrl=items&amp;tab=tracker&amp;filter=restore&amp;blog='.$Blog->ID,
		);
	}

	if( $Blog->get( 'type' ) == 'manual' )
	{ // Display this tab only for manual blogs
		$menu_entries['manual'] = array(
			'text' => T_('Manual view'),
			'href' => $admin_url.'?ctrl=items&amp;tab=manual&amp;filter=restore&amp;blog='.$Blog->ID,
		);
	}

	$type_tabs = get_item_type_tabs();
	foreach( $type_tabs as $type_tab => $type_tab_name )
	{
		$type_tab_key = 'type_'.str_replace( ' ', '_', utf8_strtolower( $type_tab ) );
		$menu_entries[ $type_tab_key ] = array(
			'text' => T_( $type_tab_name ),
			'href' => $admin_url.'?ctrl=items&amp;tab=type&amp;tab_type='.urlencode( $type_tab ).'&amp;filter=restore&amp;blog='.$Blog->ID,
		);
	}

	//$AdminUI->clear_menu_entries( 'collections' );
	$AdminUI->add_menu_entries( array( 'collections', 'posts' ), $menu_entries );

	/* fp> Custom types should be variations of normal posts by default
	  I am ok with giving extra tabs to SOME of them but then the
		posttype list has to be transformed into a normal CREATE/UPDATE/DELETE (CRUD)
		(see the stats>goals CRUD for an example of a clean CRUD)
		and each post type must have a checkbox like "use separate tab"
		Note: you can also make a select list "group with: posts|pages|etc...|other|own tab"

		$ItemTypeCache = & get_ItemTypeCache();
		$default_post_types = array(1,1000,1500,1520,1530,1570,1600,2000,3000,4000,5000);
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

	if( $display_tabs3 && $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
	{ // User has permission to edit published, draft or deprecated comments (at least one kind)
		$AdminUI->add_menu_entries( array( 'collections', 'comments' ), array(
			'fullview' => array(
				'text' => T_('Full text view'),
				'href' => $admin_url.'?ctrl=comments&amp;tab3=fullview&amp;filter=restore&amp;blog='.$Blog->ID ),
			'listview' => array(
				'text' => T_('List view'),
				'href' => $admin_url.'?ctrl=comments&amp;tab3=listview&amp;filter=restore&amp;blog='.$Blog->ID ),
			) );

		if( $current_User->check_perm( 'meta_comment', 'blog', false, $Blog ) )
		{	// Initialize menu entry for meta discussion if current user has a permission:
			$AdminUI->add_menu_entries( array( 'collections', 'comments' ), array(
				'meta' => array(
					'text' => T_('Meta discussion'),
					'href' => $admin_url.'?ctrl=comments&amp;tab3=meta&amp;filter=restore&amp;blog='.$Blog->ID ),
				) );
		}
	}
}


/**
 * Get back-office tabs from post types
 *
 * @return array Tabs
 */
function get_item_type_tabs()
{
	global $DB, $Blog;

	if( empty( $Blog ) )
	{ // Don't get the item types if Blog is not defined
		return array();
	}

	$SQL = new SQL();
	$SQL->SELECT( 'DISTINCT( ityp_usage )' );
	$SQL->FROM( 'T_items__type' );
	$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$Blog->ID );
	$SQL->ORDER_BY( 'ityp_ID' );

	$type_usages = $DB->get_col( $SQL->get() );

	$type_tabs = array();
	foreach( $type_usages as $type_usage )
	{
		if( $type_tab = get_tab_by_item_type_usage( $type_usage ) )
		{	// Only if tab exists for current item type usage:
			$type_tabs[ $type_tab[0] ] = $type_tab[1];
		}
	}

	return $type_tabs;
}


/**
 * Get tab name by item type usage value
 *
 * @return array|boolean
 */
function get_tab_by_item_type_usage( $type_usage )
{
	switch( $type_usage )
	{
		case 'post':
			$type_tab = array( 'post', NT_('Posts') );
			break;
		case 'page':
			$type_tab = array( 'page', NT_('Pages') );
			break;
		case 'special':
			$type_tab = array( 'special', NT_('Special') );
			break;
		case 'intro-front':
		case 'intro-main':
		case 'intro-cat':
		case 'intro-tag':
		case 'intro-sub':
		case 'intro-all':
			$type_tab = array( 'intro', NT_('Intros') );
			break;

		default:
			// Unknown item type usage:
			return false;
	}

	return $type_tab;
}


/**
 * Get item type usage values by tab name
 *
 * @return array
 */
function get_item_type_usage_by_tab( $tab_name )
{
	switch( $tab_name )
	{
		case 'page':
			$type_usages = array( 'page' );
			break;
		case 'special':
			$type_usages = array( 'special' );
			break;
		case 'intro':
			$type_usages = array( 'intro-front', 'intro-main', 'intro-cat', 'intro-tag', 'intro-sub', 'intro-all' );
			break;
		case 'post':
		default:
			$type_usages = array( 'post' );
			break;
	}

	return $type_usages;
}


/**
 * Allow to select status/visibility
 *
 * @param object Form
 * @param string Status
 * @param boolean Mass create
 * @param array labels of statuses
 */
function visibility_select( & $Form, $post_status, $mass_create = false, $labels = array(), $field_label = '' )
{
	$labels = array_merge( get_visibility_statuses('notes-array'), $labels );

	global $current_User, $Blog;

	$mass_create_statuses = array( 'redirected' );

	$sharing_options = array();

	foreach( $labels as $status => $label )
	{
		if( $current_User->check_perm( 'blog_post!'.$status, 'create', false, $Blog->ID ) &&
		    ( !in_array( $status, $mass_create_statuses ) || !$mass_create ) )
		{
			$sharing_options[] = array( $status, $label[0].' <span class="notes">'.$label[1].'</span>' );
		}
	}

	if( count( $sharing_options ) == 1 )
	{ // Only one status is available, don't show visibility select
		$Form->hidden( 'post_status', $sharing_options[0][0] );
		return;
	}

	$Form->radio( 'post_status', $post_status, $sharing_options, $field_label, true );
}


/**
 * Selection of the issue date
 *
 * @todo dh> should display erroneous values (e.g. when giving invalid date) as current (form) value, too.
 * @param Form
 * @param boolean Break line
 * @param string Title
 */
function issue_date_control( $Form, $break = false, $field_title = '' )
{
	global $edited_Item;

	if( $field_title == '' )
	{
		$field_title = T_('Issue date');
	}

	echo $field_title.':<br />';

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


/**
 * Load new status when the item was published
 * If the status is invalid then an error message will be added to messages
 *
 * @param boolean true if creating new post, false on update
 * @return string the publish status or an allowed status if the given status was invalid
 */
function load_publish_status( $creating = false )
{
	global $Messages;

	$publish_status = param( 'publish_status', 'string', '' );
	if( ! in_array( $publish_status, array( 'published', 'community', 'protected', 'private' ) ) )
	{ // Publish with the given status doesn't exists "published"
		if( $creating )
		{
			$Messages->add( T_( 'The post has been created but not published because it seems like you wanted to publish with an invalid status.'), 'error' );
		}
		else
		{
			$Messages->add( T_( 'The post has been updated but not published because it seems like you wanted to publish with an invalid status.'), 'error' );
		}
		$publish_status = 'draft';
	}
	return $publish_status;
}


/**
 * Display buttons to update a post
 *
 * @param object Form
 * @param boolean Is creating action
 * @param object edited Item
 * @param boolean Is in-skin editing
 * @param boolean TRUE to display a preview button
 */
function echo_publish_buttons( $Form, $creating, $edited_Item, $inskin = false, $display_preview = false )
{
	global $Blog, $current_User, $UserSettings;
	global $next_action, $highest_publish_status; // needs to be passed out for echo_publishnowbutton_js( $action )

	list( $highest_publish_status, $publish_text ) = get_highest_publish_status( 'post', $Blog->ID );
	if( ! isset( $edited_Item->status ) )
	{
		$edited_Item->status = $highest_publish_status;
	}

	// ---------- PREVIEW ----------
	if( ! $inskin || $display_preview )
	{
		$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl
		$Form->button( array( 'button', '', T_('Preview'), 'PreviewButton', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );
	}

	// ---------- VISIBILITY ----------
	if( ! $inskin )
	{ // Only for back-office
		global $AdminUI;

		echo '<span class="edit_actions_text">'.T_('Visibility').get_manual_link( 'visibility-status' ).': </span>';

		// Get those statuses which are not allowed for the current User to create posts in this blog
		$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_post!', 'create', $edited_Item->status ), array( 'trash' ) );
		// Get allowed visibility statuses
		$status_options = get_visibility_statuses( '', $exclude_statuses );

		if( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' )
		{ // Use dropdown for bootstrap skin
			$status_icon_options = get_visibility_statuses( 'icons', $exclude_statuses );
			$Form->hidden( 'post_status', $edited_Item->status );
			echo '<div class="btn-group dropup post_status_dropdown">';
			echo '<button type="button" class="btn btn-status-'.$edited_Item->status.' dropdown-toggle" data-toggle="dropdown" aria-expanded="false" id="post_status_dropdown">'
							.'<span>'.$status_options[ $edited_Item->status ].'</span>'
						.' <span class="caret"></span></button>';
			echo '<ul class="dropdown-menu" role="menu" aria-labelledby="post_status_dropdown">';
			foreach( $status_options as $status_key => $status_title )
			{
				echo '<li rel="'.$status_key.'" role="presentation"><a href="#" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.$status_title.'</span></a></li>';
			}
			echo '</ul>';
			echo '</div>';
		}
		else
		{ // Use standard select element for other skins
			echo '<select name="post_status">';
			foreach( $status_options as $status_key => $status_title )
			{
				echo '<option value="'.$status_key.'"'
							.( $edited_Item->status == $status_key ? ' selected="selected"' : '' )
							.' class="btn-status-'.$status_key.'">'
						.$status_title
					.'</option>';
			}
			echo '</select>';
		}
	}

	echo '<span class="btn-group">';

	// ---------- SAVE ----------
	$next_action = ($creating ? 'create' : 'update');
	if( ! $inskin && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{ // Show Save & Edit only on admin mode
		$Form->submit( array( 'actionArray['.$next_action.'_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & edit'), 'SaveEditButton btn-status-'.$edited_Item->status ) );
	}

	if( $inskin )
	{ // Front-office: display a save button with title depending on post status
		$button_titles = get_visibility_statuses( 'button-titles' );
		$button_title = isset( $button_titles[ $edited_Item->status ] ) ? T_( $button_titles[ $edited_Item->status ] ) : T_('Save Changes!');
	}
	else
	{ // Use static button title on back-office
		$button_title = T_('Save');
	}
	$Form->submit( array( 'actionArray['.$next_action.']', $button_title, 'SaveButton btn-status-'.$edited_Item->status ) );

	echo '</span>';

	$Form->hidden( 'publish_status', $highest_publish_status );

	if( $highest_publish_status == 'published' && $UserSettings->get_collection_setting( 'show_quick_publish', $Blog->ID ) )
	{ // Display this button to make a post published

		// Only allow publishing if in draft mode. Other modes are too special to run the risk of 1 click publication.
		$publish_style = ( $edited_Item->status == $highest_publish_status ) ? 'display: none' : 'display: inline';

		$Form->submit( array(
			'actionArray['.$next_action.'_publish]',
			/* TRANS: This is the value of an input submit button */ T_('Publish!'),
			'SaveButton btn-status-published quick-publish',
			'',
			$publish_style
		) );
	}
}


/**
 * Display buttons to update a post
 *
 * @param object Form
 * @param object edited Item
 */
function echo_item_status_buttons( $Form, $edited_Item )
{
	global $next_action, $action, $Blog;

	// Get those statuses which are not allowed for the current User to create posts in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_post!', 'create', $edited_Item->status ), array( 'trash' ) );
	// Get allowed visibility statuses
	$status_options = get_visibility_statuses( 'button-titles', $exclude_statuses );
	$status_icon_options = get_visibility_statuses( 'icons', $exclude_statuses );

	$next_action = ( is_create_action( $action ) ? 'create' : 'update' );

	$Form->hidden( 'post_status', $edited_Item->status );
	echo '<div class="btn-group dropup post_status_dropdown">';
	echo '<button type="submit" class="btn btn-status-'.$edited_Item->status.'" name="actionArray['.$next_action.']">'
				.'<span>'.T_( $status_options[ $edited_Item->status ] ).'</span>'
			.'</button>'
			.'<button type="button" class="btn btn-status-'.$edited_Item->status.' dropdown-toggle" data-toggle="dropdown" aria-expanded="false" id="post_status_dropdown">'
				.'<span class="caret"></span>'
			.'</button>';
	echo '<ul class="dropdown-menu" role="menu" aria-labelledby="post_status_dropdown">';
	foreach( $status_options as $status_key => $status_title )
	{
		echo '<li rel="'.$status_key.'" role="presentation"><a href="#" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.T_( $status_title ).'</span></a></li>';
	}
	echo '</ul>';
	echo '</div>';
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
			jQuery( '#itemform_createlinks input[name="actionArray[create_edit]"]' ).click();
		} );
	</script>
	<?php
}

/**
 * Output JavaScript code to dynamically show or hide the "Publish NOW!" button
 * and also change title of the save button
 * depending on the selected post status.
 *
 * This function is used by the edit item screens.
 */
function echo_publishnowbutton_js()
{
	global $next_action, $highest_publish_status;

	// Build a string to initialize javascript array with button titles
	$button_titles = get_visibility_statuses( 'button-titles' );
	$button_titles_js_array = array();
	foreach( $button_titles as $status => $button_title )
	{
		$button_titles_js_array[] = $status.': \''.TS_( $button_title ).'\'';
	}
	$button_titles_js_array = implode( ', ', $button_titles_js_array );

	?>
	<script type="text/javascript">
		function update_post_status_buttons( status, update_title )
		{
			var item_save_btn_titles = {<?php echo $button_titles_js_array ?>};
			var publishnow_btn = jQuery( '.edit_actions input[name="actionArray[<?php echo $next_action; ?>_publish]"]' );
			var public_status = '<?php echo $highest_publish_status; ?>';

			if( this.value == public_status || public_status == 'draft' )
			{ // Hide the "Publish NOW !" button:
				publishnow_btn.css( 'display', 'none' );
			}
			else
			{ // Show the button:
				publishnow_btn.css( 'display', 'inline' );
			}

			// Change title of the save buttons
			var save_btn = jQuery( '.edit_actions input[name="actionArray[<?php echo $next_action; ?>]"]' );
			save_btn.val( typeof( item_save_btn_titles[ status ] ) == 'undefined' ? '<?php echo TS_('Save Changes!') ?>' : item_save_btn_titles[ status ] );
			save_btn = save_btn.add( '.edit_actions input[name="actionArray[update_edit]"]' )
				.add( '.edit_actions input[name="actionArray[create_edit]"]' );
			save_btn.attr( 'class', save_btn.attr( 'class' ).replace( /btn-status-[^\s]+/, 'btn-status-' + status ) );
		}
		jQuery( '#itemform_visibility input[type=radio]' ).click( function()
		{
			update_post_status_buttons( jQuery( this ).val() );
		} );
	</script>
	<?php
}


/**
 * JS Behaviour: Output JavaScript code to dynamically select status by dropdown
 * button or submit a form if the button is used for submit action
 *
 * This function is used by the post and omment edit screens.
 *
 * @param string Type: 'post' or 'commnet'
 */
function echo_status_dropdown_button_js( $type = 'post' )
{
	?>
	<script type="text/javascript">
		jQuery( '.<?php echo $type; ?>_status_dropdown li a' ).click( function()
		{
			var item = jQuery( this ).parent();
			var status = item.attr( 'rel' );
			var dropdown_buttons = item.parent().parent().find( 'button' );
			var first_button = dropdown_buttons.parent().find( 'button:first' );
			var save_buttons = jQuery( '.edit_actions input[type="submit"]:not(.quick-publish)' ).add( dropdown_buttons );

			if( status == 'published' )
			{ // Hide button "Publish!" if current status is already the "published":
				jQuery( '.edit_actions .quick-publish' ).hide();
			}
			else
			{ // Show button "Publish!" only when another status is selected:
				jQuery( '.edit_actions .quick-publish' ).show();
			}

			save_buttons.each( function()
			{ // Change status class name to new changed for all buttons
				jQuery( this ).attr( 'class', jQuery( this ).attr( 'class' ).replace( /btn-status-[^\s]+/, 'btn-status-' + status ) );
			} );
			first_button.find( 'span:first' ).html( item.find( 'span:last' ).html() ); // update selector button to status title
			jQuery( 'input[type=hidden][name=<?php echo $type; ?>_status]' ).val( status ); // update hidden field to new status value
			item.parent().parent().removeClass( 'open' ); // hide dropdown menu

			if( first_button.attr( 'type' ) == 'submit' )
			{ // Submit form if current dropdown button is used to submit form
				first_button.click();
			}

			return false;
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
	global $restapi_url;
?>
	<script type="text/javascript">
	function init_autocomplete_tags( selector )
	{
		var tags = jQuery( selector ).val();
		var tags_json = new Array();
		if( tags.length > 0 )
		{ // Get tags from <input>
			tags = tags.split( ',' );
			for( var t in tags )
			{
				tags_json.push( { id: tags[t], name: tags[t] } );
			}
		}

		jQuery( selector ).tokenInput( '<?php echo $restapi_url.'tags' ?>',
		{
			theme: 'facebook',
			queryParam: 's',
			propertyToSearch: 'name',
			tokenValue: 'name',
			preventDuplicates: true,
			prePopulate: tags_json,
			hintText: '<?php echo TS_('Type in a tag') ?>',
			noResultsText: '<?php echo TS_('No results') ?>',
			searchingText: '<?php echo TS_('Searching...') ?>',
			jsonContainer: 'tags',
		} );
	}

	jQuery( document ).ready( function()
	{
		if( jQuery( '#suggest_item_tags' ).is( ':checked' ) )
		{
			init_autocomplete_tags( '#item_tags' );
		}

		jQuery( '#suggest_item_tags' ).click( function()
		{
			if( jQuery( this ).is( ':checked' ) )
			{ // Use plugin to suggest tags
				jQuery( '#item_tags' ).hide();
				init_autocomplete_tags( '#item_tags' );
			}
			else
			{ // Remove autocomplete tags plugin
				jQuery( '#item_tags' ).show();
				jQuery( '#item_tags' ).parent().find( 'ul.token-input-list-facebook' ).remove();
			}
		} );
		<?php
			// Don't submit a form by Enter when user is editing the tags
			echo get_prevent_key_enter_js( '#token-input-item_tags' );
		?>
	} );
	</script>
<?php
}


/**
 * Assert that the supplied post type can be used by the current user in
 * the post's extra categories' context.
 *
 * @param integer Item type ID
 * @param array The extra cats of the post.
 */
function check_perm_posttype( $item_typ_ID, $post_extracats )
{
	global $Blog, $current_User;

	$ItemTypeCache = & get_ItemTypeCache();
	$ItemType = & $ItemTypeCache->get_by_ID( $item_typ_ID );

	if( ! $Blog->is_item_type_enabled( $ItemType->ID ) )
	{ // Don't allow to use a not enabled post type:
		debug_die( 'This post type is not enabled. Please choose another one.' );
	}

	// Check permission:
	$current_User->check_perm( 'cats_item_type_'.$ItemType->perm_level, 'edit', true /* assert */, $post_extracats );
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
				$new_Item = clone $Item;

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
 * Checks if current user is allowed to post with extra categories that belong to a different collection
 * than the current main category or move the post with a main category in a different collection than
 * the previous main category collection.
 *
 * @param Object Post category (by reference).
 * @param Array Post extra categories (by reference).
 * @param integer previous post main category
 * @return boolean true - if current user is allowed to cross post.
 */
function check_cross_posting( & $post_category, & $post_extracats, $prev_main_cat = NULL )
{
	global $Messages, $blog, $current_User;
	$result = true;

	$post_category = param( 'post_category', 'integer', -1 );
	$post_extracats = param( 'post_extracats', 'array:integer', array() );

	if( is_null( $prev_main_cat ) )
	{ // new item, no need to check for previous main category
		$prev_main_cat = $post_category;
	}
	$prev_cat_blog = get_catblog( $prev_main_cat );
	$post_cat_blog = get_catblog( $post_category );
	$allow_cross_posting = get_allow_cross_posting();

	// Check if any of the extracats belong to a blog other than the current one
	foreach( $post_extracats as $key => $cat )
	{
		$cat_blog = get_catblog( $cat );
		if( ( $cat_blog != $post_cat_blog ) && ! ( $allow_cross_posting % 2 == 1 && $current_User->check_perm( 'blog_admin', '', false, $cat_blog ) ) )
		{ // this cat is not from the main category
			$Messages->add( T_('You are not allowed to cross post to several collections.') );
			$result = false;
		}
		if( ! $result )
		{ // no need to check other extracats
			break;
		}
	}

	// Check if post_category belongs to a collection different from the previous main cat collection
	if( $prev_main_cat && ( $prev_cat_blog != $post_cat_blog ) &&
			! ( $allow_cross_posting >= 2 && $current_User->check_perm( 'blog_admin', '', false, $prev_cat_blog ) && $current_User->check_perm( 'blog_admin', '', false, $post_cat_blog ) ) )
	{
		$Messages->add( T_('You are not allowed to move post between collections.') );
		$result = false;
	}

	return $result;
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
	$post_extracats = param( 'post_extracats', 'array:integer', array() );
	global $Messages, $Blog, $blog;

	load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
	$ChapterCache = & get_ChapterCache();

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
			$Chapter = & $ChapterCache->get_by_ID( $post_category );
			$post_category_Blog = $Chapter->get_Blog();
			$Messages->add( sprintf( T_('The main category for this post has been automatically set to "%s" (Blog "%s")'),
				$Chapter->get_name(), $post_category_Blog->get( 'name') ), 'warning' );
		}
	}

	if( ! $post_category || in_array( 0, $post_extracats ) )	// if category key is 0 => means it is a new category
	{
		global $current_User;
		if( ! $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
		{	// Current user cannot add a categories for this blog
			check_categories_nosave( $post_category, $post_extracats); // set up the category parameters
			$Messages->add( T_('You are not allowed to create a new category.'), 'error' );
			return false;
		}

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

		$new_Chapter = & $ChapterCache->new_obj( NULL, $blog );	// create new category object
		$new_Chapter->set( 'name', $category_name );
		if( $new_Chapter->dbinsert() !== false )
		{
			$Messages->add( T_('New category created.'), 'success' );
			if( ! $post_category ) // if new category is main category
			{
				$post_category = $new_Chapter->ID;	// set the new ID
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
				$post_extracats[] = $new_Chapter->ID;
			}

			$ChapterCache->add( $new_Chapter );
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
				$Chapter = & $ChapterCache->get_by_ID( $cat );
				$ignored_cats = $ignored_cats.$Chapter->get_name().', ';
				unset( $post_extracats[$key] );
			}
		}
		$ingnored_length = strlen( $ignored_cats );
		if( $ingnored_length > 2 )
		{ // ingnore list is not empty
			global $current_User, $admin_url;
			if( $current_User->check_perm( 'options', 'view', false ) )
			{
				$cross_posting_text = '<a href="'.$admin_url.'?ctrl=collections&amp;tab=blog_settings">'.T_('cross-posting is disabled').'</a>';
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
	$post_extracats = param( 'post_extracats', 'array:integer', array() );

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
 * Load goals on changing of category
 */
function echo_onchange_goal_cat()
{
	global $blog;
?>
	<script type="text/javascript">
		jQuery( '#goal_cat_ID' ).change( function()
		{
			jQuery( '#goal_ID' ).next().find( 'img' ).show();
			var cat_ID = jQuery( this ).val();
			jQuery.ajax(
			{
				type: 'POST',
				url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
				data: 'action=get_goals&cat_id=' + cat_ID + '&blogid=<?php echo $blog; ?>&crumb_itemgoal=<?php echo get_crumb( 'itemgoal' ); ?>',
				success: function( result )
				{
					jQuery( '#goal_ID' ).html( ajax_debug_clear( result ) ).next().find( 'img' ).hide();
				}
			} );
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
 *
 * @param string Comment type: 'feedback' | 'meta'
 */
function echo_show_comments_changed( $comment_type )
{
?>
	<script type="text/javascript">
		jQuery( '[name ^= show_comments]' ).change( function()
		{
			var item_id = jQuery('#comments_container').attr('value');
			if( ! isDefined( item_id) )
			{ // if item_id is not defined, we have to show all comments from current blog
				item_id = -1;
			}
			refresh_item_comments( item_id, 1, '<?php echo $comment_type; ?>' );
		} );
	</script>
<?php
}


/**
 * Display CommentList with the given filters
 *
 * @param integer Blog ID
 * @param integer Item ID
 * @param array Status filters
 * @param integer Limit
 * @param array Comments IDs string to exclude from the list
 * @param string Filterset name
 * @param string Expiry status: 'all', 'active', 'expired'
 * @param string Type of the comments: 'feedback' or 'meta'
 */
function echo_item_comments( $blog_ID, $item_ID, $statuses = NULL, $currentpage = 1, $limit = NULL, $comment_IDs = array(), $filterset_name = '', $expiry_status = 'active', $comment_type = 'feedback' )
{
	global $inc_path, $status_list, $Blog, $admin_url;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	if( empty( $limit ) )
	{ // Get default limit from curent user's setting
		global $UserSettings;
		$limit = $UserSettings->get( 'results_per_page' );
	}

	global $CommentList;
	$CommentList = new CommentList2( $Blog, $limit, 'CommentCache', '', $filterset_name );

	$exlude_ID_list = NULL;
	if( !empty($comment_IDs) )
	{
		$exlude_ID_list = '-'.implode( ",", $comment_IDs );
	}

	if( empty( $statuses ) )
	{
		$statuses = get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) );
	}

	if( $expiry_status == 'all' )
	{ // Display all comments
		$expiry_statuses = array( 'active', 'expired' );
	}
	else
	{ // Display active or expired comments
		$expiry_statuses = array( $expiry_status );
	}

	// if item_ID == -1 then don't use item filter! display all comments from current blog
	if( $item_ID == -1 )
	{
		$item_ID = NULL;
	}
	// set redirect_to
	if( ! is_null( $item_ID ) )
	{
		if( $comment_type == 'meta' )
		{ // Check if current user can sees meta comments of this item
			global $current_User;
			$ItemCache = & get_ItemCache();
			$Item = & $ItemCache->get_by_ID( $item_ID, false, false );
			if( ! $Item || empty( $current_User ) || ! $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
			{ // Current user has no permissions to view meta comments
				$comment_type = 'feedback';
			}
		}

		// redirect to the items full view
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&blog='.$blog_ID.'&p='.$item_ID, '&' ) );
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
		$comments_number_mode = ( $comment_type == 'meta' ? 'metas' : 'comments' );
		param( 'comments_number', 'integer', generic_ctp_number( $item_ID, $comments_number_mode, $show_comments ) );
		// Filter list:
		$CommentList->set_filters( array(
			'types' => $comment_type == 'meta' ? array( 'meta' ) : array( 'comment', 'trackback', 'pingback' ),
			'statuses' => $statuses,
			'expiry_statuses' => $expiry_statuses,
			'comment_ID_list' => $exlude_ID_list,
			'post_ID' => $item_ID,
			'order' => $comment_type == 'meta' ? 'DESC' : 'ASC',//$order,
			'comments' => $limit,
			'page' => $currentpage,
		) );
	}
	else
	{ // redirect to the comments full view
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=comments&blog='.$blog_ID.'&filter=restore', '&' ) );
		// this is an ajax call we always have to restore the filterst (we can set filters only without ajax call)
		$CommentList->set_filters( array(
			'types' => $comment_type == 'meta' ? array( 'meta' ) : array( 'comment', 'trackback', 'pingback' ),
			'order' => $comment_type == 'meta' ? 'DESC' : 'ASC',
		) );
		$CommentList->restore_filterset();
	}

	// Get ready for display (runs the query):
	$CommentList->display_init();

	$CommentList->display_if_empty( array(
		'before'    => '<div class="evo_comment"><p>',
		'after'     => '</p></div>',
		'msg_empty' => T_('No feedback for this post yet...'),
	) );

	// display comments
	require $inc_path.'comments/views/_comment_list.inc.php';
}


/**
 * Display a comment corresponding the given comment id
 *
 * @param object Comment object
 * @param string where to redirect after comment edit
 * @param boolean true to set the new redirect param, false otherwise
 * @param integer Comment index in the current list, FALSE - to don't display a comment index
 * @param boolean TRUE to display info for meta comment
 */
function echo_comment( $Comment, $redirect_to = NULL, $save_context = false, $comment_index = NULL, $display_meta_title = false )
{
	global $current_User, $localtimenow;

	$Item = & $Comment->get_Item();
	$Blog = & $Item->get_Blog();

	$is_published = ( $Comment->get( 'status' ) == 'published' );
	$expiry_delay = $Item->get_setting( 'comment_expiry_delay' );
	$is_expired = ( !empty( $expiry_delay ) && ( ( $localtimenow - mysql2timestamp( $Comment->get( 'date' ) ) ) > $expiry_delay ) );

	echo '<a name="c'.$Comment->ID.'"></a>';
	echo '<div id="comment_'.$Comment->ID.'" class="panel '.( $Comment->ID > 0 ? 'panel-default' : 'panel-warning' ).' evo_comment evo_comment__status_';
	// check if comment is expired
	if( $is_expired )
	{ // comment is expired
		echo 'expired';
	}
	elseif( $Comment->is_meta() )
	{ // meta comment
		echo 'meta';
	}
	else
	{ // comment is not expired and not meta
		$Comment->status('raw');
	}
	echo '">';

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) ||
	    ( $Comment->is_meta() && $current_User->check_perm( 'meta_comment', 'view', false, $Item ) ) )
	{ // User can moderate this comment OR Comment is meta and current user can view it
		echo '<div class="panel-heading small">';
		echo '<div>';

		if( $Comment->is_meta() )
		{ // Meta comment
			if( $comment_index !== false )
			{	// Display ID for each meta comment
				echo '<span class="badge badge-info">'.$comment_index.'</span> ';
			}

			if( $display_meta_title )
			{	// Display a title for meta comment:
				$comment_Item = & $Comment->get_Item();
				echo sprintf( T_('<a %s>Meta comment</a> on %s'),
							'href="'.$Comment->get_permanent_url().'"',
							'<a href="?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'">'.$comment_Item->dget( 'title' ).'</a>'
								.' '.$comment_Item->get_permanent_link( '#icon#' ).' &middot; ' );
			}
		}

		if( ! $Comment->is_meta() )
		{	// Display permalink oly for normal comments:
			echo '<div class="pull-right">';
			$Comment->permanent_link( array(
					'before' => '',
					'text'   => '#text#'
				) );
			echo '</div>';
		}

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( '#short_time' );
		echo '</span>';

		if( $Comment->is_meta() )
		{ // Display only author for meta comment
			$Comment->author( '', '', ' &middot; '.T_('Author').': ', '' );
		}
		else
		{ // Display the detailed info for standard comment
			$Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
			echo ' &middot; <span class="bKarma">';
			$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
			echo '</span>';

			echo '</div>';
			echo '<div style="padding-top:3px">';
			if( $is_expired )
			{
				echo '<div class="pull-right">';
				echo '<span class="bExpired">'.T_('EXPIRED').'</span>';
				echo '</div>';
			}
			$Comment->author_ip( 'IP: <span class="bIP">', '</span> &middot; ', true, true );
			$Comment->ip_country( '', ' &middot; ' );
			$Comment->author_url_with_actions( /*$redirect_to*/'', true, true );
		}
		echo '</div>';
		echo '</div>';

		echo '<div class="panel-body">';
		if( ! $Comment->is_meta() )
		{	// Display status banner only for normal comments:
			$Comment->format_status( array(
					'template' => '<div class="pull-right"><span class="note status_$status$"><span>$status_title$</span></span></div>',
				) );
		}
		if( ! $Comment->is_meta() )
		{ // Don't display the titles for meta comments
			echo '<div class="bCommentTitle">';
			echo $Comment->get_title();
			if( get_param( 'p' ) == '' )
			{ // Don't display this title on a post view page
				echo ' '.T_('in response to')
						.' <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$Item->dget('title').'</a>';
			}
			echo '</div>';
		}
		echo '<div class="bCommentText">';
		$Comment->rating();
		$Comment->avatar( 'crop-top-80x80' );
		if( $current_User->check_perm( 'meta_comment', 'edit', false, $Comment ) )
		{ // Put the comment content into this container to edit by ajax
			echo '<div id="editable_comment_'.$Comment->ID.'" class="editable_comment_content">';
		}
		$Comment->content( 'htmlbody', 'true' );
		if( $current_User->check_perm( 'meta_comment', 'edit', false, $Comment ) )
		{ // End of the container that is used to edit meta comment by ajax
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';

		if( ! empty( $Comment->ID ) )
		{	// Display action buttons panel only for existing Comment in DB:
			echo '<div class="panel-footer">';

			echo '<div class="pull-left">';

			// Display edit button if current user has the rights:
			$Comment->edit_link( ' ', ' ', get_icon( 'edit_button' ).' '.T_('Edit'), '#', button_class( 'text_primary' ).' w80px', '&amp;', $save_context, $redirect_to );

			echo '<span class="'.button_class( 'group' ).'">';
			// Display publish NOW button if current user has the rights:
			$link_params = array(
				'class'        => button_class( 'text' ),
				'save_context' => $save_context,
				'ajax_button'  => true,
				'redirect_to'  => $redirect_to,
			);
			$Comment->raise_link( $link_params );

			// Display deprecate button if current user has the rights:
			$Comment->lower_link( $link_params );

			$next_status_in_row = $Comment->get_next_status( false );
			if( $next_status_in_row && $next_status_in_row[0] != 'deprecated' )
			{ // Display deprecate button if current user has the rights:
				$Comment->deprecate_link( '', '', get_icon( 'move_down_grey', 'imgtag', array( 'title' => '' ) ), '#', button_class(), '&amp;', true, true );
			}

			// Display delete button if current user has the rights:
			$Comment->delete_link( '', '', '#', '#', button_class( 'text' ), false, '&amp;', $save_context, true, '#', $redirect_to );
			echo '</span>';

			echo '</div>';

			if( ! $Comment->is_meta() )
			{ // Display Spam Voting system
				$Comment->vote_spam( '', '', '&amp;', $save_context, true );
			}

			echo '<div class="clearfix"></div>';
			echo '</div>';
		}
	}
	else
	{	// No permissions to moderate of this comment, just preview
		echo '<div class="panel-heading small">';
		echo '<div>';

		echo '<div class="pull-right">';
		echo T_('Visibility').': ';
		echo '<span class="bStatus">';
		$Comment->status();
		echo '</span>';
		echo '</div>';

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( '#short_time' );
		echo '</span>';

		echo '</div>';
		echo '</div>';

		if( $is_published )
		{
			echo '<div class="panel-body">';
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
	}

	echo '</div>'; // end
}


/**
 * Display a page link of the comments on item full view
 *
 * @param integer the item id
 * @param string link text
 * @param integer the page number
 */
function echo_comment_pagenumber( $item_ID, $text, $page, $params = array() )
{
	$params = array_merge( array(
			'page_before' => ' ',
			'page_after'  => '',
		), $params );

	global $blog, $admin_url;
	$page_param = $page > 1 ? '&amp;currentpage='.$page : '';
	$comment_type = get_param( 'comment_type' );
	$page_param .= $comment_type == 'meta' ? '&amp;comment_type=meta' : '';
	echo $params['page_before']
			.'<a href="'.url_add_param( $admin_url, 'ctrl=items&amp;blog='.$blog.'&amp;p='.$item_ID.$page_param.'#comments' ).'" onclick="startRefreshComments( \''.request_from().'\', '.$item_ID.', '.$page.', \''.$comment_type.'\' ); return false;">'.$text.'</a>'
		.$params['page_after'];
}


/**
 * Display page links of the comments on item full view
 *
 * @param integer the item id
 * @param integer current page number
 * @param integer all comments number in the list
 * @param array Params
 */
function echo_comment_pages( $item_ID, $currentpage, $comments_number, $params = array() )
{
	global $UserSettings;

	$params = array_merge( array(
			'list_span'  => 11, // The number of pages to display for one time
			'page_size'  => $UserSettings->get( 'results_per_page' ), // The number of comments on one page
			'list_start' => '<div class="results_nav" id="paging">',
			'list_end'   => '</div>',
			'prev_text'  => T_('Previous'),
			'next_text'  => T_('Next'),
			'pages_text' => '<strong>'.T_('Pages').'</strong>:',
			'page_before'         => ' ',
			'page_after'          => '',
			'page_current_before' => ' <strong>',
			'page_current_after'  => '</strong>',
		), $params );

	if( $comments_number == 0 )
	{ // No comments
		return;
	}

	$total_pages = ceil( $comments_number / $params['page_size'] );

	if( $total_pages < 2 )
	{ // No pages
		return;
	}

	if( $currentpage > $total_pages )
	{ // current page number is greater then all page number, set current page to the last existing page
		$currentpage = $total_pages;
	}

	// Set first page
	if( $currentpage <= intval( $params['list_span'] / 2 ) )
	{ // the current page number is small
		$first_page = 1;
	}
	elseif( $currentpage > $total_pages - intval( $params['list_span'] / 2 ) )
	{ // the current page number is big
		$first_page = max( 1, $total_pages - $params['list_span'] + 1 );
	}
	else
	{ // the current page number can be centered
		$first_page = $currentpage - intval( $params['list_span'] / 2 );
	}

	// Set last page
	if( $currentpage > $total_pages - intval( $params['list_span'] / 2 ) )
	{ // the current page number is big
		$last_page = $total_pages;
	}
	else
	{
		$last_page = min( $total_pages, $first_page + $params['list_span'] - 1 );
	}

	echo '<div id="currentpage" value='.$currentpage.' /></div>';
	echo $params['list_start'];
	if( $comments_number > 0 )
	{
		echo $params['pages_text'];
		if( $currentpage > 1 )
		{ // link to previous page
			echo_comment_pagenumber( $item_ID, $params['prev_text'], $currentpage - 1, $params );
		}
		if( $first_page > 1 )
		{ // link to first page
			echo_comment_pagenumber( $item_ID, '1', '1', $params );
		}
		if( $first_page > 2 )
		{ // link to previous pages
			$page_i = ceil( $first_page / 2 );
			echo_comment_pagenumber( $item_ID, '...', $page_i, $params );
		}
		for( $i = $first_page; $i <= $last_page; $i++ )
		{ // Display list with pages
			if( $i == $currentpage )
			{
				echo $params['page_current_before'].$i.$params['page_current_after'];
			}
			else
			{
				echo_comment_pagenumber( $item_ID, $i, $i, $params );
			}
		}
		if( $last_page < $total_pages - 1 )
		{ // link to next pages
			$page_i = $last_page + floor( ( $total_pages - $last_page ) / 2 );
			echo_comment_pagenumber( $item_ID, '...', $page_i, $params );
		}
		if( $last_page < $total_pages )
		{ // link to last page
			echo_comment_pagenumber( $item_ID, $total_pages, $total_pages, $params );
		}
		if( $currentpage < $total_pages )
		{ // link to next page
			echo_comment_pagenumber( $item_ID, $params['next_text'], $currentpage + 1, $params );
		}
	}
	echo $params['list_end'];
}


/**
 * Check permission for editing of the item by current user
 *
 * @param integer post ID
 * @param boolean Set TRUE if we want to redirect when user cannot edit this post
 * @return boolean TRUE - user can edit this post
 */
function check_item_perm_edit( $post_ID, $do_redirect = true )
{
	global $Messages;
	global $Blog, $current_User;

	$user_can_edit = false;

	if( $post_ID > 0 )
	{ // Check permissions for editing of the current item:
		$ItemCache = & get_ItemCache ();
		$edited_Item = $ItemCache->get_by_ID ( $post_ID );
		$user_can_edit = $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item );
		$permission_message = T_('You don\'t have permission to edit this post');

		if( $user_can_edit )
		{ // Check if the post type is enabled:

			if( ! $edited_Item->is_type_enabled() )
			{ // Don't allow to use a not enabled post type:
				$user_can_edit = false;
				if( $edited_ItemType = & $edited_Item->get_ItemType() )
				{
					$permission_message = sprintf( T_( 'The post you are trying to edit uses the post type #%d "%s" which is currently disabled. Thus you cannot edit this post.' ),
						$edited_ItemType->ID, $edited_ItemType->get( 'name' ) );
				}
				else
				{
					$permission_message = T_( 'The post you are trying to edit uses an unknown post type. Thus you cannot edit this post.' );
				}
			}
		}
	}
	else
	{ // Check permissions for creating of a new item:
		$perm_target = empty( $Blog ) ? NULL : $Blog->ID;
		$user_can_edit = $current_User->check_perm( 'blog_post_statuses', 'edit', false, $perm_target );
		$permission_message = T_('You don\'t have permission to post into this blog');
	}

	if( ! $user_can_edit )
	{
		if( $do_redirect )
		{	// Redirect to the blog url for users without messaging permission
			$Messages->add( $permission_message );
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
		else
		{	// Current user cannot edit this post
			return false;
		}
	}

	return true;
}


/**
 * Check permission for creating of a new item by current user
 *
 * @return boolean, TRUE if user can create a new post for the current blog
 */
function check_item_perm_create()
{
	global $Blog;

	if( empty( $Blog ) )
	{	// Strange case, but we restrict to create a new post
		return false;
	}

	if( ! is_logged_in( false ) || ! $Blog->get_setting( 'in_skin_editing' ) )
	{	// Don't allow anonymous users to create a new post & If setting is OFF
		return false;
	}
	else
	{	// Check permissions for current user
		global $current_User;
		return $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID );
	}

	return true;
}


/**
 * Template tag: Display a link to create a new post
 */
function item_new_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_item_new_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to create a new post
 *
 * @return string|false
 */
function get_item_new_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $Blog;

	if( ! check_item_perm_create() )
	{	// Don't allow users to create a new post
		return false;
	}

	if( $link_text == '' )
	{	// Default text
		$link_text = T_('Write a new post');
	}

	if( $link_title == '#' ) $link_title = T_('Write a new post');

	$r = $before
		.'<a href="'.url_add_param( $Blog->get( 'url' ), 'disp=edit' ).'" title="'.$link_title.'">'
		.$link_text
		.'</a>'
		.$after;

	return $r;
}


/**
 * Display location select elements (Country, Region, Subregion, City)
 *
 * @param object Form
 * @param object Edited Item
 * @param array Params
 */
function echo_item_location_form( & $Form, & $edited_Item, $params = array() )
{
	load_class( 'regional/model/_country.class.php', 'Country' );
	load_funcs( 'regional/model/_regional.funcs.php' );

	if( ! $edited_Item->country_visible() )
	{	// If country is NOT visible it means all other location fields also are not visible, so exit here
		return;
	}

	$params = array_merge( array(
			'fold' => false,
		), $params );

	global $rsc_url;

	$Form->begin_fieldset( T_('Location'), array( 'id' => 'itemform_location', 'fold' => $params['fold'] ) );

	$table_class = '';
	if( $edited_Item->is_special() )
	{ // A post with special type should always has the location fields as not required
		// This css class hides all red stars of required fields
		$table_class = ' not_required';
	}

	$Form->switch_layout( 'table' );
	$Form->formstart = '<table id="item_locations" cellspacing="0" class="fform'.$table_class.'">'."\n";
	$Form->labelstart = '<td class="right"><strong>';
	$Form->labelend = '</strong></td>';

	echo $Form->formstart;

	$button_refresh_regional = '<button id="%s" type="submit" name="actionArray[edit_switchtab]" class="action_icon refresh_button">'.get_icon( 'refresh' ).'</button>';
	$button_refresh_regional .= '<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.T_('Loading...').'" title="'.T_('Loading...').'" style="display:none;margin-left:5px" align="top" />';

	// Country
	$CountryCache = & get_CountryCache();
	$country_is_required = ( $edited_Item->get_type_setting( 'use_country' ) == 'required' );
	$Form->select_country( 'item_ctry_ID', $edited_Item->ctry_ID, $CountryCache, T_('Country'), array( 'required' => $country_is_required, 'allow_none' => true ) );

	if( $edited_Item->region_visible() )
	{ // Region
		$region_is_required = ( $edited_Item->get_type_setting( 'use_region' ) == 'required' );
		$Form->select_input_options( 'item_rgn_ID', get_regions_option_list( $edited_Item->ctry_ID, $edited_Item->rgn_ID, array( 'none_option_text' => T_( 'Unknown' ) ) ), T_( 'Region' ), sprintf( $button_refresh_regional, 'button_refresh_region' ), array( 'required' => $region_is_required ) );
	}

	if( $edited_Item->subregion_visible() )
	{ // Subregion
		$subregion_is_required = ( $edited_Item->get_type_setting( 'use_sub_region' ) == 'required' );
		$Form->select_input_options( 'item_subrg_ID', get_subregions_option_list( $edited_Item->rgn_ID, $edited_Item->subrg_ID, array( 'none_option_text' => T_( 'Unknown' ) ) ), T_( 'Sub-region' ), sprintf( $button_refresh_regional, 'button_refresh_subregion' ), array( 'required' => $subregion_is_required ) );
	}

	if( $edited_Item->city_visible() )
	{ // City
		$city_is_required = ( $edited_Item->get_type_setting( 'use_city' ) == 'required' );
		$Form->select_input_options( 'item_city_ID', get_cities_option_list( $edited_Item->ctry_ID, $edited_Item->rgn_ID, $edited_Item->subrg_ID, $edited_Item->city_ID, array( 'none_option_text' => T_( 'Unknown' ) ) ), T_( 'City' ), sprintf( $button_refresh_regional, 'button_refresh_city' ), array( 'required' => $city_is_required ) );
	}

	echo $Form->formend;

	$Form->switch_layout( NULL );

	$Form->end_fieldset();
}


/**
 * Display custom field settings as hidden input values
 *
 * @param object Form
 * @param object edited Item
 */
function display_hidden_custom_fields( & $Form, & $edited_Item )
{
	$custom_fields = $edited_Item->get_type_custom_fields();
	foreach( $custom_fields as $custom_field )
	{ // For each custom field with type $type:
		$Form->hidden( 'item_'.$custom_field['type'].'_'.$custom_field['ID'], $edited_Item->get_setting( 'custom_'.$custom_field['type'].'_'.$custom_field['ID'] ) );
	}
}


/**
 * Save object Item into Session
 *
 * @param object Item
 */
function set_session_Item( $Item )
{
	global $Session;

	if( ! is_object( $Item ) )
	{
		return;
	}

	$edited_items = $Session->get( 'edited_items' );

	if( ! is_array( $edited_items ) )
	{ // Initialize an array for Item objects
		$edited_items = array();
	}

	$edited_items[ intval( $Item->ID ) ] = $Item;

	$Session->delete( 'edited_items' );
	$Session->set( 'edited_items', $edited_items );
}


/**
 * Get object Item from Session
 *
 * @param integer Item ID
 * @return object Item
 */
function get_session_Item( $item_ID = 0 )
{
	global $Session;

	$edited_items = $Session->get( 'edited_items' );

	if( isset( $edited_items[ $item_ID ] ) && is_object( $edited_items[ $item_ID ] ) )
	{
		$edited_Item = $edited_items[ $item_ID ];

		// Reload main Chapter
		$edited_Item->main_Chapter = NULL;
		$edited_Item->get_main_Chapter();

		// Reload Post Type
		$edited_Item->ItemType = NULL;
		$edited_Item->get_ItemType();

		return $edited_Item;
	}

	return NULL;
}


/**
 * Delete object Item from Session
 *
 * @param integer Item ID
 */
function delete_session_Item( $item_ID )
{
	global $Session;

	$edited_items = $Session->get( 'edited_items' );

	if( ! isset( $edited_items[ $item_ID ] ) )
	{ // Item doesn't exist in Session
		return;
	}

	unset( $edited_items[ $item_ID ] );

	$Session->delete( 'edited_items' );
	$Session->set( 'edited_items', $edited_items );
}


/**
 * Prepare item content
 *
 * @param string Content
 * @return string Content
 */
function prepare_item_content( $content )
{
	// Convert the content separators to new format:
	$old_separators = array(
			'&lt;!--more--&gt;', '<!--more-->', '<p>[teaserbreak]</p>',
			'&lt;!--nextpage--&gt;', '<!--nextpage-->', '<p>[pagebreak]</p>'
		);
	$new_separators = array(
			'[teaserbreak]', '[teaserbreak]', '[teaserbreak]',
			'[pagebreak]', '[pagebreak]', '[pagebreak]'
		);
	if( strpos( $content, '<code' ) !== false || strpos( $content, '<pre' ) !== false )
	{ // Call prepare_item_content_callback() on everything outside code/pre:
		$content = callback_on_non_matching_blocks( $content,
			'~<(code|pre)[^>]*>.*?</\1>~is',
			'replace_content', array( $old_separators, $new_separators, 'str' ) );
	}
	else
	{ // No code/pre blocks, replace on the whole thing
		$content = str_replace( $old_separators, $new_separators, $content );
	}

	return $content;
}


/**
 * Get priority titles of an item
 *
 * @param boolean TRUE - to include null value
 * @return array Priority titles
 */
function item_priority_titles( $include_null_value = true )
{
	$priorities = array();

	if( $include_null_value )
	{
		$priorities[0] = /* TRANS: "None" select option */ T_('No priority');
	}

	$priorities += array(
			1 => /* TRANS: Priority name */ T_('1 - Highest'),
			2 => /* TRANS: Priority name */ T_('2 - High'),
			3 => /* TRANS: Priority name */ T_('3 - Medium'),
			4 => /* TRANS: Priority name */ T_('4 - Low'),
			5 => /* TRANS: Priority name */ T_('5 - Lowest'),
		);

	return $priorities;
}


/**
 * Get priority colors of an item
 *
 * @return array Priority values
 */
function item_priority_colors()
{
	return array(
			1 => 'EB5A46', // Highest
			2 => 'FFAB4A', // High
			3 => 'F2D600', // Medium
			4 => '61BD4F', // Low
			5 => '00C2E0', // Lowest
		);
}


/**
 * Get priority title of an item by priority value
 *
 * @param integer Priority value
 * @return string Priority title
 */
function item_priority_title( $priority )
{
	$titles = item_priority_titles();

	return isset( $titles[ $priority ] ) ? $titles[ $priority ] : $priority;
}


/**
 * Get priority color of an item by priority value
 *
 * @param string Priority value
 * @return string Color value
 */
function item_priority_color( $priority )
{
	$colors = item_priority_colors();

	return isset( $colors[ $priority ] ) ? '#'.$colors[ $priority ] : 'none';
}


/**
 * Display the manual pages results table
 *
 * @param array Params
 */
function items_manual_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'results_param_prefix' => 'items_manual_',
		), $params );

	if( !is_logged_in() )
	{ // Only logged in users can access to this function
		return;
	}

	global $current_User, $blog, $Blog, $admin_url, $Session;

	$result_fadeout = $Session->get( 'fadeout_array' );

	$cat_ID = param( 'cat_ID', 'integer', 0, true );

	if( empty( $Blog ) )
	{ // Init Blog
		$BlogCache = & get_BlogCache();
		$blog = get_param( 'blog' );
		if( !empty( $blog ) )
		{ // Get Blog by ID
			$Blog = $BlogCache->get_by_ID( $blog, false );
		}
		if( empty( $Blog ) && !empty( $cat_ID ) )
		{ // Get Blog from chapter ID
			$ChapterCache = & get_ChapterCache();
			if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) )
			{
				$Blog = $Chapter->get_Blog();
				$blog = $Blog->ID;
			}
		}
	}

	if( empty( $Blog ) || $Blog->get( 'type' ) != 'manual' )
	{ // No Blog, Exit here
		return;
	}

	if( is_ajax_content() )
	{
		$order_action = param( 'order_action', 'string' );

		if( $order_action == 'update' )
		{ // Update an order to new value
			$new_value = (int)param( 'new_value', 'string', 0 );
			$order_data = param( 'order_data', 'string' );
			$order_data = explode( '-', $order_data );
			$order_obj_ID = (int)$order_data[2];
			if( $order_obj_ID > 0 )
			{
				switch( $order_data[1] )
				{
					case 'chapter':
						// Update chapter order
						$ChapterCache = & get_ChapterCache();
						if( $updated_Chapter = & $ChapterCache->get_by_ID( $order_obj_ID, false ) )
						{
							if( $current_User->check_perm( 'blog_cats', '', false, $updated_Chapter->blog_ID ) )
							{ // Check permission to edit this Chapter
								$updated_Chapter->set( 'order', $new_value );
								$updated_Chapter->dbupdate();
								$ChapterCache->clear();
							}
						}
						break;

					case 'item':
						// Update item order
						$ItemCache = & get_ItemCache();
						if( $updated_Item = & $ItemCache->get_by_ID( $order_obj_ID, false ) )
						{
							if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $updated_Item ) )
							{ // Check permission to edit this Item
								$updated_Item->set( 'order', $new_value );
								$updated_Item->dbupdate();
							}
						}
						break;
				}
			}
		}
	}

	load_class( '_core/ui/_uiwidget.class.php', 'Table' );

	$Table = new Table( 'Results', $params['results_param_prefix'] );

	$Table->title = T_('Manual view');

	// Redirect to manual pages after adding chapter
	$redirect_page = '&amp;redirect_page=manual';
	$Table->global_icon( T_('Add new chapter...'), 'add', $admin_url.'?ctrl=chapters&amp;action=new&amp;blog='.$blog.$redirect_page, ' '.T_('Add top level chapter').' &raquo;', 3, 4 );

	$Table->cols[] = array(
							'th' => T_('Name'),
						);
	$Table->cols[] = array(
							'th' => T_('URL "slug"'),
						);
	$Table->cols[] = array(
							'th' => T_('Order'),
							'th_class' => 'shrinkwrap',
						);
	$Table->cols[] = array(
							'th' => T_('Actions'),
						);

	if( is_ajax_content() )
	{ // init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$Table->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$Table->display_init( NULL, $result_fadeout );

	echo $Table->params['before'];

	$Table->display_head();

	echo $Table->replace_vars( $Table->params['content_start'] );

	$Table->display_list_start();

		$Table->display_col_headers();

		$Table->display_body_start();

		manual_display_chapters();

		$Table->display_body_end();

	$Table->display_list_end();

	// Flush fadeout
	$Session->delete( 'fadeout_array');

	echo $Table->params['content_end'];

	echo $Table->params['after'];

	if( !is_ajax_content() )
	{ // Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Display the created items results table
 *
 * @param array Params
 */
function items_created_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'actv_postown_',
			'results_title'        => T_('Posts created by the user'),
			'results_no_text'      => T_('User has not created any posts'),
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function
		return;
	}

	global $current_User;
	if( !$current_User->check_perm( 'users', 'moderate' ) )
	{	// Check minimum permission:
		return;
	}

	$edited_User = $params['edited_User'];
	if( !$edited_User )
	{	// No defined User, probably the function is calling from AJAX request
		$user_ID = param( 'user_ID', 'integer', 0 );
		if( empty( $user_ID ) )
		{	// Bad request, Exit here
			return;
		}
		$UserCache = & get_UserCache();
		if( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
		{	// Bad request, Exit here
			return;
		}
	}

	global $DB, $AdminUI;

	param( 'user_tab', 'string', '', true );
	param( 'user_ID', 'integer', 0, true );

	$SQL = new SQL();
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_items__item' );
	$SQL->WHERE( 'post_creator_user_ID = '.$DB->quote( $edited_User->ID ) );

	// Create result set:
	$created_items_Results = new Results( $SQL->get(), $params['results_param_prefix'], 'D' );
	$created_items_Results->Cache = & get_ItemCache();
	$created_items_Results->title = $params['results_title'];
	$created_items_Results->no_results_text = $params['results_no_text'];

	// Get a count of the post which current user can delete
	$deleted_posts_created_count = count( $edited_User->get_deleted_posts( 'created' ) );
	if( ( $created_items_Results->get_total_rows() > 0 ) && ( $deleted_posts_created_count > 0 ) )
	{	// Display action icon to delete all records if at least one record exists & current user can delete at least one item created by user
		$created_items_Results->global_icon( sprintf( T_('Delete all post created by %s'), $edited_User->login ), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_posts_created&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
	}

	// Initialize Results object
	items_results( $created_items_Results, array(
			'field_prefix' => 'post_',
			'display_ord' => false,
			'display_history' => false,
		) );

	$results_params = $AdminUI->get_template( 'Results' );
	$display_params = array(
		'before' => str_replace( '>', ' style="margin-top:25px" id="created_posts_result">', $results_params['before'] ),
	);

	if( is_ajax_content() )
	{ // init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$created_items_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$created_items_Results->display( $display_params );

	if( !is_ajax_content() )
	{	// Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Display the edited items results table
 *
 * @param array Params
 */
function items_edited_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'actv_postedit_',
			'results_title'        => T_('Posts edited by the user'),
			'results_no_text'      => T_('User has not edited any posts'),
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function
		return;
	}

	global $current_User;
	if( !$current_User->check_perm( 'users', 'moderate' ) )
	{	// Check minimum permission:
		return;
	}

	$edited_User = $params['edited_User'];
	if( !$edited_User )
	{	// No defined User, probably the function is calling from AJAX request
		$user_ID = param( 'user_ID', 'integer', 0 );
		if( empty( $user_ID ) )
		{	// Bad request, Exit here
			return;
		}
		$UserCache = & get_UserCache();
		if( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
		{	// Bad request, Exit here
			return;
		}
	}

	global $DB, $AdminUI;

	param( 'user_tab', 'string', '', true );
	param( 'user_ID', 'integer', 0, true );

	$edited_versions_SQL = new SQL();
	$edited_versions_SQL->SELECT( 'DISTINCT( iver_itm_ID )' );
	$edited_versions_SQL->FROM( 'T_items__version' );
	$edited_versions_SQL->WHERE( 'iver_edit_user_ID = '.$DB->quote( $edited_User->ID ) );

	$SQL = new SQL();
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_items__item ' );
	$SQL->WHERE( '( ( post_lastedit_user_ID = '.$DB->quote( $edited_User->ID ).' ) OR ( post_ID IN ( '.$edited_versions_SQL->get().' ) ) )' );
	$SQL->WHERE_and( 'post_creator_user_ID != '.$DB->quote( $edited_User->ID ) );

	// Create result set:
	$edited_items_Results = new Results( $SQL->get(), $params['results_param_prefix'], 'D' );
	$edited_items_Results->Cache = & get_ItemCache();
	$edited_items_Results->title = $params['results_title'];
	$edited_items_Results->no_results_text = $params['results_no_text'];

	// Get a count of the post which current user can delete
	$deleted_posts_edited_count = count( $edited_User->get_deleted_posts( 'edited' ) );
	if( ( $edited_items_Results->get_total_rows() > 0 ) && ( $deleted_posts_edited_count > 0 ) )
	{	// Display actino icon to delete all records if at least one record exists & current user can delete at least one item created by user
		$edited_items_Results->global_icon( sprintf( T_('Delete all post edited by %s'), $edited_User->login ), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_posts_edited&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
	}

	// Initialize Results object
	items_results( $edited_items_Results, array(
			'field_prefix' => 'post_',
			'display_ord' => false,
			'display_history' => false,
		) );

	if( is_ajax_content() )
	{ // init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$edited_items_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$results_params = $AdminUI->get_template( 'Results' );
	$display_params = array(
		'before' => str_replace( '>', ' style="margin-top:25px" id="edited_posts_result">', $results_params['before'] ),
	);
	$edited_items_Results->display( $display_params );

	if( !is_ajax_content() )
	{	// Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Display the items list (Used to load next page of the items by AJAX)
 *
 * @param array Params
 */
function items_list_block_by_page( $params = array() )
{
	$params = array_merge( array(
			'skin_name'    => '',
			'content_mode' => 'auto', // 'auto' will auto select depending on $disp-detail
			'image_size'   => 'fit-400x320',
			'block_start'  => '<div class="navigation ajax">',
			'block_end'    => '</div>',
			'links_format' => '$next$',
			'next_text'    => T_('Load more entries').'&hellip;',
		), $params );

	if( !skin_init_ajax( $params['skin_name'], 'posts' ) )
	{	// Exit here if skin cannot be initialized
		return;
	}

	while( $Item = & mainlist_get_item() )
	{	// For each blog post:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', $params );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}

	// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
	mainlist_page_links( $params );
	// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
}


/**
 * In-skin display of an Item.
 * It is a wrapper around the skin '_item_list.inc.php' file.
 *
 * @param object Item
 */
function item_inskin_display( $Item )
{
	global $cat;
	$params = array( 'Item' => $Item );

	if( isset( $cat ) && ( $cat != $Item->main_cat_ID ) )
	{
		$params = array_merge( array(
				'before_title'   => '<h3>',
				'after_title'    => '</h3>',
				'before_content' => '<div class="excerpt">',
				'after_content'  => '</div>'
			), $params );
	}

	skin_include( '_item_list.inc.php', $params );
}


/**
 * Load user data (post/comment) read statuses for current user for a list of post IDs.
 *
 * @param array Load only for posts with these ids
 */
function load_user_data_for_items( $post_ids = NULL )
{
	global $DB, $current_User, $user_post_read_statuses;

	if( ! is_logged_in() )
	{	// There are no logged in user:
		return;
	}

	if( is_array( $user_post_read_statuses ) )
	{	// User read statuses were already set:
		return;
	}
	else
	{	// Init with an empty array:
		$user_post_read_statuses = array();
	}

	$post_condition = empty( $post_ids ) ? NULL : 'uprs_post_ID IN ( '.implode( ',', $post_ids ).' )';

	// SELECT current User's post and comment read statuses for all post with the given ids:
	$SQL = new SQL( 'Load all read post date statuses for user #'.$current_User->ID );
	$SQL->SELECT( 'uprs_post_ID, uprs_read_post_ts' );
	$SQL->FROM( 'T_users__postreadstatus' );
	$SQL->WHERE( 'uprs_user_ID = '.$DB->quote( $current_User->ID ) );
	$SQL->WHERE_and( $post_condition );
	// Set those post read statuses which were opened before:
	$user_post_read_statuses = $DB->get_assoc( $SQL->get(), $SQL->title );

	if( empty( $post_ids ) )
	{	// The load was not requested for specific posts, so we have loaded all information what we have, ther rest of the posts were not read by this user:
		return;
	}

	// Set new posts read statuses:
	foreach( $post_ids as $post_ID )
	{	// Make sure to set read statuses for each requested post ID:
		if( ! isset( $user_post_read_statuses[ $post_ID ] ) )
		{	// Set each read status to 0:
			$user_post_read_statuses[ $post_ID ] = 0;
		}
	}
}


/**
 * Initialize Results object for items list
 *
 * @param object Results
 * @param array Params
 */
function items_results( & $items_Results, $params = array() )
{
	global $Blog;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'tab'                        => '',
			'field_prefix'               => '',
			'display_date'               => true,
			'display_permalink'          => true,
			'display_blog'               => true,
			'display_author'             => true,
			'display_type'               => true,
			'display_title'              => true,
			'display_title_flag'         => true,
			'display_title_status'       => true,
			'display_visibility_actions' => true,
			'display_status'             => true,
			'display_ord'                => true,
			'display_status'             => true,
			'display_history'            => true,
			'display_actions'            => true,
		), $params );

	if( $params['display_date'] )
	{	// Display Date column
		$td = '<span class="date">@get_issue_date()@</span>';
		if( $params['display_permalink'] )
		{
			$td = '@get_permanent_link( get_icon(\'permalink\'), \'\', \'\', \'auto\' )@ '.$td;
		}
		$items_Results->cols[] = array(
				'th' => T_('Date'),
				'order' => $params['field_prefix'].'datestart',
				'default_dir' => 'D',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => $td,
			);
	}

	if( $params['display_blog'] )
	{	// Display Blog column
		if( !empty( $Blog ) && $Blog->get_setting( 'aggregate_coll_IDs' ) )
		{ // Aggregated blog: display name of blog
			$items_Results->cols[] = array(
					'th' => T_('Collection'),
					'th_class' => 'nowrap',
					'td_class' => 'nowrap',
					'td' => '@load_Blog()@<a href="~regenerate_url( \'blog,results_order\', \'blog=@blog_ID@\' )~">@Blog->dget(\'shortname\')@</a>',
				);
		}
	}

	if( $params['display_author'] )
	{ // Display Author column:
		$items_Results->cols[] = array(
				'th' => T_('Author'),
				'th_class' => 'nowrap',
				'td_class' => 'nowrap',
				'order' => $params['field_prefix'].'creator_user_ID',
				'td' => '%get_user_identity_link( NULL, #post_creator_user_ID# )%',
			);
	}

	if( $params['display_type'] )
	{ // Display Type column:
		$items_Results->cols[] = array(
				'th' => T_('Type'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'order' => $params['field_prefix'].'ityp_ID',
				'td' => '%item_row_type( {Obj} )%',
			);
	}

	if( $params['display_title'] )
	{ // Display Title column
		$items_Results->cols[] = array(
				'th' => T_('Title'),
				'order' => $params['field_prefix'].'title',
				'td_class' => 'tskst_$post_pst_ID$',
				'td' => '<strong lang="@get(\'locale\')@">%task_title_link( {Obj}, '.(int)$params['display_title_flag'].' )%</strong>',
			);
	}

	if( $params['display_status'] )
	{ // Display Ord column
		$items_Results->cols[] = array(
				'th' => T_('Status'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap left',
				'order' => $params['field_prefix'].'status',
				'td' => '%item_row_status( {Obj}, {CUR_IDX} )%',
			);
	}

	if( $params['display_ord'] )
	{ // Display Ord column
		$items_Results->cols[] = array(
				'th' => T_('Ord'),
				'order' => $params['field_prefix'].'order',
				'td_class' => 'right',
				'td' => '$post_order$',
			);
	}

	if( $params['display_history'] )
	{ // Display History (i) column
		$items_Results->cols[] = array(
				'th' => /* TRANS: abbrev for info */ T_('i'),
				'th_title' => T_('Item history information'),
				'order' => $params['field_prefix'].'datemodified',
				'default_dir' => 'D',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '@get_history_link()@',
			);
	}

	if( $params['display_actions'] )
	{ // Display Actions column
		$items_Results->cols[] = array(
				'th' => T_('Actions'),
				'td_class' => 'shrinkwrap',
				'td' => '%item_edit_actions( {Obj} )%',
			);
	}
}


/**
 * Generate global icons depending on seleted tab with item type
 */
function item_type_global_icons( $object_Widget )
{
	global $current_User, $admin_url, $DB, $Blog;

	if( is_logged_in() && ! empty( $Blog ) && $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
	{ // We have permission to add a post with at least one status:
		$tab_type = ( get_param( 'tab' ) == 'type' ) ? get_param( 'tab_type' ) : '';

		$item_types_SQL = new SQL();
		$item_types_SQL->SELECT( 'ityp_ID AS ID, ityp_name AS name, ityp_perm_level AS perm_level,
			IF( ityp_ID = "'.$Blog->get_setting( 'default_post_type' ).'", 0, 1 ) AS fix_order' );
		$item_types_SQL->FROM( 'T_items__type' );
		$item_types_SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$Blog->ID );
		if( ! empty( $tab_type ) )
		{ // Get item types only by selected back-office tab
			$item_types_SQL->WHERE( 'ityp_usage IN ( '.$DB->quote( get_item_type_usage_by_tab( $tab_type ) ).' )' );
		}
		$item_types_SQL->ORDER_BY( 'fix_order, ityp_ID' );
		$item_types = $DB->get_results( $item_types_SQL->get() );

		$count_item_types = count( $item_types );
		if( $count_item_types > 0 )
		{
			if( $count_item_types > 1 )
			{ // Group only if moer than one item type for selected back-office tab
				$icon_group_create_type = 'type_create';
				$icon_group_create_mass = 'mass_create';
			}
			else
			{ // No group
				$icon_group_create_type = NULL;
				$icon_group_create_mass = NULL;
			}

			$object_Widget->global_icon( T_('Mass edit the current post list...'), 'edit', $admin_url.'?ctrl=items&amp;action=mass_edit&amp;filter=restore&amp;blog='.$Blog->ID.'&amp;redirect_to='.regenerate_url( 'action', '', '', '&' ), T_('Mass edit'), 3, 4 );

			foreach( $item_types as $item_type )
			{
				if( $current_User->check_perm( 'blog_item_type_'.$item_type->perm_level, 'edit', false, $Blog->ID ) )
				{ // We have the permission to create posts with this post type:
					$object_Widget->global_icon( T_('Create multiple posts...'), 'new', $admin_url.'?ctrl=items&amp;action=new_mass&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$item_type->ID, ' '.sprintf( T_('Mass create "%s"'), $item_type->name ), 3, 4, array( 'class' => 'action_icon btn-default' ), $icon_group_create_mass );
					$object_Widget->global_icon( T_('Write a new post...'), 'new', $admin_url.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$item_type->ID, ' '.$item_type->name, 3, 4, array( 'class' => 'action_icon btn-primary' ), $icon_group_create_type );
				}
			}
		}
	}
}


/**
 * Callback to add filters on top of the items list
 *
 * @param Form
 */
function callback_filter_item_list_table( & $Form )
{
	global $ItemList;

	// --------------------------------- START OF CURRENT FILTERS --------------------------------
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_current_filters',
			// Optional display params
			'ItemList'             => $ItemList,
			'block_start'          => '<div class="filters_item_list_table">',
			'block_end'            => '</div>',
			'block_title_start'    => '<b>',
			'block_title_end'      => ':</b> ',
			'show_filters'         => array( 'time' => 1 ),
			'display_button_reset' => false,
			'display_empty_filter' => true,
		) );
	// ---------------------------------- END OF CURRENT FILTERS ---------------------------------
}


/**
 * Helper functions to display Items results.
 * New ( not display helper ) functions must be created above item_results function
 */

/**
 * Get a link with task title
 *
 * @param object Item
 * @param boolean Display country flag
 * @param boolean Display status banner
 * @return string Link
 */
function task_title_link( $Item, $display_flag = true, $display_status = false )
{
	global $current_User, $admin_url;

	$col = '';
	if( $display_status && is_logged_in() )
	{ // Display status
		$col .= $Item->get_format_status( array(
				'template' => '<div class="pull-right"><span class="note status_$status$"><span>$status_title$</span></span></div>',
			) );
	}

	if( $display_flag )
	{ // Display country flag
		$col .= locale_flag( $Item->locale, 'w16px', 'flag', '', false ).' ';
	}

	$Item->get_Blog();

	if( is_admin_page() )
	{ // Url to item page in backoffice
		$item_url = $admin_url.'?ctrl=items&amp;blog='.$Item->get_blog_ID().'&amp;p='.$Item->ID;
	}
	else
	{ // Url to item page in frontoffice
		$item_url = $Item->get_permanent_url();
	}

	if( $Item->Blog->get_setting( 'allow_comments' ) != 'never' )
	{ // The current blog can have comments:
		$nb_comments = generic_ctp_number( $Item->ID, 'feedback', 'total' );
		$comments_url = is_admin_page() ? $item_url : url_add_tail( $item_url, '#comments' );
		$col .= '<a href="'.$comments_url.'" title="'.sprintf( T_('%d feedbacks'), $nb_comments ).'">';
		if( $nb_comments )
		{
			$comments_icon_params = array();
			$comment_moderation_statuses = $Item->Blog->get_setting( 'moderation_statuses' );
			if( ! empty( $comment_moderation_statuses ) )
			{	// Get a count of comments awaiting moderation:
				$nb_comments_moderation = generic_ctp_number( $Item->ID, 'feedback', explode( ',', $comment_moderation_statuses ) );
				if( $nb_comments_moderation > 0 )
				{
					$comments_icon_params['style'] = 'color:#cc0099';
					$comments_icon_params['title'] = T_('There are come comments awaiting moderation.');
				}
			}

			$col .= get_icon( 'comments', 'imgtag', $comments_icon_params );
		}
		else
		{
			$col .= get_icon( 'nocomment' );
		}
		$col .= '</a> ';
	}

	if( $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
	{	// Display icon of meta comments Only if current user can views meta comments:
		$metas_count = generic_ctp_number( $Item->ID, 'metas', 'total' );
		if( $metas_count > 0 )
		{	// If at least one meta comment exists
			$item_Blog = & $Item->get_Blog();
			$col .= '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$item_Blog->ID.'&amp;p='.$Item->ID.'&amp;comment_type=meta#comments">'
					.get_icon( 'comments', 'imgtag', array( 'style' => 'color:#F00', 'title' => T_('Meta comments') ) )
				.'</a> ';
		}
	}

	$col .= '<a href="'.$item_url.'" class="" title="'.
								T_('View this post...').'">'.$Item->dget( 'title' ).'</a></strong>';

	return $col;
}

/**
 * Get item type title with link to change this
 *
 * @param object Item
 * @return string
 */
function item_row_type( $Item )
{
	$type_edit_url = $Item->get_type_edit_link( 'url' );
	$type_title = $Item->get_type_setting( 'name' );

	if( empty( $type_edit_url ) )
	{ // No perm to edit post type
		return $type_title;
	}
	else
	{ // Display a link to quick change type
		return '<a href="'.$type_edit_url.'&amp;from_tab=type">'.$type_title.'</a>';
	}
}


/**
 * Get buttons to change item type
 *
 * @param object Item
 * @param integer Index of the row on page
 * @return string
 */
function item_row_status( $Item, $index )
{
	global $current_User, $AdminUI, $Blog, $admin_url;

	if( empty( $Blog ) )
	{ // global Blog object is not set, e.g. back-office User activity tab
		$Item->load_Blog();
		$blog_ID = $Item->Blog->ID;
	}
	else
	{
		$blog_ID = $Blog->ID;
	}

	// Get those statuses which are not allowed for the current User to create posts in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $blog_ID, 'blog_post!', 'create', $Item->status ), array( 'trash' ) );
	// Get allowed visibility statuses
	$status_options = get_visibility_statuses( '', $exclude_statuses );

	if( is_logged_in() && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) &&
	    isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' )
	{ // Use dropdown for bootstrap skin and if current user can edit this post
		$status_icon_options = get_visibility_statuses( 'icons', $exclude_statuses );
		$r = '<div class="btn-group '.( $index > 5 ? 'dropup' : 'dropdown' ).' post_status_dropdown">'
				.'<button type="button" class="btn btn-sm btn-status-'.$Item->status.' dropdown-toggle" data-toggle="dropdown" aria-expanded="false" id="post_status_dropdown">'
						.'<span>'.$status_options[ $Item->status ].'</span>'
					.' <span class="caret"></span></button>'
				.'<ul class="dropdown-menu" role="menu" aria-labelledby="post_status_dropdown">';
		foreach( $status_options as $status_key => $status_title )
		{
			$r .= '<li rel="'.$status_key.'" role="presentation"><a href="'
					.$admin_url.'?ctrl=items&amp;blog='.$blog_ID.'&amp;action=update_status&amp;post_ID='.$Item->ID.'&amp;status='.$status_key.'&amp;'.url_crumb( 'item' )
					.'" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.$status_title.'</span></a></li>';
		}
		$r .= '</ul>'
			.'</div>';
	}
	else
	{ // Display only status badge when user has no permission to edit this post and for non-bootstrap skin
		$r = $Item->get_format_status( array(
			'template' => '<span class="note status_$status$"><span>$status_title$</span></span>',
		) );
	}

	return $r;
}

/**
 * Edit Actions:
 *
 * @param Item
 */
function item_edit_actions( $Item )
{
	$r = '';

	// Display edit button if current user has the rights:
	$r .= $Item->get_edit_link( array(
		'before' => ' ',
		'after' => ' ',
		'text' => get_icon( 'edit' ),
		'title' => '#',
		'class' => '' ) );

	// Display duplicate button if current user has the rights:
	$r .= $Item->get_copy_link( array(
		'before' => ' ',
		'after' => ' ',
		'text' => get_icon( 'copy', 'imgtag', array( 'title' => T_('Duplicate this post...') ) ),
		'title' => '#',
		'class' => '' ) );

	// Display delete button if current user has the rights:
	$r .= $Item->get_delete_link( ' ', ' ', get_icon( 'delete' ), '#', '', false, '#', '#', regenerate_url( '', '', '', '&' ) );

	return $r;
}


/**
 * Display chapters list
 *
 * @param array Params
 */
function manual_display_chapters( $params = array() )
{
	global $Blog, $blog, $cat_ID;

	if( empty( $Blog ) && !empty( $blog ) )
	{ // Set Blog if it still doesn't exist
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog, false );
	}

	if( empty( $Blog ) )
	{ // No Blog, Exit here
		return;
	}

	$ChapterCache = & get_ChapterCache();

	$chapter_path = array();
	if( !empty( $cat_ID ) )
	{ // A category is opened
		$chapter_path = $ChapterCache->get_chapter_path( $Blog->ID, $cat_ID );
	}

	$callbacks = array(
		'line'   => 'manual_display_chapter_row',
		'posts'  => 'manual_display_post_row',
	);

	$params = array_merge( array(
			'sorted'       => true,
			'expand_all'   => false,
			'chapter_path' => $chapter_path,
		), $params );

	$ChapterCache->recurse( $callbacks, $Blog->ID, NULL, 0, 0, $params );
}


/**
 * Display chapter row
 *
 * @param object Chapter
 * @param integer Level of the category in the recursive tree
 * @param boolean TRUE - if category is opened
 */
function manual_display_chapter_row( $Chapter, $level, $params = array() )
{
	global $line_class, $current_User, $Settings;
	global $admin_url;
	global $Session;

	$params = array_merge( array(
			'is_opened' => false
		), $params );

	$result_fadeout = $Session->get( 'fadeout_array' );

	$line_class = $line_class == 'even' ? 'odd' : 'even';

	$perm_edit = $current_User->check_perm( 'blog_cats', '', false, $Chapter->blog_ID );
	$perm_create_item = $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Chapter->blog_ID );

	// Redirect to manual pages after adding/editing chapter
	$redirect_page = '&amp;redirect_page=manual';

	$r = '<tr id="cat-'.$Chapter->ID.'" class="'.$line_class.( isset( $result_fadeout ) && in_array( $Chapter->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">';

	$open_url = $admin_url.'?ctrl=items&amp;tab=manual&amp;blog='.$Chapter->blog_ID;
	// Name
	if( $params['is_opened'] )
	{ // Chapter is expanded
		$cat_icon = get_icon( 'filters_hide' );
		if( $parent_Chapter = & $Chapter->get_parent_Chapter() )
		{
			$open_url .= '&amp;cat_ID='.$parent_Chapter->ID;
		}
	}
	else
	{ // Chapter is collapsed
		$cat_icon = get_icon( 'filters_show' );
		$open_url .= '&amp;cat_ID='.$Chapter->ID;
	}
	$r .= '<td class="firstcol">'
					.'<strong style="padding-left: '.($level).'em;">'
						.'<a href="'.$open_url.'">'.$cat_icon.' '.$Chapter->dget('name').'</a> ';
	if( $perm_edit )
	{ // Current user can edit the chapters of the blog
		$edit_url = $admin_url.'?ctrl=chapters&amp;blog='.$Chapter->blog_ID.'&amp;cat_ID='.$Chapter->ID.'&amp;action=edit'.$redirect_page;
		$r .= action_icon( T_('Edit...'), 'edit', $edit_url );
	}
	$r .= '</strong></td>';

	// URL "slug"
	$r .= '<td><a href="'.htmlspecialchars($Chapter->get_permanent_url()).'">'.$Chapter->dget('urlname').'</a></td>';

	// Order
	$order_attrs = '';// ' style="padding-left:'.( ( $level * 10 ) + 5 ).'px"';
	$order_value = T_('Alphabetic');
	if( $Chapter->get_parent_subcat_ordering() == 'manual' )
	{ // Parent chapter ordering is set to manual and not alphabetic
		if( $perm_edit )
		{ // Add availability to edit an order if current user can edit chapters
			$order_attrs .= ' id="order-chapter-'.$Chapter->ID.'" title="'.format_to_output( T_('Click to change an order'), 'htmlattr' ).'"';
		}
		$order_value = $Chapter->dget('order');
	}
	$r .= '<td'.$order_attrs.'><span style="padding-left:'.$level.'em">'.$order_value.'</span></td>';

	// Actions
	$r .= '<td class="lastcol shrinkwrap">';
	if( $perm_edit || $perm_create_item )
	{ // Current user can edit the chapters of the blog or can create item in the blog
		if( $perm_edit )
		{ // Create/Edit chapter, Move to another blog
			$r .= action_icon( T_('Edit...'), 'edit', $edit_url );
			if( $Settings->get('allow_moving_chapters') )
			{ // If moving cats between blogs is allowed:
				$r .= action_icon( T_('Move to a different blog...'), 'file_move', $admin_url.'?ctrl=chapters&amp;blog='.$Chapter->blog_ID.'&amp;cat_ID='.$Chapter->ID.'&amp;action=move', T_('Move') );
			}
			$r .= action_icon( T_('New chapter...'), 'add', $admin_url.'?ctrl=chapters&amp;blog='.$Chapter->blog_ID.'&amp;cat_parent_ID='.$Chapter->ID.'&amp;action=new'.$redirect_page );
		}
		if( $perm_create_item )
		{ // Create new item
			$redirect_to = '&amp;redirect_to='.urlencode( $admin_url.'?ctrl=items&tab=manual&cat_ID='.$Chapter->ID );
			$r .= action_icon( T_('New manual page...'), 'new', $admin_url.'?ctrl=items&action=new&blog='.$Chapter->blog_ID.'&amp;cat='.$Chapter->ID.$redirect_to, NULL, NULL, NULL, array(), array( 'style' => 'width:12px' ) );
		}
		if( $perm_edit )
		{ // Delete chapter
			$r .= action_icon( T_('Delete chapter...'), 'delete', $admin_url.'?ctrl=chapters&amp;blog='.$Chapter->blog_ID.'&amp;cat_ID='.$Chapter->ID.'&amp;action=delete&amp;'.url_crumb('element').$redirect_page );
		}
	}
	else
	{
		$r .= '&nbsp;';
	}
	$r .= '</td>';

	$r .= '</tr>';

	echo $r;
}


/**
 * Display item row
 *
 * @param object Item
 * @param integer Level of the category in the recursive tree
 * @param array Params
 */
function manual_display_post_row( $Item, $level, $params = array() )
{
	global $line_class, $current_User, $Settings;
	global $admin_url;
	global $Session;

	$result_fadeout = $Session->get( 'fadeout_array' );

	$params = array_merge( array(
			'title_before' => '',
			'title_after'  => '',
			'title_field'  => 'urltitle',
		), $params );

	if( $params['chapter_ID'] != $Item->main_cat_ID )
	{	// Posts from extracats are displayed with italic:
		$params['title_before'] = '<i>';
		$params['title_after'] = '</i>';
	}

	$line_class = $line_class == 'even' ? 'odd' : 'even';

	$r = '<tr id="item-'.$Item->ID.'" class="'.$line_class.( isset( $result_fadeout ) && in_array( 'item-'.$Item->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">';

	// Title
	$edit_url = $Item->ID;
	$item_icon = get_icon( 'file_message', 'imgtag', array( 'title' => '' ) );
	$item_edit_url = $Item->get_edit_url();
	$r .= '<td class="firstcol"><strong style="padding-left: '.($level).'em;">';
	if( !empty( $item_edit_url ) )
	{ // If current user can edit this item
		$r .= '<a href="'.$Item->get_edit_url().'" title="'.T_('Edit...').'">';
	}
	else
	{
		$r .= '';
	}
	$r .= $params['title_before']
			.$item_icon.' '
			.$Item->dget('title')
			.$params['title_after'];
	$r .= !empty( $item_edit_url ) ? '</a>' : '';
	$r .= '</strong></td>';

	// URL "slug"
	$edit_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Item->ID.'&amp;action=edit' );
	$r .= '<td>'.$Item->get_title( $params );
	if( $current_User->check_perm( 'slugs', 'view', false ) )
	{ // Display icon to view all slugs of this item if current user has permission
		$r .= ' '.action_icon( T_('Edit slugs...'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$Item->ID );
	}
	$r .= '</td>';

	// Order
	$order_attrs = '';// ' style="padding-left:'.( ( $level * 10 ) + 5 ).'px"';
	$order_value = T_('Alphabetic');
	if( isset( $params['cat_order'] ) && $params['cat_order'] == 'manual' )
	{
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
		{ // Add availability to edit an order if current user can edit this item
			$order_attrs .= ' id="order-item-'.$Item->ID.'" title="'.format_to_output( T_('Click to change an order'), 'htmlattr' ).'"';
		}
		$order_value = $Item->dget('order');
	}
	$r .= '<td'.$order_attrs.'><span style="padding-left:'.$level.'em">'.$order_value.'</span></td>';

	// Actions
	$r .= '<td class="lastcol shrinkwrap">'.item_edit_actions( $Item ).'</td>';

	$r .= '</tr>';

	echo $r;
}


/**
 * Get title of the item/task cell by field type
 *
 * @param string Type of the field: 'priority', 'status', 'assigned'
 * @param object Item
 * @param integer Priority
 * @return string
 */
function item_td_task_cell( $type, $Item, $editable = true )
{
	global $current_User;

	switch( $type )
	{
		case 'priority':
			$value = $Item->priority;
			$title = item_priority_title( $Item->priority );
			break;

		case 'status':
			$value = '_'.$Item->pst_ID; // The char '_' is used to don't break a sorting by name on jeditable
			$title = $Item->get( 't_extra_status' );
			if( empty( $title ) )
			{
				$title = T_('No status');
			}
			break;

		case 'assigned':
			$value = $Item->assigned_user_ID;
			if( empty( $value ) )
			{
				$title = T_('No user');
			}
			else
			{
				$UserCache = & get_UserCache();
				$User = & $UserCache->get_by_ID( $Item->assigned_user_ID );
				$title = $User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) );
			}
			break;

		default:
			$value = 0;
			$title = '';
	}

	if( $current_User && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) && $editable )
	{ // Current user can edit this item
		return '<a href="#" rel="'.$value.'">'.$title.'</a>';
	}
	else
	{ // No perms to edit item, Display only a title
		return $title;
	}
}


/**
 * Get a <td> class of a cell
 *
 * @param integer Post ID
 * @param integer $post_pst_ID
 * @param string Class name to make this cell editable
 * @return string
 */
function item_td_task_class( $post_ID, $post_pst_ID, $editable_class )
{
	global $current_User;

	$ItemCache = & get_ItemCache();
	$Item = & $ItemCache->get_by_ID( $post_ID );

	$class = 'center nowrap tskst_'.$post_pst_ID;
	if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
	{ // Current user can edit this item, Add a class to edit a priority by click from view list
		$class .= ' '.$editable_class;
	}

	return $class;
}

/**
 * End of helper functions block to display Items results.
 * New ( not display helper ) functions must be created above items_results function.
 */

?>