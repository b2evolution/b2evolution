<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
		$BlogCache = & get_Cache( 'BlogCache' );
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
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		switch( $tab )
		{
			case 'general':
			case 'urls':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
				}
				break;

			case 'features':
				if( $edited_Blog->load_from_Request( array( 'features' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
				}
				break;

			case 'seo':
				if( $edited_Blog->load_from_Request( array( 'seo' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
				}
				break;

			case 'skin':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog skin has been changed.')
										.' <a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$edited_Blog->ID.'">'.T_('Edit...').'</a>', 'success' );
					header_redirect( $edited_Blog->gen_blogurl() );
				}
				break;

			case 'advanced':
				$old_cache_status = $edited_Blog->get_setting('cache_enabled');
				if( $edited_Blog->load_from_Request( array( 'pings', 'cache' ) ) )
				{ // Commit update to the DB:
					$new_cache_status =  $edited_Blog->get_setting('cache_enabled');
					if( $old_cache_status == false && $new_cache_status == true )
					{ // Caching has been turned ON:
						if( $edited_Blog->cache_create() )
						{
							$Messages->add( T_('Caching has been enabled for this blog.'), 'success' );
						}
						else
						{
							$Messages->add( T_('Caching could not be enabled for this blog. Check /cache/ folder file permissions.'), 'error' );
							$edited_Blog->set_setting('cache_enabled', 0 );
						}
					}
					elseif( $old_cache_status == true && $new_cache_status == false )
					{ // Caching has been turned OFF:
						$edited_Blog->cache_delete();
						$Messages->add( T_('Caching has been disabled for this blog. All cache contents have been purged.'), 'note' );
					}


					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
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
		$AdminUI->disp_view( 'skins/views/_coll_skin.view.php' );
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