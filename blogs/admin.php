<?php
/**
 * This is the main dispatcher for the admin interface.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package main
 *
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/conf/_config.php';


/**
 * @global boolean Is this an admin page? Use {@link is_admin_page()} to query it, because it may change.
 */
$is_admin_page = true;


$login_required = true;
require_once $inc_path.'_main.inc.php';


// Check global permission:
if( ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require $view_path.'errors/_access_denied.inc.php';
}


/*
 * Asynchronous processing options that may be required on any page
 */
require_once $inc_path.'_async.inc.php';


/*
 * Get the blog from param, defaulting to the last selected one for this user:
 * we need it for quite a few of the menu urls
 */
$user_selected_blog = (int)$UserSettings->get('selected_blog');
$BlogCache = & get_Cache( 'BlogCache' );
if( param( 'blog', 'integer', NULL, true ) === NULL      // We got no explicit blog choice (not even '0' for 'no blog'):
	|| ($blog != 0 && ! ($Blog = & $BlogCache->get_by_ID( $blog, false, false )) )) // or we requested a nonexistent blog
{ // Try the memorized blog from the previous action:
	$blog = $user_selected_blog;
	if( ! ($Blog = & $BlogCache->get_by_ID( $blog, false, false ) ) )
	{	// That one doesn't exist either...
		$blog = 0;
	}
}
elseif( $blog != $user_selected_blog )
{ // We have selected a new & valid blog. Update UserSettings for selected blog:
	set_working_blog( $blog );
}


// bookmarklet, upload (upload actually means sth like: select img for post):
param( 'mode', 'string', '', true );


/*
 * Get the Admin skin
 * TODO: Allow setting through GET param (dropdown in backoffice), respecting a checkbox "Use different setting on each computer" (if cookie_state handling is ready)
 */
$admin_skin = $UserSettings->get( 'admin_skin' );
$admin_skin_path = dirname(__FILE__).'/'.$adminskins_subdir.'%s/_adminUI.class.php';

if( ! $admin_skin || ! file_exists( sprintf( $admin_skin_path, $admin_skin ) ) )
{ // there's no skin for the user
	if( !$admin_skin )
	{
		$Debuglog->add( 'The user has no admin skin set.', 'skin' );
	}
	else
	{
		$Debuglog->add( 'The admin skin ['.$admin_skin.'] set by the user does not exist.', 'skin' );
	}

	$admin_skin = $Settings->get( 'admin_skin' );

	if( !$admin_skin || !file_exists( sprintf( $admin_skin_path, $admin_skin ) ) )
	{ // even the default skin does not exist!
		if( !$admin_skin )
		{
			$Debuglog->add( 'There is no default admin skin set!', 'skin' );
		}
		else
		{
			$Debuglog->add( 'The default admin skin ['.$admin_skin.'] does not exist!', array('skin','error') );
		}

		if( file_exists(sprintf( $admin_skin_path, 'legacy' )) )
		{ // 'legacy' does exist
			$admin_skin = 'legacy';

			$Debuglog->add( 'Falling back to legacy admin skin.', 'skin' );
		}
		else
		{ // get the first one available one
			$admin_skin_dirs = get_admin_skins();

			if( $admin_skin_dirs === false )
			{
				$Debuglog->add( 'No admin skin found! Check that the path '.dirname(__FILE__).'/'.$adminskins_subdir.' exists.', array('skin','error') );
			}
			elseif( empty($admin_skin_dirs) )
			{ // No admin skin directories found
				$Debuglog->add( 'No admin skin found! Check that there are skins in '.dirname(__FILE__).'/'.$adminskins_subdir.'.', array('skin','error') );
			}
			else
			{
				$admin_skin = array_shift($admin_skin_dirs);
				$Debuglog->add( 'Falling back to first available skin.', 'skin' );
			}
		}
	}
}
if( ! $admin_skin )
{
	$Debuglog->display( 'No admin skin available!', '', true, 'skin' );
	exit();
}

$Debuglog->add( 'Using admin skin &laquo;'.$admin_skin.'&raquo;', 'skin' );

/**
 * Load the AdminUI class for the skin.
 */
require_once dirname(__FILE__).'/'.$adminskins_subdir.$admin_skin.'/_adminUI.class.php';
/**
 * This is the Admin UI object which handles the UI for the backoffice.
 *
 * @global AdminUI
 */
$AdminUI = & new AdminUI();


