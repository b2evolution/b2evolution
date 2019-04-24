<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Initialize Results object for hits list
 *
 * @param object Results
 * @param array Params
 */
function hits_results( & $Results, $params = array() )
{
	$params = array_merge( array(
			'default_order' => '--D'
		), $params );

	global $blog, $Session, $sess_ID;
	global $preset_results_title, $preset_referer_type, $preset_filter_all_url;
	global $hide_columns, $admin_url;

	$hide_columns = explode( ',', $hide_columns );

	$Results->title = isset( $preset_results_title ) ? $preset_results_title : T_('Recent hits').get_manual_link( 'recent-hits-list' );

	$param_prefix = 'results_'.$Results->param_prefix;
	$tab = get_param( 'tab' );

	$filter_presets = array();
	$filter_presets['all'] = array( T_('All'), isset( $preset_filter_all_url ) ? $preset_filter_all_url : $admin_url.'?ctrl=stats&amp;tab='.$tab.'&amp;blog='.$blog.'&amp;'.$param_prefix.'order='.$params['default_order'] );
	if( !isset( $preset_referer_type ) )
	{	// Show these presets only when referer type is not set
		$filter_presets['all_but_curr'] = array( T_('All but current session'), $admin_url.'?ctrl=stats&amp;tab='.$tab.'&amp;blog='.$blog.'&amp;sess_ID='.$Session->ID.'&amp;exclude=1&amp;'.$param_prefix.'order='.$params['default_order'] );
		$filter_presets['direct_hits'] = array( T_('Direct hits'), $admin_url.'?ctrl=stats&amp;agent_type=browser&amp;tab='.$tab.'&amp;blog='.$blog.'&amp;referer_type=direct&amp;exclude=0&amp;'.$param_prefix.'order='.$params['default_order'] );
		$filter_presets['refered_hits'] = array( T_('Refered hits'), $admin_url.'?ctrl=stats&amp;agent_type=browser&amp;tab='.$tab.'&amp;blog='.$blog.'&amp;referer_type=referer&amp;exclude=0&amp;'.$param_prefix.'order='.$params['default_order'] );
	}

	$Results->filter_area = array(
		'callback' => 'filter_hits',
		'url_ignore' => $param_prefix.'page,exclude,sess_ID,remote_IP',
		'presets' => $filter_presets
		);

	$Results->cols[] = array(
			'th' => T_('Session'),
			'order' => 'hit_sess_ID',
			'td_class' => 'right nowrap',
			'td' => '%stat_session_hits( #sess_ID#, #sess_ID# )%',
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
			'td' => '%mysql2localedatetime_spans( #hit_datetime# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Agent'),
			'order' => 'hit_agent_type',
			'td_class' => 'shrinkwrap',
			'td' => '$hit_agent_type$',
			'extra' => array ( 'style' => 'background-color: %hit_agent_type_color( "#hit_agent_type#" )%;',
			'format_to_output'	=> false)
		);

	$Results->cols[] = array(
			'th' => T_('Device'),
			'order' => 'sess_device',
			'td_class' => 'shrinkwrap',
			'td' => '$sess_device$',
			'extra' => array ( 'style' => 'background-color: %hit_device_color( "#sess_device#" )%;', 'format_to_output' => false )
		);

	if( !in_array( 'referer', $hide_columns ) )
	{ // Referer Type & Domain
		$Results->cols[] = array(
				'th_group' => T_('Referer'),
				'th' => T_('Type'),
				'order' => 'hit_referer_type',
				'td_class' => 'shrinkwrap',
				'td' => '$hit_referer_type$',
				'extra' => array ( 'style' => 'background-color: %hit_referer_type_color( "#hit_referer_type#" )%;',
					'format_to_output' => false )
			);

		$Results->cols[] = array(
				'th_group' => T_('Referer'),
				'th' => T_('Domain'),
				'order' => 'dom_name',
				'td_class' => 'nowrap',
				'td' => '<a href="$hit_referer$">$dom_name$</a>',
			);
	}

	// Keywords:
	$Results->cols[] = array(
			'th' => T_('Search keywords'),
			'order' => 'hit_keyphrase',
			'td' => '%stats_search_keywords( #hit_keyphrase#, 45 )%',
			'td_class' => 'nowrap'
		);

	// Serp Rank:
	$Results->cols[] = array(
			'th' => T_('SR'),
			'th_title' => T_('Serp rank'),
			'order' => 'hit_serprank',
			'td_class' => 'center nowrap',
			'td' => '$hit_serprank$',
		);

	$Results->cols[] = array(
			'th' => T_('Goal'),
			'order' => 'goal_name',
			'default_dir' => 'D',
			'td' => '$goal_name$',
			'td_class' => 'nowrap'
		);

	$Results->cols[] = array(
			'th' => T_('Collection'),
			'order' => 'hit_coll_ID',
			'td' => '$blog_shortname$',
			'td_class' => 'nowrap'
		);
	$Results->cols[] = array(
			'th' => T_('Hit type'),
			'order' => 'hit_type',
			'td_class' => 'shrinkwrap',
			'td' => '$hit_type$',
			'extra' => array (	'style'				=> 'background-color: %hit_type_color( "#hit_type#" )%',
								'format_to_output'	=> false)
		);
	// Requested URI (linked to blog's baseurlroot+URI):
	$Results->cols[] = array(
			'th' => T_('Requested URI'),
			'order' => 'hit_uri',
			'td' => '%stats_format_req_URI( #hit_coll_ID#, #hit_uri#, 40, #hit_disp#, #hit_ctrl#, #hit_action# )%',
			'td_class' => 'nowrap'
		);
	$Results->cols[] = array(
			'th' => T_('HTTP resp'),
			'order' => 'hit_response_code',
			'td' => '$hit_response_code$',
			'td_class' => '%hit_response_code_class( #hit_response_code# )% shrinkwrap'
		);
	$Results->cols[] = array(
			'th' => T_('HTTP meth'),
			'order' => 'hit_method',
			'td' => '$hit_method$',
			'td_class' => 'shrinkwrap',
			'extra' => array(
					'style' => '%hit_method_style( "#hit_method#" )%',
					'format_to_output'=> false
				)
		);

	$Results->cols[] = array(
			'th' => T_('Remote IP'),
			'order' => 'hit_remote_addr',
			'td' => '%disp_clickable_log_IP( #hit_remote_addr# )%',
			'td_class' => 'nowrap'
		);

	$Results->cols[] = array(
			'th' => T_('Agent Name'),
			'order' => 'hit_agent_ID',
			'td' => '%get_hit_agent_name_by_ID( #hit_agent_ID# )%',
			'td_class' => 'nowrap'
		);
}


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_hits( & $Form )
{
	global $referer_type_color, $referer_type_array;
	global $agent_type_color, $agent_type_array;
	global $user_devices_color, $user_devices;
	global $hit_type_color, $hit_type_array;
	global $datestart, $datestop;
	global $preset_referer_type, $preset_agent_type;
	global $DB, $blog;

	$Form->checkbox_basic_input( 'exclude', get_param( 'exclude' ), T_( 'Exclude' ).' &mdash; ' );
	$Form->text_input( 'sess_ID', get_param( 'sess_ID' ), 15, T_( 'Session ID' ), '', array( 'maxlength' => 20 ) );
	$Form->text_input( 'remote_IP', get_param( 'remote_IP' ), 15, T_( 'Remote IP' ), '', array( 'maxlength' => 23 ) );

	$Form->date_input( 'datestartinput', $datestart, T_( 'From date' ) );
	$Form->date_input( 'datestopinput', $datestop, T_( 'To date' ) );

	if( !isset( $preset_agent_type ) )
	{
		$Form->select_input_array( 'agent_type', get_param( 'agent_type' ), $agent_type_array, T_( 'Agent type' ), '', array( 'force_keys_as_values' => true, 'background_color' => $agent_type_color ) );
	}

	$devices_array = array_keys( $user_devices );
	$devices_array = array_merge(
			array( '0' => T_( 'All' ) ),
			array_combine( $devices_array, $devices_array ),
			array( 'other' => T_( 'Other' ) )
		);
	$Form->select_input_array( 'device', get_param( 'device' ), $devices_array , T_( 'Device' ), '', array( 'force_keys_as_values' => true, 'background_color' => $user_devices_color ) );

	if( !isset( $preset_referer_type ) )
	{
		$Form->select_input_array( 'referer_type', get_param( 'referer_type' ), $referer_type_array, T_( 'Referer type' ), '', array( 'force_keys_as_values' => true, 'background_color' => $referer_type_color ) );
	}
	$Form->select_input_array( 'hit_type', get_param( 'hit_type' ), $hit_type_array, T_( 'Hit type' ), '', array( 'force_keys_as_values' => true, 'background_color' => $hit_type_color ) );

	$Form->text_input( 'reqURI', get_param( 'reqURI' ), 15, T_( 'Requested URI' ), '', array( 'maxlength' => 250 ) );

	// Get the response codes that are used in b2evolution
	$resp_codes = array(
			'0' => T_( 'All' ),
			'200' => '200',
			'301' => '301',
			'302' => '302',
			'303' => '303',
			'304' => '304',
			'400' => '400',
			'403' => '403',
			'404' => '404',
			'410' => '410',
			'500' => '500'
		);
	$Form->select_input_array( 'resp_code', get_param( 'resp_code' ), $resp_codes, T_( 'HTTP resp' ), '', array( 'force_keys_as_values' => true ) );
}


