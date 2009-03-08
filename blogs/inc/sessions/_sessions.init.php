<?php
/**
 * This is the init file for the session module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
$db_config['aliases']['T_useragents'] = $tableprefix.'useragents';


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


/**
 * sessions_Module definition
 */
class sessions_Module
{
	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( $current_User->check_perm( 'stats', 'list' ) )
		{	// Permission to view stats for user's blogs:
			if( $current_User->check_perm( 'stats', 'view' ) )
			{	// We have permission to view all stats,
				// we'll assume that we want to view th aggregate stats and not the current blog stats
				// fp> TODO: it might be useful to have a user pref for [View aggregate stats by default] vs [View current blog stats by default]
				$default = 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0';
			}
			else
			{
				$default = 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=global';
			}
			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'stats' => array(
							'text' => T_('Stats'),
							'href' => $default,
							'entries' => array(
								'summary' => array(
									'text' => T_('Hit summary'),
									'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog,
									'entries' => array(
										'global' => array(
											'text' => T_('Global hits'),
											'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog ),
										'browser' => array(
											'text' => T_('Browser hits'),
											'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=browser&amp;blog='.$blog ),
										'robot' => array(
											'text' => T_('Robot hits'),
											'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=robot&amp;blog='.$blog ),
										'feed' => array(
											'text' => T_('RSS/Atom hits'),
											'href' => 'admin.php?ctrl=stats&amp;tab=summary&amp;tab3=feed&amp;blog='.$blog ),
										),
									),
								'refsearches' => array(
									'text' => T_('Search B-hits'),
									'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog,
									'entries' => array(
										'hits' => array(
											'text' => T_('Search hits'),
											'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog ),
										'keywords' => array(
											'text' => T_('Keywords'),
											'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;tab3=keywords&amp;blog='.$blog ),
										'topengines' => array(
											'text' => T_('Top engines'),
											'href' => 'admin.php?ctrl=stats&amp;tab=refsearches&amp;tab3=topengines&amp;blog='.$blog ),
										),
									 ),
								'referers' => array(
									'text' => T_('Referered B-hits'),
									'href' => 'admin.php?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
								'other' => array(
									'text' => T_('Direct B-hits'),
									'href' => 'admin.php?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
								'useragents' => array(
									'text' => T_('User agents'),
									'href' => 'admin.php?ctrl=stats&amp;tab=useragents&amp;blog='.$blog ),
								'domains' => array(
									'text' => T_('Referring domains'),
									'href' => 'admin.php?ctrl=stats&amp;tab=domains&amp;blog='.$blog ),
							)
						),
					)
				);
		}

		if( $blog == 0 && $current_User->check_perm( 'stats', 'view' ) )
		{	// Viewing aggregate + Permission to view stats for ALL blogs:
			$AdminUI->add_menu_entries(
					'stats',
					array(
						'sessions' => array(
							'text' => T_('User sessions'),
							'href' => 'admin.php?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0',
							'entries' => array(
								'login' => array(
									'text' => T_('Users'),
									'href' => 'admin.php?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0'
									),
								'sessid' => array(
									'text' => T_('Sessions'),
									'href' => 'admin.php?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;blog=0'
									),
								'hits' => array(
									'text' => T_('Hits'),
									'href' => 'admin.php?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0'
									),
								),
						 	),
						'goals' => array(
							'text' => T_('Goals'),
							'href' => 'admin.php?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0',
							'entries' => array(
								'hits' => array(
									'text' => T_('Goal hits'),
									'href' => 'admin.php?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0'
									),
								'goals' => array(
									'text' => T_('Goals'),
									'href' => 'admin.php?ctrl=goals'
									),
								'stats' => array(
									'text' => T_('Stats'),
									'href' => 'admin.php?ctrl=goals&amp;tab3=stats'
									),
								),
							),
						)
				);
		}
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
	}
}

$sessions_Module = & new sessions_Module();


/*
 * $Log$
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
