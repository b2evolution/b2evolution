<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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

	if( $sess_ID == NULL )
	{
		$session_link = '%stat_session_hits( #sess_ID#, #sess_ID# )%';
	}
	else
	{
		$session_link = '<a href="?ctrl=stats&amp;tab='.$tab.'&amp;blog='.$blog.'" title="'.T_( 'Show all sessions' ).'">$sess_ID$</a>';
	}

	$Results->cols[] = array(
			'th' => T_('Session'),
			'order' => 'hit_sess_ID, hit_ID',
			'td_class' => 'right compact_data',
			'td' => $session_link,
		);

	$Results->cols[] = array(
			'th' => T_('User'),
			'order' => 'user_login, hit_ID',
			'td_class' => 'shrinkwrap compact_data',
			'td' => '%stat_session_login( #user_login# )%',
		);

	$Results->cols[] = array(
			'th' => T_('Date Time'),
			'order' => 'hit_ID',
			'default_dir' => 'D',
			'td_class' => 'timestamp compact_data',
			'td' => '%mysql2localedatetime_spans( #hit_datetime#, "M-d" )%',
		);

	$Results->cols[] = array(
			'th' => T_('Agent'),
			'order' => 'hit_agent_type, hit_ID',
			'td_class' => 'shrinkwrap compact_data',
			'td' => '$hit_agent_type$',
			'extra' => array ( 'style' => 'background-color: %hit_agent_type_color( "#hit_agent_type#" )%;',
			'format_to_output'	=> false)
		);

	$Results->cols[] = array(
			'th' => T_('Device'),
			'order' => 'sess_device, hit_ID',
			'td_class' => 'shrinkwrap compact_data',
			'td' => '$sess_device$',
			'extra' => array ( 'style' => 'background-color: %hit_device_color( "#sess_device#" )%;', 'format_to_output' => false )
		);

	if( !in_array( 'referer', $hide_columns ) )
	{ // Referer Type & Domain
		$Results->cols[] = array(
				'th_group' => T_('Referer'),
				'th' => T_('Type'),
				'order' => 'hit_referer_type, hit_ID',
				'td_class' => 'shrinkwrap compact_data',
				'td' => '$hit_referer_type$',
				'extra' => array ( 'style' => 'background-color: %hit_referer_type_color( "#hit_referer_type#" )%;',
					'format_to_output' => false )
			);

		$Results->cols[] = array(
				'th_group' => T_('Referer'),
				'th' => T_('Domain'),
				'order' => 'dom_name, hit_ID',
				'td_class' => 'nowrap compact_data',
				'td' => '<a href="$hit_referer$">$dom_name$</a>',
			);
	}

	// Keywords:
	$Results->cols[] = array(
			'th' => T_('Search keywords'),
			'order' => 'hit_keyphrase, hit_ID',
			'td' => '%stats_search_keywords( #hit_keyphrase#, 45 )%',
			'td_class' => 'compact_data'
		);

	// Serp Rank:
	$Results->cols[] = array(
			'th' => T_('SR'),
			'th_title' => T_('Serp rank'),
			'order' => 'hit_serprank, hit_ID',
			'td_class' => 'center compact_data',
			'td' => '$hit_serprank$',
		);

	$Results->cols[] = array(
			'th' => T_('Goal'),
			'order' => 'goal_name, hit_ID',
			'default_dir' => 'D',
			'td' => '$goal_name$',
			'td_class' => 'compact_data'
		);

	$Results->cols[] = array(
			'th' => T_('Blog'),
			'order' => 'hit_blog_ID, hit_ID',
			'td' => '$blog_shortname$',
			'td_class' => 'compact_data'
		);
	$Results->cols[] = array(
			'th' => T_('Hit type'),
			'order' => 'hit_type, hit_ID',
			'td_class' => 'shrinkwrap compact_data',
			'td' => '$hit_type$',
			'extra' => array (	'style'				=> 'background-color: %hit_type_color( "#hit_type#" )%',
								'format_to_output'	=> false)
		);
	// Requested URI (linked to blog's baseurlroot+URI):
	$Results->cols[] = array(
			'th' => T_('Requested URI'),
			'order' => 'hit_uri, hit_ID',
			'td' => '%stats_format_req_URI( #hit_blog_ID#, #hit_uri#, 40, #hit_disp#, #hit_ctrl#, #hit_action# )%',
			'td_class' => 'compact_data'
		);
	$Results->cols[] = array(
			'th' => T_('Resp Code'),
			'order' => 'hit_response_code, hit_ID',
			'td' => '$hit_response_code$',
			'td_class' => '%hit_response_code_class( #hit_response_code# )% shrinkwrap compact_data'
		);

	$Results->cols[] = array(
			'th' => T_('Remote IP'),
			'order' => 'hit_remote_addr, hit_ID',
			'td' => '%disp_clickable_log_IP( #hit_remote_addr# )%',
			'td_class' => 'compact_data'
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

	$Form->checkbox_basic_input( 'exclude', get_param('exclude'), T_('Exclude').' &mdash; ' );
	$Form->text_input( 'sess_ID', get_param('sess_ID'), 15, T_('Session ID'), '', array( 'maxlength'=>20 ) );
	$Form->text_input( 'remote_IP', get_param('remote_IP'), 15, T_('Remote IP'), '', array( 'maxlength'=>23 ) );

	$Form->date_input( 'datestartinput', $datestart, T_('From date') );
	$Form->date_input( 'datestopinput', $datestop, T_('To date') );
	
	if( !isset( $preset_agent_type ) )
	{
		$Form->select_input_array( 'agent_type', get_param('agent_type'), $agent_type_array, 'Agent type', '', array('force_keys_as_values' => true, 'background_color' => $agent_type_color) );
	}

	$devices_array = array_keys( $user_devices );
	$devices_array = array_merge(
			array( '0' => T_('All') ),
			array_combine( $devices_array, $devices_array ),
			array( 'other' => T_('Other') )
		);
	$Form->select_input_array( 'device', get_param('device'), $devices_array , 'Device', '', array('force_keys_as_values' => true, 'background_color' => $user_devices_color) );

	if( !isset( $preset_referer_type ) )
	{
		$Form->select_input_array( 'referer_type', get_param('referer_type'), $referer_type_array, 'Referer type', '', array('force_keys_as_values' => true, 'background_color' => $referer_type_color) );
	}
	$Form->select_input_array( 'hit_type', get_param('hit_type'), $hit_type_array, 'Hit type', '', array('force_keys_as_values' => true, 'background_color' => $hit_type_color) );

	$Form->text_input( 'reqURI', get_param('reqURI'), 15, T_('Requested URI'), '', array( 'maxlength'=>250 ) );
}


