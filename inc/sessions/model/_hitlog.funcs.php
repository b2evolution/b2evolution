<?php
/**
 * This file implements functions for logging of hits and extracting stats.
 *
 * NOTE: the refererList() and stats_* functions are not fully functional ATM. I'll transform them into the Hitlog object during the next days. blueyed.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Origin:
 * This file was inspired by N C Young's Referer Script released in
 * the public domain on 07/19/2002. {@link http://ncyoung.com/entry/57}.
 * See also {@link http://ncyoung.com/demo/referer/}.
 * }}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Display hits results table
 */
function hits_results_block( $params = array() )
{
	if( ! is_logged_in() )
	{ // Only logged in users can access to this function
		return;
	}

	global $blog, $current_User;

	if( $blog == 0 )
	{
		if( ! $current_User->check_perm( 'stats', 'view' ) )
		{ // Current user has no permission to view all stats (aggregated stats)
			return;
		}
	}
	else
	{
		if( ! $current_User->check_perm( 'stats', 'list', false, $blog ) )
		{ // Current user has no permission to view the stats of the selected blog
			return;
		}
	}

	/**
	 * View funcs
	 */
	load_funcs('sessions/views/_stats_view.funcs.php');

	global $blog, $admin_url, $rsc_url;
	global $Session, $UserSettings, $DB;

	global $datestartinput, $datestart, $datestopinput, $datestop;
	global $preset_referer_type, $preset_agent_type;

	$tab = param( 'tab', 'string', 'summary', true );
	$tab3 = param( 'tab3', 'string', '', true );

	switch( $tab )
	{
		case 'other':
			$preset_results_title = T_('Direct browser hits');
			$preset_referer_type = 'direct';
			$preset_agent_type = 'browser';
			$preset_filter_all_url = '?ctrl=stats&amp;tab=referers&amp;blog='.$blog;
			$hide_columns = 'referer';
			break;

		case 'referers':
			$preset_results_title = T_('Refered browser hits');
			$preset_referer_type = 'referer';
			$preset_agent_type = 'browser';
			$preset_filter_all_url = '?ctrl=stats&amp;tab=referers&amp;blog='.$blog;
			break;

		case 'refsearches':
			if( $tab3 == 'hits' )
			{
				$preset_results_title = T_('Search hits');
				$preset_referer_type = 'search';
				$preset_agent_type = 'browser';
				$preset_filter_all_url = '?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog;
			}
			break;
	}

	if( param_date( 'datestartinput', T_('Invalid date'), false,  NULL ) !== NULL )
	{ // We have a user provided localized date:
		memorize_param( 'datestart', 'string', NULL, trim( form_date( $datestartinput ) ) );
		memorize_param( 'datestartinput', 'string', NULL, empty( $datestartinput ) ? NULL : date( locale_datefmt(), strtotime( $datestartinput ) ) );
	}
	else
	{ // We may have an automated param transmission date:
		param( 'datestart', 'string', '', true );
	}
	if( param_date( 'datestopinput', T_('Invalid date'), false, NULL ) !== NULL )
	{ // We have a user provided localized date:
		memorize_param( 'datestop', 'string', NULL, trim( form_date( $datestopinput ) ) );
		memorize_param( 'datestopinput', 'string', NULL, empty( $datestopinput ) ? NULL : date( locale_datefmt(), strtotime( $datestopinput ) ) );
	}
	else
	{ // We may have an automated param transmission date:
		param( 'datestop', 'string', '', true );
	}

	$exclude = param( 'exclude', 'integer', 0, true );
	$sess_ID = param( 'sess_ID', 'integer', NULL, true );
	$remote_IP = param( 'remote_IP', 'string', NULL, true );
	$referer_type = isset( $preset_referer_type ) ? $preset_referer_type : param( 'referer_type', 'string', NULL, true );
	$agent_type = isset( $preset_agent_type ) ? $preset_agent_type : param( 'agent_type', 'string', NULL, true );
	$device = param( 'device', 'string', NULL, true );
	$hit_type = param( 'hit_type', 'string', NULL, true );
	$reqURI = param( 'reqURI', 'string', NULL, true );
	$resp_code = param( 'resp_code', 'integer', NULL, true );

	// Create result set:

	$SQL = new SQL();
	$SQL->SELECT( 'SQL_NO_CACHE hit_ID, sess_ID, sess_device, hit_datetime, hit_type, hit_referer_type, hit_uri, hit_disp, hit_ctrl, hit_action, hit_coll_ID, hit_referer, hit_remote_addr,'
		.'user_login, hit_agent_type, blog_shortname, dom_name, goal_name, hit_keyphrase, hit_serprank, hit_response_code, hit_method, hit_agent_ID' );
	$SQL->FROM( 'T_hitlog LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID'
		.' LEFT JOIN T_sessions ON hit_sess_ID = sess_ID'
		.' LEFT JOIN T_blogs ON hit_coll_ID = blog_ID'
		.' LEFT JOIN T_users ON sess_user_ID = user_ID'
		.' LEFT JOIN T_track__goalhit ON hit_ID = ghit_hit_ID'
		.' LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID' );

	$count_SQL = new SQL();
	$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(hit_ID)' );
	$count_SQL->FROM( 'T_hitlog' );

	$operator = ( $exclude ? ' <> ' : ' = ' );

	if( ! empty( $sess_ID ) )
	{ // We want to filter on the session ID:
		$filter = 'hit_sess_ID'.$operator.$sess_ID;
		$SQL->WHERE( $filter );
		$count_SQL->WHERE( $filter );
	}
	elseif( ! empty( $remote_IP ) ) // TODO: allow combine
	{ // We want to filter on the goal name:
		$filter = 'hit_remote_addr'.$operator.$DB->quote( $remote_IP );
		$SQL->WHERE( $filter );
		$count_SQL->WHERE( $filter );
	}

	if( ! empty( $referer_type ) )
	{
		$filter = 'hit_referer_type = '.$DB->quote( $referer_type );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	if( ! empty( $agent_type ) )
	{
		$filter = 'hit_agent_type = '.$DB->quote( $agent_type );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	if( ! empty( $device ) )
	{
		if( $device == 'other' )
		{ // Unknown device
			$device = '';
		}
		$filter = 'sess_device = '.$DB->quote( $device );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
		$count_SQL->FROM_add( 'LEFT JOIN T_sessions ON hit_sess_ID = sess_ID' );
	}

	if( ! empty( $hit_type ) )
	{
		$filter = 'hit_type = '.$DB->quote( $hit_type );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	if( ! empty( $reqURI ) )
	{
		$filter = 'hit_uri LIKE '.$DB->quote( $reqURI );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	if( ! empty( $resp_code ) )
	{
		$filter = 'hit_response_code = ' .$DB->quote( $resp_code );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	if( ! empty( $datestart ) )
	{
		$SQL->WHERE_and( 'hit_datetime >= '.$DB->quote( $datestart.' 00:00:00' ) );
		$count_SQL->WHERE_and( 'hit_datetime >= '.$DB->quote( $datestart.' 00:00:00' ) );
	}
	if( ! empty( $datestop ) )
	{
		$SQL->WHERE_and( 'hit_datetime <= '.$DB->quote( $datestop.' 23:59:59' ) );
		$count_SQL->WHERE_and( 'hit_datetime <= '.$DB->quote( $datestop.' 23:59:59' ) );
	}


	if( ! empty( $blog ) )
	{
		$filter = 'hit_coll_ID = '.$DB->escape( $blog );
		$SQL->WHERE_and( $filter );
		$count_SQL->WHERE_and( $filter );
	}

	$resuts_param_prefix = 'hits_';
	if( ! empty( $preset_referer_type ) )
	{
		$resuts_param_prefix = substr( $preset_referer_type, 0, 8 ).'_'.$resuts_param_prefix;
	}

	$default_order = '--D';

	$SQL->ORDER_BY( '*, hit_ID' );

	$Results = new Results( $SQL->get(), $resuts_param_prefix, $default_order, $UserSettings->get( 'results_per_page' ), $count_SQL->get(), true, 100000 );

	// Initialize Results object
	hits_results( $Results, array( 'default_order' => $default_order ) );

	if( is_ajax_content() )
	{ // init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	// Display results:
	$Results->display();

	if( !is_ajax_content() )
	{ // Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$resuts_param_prefix.'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Displays keywords used for search leading to this page
 */
function stats_search_keywords( $keyphrase, $length = 45 )
{
	global $evo_charset;

	if( empty( $keyphrase ) )
	{
		return '<span class="note">['.T_('n.a.').']</span>';
	}

	// Save original string
	$keyphrase_orig = $keyphrase;

	$keyphrase = strmaxlen( $keyphrase, $length, '...', 'raw' );

	// Convert keyword encoding, some charsets are supported only in PHP 4.3.2 and later.
	// This fixes encoding problem for Cyrillic keywords
	// See http://forums.b2evolution.net/viewtopic.php?t=17431
	$keyphrase = htmlentities( $keyphrase, ENT_COMPAT, $evo_charset );

	return '<span title="'.format_to_output( $keyphrase_orig, 'htmlattr' ).'">'.$keyphrase.'</span>';
}


/**
 * Generate a random ip
 *
 * @return string ip
 */
function generate_random_ip()
{
	return mt_rand( 0, 255 ).'.'.mt_rand( 0, 255 ).'.'.mt_rand( 0, 255 ).'.'.mt_rand( 0, 255 );
}


/**
 * Generate fake hit statistics
 *
 * @param integer the number of days to generate statistics
 * @param integer min interval between hits in seconds
 * @param integer max interval between hits in seconds
 * @param boolean TRUE to display the process dots during generating of the hits
 * @return integer count of inserted hits
 */
function generate_hit_stat( $days, $min_interval, $max_interval, $display_process = false )
{
	global $baseurlroot, $admin_url, $user_agents, $DB, $htsrv_url, $is_api_request;

	load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
	load_class( 'sessions/model/_hit.class.php', 'Hit' );

	$links = array();

	$BlogCache = & get_BlogCache();

	$blogs_id = $BlogCache->load_public();

	foreach( $blogs_id as $blog_id )
	{	// Handle all public blogs:
		$listBlog = & $BlogCache->get_by_ID( $blog_id );
		if( empty( $listBlog ) )
		{
			continue;
		}

		$ItemList = new ItemListLight( $listBlog );
		$filters = array();

		# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
		# Example: $linkblog_cat = '4,6,7';
		$linkblog_cat = '';

		# This is the array if categories to restrict the linkblog to (non recursive)
		# Example: $linkblog_catsel = array( 4, 6, 7 );
		$linkblog_catsel = array(); // $cat_array;
		// Compile cat array stuff:
		$linkblog_cat_array = array();
		$linkblog_cat_modifier = '';

		compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */ $linkblog_cat_modifier, $listBlog->ID );

		$filters['cat_array'] = $linkblog_cat_array;
		$filters['cat_modifier'] = $linkblog_cat_modifier;


		$ItemList->set_default_filters( $filters );

		// Get the items list of current blog
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			continue;
		}

		while( $Item = & $ItemList->get_category_group() )
		{
			// Open new cat:
			$Chapter = & $Item->get_main_Chapter();
			while( $Item = & $ItemList->get_item() )
			{
				$links[] = array(
					'link' => '/'.$listBlog->siteurl.'/'.$Chapter->get_url_path().$Item->urltitle, // trim( $Chapter->get_permanent_url( NULL ,' ' ) ).
					'blog_id' => $blog_id
				);
			}
		}

		// add search links for all blogs
		$links[] = array(
			'link' => url_add_param( '/'.$listBlog->siteurl, 's=$keywords$&disp=search&submit=Search', '&' ),
			'blog_id' => $blog_id
		);

		$links[] = array(
			'link' => url_add_param( '/'.$listBlog->siteurl, 'disp=users', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'users'
		);

		$links[] = array(
			'link' => url_add_param( '/'.$listBlog->siteurl, 'disp=user&user_ID=1', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'users'
		);

		$links[] = array(
			'link' => url_add_param( '/'.$listBlog->siteurl, 'disp=threads', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'threads'
		);

		$links[] = array(
			'link' => url_add_param( '/'.$listBlog->siteurl, 'disp=profile', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'profile'
		);

		$links[] = array(
			'link' => $htsrv_url.'anon_async.php',
			'blog_id' => $blog_id
		);

		$links[] = array(
			'link' => '/api/v1/collections/'.$listBlog->urlname.'/posts',
			'blog_id' => $blog_id
		);

		$links[] = array(
			'link' => '/api/v1/collections/'.$listBlog->urlname.'/search/post',
			'blog_id' => $blog_id
		);

		$links[] = array(
			'link' => '/xmlsrv/xmlrpc.php?blog='.$listBlog->ID,
			'blog_id' => $blog_id
		);
	}

	$links[] = array(
			'link' => '/api/v1/collections',
		);

	$links[] = array(
			'link' => '/xmlsrv/xmlrpc.php'
		);

	$referes = array('http://www.fake-referer1.com',
		'http://www.fake-referer2.com',
		'http://www.fake-referer3.com',
		'http://www.fake-referer4.com',
		'http://www.fake-referer5.com',
		'http://www.fake-referer6.com',
		'http://www.fake-referer7.com',
		'http://www.fake-referer8.com',
		'http://www.fake-referer9.com',
		'http://www.mail.google.com/fake/referer',
		'http://www.webmail.aol.com/fake/referer',
		'http://www.mail.yahoo.com/fake/referer',
		'http://bloglines.com/fake/referer',
		'http://www.fake-refer-online-casino1.com',
		'http://www.fake-refer-online-casino2.com',
		'http://www.fake-refer-online-casino3.com',
		'http://www.google.com/url?sa=t&rct=j&q=$keywords$&source=web&cd=4',
		'http://www.bing.com/search?q=$keywords$&src=IE-SearchBox&FORM=IE8SRC'
	);

	$devices = array(
			'iphone',
			'ipad',
			'andrtab',
			'android',
			'berrytab',
			'blkberry',
			'winphone',
			'wince',
			'palm',
			'gendvice'
		);

	$request_methods = array( 'GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'unknown' );
	$request_methods_count = count( $request_methods ) - 1;

	$robots = array();
	foreach( $user_agents as $lUserAgent )
	{
		if( $lUserAgent[0] == 'robot' )
		{
			$robots[] = $lUserAgent[1];
		}
	}

	$robots_count = count( $robots ) - 1;

	$ref_count = count( $referes ) - 1;

	$admin_link = array(
			'link' => $admin_url,
			'blog_id' => NULL
		);

	$links_count = count( $links );

	if( empty( $links_count ) )
	{
		global $Messages;
		$Messages->add( 'Cannot generate statistics without collection links.' );
		return;
	}

	// generate users id array

	$users_array = $DB->get_results('
					SELECT user_ID
					  FROM T_users
					  WHERE user_status = "activated" OR user_status= "autoactivated"
					  LIMIT 10'
					, ARRAY_A );

	$users_count = count( $users_array );
	$devices_count = count( $devices );

	if( empty( $users_count ) )
	{
		$Messages->add( 'Cannot generate statistics without valid users.' );
		return;
	}

	// Calculate the period of testing
	$cur_time = time();
	$past_time = mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ) - $days, date( 'Y' ) );

	$insert_data = '';
	$insert_data_count = 0;

	// create session array for testing
	$sessions = array();
	mt_srand( crc32( microtime() ) );
	for( $i = 0; $i <= $users_count - 1; $i++ )
	{
		$sessions[] = array(
				'sess_ID'          => -1,
				'sess_key'         => generate_random_key( 32 ),
				'sess_start_ts'    => 0,
				'sess_lastseen_ts' => 0,
				'sess_ipaddress'   => generate_random_ip(),
				'sess_user_ID'     => $users_array[$i]['user_ID'],
				'sess_device'      => $devices[ mt_rand( 0, $devices_count - 1 ) ],
				'pervios_link'     => '',
				'robot'            => ''
			);
	}

	// main cycle of generation
	//mt_srand(crc32(microtime()));
	for( $time_shift = $past_time; $cur_time > $time_shift; $time_shift += mt_rand( $min_interval, $max_interval ) )
	{
		//mt_srand(crc32(microtime()));
		$insert_data_count = $insert_data_count + 1;

		$rand_i = mt_rand( 0, $users_count - 1 );
		$rand_link = mt_rand( 0, $links_count - 1 );
		$cur_session = $sessions[$rand_i];
		$rand_request_method = $request_methods[ mt_rand( 0, $request_methods_count - 1 ) ];


		if( strstr( $links[$rand_link]['link'], '$keywords$' ) )
		{ // check if the current search link is selected randomly.
			// If yes, generate search link and add it to DB
			//mt_srand(crc32(microtime()+ $time_shift));
			$keywords = 'fake search '.mt_rand( 0, 9 );
			$links[$rand_link]['link'] = str_replace( '$keywords$', urlencode( $keywords ), $links[$rand_link]['link'] );
			if( strstr( $links[$rand_link]['link'], 's=' ) )
			{
				$links[$rand_link]['s'] = $keywords;
			}
		}

		if( $cur_session['sess_ID'] == -1 )
		{ // This session needs initialization:
			$cur_session['sess_start_ts'] = $time_shift - 1;
			$cur_session['sess_lastseen_ts'] = $time_shift;

			$DB->query( 'INSERT INTO T_sessions ( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_user_ID, sess_device )
					VALUES (
						'.$DB->quote( $cur_session['sess_key'] ).',
						'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_start_ts'] ) ).',
						'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_lastseen_ts'] ) ).',
						'.$DB->quote( $cur_session['sess_ipaddress'] ).',
						'.$DB->quote( $cur_session['sess_user_ID'] ).',
						'.$DB->quote( $cur_session['sess_device'] ).'
					)' );

			$cur_session['sess_ID'] = $DB->insert_id;
			$sessions[$rand_i] = $cur_session;

			// Check if current url is api request:
			$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

			$Test_hit = new Hit( '', $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $links[$rand_link] );
			$Test_hit->method = $rand_request_method;
			$Test_hit->log();
		}
		else
		{
			if( ( $time_shift - $cur_session['sess_lastseen_ts'] ) > 3000 || ! empty( $cur_session['robot'] ) )
			{ // This session last updated more than 3000 sec ago. Instead of this session create a new session.
				$cur_session = array(
					'sess_ID'          => -1,
					'sess_key'         => generate_random_key( 32 ),
					'sess_start_ts'    => 0,
					'sess_lastseen_ts' => 0,
					'sess_ipaddress'   => generate_random_ip(),
					'sess_user_ID'     => $users_array[ mt_rand( 0, $users_count - 1 ) ]['user_ID'],
					'sess_device'      => $devices[ mt_rand( 0, $devices_count - 1 ) ],
					'pervios_link'     => '',
					'robot'            => ''
				);

				$cur_session['sess_start_ts'] = $time_shift - 1;
				$cur_session['sess_lastseen_ts'] = $time_shift;
				$r_num = mt_rand( 0, 100 );
				if( $r_num > 40 )
				{ // Create anonymous user and make double insert into hits.
					$cur_session['sess_user_ID'] = -1;
					$DB->query( 'INSERT INTO T_sessions ( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_device )
							VALUES (
								'.$DB->quote( $cur_session['sess_key'] ).',
								'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_start_ts'] ) ).',
								'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_lastseen_ts'] ) ).',
								'.$DB->quote( $cur_session['sess_ipaddress'] ).',
								'.$DB->quote( $cur_session['sess_device'] ).'
							)' );

					if( $r_num >= 80 )
					{ // Create robot hit
						$cur_session['robot'] = $robots[ mt_rand( 0, $robots_count ) ];
					}
				}
				else
				{
					$DB->query(	'INSERT INTO T_sessions( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_user_ID, sess_device )
							VALUES (
								'.$DB->quote( $cur_session['sess_key'] ).',
								'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_start_ts'] ) ).',
								'.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_lastseen_ts'] ) ).',
								'.$DB->quote( $cur_session['sess_ipaddress'] ).',
								'.$DB->quote( $cur_session['sess_user_ID'] ).',
								'.$DB->quote( $cur_session['sess_device'] ).'
							)' );
				}

				$cur_session['sess_ID'] = $DB->insert_id;

				if( mt_rand( 0, 100 ) > 20 )
				{
					//$ref_count
					$ref_link = $referes[ mt_rand( 0, $ref_count ) ];
					if( strstr( $ref_link, '$keywords$' ) )
					{ // check if the current search link is selected randomly.
						$keywords = 'fake search '.mt_rand( 0, 9 );
						$ref_link = str_replace( '$keywords$', urlencode( $keywords ), $ref_link );
					}
				}
				else
				{
					$ref_link = '';
				}

				if( $cur_session['sess_user_ID'] == -1 )
				{
					if( empty( $cur_session['robot'] ) )
					{
						$link = array(
							'link'    => '/htsrv/login.php',
							'blog_id' => 1
						);

						// This is NOT api request:
						$is_api_request = false;

						$Test_hit = new Hit( $ref_link, $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $link );

						$Test_hit->method = $rand_request_method;

						$Test_hit->log();

						$link = array(
							'link'    => '/htsrv/login.php?redirect_to=fake_stat',
							'blog_id' => 1
						);

						$Test_hit = new Hit( $baseurlroot, $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'] + 3, 1, $link );

						$Test_hit->method = $rand_request_method;

						$Test_hit->log();

						$cur_session['pervios_link'] = $baseurlroot.$link['link'];
					}
					else
					{
						// Check if current url is api request:
						$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

						if( mt_rand( 0, 100 ) < 50 )
						{ // robot hit
							$Test_hit = new Hit( '', $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $links[$rand_link], $cur_session['robot'] );
						}
						else
						{ // rss/atom hit
							$Test_hit = new Hit( '', $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $links[$rand_link], NULL, NULL, 1 );
						}
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
					}
				}
				else
				{
					if( mt_rand( 0, 100 ) < 10 )
					{	// Test hit to admin page:

						// This is NOT api request:
						$is_api_request = false;

						$Test_hit = new Hit( '', $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $admin_link, NULL, 1 );
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
						$cur_session['pervios_link'] = $admin_url;
					}
					else
					{
						// Check if current url is api request:
						$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

						$Test_hit = new Hit( $ref_link, $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $links[$rand_link] );
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
						$cur_session['pervios_link'] = $baseurlroot.$links[$rand_link]['link'];
					}
				}
			}
			else
			{
				// Update session
				$cur_session['sess_lastseen_ts'] = $time_shift;

				// Check if current url is api request:
				$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

				$Test_hit = new Hit( $cur_session['pervios_link'], $cur_session['sess_ipaddress'], $cur_session['sess_ID'], $cur_session['sess_lastseen_ts'], 1, $links[$rand_link] );
				$Test_hit->method = $rand_request_method;
				$Test_hit->log();

				$DB->query( 'UPDATE T_sessions
					  SET sess_lastseen_ts = '.$DB->quote( date( 'Y-m-d H:i:s', $cur_session['sess_lastseen_ts'] ) ).'
					WHERE sess_ID = '.$DB->quote( $cur_session['sess_ID'] ),
					'Update session' );

				$cur_session['pervios_link'] = $baseurlroot.$links[$rand_link]['link'];

				$sessions[$rand_i] = $cur_session;
			}
		}

		$sessions[$rand_i] = $cur_session;

		if( $display_process )
		{
			if( $insert_data_count % 100 == 0 )
			{ // Display a process of creating by one dot for 100 hits
				echo ' .';
				evo_flush();
			}
		}
	}

	// Reset this back to from test values:
	$is_api_request = false;

	return $insert_data_count;
}


