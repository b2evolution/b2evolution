<?php
/**
 * This file implements the UI controller for managing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @todo dh> AFAICS there are three params used for "item ID": "p", "post_ID"
 *       and "item_ID". This should get cleaned up.
 *       Side effect: "post_ID required" error if you switch tabs (expert/simple),
 *       after an error is display (e.g. entering an invalid issue time).
 *       (related to $tab_switch_params)
 * fp> Yes, it's a mess...
 *     Ironically the correct name would be itm_ID (which is what the DB uses,
 *     except for the Items table which should actually also use itm_ prefixes instead of post_
 *     ... a lot of history lead to this :p
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var UserSettings
 */
global $UserSettings;

/**
 * @var User
 */
global $current_User;

/**
 * @var Blog
 */
global $Blog;

global $dispatcher;

$action = param_action( 'list' );
$orig_action = $action; // Used to know what action is called really

$AdminUI->set_path( 'collections', 'posts' );	// Sublevel may be attached below

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

/*
 * Init the objects we want to work on.
 *
 * Autoselect a blog where we have PERMISSION to browse (preferably the last used blog):
 * Note: for some actions, we'll get the blog from the post ID
 */

$mass_create = param( 'mass_create', 'integer' );
if( $action == 'new_switchtab' && !empty( $mass_create ) )
{	// Replace action with mass create action
	$action = 'new_mass';
}

// for post from files
if( $action == 'group_action' )
{ // Get the real action from the select:
	$action = param( 'group_action', 'string', '' );
}


switch( $action )
{
	case 'edit':
	case 'history':
	case 'history_details':
	case 'history_compare':
	case 'history_restore':
		param( 'p', 'integer', true, true );
		param( 'restore', 'integer', 0 );

		if( $restore )
		{ // Restore a post from Session
			$edited_Item = get_session_Item( $p );
		}

		if( empty( $edited_Item ) )
		{ // Load post to edit from DB:
			$ItemCache = & get_ItemCache();
			$edited_Item = & $ItemCache->get_by_ID( $p );
		}

		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );
		break;

	case 'mass_edit':
		break;

	case 'update_edit':
	case 'update':
	case 'update_publish':
	case 'update_status':
	case 'publish':
	case 'publish_now':
	case 'restrict':
	case 'deprecate':
	case 'delete':
	// Note: we need to *not* use $p in the cases above or it will conflict with the list display
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'edit_type': // this gets set as action by JS, when we switch tabs
	case 'extract_tags':
		if( $action != 'edit_switchtab' && $action != 'edit_type' )
		{ // Stop a request from the blocked IP addresses or Domains
			antispam_block_request();
		}

		param( 'post_ID', 'integer', true, true );

		if( $action == 'edit_type' )
		{ // Load post from Session
			$edited_Item = get_session_Item( $post_ID );

			// Memorize action for list of post types
			memorize_param( 'action', 'string', '', $action );

			// Use this param to know how to redirect after item type updating
			param( 'from_tab', 'string', '' );
		}

		if( empty( $edited_Item ) )
		{ // Load post to edit from DB:
			$ItemCache = & get_ItemCache();
			$edited_Item = & $ItemCache->get_by_ID( $post_ID );
		}

		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );

		// What form button has been pressed?
		param( 'save', 'string', '' );
		$exit_after_save = ( $action != 'update_edit' && $action != 'extract_tags' );
		break;

	case 'update_type':
		break;

	case 'mass_save' :
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog=' . $Blog->ID, '&' ) );
		break;

	case 'new':
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'new_mass':
	case 'new_type':
	case 'copy':
	case 'create_edit':
	case 'create_link':
	case 'create':
	case 'create_publish':
	case 'list':
		if( in_array( $action, array( 'create_edit', 'create_link', 'create', 'create_publish' ) ) )
		{ // Stop a request from the blocked IP addresses or Domains
			antispam_block_request();
		}

		if( $action == 'list' )
		{	// We only need view permission
			$selected = autoselect_blog( 'blog_ismember', 'view' );
		}
		else
		{	// We need posting permission
			$selected = autoselect_blog( 'blog_post_statuses', 'edit' );
		}

		if( ! $selected  )
		{ // No blog could be selected
			$Messages->add( T_('Sorry, you have no permission to post yet.'), 'error' );
			$action = 'nil';
		}
		else
		{
			if( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
			{	// Selected a new blog:
				$BlogCache = & get_BlogCache();
				$Blog = & $BlogCache->get_by_ID( $blog );
			}

			// Where are we going to redirect to?
			param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID, '&' ) );

			// What form buttton has been pressed?
			param( 'save', 'string', '' );
			$exit_after_save = ( $action != 'create_edit' && $action != 'create_link' );
		}
		break;

	case 'make_posts_pre':
		// form for edit several posts

		if( empty( $Blog ) )
		{
			$Messages->add( T_('No destination blog is selected.'), 'error' );
			break;
		}

		// Check perms:
		$current_User->check_perm( 'blog_post_statuses', 'edit', true, $Blog->ID );
		break;

	case 'make_posts_from_files':
		// Make posts with selected images:

		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		$FileRootCache = & get_FileRootCache();
		// getting root
		$root = param( 'root' );

		$fm_FileRoot = & $FileRootCache->get_by_ID( $root, true );

		// fp> TODO: this block should move to a general level
		// Try to go to the right blog:
		if( $fm_FileRoot->type == 'collection' )
		{
			set_working_blog( $fm_FileRoot->in_type_ID );
			// Load the blog we're in:
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		// ---

		if( empty( $Blog ) )
		{
			$Messages->add( T_('No destination blog is selected.'), 'error' );
			break;
		}

		// Get status (includes PERM CHECK):
		$item_status = param( 'post_status', 'string', $Blog->get_allowed_item_status() );
		$current_User->check_perm( 'blog_post!'.$item_status, 'create', true, $Blog->ID );

		load_class( 'files/model/_filelist.class.php', 'FileList' );
		$selected_Filelist = new Filelist( $fm_FileRoot, false );
		$fm_selected = param( "fm_selected" , "array" );
		foreach( $fm_selected as $l_source_path )
		{
			$selected_Filelist->add_by_subpath( urldecode($l_source_path), true );
		}
		// make sure we have loaded metas for all files in selection!
		$selected_Filelist->load_meta();

		// Ready to create post(s):
		load_class( 'items/model/_item.class.php', 'Item' );

		$fileNum = 0;
		$cat_Array = param( 'category', 'array:string' );
		$title_Array = param( 'post_title', 'array:string' );
		$new_categories = param( 'new_categories', 'array:string', array() );
		while( $l_File = & $selected_Filelist->get_next() )
		{
			// Create a post:
			$edited_Item = new Item();
			$edited_Item->set( 'status', $item_status );

			// replacing category if selected at preview screen
			if( isset( $cat_Array[ $fileNum ] ) )
			{
				// checking if selected "same as above" category option
				switch( $cat_Array[ $fileNum ] )
				{
					case 'same':
						// Get a category ID from previous item
						$cat_Array[ $fileNum ] = $cat_Array[ $fileNum - 1 ];
						break;

					case 'new':
						// Create a new category from an entered name

						// Check permissions:
						$current_User->check_perm( 'blog_cats', '', true, $blog );

						$ChapterCache = & get_ChapterCache();
						$new_Chapter = & $ChapterCache->new_obj( NULL, $blog );	// create new category object
						$new_Chapter->set( 'name', $new_categories[ $fileNum ] );
						if( $new_Chapter->dbinsert() !== false )
						{ // Category is created successfully
							$Messages->add( sprintf( T_('New category %s created.'), '<b>'.$new_categories[ $fileNum ].'</b>' ), 'success' );
							$ChapterCache->clear();
						}
						else
						{ // Error on creating new category
							$Messages->add( sprintf( T_('New category %s creation failed.'), '<b>'.$new_categories[ $fileNum ].'</b>' ), 'error' );
							continue; // Skip this post
						}
						$cat_Array[ $fileNum ] = $new_Chapter->ID;
						break;
				}
				$edited_Item->set( 'main_cat_ID', intval( $cat_Array[ $fileNum ] ) );
			}
			else
			{ // Use default category ID if it was not selected on the form
				$edited_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );
			}

			$title = $l_File->get('title');
			if( empty( $title ) )
			{
				$title = $l_File->get('name');
			}

			$edited_Item->set( 'title', $title );

			// replacing category if selected at preview screen
			if( isset( $title_Array[ $fileNum ] ) ) {
				$edited_Item->set( 'title', $title_Array[ $fileNum ] );
			}

			$DB->begin( 'SERIALIZABLE' );
			// INSERT NEW POST INTO DB:
			if( $edited_Item->dbinsert() )
			{
				// echo '<br>file meta: '.$l_File->meta;
				if( $l_File->meta == 'notfound' )
				{ // That file has no meta data yet, create it now!
					$l_File->dbsave();
				}

				// Let's make the link!
				$edited_Link = new Link();
				$edited_Link->set( 'itm_ID', $edited_Item->ID );
				$edited_Link->set( 'file_ID', $l_File->ID );
				$edited_Link->set( 'position', 'teaser' );
				$edited_Link->set( 'order', 1 );
				$edited_Link->dbinsert();

				$DB->commit();

				// Invalidate blog's media BlockCache
				BlockCache::invalidate_key( 'media_coll_ID', $edited_Item->get_blog_ID() );

				$Messages->add( sprintf( T_('&laquo;%s&raquo; has been posted.'), $l_File->dget('name') ), 'success' );
				$fileNum++;
			}
			else
			{
				$DB->rollback();
				$Messages->add( sprintf( T_('&laquo;%s&raquo; couldn\'t be posted.'), $l_File->dget('name') ), 'error' );
			}
		}

		// Note: we redirect without restoring filter. This should allow to see the new files.
		// &filter=restore
		header_redirect( $dispatcher.'?ctrl=items&blog='.$blog );	// Will save $Messages

		// Note: we should have EXITED here. In case we don't (error, or sth...)

		// Reset stuff so it doesn't interfere with upcomming display
		unset( $edited_Item );
		unset( $edited_Link );
		$selected_Filelist = new Filelist( $fm_Filelist->get_FileRoot(), false );
		break;

	case 'hide_quick_button':
	case 'show_quick_button':
		// Show/Hide quick button to publish a post

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Update setting:
		$UserSettings->set( 'show_quick_publish_'.$blog, ( $action == 'show_quick_button' ? 1 : 0 ) );
		$UserSettings->dbupdate();

		$prev_action = param( 'prev_action', 'string', '' );
		$item_ID = param( 'p', 'integer', 0 );

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=items&action='.$prev_action.( $item_ID > 0 ? '&p='.$item_ID : '' ).'&blog='.$blog );
		break;

	case 'reset_quick_settings':
		// Reset quick default settings for current user on the edit item screen:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Reset settings to default values
		$DB->query( 'DELETE FROM T_users__usersettings
			WHERE uset_name LIKE "fold_itemform_%_'.$blog.'"
			   OR uset_name = "show_quick_publish_'.$blog.'"' );

		$prev_action = param( 'prev_action', 'string', '' );
		$item_ID = param( 'p', 'integer', 0 );

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=items&action='.$prev_action.( $item_ID > 0 ? '&p='.$item_ID : '' ).'&blog='.$blog );
		break;

	default:
		debug_die( 'unhandled action 1:'.htmlspecialchars($action) );
}

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Posts'), $admin_url.'?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );

/**
 * Perform action:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'new':
	case 'new_mass':
		// $set_issue_date = 'now';
		$item_issue_date = date_i18n( locale_datefmt(), $localtimenow );
		$item_issue_time = date( 'H:i:s', $localtimenow );
		// pre_dump( $item_issue_date, $item_issue_time );
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'new_type': // this gets set as action by JS, when we switch tabs
		// New post form  (can be a bookmarklet form if mode == bookmarklet )

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			break;
		}

		// Used when we change a type of the duplicated item:
		$duplicated_item_ID = param( 'p', 'integer', NULL );

		if( in_array( $action, array( 'new', 'new_type' ) ) )
		{
			param( 'restore', 'integer', 0 );
			if( $restore || $action == 'new_type' )
			{ // Load post from Session
				$edited_Item = get_session_Item( 0 );

				// Memorize action for list of post types
				memorize_param( 'action', 'string', '', $action );
			}
		}

		load_class( 'items/model/_item.class.php', 'Item' );
		if( empty( $edited_Item ) )
		{ // Create new Item object
			$edited_Item = new Item();
		}

		$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		// Also used by bookmarklet
		$edited_Item->load_from_Request( true ); // needs Blog set

		// Set default locations from current user
		$edited_Item->set_creator_location( 'country' );
		$edited_Item->set_creator_location( 'region' );
		$edited_Item->set_creator_location( 'subregion' );
		$edited_Item->set_creator_location( 'city' );

		$edited_Item->status = param( 'post_status', 'string', NULL );		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status );

		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave ( $post_category, $post_extracats );

		$edited_Item->set ( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array:integer', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Item type ID:
		param( 'item_typ_ID', 'integer', 1 );

		// Initialize a page title depending on item type:
		if( empty( $item_typ_ID ) )
		{	// No selected item type, use default:
			$title = T_('New post');
		}
		else
		{	// Get item type to set a pge title:
			$ItemTypeCache = & get_ItemTypeCache();
			$ItemType = & $ItemTypeCache->get_by_ID( $item_typ_ID );
			$title = sprintf( T_('New %s'), $ItemType->get_name() );
		}

		$AdminUI->breadcrumbpath_add( $title, '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$item_typ_ID );

		$AdminUI->title_titlearea = $title.': ';;

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;

		if( $action == 'new_type' )
		{	// Save the changes of Item to Session:
			set_session_Item( $edited_Item );
			// Initialize original item ID that is used on diplicating action:
			param( 'p', 'integer', NULL );
		}
		break;


	case 'copy': // Duplicate post
		$item_ID = param( 'p', 'integer', true );
		$ItemCache = &get_ItemCache();
		$edited_Item = & $ItemCache->get_by_ID( $item_ID );

		// Load tags of the duplicated item:
		$item_tags = implode( ', ', $edited_Item->get_tags() );

		// Load all settings of the duplicating item and copy them to new item:
		$edited_Item->load_ItemSettings();
		$edited_Item->ItemSettings->_load( $edited_Item->ID, NULL );
		$edited_Item->ItemSettings->cache[0] = $edited_Item->ItemSettings->cache[ $edited_Item->ID ];

		// Set ID of copied post to 0, because some functions can update current post, e.g. $edited_Item->get( 'excerpt' )
		$edited_Item->ID = 0;

		// Change creator user to current user for correct permission checking:
		$edited_Item->set( 'creator_user_ID', $current_User->ID );

		$edited_Item->load_Blog();
		$item_status = $edited_Item->Blog->get_allowed_item_status();

		$edited_Item->set( 'status', $item_status );
		$edited_Item->set( 'dateset', 0 );	// Date not explicitly set yet
		$edited_Item->set( 'issue_date', date( 'Y-m-d H:i:s', $localtimenow ) );

		// Set post comment status and extracats
		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p );

		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave ( $post_category, $post_extracats );

		// Initialize a page title depending on item type:
		$ItemTypeCache = & get_ItemTypeCache();
		$ItemType = & $ItemTypeCache->get_by_ID( $edited_Item->ityp_ID );
		$title = sprintf( T_('Duplicate %s'), $ItemType->get_name() );

		$AdminUI->breadcrumbpath_add( $title, '?ctrl=items&amp;action=copy&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID );

		$AdminUI->title_titlearea = $title.': ';

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;
		break;


	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'edit_type': // this gets set as action by JS, when we switch tabs
		// This is somewhat in between new and edit...

		// Check permission based on DB status:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$edited_Item->status = param( 'post_status', 'string', NULL );		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status );

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		$edited_Item->load_from_Request( true ); // needs Blog set

		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave( $post_category, $post_extracats );

		$edited_Item->set( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array:integer', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Page title:
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d: %s'), $edited_Item->ID, $Blog->get('name') );

		$AdminUI->breadcrumbpath_add( sprintf( T_('Post #%s'), $edited_Item->ID ), '?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID );
		$AdminUI->breadcrumbpath_add( T_('Edit'), '?ctrl=items&amp;action=edit&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;

		if( $action == 'edit_type' )
		{ // Save the changes of Item to Session
			set_session_Item( $edited_Item );
		}
		break;

	case 'history':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );
		break;

	case 'history_details':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// get revision param, but it is possible that it is not a number because current version sign is 'C'
		param( 'r', 'integer', 0, false, false, true, false );

		$Revision = $edited_Item->get_revision( $r );
		break;

	case 'history_compare':
		// Check permission:
		if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{
			$Messages->add( T_('You have no permission to view history for this item.'), 'error' );
			header_redirect( $admin_url );
		}

		param( 'r1', 'integer', 0 );
		$r2 = (int)param( 'r2', 'string', 0 );

		$Revision_1 = $edited_Item->get_revision( $r1 );
		$Revision_2 = $edited_Item->get_revision( $r2 );

		load_class( '_core/model/_diff.class.php', 'Diff' );

		// Compare the titles of two revisions
		$revisions_difference_title = new Diff( explode( "\n", $Revision_1->iver_title ), explode( "\n", $Revision_2->iver_title ) );
		$format = new TitleDiffFormatter();
		$revisions_difference_title = $format->format( $revisions_difference_title );

		// Compare the contents of two revisions
		$revisions_difference_content = new Diff( explode( "\n", $Revision_1->iver_content ), explode( "\n", $Revision_2->iver_content ) );
		$format = new TableDiffFormatter();
		$revisions_difference_content = $format->format( $revisions_difference_content );

		break;

	case 'history_restore':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		param( 'r', 'integer', 0 );

		if( $r > 0 )
		{	// Update item only from revisions ($r == 0 for current version)
			$Revision = $edited_Item->get_revision( $r );

			$edited_Item->set( 'status', $Revision->iver_status );
			$edited_Item->set( 'title', $Revision->iver_title );
			$edited_Item->set( 'content', $Revision->iver_content );

			if( $edited_Item->dbupdate() )
			{	// Item updated
				$Messages->add( sprintf( T_('Item has been restored from revision #%s'), $r ), 'success' );
			}
		}

		header_redirect( regenerate_url( 'action', 'action=history', '', '&' ) );
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// Restrict item status to max allowed by item collection:
		$edited_Item->restrict_status_by_collection();

		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p ); // NOTE: dh> using $edited_Item->get_Chapters here instead fails (empty list, since no postIDlist).

		$item_tags = implode( ', ', $edited_Item->get_tags() );
		$trackback_url = '';

		// Page title:
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d: %s'), $edited_Item->ID, $Blog->get('name') );

		$AdminUI->breadcrumbpath_add( sprintf( T_('Post #%s'), $edited_Item->ID ), '?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID );
		$AdminUI->breadcrumbpath_add( T_('Edit'), '?ctrl=items&amp;action=edit&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;


	case 'create_edit':
	case 'create_link':
	case 'create':
	case 'create_publish':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Get params to skip/force/mark notifications and pings:
		param( 'item_members_notified', 'string', NULL );
		param( 'item_community_notified', 'string', NULL );
		param( 'item_pings_sent', 'string', NULL );

		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );

		if( $action == 'create_publish' )
		{ // load publish status from param, because a post can be published to many status
			$post_status = load_publish_status( true );
		}

		// Check if allowed to cross post.
		check_cross_posting( $post_category, $post_extracats );

		// Check if new category was started to create. If yes check if it is valid.
		check_categories( $post_category, $post_extracats );

		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'create', true, $post_extracats );

		// Get requested Post Type:
		$item_typ_ID = param( 'item_typ_ID', 'integer', true /* require input */ );
		// Check permission on post type: (also verifies that post type is enabled and NOT reserved)
		check_perm_posttype( $item_typ_ID, $post_extracats );

		// Update the folding positions for current user
		save_fieldset_folding_values( $Blog->ID );

		// CREATE NEW POST:
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Restrict item status to max allowed by item collection:
		$edited_Item->restrict_status_by_collection( true );

		// Set object params:
		$edited_Item->load_from_Request( /* editing? */ ($action == 'create_edit' || $action == 'create_link'), /* creating? */ true );

		$Plugins->trigger_event ( 'AdminBeforeItemEditCreate', array ('Item' => & $edited_Item ) );

		if( !empty( $mass_create ) )
		{	// ------ MASS CREATE ------
			$Items = & create_multiple_posts( $edited_Item, param( 'paragraphs_linebreak', 'boolean', 0 ) );
			if( empty( $Items ) )
			{
				param_error( 'content', T_( 'Content must not be empty.' ) );
			}
		}

		$result = !$Messages->has_errors();

		if( $result )
		{ // There are no validation errors
			if( isset( $Items ) && !empty( $Items ) )
			{	// We can create multiple posts from single post
				foreach( $Items as $edited_Item )
				{	// INSERT NEW POST INTO DB:
					$result = $edited_Item->dbinsert();
				}
			}
			else
			{	// INSERT NEW POST INTO DB:
				$result = $edited_Item->dbinsert();
			}
			if( !$result )
			{ // Add error message
				$Messages->add( T_('Couldn\'t create the new post'), 'error' );
			}
		}

		if( $result && $action == 'create_link' )
		{	// If the item has been inserted correctly and we should copy all links from the duplicated item:
			if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
			    && $current_User->check_perm( 'files', 'view', false ) )
			{	// Allow this action only if current user has a permission to view the links of new created item:
				$original_item_ID = param( 'p', 'integer', NULL );
				$ItemCache = & get_ItemCache();
				if( $original_Item = & $ItemCache->get_by_ID( $original_item_ID, false, false ) )
				{	// Copy the links only if the requested item is correct:
					if( $current_User->check_perm( 'item_post!CURSTATUS', 'view', false, $original_Item ) )
					{	// Current user must has a permission to view an original item
						$DB->query( 'INSERT INTO T_links ( link_datecreated, link_datemodified, link_creator_user_ID,
								link_lastedit_user_ID, link_itm_ID, link_file_ID, link_ltype_ID, link_position, link_order )
							SELECT '.$DB->quote( date2mysql( $localtimenow ) ).', '.$DB->quote( date2mysql( $localtimenow ) ).', '.$current_User->ID.',
								'.$current_User->ID.', '.$edited_Item->ID.', link_file_ID, link_ltype_ID, link_position, link_order
									FROM T_links
									WHERE link_itm_ID = '.$original_Item->ID );
					}
					else
					{	// Display error if user tries to duplicate the disallowed item:
						$Messages->add( T_('You have no permission to duplicate the original post.'), 'error' );
						$result = false;
					}
				}
			}
		}

		if( !$result )
		{ // could not insert the new post ( validation errors or unsuccessful db insert )
			if( !empty( $mass_create ) )
			{
				$action = 'new_mass';
			}
			// Params we need for tab switching:
			$tab_switch_params = 'blog='.$blog;
			break;
		}

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'note' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_notifications( NULL, true, $item_members_notified, $item_community_notified, $item_pings_sent );

		$Messages->add( T_('Post has been created.'), 'success' );

		// Delete Item from Session
		delete_session_Item( 0 );

		if( ! $exit_after_save && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// We want to continue editing...
			$tab_switch_params = 'p='.$edited_Item->ID;
			$action = 'edit';	// It's basically as if we had updated
			break;
		}

		// We want to highlight the edited object on next list display:
		$Session->set( 'fadeout_array', array( 'item-'.$edited_Item->ID ) );

		if( $edited_Item->status == 'published' &&
		    ! strpos( $redirect_to, 'tab=tracker' ) &&
		    ! strpos( $redirect_to, 'tab=manual' ) )
		{	// fp> I noticed that after publishing a new post, I always want to see how the blog looks like
			// If anyone doesn't want that, we can make this optional...
			// sam2kb> Please make this optional, this is really annoying when you create more than one post or when you publish draft images created from FM.
			// yura> When a post is created from "workflow" or "manual" we should display a post list

			// Where do we want to go after publishing?
			if( $edited_Item->Blog->get_setting( 'enable_goto_blog' ) == 'blog' )
			{	// go to blog:
				$edited_Item->load_Blog();
				$redirect_to = $edited_Item->Blog->gen_blogurl();
			}
			elseif( $edited_Item->Blog->get_setting( 'enable_goto_blog' ) == 'post' )
			{	// redirect to post page:
				$redirect_to = $edited_Item->get_permanent_url();
			}
			else// 'no'
			{	// redirect to posts list:
				$redirect_to = NULL;
				// header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
			}
		}

		if( ( $redirect_to === NULL ) ||
			( ( $allow_redirects_to_different_domain != 'always' ) && ( ! url_check_same_domain( $baseurl, $redirect_to ) ) ) )
		{ // The redirect url was set to NULL ( $blog_redirect_setting == 'no' ) or the redirect_to url is outside of the base domain
			if( $redirect_to !== NULL )
			{ // Add messages which explain the different target of the redirect
				$Messages->add( sprintf( 'We did not automatically redirect you to [%s] because it\'s on a different domain.', $redirect_to ) );
			}
			header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
		}

		// REDIRECT / EXIT
		header_redirect( url_add_param( $redirect_to, 'highlight='.$edited_Item->ID, '&' ) );
		// Switch to list mode:
		// $action = 'list';
		//init_list_mode();
		break;


	case 'update_edit':
	case 'update':
	case 'update_publish':
	case 'extract_tags':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check edit permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// Update the folding positions for current user
		save_fieldset_folding_values( $Blog->ID );

		// Get params to skip/force/mark notifications and pings:
		param( 'item_members_notified', 'string', NULL );
		param( 'item_community_notified', 'string', NULL );
		param( 'item_pings_sent', 'string', NULL );

		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );

		if( $action == 'update_publish' )
		{ // load publish status from param, because a post can be published to many status
			$post_status = load_publish_status();
		}

		// Check if allowed to cross post.
		if( ! check_cross_posting( $post_category, $post_extracats, $edited_Item->main_cat_ID ) )
		{
			break;
		}

		// Check if new category was started to create.  If yes check if it is valid.
		$isset_category = check_categories( $post_category, $post_extracats );

		// Get requested Post Type:
		$item_typ_ID = param( 'item_typ_ID', 'integer', true /* require input */ );
		// Check permission on post type: (also verifies that post type is enabled and NOT reserved)
		check_perm_posttype( $item_typ_ID, $post_extracats );

		// Is this post publishing right now?
		$is_publishing = ( $edited_Item->get( 'status' ) != 'published' && $post_status == 'published' );

		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );

		// Restrict item status to max allowed by item collection:
		$edited_Item->restrict_status_by_collection( true );

		if( $isset_category )
		{ // we change the categories only if the check was succesful

			// get current extra_cats that are in collections where current user is not a coll admin
			$ChapterCache = & get_ChapterCache();

			$prev_extra_cat_IDs = postcats_get_byID( $edited_Item->ID );
			$off_limit_cats = array();
			$r = array();

			foreach( $prev_extra_cat_IDs as $cat )
			{
				$cat_blog = get_catblog( $cat );
				if( ! $current_User->check_perm( 'blog_admin', '', false, $cat_blog ) )
				{
					$Chapter = $ChapterCache->get_by_ID( $cat );
					$off_limit_cats[$cat] = $Chapter;
					$r[] = '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->dget( 'name' ).'</a>';
				}
			}

			if( $off_limit_cats )
			{
				$Messages->add( sprintf( T_('Please note: this item is also cross-posted to the following other categories/collections: %s'),
						implode( ', ', $r ) ), 'note' );
			}

			$post_extracats = array_unique( array_merge( $post_extracats,array_keys( $off_limit_cats ) ) );

			$edited_Item->set( 'main_cat_ID', $post_category );
			$edited_Item->set( 'extra_cat_IDs', $post_extracats );
		}

		// Set object params:
		$edited_Item->load_from_Request( false );

		$Plugins->trigger_event( 'AdminBeforeItemEditUpdate', array( 'Item' => & $edited_Item ) );

		// Params we need for tab switching (in case of error or if we save&edit)
		$tab_switch_params = 'p='.$edited_Item->ID;

		if( $Messages->has_errors() )
		{	// There have been some validation errors:
			break;
		}

		// UPDATE POST IN DB:
		if( !$edited_Item->dbupdate() )
		{ // Could not update successful
			$Messages->add( T_('The post couldn\'t be updated.'), 'error' );
			break;
		}

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'note' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_notifications( NULL, false, $item_members_notified, $item_community_notified, $item_pings_sent );

		$Messages->add( T_('Post has been updated.'), 'success' );

		if( $action == 'extract_tags' )
		{	// Extract all possible tags from item contents:
			$searched_tags = $edited_Item->search_tags_by_content();
			// Append new searched tags to existing item's tags:
			$item_tags .= ','.implode( ',', $searched_tags );
			// Clear temp commas:
			$item_tags = utf8_trim( $item_tags, ',' );
		}

		// Delete Item from Session
		delete_session_Item( $edited_Item->ID );

		if( ! $exit_after_save )
		{ // We want to continue editing...
			break;
		}

		/* fp> I noticed that after publishing a new post, I always want
		 *     to see how the blog looks like. If anyone doesn't want that,
		 *     we can make this optional...
		 */
		if( $edited_Item->status == 'redirected' ||
		    strpos( $redirect_to, 'tab=tracker' ) )
		{ // We should show the posts list if:
			//    a post is in "Redirected" status
			//    a post is updated from "workflow" view tab
			$blog_redirect_setting = 'no';
		}
		elseif( strpos( $redirect_to, 'tab=manual' ) )
		{	// Use the original $redirect_to if a post is updated from "manual" view tab:
			$blog_redirect_setting = 'orig';
		}
		elseif( $is_publishing )
		{	// The post's last status wasn't "published", but we're going to publish it now:
			$edited_Item->load_Blog();
			$blog_redirect_setting = $edited_Item->Blog->get_setting( 'enable_goto_blog' );
		}
		else
		{ // The post was changed
			$blog_redirect_setting = $edited_Item->Blog->get_setting( 'editing_goto_blog' );
		}

		if( $blog_redirect_setting == 'blog' )
		{ // go to blog:
			$edited_Item->load_Blog();
			$redirect_to = $edited_Item->Blog->gen_blogurl();
		}
		elseif( $blog_redirect_setting == 'post' )
		{ // redirect to post page:
			$redirect_to = $edited_Item->get_permanent_url();
		}
		elseif( $blog_redirect_setting == 'orig' )
		{ // Use original $redirect_to:
			// Set highlight:
			$Session->set( 'highlight_id', $edited_Item->ID );
			$redirect_to = url_add_param( $redirect_to, 'highlight_id='.$edited_Item->ID, '&' );
		}
		else
		{ // $blog_redirect_setting == 'no', set redirect_to = NULL which will redirect to posts list
			$redirect_to = NULL;
		}

		if( ( $redirect_to === NULL ) ||
			( ( $allow_redirects_to_different_domain != 'always' ) && ( ! url_check_same_domain( $baseurl, $redirect_to ) ) ) )
		{ // The redirect url was set to NULL ( $blog_redirect_setting == 'no' ) or the redirect_to url is outside of the base domain
			if( $redirect_to !== NULL )
			{ // Add messages which explain the different target of the redirect
				$Messages->add( sprintf( 'We did not automatically redirect you to [%s] because it\'s on a different domain.', $redirect_to ) );
			}
			// Set highlight
			$Session->set( 'highlight_id', $edited_Item->ID );
			header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to, 303 );
		/* EXITED */
		break;

	case 'update_type':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		param( 'from_tab', 'string', NULL );
		param( 'post_ID', 'integer', true, true );
		param( 'ityp_ID', 'integer', true );

		// Used when we change a type of the duplicated item:
		$duplicated_item_ID = param( 'p', 'integer', NULL );

		// Load post from Session
		$edited_Item = get_session_Item( $post_ID );

		if( $Blog->is_item_type_enabled( $ityp_ID ) )
		{ // Set new post type only if it is enabled for the Blog:
			$edited_Item->set( 'ityp_ID', $ityp_ID );
		}

		// Unset old object of Post Type to reload it on next page
		unset( $edited_Item->ItemType );

		// Check edit permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		set_session_Item( $edited_Item );

		if( ! empty( $from_tab ) && $from_tab == 'type' )
		{ // It goes from items lists to update item type immediately:

			if( $Blog->is_item_type_enabled( $ityp_ID ) )
			{ // Update only when the selected item type is enabled for the Blog:
				$Messages->add( T_('Post type has been updated.'), 'success' );

				// Update item to set new type right now
				$edited_Item->dbupdate();

				// Highlight the updated item in list
				$Session->set( 'highlight_id', $edited_Item->ID );
			}

			// Set redirect back to items list with new item type tab
			$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&tab=type&tab_type='.$edited_Item->get_type_setting( 'usage' ).'&filter=restore';
		}
		else
		{ // Set default redirect urls (It goes from the item edit form)
			if( $post_ID > 0 )
			{ // Edit item form
				$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&action=edit&restore=1&p='.$edited_Item->ID;
			}
			elseif( $duplicated_item_ID > 0 )
			{ // Copy item form
				$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&action=new&restore=1&p='.$duplicated_item_ID;
			}
			else
			{ // New item form
				$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&action=new&restore=1';
			}
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		/* EXITED */
		break;

	case 'update_status':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		param( 'status', 'string', true );

		// Check edit permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );
		$current_User->check_perm( 'item_post!'.$status, 'edit', true, $edited_Item );

		// Set new post type
		$edited_Item->set( 'status', $status );

		if( $edited_Item->get( 'status' ) == 'redirected' && empty( $edited_Item->url ) )
		{ // Note: post_url is not part of the simple form, so this message can be a little bit awkward there
			param_error( 'post_url',
				T_('If you want to redirect this post, you must specify an URL!').' ('.T_('Advanced properties panel').')',
				T_('If you want to redirect this post, you must specify an URL!') );
		}

		if( param_errors_detected() )
		{ // If errors then redirect to edit form
			$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&action=edit&restore=1&p='.$edited_Item->ID;
		}
		else
		{ // No errors, Update the item and redirect back to list
			$Messages->add( T_('Post status has been updated.'), 'success' );

			// Update item to set new type right now
			$edited_Item->dbupdate();

			// Execute or schedule notifications & pings:
			$edited_Item->handle_notifications();

			// Set redirect back to items list with new item type tab:
			$tab = get_tab_by_item_type_usage( $edited_Item->get_type_setting( 'usage' ) );
			$redirect_to = $admin_url.'?ctrl=items&blog='.$Blog->ID.'&tab=type&tab_type='.( $tab ? $tab[0] : 'post' ).'&filter=restore';

			// Highlight the updated item in list
			$Session->set( 'highlight_id', $edited_Item->ID );
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		/* EXITED */
		break;


	case 'mass_save' :
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb ( 'item' );

		init_list_mode ();
		$ItemList->query ();

		global $DB;

		$update_nr = 0;

		while ( $Item = & $ItemList->get_item () )
		{	// check user permission
			$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $Item );

			// Not allow html content on post titles
			$title = param ( 'mass_title_' . $Item->ID, 'htmlspecialchars', NULL );
			$urltitle = param ( 'mass_urltitle_' . $Item->ID, 'string', NULL );
			$titletag = param ( 'mass_titletag_' . $Item->ID, 'string', NULL );

			if ($title != NULL)
			{
				$Item->set ( 'title', $title );
			}

			if ($urltitle != NULL)
			{
				$Item->set ( 'urltitle', $urltitle );
			}

			if ($titletag != NULL)
			{
				$Item->set ( 'titletag', $titletag );
			}

			if( $Item->dbupdate ())
			{
				$update_nr++;	// successfully updated post number
			}
		}

		if( $update_nr > 0 )
		{
			$Messages->add( $update_nr == 1 ?
				T_('One post has been updated!') :
				sprintf( T_('%d posts have been updated!'), $update_nr ), 'success' );
		}
		else
		{
			$Messages->add( T_('No update executed!') );
		}
		// REDIRECT / EXIT
		header_redirect ( $redirect_to, 303 );
		/* EXITED */
		break;

	case 'publish' :
	case 'publish_now' :
		// Publish NOW:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		$post_status = ( $action == 'publish_now' ) ? 'published' : param( 'post_status', 'string', 'published' );

		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'item_post!'.$post_status, 'edit', true, $edited_Item );

		$edited_Item->set( 'status', $post_status );

		if( $action == 'publish_now' )
		{ // Update post dates
			$current_User->check_perm( 'blog_edit_ts', 'edit', true, $Blog->ID );
			// fp> TODO: remove seconds ONLY if date is in the future
			$edited_Item->set( 'datestart', remove_seconds($localtimenow) );
			$edited_Item->set( 'datemodified', date('Y-m-d H:i:s', $localtimenow) );
		}

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		// Get params to skip/force/mark notifications and pings:
		param( 'item_members_notified', 'string', NULL );
		param( 'item_community_notified', 'string', NULL );
		param( 'item_pings_sent', 'string', NULL );

		// Execute or schedule notifications & pings:
		$edited_Item->handle_notifications( NULL, false, $item_members_notified, $item_community_notified, $item_pings_sent );

		// Set the success message corresponding for the new status
		switch( $edited_Item->status )
		{
			case 'published':
				$success_message = T_('Post has been published.');
				break;
			case 'community':
				$success_message = T_('The post is now visible by the community.');
				break;
			case 'protected':
				$success_message = T_('The post is now visible by the members.');
				break;
			case 'review':
				$success_message = T_('The post is now visible by moderators.');
				break;
			default:
				$success_message = T_('Post has been updated.');
				break;
		}
		$Messages->add( $success_message, 'success' );

		// Delete Item from Session
		delete_session_Item( $edited_Item->ID );

		// fp> I noticed that after publishing a new post, I always want to see how the blog looks like
		// If anyone doesn't want that, we can make this optional...

		// REDIRECT / EXIT
		if( $action == 'publish' && !empty( $redirect_to ) && ( strpos( $redirect_to, $admin_url ) !== 0 ) )
		{ // We clicked publish button from the front office
			header_redirect( $redirect_to );
		}
		elseif( $edited_Item->Blog->get_setting( 'enable_goto_blog' ) == 'blog' )
		{	// Redirect to blog:
			$edited_Item->load_Blog();
			header_redirect( $edited_Item->Blog->gen_blogurl() );
		}
		elseif( $edited_Item->Blog->get_setting( 'enable_goto_blog' ) == 'post' )
		{	// Redirect to post page:
			header_redirect( $edited_Item->get_permanent_url() );
		}
		else// 'no'
		{	// Redirect to posts list:
			header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
		}
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
		break;

	case 'restrict':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		$post_status = param( 'post_status', 'string', true );
		// Check permissions:
		$current_User->check_perm( 'item_post!'.$post_status, 'moderate', true, $edited_Item );

		$edited_Item->set( 'status', $post_status );

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		$Messages->add( T_('Post has been restricted.'), 'success' );

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		break;

	case 'deprecate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		$post_status = 'deprecated';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'item_post!'.$post_status, 'edit', true, $edited_Item );

		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		$Messages->add( T_('Post has been deprecated.'), 'success' );

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
		break;


	case 'delete':
		// Delete an Item:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check permission:
		$current_User->check_perm( 'blog_del_post', '', true, $blog );

		// fp> TODO: non javascript confirmation
		// $AdminUI->title = T_('Deleting post...');

		$Plugins->trigger_event( 'AdminBeforeItemEditDelete', array( 'Item' => & $edited_Item ) );

		if( ! $Messages->has_errors() )
		{	// There have been no validation errors:
			// DELETE POST FROM DB:
			$edited_Item->dbdelete();

			$Messages->add( T_('Post has been deleted.'), 'success' );
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
		break;


	case 'mass_edit' :
		init_list_mode ();
		break;


	case 'list':
		init_list_mode();

		if( $ItemList->single_post )
		{	// We have requested to view a SINGLE specific post:
			$action = 'view';
		}
		break;

	case 'make_posts_pre':
		// Make posts with selected images action:
		break;

	default:
		debug_die( 'unhandled action 2: '.htmlspecialchars($action) );
}