/**
 * Helper function for "Requested URI" column
 *
 * @param integer Blog ID
 * @param string Requested URI
 * @param integer Output string lenght
 * @param string Display
 * @param string Controller
 * @return string
 */
function stats_format_req_URI( $hit_coll_ID, $hit_uri, $max_len = 40, $hit_disp = NULL, $hit_ctrl = NULL, $hit_action = NULL)
{
	$BlogCache = & get_BlogCache();
	if( $tmp_Blog = & $BlogCache->get_by_ID( $hit_coll_ID, false, false ) )
	{	// Use root url of the requested collection if it still exists in DB:
		$full_url = $tmp_Blog->get_baseurl_root().$hit_uri;
	}
	else
	{	// Don't use root url if a request was without collection or it doesn't exist in DB anymore:
		$full_url = $hit_uri;
	}

	$int_search_uri = urldecode( $hit_uri );
	if( ( utf8_strpos( $int_search_uri , '?s=' ) !== false )
	 || ( utf8_strpos( $int_search_uri , '&s=' ) !== false ) )
	{ // This is an internal search:
		preg_match( '~[?&]s=([^&#]*)~', $int_search_uri, $res );
		$hit_uri = sprintf( T_( 'Internal search: %s' ), $res[1] );
	}
	elseif( $hit_disp == 'redirect' )
	{	// This is a redirect:
		return '['.get_link_tag( $full_url, 'redirect' ).']';
	}
	elseif( strpos( $hit_uri, 'email_passthrough.php' ) !== false )
	{	// This is a click from email message:
		return '['.get_link_tag( $full_url, 'email_passthrough' ).']';
	}
	elseif( utf8_strlen( $hit_uri ) > $max_len )
	{
		$hit_uri = '...'.utf8_substr( $hit_uri, -$max_len );
	}

	if( $hit_disp != NULL || $hit_ctrl != NULL || $hit_action != NULL)
	{
		$hit_uri = '';
		if( $hit_disp != NULL )
		{
			$hit_uri .= '[disp=<a href="'.$full_url.'">'.$hit_disp.'</a>]';
			if( $hit_disp == 'single' || $hit_disp == 'page' )
			{	// Display item slug:
				$hit_uri .= ' <a href="'.$full_url.'">'.preg_replace( '#^.+/([^/]+)$#', '$1', $full_url ).'</a>';
			}
		}
		if( $hit_ctrl != NULL )
		{
			$hit_uri .= ' [ctrl=<a href="'.$full_url.'">'.$hit_ctrl.'</a>]';
		}
		if( $hit_action != NULL )
		{
			$hit_uri .= ' [action=<a href="'.$full_url.'">'.$hit_action.'</a>]';
		}

		return $hit_uri;
	}

	return '<a href="'.$full_url.'">'.$hit_uri.'</a>';
}