/**
 * Helper function for "Requested URI" column
 * @param integer Blog ID
 * @param string Requested URI
 * @param integer Output string lenght
 * @param string Display
 * @param string Controller
 * @return string
 */
function stats_format_req_URI( $hit_blog_ID, $hit_uri, $max_len = 40, $hit_disp = NULL, $hit_ctrl = NULL, $hit_action = NULL)
{
	if( !empty( $hit_blog_ID ) )
	{
		$BlogCache = & get_BlogCache();
		$tmp_Blog = & $BlogCache->get_by_ID( $hit_blog_ID );
		$full_url = $tmp_Blog->get_baseurl_root().$hit_uri;
	}
	else
	{
		$full_url = $hit_uri;
	}

	$int_search_uri = urldecode($hit_uri);
	if( ( evo_strpos( $int_search_uri , '?s=' ) !== false )
	 || ( evo_strpos( $int_search_uri , '&s=' ) !== false ) )
	{	// This is an internal search:
		preg_match( '~[?&]s=([^&#]*)~', $int_search_uri, $res );
		$hit_uri = 'Internal search : '.$res[1];
	}
	elseif( evo_strlen($hit_uri) > $max_len )
	{
		$hit_uri = '...'.evo_substr( $hit_uri, -$max_len );
	}

	if( $hit_disp != NULL || $hit_ctrl != NULL || $hit_action != NULL)
	{
		$hit_uri = '';

		if($hit_disp != NULL)
		{
			$hit_uri = '[disp=<a href="'.$full_url.'">'.$hit_disp.'</a>]';
		}
		if($hit_ctrl != NULL)
		{
			$hit_uri =$hit_uri.' [ctrl=<a href="'.$full_url.'">'.$hit_ctrl.'</a>]';
		}
		if($hit_action != NULL)
		{
			$hit_uri =$hit_uri.' [action=<a href="'.$full_url.'">'.$hit_action.'</a>]';
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
	if( empty($login) )
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
function stat_session_hits( $sess_ID,  $link_text )
{
	global $blog;
	return '<strong><a href="?ctrl=stats&tab='.get_param( 'tab' ).'&colselect_submit=Filter+list&sess_ID='.$sess_ID.'&remote_IP=&blog='.$blog.'">'.$link_text.'</a></strong>';
}


/**
 * Display clickable log IP address
 *
 * @param string remote adress IP
 */
function disp_clickable_log_IP( $hit_remote_addr )
{
	global $current_User, $blog;
	static $perm = NULL;

	if( empty( $perm ) )
	{
		$perm = $current_User->check_perm( 'stats', 'view' );
	}

	if( $perm == true )
	{
		return '<a href="?ctrl=stats&tab='.get_param( 'tab' ).'&colselect_submit=Filter+list&sess_ID=&remote_IP='.$hit_remote_addr.'&blog='.$blog.'">'.$hit_remote_addr.'</a>';
	}
	else
	{
		return "$hit_remote_addr";
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
	if(!empty ($referer_type_color[$hit_referer_type]))
	{
		return '<span style="background-color: #'.$referer_type_color[$hit_referer_type].'" >'.$hit_referer_type.'</span>';
	}
	else
	{
		return "$hit_referer_type";
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
	if(!empty ($agent_type_color[$hit_agent_type]))
	{
		return '<span style="background-color: #'.$agent_type_color[$hit_agent_type].'" >'.$hit_agent_type.'</span>';
	}
	else
	{
		return "$hit_agent_type";
	}

}

/**
 * Generate html response code class
 * @param integer response code
 * @return string class
 */
function hit_response_code_class($hit_response_code)
{
	$class = '';

	if($hit_response_code >= 200 && $hit_response_code < 300)
	{
		$class =  "code_2xx";
	}
	if($hit_response_code >= 300 && $hit_response_code < 400)
	{
		$class =  "code_3xx";
	}

	if($hit_response_code == 304)
	{
		$class =  "code_304";
	}

	if ($hit_response_code >= 400)
	{
		$class =  "code_4xx";
	}


	return $class;
}



/**
 * Generate color
 * @param string hit_type
 * @return string color in hex format #FFFFFF
 */
function hit_type_color($hit_type)
{
	global $hit_type_color;
	$color = '#FFFFFF';

	if( ! empty ( $hit_type_color[$hit_type] ) )
	{
		$color ='#'.$hit_type_color[$hit_type];
	}

	return $color;
}


/**
 * Generate color
 * @param string hit_agent_type
 * @return string color in hex format #FFFFFF
 */
function hit_agent_type_color($hit_agent_type)
{
	global $agent_type_color;
	$color = '#FFFFFF';

	if( ! empty ( $agent_type_color[$hit_agent_type] ) )
	{
		$color ='#'.$agent_type_color[$hit_agent_type];
	}

	return $color;
}


/**
 * Generate color
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
 * Generate color
 * @param string hit_referer_type
 * @return string color in hex format #FFFFFF
 */
function hit_referer_type_color($hit_referer_type)
{
	global $referer_type_color;
	$color = '#FFFFFF';

	if( ! empty ( $referer_type_color[$hit_referer_type] ) )
	{
		$color ='#'.$referer_type_color[$hit_referer_type];
	}

	return $color;
}
?>