/**
 * Initialize list mode; Several actions need this.
 */
function init_list_mode()
{
	global $tab, $tab_type, $Blog, $UserSettings, $ItemList, $AdminUI, $current_User;

	// set default itemslist param prefix
	$items_list_param_prefix = 'items_';

	if ( param( 'p', 'integer', NULL ) || param( 'title', 'string', NULL ) )
	{	// Single post requested, do not filter any post types. If the user
		// has clicked a post link on the dashboard and previously has selected
		// a tab which would filter this post, it wouldn't be displayed now.
		$tab = 'full';
		// in case of single item view params prefix must be empty
		$items_list_param_prefix = NULL;
	}
	else
	{	// Store/retrieve preferred tab from UserSettings:
		$UserSettings->param_Request( 'tab', 'pref_browse_tab', 'string', NULL, true /* memorize */, true /* force */ );
		$UserSettings->param_Request( 'tab_type', 'pref_browse_tab_type', 'string', NULL, true /* memorize */, true /* force */ );
	}

	if( $tab == 'tracker' && ( ! $Blog->get_setting( 'use_workflow' ) || ! $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) ) )
	{ // Display workflow view only if it is enabled
		global $Messages;
		$Messages->add( T_('Workflow feature has not been enabled for this collection.'), 'note' );
		$tab = 'full';
	}

	/*
	 * Init list of posts to display:
	 */
	load_class( 'items/model/_itemlist.class.php', 'ItemList2' );

	if( !empty( $tab ) && !empty( $items_list_param_prefix ) )
	{	// Use different param prefix for each tab
		$items_list_param_prefix .= substr( $tab, 0, 7 ).'_';//.utf8_strtolower( $tab_type ).'_';
	}

	// Set different filterset name for each different tab and tab_type
	$filterset_name = ( $tab == 'type' ) ? $tab.'_'.utf8_strtolower( $tab_type ) : $tab;

	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL, $UserSettings->get('results_per_page'), 'ItemCache', $items_list_param_prefix, $filterset_name /* filterset name */ ); // COPY (func)

	$ItemList->set_default_filters( array(
			'visibility_array' => get_visibility_statuses('keys'),
		) );

	if( $Blog->get_setting('orderby') == 'RAND' )
	{	// Do not display random posts in backoffice for easy management
		$ItemList->set_default_filters( array(
				'orderby' => 'datemodified',
			) );
	}

	switch( $tab )
	{
		case 'full':
			$ItemList->set_default_filters( array(
					'itemtype_usage' => NULL // All types
				) );
			// $AdminUI->breadcrumbpath_add( T_('All items'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );

			// require colorbox js
			require_js_helper( 'colorbox' );

			$AdminUI->breadcrumbpath_add( T_('All'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );
			break;

		case 'manual':
			if( $Blog->get( 'type' ) != 'manual' )
			{	// Display this tab only for manual blogs
				global $admin_url;
				header_redirect( $admin_url.'?ctrl=items&blog='.$Blog->ID.'&tab=type&tab_type=post&filter=restore' );
			}

			global $ReqURI, $blog;

			init_field_editor_js( array(
					'action_url' => $ReqURI.'&blog='.$blog.'&order_action=update&order_data=',
				) );

			$AdminUI->breadcrumbpath_add( T_('Manual view'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'type':
			// Filter a posts list by type
			$ItemList->set_default_filters( array(
					'itemtype_usage' => implode( ',', get_item_type_usage_by_tab( $tab_type ) ),
				) );
			$AdminUI->breadcrumbpath_add( T_( $tab_type ), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab_type='.urlencode( $tab_type ).'&amp;filter=restore' );
			break;

		case 'tracker':
			// In tracker mode, we want a different default sort:
			$ItemList->set_default_filters( array(
					'orderby' => 'priority',
					'order' => 'ASC' ) );
			$AdminUI->breadcrumbpath_add( T_( 'Workflow view' ), '?ctrl=items&amp;blog=$blog$&amp;tab=tracker&amp;filter=restore' );

			$AdminUI->set_page_manual_link( 'workflow-features' );

			// JS to edit priority of items from list view
			require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
			break;

		default:
			// Delete the pref_browse_tab setting so that the default
			// (full) gets used the next time the user wants to browse
			// a blog and we don't run into the same error again.
			$UserSettings->delete( 'pref_browse_tab' );
			$UserSettings->dbupdate();
			debug_die( 'Unknown filterset ['.$tab.']' );
	}

	// Init filter params:
	if( ! $ItemList->load_from_Request() )
	{ // If we could not init a filterset from request
		// typically happens when we could no fall back to previously saved filterset...
		// echo ' no filterset!';
	}
}

/**
 * Configure page navigation:
 */
switch( $action )
{
	case 'new':
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'new_type': // this gets set as action by JS, when we switch tabs
	case 'copy':
	case 'create_edit':
	case 'create_link':
	case 'create':
	case 'create_publish':
		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_post_statuses', 'edit', array( 'ctrl' => 'items', 'action' => 'new' ) );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$error_message = T_('Since this blog has no categories, you cannot post into it.');
			if( $current_User->check_perm( 'blog_cats', 'edit', false, $blog ) )
			{ // If current user has a permission to create a category
				global $admin_url;
				$error_message .= ' '.sprintf( T_('You must <a %s>create categories</a> first.'), 'href="'.$admin_url.'?ctrl=chapters&amp;blog='.$blog.'"');
			}
			$Messages->add( $error_message, 'error' );
			$action = 'nil';
			break;
		}

		/* NOBREAK */

	case 'edit':
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'edit_type': // this gets set as action by JS, when we switch tabs
	case 'update_edit':
	case 'update': // on error
	case 'update_publish': // on error
	case 'history':
	case 'extract_tags':

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'items', 'filter' => 'restore' ) );

		switch( $action )
		{
			case 'edit':
			case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
			case 'update_edit':
			case 'update': // on error
			case 'update_publish': // on error
			case 'history':
			case 'extract_tags':
				if( $current_User->check_perm( 'item_post!CURSTATUS', 'delete', false, $edited_Item ) )
				{	// User has permissions to delete this post
					$AdminUI->global_icon( T_('Delete this post'), 'delete', $admin_url.'?ctrl=items&amp;action=delete&amp;post_ID='.$edited_Item->ID.'&amp;'.url_crumb('item'),
						' '.T_('Delete'), 4, 3, array(
								'onclick' => 'return confirm(\''.TS_('You are about to delete this post!\\nThis cannot be undone!').'\')',
								'style' => 'margin-right: 3ex;',	// Avoid misclicks by all means!
						) );
				}

				$AdminUI->global_icon( T_('Permanent link to full entry'), 'permalink', $edited_Item->get_permanent_url(),
						' '.T_('Permalink'), 4, 3, array(
								'style' => 'margin-right: 3ex',
						) );

				$edited_item_url = $edited_Item->get_copy_url();
				if( ! empty( $edited_item_url ) )
				{	// If user has a permission to copy the edited Item:
					$AdminUI->global_icon( T_('Duplicate this post...'), 'copy', $edited_item_url,
						' '.T_('Duplicate...'), 4, 3, array(
								'style' => 'margin-right: 3ex;',
						) );
				}
				break;
		}

		if( ! in_array( $action, array( 'new_type', 'edit_type' ) ) )
		{
			if( $edited_Item->ID > 0 )
			{ // Display a link to history if Item exists in DB
				$AdminUI->global_icon( T_('History'), '', $edited_Item->get_history_url(),
					$edited_Item->history_info_icon().' '.T_('History'), 4, 3, array(
							'style' => 'margin-right: 3ex'
					) );

				// Params we need for tab switching
				$tab_switch_params = 'p='.$edited_Item->ID;
			}
			else
			{
				$tab_switch_params = '';
			}

			if( $Blog->get_setting( 'in_skin_editing' ) && ( $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID ) || get_param( 'p' ) > 0 ) )
			{ // Show 'In skin' link if Blog setting 'In-skin editing' is ON and User has a permission to publish item in this blog
				$mode_inskin_url = url_add_param( $Blog->get( 'url' ), 'disp=edit&amp;'.$tab_switch_params );
				$mode_inskin_action = get_samedomain_htsrv_url().'item_edit.php';
				$AdminUI->global_icon( T_('In skin editing'), 'edit', $mode_inskin_url,
						' '.T_('In skin editing'), 4, 3, array(
						'style' => 'margin-right: 3ex',
						'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$mode_inskin_action.'\' );'
				) );
			}

			$AdminUI->global_icon( T_('Cancel editing!'), 'close', $redirect_to, T_('Cancel'), 4, 2 );

			init_tokeninput_js();
		}

		break;

	case 'new_mass':

		$AdminUI->set_coll_list_params( 'blog_post_statuses', 'edit', array( 'ctrl' => 'items', 'action' => 'new' ) );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$error_message = T_('Since this blog has no categories, you cannot post into it.');
			if( $current_User->check_perm( 'blog_cats', 'edit', false, $blog ) )
			{ // If current user has a permission to create a category
				global $admin_url;
				$error_message .= ' '.sprintf( T_('You must <a %s>create categories</a> first.'), 'href="'.$admin_url.'?ctrl=chapters&amp;blog='.$blog.'"');
			}
			$Messages->add( $error_message, 'error' );
			$action = 'nil';
			break;
		}

	break;

	case 'view':
		// We're displaying a SINGLE specific post:
		$item_ID = param( 'p', 'integer', true );

		$AdminUI->title_titlearea = T_('View post & comments');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'items', 'tab' => $tab, 'filter' => 'restore' ) );

		$AdminUI->breadcrumbpath_add( sprintf( T_('Post #%s'), $item_ID ), '?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$item_ID );
		$AdminUI->breadcrumbpath_add( T_('View post & comments'), '?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$item_ID );
		break;

	case 'list':
		// We're displaying a list of posts:

		$AdminUI->title_titlearea = T_('Browse blog');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'items', 'tab' => $tab, 'filter' => 'restore' ) );

		break;
}