/**
 * display avatar and login linking to sessions list for user
 *
 * @param mixed $login
 */
function stat_session_login( $login )
{
	if( empty( $login ) )
	{
		return '<span class="note">'.T_('Anon.').'</span>';
	}

	return get_user_identity_link( $login, NULL, 'admin' );
}


/**
 * Display session hits
 *
 * @param string session ID
 * @param string link text
 */
function stat_session_hits( $sess_ID, $link_text )
{
	global $admin_url;

	$tab = get_param( 'tab' );
	if( empty( $tab ) )
	{
		$tab = 'hits';
	}

	return '<strong><a href="'.$admin_url.'?ctrl=stats&amp;tab='.$tab.'&amp;sess_ID='.$sess_ID.'&amp;blog=0">'.$link_text.'</a></strong>';
}


/**
 * Display clickable log IP address
 *
 * @param string remote adress IP
 */
function disp_clickable_log_IP( $hit_remote_addr )
{
	global $current_User, $admin_url;
	static $perm = NULL;

	if( empty( $perm ) )
	{
		$perm = $current_User->check_perm( 'stats', 'view' );
	}

	if( $perm == true )
	{
		return '<a href="?ctrl=stats&tab='.get_param( 'tab' ).'&colselect_submit=Filter+list&sess_ID=&remote_IP='.$hit_remote_addr.'&blog=0">'.$hit_remote_addr.'</a>'
				.' <a href="'.$admin_url.'?ctrl=antispam&amp;action=whois&amp;query='.$hit_remote_addr.'" onclick="return get_whois_info(\''.$hit_remote_addr.'\');">'
				.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('Check domain registration (WHOIS)...') ) ).'</a>';
	}
	else
	{
		return $hit_remote_addr;
	}
}


