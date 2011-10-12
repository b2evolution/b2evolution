<?php
/**
 * Edit item with in-skin mode.
 *
 */

require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

if( empty( $Blog ) )
{
	param( 'blog', 'integer', 0 );

	if( isset( $blog) && $blog > 0 )
	{
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog, false, false );
	}
}

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$action = param_action();

if( !empty( $action ) && $action != 'new' )
{ // Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'item' );
}

switch( $action )
{
	case 'update' :
	case 'edit_switchtab' : // this gets set as action by JS, when we switch tabs
		// Load post to edit:
		$post_ID = param ( 'post_ID', 'integer', true, true );
		$ItemCache = & get_ItemCache ();
		$edited_Item = & $ItemCache->get_by_ID ( $post_ID );
		
		// Load the blog we're in:
		$Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );

		// What form button has been pressed?
		param( 'save', 'string', '' );
		$exit_after_save = ( $action != 'update_edit' );
		break;
}

switch( $action )
{
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
		$edited_Item->status = $Blog->get_allowed_item_status ( $edited_Item->status );
		
		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave ( $post_category, $post_extracats );
		
		$edited_Item->set ( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&filter=restore&blog='.$Blog->ID, '&' ) );
		break;

	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
		// Check permission based on DB status:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$edited_Item->status = param( 'post_status', 'string', NULL );		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status );

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		$edited_Item->load_from_Request ( true ); // needs Blog set
		
		// Check if new category was started to create. If yes then set up parameters for next page
		check_categories_nosave ( $post_category, $post_extracats );
		
		$edited_Item->set ( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;

	case 'create': // Create a new post
		$exit_after_save = ( $action != 'create_edit' );

		// We need early decoding of these in order to check permissions:
		$post_status = param( 'post_status', 'string', 'published' );
		
		// Check if new category was started to create. If yes check if it is valid.
		check_categories ( $post_category, $post_extracats );
		
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );
		// Check permission on post type:
		check_perm_posttype( $post_extracats );

		// CREATE NEW POST:
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', 'published' );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( /* editing? */ ($action == 'create_edit'), /* creating? */ true );
		
		$Plugins->trigger_event ( 'AdminBeforeItemEditCreate', array ('Item' => & $edited_Item ) );
		
		if( !empty( $mass_create ) )
		{	// ------ MASS CREATE ------
			$Items = & create_multiple_posts( $edited_Item, param( 'paragraphs_linebreak', 'boolean', 0 ) );
			if( empty( $Items ) )
			{
				param_error( 'content', T_( 'Content must not be empty.' ) );
			}
		}

		if( $Messages->has_errors() )
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
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'note' );
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

		// REDIRECT / EXIT
		header_redirect( $edited_Item->get_tinyurl() );
		break;

	case 'update': // Update an existing post
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check edit permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// We need early decoding of these in order to check permissions:
		param( 'post_status', 'string', 'published' );
		
		// Check if new category was started to create.  If yes check if it is valid.
		$isset_category = check_categories ( $post_category, $post_extracats );
		
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post!'.$post_status, 'edit', true, $post_extracats );
		// Check permission on post type:
		check_perm_posttype( $post_extracats );

		// Is this post already published?
		$was_published = $edited_Item->status == 'published';

		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set ( 'status', $post_status );
		
		if( $isset_category )
		{ // we change the categories only if the check was succesfull
			$edited_Item->set ( 'main_cat_ID', $post_category );
			$edited_Item->set ( 'extra_cat_IDs', $post_extracats );
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
		$edited_Item->dbupdate();

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
		$edited_Item->handle_post_processing( $exit_after_save );

		$Messages->add( T_('Post has been updated.'), 'success' );

		if( ! in_array( $post_status, array( 'published', 'protected', 'private' ) ) )
		{	// If post is not published we show it in the Back-office
			$edited_Item->load_Blog();
			$redirect_to =  url_add_param( $admin_url, 'ctrl=items&blog='.$edited_Item->Blog->ID.'&p='.$edited_Item->ID, '&' );
		}
		else
		{	// User can see this post in the Front-office
			$redirect_to = $edited_Item->get_tinyurl();
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		/* EXITED */
		break;
}

// Display a 'In-skin editing' form
$SkinCache = & get_SkinCache();
$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
$skin = $Skin->folder;
$disp = 'edit';
$ads_current_skin_path = $skins_path.$skin.'/';
require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.3  2011/10/12 13:54:36  efy-yurybakh
 * In skin posting
 *
 * Revision 1.2  2011/10/12 11:23:31  efy-yurybakh
 * In skin posting (beta)
 *
 * Revision 1.1  2011/10/11 18:26:11  efy-yurybakh
 * In skin posting (beta)
 *
 * 
 */
?>
