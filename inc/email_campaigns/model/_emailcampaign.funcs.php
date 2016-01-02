<?php
/**
 * This file implements newsletter functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get number of users for newsletter from UserList filterset
 *
 * @return array Numbers of users:
 *     'all' - Currently selected recipients (Accounts which accept newsletter emails)
 *     'active' - Already received (Accounts which have already been sent this newsletter)
 *     'newsletter' - Ready to send (Accounts which have not been sent this newsletter yet)
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

	$url = $edit_url.$glue.'tab=compose';
	$modes['compose'] = array(
		'text' => T_('Compose'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['compose']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'compose'} );";
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
 * @param string Current tab: 'info', 'compose', 'send'
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