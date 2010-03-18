<?php
/**
 * This file implements the UI controller for managing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
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

global $dispatcher;

$action = param_action( 'list' );

$AdminUI->set_path( 'items' );	// Sublevel may be attached below


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

switch( $action )
{
	case 'edit_links':
		param( 'item_ID', 'integer', true, true );
		$ItemCache = & get_ItemCache();
		$edited_Item = & $ItemCache->get_by_ID( $item_ID );
		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		break;

	case 'set_item_link_position':
		param('link_position', 'string', true);
	case 'unlink':
	case 'link_move_up':
	case 'link_move_down':
		// Name of the iframe we want some action to come back to:
		param( 'iframe_name', 'string', '', true );

		// TODO fp> when moving an "after_more" above a "teaser" img, it should change to "teaser" too.
		// TODO fp> when moving a "teaser" below an "aftermore" img, it should change to "aftermore" too.

		param( 'link_ID', 'integer', true );
		$LinkCache = & get_LinkCache();
		if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) !== false )
		{	// We have a link, get the Item it is attached to:
			$edited_Item = & $edited_Link->Item;

			// Load the blog we're in:
			$Blog = & $edited_Item->get_Blog();
			set_working_blog( $Blog->ID );
		}
		else
		{	// We could not find the link to edit:
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Link') ), 'error' );
			unset( $edited_Link );
			unset( $link_ID );
			if( $mode == 'iframe' )
			{
				$action = 'edit_links';
				param( 'item_ID', 'integer', true, true );
				$ItemCache = & get_ItemCache();
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
	case 'history':
 		// Load post to edit:
		param( 'p', 'integer', true, true );
		$ItemCache = & get_ItemCache();
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
		$ItemCache = & get_ItemCache();
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
	case 'new_mass':
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
				$BlogCache = & get_BlogCache();
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

$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Contents'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );

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
		// New post form  (can be a bookmarklet form if mode == bookmarklet )

		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		// Also used by bookmarklet
		$edited_Item->load_from_Request( true ); // needs Blog set

		$edited_Item->status = param( 'post_status', 'string', NULL );		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status );

		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave( $post_category, $post_extracats );

		$edited_Item->set('main_cat_ID', $post_category);
		if( $edited_Item->main_cat_ID && $allow_cross_posting < 3 && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

 		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
 		param( 'trackback_url', 'string', '' );

		// Page title:
		$AdminUI->title = T_('New post:').' ';
		if( param( 'item_typ_ID', 'integer', NULL ) !== NULL )
		{
			switch( $item_typ_ID )
			{
				case 1000:
					$AdminUI->title = T_('New page:').' ';
					break;

				case 1600:
					$AdminUI->title = T_('New intro:').' ';
					break;

				case 2000:
					$AdminUI->title = T_('New podcast episode:').' ';
					break;

				case 3000:
					$AdminUI->title = T_('New link:').' ';
					break;
			}
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

		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave( $post_category, $post_extracats );

		$edited_Item->set('main_cat_ID', $post_category);
		if( $edited_Item->main_cat_ID && $allow_cross_posting < 3 && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

 		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
 		param( 'trackback_url', 'string', '' );

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;

	case 'history':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p ); // NOTE: dh> using $edited_Item->get_Chapters here instead fails (empty list, since no postIDlist).

		$item_tags = implode( ', ', $edited_Item->get_tags() );
		$trackback_url = '';

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;


	case 'create_edit':
	case 'create':
	case 'create_publish':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );

		if( $action == 'create_publish' )
		{
			if( ! in_array( $post_status, array( 'private', 'protected' ) ) )
			{	// Only use "published" if something other than "private" or "protected" has been selected:
				$post_status = 'published';
			}
			else
			{
				// Tblue> Message contents could be confusing.
				$Messages->add( sprintf( T_( 'The post has been created but not published because it seems like you wanted to set its status to "%s" instead.  If you really want to make it public, manually change its status to "Published" and click the "Save" button.' ),
					$post_status == 'protected' ? T_( 'Protected' ) : T_( 'Private' ) ), 'error' );
			}

		}

		// Check if new category was started to create. If yes check if it is valid.
		check_categories( $post_category, $post_extracats );
		
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );
		// Check permission on post type:
		check_perm_posttype( $post_extracats );

		// CREATE NEW POST:
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( /* editing? */ ($action == 'create_edit'), /* creating? */ true );

		$Plugins->trigger_event( 'AdminBeforeItemEditCreate', array( 'Item' => & $edited_Item ) );

		if( !empty( $mass_create ) )
		{	// ------ MASS CREATE ------
			$Items = & create_multiple_posts( $edited_Item, param( 'paragraphs_linebreak', 'boolean', 0 ) );
			if( empty( $Items ) )
			{
				param_error( 'content', T_( 'Content must not be empty.' ) );
			}
		}

		if( $Messages->count('error') )
		{
			if( !empty( $mass_create ) )
			{
				$action = 'new_mass';
			}
			// There have been some validation errors:
			// Params we need for tab switching:
			$tab_switch_params = 'blog='.$blog;
			break;
		}

		if( isset( $Items ) && !empty( $Items ) )
		{	// We can create multiple posts from single post
			foreach( $Items as $edited_Item )
			{	// INSERT NEW POST INTO DB:
				$edited_Item->dbinsert();
			}
		}
		else
		{	// INSERT NEW POST INTO DB:
			$edited_Item->dbinsert();
		}

		param( 'is_attachments', 'string' );
		if( !empty( $is_attachments ) && $is_attachments === 'true' )
		{ // Set session variable to dynamically create js popup:
			$Session->set('create_edit_attachment', true);
		}

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
				trackbacks( $trackback_url, $edited_Item );
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

		if( $edited_Item->status == 'published' )
		{	// fp> I noticed that after publishing a new post, I always want to see how the blog looks like
			// If anyone doesn't want that, we can make this optional...
			// sam2kb> Please make this optional, this is really annoying when you create more than one post or when you publish draft images created from FM.
			
			// Where do we want to go after publishing?
			if( ! $edited_Item->Blog->get_setting( 'enable_goto_blog' ) )
			{	// redirect to posts list:
				header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
			}
			// go to blog:
			$edited_Item->load_Blog();
			$redirect_to = $edited_Item->Blog->gen_blogurl();
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
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check edit permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );

		if( $action == 'update_publish' )
		{
			if( ! in_array( $post_status, array( 'private', 'protected' ) ) )
			{	/* Only use "published" if something other than "private"
				   or "protected" has been selected: */
				$post_status = 'published';
			}
			else
			{
				// Tblue> Message contents could be confusing.
				$Messages->add( sprintf( T_( 'The post has been updated but not published because it seems like you wanted to set its status to "%s" instead. If you really want to make it public, manually change its status to "Published" and click the "Save" button.' ),
									$post_status == 'protected' ? T_( 'Protected' ) : T_( 'Private' ) ), 'error' );
			}

		}

		// Check if new category was started to create.  If yes check if it is valid.
		$isset_category = check_categories( $post_category, $post_extracats );
		
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );
		// Check permission on post type:
		check_perm_posttype( $post_extracats );

		// Is this post already published?
		$was_published = $edited_Item->status == 'published';

		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		
		if( $isset_category )
		{ // we change the categories only if the check was succesfull
			$edited_Item->set( 'main_cat_ID', $post_category );
			$edited_Item->set( 'extra_cat_IDs', $post_extracats );
		}

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
				trackbacks( $trackback_url, $edited_Item );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing( $exit_after_save );

		$Messages->add( T_('Post has been updated.'), 'success' );

		if( ! $exit_after_save )
		{	// We want to continue editing...
			break;
		}

		/* fp> I noticed that after publishing a new post, I always want
		 *     to see how the blog looks like. If anyone doesn't want that,
		 *     we can make this optional...
		 */
		if( ! $was_published && $edited_Item->status == 'published' )
		{	// The post's last status wasn't "published", but we're going to publish it now.
			
			if( ! $edited_Item->Blog->get_setting( 'enable_goto_blog' ) )
			{	// redirect to posts list

				//Set highlight
				$Session->set('highlight_id', $edited_Item->ID);

				header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
			}
			
			$edited_Item->load_Blog();
			$redirect_to = $edited_Item->Blog->gen_blogurl();
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to, 303 );
		/* EXITED */
		break;


	case 'publish':
		// Publish NOW:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		$post_status = 'published';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'item_post!'.$post_status, 'edit', true, $edited_Item );
		$current_User->check_perm( 'edit_timestamp', 'any', true );

		$edited_Item->set( 'status', $post_status );