/**
 * Get domain type titles
 *
 * @param boolean TRUE to escape quotes in titles
 * @return array Domain titles
 */
function stats_dom_type_titles( $escape_quotes = false )
{
	return array(
			'unknown'    => $escape_quotes ? TS_('Unknown') : T_('Unknown'),
			'normal'     => $escape_quotes ? TS_('Referer') : T_('Referer'),
			'searcheng'  => $escape_quotes ? TS_('Search referer') : T_('Search referer'),
			'aggregator' => $escape_quotes ? TS_('Aggregator referer') : T_('Aggregator referer'),
			'email'      => $escape_quotes ? TS_('Email provider') : T_('Email provider'),
		);
}


/**
 * Get domain status titles
 *
 * @param boolean TRUE to escape quotes in titles
 * @return array Domain titles
 */
function stats_dom_status_titles( $escape_quotes = false )
{
	return array(
			'trusted' => $escape_quotes ? TS_('Trusted') : T_('Trusted'),
			'unknown' => $escape_quotes ? TS_('Unknown') : T_('Unknown'),
			'suspect' => $escape_quotes ? TS_('Suspect') : T_('Suspect'),
			'blocked' => $escape_quotes ? TS_('Blocked') : T_('Blocked'),
		);
}


/**
 * Get status colors of domain
 *
 * @return array Color values
 */
