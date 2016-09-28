<?php
/**
 * This is the init file for the session module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Minimum PHP version required for sessions module to function properly
 */
$required_php_version[ 'sessions' ] = '5.2';

/**
 * Minimum MYSQL version required for sessions module to function properly
 */
$required_mysql_version[ 'sessions' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_basedomains'] = $tableprefix.'basedomains';
$db_config['aliases']['T_hitlog'] = $tableprefix.'hitlog';
$db_config['aliases']['T_sessions'] = $tableprefix.'sessions';
$db_config['aliases']['T_track__goal'] = $tableprefix.'track__goal';
$db_config['aliases']['T_track__goalhit'] = $tableprefix.'track__goalhit';
$db_config['aliases']['T_track__goalcat'] = $tableprefix.'track__goalcat';
$db_config['aliases']['T_track__keyphrase'] = $tableprefix.'track__keyphrase';


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
 * Get the GoalCache
 *
 * @return GoalCache
 */
function & get_GoalCache()
{
	global $GoalCache;

	if( ! isset( $GoalCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'sessions/model/_goal.class.php', 'Goal' );
		$GoalCache = new DataObjectCache( 'Goal', false, 'T_track__goal', 'goal_', 'goal_ID', 'goal_key', 'goal_name' ); // COPY (FUNC)
	}

	return $GoalCache;
}

/**
 * Get the GoalCategoryCache
 *
 * @param string The text that gets used for the "None" option in the objects options list (Default: T_('None')).
 * @return GoalCategoryCache
 */
function & get_GoalCategoryCache( $allow_none_text = NULL )
{
	global $GoalCategoryCache;

	if( ! isset( $GoalCategoryCache ) )
	{ // Cache doesn't exist yet:
		if( ! isset( $allow_none_text ) )
		{
			$allow_none_text = NT_('None');
		}
		load_class( 'sessions/model/_goalcat.class.php', 'GoalCategory' );
		$GoalCategoryCache = new DataObjectCache( 'GoalCategory', false, 'T_track__goalcat', 'gcat_', 'gcat_ID', 'gcat_name', 'gcat_name', $allow_none_text ); // COPY (FUNC)
	}

	return $GoalCategoryCache;
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
		$this->check_required_php_version( 'sessions' );
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
		global $Blog, $activate_collection_toolbar;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( ( ! is_admin_page() || ! empty( $activate_collection_toolbar ) ) && ! empty( $Blog ) && $current_User->check_perm( 'stats', 'list', false, $Blog->ID ) )
		{ // Permission to view stats for user's blogs:
			$entries = array(
				'stats_separator' => array( 'separator' => true ),
				'stats' => array(
					'text' => T_('Collection Analytics'),
					'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$Blog->ID,
					'entries' => array(
						'summary' => array(
							'text' => T_('Hit summary').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$Blog->ID ),
						'refsearches' => array(
							'text' => T_('Search B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$Blog->ID ),
						'referers' => array(
							'text' => T_('Referered B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=referers&amp;blog='.$Blog->ID ),
						'other' => array(
							'text' => T_('Direct B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=other&amp;blog='.$Blog->ID ),
						'hits' => array(
							'text' => T_('All Hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog='.$Blog->ID ),
						'domains' => array(
							'text' => T_('Referring domains').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=domains&amp;blog='.$Blog->ID ),
						)
				) );

			$topleft_Menu->add_menu_entries( 'blog', $entries );
		}

		if( $current_User->check_perm( 'stats', 'view' ) )
		{	// We have permission to view all stats

			// TODO: this is hackish and would require a proper function call
			$topleft_Menu->_menus['entries']['tools']['disabled'] = false;

			$entries = array(
				'stats_separator' => array( 'separator' => true ),
				'stats' => array(
					'text' => T_('Global Analytics'),
					'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0',
					'entries' => array(
						'summary' => array(
							'text' => T_('Hit summary').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0' ),
						'refsearches' => array(
							'text' => T_('Search B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog=0' ),
						'referers' => array(
							'text' => T_('Referered B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=referers&amp;blog=0' ),
						'other' => array(
							'text' => T_('Direct B-hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=other&amp;blog=0' ),
						'hits' => array(
							'text' => T_('All Hits').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog=0' ),
						array( 'separator' => true ),
						'ips' => array(
							'text' => T_('IPs').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=ips&amp;blog=0' ),
						'domains' => array(
							'text' => T_('Referring domains').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=domains&amp;blog=0' ),
						'goals' => array(
							'text' => T_('Goals').'&hellip;',
							'href' => $admin_url.'?ctrl=goals&amp;blog=0' ),
						'settings' => array(
							'text' => T_('Settings').'&hellip;',
							'href' => $admin_url.'?ctrl=stats&amp;tab=settings&amp;blog=0' ),
						)
				) );

			if( !is_admin_page() )
			{
				$blog_ID = empty( $Blog ) ? 0 : $Blog->ID;
				$entries['stats_page'] = array(
						'text' => T_('Page stats').'&hellip;',
						'href' => $admin_url.'?ctrl=stats&tab=hits&blog='.$blog_ID.'&reqURI='.rawurlencode( $_SERVER['REQUEST_URI'] ),
					);
			}

			$topleft_Menu->add_menu_entries( 'tools', $entries );
		}
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog, $admin_url;
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
							'text' => T_('Analytics'),
							'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global',
							'entries' => array(
								'summary' => array(
									'text' => T_('Hit summary'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog,
									'order' => 'group_last',
									'entries' => array(
										'global' => array(
											'text' => T_('Global hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog ),
										'browser' => array(
											'text' => T_('Browser hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=browser&amp;blog='.$blog ),
										'api' => array(
											'text' => T_('API hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=api&amp;blog='.$blog ),
										'robot' => array(
											'text' => T_('Robot hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=robot&amp;blog='.$blog ),
										'feed' => array(
											'text' => T_('RSS/Atom hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=feed&amp;blog='.$blog ),
										),
									),
								),
							),
						)
					);

			$ips_entries = array( 'top' => array(
					'text' => T_('Top IPs'),
					'href' => $admin_url.'?ctrl=stats&amp;tab=ips&amp;blog='.$blog
				) );
			if( $current_User->check_perm( 'spamblacklist', 'view' ) )
			{ // Display IP ranges only if current user has access to view Antispam tools
				$ips_entries['ranges'] = array(
					'text' => T_('IP Ranges'),
					'href' => $admin_url.'?ctrl=antispam&amp;tab=stats&amp;tab3=ipranges&amp;blog='.$blog
				);
			}

			$AdminUI->add_menu_entries( 'stats', array(
								'refsearches' => array(
									'text' => T_('Search B-hits'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog,
									'entries' => array(
										'hits' => array(
											'text' => T_('Search hits'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog ),
										'keywords' => array(
											'text' => T_('Keywords'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=keywords&amp;blog='.$blog ),
										'topengines' => array(
											'text' => T_('Top engines'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=refsearches&amp;tab3=topengines&amp;blog='.$blog ),
										),
									),
								'referers' => array(
									'text' => T_('Referered B-hits'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
								'other' => array(
									'text' => T_('Direct B-hits'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
								'hits' => array(
									'text' => T_('All Hits'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog='.$blog ),
								'domains' => array(
									'text' => T_('Referring domains'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=domains&amp;blog='.$blog,
									'order' => 'group_last',
									'entries' => array(
										'all' => array(
											'text' => T_('All referrers'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=domains&amp;blog='.$blog ),
										'top' => array(
											'text' => T_('Top referrers'),
											'href' => $admin_url.'?ctrl=stats&amp;tab=domains&amp;tab3=top&amp;blog='.$blog ),
										),
									),
								'ips' => array(
									'text' => T_('IPs'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=ips&amp;blog='.$blog,
									'entries' => $ips_entries ),
							)
						);

			if( $current_User->check_perm( 'stats', 'view' ) ||
			    autoselect_blog( 'stats', 'view' ) )
			{ // Viewing aggregate + Permission to view stats for ALL blogs:
				$AdminUI->add_menu_entries(
					'stats',
					array(
						'goals' => array(
							'text' => T_('Goals'),
							'href' => $admin_url.'?ctrl=goals&amp;blog='.$blog,
							'entries' => array(
								'goals' => array(
									'text' => T_('Goals'),
									'href' => $admin_url.'?ctrl=goals&amp;blog='.$blog
									),
								'cats' => array(
									'text' => T_('Categories'),
									'href' => $admin_url.'?ctrl=goals&amp;tab3=cats&amp;blog='.$blog
									),
								'hits' => array(
									'text' => T_('Goal hits'),
									'href' => $admin_url.'?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog='.$blog
									),
								'stats' => array(
									'text' => T_('Stats'),
									'href' => $admin_url.'?ctrl=goals&amp;tab3=stats&amp;blog='.$blog
									),
								),
							),
						'settings' => array(
							'text' => T_('Settings'),
							'href' => $admin_url.'?ctrl=stats&amp;tab=settings&amp;blog='.$blog ),
						)
				);
			}
		}
	}


	/**
	 * Get the sessions module cron jobs
	 *
	 * @see Module::get_cron_jobs()
	 */
	function get_cron_jobs()
	{
		return array(
			'process-hit-log' => array(
				'name'   => T_('Extract info from hit log'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_process_hitlog.job.php',
				'params' => NULL,
			),
			'prune-old-hits-and-sessions' => array(
				'name'   => T_('Prune old hits & sessions (includes OPTIMIZE)'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_prune_hits_sessions.job.php',
				'params' => NULL,
			),
		);
	}
}

$sessions_Module = new sessions_Module();

?>