/*
 * Construct the menu:
 * It is useful to have the whole structure in case we want to display DHTML drop down menus for example
 * Thus we should make this menu as complete as possible and change it as little as possible with dynamic functions later.
 * TODO: 'href' should be splitted into 'ctrl' and 'params'/'params_eval'
 */
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'dashboard' => array(
				'text' => T_('Dashboard'),
				'href' => 'admin.php?ctrl=dashboard&amp;blog='.$blog,
				'style' => 'font-weight: bold;'
				),

			'items' => array(
				'text' => T_('Posts / Comments'),
				'href' => 'admin.php?ctrl=items&amp;blog='.$blog.'&amp;filter=restore',
			),

			'files' => array(
				'text' => T_('Files'),
				'title' => T_('File management'),
				'href' => 'admin.php?ctrl=files',
				'perm_eval' => 'global $Settings; return $Settings->get( \'fm_enabled\' ) && $current_User->check_perm( \'files\', \'view\' );',
				'entries' => array(
					'browse' => array(
						'text' => T_('Browse'),
						'href' => 'admin.php?ctrl=files' ),
					'upload' => array(
						'text' => T_('Upload'),
						'href' => 'admin.php?ctrl=upload' ),
					),
			),

			'stats' => array(
				'text' => T_('Stats'),
				'perm_name' => 'stats',
				'perm_level' => 'view',
				'href' => 'admin.php?ctrl=stats',
				'entries' => array(
					'summary' => array(
						'text' => T_('Hit summary'),
						'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;blog='.$blog ),
					'browserhits' => array(
						'text' => T_('Browser hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=browserhits&amp;blog='.$blog ),
					'refsearches' => array(
						'text' => T_('Search B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;blog='.$blog ),
					'referers' => array(
						'text' => T_('Referered B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
					'other' => array(
						'text' => T_('Direct B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
					'robots' => array(
						'text' => T_('Robot hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=robots&amp;blog='.$blog ),
					'syndication' => array(
						'text' => T_('XML hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=syndication&amp;blog='.$blog ),
					'useragents' => array(
						'text' => T_('User agents'),
						'href' => 'admin.php?ctrl=stats&amp;tab=useragents&amp;blog='.$blog ),
					'domains' => array(
						'text' => T_('Referring domains'),
						'href' => 'admin.php?ctrl=stats&amp;tab=domains&amp;blog='.$blog ),
					'sessions' => array(
						'text' => T_('Sessions'),
						'href' => 'admin.php?ctrl=stats&amp;tab=sessions&amp;blog='.$blog ),
				)
			),

			'users' => array(
				'text' => T_('Users'),
				'title' => T_('User management'),
				'perm_name' => 'users',
				'perm_level' => 'view',
				'text_noperm' => T_('My profile'),	// displayed if perm not granted
				'href' => 'admin.php?ctrl=users',
			),

		)
	);


// BLOG SETTINGS:
$coll_settings_perm = 'global $ctrl, $current_User; return $ctrl != "collections"
			&& $current_User->check_perm( "blog_properties", "any", false, '.$blog.' );';
$coll_chapters_perm = 'global $ctrl, $current_User; return $ctrl != "collections"
			&& $current_User->check_perm( "blog_cats", "", false, '.$blog.' );';
if( $blog && $coll_settings_perm )
{	// Default: show Generel Blog Settings
	$default_page = 'admin.php?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog;
}
elseif( $blog && $coll_chapters_perm )
{	// Default: show categories
	$default_page = 'admin.php?ctrl=chapters&amp;blog='.$blog;
}
else
{	// Default: Show list of blogs
	$default_page = 'admin.php?ctrl=collections';
}
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'blogs' => array(
				'text' => T_('Blog settings'),
				'href' => $default_page,
				'entries' => array(
					'general' => array(
						'text' => T_('General'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'features' => array(
						'text' => T_('Features'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'skin' => array(
						'text' => T_('Skin'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'display' => array(
						'text' => T_('Display'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=display&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'widgets' => array(
						'text' => T_('Widgets'),
						'href' => 'admin.php?ctrl=widgets&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'chapters' => array(
						'text' => T_('Categories'),
						'href' => 'admin.php?ctrl=chapters&amp;blog='.$blog,
						'perm_eval' => $coll_chapters_perm ),
					'urls' => array(
						'text' => T_('URLs'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=urls&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'advanced' => array(
						'text' => T_('Advanced'),
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'perm' => array(
						'text' => T_('User perms'), // keep label short
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=perm&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
					'permgroup' => array(
						'text' => T_('Group perms'), // keep label short
						'href' => 'admin.php?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$blog,
						'perm_eval' => $coll_settings_perm ),
				)
			),

			'options' => array(
				'text' => T_('Global settings'),
				'perm_name' => 'options',
				'perm_level' => 'view',
				'href' => 'admin.php?ctrl=settings',
				'entries' => array(
					'general' => array(
						'text' => T_('General'),
						'href' => 'admin.php?ctrl=settings' ),
					'features' => array(
						'text' => T_('Features'),
						'href' => 'admin.php?ctrl=features' ),
					'skins' => array(
						'text' => T_('Skins'),
						'href' => 'admin.php?ctrl=skins'),
					'plugins' => array(
						'text' => T_('Plugins'),
						'href' => 'admin.php?ctrl=plugins'),
					'antispam' => array(
						'text' => T_('Antispam'),
						'href' => 'admin.php?ctrl=set_antispam'),
					'regional' => array(
						'text' => T_('Regional'),
						'href' => 'admin.php?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
					'files' => array(
						'text' => T_('Files'),
						'href' => 'admin.php?ctrl=fileset' ),
					'filetypes' => array(
						'text' => T_('File types'),
						'href' => 'admin.php?ctrl=filetypes' ),
					'types' => array(
						'text' => T_('Post types'),
						'title' => T_('Post types management'),
						'href' => 'admin.php?ctrl=itemtypes'),
					'statuses' => array(
						'text' => T_('Post statuses'),
						'title' => T_('Post statuses management'),
						'href' => 'admin.php?ctrl=itemstatuses'),
				)
			),

			'tools' => array(
				'text' => T_('Tools'),
				'href' => 'admin.php?ctrl=crontab',
				'perm_name' => 'options',
				'perm_level' => 'view',	// FP> This assumes that we don't let regular users access the tools, including plugin tools.
				'entries' =>  array(
					'cron' => array(
						'text' => T_('Scheduler'),
						'perm_name' => 'options',
						'perm_level' => 'view',
						'href' => 'admin.php?ctrl=crontab' ),
					'system' => array(
						'text' => T_('System'),
						'perm_name' => 'options',
						'perm_level' => 'view',
						'href' => 'admin.php?ctrl=system' ),
					'antispam' => array(
						'text' => T_('Antispam'),
						'perm_name' => 'spamblacklist',
						'perm_level' => 'view',
						'href' => 'admin.php?ctrl=antispam'	),
					'' => array(	// fp> '' is dirty
						'text' => T_('Misc'),
						'href' => 'admin.php?ctrl=tools' ),
				)
			),

		)
	);


$Plugins->trigger_event( 'AdminAfterMenuInit' );


/*
 * Pass over to controller...
 */

// Get requested controller and memorize it:
param( 'ctrl', '/^[a-z0-9_]+$/', $default_ctrl, true );


// Redirect old-style URLs (e.g. /admin/plugins.php), if they come here because the webserver maps "/admin/" to "/admin.php"
// NOTE: this is just meant as a transformation from pre-1.8 to 1.8!
if( ! empty( $_SERVER['PATH_INFO'] ) && $_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF'] ) // the "!= PHP_SELF" check seems needed by IIS..
{
	// Try to find the appropriate controller (ctrl) setting
	foreach( $ctrl_mappings as $k => $v )
	{
		if( preg_match( '~'.preg_quote( $_SERVER['PATH_INFO'], '~' ).'$~', $v ) )
		{
			$ctrl = $k;
			break;
		}
	}

	// Sanitize QUERY_STRING
	if( ! empty( $_SERVER['QUERY_STRING'] ) )
	{
		$query_string = explode( '&', $_SERVER['QUERY_STRING'] );
		foreach( $query_string as $k => $v )
		{
			$query_string[$k] = strip_tags($v);
		}
		$query_string = '&'.implode( '&', $query_string );
	}
	else
	{
		$query_string = '';
	}

	header_redirect( url_add_param( $admin_url, 'ctrl='.$ctrl.$query_string, '&' ), true );
	exit;
}


// Check matching controller file:
if( !isset($ctrl_mappings[$ctrl]) )
{
	debug_die( 'The requested controller ['.$ctrl.'] does not exist.' );
}

// Call the requested controller:
require $control_path.$ctrl_mappings[$ctrl];

// log the hit on this page (according to settings) if the admin_skin hasn't already done so:
$Hit->log();

/*
 * $Log$
 * Revision 1.19  2007/01/24 00:48:57  fplanque
 * Refactoring
 *
 * Revision 1.18  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.17  2006/08/26 20:32:48  fplanque
 * fixed redirects
 *
 * Revision 1.16  2006/08/05 23:37:03  fplanque
 * no message
 *
 * Revision 1.14  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.13  2006/06/13 21:49:14  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.12.2.1  2006/06/12 20:00:29  fplanque
 * one too many massive syncs...
 *
 * Revision 1.12  2006/04/19 22:26:24  blueyed
 * cleanup/polish
 *
 * Revision 1.11  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.10  2006/04/14 19:25:31  fplanque
 * evocore merge with work app
 *
 * Revision 1.9  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>
