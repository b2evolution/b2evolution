<?php
/**
 * This file implements the UI view for Users > User sessions > Hits
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
$remote_IP = param( 'remote_IP', 'string', NULL, true );
$referer_type = param( 'referer_type', 'string', NULL, true );
$agent_type = param( 'agent_type', 'string', NULL, true );

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE hit_ID, sess_ID, hit_datetime, hit_referer_type, hit_uri, hit_blog_ID, hit_referer, hit_remote_addr,'
	. 'user_login, hit_agent_type, blog_shortname, dom_name, goal_name, keyp_phrase, hit_serprank' );
$SQL->FROM( 'T_hitlog LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID'
	. ' LEFT JOIN T_track__keyphrase ON hit_keyphrase_keyp_ID = keyp_ID'
	. ' LEFT JOIN T_sessions ON hit_sess_ID = sess_ID'
	. ' LEFT JOIN T_blogs ON hit_blog_ID = blog_ID'
	. ' LEFT JOIN T_users ON sess_user_ID = user_ID'
	. ' LEFT JOIN T_track__goalhit ON hit_ID = ghit_hit_ID'
	. ' LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT(hit_ID)' );
$CountSQL->FROM( 'T_hitlog' );

$operator = ($exclude ? ' <> ' : ' = ' );

if( ! empty( $sess_ID ) )
{	// We want to filter on the session ID:
	$filter = 'hit_sess_ID' . $operator . $sess_ID;
	$SQL->WHERE( $filter );
	$CountSQL->WHERE( $filter );
}
elseif( !empty($remote_IP) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$filter = 'hit_remote_addr' . $operator . $DB->quote( $remote_IP );
	$SQL->WHERE( $filter );
	$CountSQL->WHERE( $filter );
}
if( !empty($referer_type) ) 
{ 
	$filter = 'hit_referer_type = ' .$DB->quote($referer_type);
	$SQL->WHERE_and( $filter );
	$CountSQL->WHERE_and( $filter );
}

if( !empty($agent_type) ) 
{ 
	$filter = 'hit_agent_type = '. $DB->quote($agent_type);
	$SQL->WHERE_and( $filter );
	$CountSQL->WHERE_and( $filter );
}

$Results = new Results( $SQL->get(), 'hits_', '--D', 20, $CountSQL->get() );

$Results->title = T_('Recent hits');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_hits( & $Form )
{
	$Form->checkbox_basic_input( 'exclude', get_param('exclude'), T_('Exclude').' &mdash; ' );
	$Form->text_input( 'sess_ID', get_param('sess_ID'), 15, T_('Session ID'), '', array( 'maxlength'=>20 ) );
	$Form->text_input( 'remote_IP', get_param('remote_IP'), 15, T_('Remote IP'), '', array( 'maxlength'=>23 ) );

	$field_options = array ('0'			=> 'All',
							'search'	=> 'Search',
							'blacklist' => 'Blacklist',
							'referer'	=> 'Referer',
							'direct'	=> 'Direct',
							'spam'		=> 'Spam',
							'self'		=> 'Self',
							);

	$Form->select_input_array( 'referer_type', get_param('referer_type'), $field_options, 'Referer type', '', array('force_keys_as_values' => true) );

	$field_options = array ('0'			=> 'All',
							'rss'		=> 'RSS',
							'robot'		=> 'Robot',
							'browser'	=> 'Browser',
							'unknown'	=> 'Unknown',
							);

	$Form->select_input_array( 'agent_type', get_param('agent_type'), $field_options, 'Agent type', '', array('force_keys_as_values' => true) );
}
$Results->filter_area = array(
	'callback' => 'filter_hits',
	'url_ignore' => 'results_hits_page,exclude,sess_ID,remote_IP',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0' ),
		'all_but_curr' => array( T_('All but current session'), '?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID='.$Session->ID.'&amp;exclude=1' ),
		'direct_hits' => array( T_('Direct hits'), '?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;referer_type=direct&amp;exclude=0' )
		)
	);

if( $sess_ID == NULL )
{
	$session_link = '%stat_session_hits( #sess_ID#, #sess_ID# )%';
}
else
{
	$session_link = '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0" title="'.T_( 'Show all sessions' ).'">$sess_ID$</a>';
}
$Results->cols[] = array(
		'th' => T_('Session'),
		'order' => 'hit_sess_ID',
		'td_class' => 'right',
		'td' => $session_link,
	);

$Results->cols[] = array(
		'th' => T_('User'),
		'order' => 'user_login',
		'td_class' => 'shrinkwrap',
		'td' => '%stat_session_login( #user_login# )%',
	);

$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_ID',
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
		'order' => 'hit_agent_type',
		'td' => '$hit_agent_type$',
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
		'order' => 'keyp_phrase',
		'td' => '%stats_search_keywords( #keyp_phrase#, 45 )%',
	);

// Serp Rank:
$Results->cols[] = array(
		'th' => T_('SR'),
		'order' => 'hit_serprank',
		'td_class' => 'center',
		'td' => '$hit_serprank$',
	);

$Results->cols[] = array(
		'th' => T_('Goal'),
		'order' => 'goal_name',
		'default_dir' => 'D',
		'td' => '$goal_name$',
	);

$Results->cols[] = array(
		'th' => T_('Blog'),
		'order' => 'hit_blog_ID',
		'td' => '$blog_shortname$',
	);

// Requested URI (linked to blog's baseurlroot+URI):
$Results->cols[] = array(
		'th' => T_('Requested URI'),
		'order' => 'hit_uri',
		'td' => '%stats_format_req_URI( #hit_blog_ID#, #hit_uri# )%',
	);

$Results->cols[] = array(
		'th' => T_('Remote IP'),
		'order' => 'hit_remote_addr',
		'td' => '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;remote_IP=$hit_remote_addr$">$hit_remote_addr$</a>',
	);



// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.18  2011/09/30 06:25:24  efy-vitalij
 * Remove Hits tab from User Sessions
 *
 * Revision 1.17  2011/09/29 09:27:32  efy-vitalij
 * add referer type and agent type filters and add direct hits filter link
 *
 * Revision 1.16  2011/09/23 22:37:09  fplanque
 * minor / doc
 *
 * Revision 1.15  2011/09/23 07:41:57  efy-asimo
 * Unified usernames everywhere in the app - first part
 *
 * Revision 1.14  2011/09/04 22:13:18  fplanque
 * copyright 2011
 *
 * Revision 1.13  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.12  2010/01/30 18:55:34  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.11  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.10  2009/09/25 20:26:26  fplanque
 * fixes/doc
 *
 * Revision 1.9  2009/09/25 13:09:36  efy-vyacheslav
 * Using the SQL class to prepare queries
 *
 * Revision 1.8  2009/09/20 00:27:08  fplanque
 * cleanup/doc/simplified
 *
 * Revision 1.7  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.6  2009/07/09 00:11:18  fplanque
 * minor
 *
 * Revision 1.5  2009/07/08 01:45:48  sam2kb
 * Added param $length to stats_search_keywords()
 * Changed keywords length for better accessibility on low resolution screens
 *
 * Revision 1.4  2009/05/10 00:28:51  fplanque
 * serp rank logging
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/05/10 22:59:10  fplanque
 * keyphrase logging
 *
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>