function stats_dom_status_colors()
{
	return array(
			'trusted' => '00CC00',
			'unknown' => '999999',
			'suspect' => 'FFAA00',
			'blocked' => 'FF0000',
		);
}


/**
 * Get array of status icons for domains
 *
 * @return array Status icons
 */
function stats_dom_status_icons()
{
	return array(
			'trusted' => get_icon( 'bullet_green', 'imgtag', array( 'title' => aipr_status_title( 'trusted' ) ) ),
			'unknown' => get_icon( 'bullet_white', 'imgtag', array( 'title' => aipr_status_title( 'unknown' ) ) ),
			'suspect' => get_icon( 'bullet_orange', 'imgtag', array( 'title' => aipr_status_title( 'suspect' ) ) ),
			'blocked' => get_icon( 'bullet_red', 'imgtag', array( 'title' => aipr_status_title( 'blocked' ) ) )
		);
}


/**
 * Get domain type title by value
 *
 * @param string Domain type value
 * @param boolean TRUE to escape quotes in titles
 * @return string Domain type title
 */
function stats_dom_type_title( $dom_type, $escape_quotes = false )
{
	$dom_type_titles = stats_dom_type_titles( $escape_quotes );
	return isset( $dom_type_titles[ $dom_type ] ) ? $dom_type_titles[ $dom_type ] : $dom_type;
}