// fp> TODO: remove seconds ONLY if date is in the future
		$edited_Item->set( 'datestart', remove_seconds($localtimenow) );
		$edited_Item->set( 'datemodified', date('Y-m-d H:i:s', $localtimenow) );

		// UPDATE POST IN DB:
		$edited_Item->dbupdate();

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing();

		$Messages->add( T_('Post has been published.'), 'success' );

		// fp> I noticed that after publishing a new post, I always want to see how the blog looks like
		// If anyone doesn't want that, we can make this optional...
		$edited_Item->load_Blog();
		$redirect_to = $edited_Item->Blog->gen_blogurl();

		// REDIRECT / EXIT
		if( $edited_Item->Blog->get_setting( 'enable_goto_blog' ) )
		{	// Redirect to blog:
			header_redirect( $redirect_to );
		}
		else
		{
			// Redirect to posts list:
			header_redirect( regenerate_url( '', '&highlight='.$edited_Item->ID, '', '&' ) );
		}
		// Switch to list mode:
		// $action = 'list';
		// init_list_mode();
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

		// Add JavaScript.
		// TODO: dh> move this into a .js file specific to the items page(s)?!
		global $htsrv_url;
		add_js_headline('
		function evo_display_position_onchange() {
			var oThis = this;
			jQuery.get(\''.$htsrv_url.'async.php?action=set_item_link_position&link_ID=\' + this.id.substr(17)+\'&link_position=\'+this.value+\'&'.url_crumb('itemlink').'\', {
			}, function(r, status) {
				if( r == "OK" ) {
					evoFadeSuccess( jQuery(oThis.form).closest(\'tr\') );
					jQuery(oThis.form).closest(\'td\').removeClass(\'error\');
				} else {
					jQuery(oThis).val(r);
					evoFadeFailure( jQuery(oThis.form).closest(\'tr\') );
					jQuery(oThis.form).closest(\'td\').addClass(\'error\');
				}
			} );
			return false;
		}');
		break;


	case 'unlink':
 		// Delete a link:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

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


	case 'link_move_up':
	case 'link_move_down':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$itemLinks = $edited_Item->get_Links();

		// TODO fp> when moving an "after_more" above a "teaser" img, it should change to "teaser" too.
		// TODO fp> when moving a "teaser" below an "aftermore" img, it should change to "aftermore" too.

		// Switch order with the next/prev one
		if( $action == 'link_move_up' )
		{
			$switchcond = 'return ($loop_Link->get("order") > $i
				&& $loop_Link->get("order") < '.$edited_Link->get("order").');';
			$i = -1;
		}
		else
		{
			$switchcond = 'return ($loop_Link->get("order") < $i
				&& $loop_Link->get("order") > '.$edited_Link->get("order").');';
			$i = PHP_INT_MAX;
		}
		foreach( $itemLinks as $loop_Link )
		{ // find nearest order
			if( $loop_Link == $edited_Link )
				continue;

			if( eval($switchcond) )
			{
				$i = $loop_Link->get('order');
				$switch_Link = $loop_Link;
			}
		}
		if( $i > -1 && $i < PHP_INT_MAX )
		{ // switch
			$switch_Link->set('order', $edited_Link->get('order'));

			// HACK: go through order=0 to avoid duplicate key conflict
			$edited_Link->set('order', 0);
			$edited_Link->dbupdate( true );
			$switch_Link->dbupdate( true );

			$edited_Link->set('order', $i);
			$edited_Link->dbupdate( true );


			if( $action == 'link_move_up' )
				$msg = T_('Link has been moved up.');
			else
				$msg = T_('Link has been moved down.');

			$Messages->add( $msg, 'success' );
		}
		else
		{
			$Messages->add( T_('Link order has not been changed.'), 'note' );
		}

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


	case 'set_item_link_position':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID($link_ID);

		if( $Link->set('position', $link_position) && $Link->dbupdate() )
		{
			$Messages->add( T_('Link position has been changed.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Link position has not been changed.'), 'note' );
		}

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
		debug_die( 'unhandled action 2: '.htmlspecialchars($action) );
}


/**
 * Initialize list mode; Several actions need this.
 */
function init_list_mode()
{
	global $tab, $Blog, $UserSettings, $ItemList, $AdminUI;

	if ( param( 'p', 'integer', NULL ) || param( 'title', 'string', NULL ) )
	{	// Single post requested, do not filter any post types. If the user
		// has clicked a post link on the dashboard and previously has selected
		// a tab which would filter this post, it wouldn't be displayed now.
		$tab = 'full';
	}
	else
	{	// Store/retrieve preferred tab from UserSettings:
		$UserSettings->param_Request( 'tab', 'pref_browse_tab', 'string', NULL, true /* memorize */, true /* force */ );
	}

	/*
	 * Init list of posts to display:
	 */
	load_class( 'items/model/_itemlist.class.php', 'ItemList2' );

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
			// $AdminUI->breadcrumbpath_add( T_('All items'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'list':
			// Nothing special
			$AdminUI->breadcrumbpath_add( T_('Regular posts'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'pages':
			$ItemList->set_default_filters( array(
					'types' => '1000', // Pages
				) );
 			$AdminUI->breadcrumbpath_add( T_('Pages'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'intros':
			$ItemList->set_default_filters( array(
					'types' => '1500,1520,1530,1570,1600', // Intros
				) );
 			$AdminUI->breadcrumbpath_add( T_('Intro posts'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'podcasts':
			$ItemList->set_default_filters( array(
					'types' => '2000', // Podcasts
				) );
 			$AdminUI->breadcrumbpath_add( T_('Podcasts'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

		case 'links':
			$ItemList->set_default_filters( array(
					'types' => '3000', // Links
				) );
 			$AdminUI->breadcrumbpath_add( T_('Links'), '?ctrl=items&amp;blog=$blog$&amp;tab='.$tab.'&amp;filter=restore' );
			break;

/* see note soemwhere else
		case 'custom':
			$ItemList->set_default_filters( array(
					'types' => '-1,1000,1500,1520,1530,1570,1600,2000,3000,4000,5000', // Links
				) );
			break;
*/

		case 'tracker':
			// In tracker mode, we want a different default sort:
			$ItemList->set_default_filters( array(
					'orderby' => 'priority',
					'order' => 'ASC' ) );
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
	case 'create_edit':
	case 'create':
	case 'create_publish':
		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_post_statuses', 'edit',
						array( 'ctrl' => 'items', 'action' => 'new' ), NULL, '',
						'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'\', %s )' );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( sprintf( T_('Since this blog has no categories, you cannot post into it. You must <a %s>create categories</a> first.'), 'href="'.$dispatcher.'?ctrl=chapters&amp;blog='.$blog.'"') , 'error' );
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
		$tab = $UserSettings->param_Request( 'tab', 'pref_edit_tab', 'string', NULL, true /* memorize */, true /* force */ );

		$AdminUI->add_menu_entries(
				'items',
				array(
						'simple' => array(
							'text' => T_('Simple'),
							'href' => $dispatcher.'?ctrl=items&amp;action='.$action.'&amp;tab=simple&amp;'.$tab_switch_params,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'?tab=simple&amp;blog='.$blog.'\' );',
							// 'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
							),
						'expert' => array(
							'text' => T_('Expert'),
							'href' => $dispatcher.'?ctrl=items&amp;action='.$action.'&amp;tab=expert&amp;'.$tab_switch_params,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'?tab=expert&amp;blog='.$blog.'\' );',
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
				if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $edited_Item->get_blog_ID() ) )
				{ // User has no right to delete this post
					break;
				}
				$AdminUI->global_icon( T_('Delete this post'), 'delete', '?ctrl=items&amp;action=delete&amp;post_ID='.$edited_Item->ID.'&amp;'.url_crumb('item'),
						 T_('delete'), 4, 3, array(
						 		'onclick' => 'return confirm(\''.TS_('You are about to delete this post!\\nThis cannot be undone!').'\')',
						 		'style' => 'margin-right: 3ex;',	// Avoid misclicks by all means!
						 ) );
				break;
		}

		$AdminUI->global_icon( T_('Cancel editing!'), 'close', $redirect_to, T_('cancel'), 4, 2 );

		break;

	case 'new_mass':

		$AdminUI->set_coll_list_params( 'blog_post_statuses', 'edit',
						array( 'ctrl' => 'items', 'action' => 'new' ), NULL, '',
						'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$dispatcher.'\', %s )' );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( sprintf( T_('Since this blog has no categories, you cannot post into it. You must <a %s>create categories</a> first.'), 'href="'.$dispatcher.'?ctrl=chapters&amp;blog='.$blog.'"') , 'error' );
			$action = 'nil';
			break;
		}

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

// Load the appropriate blog navigation styles (including calendar, comment forms...):
require_css( $rsc_url.'css/blog_base.css' );

// Load the appropriate ITEM/POST styles depending on the blog's skin:
if( ! empty( $Blog->skin_ID) )
{
	$SkinCache = & get_SkinCache();
	/**
	 * @var Skin
	 */
	$Skin = $SkinCache->get_by_ID( $Blog->skin_ID );
	require_css( $skins_url.$Skin->folder.'/item.css' );		// fp> TODO: this needs to be a param... "of course" -- if none: else item_default.css ?
				// else: $item_css_url = $rsc_url.'css/item_base.css';
}
// else item_default.css ? is it still possible to have no skin set?


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
		$Plugins_admin = & get_Plugins_admin();
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


	case 'new_mass':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		$item_title = $edited_Item->title;
		$item_content = $edited_Item->content;

		// Format content for editing, if we were not already in editing...
		$Plugins_admin = & get_Plugins_admin();
		$Plugins_admin->unfilter_contents( $item_title /* by ref */, $item_content /* by ref */, $edited_Item->get_renderers_validated() );

		$AdminUI->disp_view( 'items/views/_item_mass.form.php' );

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
		// Memorize 'action' for prev/next links
		memorize_param( 'action', 'string', NULL );
		
		// View attachments
		$AdminUI->disp_view( 'items/views/_item_links.view.php' );
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
 * Revision 1.98  2010/03/18 03:32:49  sam2kb
 * Memorize action for prev/next links in attachments iframe
 *
 * Revision 1.97  2010/03/12 10:20:27  efy-asimo
 * Don't let to create a post with no title if, always needs a title is set
 *
 * Revision 1.96  2010/03/09 11:30:19  efy-asimo
 * create categories on the fly -  fix
 *
 * Revision 1.95  2010/03/04 19:36:04  fplanque
 * minor/doc
 *
 * Revision 1.94  2010/03/04 16:40:34  fplanque
 * minor
 *
 * Revision 1.93  2010/02/26 22:15:53  fplanque
 * whitespace/doc/minor
 *
 * Revision 1.91  2010/02/08 17:53:05  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.90  2010/02/06 11:48:32  efy-yury
 * add checkbox 'go to blog after posting' in blog settings
 *
 * Revision 1.89  2010/02/05 09:51:27  efy-asimo
 * create categories on the fly
 *
 * Revision 1.88  2010/02/02 21:16:35  efy-yury
 * update: attachments popup now opens when pushed the button 'Save and start attaching files'
 *
 * Revision 1.87  2010/01/30 18:55:28  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.86  2010/01/30 10:29:07  efy-yury
 * add: crumbs
 *
 * Revision 1.85  2010/01/21 18:16:49  efy-yury
 * update: fadeouts
 *
 * Revision 1.84  2010/01/18 20:14:13  efy-yury
 * update items: crumbs
 *
 * Revision 1.83  2010/01/03 18:52:57  fplanque
 * crumbs...
 *
 * Revision 1.82  2009/12/29 18:44:23  sam2kb
 * Trackbacks use $Item->get_excerpt()
 *
 * Revision 1.81  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.80  2009/12/08 20:16:12  fplanque
 * Better handling of the publish! button on post forms
 *
 * Revision 1.79  2009/12/06 22:55:17  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.78  2009/12/01 03:45:37  fplanque
 * multi dimensional invalidation
 *
 * Revision 1.77  2009/11/27 12:29:06  efy-maxim
 * drop down
 *
 * Revision 1.76  2009/11/23 21:50:36  efy-maxim
 * ajax dropdown
 *
 * Revision 1.75  2009/11/20 21:59:04  sam2kb
 * doc
 *
 * Revision 1.74  2009/10/27 22:40:22  fplanque
 * removed UGLY UGLY UGLY messages from iframe
 *
 * Revision 1.73  2009/10/19 13:28:13  efy-maxim
 * paragraphs at each line break or separate posts with a blank line
 *
 * Revision 1.72  2009/10/18 20:46:27  fplanque
 * no message
 *
 * Revision 1.71  2009/10/18 11:29:42  efy-maxim
 * 1. mass create in 'All' tab; 2. "Text Renderers" and "Comments"
 *
 * Revision 1.70  2009/10/13 23:26:16  blueyed
 * Drop special handling of link_position + link_order: it is confusing, and will not scale well with more link_positions probably.
 *
 * Revision 1.69  2009/10/13 00:24:28  blueyed
 * Cleanup attachment position handling. Make it work for non-JS.
 *
 * Revision 1.68  2009/10/12 11:59:43  efy-maxim
 * Mass create
 *
 * Revision 1.67  2009/10/11 03:00:10  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.66  2009/09/29 02:03:41  sam2kb
 * Use date_i18n() for $item_issue_date, otherwise regional dates don't validate
 * See http://forums.b2evolution.net/viewtopic.php?t=19743
 *
 * Revision 1.65  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.64  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.63  2009/09/20 18:13:19  fplanque
 * doc
 *
 * Revision 1.62  2009/09/20 13:59:13  waltercruz
 * Adding a tab to show custom types (will be displayed only if you have custom types)
 *
 * Revision 1.61  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.60  2009/09/14 13:16:42  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.59  2009/09/13 21:29:21  blueyed
 * MySQL query cache optimization: remove information about seconds from post_datestart and item_issue_date.
 *
 * Revision 1.58  2009/08/30 19:54:25  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.57  2009/08/29 12:23:56  tblue246
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
 * Revision 1.56  2009/08/23 20:08:26  tblue246
 * - Check extra categories when validating post type permissions.
 * - Removed User::check_perm_catusers() + Group::check_perm_catgroups() and modified User::check_perm() to perform the task previously covered by these two methods, fixing a redundant check of blog group permissions and a malfunction introduced by the usage of Group::check_perm_catgroups().
 *
 * Revision 1.55  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.54  2009/08/17 00:48:41  sam2kb
 * Fixed "Unknown filterset []" bug (save default params to UserSettings)
 * See http://forums.b2evolution.net/viewtopic.php?t=19440
 *
 * Revision 1.53  2009/07/17 22:33:26  tblue246
 * minor/doc
 *
 * Revision 1.52  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.51  2009/07/06 22:49:12  fplanque
 * made some small changes on "publish now" handling.
 * Basically only display it for drafts everywhere.
 *
 * Revision 1.50  2009/07/06 16:52:15  tblue246
 * minor
 *
 * Revision 1.49  2009/07/06 16:04:07  tblue246
 * Moved echo_publishnowbutton_js() to _item.funcs.php
 *
 * Revision 1.48  2009/07/06 13:45:05  tblue246
 * PHPdoc for echo_publishnowbutton_js().
 *
 * Revision 1.47  2009/07/06 13:37:16  tblue246
 * - Backoffice, write screen:
 * 	- Hide the "Publish NOW !" button using JavaScript if the post types "Protected" or "Private" are selected.
 * 	- Do not publish draft posts whose post status has been set to either "Protected" or "Private" and inform the user (note).
 * - Backoffice, post lists:
 * 	- Only display the "Publish NOW!" button for draft posts.
 *
 * Revision 1.46  2009/05/21 12:34:39  fplanque
 * Options to select how much content to display (excerpt|teaser|normal) on different types of pages.
 *
 * Revision 1.45  2009/05/20 20:09:01  tblue246
 * When updating and publishing a post, only redirect to the blog when the post's status wasn't set to 'published' before.
 *
 * Revision 1.44  2009/05/20 17:55:13  tblue246
 * Comment about "redirect to blog after post has been published"
 *
 * Revision 1.43  2009/05/20 14:12:25  fplanque
 * The blog is now always displayed after publishign a post.
 *
 * Revision 1.42  2009/05/18 03:59:39  fplanque
 * minor/doc
 *
 * Revision 1.41  2009/05/18 02:59:15  fplanque
 * Skins can now have an item.css file to specify content formats. Used in TinyMCE.
 * Note there are temporarily too many CSS files.
 * Two ways of solving is: smart resource bundles and/or merge files that have only marginal benefit in being separate
 *
 * Revision 1.40  2009/03/13 03:45:02  fplanque
 * there is no bug. rollback.
 *
 * Revision 1.39  2009/03/13 03:07:55  waltercruz
 * fixing bug in listing
 *
 * Revision 1.38  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.37  2009/03/03 21:32:49  blueyed
 * TODO/doc about cat_load_postcats_cache
 *
 * Revision 1.36  2009/02/26 22:02:02  blueyed
 * Fix creating new item, broken with lazily handling blog_ID/main_cat_ID
 *
 * Revision 1.35  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.34  2009/02/24 22:58:19  fplanque
 * Basic version history of post edits
 *
 * Revision 1.33  2009/02/22 23:20:19  fplanque
 * partial rollback of stuff that can't be right...
 *
 * Revision 1.32  2009/02/22 17:11:13  tblue246
 * Remove the "in blog" from all page titles because it is rather confusing.
 *
 * Revision 1.31  2009/02/18 18:49:39  blueyed
 * Pass correct $editing value to Item::load_from_Request for $action="create_edit".
 * This fixes (i.e. skips) e.g. title validation when clicking "Save & start attaching files".
 *
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
 */
?>
