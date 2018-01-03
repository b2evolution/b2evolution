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

	$url = $edit_url.$glue.'tab=recipient'.$glue.'filter=new';
	$modes['recipient'] = array(
		'text' => T_('Recipient list'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['recipient']['onclick'] = "return b2edit_reload( document.getElementById('campaign_form'), '$url', 'undefined', {tab:'recipient'} );";
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


/**
 * Queue user to receive to email campaign
 *
 * @param integer Campaign ID
 * @param integer User ID to queue
 */
function queue_campaign_user( $campaign_ID, $user_ID )
{
	global $DB;

	if( empty( $campaign_ID ) || empty( $user_ID ) )
	{
		return;
	}

	$DB->query( 'UPDATE T_email__campaign_send
			SET csnd_emlog_ID = NULL
			WHERE csnd_camp_ID = '.$DB->quote( $campaign_ID ).'
			AND csnd_user_ID = '.$DB->quote( $user_ID ) );
}


/**
 * Get EmailCampaign object from object which is used to select recipients
 *
 * @return object EmailCampaign
 */
function & get_session_EmailCampaign()
{
	global $Session;

	$EmailCampaignCache = & get_EmailCampaignCache();
	$edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( intval( $Session->get( 'edited_campaign_ID' ) ), false, false );

	return $edited_EmailCampaign;
}

?>