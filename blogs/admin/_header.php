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

param( 'blog', 'integer', 0, true ); // We may need this for the urls
param( 'mode', 'string', '' );  // Sidebar, bookmarklet


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
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'new' => array(
				'text'=>T_('Write'),
				'href' => 'b2edit.php?blog='.$blog,
				'style' => 'font-weight: bold;'
			),

			'edit' => array(
				'text'=>T_('Browse'),
				'href'=>'b2browse.php?blog='.$blog,
				'style'=>'font-weight: bold;',
				'entries' => array(
					// Entries will be added here dynamically (and CONTEXTTUALLY) on the browsing page.
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

			// TODO: check filemanager permission
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
						'href' =>'fileset.php' ),
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

?>