/**
 * Get domain status title by value
 *
 * @param string Domain status value
 * @param boolean TRUE to escape quotes in titles
 * @return string Domain status title
 */
function stats_dom_status_title( $dom_status, $escape_quotes = false )
{
	$dom_status_titles = stats_dom_status_titles( $escape_quotes );
	return isset( $dom_status_titles[ $dom_status ] ) ? $dom_status_titles[ $dom_status ] : $dom_status;
}


/**
 * Get domain status color by value
 *
 * @param string Domain status value
 * @return string Domain status color
 */
function stats_dom_status_color( $dom_status )
{
	$dom_status_colors = stats_dom_status_colors();
	return isset( $dom_status_colors[ $dom_status ] ) ? '#'.$dom_status_colors[ $dom_status ] : 'none';
}


/**
 * Get domain status icon by value
 *
 * @param string Domain status value
 * @return string Domain status icon
 */
function stats_dom_status_icon( $dom_status )
{
	$dom_status_icons = stats_dom_status_icons();
	return isset( $dom_status_icons[ $dom_status ] ) ? $dom_status_icons[ $dom_status ] : '';
}


/**
 * Get top existing Domain object by subdomain name
 *
 * @param string Subdomain name
 * @return onject Domain object
 */
function & get_Domain_by_subdomain( $subdomain_name )
{
	$DomainCache = & get_DomainCache();

	$subdomain_name = explode( '.', $subdomain_name );

	for( $i = 0; $i < count( $subdomain_name ); $i++ )
	{
		$domain_name = implode( '.', array_slice( $subdomain_name, $i ) );

		if( $Domain = & $DomainCache->get_by_name( $domain_name, false, false ) ||
		    $Domain = & $DomainCache->get_by_name( '.'.$domain_name, false, false ) )
		{	// Domain exists with name, Get it:
			return $Domain;
		}
	}

	$Domain = NULL;
	return $Domain;
}


