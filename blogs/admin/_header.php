<?php
/**
 * This file initializes the admin/backoffice!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
$login_required = true;
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_main.inc.php';


// Check global permission:
if( ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require dirname(__FILE__).'/_access_denied.inc.php';
}


// Get the blog from param, defaulting to the last selected one for this user:
$user_selected_blog = (int)$UserSettings->get('selected_blog'); // QUESTION: we might want to exclude $pagenow=stats.php here..?!
param( 'blog', 'integer', $user_selected_blog, true ); // We may need this for the urls
if( $blog != $user_selected_blog )
{ // Update UserSettings for selected blog:
	$UserSettings->set( 'selected_blog', $blog );
	$UserSettings->dbupdate();
}

param( 'mode', 'string', '', true );  // Sidebar, bookmarklet, upload (upload actually means th like: select img for post)

// Get the Admin skin
// TODO: Allow setting through GET param (dropdown in backoffice), respecting a checkbox "Use different setting on each computer" (if cookie_state handling is ready)
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

// Construct the menu:
// It is useful to have the whole structure in case we want to display DHTML drop down menus for example
// Thus we should make this menu as complete as possible and change it as little as possible with dynamic functions later.
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'new' => array(
				'text' => T_('Write'),
				'href' => 'b2edit.php?blog='.$blog,
				'style' => 'font-weight: bold;'
			),

			'edit' => array(
				'text'=>T_('Browse'),
				'href'=>'b2browse.php?blog='.$blog.'&amp;filter=restore',
				'style'=>'font-weight: bold;',
				'entries' => array(
						'postlist' => array(
							'text' => T_('Post list'),
							'href' => regenerate_url( 'tab', 'tab=postlist' ),
							),
						'posts' => array(
							'text' => T_('Full posts'),
							'href' => regenerate_url( 'tab', 'tab=posts' ),
							),
						// EXPERIMENTAL:
						'exp' => array(
							'text' => T_('Experimental'),
							'href' => regenerate_url( 'tab', 'tab=exp&amp;filter=restore' ),
							),
						'tracker' => array(
							'text' => T_('Tracker'),
							'href' => regenerate_url( 'tab', 'tab=tracker&amp;filter=restore' ),
							),
					/*	'commentlist' => array(
							'text' => T_('Comment list'),
							'href' => 'b2browse.php?tab=commentlist ), */
						'comments' => array(
							'text' => T_('Comments'),
							'href' => regenerate_url( 'tab', 'tab=comments' ),
							),
					)
			),

			'cats' => array(
				'text'=>T_('Categories'),
				'href'=>'categories.php?blog='.$blog
			),

			'blogs' => array(
				'text'=>T_('Blogs'),
				'href'=>'blogs.php',
				'entries' => array(
					'general' => array(
						'text' => T_('General'),
						'href' => 'blogs.php?tab=general&amp;action=edit&amp;blog='.$blog,
						'perm_eval' => 'return $GLOBALS["blog"];' ), // hack!?
					'perm' => array(
						'text' => T_('User Permissions'),
						'href' => 'blogs.php?tab=perm&amp;action=edit&amp;blog='.$blog,
						'perm_eval' => 'return $GLOBALS["blog"];' ), // hack!?
					'permgroup' => array(
						'text' => T_('Group Permissions'),
						'href' => 'blogs.php?tab=permgroup&amp;action=edit&amp;blog='.$blog,
						'perm_eval' => 'return $GLOBALS["blog"];' ), // hack!?
					'advanced' => array(
						'text' => T_('Advanced'),
						'href' => 'blogs.php?tab=advanced&amp;action=edit&amp;blog='.$blog,
						'perm_eval' => 'return $GLOBALS["blog"];' ), // hack!?
				)
			),

			'stats' => array(
				'text'=>T_('Stats'),
				'perm_name'=>'stats',
				'perm_level'=>'view',
				'href'=>'stats.php',
				'entries' => array(
					'summary' => array(
						'text' => T_('Summary'),
						'href' => 'stats.php?tab=summary&amp;blog='.$blog ),
					'other' => array(
						'text' => T_('Direct Accesses'),
						'href' => 'stats.php?tab=other&amp;blog='.$blog ),
					'referers' => array(
						'text' => T_('Referers'),
						'href' => 'stats.php?tab=referers&amp;blog='.$blog ),
					'refsearches' => array(
						'text' => T_('Refering Searches'),
						'href' => 'stats.php?tab=refsearches&amp;blog='.$blog ),
					'syndication' => array(
						'text' => T_('Syndication'),
						'href' => 'stats.php?tab=syndication&amp;blog='.$blog ),
					'useragents' => array(
						'text' => T_('User Agents'),
						'href' => 'stats.php?tab=useragents&amp;blog='.$blog ),
				)
			),

			'antispam' => array(
				'text'=>T_('Antispam'),
				'perm_name'=>'spamblacklist',
				'perm_level'=>'view',
				'href'=>'antispam.php'
			),

			'templates' => array(
				'text'=>T_('Templates'),
				'title' => T_('Custom skin template editing'),
				'perm_name'=>'templates',
				'perm_level'=>'any',
				'href'=>'b2template.php'
			),

			'files' => array(
				'text' => T_('Files'),
				'title' => T_('File Management'),
				'href'=>'files.php',
				'perm_eval' => 'global $Settings; return $Settings->get( \'fm_enabled\' ) && $current_User->check_perm( \'files\', \'view\' );'
			),

			'users' => array(
				'text'=>T_('Users & Groups'),
				'title'=>T_('User management'),
				'perm_name'=>'users',
				'perm_level'=>'view',
				'text_noperm'=>T_('User Profile'),	// displayed if perm not granted
				'href'=>'b2users.php'
			),

			'options' => array(
				'text' => T_('Settings'),
				'perm_name' => 'options',
				'perm_level' => 'view',
				'href' => 'settings.php',
				'entries' => array(
					'general' => array(
						'text' => T_('General'),
						'href' => 'settings.php' ),
					'features' => array(
						'text' => T_('Features'),
						'href' => 'features.php' ),
					'regional' => array(
						'text' => T_('Regional'),
						'href' => 'locales.php'.( (isset($loc_transinfo) && $loc_transinfo) ? '?loc_transinfo=1' : '' ) ),
					'files' => array(
						'text' => T_('Files'),
						'href' => 'fileset.php' ),
					'filetypes' => array(
						'text' => T_('File types'),
						'href' => 'filetypes.php' ),
					'statuses' => array(
						'text' => T_('Post statuses'),
						'title' => T_('Post statuses management'),
						'href' => 'statuses.php'),
					'types' => array(
						'text' => T_('Post types'),
						'title' => T_('Post types management'),
						'href' => 'types.php'),
					'plugins' => array(
						'text' => T_('Plug-ins'),
						'href' => 'plugins.php'),
				)
			),

			'tools' => array(
				'text'=>T_('Tools'),
				'href'=>'tools.php'
			),
		)
	);


$Plugins->trigger_event( 'AdminAfterMenuInit' );

?>