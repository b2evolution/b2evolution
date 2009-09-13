<?php
/**
 * This file implements the UI view for the Session list.
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

$user = param( 'user', 'string', '', true );

// Create result set:
$sql = 'SELECT SQL_NO_CACHE sess_ID, user_login, sess_hitcount, sess_lastseen, sess_ipaddress
					FROM T_sessions LEFT JOIN T_users ON sess_user_ID = user_ID';
$count_sql = 'SELECT SQL_NO_CACHE COUNT(sess_ID)
					      FROM T_sessions';
if( !empty( $user ) )
{
	$sql .= ' WHERE user_login LIKE "%'.$DB->escape($user).'%"';
	$count_sql .= ' LEFT JOIN T_users ON sess_user_ID = user_ID
		WHERE user_login LIKE "%'.$DB->escape($user).'%"';
}

$Results = & new Results( $sql, 'sess_', 'D', 20, $count_sql );

$Results->title = T_('Recent sessions');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_sessions( & $Form )
{
	$Form->text( 'user', get_param('user'), 20, T_('User login') );
}
$Results->filter_area = array(
	'callback' => 'filter_sessions',
	'url_ignore' => 'results_sess_page,user',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;blog=0' ),
		)
	);

$Results->cols[] = array(
						'th' => T_('ID'),
						'order' => 'sess_ID',
						'default_dir' => 'D',
						'td_class' => 'right',
						'td' => '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_ID$</a>',
					);

$Results->cols[] = array(
						'th' => T_('Last seen'),
						'order' => 'sess_lastseen',
						'default_dir' => 'D',
						'td_class' => 'timestamp',
						'td' => '%mysql2localedatetime_spans( #sess_lastseen# )%',
 					);

$Results->cols[] = array(
						'th' => T_('User login'),
						'order' => 'user_login',
						'td' => '%stat_session_login( #user_login# )%',
					);

$Results->cols[] = array(
						'th' => T_('Remote IP'),
						'order' => 'sess_ipaddress',
						'td' => '$sess_ipaddress$',
					);

$Results->cols[] = array(
						'th' => T_('Hit count'),
						'order' => 'sess_hitcount',
						'td_class' => 'right',
						'total_class' => 'right',
						'td' => '$sess_hitcount$',
					);

// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.6  2009/09/13 21:27:20  blueyed
 * SQL_NO_CACHE for SELECT queries using T_sessions
 *
 * Revision 1.5  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.4  2008/12/27 21:09:28  fplanque
 * minor
 *
 * Revision 1.3  2008/12/27 20:19:30  fplanque
 * partial rollback ( changes don't make sense to me )
 *
 * Revision 1.2  2008/11/20 23:11:41  blueyed
 * Session stats: fix SQL for 'Sessions'/user view and ignore invalidated sessions
 * fp>why ignore invalidated sessions?
 *
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>