/**
 * Get Domain object by url
 *
 * @param string URL
 * @return onject Domain object
 */
function & get_Domain_by_url( $url )
{
	// Exctract domain name from url:
	$domain_name = url_part( $url, 'host' );

	$Domain = & get_Domain_by_subdomain( $domain_name );

	return $Domain;
}


/**
 * Get user agent name by ID
 *
 * @param integer Agent ID
 * @param string Agent name or Agent ID if agent is not found
 */
function get_hit_agent_name_by_ID( $agent_ID )
{
	global $user_agents;

	if( isset( $user_agents[ $agent_ID ] ) && ! empty( $user_agents[ $agent_ID ][2] ) )
	{ // Agent is found with given ID
		return $user_agents[ $agent_ID ][2];
	}
	else
	{ // No agent, Return ID
		return $agent_ID;
	}
}


/**
 * Extract keyphrases from the hitlog
 *
 * @return mixed boolean true on success, string message if the process is already running and not allowed to run
 */
function extract_keyphrase_from_hitlogs()
{
	global $DB, $Messages;

	// Set lock name based on the database name, table name and process name
	$lock_name = $DB->dbname.'.T_track__keyphrase.extract_keyphrase';

	if( $DB->get_var( 'SELECT IS_FREE_LOCK( '.$DB->quote( $lock_name ).' )' ) === '0' )
	{ // The "exctract_keyphrase" process is already running on a different request, do not start it again
		// Do not translate.
		return 'Keyphrase extraction is already in progress in a different process. This new request would duplicate the effort so it is aborted.';
	}

	// Important: If a two or more different simultanious process will arrive to this point at the same time, only one of them will acquire the lock!
	// The other processes have to wait until the one who acquired the lock will release it. After that the other process will get it one by one.

	// Get lock with a 20 seconds timeout
	$DB->get_var( 'SELECT GET_LOCK( '.$DB->quote( $lock_name ).', 20 )' );

	// Look for unextracted keyphrases:
	$sql = 'SELECT MIN(h.hit_ID) as min, MAX(h.hit_ID) as max
				FROM T_hitlog as h
				WHERE h.hit_keyphrase IS NOT NULL
					AND h.hit_keyphrase_keyp_ID IS NULL';
	$ids = $DB->get_row( $sql, ARRAY_A, NULL, ' Get max/min hits ids of unextracted keyphrases' );

	if( ! empty ( $ids['min'] ) && ! empty ( $ids['max'] ) )
	{	// Extract keyphrases if needed:

		$sql = 'INSERT INTO T_track__keyphrase(keyp_phrase, keyp_count_refered_searches)
					SELECT h.hit_keyphrase, 1
					FROM T_hitlog as h
					WHERE
						(h.hit_ID >= '.$ids['min'].' AND h.hit_ID <= '.$ids['max'].')
						AND h.hit_keyphrase IS NOT NULL
						AND h.hit_keyphrase_keyp_ID IS NULL
						AND h.hit_referer_type = "search"
				ON DUPLICATE KEY UPDATE
				T_track__keyphrase.keyp_count_refered_searches = T_track__keyphrase.keyp_count_refered_searches + 1';
		$DB->query( $sql, ' Insert/Update external keyphrase' );

		$sql = 'INSERT INTO T_track__keyphrase(keyp_phrase, keyp_count_internal_searches)
					SELECT h.hit_keyphrase, 1
					FROM T_hitlog as h
					WHERE
						(h.hit_ID >= '.$ids['min'].' AND h.hit_ID <= '.$ids['max'].')
						AND h.hit_keyphrase IS NOT NULL
						AND h.hit_keyphrase_keyp_ID IS NULL
						AND h.hit_referer_type != "search"
				ON DUPLICATE KEY UPDATE
				T_track__keyphrase.keyp_count_internal_searches = T_track__keyphrase.keyp_count_internal_searches + 1';
		$DB->query( $sql, 'Insert/Update  internal keyphrase' );

		$sql = 'UPDATE T_hitlog as h, T_track__keyphrase as k
				SET h.hit_keyphrase_keyp_ID = k.keyp_ID
				WHERE
					h.hit_keyphrase = k.keyp_phrase
					AND ( h.hit_ID >= '.$ids['min'].' )
					AND ( h.hit_ID <= '.$ids['max'].' )
					AND ( h.hit_keyphrase_keyp_ID IS NULL )';
		$DB->query( $sql, 'Update hitlogs keyphrase id' );
	}

	$DB->get_var( 'SELECT RELEASE_LOCK( '.$DB->quote( $lock_name ).' )' );

	return true;
}


