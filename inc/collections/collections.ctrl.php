<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


param( 'tab', 'string', 'site_settings', true );

param_action( 'list' );

if( strpos( $action, 'new' ) !== false )
{ // Simulate tab to value 'new' for actions to create new blog
	$tab = 'new';
}
if( ! in_array( $action, array( 'new', 'new-selskin', 'new-installskin', 'new-name', 'create', 'update_settings_blog', 'update_settings_site' ) ) )
{
	if( valid_blog_requested() )
	{
		// echo 'valid blog requested';
		$edited_Blog = & $Blog;
	}
	else
	{
		// echo 'NO valid blog requested';
		$action = 'list';
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New collection: Select blog type
	case 'copy':
		// Copy collection:
		// Check permissions:
		if( ! $current_User->check_perm( 'blogs', 'create' ) )
		{
			$Messages->add( T_('You don\'t have permission to create a collection.'), 'error' );
			$redirect_to = param( 'redirect_to', 'url', $admin_url );
			header_redirect( $redirect_to );
		}

		$user_Group = $current_User->get_Group();
		$max_allowed_blogs = $user_Group->get_GroupSettings()->get( 'perm_max_createblog_num', $user_Group->ID );
		$user_blog_count = $current_User->get_num_blogs();

		if( $max_allowed_blogs != '' && $max_allowed_blogs <= $user_blog_count )
		{
			$Messages->add( sprintf( T_('You already own %d collection/s. You are not currently allowed to create any more.'), $user_blog_count ) );
			$redirect_to = param( 'redirect_to', 'url', $admin_url );
			header_redirect( $redirect_to );
		}

		$AdminUI->append_path_level( 'new', array( 'text' => T_('New') ) );
		break;

	case 'new-selskin':
	case 'new-installskin':
		// New collection: Select or Install skin
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		param( 'kind', 'string', true );

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( /* TRANS: %s can become "Standard blog", "Photoblog", "Group blog" or "Forum" */ T_('New "%s" collection'), get_collection_kinds($kind) ) ) );
		break;

	case 'new-name':
		// New collection: Set general parameters
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );

		param( 'skin_ID', 'integer', true );

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( T_('New %s'), get_collection_kinds($kind) ) ) );
		break;

	case 'create':
		// Insert into DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );
		if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
		{ // validate the urlname, which was already set by init_by_kind() function
		 	// It needs to validated, because the user can not set the blog urlname, and every new blog would have the same urlname without validation.
		 	// When user has edit permission to blog admin part, the urlname will be validated in load_from_request() function.
			$edited_Blog->set( 'urlname', urltitle_validate( $edited_Blog->get( 'urlname' ) , '', 0, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
		}

		param( 'skin_ID', 'integer', true );
		$edited_Blog->set_setting( 'normal_skin_ID', $skin_ID );

		if( $edited_Blog->load_from_Request( array() ) )
		{
			// create the new blog
			$edited_Blog->create( $kind );

			// We want to highlight the edited object on next list display:
			// $Session->set( 'fadeout_array', array( 'blog_ID' => array($edited_Blog->ID) ) );

			header_redirect( $admin_url.'?ctrl=coll_settings&tab=dashboard&blog='.$edited_Blog->ID ); // will save $Messages into Session
		}
		break;

	case 'duplicate':
		// Duplicate collection:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		if( $edited_Blog->duplicate() )
		{	// The collection has been duplicated successfully:
			$Messages->add( T_('The collection has been duplicated.'), 'success' );

			header_redirect( $admin_url.'?ctrl=coll_settings&tab=dashboard&blog='.$edited_Blog->ID ); // will save $Messages into Session
		}

		//
		$action = 'copy';
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$msg = sprintf( T_('Blog &laquo;%s&raquo; deleted.'), $edited_Blog->dget('name') );

			if( $edited_Blog->dbdelete() )
			{ // Blog was deleted
				$Messages->add( $msg, 'success' );

				$BlogCache->remove_by_ID( $blog );
				unset( $edited_Blog );
				unset( $Blog );
				forget_param( 'blog' );
				set_working_blog( 0 );
				$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
				$UserSettings->dbupdate();
			}

			$action = 'list';
			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = param( 'redirect_to', 'url', $admin_url.'?ctrl=dashboard' );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // Check if blog has delete restrictions
			if( ! $edited_Blog->check_delete( sprintf( T_('Cannot delete Blog &laquo;%s&raquo;'), $edited_Blog->get_name() ), array( 'file_root_ID', 'cat_blog_ID' ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
			// Force this virtual tab to select a correct path on delete action
			$tab = 'delete';
		}
		break;


	case 'update_settings_blog':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$Settings->set( 'blogs_order_by', param( 'blogs_order_by', 'string', true ) );
		$Settings->set( 'blogs_order_dir', param( 'blogs_order_dir', 'string', true ) );

		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		$Settings->set( 'newblog_cache_enabled', param( 'newblog_cache_enabled', 'integer', 0 ) );
		$Settings->set( 'newblog_cache_enabled_widget', param( 'newblog_cache_enabled_widget', 'integer', 0 ) );

		// Outbound pinging:
		param( 'outbound_notifications_mode', 'string', true );
		$Settings->set( 'outbound_notifications_mode',  get_param('outbound_notifications_mode') );

		// Categories:
		$Settings->set( 'allow_moving_chapters', param( 'allow_moving_chapters', 'integer', 0 ) );

		// Cross posting:
		$Settings->set( 'cross_posting', param( 'cross_posting', 'integer', 0 ) );
		$Settings->set( 'cross_posting_blogs', param( 'cross_posting_blogs', 'integer', 0 ) );

		// Redirect moved posts:
		$Settings->set( 'redirect_moved_posts', param( 'redirect_moved_posts', 'integer', 0 ) );

		// Subscribing to new blogs:
		$Settings->set( 'subscribe_new_blogs', param( 'subscribe_new_blogs', 'string', 'public' ) );

		// Default skins:
		if( param( 'def_normal_skin_ID', 'integer', NULL ) !== NULL )
		{ // this can't be NULL
			$Settings->set( 'def_normal_skin_ID', get_param( 'def_normal_skin_ID' ) );
		}
		$Settings->set( 'def_mobile_skin_ID', param( 'def_mobile_skin_ID', 'integer', 0 ) );
		$Settings->set( 'def_tablet_skin_ID', param( 'def_tablet_skin_ID', 'integer', 0 ) );

		// Comment recycle bin
		param( 'auto_empty_trash', 'integer', $Settings->get_default('auto_empty_trash'), false, false, true, false );
		$Settings->set( 'auto_empty_trash', get_param('auto_empty_trash') );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Blog settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=collections&tab=blog_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update_settings_site':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Lock system
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$system_lock = param( 'system_lock', 'integer', 0 );
			if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
			{ // System lock was turned off and there was no error, remove the warning about the system lock
				$Messages->clear();
			}
			$Settings->set( 'system_lock', $system_lock );
		}

		// Site code
		$Settings->set( 'site_code',  param( 'site_code', 'string', '' ) );

		// Site color
		$site_color = param( 'site_color', 'string', '' );
		param_check_regexp( 'site_color', '~^(#([a-f0-9]{3}){1,2})?$~i', T_('Invalid color code.'), NULL, false );
		$Settings->set( 'site_color', $site_color );

		// Site short name
		$short_name = param( 'notification_short_name', 'string', '' );
		param_check_not_empty( 'notification_short_name' );
		$Settings->set( 'notification_short_name', $short_name );

		// Site long name
		$Settings->set( 'notification_long_name', param( 'notification_long_name', 'string', '' ) );

		// Small site logo url
		param( 'notification_logo', 'string', '' );
		param_check_url( 'notification_logo', 'http-https' );
		$Settings->set( 'notification_logo', get_param( 'notification_logo' ) );

		// Site footer text
		$Settings->set( 'site_footer_text', param( 'site_footer_text', 'string', '' ) );

		// Enable site skins
		$old_site_skins_enabled = $Settings->get( 'site_skins_enabled' );
		$Settings->set( 'site_skins_enabled', param( 'site_skins_enabled', 'integer', 0 ) );
		if( $old_site_skins_enabled != $Settings->get( 'site_skins_enabled' ) )
		{ // If this setting has been changed we should clear all page caches:
			load_funcs( 'tools/model/_maintenance.funcs.php' );
			dbm_delete_pagecache( false );
		}

		// Terms & Conditions:
		$Settings->set( 'site_terms_enabled', param( 'site_terms_enabled', 'integer', 0 ) );
		$Settings->set( 'site_terms', param( 'site_terms', 'integer', '' ) );

		// Default blog
		$Settings->set( 'default_blog_ID', param( 'default_blog_ID', 'integer', 0 ) );

		// Blog for info pages
		$Settings->set( 'info_blog_ID', param( 'info_blog_ID', 'integer', 0 ) );

		// Blog for login|registration
		$Settings->set( 'login_blog_ID', param( 'login_blog_ID', 'integer', 0 ) );

		// Blog for messaging
		$Settings->set( 'msg_blog_ID', param( 'msg_blog_ID', 'integer', 0 ) );

		// Reload page timeout
		$reloadpage_timeout = param_duration( 'reloadpage_timeout' );
		if( $reloadpage_timeout > 99999 )
		{
			param_error( 'reloadpage_timeout', sprintf( T_( 'Reload-page timeout must be between %d and %d seconds.' ), 0, 99999 ) );
		}
		$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

		// General cache
		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Site settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=collections&tab=site_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		break;
}

switch( $tab )
{
	case 'site_settings':
		// Check minimum permission:
		$current_User->check_perm( 'options', 'view', true );

		$AdminUI->set_path( 'site', 'settings' );

		$AdminUI->breadcrumbpath_init( false );
		$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
		$AdminUI->breadcrumbpath_add( T_('Site Settings'), $admin_url.'?ctrl=collections&amp;tab=site_settings' );

		$AdminUI->set_page_manual_link( 'site-settings' );

		init_colorpicker_js();
		break;

	case 'blog_settings':
		// Check minimum permission:
		$current_User->check_perm( 'options', 'view', true );

		// We should activate toolbar menu items for this controller and tab
		$activate_collection_toolbar = true;

		$AdminUI->set_path( 'collections', 'settings', 'blog_settings' );

		$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
		$AdminUI->breadcrumbpath_add( T_('Settings'), $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Common Settings'), $admin_url.'?ctrl=collections&amp;tab=blog_settings&amp;blog=$blog$' );

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'global-collection-settings' );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'collections', 'tab' => 'blog_settings' ) );
		break;

	case 'new':
		// Init JS to autcomplete the user logins
		init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );

		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
		$AdminUI->breadcrumbpath_add( T_('New Collection'), $admin_url.'?ctrl=collections&amp;action=new' );

		// Set an url for manual page:
		switch( $action )
		{
			case 'new-selskin':
				$AdminUI->set_page_manual_link( 'pick-skin-for-new-collection' );
				break;
			case 'new-name':
				$AdminUI->set_page_manual_link( 'new-collection-settings' );
				break;
			default:
				$AdminUI->set_page_manual_link( 'create-collection-select-type' );
				break;
		}
		break;

	case 'delete':
		// Page to confirm a blog deletion
		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );

		// We should activate toolbar menu items for this controller and tab
		$activate_collection_toolbar = true;
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


