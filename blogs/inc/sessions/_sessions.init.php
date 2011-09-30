<?php
/**
 * This is the init file for the session module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_basedomains'] = $tableprefix.'basedomains';
$db_config['aliases']['T_hitlog'] = $tableprefix.'hitlog';
$db_config['aliases']['T_track__keyphrase'] = $tableprefix.'track__keyphrase';
$db_config['aliases']['T_sessions'] = $tableprefix.'sessions';
$db_config['aliases']['T_track__goal'] = $tableprefix.'track__goal';
$db_config['aliases']['T_track__goalhit'] = $tableprefix.'track__goalhit';
$db_config['aliases']['T_logs__internal_searches'] = $tableprefix.'logs__internal_searches';


/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings['stats'] = 'sessions/stats.ctrl.php';
$ctrl_mappings['goals'] = 'sessions/goals.ctrl.php';
$ctrl_mappings['internalsearches'] = 'sessions/internal_searches.ctrl.php';


/**
 * Get the GoalCache
 *
 * @return GoalCache
 */
function & get_GoalCache()
{
	global $GoalCache;

	if( ! isset( $GoalCache ) )
	{	// Cache doesn't exist yet:
		$GoalCache = new DataObjectCache( 'Goal', false, 'T_track__goal', 'goal_', 'goal_ID', 'goal_name', 'goal_name' ); // COPY (FUNC)
	}

	return $GoalCache;
}

/**
 * Get the Internal Searches Cache
 *
 * @return InternalSearchesCache
 */
function & get_InternalSearchesCache()
{
	global $InternalSearchesCache;

	if( ! isset( $InternalSearchesCache ) )
	{	// Cache doesn't exist yet:
		$InternalSearchesCache = new DataObjectCache( 'InternalSearches', false, 'T_logs__internal_searches', 'isrch_', 'isrch_ID', 'isrch_sessionID', 'isrch_keywords' ); // COPY (FUNC)
	}

	return $InternalSearchesCache;
}


/**
 * sessions_Module definition
 */