/**
 * Parse extra params of goal hit (E.g. 'item_ID=123')
 *
 * @param string Value of extra params
 * @param string
 */
function stats_goal_hit_extra_params( $ghit_params )
{
	if( preg_match( '/^item_ID=([0-9]+)$/i', $ghit_params, $matches ) )
	{ // Parse item ID
		$ItemCache = & get_ItemCache();
		if( $Item = & $ItemCache->get_by_ID( intval( $matches[1] ), false, false ) )
		{ // Display a link to view with current item title
			global $current_User;
			if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
			{ // Link to admin view
				return $Item->get_title( array( 'link_type' => 'admin_view' ) );
			}
			else
			{ // Link to permament url (it is allowed for current post type)
				return $Item->get_title();
			}
		}
	}

	return htmlspecialchars( $ghit_params );
}


/**
 * Display panel with buttons to control a view of hits summary pages:
 *     - Two buttons to toggle between type of hits summary data(Live or Aggregate)
 *     - Button to aggregate hits and sessions right now
 */
function display_hits_summary_panel()
{
	global $ReqURL, $current_User;

	$hits_summary_mode = get_hits_summary_mode();

	$current_url = preg_replace( '/(\?|&)hits_summary_mode=([^&]+|$)/', '', $ReqURL );

	echo '<div class="btn-group pull-left">';

	// Button to switch to view the live hits:
	echo '<a href="'.url_add_param( $current_url, 'hits_summary_mode=live' ).'"'
		.' class="btn btn-default'.( $hits_summary_mode == 'live' ? ' active' : '' ).'">'
		.T_('Live data')
		.'</a>';

	// Button to switch to view the aggregated hits data:
	echo '<a href="'.url_add_param( $current_url, 'hits_summary_mode=aggregate' ).'"'
		.' class="btn btn-default'.( $hits_summary_mode == 'aggregate' ? ' active' : '' ).'">'
		.T_('Aggregate data')
		.'</a>';

	echo '</div>';

	if( $hits_summary_mode == 'aggregate' )
	{	// Filter the aggregated data by date period:
		global $UserSettings;

		echo '<div class="evo_aggregate_filter pull-left">';
		$Form = new Form();
		$Form->hidden_ctrl();
		$Form->hidden( 'tab', get_param( 'tab' ) );
		$Form->hidden( 'tab3', get_param( 'tab3' ) );
		$Form->hidden( 'blog', get_param( 'blog' ) );
		$Form->hidden( 'action', 'filter_aggregated' );
		$Form->add_crumb( 'aggfilter' );

		$Form->switch_layout( 'none' );

		$Form->begin_form();

		$Form->select_input_array( 'agg_period', $UserSettings->get( 'agg_period' ), array(
				'last_30_days'   => sprintf( T_('Last %d days'), 30 ),
				'last_60_days'   => sprintf( T_('Last %d days'), 60 ),
				'current_month'  => T_( 'Current Month to date' ),
				'specific_month' => T_( 'Specific Month:' ),
			), T_('Show') );

		$months_years_params = array( 'force_keys_as_values' => true );
		if( $UserSettings->get( 'agg_period' ) != 'specific_month' )
		{
			$months_years_params['style'] = 'display:none';
		}
		$months = array();
		for( $m = 1; $m <= 12; $m++ )
		{
			$months[ $m ] = T_( date( 'F', mktime( 0, 0, 0, $m ) ) );
		}
		$agg_month = $UserSettings->get( 'agg_month' );
		$Form->select_input_array( 'agg_month', ( empty( $agg_month ) ? date( 'n' ) : $agg_month ), $months, '', NULL, $months_years_params );

		$years = array();
		for( $y = date( 'Y' ) - 20; $y <= date( 'Y' ); $y++ )
		{
			$years[ $y ] = $y;
		}
		$agg_year = $UserSettings->get( 'agg_year' );
		$Form->select_input_array( 'agg_year', ( empty( $agg_year ) ? date( 'Y' ) : $agg_year ), $years, '', NULL, $months_years_params );

		$Form->end_form( array( array( 'submit', 'submit', T_('Filter'), 'btn-info' ) ) );

		echo '<script type="text/javascript">
			jQuery( "#agg_period" ).change( function()
			{
				if( jQuery( this ).val() == "specific_month" )
				{
					jQuery( "#agg_month, #agg_year" ).show();
				}
				else
				{
					jQuery( "#agg_month, #agg_year" ).hide();
				}
			} );
			</script>';

		echo '</div>';
	}

	if( $current_User->check_perm( 'stats', 'edit' ) )
	{	// Display button to aggregate hits right now only if current user has a permission to edit hits:
		echo '<a href="'.url_add_param( $current_url, 'action=aggregate&'.url_crumb( 'aggregate' ) ).'"'
			.' class="btn btn-default pull-right">'
			.T_('Aggregate Now')
			.'</a>';
	}

	echo '<div class="clear"></div>';
}