/**
 * Display color referer
 *
 * @param hit referer type
 */
function disp_color_referer( $hit_referer_type )
{
	global $referer_type_color;
	if( ! empty( $referer_type_color[$hit_referer_type] ) )
	{
		return '<span style="background-color: #'.$referer_type_color[$hit_referer_type].'">'.$hit_referer_type.'</span>';
	}
	else
	{
		return $hit_referer_type;
	}
}


/**
 * Display color agent type
 *
 * @param hit agent type
 */
function disp_color_agent( $hit_agent_type )
{
	global $agent_type_color;
	if( ! empty( $agent_type_color[$hit_agent_type] ) )
	{
		return '<span style="background-color: #'.$agent_type_color[$hit_agent_type].'">'.$hit_agent_type.'</span>';
	}
	else
	{
		return $hit_agent_type;
	}
}


/**
 * Generate html response code class
 *
 * @param integer response code
 * @return string class
 */
function hit_response_code_class( $hit_response_code )
{
	if( $hit_response_code >= 500 )
	{	// Server errors:
		return 'text-danger';
	}
	elseif( $hit_response_code >= 400 )
	{	// Code errors:
		return 'text-warning';
	}
	elseif( $hit_response_code == 304 )
	{	// 304 means "Not Modified"; Display this as success 2xx codes:
		return 'text-success';
	}
	elseif( $hit_response_code >= 300 )
	{	// Redirects:
		return 'text-info';
	}
	elseif( $hit_response_code >= 200 )
	{	// Success pages:
		return 'text-success';
	}

	return '';
}


/**
 * Generate color for hit type
 *
 * @param string hit_type
 * @return string color in hex format #FFFFFF
 */
function hit_type_color( $hit_type )
{
	global $hit_type_color;
	$color = '#FFFFFF';

	if( ! empty( $hit_type_color[$hit_type] ) )
	{
		$color ='#'.$hit_type_color[$hit_type];
	}

	return $color;
}


/**
 * Generate color for hit agent type
 *
 * @param string hit_agent_type
 * @return string color in hex format #FFFFFF
 */
function hit_agent_type_color( $hit_agent_type )
{
	global $agent_type_color;
	$color = '#FFFFFF';

	if( ! empty( $agent_type_color[$hit_agent_type] ) )
	{
		$color ='#'.$agent_type_color[$hit_agent_type];
	}

	return $color;
}


/**
 * Generate color for hit device
 *
 * @param string hit_device
 * @return string color in hex format #FFFFFF
 */
function hit_device_color( $hit_device )
{
	global $user_devices_color;
	$color = '#FFFFFF';

	if( !empty( $user_devices_color[ $hit_device ] ) )
	{
		$color ='#'.$user_devices_color[ $hit_device ];
	}

	return $color;
}


/**
 * Generate color for hit referer type
 *
 * @param string hit_referer_type
 * @return string color in hex format #FFFFFF
 */
function hit_referer_type_color( $hit_referer_type )
{
	global $referer_type_color;
	$color = '#FFFFFF';

	if( ! empty ( $referer_type_color[ $hit_referer_type ] ) )
	{
		$color ='#'.$referer_type_color[ $hit_referer_type ];
	}

	return $color;
}


/**
 * Get status code of IP range by IP address
 *
 * @param string IP address in format xxx.xxx.xxx.xxx
 * @return string Status value as it is stored in DB
 */
