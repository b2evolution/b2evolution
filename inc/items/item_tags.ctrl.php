<?php
/**
 * This file implements the item tags control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_itemtag.class.php', 'ItemTag' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'site', 'tags' );

param_action( 'list' );
param( 'return_to', 'url', '', true );
param( 'tag_filter', 'string', '', true );
param( 'tag_item_ID', 'string', '', true );

if( param( 'tag_ID', 'integer', '', true) )
{ // Load item tag:
	$ItemTagCache = & get_ItemTagCache();
	if( ( $edited_ItemTag = & $ItemTagCache->get_by_ID( $tag_ID, false ) ) === false )
	{ // We could not find the goal to edit:
		unset( $edited_ItemTag );
		forget_param( 'tag_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), /* TRANS: noun */ T_('Tag') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'list':
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$edited_ItemTag = new ItemTag();
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		break;

	case 'create':
		// Create new tag...
		$edited_ItemTag = new ItemTag();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to create tags:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_ItemTag->load_from_Request() )
		{ // We could load data from form without errors:
			// Insert in DB:
			$edited_ItemTag->dbinsert();
			$Messages->add( T_('New tag has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=itemtags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update tag...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an tag_ID:
		param( 'tag_ID', 'integer', true );

		// load data from request
		if( $edited_ItemTag->load_from_Request() )
		{ // We could load data from form without errors:
			// Update tag in DB:
			$edited_ItemTag->dbupdate();
			$Messages->add( T_('Tag has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=itemtags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete tag:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an tag_ID:
		param( 'tag_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Tag "%s" has been deleted.'), '<b>'.$edited_ItemTag->dget( 'name' ).'</b>' );
			$edited_ItemTag->dbdelete();
			unset( $edited_ItemTag );
			forget_param( 'tag_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=itemtags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // not confirmed, Check for restrictions:
			if( ! $edited_ItemTag->check_delete( sprintf( T_('Cannot delete tag "%s"'), '<b>'.$edited_ItemTag->dget( 'name' ).'</b>' ), array(), true ) )
			{ // There are restrictions:
				$action = 'list';
			}
		}
		break;

	case 'unlink':
		// Unlink tag from the post:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$item_ID = param( 'item_ID', 'integer', 0, true );

		$ItemCache = & get_ItemCache();
		$edited_Item = & $ItemCache->get_by_ID( $item_ID );

		// Check permission based on DB status:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$result = $DB->query( 'DELETE FROM T_items__itemtag
			WHERE itag_itm_ID = '.$DB->quote( $edited_Item->ID ).'
			  AND itag_tag_ID = '.$DB->quote( $edited_ItemTag->ID ) );

		if( $result )
		{
			$Messages->add( sprintf( T_('Tag "%s" has been unlinked from the post "%s".'),
				'<b>'.$edited_ItemTag->dget( 'name' ).'</b>',
				'<b>'.$edited_Item->dget( 'title' ).'</b>' ), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=itemtags&tag_ID='.$edited_ItemTag->ID.'&action=edit&return_to='.urlencode( $return_to ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'merge_confirm':
	case 'merge_cancel':
		// Merge previous tag with other tag:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$old_tag_ID = param( 'old_tag_ID', 'integer', 0, true );

		$ItemTagCache = & get_ItemTagCache();
		$old_ItemTag = & $ItemTagCache->get_by_ID( $old_tag_ID, false );

		if( empty( $edited_ItemTag ) || empty( $old_ItemTag ) )
		{ // Wrong request, Redirect to item tags
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=itemtags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $action == 'merge_cancel' )
		{ // Merge action has been cancelled, REdirect to edit tag
			header_redirect( $admin_url.'?ctrl=itemtags&action=edit&tag_ID='.$old_ItemTag->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Delete old tag from the items if they already have new tag
		// in order to avoid conflicts in the update sql query below
		$new_tag_item_IDs = $DB->get_col( 'SELECT itag_itm_ID
			 FROM T_items__itemtag
			WHERE itag_tag_ID = '.$DB->quote( $edited_ItemTag->ID ) );
		if( ! empty( $new_tag_item_IDs ) )
		{
			$DB->query( 'DELETE FROM T_items__itemtag
				WHERE itag_itm_ID IN ( '.$DB->quote( $new_tag_item_IDs ).' )
				  AND itag_tag_ID = '.$DB->quote( $old_tag_ID ) );
		}

		// Replace all previous tags with new existing tags
		$DB->query( 'UPDATE T_items__itemtag
			  SET itag_tag_ID = '.$DB->quote( $edited_ItemTag->ID ).'
			WHERE itag_tag_ID = '.$DB->quote( $old_tag_ID ) );

		// Delete previous tag completely
		$DB->query( 'DELETE FROM T_items__tag
			WHERE tag_ID = '.$DB->quote( $old_ItemTag->ID ) );

		$Messages->add( sprintf( T_('The previously named "%s" tag has been merged with the existing "%s" tag.'),
				'<b>'.$old_ItemTag->dget( 'name' ).'</b>',
				'<b>'.$edited_ItemTag->dget( 'name' ).'</b>' ), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=itemtags&tag_ID='.$edited_ItemTag->ID.'&action=edit', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'cleanup':
		// Delete all orphan Tag entries:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$DB->query( 'DELETE T_items__itemtag FROM T_items__itemtag
				LEFT JOIN T_items__item ON itag_itm_ID = post_ID
			 WHERE post_ID IS NULL' );
		$Messages->add_to_group( sprintf( T_('Removed %d associations with non-existing posts.'), $DB->rows_affected ), 'success', T_('Deleting orphan tags:') );

		$DB->query( 'DELETE T_items__tag FROM T_items__tag
				LEFT JOIN T_items__itemtag ON tag_ID = itag_tag_ID
			 WHERE itag_itm_ID IS NULL' );
		$Messages->add_to_group( sprintf( T_('Removed %d obsolete tag entries.'), $DB->rows_affected ), 'success', T_('Deleting orphan tags:') );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=itemtags', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Tags'), $admin_url.'?ctrl=itemtags' );

if( $action == 'new' || $action == 'edit' )
{
	$AdminUI->set_page_manual_link( 'item-tag-form' );
}
else
{
	$AdminUI->set_page_manual_link( 'item-tags-list' );
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

	case 'new':
	case 'edit':
		// Display tag form
		$AdminUI->disp_view( 'items/views/_itemtag.form.php' );
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_ItemTag->confirm_delete(
				sprintf( T_('Delete tag "%s"?'), '<b>'.$edited_ItemTag->dget( 'name' ).'</b>' ),
				'tag', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'list':
		// list tags:
		$AdminUI->disp_view( 'items/views/_itemtags.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>