class sessions_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
	}

	/**
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		/**
		 * @var Menu
		 */
		global $topleft_Menu;
		global $current_User;
		global $admin_url;
		global $Blog;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( !empty($Blog) && $current_User->check_perm( 'stats', 'list' ) )
		{	// Permission to view stats for user's blogs:
			$entries = array();
			$entries['stats_sep'] = array(
				'separator' => true,
			);
			$entries['stats'] = array(
				'text' => T_('Blog stats').'&hellip;',
				'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$Blog->ID,
			);

			$topleft_Menu->add_menu_entries( 'blog', $entries );
		}

		if( $current_User->check_perm( 'stats', 'view' ) )
		{	// We have permission to view all stats

			// TODO: this is hackish and would require a proper function call
			$topleft_Menu->_menus['entries']['tools']['disabled'] = false;

			// TODO: this is hackish and would require a proper function call
			if( ! empty($topleft_Menu->_menus['entries']['tools']['entries']) )
			{	// There are already entries aboce, insert a separator:
				$topleft_Menu->add_menu_entries( 'tools', array(
						'stats_sep' => array(
								'separator' => true,
							),
					)
				);
			}

			$entries = array(
				'stats' => array(
						'text' => T_('Global Stats').'&hellip;',
						'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0',
					 ),
				'sessions' => array(
						'text' => T_('User sessions').'&hellip;',
						'href' => $admin_url.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0',
					 ),
				'goals' => array(
						'text' => T_('Goals').'&hellip;',
						'href' => $admin_url.'?ctrl=goals',
					 ),
				);

			$topleft_Menu->add_menu_entries( 'tools', $entries );
		}
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'stats', 'list' ) )
		{	// Permission to view stats for user's blogs:
			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'stats' => array(
							'text' => T_('Stats'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global',
							'entries' => array(
								'summary' => array(
									'text' => T_('Hit summary'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog,
									'entries' => array(
										'global' => array(
											'text' => T_('Global hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog ),
										'browser' => array(
											'text' => T_('Browser hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=browser&amp;blog='.$blog ),
										'robot' => array(
											'text' => T_('Robot hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=robot&amp;blog='.$blog ),
										'feed' => array(
											'text' => T_('RSS/Atom hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=feed&amp;blog='.$blog ),
										),
									),
								),
							),
						)
					);

			$AdminUI->add_menu_entries( 'stats', array(
								'refsearches' => array(
									'text' => T_('Search B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog,
									'entries' => array(
										'hits' => array(
											'text' => T_('Search hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog ),
										'keywords' => array(
											'text' => T_('Keywords'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=keywords&amp;blog='.$blog ),
										'topengines' => array(
											'text' => T_('Top engines'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=topengines&amp;blog='.$blog ),
										'intsearches' => array(
											'text' => T_('Internal searches'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=intsearches&amp;blog='.$blog ),
										),
									 ),
								'referers' => array(
									'text' => T_('Referered B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
								'other' => array(
									'text' => T_('Direct B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
								'hits' => array(
									'text' => T_('All Hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=hits&amp;blog='.$blog ),
								'domains' => array(
									'text' => T_('Referring domains'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=domains&amp;blog='.$blog ),
							)
						);
		}

		if( $blog == 0 && $current_User->check_perm( 'stats', 'view' ) )
		{	// Viewing aggregate + Permission to view stats for ALL blogs:
			$AdminUI->add_menu_entries(
					'stats',
					array(
						'goals' => array(
							'text' => T_('Goals'),
							'href' => $dispatcher.'?ctrl=goals',
							'entries' => array(
								'goals' => array(
									'text' => T_('Goals'),
									'href' => $dispatcher.'?ctrl=goals'
									),
								'hits' => array(
									'text' => T_('Goal hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0'
									),
								'stats' => array(
									'text' => T_('Stats'),
									'href' => $dispatcher.'?ctrl=goals&amp;tab3=stats'
									),
								),
							),
						)
				);
		}
	}

	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{

		global $blog, $dispatcher, $ctrl;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'stats', 'view' ) )
		{	// Viewing aggregate + Permission to view stats for ALL blogs:
			$sessions_menu = array(
				'sessions' => array(
					'text' => T_('User sessions'),
					'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0',
					'entries' => array(
						'login' => array(
							'text' => T_('Users'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0'
							),
						'sessid' => array(
							'text' => T_('Sessions'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;blog=0'
							)
						),
					),
			 	);
			// insert at 2nd position:
			if( ! $AdminUI->insert_menu_entries_after( 'users', $sessions_menu, 0 )	)
			{
				$AdminUI->add_menu_entries( 'user', $sessions_menu );
			}
		}
	}

	/**
	 * Check global permissions for this module (not for specific object - those should be handled in the appropriate DataObject class)
	 *
	 * @todo fp> break up central User::check_perm() so that add-on modules do not need to add code into User class.
	 *
	 * @param mixed $action
	 * @param mixed $assert
	 */
	function check_perm( $action = 'view', $assert = true )
	{
		global $current_User;

		return $current_User->check_perm( 'stats', $action, $assert );
	}


}

$sessions_Module = new sessions_Module();


/*
 * $Log$
 * Revision 1.36  2011/09/30 06:25:24  efy-vitalij
 * Remove Hits tab from User Sessions
 *
 * Revision 1.35  2011/09/29 09:16:16  efy-vitalij
 * add menu item All Hits
 *
 * Revision 1.34  2011/09/15 08:58:46  efy-asimo
 * Change user tabs display
 *
 * Revision 1.33  2011/09/13 15:31:35  fplanque
 * Enhanced back-office navigation.
 *
 * Revision 1.32  2011/09/07 12:00:18  lxndral
 * internal searches update
 *
 * Revision 1.31  2011/09/04 22:13:18  fplanque
 * copyright 2011
 *
 * Revision 1.30  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.29  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.28  2010/01/30 18:55:33  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.27  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.26  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.25  2009/10/26 12:59:36  efy-maxim
 * users management
 *
 * Revision 1.24  2009/10/25 15:22:44  efy-maxim
 * user - identity, password, preferences tabs
 *
 * Revision 1.23  2009/09/25 20:45:41  fplanque
 * fix
 *
 * Revision 1.22  2009/09/21 03:14:35  fplanque
 * modularized a little more
 *
 * Revision 1.21  2009/09/20 18:13:20  fplanque
 * doc
 *
 * Revision 1.20  2009/09/20 12:00:31  efy-sergey
 * Moved Stats>User Sessions tab 2nd position
 *
 * Revision 1.19  2009/09/20 00:27:08  fplanque
 * cleanup/doc/simplified
 *
 * Revision 1.18  2009/09/19 21:49:03  efy-sergey
 * Moved Stats>User Sessions tab to Users>Sessions
 *
 * Revision 1.17  2009/09/19 20:49:51  fplanque
 * Cleaner way of implementing permissions.
 *
 * Revision 1.16  2009/09/16 00:48:50  fplanque
 * getting a bit more serious with modules
 *
 * Revision 1.15  2009/09/15 19:31:54  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.14  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.13  2009/07/06 23:52:25  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.12  2009/05/16 00:29:41  fplanque
 * better menu structure (load the fastest page by default)
 *
 * Revision 1.11  2009/04/15 13:17:20  tblue246
 * bugfixes
 *
 * Revision 1.10  2009/03/23 22:19:45  fplanque
 * evobar right menu is now also customizable by plugins
 *
 * Revision 1.9  2009/03/23 04:09:43  fplanque
 * Best. Evobar. Menu. Ever.
 * menu is now extensible by plugins
 *
 * Revision 1.8  2009/03/22 23:39:33  fplanque
 * new evobar Menu structure
 * Superfish jQuery menu library
 * + removed obsolete JS includes
 *
 * Revision 1.7  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.6  2009/03/07 21:35:09  blueyed
 * doc
 *
 * Revision 1.5  2008/05/26 19:30:32  fplanque
 * enhanced analytics
 *
 * Revision 1.4  2008/05/10 22:59:09  fplanque
 * keyphrase logging
 *
 * Revision 1.3  2008/04/24 01:56:08  fplanque
 * Goal hit summary
 *
 * Revision 1.2  2008/04/17 11:53:18  fplanque
 * Goal editing
 *
 * Revision 1.1  2008/04/06 19:19:30  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 */
?>