function hit_iprange_status( $IP_address )
{
	global $DB, $hit_iprange_statuses_cache;

	$IP_address = ip2int( $IP_address );

	if( ! is_array( $hit_iprange_statuses_cache ) )
	{ // Initialize it only first time
		$SQL = new SQL();
		$SQL->SELECT( 'aipr_status AS status, aipr_IPv4start AS start, aipr_IPv4end AS end' );
		$SQL->FROM( 'T_antispam__iprange' );
		$hit_iprange_statuses_cache = $DB->get_results( $SQL->get() );
	}

	// Use this empty value for IPs without detected ranges in DB
	$ip_range_status = '';

	// Find status in the cache
	foreach( $hit_iprange_statuses_cache as $hit_iprange_status )
	{
		if( $IP_address >= $hit_iprange_status->start &&
		    $IP_address <= $hit_iprange_status->end )
		{ // IP is detected in this range
			$ip_range_status = $hit_iprange_status->status;
			break;
		}
	}

	return $ip_range_status;
}


/**
 * Get status title of IP range by IP address
 *
 * @param string IP address in format xxx.xxx.xxx.xxx
 * @return string Status title
 */
function hit_iprange_status_title( $IP_address )
{
	global $current_User, $admin_url;

	// Get status code of IP range by IP address
	$ip_range_status = hit_iprange_status( $IP_address );

	if( $ip_range_status === '' )
	{ // No IP range for this IP address
		if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
		{ // Display a link to create new one if user has an access
			return '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;action=iprange_new&amp;ip='.$IP_address.'">'.T_('Create').'</a>';
		}
		else
		{ // No access to create new IP range
			return '';
		}
	}

	if( $current_User->check_perm( 'spamblacklist', 'view' ) )
	{ // Current user has access to view IP ranges
		global $blog;
		$blog_param = empty( $blog ) ? '' : '&amp;blog=1';
		return '<a href="'.$admin_url.'?ctrl=antispam&amp;tab=stats&amp;tab3=ipranges&amp;ip_address='.$IP_address.$blog_param.'">'.aipr_status_title( $ip_range_status ).'</a>';
	}
	else
	{ // No view access, Display only the status
		return aipr_status_title( $ip_range_status );
	}
}


/**
 * Get status color of IP range by IP address
 *
 * @param string IP address in format xxx.xxx.xxx.xxx
 * @return string Status color
 */
function hit_iprange_status_color( $IP_address )
{
	// Get status code of IP range by IP address
	$ip_range_status = hit_iprange_status( $IP_address );

	if( $ip_range_status === '' )
	{ // No IP range for this IP address
		return '';
	}

	return aipr_status_color( $ip_range_status );
}


/**
 * Get style for hit method cell
 *
 * @param string Hit request method
 * @return string Method style
 */
function hit_method_style( $hit_method )
{
	global $hit_method_color;

	// Purple color for non traditional methods:
	$color = '551A8B';

	if( isset( $hit_method_color[ $hit_method ] ) )
	{	// Get background color from config array:
		$color = $hit_method_color[ $hit_method ];
	}

	$style = 'color: #'.$color;

	return $style;
}


/**
 * Get hits data for chart and table for Analytics: Global hits - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array Hits data
 */
function get_hits_results_global( $mode = 'live' )
{
	global $DB, $blog;

	// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
	// Bug report: http://lists.mysql.com/bugs/36
	// Solution : CAST to string
	// TODO: I've also limited this to hit_agent_type "browser" here, according to the change for "referers" (Rev 1.6)
	//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
	$SQL = new SQL( 'Get global hits summary (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_agent_type, hit_type,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated/compared data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_agent_type AS hit_agent_type, hagg_type AS hit_type,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE( 'hagg_coll_ID = '.$DB->quote( $blog ) );
		}
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );
	}
	$SQL->GROUP_BY( 'year, month, day, hit_agent_type, hit_type' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, hit_agent_type, hit_type' );

	$hits = $DB->get_results( $SQL, ARRAY_A );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$hits = fill_empty_hit_days( $hits, $hits_start_date, $hits_end_date );

	return $hits;
}


/**
 * Get hits data for chart and table for Analytics: Hits from web browsers - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array 0 - hits data, 1 - sessions data
 */
