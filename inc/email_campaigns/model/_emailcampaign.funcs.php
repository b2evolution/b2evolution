<?php
/**
 * This file implements newsletter functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
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
			SET csnd_status = IF( csnd_emlog_ID IS NULL, "ready_to_send", "ready_to_resend" )
			WHERE csnd_camp_ID = '.$DB->quote( $campaign_ID ).'
			AND csnd_user_ID = '.$DB->quote( $user_ID ) );
}


/**
 * Skip user from receiving email campaign
 *
 * @param integer Campaign ID
 * @param integer User ID to skip
 */
function skip_campaign_user( $campaign_ID, $user_ID )
{
	global $DB;

	if( empty( $campaign_ID ) || empty( $user_ID ) )
	{
		return;
	}

	$DB->query( 'UPDATE T_email__campaign_send
			SET csnd_status = "skipped"
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


/**
 * Display the campaigns results table
 *
 * @param array Params
 */
function campaign_results_block( $params = array() )
{
	global $admin_url, $UserSettings, $current_User, $DB;

	$params = array_merge( array(
		'enlt_ID'               => NULL,
		'results_title'         => T_('Email campaigns').get_manual_link( 'email-campaigns' ),
		'display_create_button' => true
	), $params );

	// Create result set:
	$SQL = new SQL();
	$SQL->SELECT( 'SQL_NO_CACHE *' );
	$SQL->FROM( 'T_email__campaign' );
	$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );
	$SQL->FROM_add( 'LEFT JOIN (
										SELECT csnd_camp_ID, COUNT(*) AS send_count
										FROM T_email__campaign_send
										WHERE csnd_emlog_ID IS NOT NULL
										GROUP BY csnd_camp_ID
									) AS sent
										ON sent.csnd_camp_ID = ecmp_ID' );

	/* The query below might be more efficient:
	$SQL = new SQL();
	$SQL->SELECT( 'SQL_NO_CACHE T_email__campaign.*, T_email__newsletter.*, SUM(IF(csnd_emlog_ID IS NOT NULL, 1, 0)) AS send_count' );
	$SQL->FROM( 'T_email__campaign' );
	$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );
	$SQL->FROM_add( 'LEFT JOIN T_email__campaign_send ON csnd_camp_ID = ecmp_ID AND csnd_emlog_ID IS NOT NULL' );
	$SQL->WHERE( 1 );
	$SQL->GROUP_BY( 'ecmp_ID' );
	*/

	$count_SQL = new SQL();
	$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( ecmp_ID )' );
	$count_SQL->FROM( 'T_email__campaign' );
	$count_SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );

	if( isset( $params['enlt_ID'] ) )
	{
		$SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
		$count_SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
	}

	$Results = new Results( $SQL->get(), 'emcmp_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );
	$Results->Cache = & get_EmailCampaignCache();
	$Results->title = $params['results_title'];

	if( $current_User->check_perm( 'emails', 'edit' ) && $params['display_create_button'] )
	{ // User must has a permission to edit emails
		$Results->global_icon( T_('Create new campaign').'...', 'new', $admin_url.'?ctrl=campaigns&amp;action=new', T_('Create new campaign').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	}

	$Results->cols[] = array(
			'th' => T_('ID'),
			'order' => 'ecmp_ID',
			'th_class' => 'shrinkwrap',
			'td_class' => 'right',
			'td' => '$ecmp_ID$',
		);

	$Results->cols[] = array(
			'th' => T_('Date'),
			'order' => 'ecmp_date_ts',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'timestamp compact_data',
			'td' => '%mysql2localedatetime_spans( #ecmp_date_ts# )%',
		);

	$Results->cols[] = array(
			'th' => T_('List'),
			'order' => 'enlt_name',
			'td' => '<a href="'.$admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID=$enlt_ID$"><b>$enlt_name$</b></a>',
			'th_class' => 'shrinkwrap',
			'td_class' => 'nowrap',
		);

	$Results->cols[] = array(
			'th' => T_('Email title'),
			'order' => 'ecmp_email_title',
			'td' => '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$"><b>$ecmp_email_title$</b></a>',
		);

	$Results->cols[] = array(
			'th' => T_('Sending'),
			'order' => 'ecmp_auto_send',
			'th_class' => 'shrinkwrap',
			'td_class' => 'nowrap',
			'td' => '%{Obj}->get_sending_title()%',
		);

	$Results->cols[] = array(
			'th' => T_('Sent manually'),
			'order' => 'ecmp_sent_ts',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'timestamp compact_data',
			'td' => '%mysql2localedatetime_spans( #ecmp_sent_ts# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Sent automatically'),
			'order' => 'ecmp_auto_sent_ts',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'timestamp compact_data',
			'td' => '%mysql2localedatetime_spans( #ecmp_auto_sent_ts# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Send count'),
			'order' => 'send_count',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'$send_count$'
		);

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( T_('Edit this email campaign...'), 'properties', $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$' )
				.( $current_User->check_perm( 'emails', 'edit' ) ?
				// Display an action icon to delete newsletter if current User has a perm:
				action_icon( T_('Delete this email address!'), 'delete', regenerate_url( 'ecmp_ID,action', 'ecmp_ID=$ecmp_ID$&amp;action=delete&amp;'.url_crumb('campaign') ) ) : '' )
		);

	// Display results:
	$Results->display();
}
?>