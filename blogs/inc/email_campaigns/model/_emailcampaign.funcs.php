<?php
/**
 * This file implements newsletter functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _emailcampaign.funcs.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get number of users for newsletter from UserList filterset
 *
 * @return array 
 * 		'all' - Number of accounts in filterset
 * 		'active' - Number of active accounts in filterset
 * 		'newsletter' - Number of active accounts which accept newsletter email
 */
function get_newsletter_users_numbers()
{
	$numbers = array(
			'all'        => 0,
			'active'     => 0,
			'newsletter' => 0,
		);

	$users_IDs = get_filterset_user_IDs();

	if( count( $users_IDs ) )
	{ // Found users in the filterset
		global $DB;

		$numbers['all'] = count( $users_IDs );

		// Get number of all active users
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( * )' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_ID IN ( '.implode( ', ', $users_IDs ).' )' );
		$SQL->WHERE_and( 'user_status IN ( \'activated\', \'autoactivated\' )' );
		$numbers['active'] = $DB->get_var( $SQL->get() );

		// Get number of all active users which accept newsletter email
		$SQL = get_newsletter_users_sql( $users_IDs );
		$SQL->SELECT( 'COUNT( * )' );
		$numbers['newsletter'] = $DB->get_var( $SQL->get() );
	}

	return $numbers;
}


/**
 * Get SQL for active users which accept newsletter email
 *
 * @param array users IDs
 * @return object SQL
 */
function get_newsletter_users_sql( $users_IDs )
{
	global $Settings;

	$SQL = new SQL();
	$SQL->SELECT( 'u.user_ID' );
	$SQL->FROM( 'T_users u' );
	$SQL->FROM_add( 'LEFT OUTER JOIN T_users__usersettings us ON u.user_ID = us.uset_user_ID' );
	$SQL->FROM_add( 'AND us.uset_name = \'newsletter_news\'' );
	$SQL->WHERE( 'u.user_ID IN ( '.implode( ', ', $users_IDs ).' )' );
	$SQL->WHERE_and( 'u.user_status IN ( \'activated\', \'autoactivated\' )' );
	if( $Settings->get( 'def_newsletter_news' ) )
	{	// If General setting "newsletter_news" = 1 we also should include all users without defined user's setting "newsletter_news"
		$SQL->WHERE_and( '( us.uset_value = 1 OR us.uset_value IS NULL )' );
	}
	else
	{	// If General setting "newsletter_news" = 0 we take only users which accept newsletter email
		$SQL->WHERE_and( 'us.uset_value = 1' );
	}

	return $SQL;
}


/**
 * Get user IDs from current filterset of users list
 *
 * @param string Filterset name
 * return array User IDs
 */
function get_filterset_user_IDs( $filterset_name = 'admin' )
{
	load_class( 'users/model/_userlist.class.php', 'UserList' );
	// Initialize users list from session cache in order to get users IDs for newsletter
	$UserList = new UserList( $filterset_name );
	$UserList->memorize = false;
	$UserList->load_from_Request();

	return $UserList->filters['users'];
}

/**
 * Get campaign edit modes
 *
 * @param integer Campaign ID
 * @return array with modes
 */
function get_campaign_edit_modes( $campaign_ID, $glue = '&amp;' )
{
	global $admin_url, $current_User;

	$modes = array();

	$edit_url = $admin_url.'?ctrl=campaigns'.$glue.'action=edit'.$glue.'ecmp_ID='.$campaign_ID;

	$url = $edit_url.$glue.'tab=info';
	$modes['info'] = array(
		'text' => T_('Campaign info'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['info']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'info'} );";
	}

	$url = $edit_url.$glue.'tab=html';
	$modes['html'] = array(
		'text' => T_('HTML message'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['html']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'html'} );";
	}

	$url = $edit_url.$glue.'tab=text';
	$modes['text'] = array(
		'text' => T_('Plain Text message'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['text']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'text'} );";
	}

	$url = $edit_url.$glue.'tab=send';
	$modes['send'] = array(
		'text' => T_('Review and send'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['send']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'send'} );";
	}

	return $modes;
}


/**
 * Get URL for current/next tab of edit campaign view
 *
 * @param string Current tab: 'info', 'html', 'text', 'send'
 * @param integer Campaign ID
 * @param string Type of tab: 'current', 'next'
 * @param string Glue
 * @return string URL
 */
function get_campaign_tab_url( $current_tab, $campaign_ID, $type = 'current', $glue = '&' )
{
	$modes = get_campaign_edit_modes( $campaign_ID, $glue );

	switch( $type )
	{
		case 'current':
			// Get URL of current tab
			if( !empty( $modes[ $current_tab ] ) )
			{
				return $modes[ $current_tab ]['href'];
			}
			break;

		case 'next':
		default:
			// Get URL of next tab
			$this_tab = false;
			foreach( $modes as $tab_name => $tab_info )
			{
				if( $this_tab )
				{ // We find URL for next tab
					return $tab_info['href'];
				}
				if( $tab_name == $current_tab )
				{ // The next tab will be what we find
					$this_tab = true;
				}
			}
		break;
	}

	return '';
}

?>