function get_hits_results_browser( $mode = 'live' )
{
	global $DB, $blog;

	// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
	// Bug report: http://lists.mysql.com/bugs/36
	// Solution : CAST to string
	// waltercruz >> MySQL sorts ENUM columns according to the order in which the enumeration
	// members were listed in the column specification, not the lexical order. Solution: CAST to string using using CONCAT
	// or CAST (but CAST only works from MySQL 4.0.2)
	// References:
	// http://dev.mysql.com/doc/refman/5.0/en/enum.html
	// http://dev.mysql.com/doc/refman/4.1/en/cast-functions.html
	// TODO: I've also limited this to agent_type "browser" here, according to the change for "referers" (Rev 1.6)
	//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
	$SQL = new SQL( 'Get hits summary from web browsers (mode: '.$mode.')' );
	$sessions_SQL = new SQL( 'Get sessions summary from web browsers (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_referer_type AS referer_type, hit_type,
			GROUP_CONCAT( DISTINCT hit_sess_ID SEPARATOR "," ) AS sessions,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		$SQL->WHERE( 'hit_agent_type = "browser"' );

		$sessions_SQL->SELECT( 'SQL_NO_CACHE DATE( hit_datetime ) AS hit_date, COUNT( DISTINCT hit_sess_ID )' );
		$sessions_SQL->FROM( 'T_hitlog' );
		$sessions_SQL->WHERE( 'hit_agent_type = "browser"' );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
			$sessions_SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_referer_type AS referer_type, hagg_type AS hit_type,
			"" AS sessions,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		$SQL->WHERE( 'hagg_agent_type = "browser"' );
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );

		$sessions_SQL->SELECT( 'hags_date AS hit_date, hags_count_browser' );
		$sessions_SQL->FROM( 'T_hits__aggregate_sessions' );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
			$sessions_SQL->WHERE( 'hags_coll_ID = '.$DB->quote( $blog ) );
		}
		else
		{	// Get ALL aggregated sessions:
			$sessions_SQL->WHERE( 'hags_coll_ID = 0' );
		}
		// Filter by date:
		$sessions_SQL->WHERE_and( 'hags_date >= '.$DB->quote( $hits_start_date ) );
		$sessions_SQL->WHERE_and( 'hags_date <= '.$DB->quote( $hits_end_date ) );
	}
	$SQL->GROUP_BY( 'year, month, day, referer_type, hit_type' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, referer_type, hit_type' );
	$sessions_SQL->GROUP_BY( 'hit_date' );
	$sessions_SQL->ORDER_BY( 'hit_date DESC' );

	$res_hits = $DB->get_results( $SQL, ARRAY_A );
	$sessions = $DB->get_assoc( $sessions_SQL );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	return array( $res_hits, $sessions );
}


/**
 * Get hits data for chart and table for Analytics: Hits from search and referers - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array
 */
function get_hits_results_search_referers( $mode = 'live' )
{
	global $DB, $blog;

	// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
	// Bug report: http://lists.mysql.com/bugs/36
	// Solution : CAST to string
	// waltercruz >> MySQL sorts ENUM columns according to the order in which the enumeration
	// members were listed in the column specification, not the lexical order. Solution: CAST to string using using CONCAT
	// or CAST (but CAST only works from MySQL 4.0.2)
	// References:
	// http://dev.mysql.com/doc/refman/5.0/en/enum.html
	// http://dev.mysql.com/doc/refman/4.1/en/cast-functions.html
	// TODO: I've also limited this to agent_type "browser" here, according to the change for "referers" (Rev 1.6)
	//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
	$SQL = new SQL( 'Get hits summary from web browsers (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_referer_type AS referer_type, hit_type,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		$SQL->WHERE( 'hit_agent_type = "browser"' );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_referer_type AS referer_type, hagg_type AS hit_type,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		$SQL->WHERE( 'hagg_agent_type = "browser"' );
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
		}
	}
	$SQL->GROUP_BY( 'year, month, day, referer_type, hit_type' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, referer_type, hit_type' );

	$res_hits = $DB->get_results( $SQL, ARRAY_A );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	return $res_hits;
}


/**
 * Get hits data for chart and table for Analytics: Hits from API - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array 0 - hits data, 1 - sessions data
 */