/*
 * Add sub menu entries:
 * We do this here instead of _header because we need to include all filter params into regenerate_url()
 */
attach_browse_tabs();


if( isset( $edited_Item ) && ( $ItemType = & $edited_Item->get_ItemType() ) )
{	// Set a tab type for edited/viewed item:
	$tab = 'type';
	if( $tab_type = get_tab_by_item_type_usage( $ItemType->usage ) )
	{	// Only if tab exists for current item type usage:
		$tab_type = $tab_type[0];
	}
	else
	{
		$tab_type = 'all';
	}
}

if( ! empty( $tab ) && $tab == 'type' )
{ // Set a path from dynamic tabs
	$AdminUI->append_path_level( 'type_'.str_replace( ' ', '_', utf8_strtolower( $tab_type ) ) );
}
else
{ // Set a path to selected tab
	$AdminUI->append_path_level( empty( $tab ) ? 'full' : $tab );
}

if( ( isset( $tab ) && in_array( $tab, array( 'full', 'type', 'tracker' ) ) ) || strpos( $action, 'edit' ) === 0 )
{ // Init JS to autcomplete the user logins
	init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
	// Initialize date picker for _item_expert.form.php
	init_datepicker_js();
}

// Load the appropriate blog navigation styles (including calendar, comment forms...):
require_css( $AdminUI->get_template( 'blog_base.css' ) ); // Default styles for the blog navigation
require_js( 'communication.js' ); // auto requires jQuery
init_plugins_js( 'rsc_url', $AdminUI->get_template( 'tooltip_plugin' ) );