/**
 * Get dates for filter the aggregated hits
 *
 * @return array Array with two items: 0 - start date, 1 - end date
 */
function get_filter_aggregated_hits_dates()
{
	global $DB, $UserSettings;

	switch( $UserSettings->get( 'agg_period' ) )
	{
		case 'last_60_days':
			$start_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 59 ) ); // Date of 60 days ago
			$end_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 1 ) ); // Yesterday
			break;

		case 'current_month':
			$start_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), 1 ) ); // First day of current month
			$end_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 1 ) ); // Yesterday
			break;

		case 'specific_month':
			$agg_month = $UserSettings->get( 'agg_month' );
			$agg_year = $UserSettings->get( 'agg_year' );
			if( empty( $agg_month ) )
			{
				$agg_month = date( 'm' );
			}
			if( empty( $agg_year ) )
			{
				$agg_year = date( 'Y' );
			}
			$start_date = date( 'Y-m-d', mktime( 0, 0, 0, $agg_month, 1, $agg_year ) ); // First day of the selected month
			$end_date = date( 'Y-m-d', mktime( 0, 0, 0, $agg_month + 1, 0, $agg_year ) ); // Last day of the selected month
			break;

		case 'last_30_days':
		default:
			$start_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 29 ) ); // Date of 30 days ago
			$end_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 1 ) ); // Yesterday
			break;
	}

	return array( $start_date, $end_date );
}