function get_hits_results_api( $mode = 'live' )
{
	global $DB, $blog;

	$SQL = new SQL( 'Get API hits summary (mode: '.$mode.')' );
	$sessions_SQL = new SQL( 'Get API sessions summary (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_referer_type AS referer_type,
			GROUP_CONCAT( DISTINCT hit_sess_ID SEPARATOR "," ) AS sessions,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		$SQL->WHERE( 'hit_type = "api"' );

		$sessions_SQL->SELECT( 'SQL_NO_CACHE DATE( hit_datetime ) AS hit_date, COUNT( DISTINCT hit_sess_ID )' );
		$sessions_SQL->FROM( 'T_hitlog' );
		$sessions_SQL->WHERE( 'hit_type = "api"' );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
			$sessions_SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_referer_type AS referer_type,
			"" AS sessions,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		$SQL->WHERE( 'hagg_type = "api"' );
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );

		$sessions_SQL->SELECT( 'hags_date AS hit_date, hags_count_api' );
		$sessions_SQL->FROM( 'T_hits__aggregate_sessions' );

		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
			$sessions_SQL->WHERE( 'hags_coll_ID = '.$DB->quote( $blog ) );
		}
		else
		{	// Get ALL aggregated sessions:
			$sessions_SQL->WHERE( 'hags_coll_ID = 0' );
		}
		// Filter by date:
		$sessions_SQL->WHERE_and( 'hags_date >= '.$DB->quote( $hits_start_date ) );
		$sessions_SQL->WHERE_and( 'hags_date <= '.$DB->quote( $hits_end_date ) );
	}
	$SQL->GROUP_BY( 'year, month, day, referer_type' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, referer_type' );
	$sessions_SQL->GROUP_BY( 'hit_date' );
	$sessions_SQL->ORDER_BY( 'hit_date DESC' );

	$res_hits = $DB->get_results( $SQL, ARRAY_A );
	$sessions = $DB->get_assoc( $sessions_SQL );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	return array( $res_hits, $sessions );
}


/**
 * Get hits data for chart and table for Analytics: Hits from indexing robots / spiders / crawlers - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array Hits data
 */
function get_hits_results_robot( $mode = 'live' )
{
	global $DB, $blog;

	$SQL = new SQL( 'Get robot hits summary (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		$SQL->WHERE( 'hit_agent_type = "robot"' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		$SQL->WHERE( 'hagg_agent_type = "robot"' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
		}
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );
	}
	$SQL->GROUP_BY( 'year, month, day' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC' );

	$res_hits = $DB->get_results( $SQL, ARRAY_A );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	return $res_hits;
}


/**
 * Get hits data for chart and table for Analytics: Hits from RSS/Atom feed readers - Summary
 *
 * @param string Mode: 'live', 'aggregate', 'compare'
 * @return array Hits data
 */
function get_hits_results_rss( $mode = 'live' )
{
	global $DB, $blog;

	$SQL = new SQL( 'Get RSS/Atom feed readers hits summary (mode: '.$mode.')' );
	if( $mode == 'live' )
	{	// Get the live data:
		$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits,
			EXTRACT( YEAR FROM hit_datetime ) AS year,
			EXTRACT( MONTH FROM hit_datetime ) AS month,
			EXTRACT( DAY FROM hit_datetime ) AS day' );
		$SQL->FROM( 'T_hitlog' );
		$SQL->WHERE( 'hit_type = "rss"' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		}

		$hits_start_date = NULL;
		$hits_end_date = date( 'Y-m-d' );
	}
	else
	{	// Get the aggregated data:
		$SQL->SELECT( 'SUM( hagg_count ) AS hits,
			EXTRACT( YEAR FROM hagg_date ) AS year,
			EXTRACT( MONTH FROM hagg_date ) AS month,
			EXTRACT( DAY FROM hagg_date ) AS day' );
		$SQL->FROM( 'T_hits__aggregate' );
		$SQL->WHERE( 'hagg_type = "rss"' );
		if( $blog > 0 )
		{	// Filter by collection:
			$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
		}
		// Filter by date:
		list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates( $mode );
		$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
		$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );
	}
	$SQL->GROUP_BY( 'year, month, day' );
	$SQL->ORDER_BY( 'year DESC, month DESC, day DESC' );

	$res_hits = $DB->get_results( $SQL, ARRAY_A );

	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	return $res_hits;
}


/**
 * Display diagram for hits data
 *
 * @param string Diagram type: 'global', 'browser', 'search_referers', 'api', 'robot', 'rss'
 * @param array Diagram columns
 * @param array Hits data
 * @param string Canvas ID for JavaScript initialization
 */
