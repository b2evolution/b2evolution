<?php
/**
 * This file implements the UI controller for the browsing posts.
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

param( 'action', 'string', 'list' );


/*
 * Init the objects we want to work on.
 *
 * Autoselect a blog where we have PERMISSION to browse (preferably the last used blog):
 * Note: for some actions, we'll get the blog from the post ID
 */
switch( $action )
{
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
 		// Load post to edit:
 		// Note: we need to *not* use $p here or it will conflict with the list display
		param( 'post_ID', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $post_ID );

		// Load the blog we're in:
		$Blog = $edited_Item->get_Blog();
		$blog = $Blog->ID;
		break;

	case 'new':
	case 'create':
	case 'list':
		if( $action == 'list' )
		{	// We only need view permission
			$blog = autoselect_blog( param( 'blog', 'integer', 0 ), 'blog_ismember', 1 );
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
		debug_die( 'unhandled action 1' );
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
		// New post form  (can be a bookmarklet form if mode == bookmarklet )

		load_class( 'MODEL/items/_item.class.php' );
		$edited_Item = & new Item();

		$edited_Item->blog_ID = $blog;

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

		// We use the variables below to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...

		// Note: most params are handled by "$edited_Item->load_from_Request();" below..
		$edited_Item->load_from_Request(); // needs Blog set

		param( 'post_extracats', 'array', array() );
		param( 'edit_date', 'integer', 0 ); // checkbox
		$default_main_cat = param( 'post_category', 'integer', $edited_Item->main_cat_ID );
		if( $default_main_cat && $allow_cross_posting < 3 && get_catblog($default_main_cat) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing: setting it to 0 uses the first cat in the list
			$default_main_cat = 0;
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

		// Page title:
		$AdminUI->title = T_('New post in blog:').' ';
		$AdminUI->title_titlearea = $AdminUI->title;
		$js_doc_title_prefix = $AdminUI->title;

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;
		break;


	case 'edit':
		// Check permission:
		$post_status = $edited_Item->get( 'status' );
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		// We use the variables below to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...

		$post_locale = $edited_Item->get( 'locale' );

		// $cat = $edited_Item->get( 'main_cat_ID' );

		$post_title = $edited_Item->get( 'title' );
		$post_urltitle = $edited_Item->get( 'urltitle' );
		$post_url = $edited_Item->get( 'url' );
		$content = $edited_Item->get( 'content' );
		$post_trackbacks = '';
		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $p );

		// Page title:
		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$p;
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
		{	// There hace been some validation errors:
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

		$action = 'list';
		init_list_mode();
		break;


	case 'delete':
		break;


	case 'list':
		init_list_mode();

		if( $ItemList->single_post )
		{	// We have requested to view a SINGLE specific post:
			$action = 'view';
		}
		break;


	default:
		debug_die( 'unhandled action 2' );
}

/*
 * Action pass 2
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

		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_post_statuses', 'any',
						$pagenow.'?ctrl=items&amp;action=new&amp;blog=%d', NULL, ''
						/* , 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$pagenow.'\', %d )' */ );

		// We don't check the following earlier, because we want the blog switching buttons to be available:
		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'error' );
			$action = 'nil';
			break;
		}

		/* NOBREAK */

	case 'edit':
	case 'update': // on error
		// Get tab ("simple" or "expert") from Request or UserSettings:
		$tab = $UserSettings->param_Request( 'tab', 'pref_edit_tab', 'string', NULL, true /* memorize */ );

		$AdminUI->add_menu_entries(
				'items',
				array(
						'simple' => array(
							'text' => T_('Simple'),
							'href' => 'admin.php?ctrl=items&amp;action='.$action.'&amp;tab=simple&amp;'.$tab_switch_params,
							// fp> TODO: fix: 'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=simple&amp;blog='.$blog.'\' );',
							// 'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
							),
						'expert' => array(
							'text' => T_('Expert'),
							'href' => 'admin.php?ctrl=items&amp;action='.$action.'&amp;tab=expert&amp;'.$tab_switch_params,
							// fp> TODO: fix: 'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=expert&amp;blog='.$blog.'\' );',
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
		$AdminUI->add_menu_entries(
				'items',
				array(
						'full' => array(
							'text' => T_('Full posts'),
							'href' => regenerate_url( 'tab', 'tab=full&amp;filter=restore' ),
							),
						'list' => array(
							'text' => T_('Post list'),
							'href' => regenerate_url( 'tab', 'tab=list&amp;filter=restore' ),
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
								'href' => regenerate_url( 'tab', 'tab=tracker&amp;filter=restore' ),
								),
						)
				);
		}

		/* ignore these for now
		$AdminUI->add_menu_entries(
				'items',
				array(
						'comments' => array(
							'text' => T_('Comments'),
							'href' => regenerate_url( 'tab', 'tab=comments' ),
							),
					)
			);
		*/
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

	case 'new':
	case 'create':
		$next_action = 'create';
		$creating = true; // used by cat_select_before_each()
	case 'edit':
	case 'update':	// on error
	case 'delete':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		if( empty($next_action) )
		{
			$next_action = 'update';
		}

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
		// View a single post:

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
 * Revision 1.2  2006/12/12 00:39:46  fplanque
 * The bulk of item editing is done.
 *
 * Revision 1.1  2006/12/11 18:04:52  fplanque
 * started clean "1-2-3-4" item editing
 *
 */
?>