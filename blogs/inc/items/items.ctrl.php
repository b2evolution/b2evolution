<?php
/**
 * This file implements the UI controller for managing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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

$action = param_action( 'list' );

$AdminUI->set_path( 'items' );	// Sublevel may be attached below

/*
 * Init the objects we want to work on.
 *
 * Autoselect a blog where we have PERMISSION to browse (preferably the last used blog):
 * Note: for some actions, we'll get the blog from the post ID
 */
switch( $action )
{
	case 'edit_links':
		param( 'item_ID', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $item_ID );
		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		break;

	case 'unlink':
		// Name of the iframe we want some action to come back to:
		param( 'iframe_name', 'string', '', true );

		param( 'link_ID', 'integer', true );
		$LinkCache = & get_Cache( 'LinkCache' );
		if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) !== false )
		{	// We have a link, get the Item it is attached to:
			$edited_Item = & $edited_Link->Item;

			// Load the blog we're in:
			$Blog = & $edited_Item->get_Blog();
			set_working_blog( $Blog->ID );
		}
		else
		{	// We could not find the link to edit:
			$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
			unset( $edited_Link );
			unset( $link_ID );
			if( $mode == 'iframe' )
			{
				$action = 'edit_links';
				param( 'item_ID', 'integer', true, true );
				$ItemCache = & get_Cache( 'ItemCache' );
				$edited_Item = & $ItemCache->get_by_ID( $item_ID );
				// Load the blog we're in:
				$Blog = & $edited_Item->get_Blog();
			}
			else
			{
				$action = 'nil';
			}
		}
		break;

	case 'edit':
 		// Load post to edit:
		param( 'p', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $p );

		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );
		break;

	case 'update_edit':
	case 'update':
	case 'update_publish':
	case 'publish':
	case 'deprecate':
	case 'delete':
 		// Note: we need to *not* use $p in the cases above or it will conflict with the list display
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
 		// Load post to edit:
		param( 'post_ID', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $post_ID );

		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );

		// What form button has been pressed?
		param( 'save', 'string', '' );
		$exit_after_save = ( $action != 'update_edit' );
		break;

	case 'new':
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'create_edit':
	case 'create':
	case 'create_publish':
	case 'list':
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
				$BlogCache = & get_Cache( 'BlogCache' );
				$Blog = & $BlogCache->get_by_ID( $blog );
			}

			// Where are we going to redirect to?
			param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID, '&' ) );

			// What form buttton has been pressed?
			param( 'save', 'string', '' );
			$exit_after_save = ( $action != 'create_edit' );
		}
		break;

	default:
		debug_die( 'unhandled action 1:'.htmlspecialchars($action) );
}


