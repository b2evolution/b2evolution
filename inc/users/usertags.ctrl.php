<?php
/**
 * This file implements the user tags control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_usertag.class.php', 'UserTag' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'users', 'usertags' );

param_action( 'list' );
param( 'return_to', 'url', '', true );
param( 'utag_filter', 'string', '', true );
param( 'tag_user_ID', 'string', '', true );

if( param( 'utag_ID', 'integer', '', true) )
{ // Load user tag:
	$UserTagCache = & get_UserTagCache();
	if( ( $edited_UserTag = & $UserTagCache->get_by_ID( $utag_ID, false ) ) === false )
	{ // We could not find the goal to edit:
		unset( $edited_UserTag );
		forget_param( 'utag_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), /* TRANS: noun */ T_('User Tag') ), 'error' );
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

		$edited_UserTag = new UserTag();
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		break;

	case 'create':
		// Create new user tag...
		$edited_UserTag = new UserTag();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to create tags:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_UserTag->load_from_Request() )
		{ // We could load data from form without errors:
			// Insert in DB:
			$edited_UserTag->dbinsert();
			$Messages->add( T_('New tag has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=usertags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update user tag...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an tag_ID:
		param( 'utag_ID', 'integer', true );

		// load data from request
		if( $edited_UserTag->load_from_Request() )
		{ // We could load data from form without errors:
			// Update user tag in DB:
			$edited_UserTag->dbupdate();
			$Messages->add( T_('Tag has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=usertags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete user tag:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an tag_ID:
		param( 'utag_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Tag "%s" has been deleted.'), '<b>'.$edited_UserTag->dget( 'name' ).'</b>' );
			$edited_UserTag->dbdelete();
			unset( $edited_UserTag );
			forget_param( 'utag_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=usertags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // not confirmed, Check for restrictions:
			if( ! $edited_UserTag->check_delete( sprintf( T_('Cannot delete tag "%s"'), '<b>'.$edited_UserTag->dget( 'name' ).'</b>' ), array(), true ) )
			{ // There are restrictions:
				$action = 'list';
			}
		}
		break;

	case 'unlink':
		// Unlink tag from the post:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$user_ID = param( 'user_ID', 'integer', 0, true );

		$UserCache = & get_UserCache();
		$edited_User = & $UserCache->get_by_ID( $user_ID );

		// Check permission based on DB status:
		$current_User->check_perm( 'user', 'edit', true, $edited_User );

		$result = $DB->query( 'DELETE FROM T_users__usertag
			WHERE uutg_user_ID = '.$DB->quote( $edited_User->ID ).'
			  AND uutg_emtag_ID = '.$DB->quote( $edited_UserTag->ID ) );

		if( $result )
		{
			$Messages->add( sprintf( T_('Tag "%s" has been unlinked from user "%s".'),
				'<b>'.$edited_UserTag->dget( 'name' ).'</b>',
				'<b>'.$edited_User->dget( 'login' ).'</b>' ), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=usertags&utag_ID='.$edited_UserTag->ID.'&action=edit&return_to='.urlencode( $return_to ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'merge_confirm':
	case 'merge_cancel':
		// Merge previous tag with other tag:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$old_tag_ID = param( 'old_tag_ID', 'integer', 0, true );

		$UserTagCache = & get_UserTagCache();
		$old_UserTag = & $UserTagCache->get_by_ID( $old_tag_ID, false );

		if( empty( $edited_UserTag ) || empty( $old_UserTag ) )
		{ // Wrong request, Redirect to user tags
			header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=usertags', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $action == 'merge_cancel' )
		{ // Merge action has been cancelled, REdirect to edit tag
			header_redirect( $admin_url.'?ctrl=usertags&action=edit&tag_ID='.$old_UserTag->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Delete old tag from the items if they already have new tag
		// in order to avoid conflicts in the update sql query below
		$new_tag_user_IDs = $DB->get_col( 'SELECT uutg_user_ID
			 FROM T_users__usertag
			WHERE uutg_tag_ID = '.$DB->quote( $edited_UserTag->ID ) );
		if( ! empty( $new_tag_user_IDs ) )
		{
			$DB->query( 'DELETE FROM T_users__usertag
				WHERE uutg_user_ID IN ( '.$DB->quote( $new_tag_user_IDs ).' )
				  AND uutg_emtag_ID = '.$DB->quote( $old_tag_ID ) );
		}

		// Replace all previous tags with new existing tags
		$DB->query( 'UPDATE T_users__usertag
			  SET uutg_user_ID = '.$DB->quote( $edited_UserTag->ID ).'
			WHERE uutg_emtag_ID = '.$DB->quote( $old_tag_ID ) );

		// Delete previous tag completely
		$DB->query( 'DELETE FROM T_users__tag
			WHERE utag_ID = '.$DB->quote( $old_UserTag->ID ) );

		$Messages->add( sprintf( T_('The previously named "%s" tag has been merged with the existing "%s" tag.'),
				'<b>'.$old_UserTag->dget( 'name' ).'</b>',
				'<b>'.$edited_UserTag->dget( 'name' ).'</b>' ), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=usertags&tag_ID='.$edited_UserTag->ID.'&action=edit', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'cleanup':
		// Delete all orphan Tag entries:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usertag' );

		// Check that current user has permission to edit tags:
		$current_User->check_perm( 'options', 'edit', true );

		$DB->query( 'DELETE T_users__usertag FROM T_users__usertag
				LEFT JOIN T_users ON uutg_user_ID = user_ID
			 WHERE user_ID IS NULL' );
		$Messages->add_to_group( sprintf( T_('Removed %d associations with non-existing users.'), $DB->rows_affected ), 'success', T_('Deleting orphan tags:') );

		$DB->query( 'DELETE T_users__tag FROM T_users__tag
				LEFT JOIN T_users__usertag ON utag_ID = uutg_emtag_ID
			 WHERE uutg_user_ID IS NULL' );
		$Messages->add_to_group( sprintf( T_('Removed %d obsolete tag entries.'), $DB->rows_affected ), 'success', T_('Deleting orphan tags:') );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $return_to ? $return_to : $admin_url.'?ctrl=usertags', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Users'), $admin_url.'?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('User Tags'), $admin_url.'?ctrl=usertags' );

if( $action == 'new' || $action == 'edit' )
{
	$AdminUI->set_page_manual_link( 'user-tag-form' );
}
else
{
	$AdminUI->set_page_manual_link( 'user-tags-list' );
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
		$AdminUI->disp_view( 'users/views/_usertag.form.php' );
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_UserTag->confirm_delete(
				sprintf( T_('Delete tag "%s"?'), '<b>'.$edited_UserTag->dget( 'name' ).'</b>' ),
				'usertag', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'list':
		// list tags:
		$AdminUI->disp_view( 'users/views/_usertags.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>