<?php
/**
 * This file implements the UI view for the Hit list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


$exclude = param( 'exclude', 'integer', 0, true );
$sess_ID = param( 'sess_ID', 'integer', NULL, true );
$goal_name = param( 'goal_name', 'string', NULL, true );

// Create result set:
$sql = 'SELECT hit_ID, sess_ID, sess_hitcount, hit_datetime, hit_referer_type, hit_uri, hit_blog_ID, hit_referer, hit_remote_addr,
								user_login, agnt_type, dom_name, goal_name
					FROM T_track__goalhit LEFT JOIN T_hitlog ON ghit_hit_ID = hit_ID
								LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
								LEFT JOIN T_sessions ON hit_sess_ID = sess_ID
								LEFT JOIN T_useragents ON hit_agnt_ID = agnt_ID
								LEFT JOIN T_users ON sess_user_ID = user_ID
								LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID';
$count_sql = 'SELECT COUNT(ghit_ID)
								FROM T_track__goalhit';

if( !empty($sess_ID) )
{	// We want to filter on the session ID:
	$operator = ($exclude ? ' <> ' : ' = ' );
	$sql .= ' WHERE hit_sess_ID'.$operator.$sess_ID;
	$count_sql .= ' LEFT JOIN T_hitlog ON ghit_hit_ID = hit_ID
								LEFT JOIN T_sessions ON hit_sess_ID = sess_ID
				 WHERE hit_sess_ID'.$operator.$sess_ID;
}
elseif( !empty($goal_name) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$operator = ($exclude ? ' NOT LIKE ' : ' LIKE ' );
	$sql .= ' WHERE goal_name'.$operator.$DB->quote($goal_name.'%');
	$count_sql .= ' LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID
						WHERE goal_name'.$operator.$DB->quote($goal_name.'%');

}

$Results = & new Results( $sql, 'hits_', '--D', 20, $count_sql );

$Results->title = T_('Recent goal hits');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_hits( & $Form )
{
	$Form->checkbox_basic_input( 'exclude', get_param('exclude'), T_('Exclude').' &mdash; ' );
	$Form->text_input( 'sess_ID', get_param('sess_ID'), 15, T_('Session ID'), '', array( 'maxlength'=>20 ) );
	$Form->text_input( 'goal_name', get_param('goal_name'), 20, T_('Goal names starting with'), '', array( 'maxlength'=>50 ) );
}
$Results->filter_area = array(
	'callback' => 'filter_hits',
	'url_ignore' => 'results_hits_page,exclude,sess_ID,goal_name',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0' ),
		'all_but_curr' => array( T_('All but current session'), '?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0&amp;sess_ID='.$Session->ID.'&amp;exclude=1' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('Session'),
		'order' => 'hit_sess_ID',
		'td_class' => 'right',
		'td' => '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_ID$</a>',
	);

$Results->cols[] = array(
		'th' => T_('User'),
		'order' => 'user_login',
		'td' => '%stat_session_login( #user_login#, true )%',
	);

$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'ghit_ID',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #hit_datetime#, "M-d" )%',
 	);

$Results->cols[] = array(
		'th' => T_('Type'),
		'order' => 'hit_referer_type',
		'td' => '$hit_referer_type$',
	);

$Results->cols[] = array(
		'th' => T_('U.A.'),
		'order' => 'agnt_type',
		'td' => '$agnt_type$',
	);

$Results->cols[] = array(
		'th' => T_('Referer'),
		'order' => 'dom_name',
		'td_class' => 'nowrap',
		'td' => '<a href="$hit_referer$">$dom_name$</a>',
	);

// Keywords:
$Results->cols[] = array(
		'th' => T_('Search keywords'),
		'td' => '%stats_search_keywords( #hit_referer# )%',
	);

$Results->cols[] = array(
		'th' => T_('Goal'),
		'order' => 'goal_name',
		'default_dir' => 'D',
		'td' => '$goal_name$',
	);

$Results->cols[] = array(
		'th' => T_('Hits'),
		'order' => 'sess_hitcount',
		'td_class' => 'right',
		'td' => '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_hitcount$</a>',
	);

// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>