/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
		// Do nothing
		break;

	case 'new':
		$set_issue_date = 'now';
		$item_issue_date = date( locale_datefmt(), $localtimenow );
		$item_issue_time = date( 'H:i:s', $localtimenow );
		// pre_dump( $item_issue_date, $item_issue_time );
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
		// New post form  (can be a bookmarklet form if mode == bookmarklet )

		load_class('items/model/_item.class.php');
		$edited_Item = & new Item();

		$edited_Item->blog_ID = $blog;

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		// Also used by bookmarklet
		$edited_Item->load_from_Request( true ); // needs Blog set

		$edited_Item->status = param( 'post_status', 'string', NULL );		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status );


		param( 'post_extracats', 'array', array() );

		$edited_Item->main_cat_ID = param( 'post_category', 'integer', $Blog->get_default_cat_ID() );
		if( $edited_Item->main_cat_ID && $allow_cross_posting < 3 && get_catblog($edited_Item->main_cat_ID) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->main_cat_ID = $Blog->get_default_cat_ID();
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

 		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
 		param( 'trackback_url', 'string', '' );

		// Page title:

		if( param( 'item_typ_ID', 'integer', NULL ) !== NULL )
		{
			switch( $item_typ_ID )
			{
				case 2:
					$AdminUI->title = T_('New link in blog:').' ';
					break;

				case 1000:
					$AdminUI->title = T_('New page in blog:').' ';
					break;

				case 1600:
					$AdminUI->title = T_('New intro in blog:').' ';
					break;

				case 2000:
					$AdminUI->title = T_('New podcast episode:').' ';
					break;

				default:
					$AdminUI->title = T_('New post in blog:').' ';
					break;
			}
		}
		else
		{
			$AdminUI->title = T_('New post in blog:').' ';
		}

		$AdminUI->title_titlearea = $AdminUI->title;
		$js_doc_title_prefix = $AdminUI->title;

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;
		break;


	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
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

		param( 'post_extracats', 'array', array() );
		$edited_Item->main_cat_ID = param( 'post_category', 'integer', $edited_Item->main_cat_ID );
		if( $edited_Item->main_cat_ID && $allow_cross_posting < 3 && get_catblog($edited_Item->main_cat_ID) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->main_cat_ID = $Blog->get_default_cat_ID();
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

 		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
 		param( 'trackback_url', 'string', '' );

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;


	case 'edit':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p );

		$item_tags = implode( ', ', $edited_Item->get_tags() );
		$trackback_url = '';

		$set_issue_date = 'set';

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;


	case 'create_edit':
	case 'create':
	case 'create_publish':
		// We need early decoding of these in order to check permissions:
		param( 'post_category', 'integer', true );
		param( 'post_extracats', 'array', array() );
		if( $action == 'create_publish' )
		{
			$post_status = 'published';
			$set_issue_date = 'now';
		}
		else
		{
			param( 'post_status', 'string', 'published' );
		}
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );


		// CREATE NEW POST:
		load_class('items/model/_item.class.php');
		$edited_Item = & new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( false );

		$Plugins->trigger_event( 'AdminBeforeItemEditCreate', array( 'Item' => & $edited_Item ) );

		if( $Messages->count('error') )
		{	// There have been some validation errors:
			// Params we need for tab switching:
			$tab_switch_params = 'blog='.$blog;
			break;
		}

		// INSERT NEW POST INTO DB:
		$edited_Item->dbinsert();

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'info' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item->content, $edited_Item->title, $edited_Item->ID);
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing( $exit_after_save );

		$Messages->add( T_('Post has been created.'), 'success' );

		if( ! $exit_after_save )
		{	// We want to continue editing...
			$tab_switch_params = 'p='.$edited_Item->ID;
			$action = 'edit';	// It's basically as if we had updated
			break;
		}
		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		// Switch to list mode:
		// $action = 'list';
		//init_list_mode();
		break;


	case 'update_edit':
	case 'update':
	case 'update_publish':
		// We need early decoding of these in order to check permissions:
		param( 'post_category', 'integer', true );
		param( 'post_extracats', 'array', array() );
		if( $action == 'update_publish' )
		{
			$post_status = 'published';
			$set_issue_date = 'now';
		}
		else
		{
			param( 'post_status', 'string', 'published' );
		}
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );


		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( false );

		$Plugins->trigger_event( 'AdminBeforeItemEditUpdate', array( 'Item' => & $edited_Item ) );

 		// Params we need for tab switching (in case of error or if we save&edit)
		$tab_switch_params = 'p='.$edited_Item->ID;

		if( $Messages->count('error') )
		{	// There have been some validation errors:
			break;
		}

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'info' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item->content, $edited_Item->title, $edited_Item->ID );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing( $exit_after_save );

		$Messages->add( T_('Post has been updated.'), 'success' );

		if( ! $exit_after_save )
		{	// We want to continue editing...
			break;
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
		break;


	case 'publish':
		// Publish NOW:

		$post_status = 'published';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'item_post!'.$post_status, 'edit', true, $edited_Item );
		$current_User->check_perm( 'edit_timestamp', 'any', true ) ;

		$edited_Item->set( 'status', $post_status );

		$post_date = date('Y-m-d H:i:s', $localtimenow);
		$edited_Item->set( 'datestart', $post_date );
		$edited_Item->set( 'datemodified', $post_date );

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing();

		$Messages->add( T_('Post has been published.'), 'success' );

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
		break;


	case 'deprecate':

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

		// Check permission:
		$current_User->check_perm( 'blog_del_post', '', true, $blog );

		// fp> TODO: non javascript confirmation
		// $AdminUI->title = T_('Deleting post...');

		$Plugins->trigger_event( 'AdminBeforeItemEditDelete', array( 'Item' => & $edited_Item ) );

		if( ! $Messages->count('error') )
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


	case 'list':
		init_list_mode();

		if( $ItemList->single_post )
		{	// We have requested to view a SINGLE specific post:
			$action = 'view';
		}
		break;


	case 'edit_links':
		// Display attachment list

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );
		break;


	case 'unlink':
 		// Delete a link:

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// Unlink File from Item:
		$msg = sprintf( T_('Link has been deleted from &laquo;%s&raquo;.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset($edited_Link);
		$Messages->add( $msg, 'success' );

		// go on to view:
		//$p = $edited_Item->ID;
		//init_list_mode();
		//$action = 'view';
		// REDIRECT / EXIT
		if( $mode == 'iframe' )
		{
	 		header_redirect( regenerate_url( '', 'action=edit_links&mode=iframe&item_ID='.$edited_Item->ID, '', '&' ) );
		}
		else
		{
	 		header_redirect( regenerate_url( '', 'p='.$edited_Item->ID, '', '&' ) );
		}
		break;


	default:
		debug_die( 'unhandled action 2' );
}

/**
 * Initialize list mode; Several actions need this.
 */
function init_list_mode()
{
	global $tab, $Blog, $UserSettings, $ItemList;

	if ( param( 'p', 'integer', NULL ) || param( 'title', 'string', NULL ) )
	{	// Single post requested, do not filter any post types. If the user
		// has clicked a post link on the dashboard and previously has selected
		// a tab which would filter this post, it wouldn't be displayed now.
		$tab = 'full';
	}
	else
	{	// Store/retrieve preferred tab from UserSettings:
		$UserSettings->param_Request( 'tab', 'pref_browse_tab', 'string', NULL, true /* memorize */ );
	}

	/*
	 * Init list of posts to display:
	 */
	load_class('items/model/_itemlist.class.php');

	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL, $UserSettings->get('results_per_page'), 'ItemCache', '', $tab /* filterset name */ ); // COPY (func)

	$ItemList->set_default_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated', 'redirected' ),
		) );

	switch( $tab )
	{
		case 'full':
			$ItemList->set_default_filters( array(
					'types' => NULL, // All types (suited for tab with full posts)
				) );
			break;

		case 'list':
			// Nothing special
			break;

		case 'pages':
			$ItemList->set_default_filters( array(
					'types' => '1000', // Pages
				) );
			break;

		case 'intros':
			$ItemList->set_default_filters( array(
					'types' => '1500,1520,1530,1570,1600', // Intros
				) );
			break;

		case 'podcasts':
			$ItemList->set_default_filters( array(
					'types' => '2000', // Podcasts
				) );
			break;

		case 'links':
			$ItemList->set_default_filters( array(
					'types' => '2', // Links
				) );
			break;

		case 'tracker':
			// In tracker mode, we want a different default sort:
			$ItemList->set_default_filters( array(
					'orderby' => 'priority',
					'order' => 'ASC' ) );
			break;

		default:
			// Delete the pref_browse_tab setting so that the default
			// (full) gets used the next time the user wants to browse
			// a blog.
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
	case 'create_edit':
	case 'create':
	case 'create_publish':
		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_post_statuses', 'edit',
						array( 'ctrl' => 'items', 'action' => 'new' ), NULL, '',
						'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php\', %s )' );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( sprintf( T_('Since this blog has no categories, you cannot post into it. You must <a %s>create categories</a> first.'), 'href="admin.php?ctrl=chapters&amp;blog='.$blog.'"') , 'error' );
			$action = 'nil';
			break;
		}

		/* NOBREAK */

	case 'edit':
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'update_edit':
	case 'update': // on error
	case 'update_publish': // on error
		// Get tab ("simple" or "expert") from Request or UserSettings:
		$tab = $UserSettings->param_Request( 'tab', 'pref_edit_tab', 'string', NULL, true /* memorize */ );

		$AdminUI->add_menu_entries(
				'items',
				array(
						'simple' => array(
							'text' => T_('Simple'),
							'href' => 'admin.php?ctrl=items&amp;action='.$action.'&amp;tab=simple&amp;'.$tab_switch_params,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?tab=simple&amp;blog='.$blog.'\' );',
							// 'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
							),
						'expert' => array(
							'text' => T_('Expert'),
							'href' => 'admin.php?ctrl=items&amp;action='.$action.'&amp;tab=expert&amp;'.$tab_switch_params,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?tab=expert&amp;blog='.$blog.'\' );',
							// 'name' => 'switch_to_expert_tab_nocheckchanges', // no bozo check
							),
					)
			);

		switch( $action )
		{
			case 'edit':
			case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
			case 'update_edit':
			case 'update': // on error
			case 'update_publish': // on error
				if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $edited_Item->blog_ID ) )
				{ // User has no right to delete this post
					break;
				}
				$AdminUI->global_icon( T_('Delete this post'), 'delete', '?ctrl=items&amp;action=delete&amp;post_ID='.$edited_Item->ID,
						 T_('delete'), 4, 3, array(
						 		'onclick' => 'return confirm(\''.TS_('You are about to delete this post!\\nThis cannot be undone!').'\')',
						 		'style' => 'margin-right: 3ex;',	// Avoid misclicks by all means!
						 ) );
				break;
		}

		$AdminUI->global_icon( T_('Cancel editing!'), 'close', $redirect_to, T_('cancel'), 4, 2 );

		break;

	case 'view':
		// We're displaying a SINGLE specific post:
		$AdminUI->title = $AdminUI->title_titlearea = T_('View post & comments');
		break;

	case 'list':
		// We're displaying a list of posts:

		$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'items', 'tab' => $tab, 'filter' => 'restore' ) );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		break;

	case 'edit_links':
		// Embedded iframe:
		$tab = '';
		$mode = 'iframe';
		break;
}