/**
 * Get mode of hits summary data
 *
 * @return string Mode: 'live' or 'aggregate'
 */
function get_hits_summary_mode()
{
	global $Session;

	$hits_summary_mode = $Session->get( 'hits_summary_mode' );
	if( empty( $hits_summary_mode ) )
	{	// Set mode to display the aggregated data by default:
		$hits_summary_mode = 'aggregate';
	}

	return $hits_summary_mode;
}


/**
 * Find the dates without hits and fill them with 0 to display on graph and table
 *
 * @param array Source hits data
 * @param string Start date of hits log in format 'YYYY-mm-dd'
 * @param string End date of hits log in format 'YYYY-mm-dd'
 * @return array Fixed hits data
 */
// erwin> replaced with a more generic fill_empty_days() in _misc.funcs.php
function fill_empty_hit_days( $hits_data, $start_date, $end_date )
{
	$fixed_hits_data = array();

	if( empty( $hits_data ) )
	{
		return $fixed_hits_data;
	}

	// Get additional fields which must be exist in each array item of new filled empty day below:
	$additional_fields = array_diff_key( $hits_data[0], array( 'hits' => 0, 'year' => 0, 'month' => 0, 'day' => 0 ) );

	// Check if hits data array contains start and end dates:
	$start_date_is_contained = empty( $start_date );
	$end_date_is_contained = empty( $end_date );
	if( ! $start_date_is_contained || ! $end_date_is_contained )
	{
		foreach( $hits_data as $hit )
		{
			$this_date = $hit['year'].'-'.$hit['month'].'-'.$hit['day'];
			if( $this_date == $start_date )
			{	// The start date is detected:
				$start_date_is_contained = true;
			}
			if( $this_date == $end_date )
			{	// The start date is detected:
				$end_date_is_contained = true;
			}
			if( $start_date_is_contained && $end_date_is_contained )
			{	// Stop array searching here because we have found the dates:
				break;
			}
		}
	}

	if( ! $start_date_is_contained )
	{	// Add item to array with 0 for start date if stats has no data for the date:
		array_push( $hits_data, array(
				'hits'     => 0,
				'year'     => date( 'Y', strtotime( $start_date ) ),
				'month'    => date( 'n', strtotime( $start_date ) ),
				'day'      => date( 'j', strtotime( $start_date ) ),
			) + $additional_fields );
	}
	if( ! $end_date_is_contained )
	{	// Add item to array with 0 for end date if stats has no data for the date:
		array_unshift( $hits_data, array(
				'hits'     => 0,
				'year'     => date( 'Y', strtotime( $end_date ) ),
				'month'    => date( 'n', strtotime( $end_date ) ),
				'day'      => date( 'j', strtotime( $end_date ) ),
			) + $additional_fields );
	}

	foreach( $hits_data as $hit )
	{
		$this_date = $hit['year'].'-'.$hit['month'].'-'.$hit['day'];

		if( isset( $prev_date ) && $prev_date != $this_date )
		{	// If hits are from another day:
			$prev_time = strtotime( $prev_date ) - 86400;
			$this_time = strtotime( $this_date );

			if( $prev_time != $this_time )
			{	// If previous date is not previous day(it means some day has no hits):
				$empty_days = ( $prev_time - $this_time ) / 86400;
				for( $d = 0; $d <= $empty_days; $d++ )
				{	// Add each empty day to array with 0 hits count:
					$empty_day = $prev_time - $d * 86400;
					$fixed_hits_data[] = array(
							'hits'     => 0,
							'year'     => date( 'Y', $empty_day ),
							'month'    => date( 'n', $empty_day ),
							'day'      => date( 'j', $empty_day ),
						) + $additional_fields;
				}
			}
		}

		$prev_date = $hit['year'].'-'.$hit['month'].'-'.$hit['day'];
		$fixed_hits_data[] = $hit;
	}

	return $fixed_hits_data;
}
?>