/* fp> I am disabling this. We haven't really used per-blof styles yet and at the moment it creates interference with boostrap Admin
// Load the appropriate ITEM/POST styles depending on the blog's skin:
// It's possible that we have no Blog on the restricted admin interface, when current User doesn't have permission to any blog
if( !empty( $Blog ) )
{ // set blog skin ID if the Blog is set
	$blog_sking_ID = $Blog->get_skin_ID();
	if( ! empty( $blog_sking_ID ) )
	{
		$SkinCache = & get_SkinCache();
		$Skin = $SkinCache->get_by_ID( $blog_sking_ID );
		require_css( 'basic_styles.css', 'blog' ); // the REAL basic styles
		require_css( 'item_base.css', 'blog' ); // Default styles for the post CONTENT
		require_css( $skins_url.$Skin->folder.'/item.css' ); // fp> TODO: this needs to be a param... "of course" -- if none: else item_default.css ?
		// else: $item_css_url = $rsc_url.'css/item_base.css';
	}
	// else item_default.css ? is it still possible to have no skin set?
}
*/

if( $action == 'view' || strpos( $action, 'edit' ) !== false || strpos( $action, 'new' ) !== false )
{ // Initialize js to autocomplete usernames in post/comment form
	init_autocomplete_usernames_js();
}

