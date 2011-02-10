<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

param_action( 'edit' );
param( 'tab', 'string', 'general', true );

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
	// redirect to blog list:
	header_redirect( '?ctrl=collections' );
	// EXITED:
	$Messages->add( T_('Sorry, you have no permission to edit blog properties.'), 'error' );
	$action = 'nil';
	$tab = '';
}

memorize_param( 'blog', 'integer', -1 );	// Needed when generating static page for example

param( 'skinpage', 'string', '' );
if( $tab == 'skin' && $skinpage != 'selection' )	// If not screen selection => screen settings
{
	$SkinCache = & get_SkinCache();
	/**
	 * @var Skin
	 */
	$edited_Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
}


if( ( $tab == 'perm' || $tab == 'permgroup' )
	&& ( empty($blog) || ! $Blog->advanced_perms ) )
{	// We're trying to access advanced perms but they're disabled!
	$tab = 'features';	// the screen where you can enable advanced perms
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
	case 'edit':
	case 'filter1':
	case 'filter2':
		// Edit collection form (depending on tab):
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		param( 'preset', 'string', '' );

		$edited_Blog->load_presets( $preset );

		break;

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

			case 'features':
				if( $edited_Blog->load_from_Request( array( 'features' ) ) )
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
						header_redirect( $edited_Blog->gen_blogurl() );
					}
				}
				else
				{ // Update params/Settings
					$edited_Skin->load_params_from_Request();
	
					if(	! param_errors_detected() )
					{	// Update settings:
						$edited_Skin->dbupdate_settings();
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
				$old_cache_status = $edited_Blog->get_setting('cache_enabled');
				if( $edited_Blog->load_from_Request( array( 'pings', 'cache', 'authors' ) ) )
				{ // Commit update to the DB:
					$new_cache_status = $edited_Blog->get_setting('cache_enabled');

					load_class( '_core/model/_pagecache.class.php', 'PageCache' );
					$PageCache = new PageCache( $edited_Blog );

					if( $old_cache_status == false && $new_cache_status == true )
					{ // Caching has been turned ON:
						if( $PageCache->cache_create() )
						{
							$Messages->add( T_('Page caching has been enabled for this blog.'), 'success' );
						}
						else
						{
							$Messages->add( T_('Page caching could not be enabled for this blog. Check /cache/ folder file permissions.'), 'error' );
							$edited_Blog->set_setting('cache_enabled', 0 );
						}
					}
					elseif( $old_cache_status == true && $new_cache_status == false )
					{ // Caching has been turned OFF:
						$PageCache->cache_delete();
						$Messages->add( T_('Page caching has been disabled for this blog. All cache contents have been purged.'), 'note' );
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
		}

		break;
}

$AdminUI->set_path( 'blogs',  $tab  );


/**
 * Display page header, menus & messages:
 */
$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
											array( 'ctrl' => 'coll_settings', 'tab' => $tab, 'action' => 'edit' ),
											T_('List'), '?ctrl=collections&amp;blog=0' );


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Blog settings'), '?ctrl=coll_settings&amp;blog=$blog$' );
switch( $AdminUI->get_path(1) )
{
	case 'general':
		$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'features':
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'skin':
		if( $skinpage == 'selection' )
		{
			$AdminUI->breadcrumbpath_add( T_('Skin selection'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab.'&amp;skinpage=selection' );
		}
		else
		{
			$AdminUI->breadcrumbpath_add( T_('Settings for current skin'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		}
		break;

	case 'plugin_settings':
		$AdminUI->breadcrumbpath_add( T_('Blog specific plugin settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'urls':
		$AdminUI->breadcrumbpath_add( T_('URL configuration'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'seo':
		$AdminUI->breadcrumbpath_add( T_('SEO settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'advanced':
		$AdminUI->breadcrumbpath_add( T_('Advanced settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'perm':
		$AdminUI->breadcrumbpath_add( T_('User permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'permgroup':
		$AdminUI->breadcrumbpath_add( T_('Group permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
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
	case 'general':
		$next_action = 'update';
		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );
		break;

	case 'features':
		$AdminUI->disp_view( 'collections/views/_coll_features.form.php' );
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

// End payload block:
$AdminUI->disp_payload_end();


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.37  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.36  2010/10/05 12:53:46  efy-asimo
 * Move twitter_unlink into twitter_plugin
 *
 * Revision 1.35  2010/10/01 13:56:32  efy-asimo
 * twitter plugin save contact and fix
 *
 * Revision 1.34  2010/09/29 13:19:02  efy-asimo
 * Twitter user unlink, and twitter config params move to plugin
 *
 * Revision 1.33  2010/07/06 08:17:39  efy-asimo
 * Move "Multiple authors" block to Blog setings advanced tab. Fix validating urlname when user has no blog_admin permission.
 *
 * Revision 1.32  2010/02/26 15:52:20  efy-asimo
 * combine skin and skin settings tab into one single tab
 *
 * Revision 1.31  2010/02/08 17:52:07  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.30  2010/01/30 18:55:20  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.29  2010/01/11 16:06:23  efy-yury
 * More crumbs
 *
 * Revision 1.28  2010/01/10 17:57:33  efy-yury
 * minor: replace $redirect_url on constant value
 *
 * Revision 1.27  2010/01/09 21:07:06  blueyed
 * fix typo
 *
 * Revision 1.26  2010/01/09 13:30:12  efy-yury
 * added redirect 303 for prevent dublicate sql executions
 *
 * Revision 1.25  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.24  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.23  2009/11/30 04:31:38  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.22  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.21  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.20  2009/09/14 12:42:25  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.19  2009/05/26 19:31:56  fplanque
 * Plugins can now have Settings that are specific to each blog.
 *
 * Revision 1.18  2009/05/24 21:14:38  fplanque
 * _skin.class.php can now provide skin specific settings.
 * Demo: the custom skin has configurable header colors.
 * The settings can be changed through Blog Settings > Skin Settings.
 * Anyone is welcome to extend those settings for any skin you like.
 *
 * Revision 1.17  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.16  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.15  2008/09/28 08:06:07  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.14  2008/09/28 05:05:07  fplanque
 * minor
 *
 * Revision 1.13  2008/09/27 00:48:32  fplanque
 * caching step 0.
 *
 * Revision 1.12  2008/03/21 10:45:55  yabs
 * validation
 *
 * Revision 1.11  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.10  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.9  2007/12/27 01:58:49  fplanque
 * additional SEO
 *
 * Revision 1.8  2007/11/25 18:20:38  fplanque
 * additional SEO settings
 *
 * Revision 1.7  2007/11/25 14:28:18  fplanque
 * additional SEO settings
 *
 * Revision 1.6  2007/11/24 21:41:12  fplanque
 * additional SEO settings
 *
 * Revision 1.5  2007/11/02 02:45:51  fplanque
 * refactored blog settings / UI
 *
 * Revision 1.4  2007/10/08 10:24:49  fplanque
 * UI improvement
 *
 * Revision 1.3  2007/09/29 03:42:12  fplanque
 * skin install UI improvements
 *
 * Revision 1.2  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.1  2007/06/25 10:59:30  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.17  2007/06/12 23:16:04  fplanque
 * non admins can no longer change admin blog perms
 *
 * Revision 1.16  2007/05/31 03:02:21  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.15  2007/05/13 18:49:54  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.14  2007/04/26 00:11:07  fplanque
 * (c) 2007
 *
 * Revision 1.13  2007/03/07 02:38:58  fplanque
 * do some recovery on incorrect $blog
 *
 * Revision 1.12  2006/12/19 20:33:35  blueyed
 * doc/todo
 *
 * Revision 1.11  2006/12/18 03:20:41  fplanque
 * _header will always try to set $Blog.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.10  2006/12/17 02:42:21  fplanque
 * streamlined access to blog settings
 *
 * Revision 1.9  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.8  2006/12/10 23:56:26  fplanque
 * Worfklow stuff is now hidden by default and can be enabled on a per blog basis.
 *
 * Revision 1.7  2006/12/03 19:00:30  blueyed
 * Moved collection perm JavaScript to the views, as per todo
 *
 * Revision 1.6  2006/12/03 16:37:14  fplanque
 * doc
 *
 * Revision 1.5  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.4  2006/11/18 17:57:17  blueyed
 * blogperms_switch_layout() moved/renamed
 */
?>