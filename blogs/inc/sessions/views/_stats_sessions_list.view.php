<?php
/**
 * This file implements the UI view for the Session list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _stats_sessions_list.view.php 6411 2014-04-07 15:17:33Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url, $UserSettings, $edited_User, $user_tab, $Plugins;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

$user_ID = param( 'user_ID', 'integer', 0, true );

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE sess_ID, user_login, TIMESTAMPDIFF( SECOND, sess_start_ts, sess_lastseen_ts ) as sess_length, sess_lastseen_ts, sess_ipaddress' );
$SQL->FROM( 'T_sessions LEFT JOIN T_users ON sess_user_ID = user_ID' );

$Count_SQL = new SQL();
$Count_SQL->SELECT( 'SQL_NO_CACHE COUNT(sess_ID)' );
$Count_SQL->FROM( 'T_sessions LEFT JOIN T_users ON sess_user_ID = user_ID' );

if( empty( $user_ID ) )
{ // display only this user sessions in user tab
	$user_ID = $edited_User->ID;
}

$SQL->WHERE( 'user_ID = '.$user_ID );
$Count_SQL->WHERE( 'user_ID = '.$user_ID );

memorize_param( 'user_tab', 'string', '', $user_tab );

// Begin payload block:
$this->disp_payload_begin();

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'sessions'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Results = new Results( $SQL->get(), 'sess_', 'D', $UserSettings->get( 'results_per_page' ), $Count_SQL->get() );

// echo user edit action icons
echo_user_actions( $Results, $edited_User, 'edit' );
echo '<span class="floatright">'.$Results->gen_global_icons().'</span>';
$Results->global_icons = array();

// echo user tabs
echo '<div>'.get_usertab_header( $edited_User, $user_tab, T_( 'Sessions' ) ).'</div>';

$Results->title = T_('Recent sessions');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */

$Results->cols[] = array(
						'th' => T_('ID'),
						'order' => 'sess_ID',
						'default_dir' => 'D',
						'td_class' => 'right',
						'td' => '<a href="?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_ID$</a>',
					);

$Results->cols[] = array(
						'th' => T_('Last seen'),
						'order' => 'sess_lastseen_ts',
						'default_dir' => 'D',
						'td_class' => 'timestamp',
						'td' => '%mysql2localedatetime_spans( #sess_lastseen_ts# )%',
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

// Get additional columns from the Plugins
$Plugins->trigger_event( 'GetAdditionalColumnsTable', array(
	'table'   => 'sessions',
	'column'  => 'sess_ipaddress',
	'Results' => $Results ) );

function display_sess_length( $sess_ID, $sess_length )
{
	$result = '';
	$second = $sess_length % 60;
	$sess_length = ( $sess_length - $second ) / 60;
	$minute = $sess_length % 60;
	$sess_length = ( $sess_length - $minute ) / 60;
	$hour = $sess_length % 24;
	$day = ( $sess_length - $hour ) / 24;

	if( $day > 0 )
	{
		$result = sprintf( ( ( $day > 1 ) ? T_( '%d days' ) : T_( '%d day' ) ), $day ).' ';
	}
	if( $hour < 10 )
	{
		$hour = '0'.$hour;
	}
	if( $minute < 10 )
	{
		$minute = '0'.$minute;
	}
	if( $second < 10 )
	{
		$second = '0'.$second;
	}

	$result .= $hour.':'.$minute.':'.$second;
	return stat_session_hits( $sess_ID, $result );
}

$Results->cols[] = array(
						'th' => T_('Session length'),
						'order' => 'sess_length',
						'td_class' => 'center',
						'total_class' => 'right',
						'td' => '%display_sess_length( #sess_ID#, #sess_length# )%',
					);

// Display results:
$Results->display();

// End payload block:
$this->disp_payload_end();

?>