function display_hits_diagram( $type, $diagram_columns, $res_hits, $canvas_id = 'canvasbarschart' )
{
	global $blog, $admin_url;

	$last_date = 0;

	// Initialize the data to open an url by click on bar item:
	$chart = array( 'link_data' => array( 'params' => array() ) );
	switch( $type )
	{
		case 'global':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&hit_type=$param1$&agent_type=$param2$';
			break;

		case 'browser':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=browser&referer_type=$param1$&hit_type=$param2$';
			$sessions = $res_hits[1];
			$res_hits = $res_hits[0];
			break;

		case 'search_referers':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=browser&referer_type=$param1$';
			break;

		case 'api':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&referer_type=$param1$&hit_type=api';
			$sessions = $res_hits[1];
			$res_hits = $res_hits[0];
			break;

		case 'robot':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=$param1$';
			break;

		case 'rss':
			$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&hit_type=$param1$';
			break;
	}

	// This defines what hits will go where
	// This maps a 'hit_type' (from any agent type that is 'browser' or 'robot') to a column
	// OR it can also map 'hit_type'_'hit_agent_type' (concatenated with _ ) to a column
	// OR the 'unknown' column will get ANY hits from an unknown user agent (this will go to the "other" column)
	$col_mapping = array();
	$col_num = 1;
	$chart['chart_data'][ 0 ] = array();
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		$chart['chart_data'][ $col_num ] = array();
		if( $diagram_column_data['link_data'] !== false )
		{
			$chart['link_data']['params'][] = $diagram_column_data['link_data'];
		}
		$col_mapping[ $diagram_column_key ] = $col_num++;
	}

	$chart['dates'] = array();

	if( isset( $diagram_columns['session'] ) )
	{	// Draw last data as line only for Sessions:
		$chart['draw_last_line'] = true;
	}

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{	// We just hit a new day, let's display the previous one:
			$last_date = $this_date; // that'll be the next one
			$count ++;
			array_unshift( $chart['chart_data'][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			$col_num = 1;
			foreach( $diagram_columns as $diagram_column_data )
			{
				array_unshift( $chart['chart_data'][ $col_num++ ], 0 );
			}
			array_unshift( $chart['dates'], $last_date );
		}

		switch( $type )
		{
			case 'global':
				if( $row_stats['hit_agent_type'] == 'unknown' )
				{	// only those hits are calculated which hit_agent_type = unknown
					$hit_key = $row_stats['hit_agent_type'];
				}
				elseif( ! empty ( $col_mapping[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] ) )
				{	// those hits are calculated here if hit_type = standard and hit_agent_type = browser, robot
					$hit_key = $row_stats['hit_type'].'_'.$row_stats['hit_agent_type'];
				}
				elseif( ! empty ( $col_mapping[$row_stats['hit_type']] ) )
				{	// those hits are calculated here which did not match either of the above rules
					$hit_key = $row_stats['hit_type'];
				}
				else
				{
					$hit_key = NULL;
				}
				break;

			case 'browser':
				$hit_key = in_array( $row_stats['hit_type'], array( 'ajax', 'admin' ) ) ? $row_stats['hit_type'] : $row_stats['referer_type'];
				break;

			case 'search_referers':
			case 'api':
				$hit_key = $row_stats['referer_type'];
				break;

			case 'robot':
				$hit_key = 'robot';
				break;

			case 'rss':
				$hit_key = 'rss';
				break;
		}

		if( isset( $col_mapping[ $hit_key ] ) )
		{
			$chart['chart_data'][ $col_mapping[ $hit_key ] ][0] += $row_stats['hits'];
		}

		if( isset( $col_mapping['session'] ) )
		{	// Store a count of sessions:
			$chart['chart_data'][ $col_mapping['session'] ][0] = ( isset( $sessions[ date( 'Y-m-d', $this_date ) ] ) ? $sessions[ date( 'Y-m-d', $this_date ) ] : 0 );
		}
	}

	// Initialize titles and colors for diagram columns:
	array_unshift( $chart['chart_data'][ 0 ], '' );
	$col_num = 1;
	$chart['series_color'] = array();
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		$chart['series_color'][ $col_num ] = $diagram_column_data['color'];
		array_unshift( $chart['chart_data'][ $col_num++ ], $diagram_column_data['title'] );
	}

	$chart['canvas_bg'] = array( 'width' => '100%', 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart, NULL, $canvas_id );
	echo '</div>';
}
?>