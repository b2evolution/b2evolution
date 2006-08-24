<?php
/**
 * This file initializes the admin/backoffice!
 *
 * Note: This file will be merged into admin.php
 *
 * @package admin
 */


// Get the blog from param, defaulting to the last selected one for this user:
$user_selected_blog = (int)$UserSettings->get('selected_blog');
param( 'blog', 'integer', $user_selected_blog, true ); // We may need this for the urls
if( $blog != $user_selected_blog )
{ // Update UserSettings for selected blog:
	$UserSettings->set( 'selected_blog', $blog );
	$UserSettings->dbupdate();
}

param( 'mode', 'string', '', true );  // Sidebar, bookmarklet, upload (upload actually means sth like: select img for post)

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
// TODO: 'href' should be splitted into 'ctrl' and 'params'/'params_eval'
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'new' => array(
				'text' => T_('Write'),
				'href' => 'admin.php?ctrl=edit&amp;blog='.$blog,
				'style' => 'font-weight: bold;',
				'entries' => array(
						'simple' => array(
							'text' => T_('Simple'),
							'href' => 'admin.php?ctrl=edit&amp;tab=simple&amp;blog='.$blog,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=simple&amp;blog='.$blog.'\' );',
							'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
							),
						'expert' => array(
							'text' => T_('Expert'),
							'href' => 'admin.php?ctrl=edit&amp;tab=expert&amp;blog='.$blog,
							'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=expert&amp;blog='.$blog.'\' );',
							'name' => 'switch_to_expert_tab_nocheckchanges', // no bozo check
							),
					)
			),

			'edit' => array(
				'text' => T_('Posts'),
				'href' => 'admin.php?ctrl=browse&amp;blog='.$blog.'&amp;filter=restore',
				'style' => 'font-weight: bold;',
				'entries' => array(
					// NOTE: the following entries are defaults in case of the DHTML drop down menu,
					// they will be overridden in the browse controller
						/* Deprecated:
						'postlist' => array(
							'text' => T_('Post list (Old)'),
							'href' => 'admin.php?ctrl=browse&amp;tab=postlist&amp;blog='.$blog,
							),
						*/
						'postlist2' => array(
							'text' => T_('Post list'),
							'href' => 'admin.php?ctrl=browse&amp;tab=postlist2&amp;blog='.$blog,
							),
						'tracker' => array(
							'text' => T_('Tracker'),
							'href' => 'admin.php?ctrl=browse&amp;tab=tracker&amp;blog='.$blog,
							),
						'posts' => array(
							'text' => T_('Full posts'),
							'href' => 'admin.php?ctrl=browse&amp;tab=posts&amp;blog='.$blog,
							),
					/*	'commentlist' => array(
							'text' => T_('Comment list'),
							'href' => 'admin.php?ctrl=browse&amp;tab=commentlist&amp;blog='.$blog,
							*/
						'comments' => array(
							'text' => T_('Comments'),
							'href' => 'admin.php?ctrl=browse&amp;tab=comments&amp;blog='.$blog,
							),
					)
			),

			'cats' => array(	// TODO: move this to 'blog settings>chapters'
				'text' => T_('Categories'),
				'href' => 'admin.php?ctrl=chapters&amp;blog='.$blog
			),

			'blogs' => array(
				'text' => T_('Blog settings'),
				'href' => 'admin.php?ctrl=collections',
				'entries' => array(
					'general' => array(
						'text' => T_('General'),
						'href' => 'admin.php?ctrl=collections&amp;tab=general&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
					'skin' => array(
						'text' => T_('Skin'),
						'href' => 'admin.php?ctrl=collections&amp;tab=skin&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
					'display' => array(
						'text' => T_('Display'),
						'href' => 'admin.php?ctrl=collections&amp;tab=display&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
					'advanced' => array(
						'text' => T_('Advanced'),
						'href' => 'admin.php?ctrl=collections&amp;tab=advanced&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
					'perm' => array(
						'text' => T_('User permissions'),
						'href' => 'admin.php?ctrl=collections&amp;tab=perm&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
					'permgroup' => array(
						'text' => T_('Group permissions'),
						'href' => 'admin.php?ctrl=collections&amp;tab=permgroup&amp;blog='.$blog,
						'perm_eval' => 'global $action; return $action != "list";' ),
				)
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
					'other' => array(
						'text' => T_('Direct B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
					'referers' => array(
						'text' => T_('Referered B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
					'refsearches' => array(
						'text' => T_('Search B-hits'),
						'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;blog='.$blog ),
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

			'antispam' => array( // TODO: move this as the default submenu of Tools?
				'text' => T_('Antispam'),
				'perm_name' => 'spamblacklist',
				'perm_level' => 'view',
				'href' => 'admin.php?ctrl=antispam'
			),

			'templates' => array(
				'text' => T_('Templates'),
				'title' => T_('Custom skin template editing'),
				'perm_name' => 'templates',
				'perm_level' => 'any',
				'href'=>'admin.php?ctrl=templates'
			),

			'files' => array(
				'text' => T_('Files'),
				'title' => T_('File management'),
				'href' => 'admin.php?ctrl=files',
				'perm_eval' => 'global $Settings; return $Settings->get( \'fm_enabled\' ) && $current_User->check_perm( \'files\', \'view\' );'
			),

			'users' => array(
				'text' => T_('Users'),
				'title' => T_('User management'),
				'perm_name' => 'users',
				'perm_level' => 'view',
				'text_noperm' => T_('My profile'),	// displayed if perm not granted
				'href' => 'admin.php?ctrl=users',
			),

			'options' => array(
				'text' => T_('App settings'),
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
					'regional' => array(
						'text' => T_('Regional'),
						'href' => 'admin.php?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
					'files' => array(
						'text' => T_('Files'),
						'href' => 'admin.php?ctrl=fileset' ),
					'filetypes' => array(
						'text' => T_('File types'),
						'href' => 'admin.php?ctrl=filetypes' ),
					'statuses' => array(
						'text' => T_('Post statuses'),
						'title' => T_('Post statuses management'),
						'href' => 'admin.php?ctrl=itemstatuses'),
					'types' => array(
						'text' => T_('Post types'),
						'title' => T_('Post types management'),
						'href' => 'admin.php?ctrl=itemtypes'),
					'plugins' => array(
						'text' => T_('Plugins'),
						'href' => 'admin.php?ctrl=plugins'),
					'antispam' => array(
						'text' => T_('Antispam'),
						'href' => 'admin.php?ctrl=set_antispam'),
				)
			),

			'tools' => array(
				'text' => T_('Tools'),
				'href' => 'admin.php?ctrl=tools'
			),
		)
	);


// CRON:
$AdminUI->add_menu_entries(
		NULL, // root
		array(
			'cron' => array(
				'text' => T_('Scheduler'),
				'perm_name' => 'options',
				'perm_level' => 'view',
				'href' => 'admin.php?ctrl=crontab',
			),
		)
);


$Plugins->trigger_event( 'AdminAfterMenuInit' );


/*
 * $Log$
 * Revision 1.15  2006/08/24 21:41:13  fplanque
 * enhanced stats
 *
 * Revision 1.14  2006/08/18 20:36:44  fplanque
 * no message
 *
 * Revision 1.13  2006/08/18 17:23:58  fplanque
 * Visual skin selector
 *
 * Revision 1.12  2006/07/12 20:18:19  fplanque
 * session stats + minor enhancements
 *
 * Revision 1.11  2006/07/08 22:48:23  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.10  2006/07/07 23:14:57  fplanque
 * we desperately need a simplified edit screen!!
 *
 * Revision 1.9  2006/06/26 23:10:24  fplanque
 * minor / doc
 *
 * Revision 1.8  2006/06/23 19:41:20  fplanque
 * no message
 *
 * Revision 1.7  2006/06/13 21:49:14  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.5.2.3  2006/06/13 18:27:50  fplanque
 * fixes
 *
 * Revision 1.6  2006/05/19 18:15:04  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.5.2.1  2006/05/19 15:06:22  fplanque
 * dirty sync
 *
 * Revision 1.5  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>