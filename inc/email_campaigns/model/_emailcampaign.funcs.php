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

	$EmailCampaignCache = & get_EmailCampaignCache();
	$EmailCampaign = & $EmailCampaignCache->get_by_ID( $campaign_ID );

	$modes = array();

	$edit_url = $admin_url.'?ctrl=campaigns'.$glue.'action=edit'.$glue.'ecmp_ID='.$campaign_ID;

	$url = $edit_url.$glue.'tab=info';
	$modes['info'] = array(
		'text' => T_('Campaign info'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['info']['onclick'] = "return b2edit_reload( '#campaign_form', '$url', 'undefined', {tab:'info'} );";
	}

	$url = $edit_url.$glue.'tab=compose';
	$modes['compose'] = array(
		'text' => T_('Compose'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['compose']['onclick'] = "return b2edit_reload( '#campaign_form', '$url', 'undefined', {tab:'compose'} );";
	}

	$url = $edit_url.$glue.'tab=plaintext';
	$modes['plaintext'] = array(
		'text'  => T_('Plain-text version'),
		'href'  => $url,
		'class' => 'ecmp_plaintext_tab'.( $EmailCampaign->get( 'sync_plaintext' ) ? ' hidden' : '' ),
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['plaintext']['onclick'] = "return b2edit_reload( '#campaign_form', '$url', 'undefined', {tab:'plaintext'} );";
	}

	$url = $edit_url.$glue.'tab=send';
	$modes['send'] = array(
		'text' => T_('Review and send'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['send']['onclick'] = "return b2edit_reload( '#campaign_form', '$url', 'undefined', {tab:'send'} );";
	}

	$url = $edit_url.$glue.'tab=recipient'.$glue.'filter=new';
	$modes['recipient'] = array(
		'text' => T_('Recipient list'),
		'href' => $url
	);
	if( $current_User->check_perm( 'emails', 'edit' ) )
	{ // User must has a permission to edit emails
		$modes['recipient']['onclick'] = "return b2edit_reload( '#campaign_form', '$url', 'undefined', {tab:'recipient'} );";
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

	$EmailCampaignCache = & get_EmailCampaignCache();
	$EmailCampaign = & $EmailCampaignCache->get_by_ID( $campaign_ID );

	switch( $type )
	{
		case 'current':
			// Get URL of current tab
			if( !empty( $modes[ $current_tab ] ) )
			{
				if( $current_tab == 'plaintext' && $EmailCampaign->get( 'sync_plaintext' ) )
				{	// Don't allow tab plaintext when it is not enabled:
					return $modes['compose']['href'];
				}
				else
				{
					return $modes[ $current_tab ]['href'];
				}
			}
			break;

		case 'next':
		default:
			// Get URL of next tab:
			$this_tab = false;
			foreach( $modes as $tab_name => $tab_info )
			{
				if( $this_tab )
				{ // We find URL for next tab
					return ( $tab_name == 'plaintext' && $EmailCampaign->get( 'sync_plaintext' ) ) ? $modes['send']['href'] : $tab_info['href'];
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
 * Check if campaign email is opened
 *
 * @param integer Email log ID
 * @param array Associative array of campaign email send data
 * @return mixed NULL if the email log record does not exist, true if email is considered open, false otherwise
 */
function is_unopened_campaign_mail( $email_log_ID, & $send_data = NULL )
{
	global $DB;

	$send_data = $DB->get_row( 'SELECT * FROM T_email__campaign_send WHERE csnd_emlog_ID = '.$DB->quote( $email_log_ID ).' LIMIT 1', ARRAY_A );

	if( $send_data )
	{
		// Unsubscribe clicks do not "open" emails
		return empty( $send_data['csnd_last_open_ts'] ) && // image load
				empty( $send_data['csnd_last_click_ts'] ) &&
				empty( $send_data['csnd_like'] ) &&
				empty( $send_data['csnd_cta1'] ) &&
				empty( $send_data['csnd_cta2'] ) &&
				empty( $send_data['csnd_cta3'] );
	}
	else
	{
		return NULL;
	}
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
	$edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( $Session->get( 'edited_campaign_ID' ), false, false );

	return $edited_EmailCampaign;
}


/**
 * Display link to filtered userlist
 *
 * @param Object EmailCampaign object
 * @param String Recipient action on email campaign
 * @return String <a> tag
 */
function campaign_td_recipient_action( $row, $recipient_action )
{
	global $admin_url;

	if( empty( $row->ecmp_send_count ) )
	{
		return NULL;
	}

	$url = $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID='.$row->ecmp_ID.'&amp;tab=recipient&amp;filter=new';

	switch( $recipient_action )
	{
		case 'img_loaded':
			$text = $row->ecmp_img_loads;
			break;

		case 'link_clicked':
			$text = $row->ecmp_link_clicks;
			break;

		case 'cta1':
			$text = $row->ecmp_cta1_clicks;
			break;

		case 'cta2':
			$text = $row->ecmp_cta2_clicks;
			break;

		case 'cta3':
			$text = $row->ecmp_cta3_clicks;
			break;

		case 'liked':
			$text = $row->ecmp_like_count;
			$class = 'text-success';
			break;

		case 'disliked':
			$text = $row->ecmp_dislike_count;
			$class = 'text-danger';
			break;

		case 'clicked_unsubscribe':
			$text = $row->ecmp_unsub_clicks;
			$class = 'text-danger';
			break;
	}

	return '<a href="'.$url.( empty( $recipient_action ) ? '' : '&amp;recipient_action='.$recipient_action ).'"'.
			( empty( $class ) ? '': ' class="'.$class.'"' ).'>'.$text.'</a>';
}


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_campaign_results_block( & $Form )
{
	$Form->text_input( 'username', get_param( 'username' ), 40, T_('Username or email address') );
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
	$SQL->SELECT( 'T_email__campaign.*, enlt_ID, enlt_name, IF( ecmp_send_count = 0, 0, ecmp_open_count / ecmp_send_count ) AS open_rate' );
	$SQL->FROM( 'T_email__campaign' );
	$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );
	$SQL->WHERE( 1 );

	$count_SQL = new SQL();
	$count_SQL->SELECT( 'COUNT( ecmp_ID )' );
	$count_SQL->FROM( 'T_email__campaign' );
	$count_SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );

	if( isset( $params['enlt_ID'] ) )
	{	// Filter by Newsletter:
		$SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
		$count_SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
	}

	$username = param( 'username', 'string', NULL );
	if( ! empty( $username ) )
	{	// Filter by user login, first name, last name, nickname:
		$sql_where = array();
		$kw_array = explode( ' ', $username );
		foreach( $kw_array as $kw )
		{	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
			$sql_where[] = 'CONCAT_WS( " ", user_login, user_firstname, user_lastname, user_nickname, user_email ) LIKE '.$DB->quote( '%'.$kw.'%' );
		}
		$sql_where = implode( ' OR ', $sql_where );
		$SQL->WHERE_and( $sql_where );
		$count_SQL->WHERE_and( $sql_where );
		// Join additional tables for the user columns:
		$SQL->FROM_add( 'LEFT JOIN T_users ON csnd_user_ID = user_ID' );
		$count_SQL->FROM_add( 'LEFT JOIN T_email__campaign_send ON csnd_camp_ID = ecmp_ID AND csnd_emlog_ID IS NOT NULL' );
		$count_SQL->FROM_add( 'LEFT JOIN T_users ON csnd_user_ID = user_ID' );
	}

	$Results = new Results( $SQL->get(), 'emcmp_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );
	$Results->Cache = & get_EmailCampaignCache();
	$Results->title = $params['results_title'];

	if( $current_User->check_perm( 'emails', 'edit' ) && $params['display_create_button'] )
	{ // User must has a permission to edit emails
		$Results->global_icon( T_('Create new campaign').'...', 'new', $admin_url.'?ctrl=campaigns&amp;action=new'.( isset( $params['enlt_ID'] ) ? '&amp;enlt_ID='.$params['enlt_ID'] : '' ), T_('Create new campaign').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	}

	$Results->filter_area = array( 'callback' => 'filter_campaign_results_block' );

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
			'td_class' => 'timestamp',
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
			'th' => T_('Campaign name'),
			'order' => 'ecmp_name',
			'td' => '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$"><b>$ecmp_name$</b></a>',
		);

	$Results->cols[] = array(
			'th' => T_('Email title'),
			'order' => 'ecmp_email_title',
			'td' => '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$&amp;tab=compose"><b>$ecmp_email_title$</b></a>',
		);

	$Results->cols[] = array(
			'th' => T_('Welcome'),
			'order' => 'ecmp_welcome',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' => '%campaign_td_welcome( #ecmp_ID#, #ecmp_welcome# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Sent manually'),
			'order' => 'ecmp_sent_ts',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'timestamp',
			'td' => '%mysql2localedatetime_spans( #ecmp_sent_ts# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Sent automatically'),
			'order' => 'ecmp_auto_sent_ts',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'timestamp',
			'td' => '%mysql2localedatetime_spans( #ecmp_auto_sent_ts# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Send count'),
			'order' => 'ecmp_send_count',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'$ecmp_send_count$'
		);

	$Results->cols[] = array(
			'th' => T_('Open rate'),
			'order' => 'open_rate',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%empty( #ecmp_send_count# ) ? "" : number_format( #open_rate# * 100, 1 )%%'
		);

	$Results->cols[] = array(
			'th' => /* TRANS: Image load count */ T_('Img loads'),
			'order' => 'ecmp_img_loads',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%campaign_td_recipient_action( {row}, "img_loaded" )%',
		);

	$Results->cols[] = array(
			'th' => T_('Link clicks'),
			'order' => 'ecmp_link_clicks',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%campaign_td_recipient_action( {row}, "link_clicked" )%',
		);

	$Results->cols[] = array(
			'th' => /* TRANS: Call To Action 1*/ T_('CTA1'),
			'order' => 'ecmp_cta1_clicks',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%campaign_td_recipient_action( {row}, "cta1" )%',
		);

	$Results->cols[] = array(
			'th' => /* TRANS: Call To Action 2*/ T_('CTA2'),
			'order' => 'ecmp_cta2_clicks',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%campaign_td_recipient_action( {row}, "cta2" )%',
		);

	$Results->cols[] = array(
			'th' => /* TRANS: Call To Action 3*/ T_('CTA3'),
			'order' => 'ecmp_cta3_clicks',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center',
			'td' =>'%campaign_td_recipient_action( {row}, "cta3" )%',
		);

	$Results->cols[] = array(
			'th' => T_('Likes'),
			'order' => 'ecmp_like_count',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center text-success',
			'td' =>'%campaign_td_recipient_action( {row}, "liked" )%',
		);

	$Results->cols[] = array(
			'th' => T_('Dislikes'),
			'order' => 'ecmp_dislike_count',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center text-danger',
			//'td' =>'%empty( #send_count# ) ? "" : #dislike_count#%'
			'td' =>'%campaign_td_recipient_action( {row}, "disliked" )%',
		);

	$Results->cols[] = array(
			'th' => T_('Unsub clicks'),
			'order' => 'ecmp_unsub_clicks',
			'default_dir' => 'D',
			'th_class' => 'shrinkwrap',
			'td_class' => 'center text-danger',
			//'td' =>'%empty( #send_count# ) ? "" : #unsubscribe_click_count#%'
			'td' => '%campaign_td_recipient_action( {row}, "clicked_unsubscribe" )%'
		);

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( T_('Edit this email campaign...'), 'properties', $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$' )
				.( $current_User->check_perm( 'emails', 'edit' ) ?
				// Display an action icon to delete newsletter if current User has a perm:
				action_icon( T_('Duplicate this email campaign...'), 'copy', $admin_url.'?ctrl=campaigns&amp;action=copy&amp;ecmp_ID=$ecmp_ID$' )
				.action_icon( T_('Delete this email campaign!'), 'delete', $admin_url.'?ctrl=campaigns&amp;action=delete&amp;ecmp_ID=$ecmp_ID$&amp;'.url_crumb( 'campaign' ) ) : '' )
		);

	// Display results:
	$Results->display( NULL, 'session' );
}


/**
 * Helper function to display a welcome status of email campaign in list
 *
 * @param integer Email Campaign ID
 * @param integer TRUE/1 if this is a welcome Email Campaign
 * @return string
 */
function campaign_td_welcome( $ecmp_ID, $ecmp_welcome )
{
	global $current_User;

	if( $ecmp_welcome )
	{	// If newsletter is active:
		$welcome_icon = get_icon( 'bullet_green', 'imgtag', array( 'title' => T_('The email campaign is used as "Welcome" for its list.') ) );
	}
	else
	{	// If newsletter is NOT active:
		$welcome_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => T_('The email campaign is not used as "Welcome" for its list.') ) );
	}

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{	// Make icon toggle welcome status if current User has a perm to edit this:
		global $admin_url, $ctrl;
		$url_param = $ctrl == 'newsletters' ? '&amp;from='.$ctrl : '';
		$welcome_icon = '<a href="'.$admin_url.'?ctrl=campaigns&amp;action='.( $ecmp_welcome ? 'disable_welcome' : 'enable_welcome' )
			.'&amp;ecmp_ID='.$ecmp_ID.$url_param.'&amp;'.url_crumb( 'campaign' ).'">'.$welcome_icon.'</a>';
	}

	return $welcome_icon;
}
?>