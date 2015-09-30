<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Store/retrieve preferred tab from UserSettings:
$UserSettings->param_Request( 'tab', 'pref_coll_settings_tab', 'string', 'general', true /* memorize */, true /* force */ );
if( $tab == 'widgets' )
{	// This is another controller!
	require_once dirname(__FILE__).'/../widgets/widgets.ctrl.php';
	return;
}
else if( $tab == 'manage_skins' )
{	// This is another controller!
	require_once dirname(__FILE__).'/../skins/skins.ctrl.php';
	return;
}


param_action( 'edit' );

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

// Check permissions on requested blog and autoselect an appropriate blog if necessary.
// This will prevent a fat error when switching tabs and you have restricted perms on blog properties.
if( $selected = autoselect_blog( 'blog_properties', 'edit' ) ) // Includes perm check
{	// We have a blog to work on:

	if( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
	{	// Selected a new blog:
		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Blog = & $BlogCache->get_by_ID( $blog );
	}

	/**
	 * @var Blog
	 */
	$edited_Blog = & $Blog;
}
else
{	// We could not find a blog we have edit perms on...
	// Note: we may still have permission to edit categories!!
	$Messages->add( T_('Sorry, you have no permission to edit blog properties.'), 'error' );
	// redirect to blog list:
	header_redirect( $admin_url.'?ctrl=dashboard' );
	// EXITED:
}

memorize_param( 'blog', 'integer', -1 );	// Needed when generating static page for example

param( 'skinpage', 'string', '' );
if( $tab == 'skin' && $skinpage != 'selection' )	// If not screen selection => screen settings
{
	$SkinCache = & get_SkinCache();
	/**
	 * @var Skin
	 */
	$normal_Skin = & $SkinCache->get_by_ID( $Blog->get_setting( 'normal_skin_ID' ) );
	$mobile_Skin = & $SkinCache->get_by_ID( $Blog->get_setting( 'mobile_skin_ID' ) );
	$tablet_Skin = & $SkinCache->get_by_ID( $Blog->get_setting( 'tablet_skin_ID' ) );
}


if( ( $tab == 'perm' || $tab == 'permgroup' )
	&& ( empty($blog) || ! $Blog->advanced_perms ) )
{	// We're trying to access advanced perms but they're disabled!
	$tab = 'general';	// the screen where you can enable advanced perms
	if( $action == 'update' )
	{ // make sure we don't update anything here
		$action = 'edit';
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'update':
		// Update DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		$update_redirect_url = '?ctrl=coll_settings&tab='.$tab.'&blog='.$blog;

		switch( $tab )
		{
			case 'general':
			case 'urls':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'home':
			case 'features':
			case 'comments':
			case 'other':
			case 'more':
				if( $edited_Blog->load_from_Request( array( $tab ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'seo':
				if( $edited_Blog->load_from_Request( array( 'seo' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'skin':
				if( $skinpage == 'selection' )
				{
					if( $edited_Blog->load_from_Request( array() ) )
					{ // Commit update to the DB:
						$edited_Blog->dbupdate();
						$Messages->add( T_('The blog skin has been changed.')
											.' <a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$edited_Blog->ID.'">'.T_('Edit...').'</a>', 'success' );
						if( ( !$Session->is_mobile_session() && !$Session->is_tablet_session() && param( 'normal_skin_ID', 'integer', NULL ) !== NULL ) ||
						    ( $Session->is_mobile_session() && param( 'mobile_skin_ID', 'integer', NULL ) !== NULL ) ||
						    ( $Session->is_tablet_session() && param( 'tablet_skin_ID', 'integer', NULL ) !== NULL ) )
						{	// Redirect to blog home page if we change the skin for current device type
							header_redirect( $edited_Blog->gen_blogurl() );
						}
						else
						{	// Redirect to admin skins page if we change the skin for another device type
							header_redirect( $admin_url.'?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID );
						}
					}
				}
				else
				{ // Update params/Settings
					$normal_Skin->load_params_from_Request();
					$mobile_Skin->load_params_from_Request();
					$tablet_Skin->load_params_from_Request();

					if(	! param_errors_detected() )
					{	// Update settings:
						$normal_Skin->dbupdate_settings();
						$mobile_Skin->dbupdate_settings();
						$tablet_Skin->dbupdate_settings();
						$Messages->add( T_('Skin settings have been updated'), 'success' );
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( $update_redirect_url, 303 ); // Will EXIT
					}
				}
				break;

			case 'plugin_settings':
				// Update Plugin params/Settings
				load_funcs('plugins/_plugin.funcs.php');

				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$pluginsettings = $loop_Plugin->get_coll_setting_definitions( $tmp_params = array('for_editing'=>true) );
					if( empty($pluginsettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginsettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'CollSettings', $Blog );
					}

					// Let plugins process settings
					$Plugins->call_method( $loop_Plugin->ID, 'PluginCollSettingsUpdateAction', $tmp_params = array() );
				}

				if(	! param_errors_detected() )
				{	// Update settings:
					$Blog->dbupdate();
					$Messages->add( T_('Plugin settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'advanced':
				if( $edited_Blog->load_from_Request( array( 'pings', 'cache', 'authors', 'login', 'styles' ) ) )
				{ // Commit update to the DB:
					if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
					{
						$cache_status = param( 'cache_enabled', 'integer', 0 );
						load_funcs( 'collections/model/_blog.funcs.php' );
						$result = set_cache_enabled( 'cache_enabled', $cache_status, $edited_Blog->ID, false );
						if( $result != NULL )
						{
							list( $status, $message ) = $result;
							$Messages->add( $message, $status );
						}
					}

					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'perm':
				blog_update_perms( $blog, 'user' );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;

			case 'permgroup':
				blog_update_perms( $blog, 'group' );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;

			case 'chapters':
				param( 'category_ordering', 'string' );
				$edited_Blog->set_setting( 'category_ordering', get_param( 'category_ordering' ) );
				$edited_Blog->dbupdate();
				$Messages->add( T_('Category ordering has been changed.'), 'success' );
				header_redirect( param( 'redirect_to', 'url', '?ctrl=chapters&blog='.$edited_Blog->ID ), 303 ); // Will EXIT
				break;
		}

		break;

	case 'update_type':
		// Update DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		$update_redirect_url = '?ctrl=coll_settings&tab='.$tab.'&blog='.$blog;

		param( 'reset', 'boolean', '' );
		param( 'type', 'string', '' );
		param_check_not_empty( 'type', T_('Please select a type') );

		if( param_errors_detected() )
		{
			$action = 'type';
			break;
		}

		if( $reset )
		{	// Reset all settings
			// Remove previous widgets, plugin and skin settings
			$DB->query( 'DELETE FROM T_widget WHERE wi_coll_ID = '.$DB->quote( $edited_Blog->ID ) );
			$DB->query( 'DELETE FROM T_coll_settings
				WHERE cset_coll_ID = '.$DB->quote( $edited_Blog->ID ).'
				AND ( cset_name LIKE "skin%" OR cset_name LIKE "plugin%" )' );
			// ADD DEFAULT WIDGETS:
			load_funcs( 'widgets/_widgets.funcs.php' );
			insert_basic_widgets( $edited_Blog->ID, false, $type );
		}

		$edited_Blog->init_by_kind( $type, $edited_Blog->get( 'name' ), $edited_Blog->get( 'shortname' ), $edited_Blog->get( 'urlname' ) );
		$edited_Blog->dbupdate();

		$Messages->add( T_('The collection type has been updated'), 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $update_redirect_url, 303 ); // Will EXIT

		break;

	case 'enable_setting':
	case 'disable_setting':
		// Update blog settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		$update_redirect_url = $admin_url.'?ctrl=dashboard';

		$setting = param( 'setting', 'string', '' );
		$setting_value = ( $action == 'enable_setting' ? '1' : '0' );

		switch( $setting )
		{
			case 'fav':
				// Favorite Blog
				$edited_Blog->set( 'favorite', $setting_value );
				$result_message = T_('The collection setting has been updated.');
				break;

			case 'page_cache':
				// Page caching
				$edited_Blog->set_setting( 'cache_enabled', $setting_value );
				if( $setting_value )
				{ // If we are enabling the page caching we should also enable AJAX forms
					$edited_Blog->set_setting( 'ajax_form_enabled', 1 );
				}
				$result_message = $setting_value ?
						T_('Page caching has been turned on for the collection.') :
						T_('Page caching has been turned off for the collection.');
				break;

			case 'block_cache':
				// Widget/block caching
				$edited_Blog->set_setting( 'cache_enabled_widgets', $setting_value );
				$result_message = $setting_value ?
						T_('Block caching has been turned on for the collection.') :
						T_('Block caching has been turned off for the collection.');
				break;

			default:
				// Incorrect setting name
				header_redirect( $update_redirect_url, 303 ); // Will EXIT
				break;
		}

		// Update the changed settings
		$edited_Blog->dbupdate();

		$Messages->add( $result_message, 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $update_redirect_url, 303 ); // Will EXIT

		break;
}

$AdminUI->set_path( 'collections', $tab );


/**
 * Display page header, menus & messages:
 */
$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
						array( 'ctrl' => 'coll_settings', 'tab' => $tab, 'action' => 'edit' ) );


$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=dashboard&amp;blog=$blog$' ) );
switch( $AdminUI->get_path(1) )
{
	case 'general':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'general-collection-settings' );
		if( $action == 'type' )
		{
			$AdminUI->breadcrumbpath_add( T_('Collection type'), '?ctrl=coll_settings&amp;blog=$blog$&amp;action=type&amp;tab='.$tab );
		}
		// Init JS to autcomplete the user logins
		init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
		break;

	case 'home':
		$AdminUI->set_path( 'collections', 'features', $tab );
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->breadcrumbpath_add( T_('Front page'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'collection-front-page-settings' );
		break;

	case 'features':
		$AdminUI->set_path( 'collections', 'features', $tab );
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
		$AdminUI->breadcrumbpath_add( T_('Posts'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'post-features' );
		break;

	case 'comments':
		$AdminUI->set_path( 'collections', 'features', $tab );
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
		$AdminUI->breadcrumbpath_add( T_('Comments'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'comment-features' );
		break;

	case 'other':
		$AdminUI->set_path( 'collections', 'features', $tab );
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
		$AdminUI->breadcrumbpath_add( T_('Other displays'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'features-others' );
		break;

	case 'more':
		$AdminUI->set_path( 'collections', 'features', $tab );
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
		$AdminUI->breadcrumbpath_add( T_('More'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'features-more' );
		break;

	case 'skin':
		$AdminUI->set_path( 'collections', 'skin', 'current_skin' );
		$AdminUI->breadcrumbpath_add( T_('Skins'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		if( $skinpage == 'selection' )
		{
			$AdminUI->breadcrumbpath_add( T_('Skin selection'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab.'&amp;skinpage=selection' );
		}
		else
		{
			init_colorpicker_js();
			$AdminUI->breadcrumbpath_add( T_('Skins for this blog'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		}
		$AdminUI->set_page_manual_link( 'skins-for-this-blog' );
		break;

	case 'plugin_settings':
		$AdminUI->breadcrumbpath_add( T_('Plugins'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'blog-plugin-settings' );
		break;

	case 'urls':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('URLs'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'url-settings' );
		break;

	case 'seo':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('SEO'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'seo-settings' );
		break;

	case 'advanced':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('Advanced settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'advanced-collection-settings' );
		break;

	case 'perm':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		load_funcs( 'collections/views/_coll_perm_view.funcs.php' );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('User permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'advanced-user-permissions' );
		break;

	case 'permgroup':
		$AdminUI->set_path( 'collections', 'settings', $tab );
		load_funcs( 'collections/views/_coll_perm_view.funcs.php' );
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
		$AdminUI->breadcrumbpath_add( T_('Group permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->set_page_manual_link( 'advanced-group-permissions' );
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


// Begin payload block:
$AdminUI->disp_payload_begin();


// Display VIEW:
switch( $AdminUI->get_path(1) )
{
	case 'features':
		switch( $AdminUI->get_path(2) )
		{
			case 'features';
				$AdminUI->disp_view( 'collections/views/_coll_features.form.php' );
				break;
			case 'comments';
				$AdminUI->disp_view( 'collections/views/_coll_comments.form.php' );
				break;
			case 'other';
				$AdminUI->disp_view( 'collections/views/_coll_other.form.php' );
				break;
			case 'more';
				$AdminUI->disp_view( 'collections/views/_coll_more.form.php' );
				break;
			default:
				$AdminUI->disp_view( 'collections/views/_coll_home.form.php' );
				break;
		}
		break;

	case 'skin':
		if( $skinpage == 'selection' )
		{
			$AdminUI->disp_view( 'skins/views/_coll_skin.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'skins/views/_coll_skin_settings.form.php' );
		}
		break;

	case 'plugin_settings':
		$AdminUI->disp_view( 'collections/views/_coll_plugin_settings.form.php' );
		break;

	case 'settings':
		switch( $AdminUI->get_path(2) )
		{
			case 'general':
				if( $action == 'type' )
				{	// Form to change type
					$AdminUI->disp_view( 'collections/views/_coll_type.form.php' );
				}
				else
				{	// General settings of blog
					$next_action = 'update';
					$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );
				}
				break;
			case 'urls':
				$AdminUI->disp_view( 'collections/views/_coll_urls.form.php' );
				break;
			case 'seo':
				$AdminUI->disp_view( 'collections/views/_coll_seo.form.php' );
				break;
			case 'advanced':
				$AdminUI->disp_view( 'collections/views/_coll_advanced.form.php' );
				break;
			case 'perm':
				$AdminUI->disp_view( 'collections/views/_coll_user_perm.form.php' );
				break;
			case 'permgroup':
				$AdminUI->disp_view( 'collections/views/_coll_group_perm.form.php' );
				break;
		}
		break;
}

// End payload block:
$AdminUI->disp_payload_end();


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>