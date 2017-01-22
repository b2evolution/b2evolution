<?php
/**
 * This file implements the controller for post types management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Itemtype class:
load_class( 'items/model/_itemtype.class.php', 'ItemType' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var User
 */
global $current_User;

global $dispatcher;

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

$tab = param( 'tab', 'string', 'settings', true );

$tab3 = param( 'tab3', 'string', 'types', true );

$AdminUI->set_path( 'collections', $tab, $tab3 );

// Get action parameter from request:
param_action();

if( param( 'ityp_ID', 'integer', '', true ) )
{ // Load itemtype from cache:
	$ItemtypeCache = & get_ItemTypeCache();
	if( ($edited_Itemtype = & $ItemtypeCache->get_by_ID( $ityp_ID, false )) === false )
	{	// We could not find the post type to edit:
		unset( $edited_Itemtype );
		forget_param( 'ityp_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), 'Itemtype' ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Itemtype) )
		{	// We don't have a model to use, start with blank object:
			$edited_Itemtype = new ItemType();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Itemtype = clone $edited_Itemtype;
			// Load all custom fields of the copied post type
			$edited_Itemtype->get_custom_fields();
			// Reset ID of new post type
			$edited_Itemtype->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );
		break;

	case 'create': // Record new Itemtype
	case 'create_new': // Record Itemtype and create new
	case 'create_copy': // Record Itemtype and create similar
		// Insert new post type...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		$edited_Itemtype = new ItemType();

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_Itemtype->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Itemtype->dbinsert();
			$Messages->add( T_('New Post Type created.'), 'success' );

			// Update allowed item statuses
			$edited_Itemtype->update_item_statuses_from_Request();

			// What next?
			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new&ityp_ID='.$edited_Itemtype->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit post type form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );

		// load data from request
		if( $edited_Itemtype->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();

			$edited_Itemtype->update_item_statuses_from_Request();
			$edited_Itemtype->dbupdate();
			$Messages->add( T_('Post type updated.'), 'success' );

			$DB->commit();

			header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete post type:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );

		$default_ids = ItemType::get_default_ids();

		if( ( $item_type_blog_ID = array_search( $edited_Itemtype->ID, $default_ids ) ) !== false )
		{ // is default post type of the blog
			if( $item_type_blog_ID == 'default' )
			{
				$Messages->add( T_('This Item type is the default for all collections. You can not delete this Item type.' ) );
			}
			else
			{
				$BlogCache = & get_BlogCache();
				$blog_names = array();
				foreach( $default_ids as $blog_ID => $item_type_ID )
				{
					if( $edited_Itemtype->ID == $item_type_ID && ( $Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) ) )
					{
						$blog_names[] = '<a href="'.$admin_url.'?ctrl=coll_settings&tab=features&blog='.$Blog->ID.'#fieldset_wrapper_post_options"><b>'.$Blog->get('name').'</b></a>';
					}
				}
				$Messages->add( sprintf( T_('This Item type is the default for the collections: %s. You can not delete this Item type.' ), implode( ', ', $blog_names ) ) );
			}
			// To don't display a confirmation question
			$action = 'edit';
		}
		else
		{ // ID is good
			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				$msg = sprintf( T_('Post type &laquo;%s&raquo; deleted.'), $edited_Itemtype->dget('name') );
				$edited_Itemtype->dbdelete();
				unset( $edited_Itemtype );
				forget_param( 'ityp_ID' );
				$Messages->add( $msg, 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
			else
			{	// not confirmed, Check for restrictions:
				if( ! $edited_Itemtype->check_delete( sprintf( T_('Cannot delete Post Type &laquo;%s&raquo;'), $edited_Itemtype->dget('name') ) ) )
				{	// There are restrictions:
					$action = 'view';
				}
			}
		}
		break;

	case 'enable':
	case 'disable':
		// Enable/Disable item type for the selected blog:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( $edited_Itemtype )
		{ // Do only when item type exists in DB
			if( $action == 'enable' )
			{ // Enable item type for the collection
				$DB->query( 'REPLACE INTO T_items__type_coll
								 ( itc_ityp_ID, itc_coll_ID )
					VALUES ( '.$DB->quote( $edited_Itemtype->ID ).', '.$DB->quote( $blog ).' )' );
				$Messages->add( T_('Post type has been enabled for this collection.'), 'success' );
			}
			elseif( $Blog->can_be_item_type_disabled( $edited_Itemtype->ID, true ) )
			{ // Disable item type for the collection only if it is allowed:
				$DB->query( 'DELETE FROM T_items__type_coll
					WHERE itc_ityp_ID = '.$DB->quote( $edited_Itemtype->ID ).'
					  AND itc_coll_ID = '.$DB->quote( $blog ) );
				$Messages->add( T_('Post type has been disabled for this collection.'), 'success' );
			}
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'default':
		// Set default item type for the selected collection:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( $edited_Itemtype )
		{	// Do only when item type exists in DB:

			// Update default item type to new selected:
			$Blog->set_setting( 'default_post_type', $edited_Itemtype->ID );
			$Blog->dbupdate();

			// Enable new default item type for the selected collection automatically:
			$DB->query( 'REPLACE INTO T_items__type_coll
								 ( itc_ityp_ID, itc_coll_ID )
					VALUES ( '.$DB->quote( $edited_Itemtype->ID ).', '.$DB->quote( $blog ).' )' );

			$Messages->add( T_('The item type has been set as the default for this collection.'), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}

// Generate available blogs list:
$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'itemtypes', 'tab' => $tab, 'tab3' => 'types' ) );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Settings'), $admin_url.'?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
$AdminUI->breadcrumbpath_add( T_('Post Types'), $admin_url.'?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=types' );

// Set an url for manual page:
switch( $action )
{
	case 'delete':
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->set_page_manual_link( 'item-type-form' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'managing-item-types' );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'delete':
		// We need to ask for confirmation:
		$edited_Itemtype->confirm_delete(
				sprintf( T_('Delete Post Type &laquo;%s&raquo;?'),  $edited_Itemtype->dget('name') ),
				'itemtype', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'items/views/_itemtype.form.php' );
		break;


	default:
		// No specific request, list all post types:
		// Cleanup context:
		forget_param( 'ityp_ID' );
		// Display post types list:
		$AdminUI->disp_view( 'items/views/_itemtypes.view.php' );
		break;

}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>