switch( $action )
{
	case 'new':
		//$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'collections/views/_coll_sel_type.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-selskin':
	case 'new-installskin':
		//	$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'skins/views/_coll_sel_skin.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-name':
	case 'create': // in case of validation error
		//$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'copy':
		$AdminUI->disp_payload_begin();

		$next_action = 'duplicate';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		$delete_notes = array();

		// Check how many posts and comments will be deleted
		$number_of_items = $edited_Blog->get_number_of_items();
		if( $number_of_items > 0 )
		{ // There is at least one item
			$number_of_comments = $edited_Blog->get_number_of_comments();
			if( $number_of_comments > 0 )
			{ // There is at least one comment
				$delete_notes[] = array( sprintf( T_('WARNING: This collection contains %d items and %d comments.'), $number_of_items, $number_of_comments ), 'warning' );
			}
			else
			{
				$delete_notes[] = array( sprintf( T_('WARNING: This collection contains %d items.'), $number_of_items ), 'warning' );
			}
		}

		// Check if the deleting blog is used as default blog and Display a warning
		if( $default_Blog = & get_setting_Blog( 'default_blog_ID' ) && $default_Blog->ID == $edited_Blog->ID )
		{ // Default blog
			$delete_notes[] = array( T_('WARNING: You are about to delete the default collection.'), 'warning' );
		}
		if( $info_Blog = & get_setting_Blog( 'info_blog_ID' ) && $info_Blog->ID == $edited_Blog->ID  )
		{ // Info blog
			$delete_notes[] = array( T_('WARNING: You are about to delete the collection used for info pages.'), 'warning' );
		}
		if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $login_Blog->ID == $edited_Blog->ID  )
		{ // Login blog
			$delete_notes[] = array( T_('WARNING: You are about to delete the collection used for login/registration pages.'), 'warning' );
		}
		if( $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) && $msg_Blog->ID == $edited_Blog->ID  )
		{ // Messaging blog
			$delete_notes[] = array( T_('WARNING: You are about to delete the collection used for messaging pages.'), 'warning' );
		}

		$delete_notes[] = array( T_('Note: Some files in this collection\'s fileroot may be linked to users or to other collections posts and comments. Those files will ALSO be deleted, which may be undesirable!'), 'note' );
		$edited_Blog->confirm_delete( sprintf( T_('Delete collection &laquo;%s&raquo;?'), $edited_Blog->get_name() ), 'collection', $action,
			get_memorized( 'action' ), $delete_notes );
		break;


	default:
		// List the blogs:
		$AdminUI->disp_payload_begin();
		// Display VIEW:
		switch( $tab )
		{
			case 'site_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_site.form.php' );
				break;

			case 'blog_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_blog.form.php' );
				break;
		}
		$AdminUI->disp_payload_end();

}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>