if( in_array( $action, array( 'new', 'copy', 'create_edit', 'create_link', 'create', 'create_publish', 'edit', 'update_edit', 'update', 'update_publish', 'extract_tags' ) ) )
{ // Set manual link for edit expert mode
	$AdminUI->set_page_manual_link( 'expert-edit-screen' );
}

// Set an url for manual page:
switch( $action )
{
	case 'history':
	case 'history_details':
		$AdminUI->set_page_manual_link( 'item-revision-history' );
		break;
	case 'new':
	case 'new_switchtab':
	case 'edit':
	case 'edit_switchtab':
	case 'copy':
	case 'create':
	case 'create_edit':
	case 'create_link':
	case 'create_publish':
	case 'update':
	case 'update_edit':
	case 'update_publish':
	case 'extract_tags':
		$AdminUI->set_page_manual_link( 'expert-edit-screen' );
		break;
	case 'edit_type':
		$AdminUI->set_page_manual_link( 'change-post-type' );
		break;
	case 'new_mass':
		$AdminUI->set_page_manual_link( 'mass-new-screen' );
		break;
	case 'mass_edit':
		$AdminUI->set_page_manual_link( 'mass-edit-screen' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'browse-edit-tab' );
		break;
}
if( $tab == 'manual' )
{
	$AdminUI->set_page_manual_link( 'manual-pages-editor' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top( $mode != 'iframe' );	// do NOT display stupid messages in iframe (UGLY UGLY UGLY!!!!)


/*
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'new_type': // this gets set as action by JS, when we switch tabs
	case 'edit_type': // this gets set as action by JS, when we switch tabs
		$bozo_start_modified = true;	// We want to start with a form being already modified
	case 'new':
	case 'copy':
	case 'create_edit':
	case 'create_link':
	case 'create':
	case 'create_publish':
	case 'edit':
	case 'update_edit':
	case 'update':	// on error
	case 'update_publish':	// on error
	case 'extract_tags':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

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

		// Display VIEW:
		if( in_array( $action, array( 'new_type', 'edit_type' ) ) )
		{ // Form to change post type
			$AdminUI->disp_view( 'items/views/_item_edit_type.form.php' );
		}
		else
		{ // Form to edit item
			$AdminUI->disp_view( 'items/views/_item_expert.form.php' );
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	case 'new_mass':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

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

		$AdminUI->disp_view( 'items/views/_item_mass.form.php' );

		// End payload block:
		$AdminUI->disp_payload_end();

		break;


	case 'view':
	case 'delete':
		// View a single post:

		// Memorize 'p' in case we reload while changing some display settings
		memorize_param( 'p', 'integer', NULL );

		// What comments view, 'feedback' - all user comments, 'meta' - meta comments of the admins
		param( 'comment_type', 'string', 'feedback', true );

		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// We use the "full" view for displaying single posts:
		$AdminUI->disp_view( 'items/views/_item_list_full.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();

		break;

	case 'history':
		memorize_param( 'action', 'string', NULL );

		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// view:
		$AdminUI->disp_view( 'items/views/_item_history.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'history_details':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// view:
		$AdminUI->disp_view( 'items/views/_item_history_details.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'history_compare':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// view:
		$AdminUI->disp_view( 'items/views/_item_history_compare.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'mass_edit' :
		// Begin payload block:
		$AdminUI->disp_payload_begin ();

		// view:
		$AdminUI->disp_view ( 'items/views/_item_mass_edit.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end ();
		break;

	case 'make_posts_pre':
		// Make posts with selected images action:

		$FileRootCache = & get_FileRootCache();
		// getting root
		$root = param("root");
		global $fm_FileRoot;
		$fm_FileRoot = & $FileRootCache->get_by_ID($root, true);

		// Begin payload block:
		$AdminUI->disp_payload_begin();
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		$AdminUI->disp_view( 'items/views/_file_create_posts.form.php' );
		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// fplanque> Note: this is depressing, but I have to put a table back here
		// just because IE supports standards really badly! :'(
		$table_browse_template = $AdminUI->get_template( 'table_browse' );
		echo $table_browse_template['table_start'];

		if( $tab == 'manual' )
		{
			echo $table_browse_template['full_col_start'];
		}
		else{
			echo $table_browse_template['left_col_start'];
		}

			switch( $tab )
			{
				case 'tracker':
					// Display VIEW:
					$AdminUI->disp_view( 'items/views/_item_list_track.view.php' );
					break;

				case 'full':
					// Display VIEW:
					$AdminUI->disp_view( 'items/views/_item_list_full.view.php' );
					break;

				case 'manual':
					// Display VIEW:
					$AdminUI->disp_view( 'items/views/_item_list_manual.view.php' );
					break;

				case 'type':
				default:
					// Display VIEW:
					$AdminUI->disp_view( 'items/views/_item_list_table.view.php' );
					break;
			}

			// TODO: a specific field for the backoffice, at the bottom of the page
			// would be used for moderation rules.
			if( $Blog->get( 'notes' ) )
			{
				$block_item_Widget = new Widget( 'block_item' );
				$block_item_Widget->title = T_('Notes');
				// show a quicklink to edit if user has permission:
/* fp> TODO: use an action icon (will appear on the right)
				if( $current_User->check_perm( 'blog_properties', 'edit', false, $blog ) )
					$block_item_Widget->title .=	' <a href="?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID.'#ffield_blog_notes">'.get_icon( 'edit' ).'</a>';
*/
				$block_item_Widget->disp_template_replaced( 'block_start' );
				$Blog->disp( 'notes', 'htmlbody' );
				$block_item_Widget->disp_template_replaced( 'block_end' );
			}

		echo $table_browse_template['left_col_end'];

		if( $tab != 'manual' )
		{
			echo $table_browse_template['right_col_start'];
				// Display VIEW:
				$AdminUI->disp_view( 'items/views/_item_list_sidebar.view.php' );
			echo $table_browse_template['right_col_end'];
		}

		echo $table_browse_template['table_end'];

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>