if( !empty($tab) )
{
	$AdminUI->append_path_level( $tab );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

  case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
		$bozo_start_modified = true;	// We want to start with a form being already modified
	case 'new':
	case 'create_edit':
	case 'create':
	case 'create_publish':
	case 'edit':
	case 'update_edit':
	case 'update':	// on error
	case 'update_publish':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		$item_title = $edited_Item->title;
		$item_content = $edited_Item->content;

		// Format content for editing, if we were not already in editing...
		$Plugins_admin = & get_Cache('Plugins_admin');
		$Plugins_admin->unfilter_contents( $item_title /* by ref */, $item_content /* by ref */, $edited_Item->get_renderers_validated() );

		// Display VIEW:
		switch( $tab )
		{
			case 'simple':
				$AdminUI->disp_view( 'items/views/_item_simple.form.php' );
				break;

			case 'expert':
			default:
				$AdminUI->disp_view( 'items/views/_item_expert.form.php' );
				break;
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'view':
	case 'delete':
		// View a single post:

		// Memorize 'p' in case we reload while changing some display settings
		memorize_param( 'p', 'integer', NULL );

 		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// We use the "full" view for displaying single posts:
		$AdminUI->disp_view( 'items/views/_item_list_full.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();

		break;

	case 'edit_links':
		// View attachments
		$AdminUI->disp_view( 'items/views/_item_links.view.php' );
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// fplanque> Note: this is depressing, but I have to put a table back here
		// just because IE supports standards really badly! :'(
		echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr>';

		echo '<td class="browse_left_col">';

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

				case 'list':
				case 'pages':
				case 'intros':
				case 'podcasts':
				default:
					// Display VIEW:
					$AdminUI->disp_view( 'items/views/_item_list_table.view.php' );
					break;
			}

			// TODO: a specific field for the backoffice, at the bottom of the page
			// would be used for moderation rules.
			if( $Blog->get( 'notes' ) )
			{
				$block_item_Widget = & new Widget( 'block_item' );
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

		echo '</td>';

		echo '<td class="browse_right_col">';
			// Display VIEW:
			$AdminUI->disp_view( 'items/views/_item_list_sidebar.view.php' );
		echo '</td>';

		echo '</tr></table>';

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.30  2009/02/13 14:21:03  waltercruz
 * Fixing titles
 *
 * Revision 1.29  2009/01/25 18:59:04  blueyed
 * phpdoc: fix multiple package tags error
 *
 * Revision 1.28  2009/01/24 20:05:28  tblue246
 * - Do not filter post types if a single post is requested (see comment in code for an explanation).
 * - debug_die() when an invalid filterset name is passed via the tab parameter and restore the default value.
 *
 * Revision 1.27  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 * Revision 1.26  2009/01/23 22:08:12  tblue246
 * - Filter reserved post types from dropdown box on the post form (expert tab).
 * - Indent/doc fixes
 * - Do not check whether a post title is required when only e. g. switching tabs.
 *
 * Revision 1.25  2009/01/21 22:26:26  fplanque
 * Added tabs to post browsing admin screen All/Posts/Pages/Intros/Podcasts/Comments
 *
 * Revision 1.24  2008/09/23 05:26:38  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.23  2008/05/29 22:15:48  blueyed
 * typo
 *
 * Revision 1.22  2008/04/14 16:24:39  fplanque
 * use ActionArray[] to make action handlign more robust
 *
 * Revision 1.21  2008/04/13 20:40:08  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.20  2008/04/03 22:03:11  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.19  2008/04/03 15:54:20  fplanque
 * enhanced edit layout
 *
 * Revision 1.18  2008/04/03 14:54:34  fplanque
 * date fixes
 *
 * Revision 1.17  2008/04/03 13:39:14  fplanque
 * fix
 *
 * Revision 1.16  2008/03/22 15:20:20  fplanque
 * better issue time control
 *
 * Revision 1.15  2008/01/22 16:16:48  fplanque
 * bugfix
 *
 * Revision 1.14  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.13  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.12  2007/12/26 17:58:31  fplanque
 * minor
 *
 * Revision 1.11  2007/12/26 11:27:14  yabs
 * ui improvement
 *
 * Revision 1.10  2007/11/29 22:47:15  fplanque
 * tags everywhere + debug
 *
 * Revision 1.9  2007/11/08 17:48:41  blueyed
 * Fixed encoding of redirect_to
 *
 * Revision 1.8  2007/09/29 09:50:32  yabs
 * validation
 *
 * Revision 1.7  2007/09/26 21:53:24  fplanque
 * file manager / file linking enhancements
 *
 * Revision 1.6  2007/09/22 19:23:56  fplanque
 * various fixes & enhancements
 *
 * Revision 1.5  2007/09/04 22:25:18  fplanque
 * fix
 *
 * Revision 1.4  2007/09/04 22:16:33  fplanque
 * in context editing of posts
 *
 * Revision 1.3  2007/09/03 16:44:31  fplanque
 * chicago admin skin
 *
 * Revision 1.2  2007/07/09 23:03:04  fplanque
 * cleanup of admin skins; especially evo
 *
 * Revision 1.1  2007/06/25 11:00:24  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.27  2007/06/11 01:53:54  fplanque
 * fix
 *
 * Revision 1.26  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.25  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.24  2007/05/13 18:49:54  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.23  2007/05/09 01:01:32  fplanque
 * permissions cleanup
 *
 * Revision 1.22  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.21  2007/04/05 22:57:33  fplanque
 * Added hook: UnfilterItemContents
 *
 * Revision 1.20  2007/03/26 14:21:30  fplanque
 * better defaults for pages implementation
 *
 * Revision 1.19  2007/03/21 02:21:37  fplanque
 * item controller: highlight current (step 2)
 *
 * Revision 1.18  2007/03/21 01:44:51  fplanque
 * item controller: better return to current filterset - step 1
 *
 * Revision 1.17  2007/03/11 23:56:03  fplanque
 * fixed some post editing oddities / variable cleanup (more could be done)
 *
 * Revision 1.16  2007/03/07 02:37:43  fplanque
 * OMG I decided that pregenerating the menus was getting to much of a PITA!
 * It's a zillion problems with the permissions.
 * This will be simplified a lot. Enough of these crazy stuff.
 *
 * Revision 1.15  2007/03/02 01:36:51  fplanque
 * small fixes
 *
 * Revision 1.14  2007/02/23 00:21:23  blueyed
 * Fixed Plugins::get_next() if the last Plugin got unregistered; Added AdminBeforeItemEditDelete hook
 *
 * Revision 1.13  2007/01/16 00:44:42  fplanque
 * don't use $admin_email in  the app
 *
 * Revision 1.12  2006/12/24 00:42:14  fplanque
 * refactoring / Blog::get_default_cat_ID()
 *
 * Revision 1.11  2006/12/23 23:37:35  fplanque
 * refactoring / Blog::get_default_cat_ID()
 *
 * Revision 1.10  2006/12/23 23:15:22  fplanque
 * refactoring / Blog::get_allowed_item_status()
 *
 * Revision 1.9  2006/12/18 03:20:41  fplanque
 * _header will always try to set $Blog.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.8  2006/12/18 01:43:25  fplanque
 * minor bugfix
 *
 * Revision 1.7  2006/12/14 00:01:49  fplanque
 * land in correct collection when opening FM from an Item
 *
 * Revision 1.6  2006/12/12 23:23:29  fplanque
 * finished post editing v2.0
 *
 * Revision 1.5  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.4  2006/12/12 18:04:53  fplanque
 * fixed item links
 *
 * Revision 1.3  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.2  2006/12/12 00:39:46  fplanque
 * The bulk of item editing is done.
 *
 * Revision 1.1  2006/12/11 18:04:52  fplanque
 * started clean "1-2-3-4" item editing
 */
?>
