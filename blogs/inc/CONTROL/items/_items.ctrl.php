<?php
/**
 * This file implements the UI controller for managing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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

param( 'action', 'string', 'list' );


/*
 * Init the objects we want to work on.
 *
 * Autoselect a blog where we have PERMISSION to browse (preferably the last used blog):
 * Note: for some actions, we'll get the blog from the post ID
 */
switch( $action )
{
	case 'unlink':
	  param( 'link_ID', 'integer', true );
		$LinkCache = & get_Cache( 'LinkCache' );
		if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) !== false )
		{	// We have a link, get the Item it is attached to:
			$edited_Item = & $edited_Link->Item;

			// Load the blog we're in:
			$Blog = $edited_Item->get_Blog();
			$blog = $Blog->ID;
		}
		else
		{	// We could not find the link to edit:
			$Messages->head = T_('Cannot edit link!');
			$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
			unset( $edited_Link );
			unset( $link_ID );
			$action = 'nil';
		}
		break;

	case 'edit':
 		// Load post to edit:
		param( 'p', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $p );

		// Load the blog we're in:
		$Blog = $edited_Item->get_Blog();
		$blog = $Blog->ID;
		break;

	case 'update':
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
		$Blog = $edited_Item->get_Blog();
		$blog = $Blog->ID;
		break;

	case 'new':
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
	case 'create':
	case 'list':
		if( $action == 'list' )
		{	// We only need view permission
			$blog = autoselect_blog( $blog, 'blog_ismember', 1 );
		}
		else
		{	// We need posting permission
			$blog = autoselect_blog( $blog, 'blog_post_statuses', 'any' );
		}

		if( ! $blog  )
		{ // No blog could be selected
			$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'),
											 'href="mailto:'.$admin_email.'?subject=b2evo-promotion"' ), 'error' );
			$action = 'nil';
		}
		else
		{ // We could select a valid blog which we have permission to access:
			$BlogCache = & get_Cache( 'BlogCache' );
			$Blog = & $BlogCache->get_by_ID( $blog );
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
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
		// New post form  (can be a bookmarklet form if mode == bookmarklet )

		load_class( 'MODEL/items/_item.class.php' );
		$edited_Item = & new Item();

		$edited_Item->blog_ID = $blog;

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		// Also used by bookmarklet
		$edited_Item->load_from_Request(); // needs Blog set

		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok...
		param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
		if( ! $current_User->check_perm( 'blog_post_statuses', $post_status, false, $blog ) )
		{ // We need to find another one:
			if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
				$post_status = 'published';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
				$post_status = 'protected';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
				$post_status = 'private';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
				$post_status = 'draft';
			else
				$post_status = 'deprecated';
		}

		param( 'post_extracats', 'array', array() );
		param( 'edit_date', 'integer', 0 ); // checkbox
		$default_main_cat = param( 'post_category', 'integer', $edited_Item->main_cat_ID );
		if( $default_main_cat && $allow_cross_posting < 3 && get_catblog($default_main_cat) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing: setting it to 0 uses the first cat in the list
			$default_main_cat = 0;
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

		// Trackback addresses (never saved into item)
 		param( 'trackback_url', 'string', '' );

		// Page title:
		$AdminUI->title = T_('New post in blog:').' ';
		$AdminUI->title_titlearea = $AdminUI->title;
		$js_doc_title_prefix = $AdminUI->title;

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;
		break;


	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
		// This is somewhat in between new and edit...

		// Check permission based on DB status:
		$current_User->check_perm( 'blog_post_statuses', $edited_Item->get( 'status' ), true, $blog );

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		$edited_Item->load_from_Request(); // needs Blog set

		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok...
		param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
		if( ! $current_User->check_perm( 'blog_post_statuses', $post_status, false, $blog ) )
		{ // We need to find another one:
			if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
				$post_status = 'published';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
				$post_status = 'protected';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
				$post_status = 'private';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
				$post_status = 'draft';
			else
				$post_status = 'deprecated';
		}

		param( 'post_extracats', 'array', array() );
		param( 'edit_date', 'integer', 0 ); // checkbox
		$default_main_cat = param( 'post_category', 'integer', $edited_Item->main_cat_ID );
		if( $default_main_cat && $allow_cross_posting < 3 && get_catblog($default_main_cat) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing: setting it to 0 uses the first cat in the list
			$default_main_cat = 0;
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

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
		$post_status = $edited_Item->get( 'status' );
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p );

  	$trackback_url = '';

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;


	case 'create':
		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );
		param( 'post_category', 'integer', true );
		param( 'post_extracats', 'array', array() );
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post_statuses', $post_status, true, $post_extracats );


		// CREATE NEW POST:
		load_class( 'MODEL/items/_item.class.php' );
		$edited_Item = & new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request();

		// TODO: fp> Add a radio into blog settings > Features > Post title: () required () optional () none
		/*
		if( empty($edited_Item->title) )
		{ // post_title is "TEXT NOT NULL" and a title makes sense anyway
			$Messages->add( T_('Please give a title.') );
		}
		*/

		$Plugins->trigger_event( 'AdminBeforeItemEditCreate', array( 'Item' => & $edited_Item ) );

		if( $Messages->count('error') )
		{	// There hace been some validation errors:
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
				load_funcs( '_misc/_trackback.funcs.php' );
				trackbacks( $trackback_url, $edited_Item->content, $edited_Item->title, $edited_Item->ID);
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing();

		$Messages->add( T_('Post has been created.'), 'success' );

		// Switch to list mode:
		$action = 'list';
		init_list_mode();
		break;


	case 'update':
		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );
		param( 'post_category', 'integer', true );
		param( 'post_extracats', 'array', array() );
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post_statuses', $post_status, true, $post_extracats );


		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( true );

		$Plugins->trigger_event( 'AdminBeforeItemEditUpdate', array( 'Item' => & $edited_Item ) );

		if( $Messages->count('error') )
		{	// There have been some validation errors:
			// Params we need for tab switching:
			$tab_switch_params = 'p='.$post_ID;
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
				load_funcs( '_misc/_trackback.funcs.php' );
				trackbacks( $trackback_url, $edited_Item->content, $edited_Item->title, $edited_Item->ID );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing();

		$Messages->add( T_('Post has been updated.'), 'success' );

		// Switch to list mode:
		$action = 'list';
		init_list_mode();
		break;


	case 'publish':
		// Publish NOW:

		$post_status = 'published';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );
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

		// Switch to list mode:
		$action = 'list';
		init_list_mode();
		break;


	case 'deprecate':

		$post_status = 'deprecated';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		$Messages->add( T_('Post has been deprecated.'), 'success' );

		// Switch to list mode:
		$action = 'list';
		init_list_mode();
		break;


	case 'delete':
		// Delete an Item:

		// Check permission:
		$current_User->check_perm( 'blog_del_post', '', true, $blog );

		// fp> TODO: non javascript confirmation
		// $AdminUI->title = T_('Deleting post...');

		// DELETE POST FROM DB:
		$edited_Item->dbdelete();

		$Messages->add( T_('Post has been deleted.'), 'success' );

		// Switch to list mode:
		$action = 'list';
		init_list_mode();
		break;


	case 'list':
		init_list_mode();

		if( $ItemList->single_post )
		{	// We have requested to view a SINGLE specific post:
			$action = 'view';
		}
		break;


	case 'unlink':
 		// Delete a link:

		// Check permission:
		$current_User->check_perm( 'item', 'edit', true, $edited_Item );

		// Unlink File from Item:
		$msg = sprintf( T_('Link has been deleted from &laquo;%s&raquo;.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset($edited_Link);
		$Messages->add( $msg, 'success' );

		// go on to view:
		$p = $edited_Item->ID;
		init_list_mode();
		$action = 'view';
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

	// Store/retrieve preferred tab from UserSettings:
	$UserSettings->param_Request( 'tab', 'pref_browse_tab', 'string', NULL, true /* memorize */ );

	/*
	 * Init list of posts to display:
	 */
	load_class( 'MODEL/items/_itemlist2.class.php' );

	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL ); // COPY (func)

	$ItemList->set_default_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated' ),
		) );

	if( $tab == 'tracker' )
	{	// In tracker mode, we want a different default sort:
		$ItemList->set_default_filters( array(
				'orderby' => 'priority',
				'order' => 'ASC' ) );
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
		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_post_statuses', 'any',
						'admin.php?ctrl=items&amp;action=new&amp;blog=%d', NULL, '',
						'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php\', %d )' );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'error' );
			$action = 'nil';
			break;
		}

		/* NOBREAK */

	case 'edit':
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'update': // on error
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
		break;

	case 'view':
		// We're displaying a SINGLE specific post:
		$AdminUI->title = $AdminUI->title_titlearea = T_('View post & comments');
		break;

	case 'list':
		// We're displaying a list of posts:

		$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog:');

		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_ismember', 1, 'admin.php?ctrl=items&amp;blog=%d&amp;tab='.$tab.'&amp;filter=restore' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		break;
}

$AdminUI->set_path( 'items', !empty($tab) ? $tab : NULL );

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
	case 'create':
	case 'edit':
	case 'update':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		switch( $tab )
		{
			case 'simple':
				$AdminUI->disp_view( 'items/_item_simple.form.php' );
				break;

			case 'expert':
			default:
				$AdminUI->disp_view( 'items/_item.form.php' );
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
		$AdminUI->disp_view( 'items/_browse_posts_exp.inc.php' );

		// End payload block:
		$AdminUI->disp_payload_end();

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
				case 'full':
					// Display VIEW:
					$AdminUI->disp_view( 'items/_browse_posts_exp.inc.php' );
					break;

				case 'list':
					// Display VIEW:
					$AdminUI->disp_view( 'items/_browse_posts_list2.view.php' );
					break;

				case 'tracker':
					// Display VIEW:
					$AdminUI->disp_view( 'items/_browse_tracker.inc.php' );
					break;
			}
		echo '</td>';

		echo '<td class="browse_right_col">';
			// Display VIEW:
			$AdminUI->disp_view( 'items/_browse_posts_sidebar.inc.php' );
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
 *
 */
?>