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
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
 * @todo Transform to make this a stub for {@link $Hitlist}
 *
 * Extract stats
 */
function refererList(
	$howMany = 5,
	$visitURL = '',
	$disp_blog = 0,
	$disp_uri = 0,
	$type = "'referer'",		// was: 'referer' normal refer, 'invalid', 'badchar', 'blacklist', 'rss', 'robot', 'search'
													// new: 'search', 'blacklist', 'referer', 'direct', ('spam' but spam is not logged)
	$groupby = '', 	// dom_name
	$blog_ID = '',
	$get_total_hits = false, // Get total number of hits (needed for percentages)
	$get_user_agent = false ) // Get the user agent
{
	global $DB, $res_stats, $stats_total_hits, $ReqURI;

	if( strpos( $type, "'" ) !== 0 )
	{ // no quote at position 0
		$type = "'".$type."'";
	}

	//if no visitURL, will show links to current page.
	//if url given, will show links to that page.
	//if url="global" will show links to all pages
	if (!$visitURL)
	{
		$visitURL = $ReqURI;
	}

	if( $groupby == '' )
	{ // No grouping:
		$sql = 'SELECT hit_ID, UNIX_TIMESTAMP(hit_datetime) AS hit_datetime, hit_referer, dom_name';
	}
	else
	{ // group by
		if( $groupby == 'baseDomain' )
		{ // compatibility HACK!
			$groupby = 'dom_name';
		}
		$sql = 'SELECT COUNT(*) AS totalHits, hit_referer, dom_name';
	}
	if( $disp_blog )
	{
		$sql .= ', hit_coll_ID';
	}
	if( $disp_uri )
	{
		$sql .= ', hit_uri';
	}
	if( $get_user_agent )
	{
		$sql .= ', agnt_signature';
	}

	$sql_from_where = "
			  FROM T_hitlog LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
			 WHERE hit_referer_type IN (".$type.")
			   AND hit_agent_type = 'browser'";
	if( !empty($blog_ID) )
	{
		$sql_from_where .= " AND hit_coll_ID = '".$blog_ID."'";
	}
	if ( $visitURL != 'global' )
	{
		$sql_from_where .= " AND hit_uri = '".$DB->escape($visitURL, 0, 250)."'";
	}

	$sql .= $sql_from_where;

	if( $groupby == '' )
	{ // No grouping:
		$sql .= ' ORDER BY hit_ID DESC';
	}
	else
	{ // group by
		$sql .= " GROUP BY ".$groupby." ORDER BY totalHits DESC";
	}
	$sql .= ' LIMIT '.$howMany;

	$res_stats = $DB->get_results( $sql, ARRAY_A );

	if( $get_total_hits )
	{ // we need to get total hits
		$sql = 'SELECT COUNT(*) '.$sql_from_where;
		$stats_total_hits = $DB->get_var( $sql );
	}
	else
	{ // we're not getting total hits
		$stats_total_hits = 1;		// just in case some tries a percentage anyway (avoid div by 0)
	}

}


/*
 * stats_hit_ID(-)
 */
function stats_hit_ID()
{
	global $row_stats;
	echo $row_stats['visitID'];
}

/*
 * stats_hit_remote_addr(-)
 */
function stats_hit_remote_addr()
{
	global $row_stats;
	echo $row_stats['hit_remote_addr'];
}

/*
 * stats_time(-)
 */
function stats_time( $format = '' )
{
	global $row_stats;
	if( $format == '' )
		$format = locale_datefmt().' '.locale_timefmt();
	echo date_i18n( $format, $row_stats['hit_datetime'] );
}


/*
 * stats_total_hit_count(-)
 */
function stats_total_hit_count()
{
	global $stats_total_hits;
	echo $stats_total_hits;
}


/*
 * stats_hit_count(-)
 */
function stats_hit_count( $disp = true )
{
	global $row_stats;
	if( $disp )
		echo $row_stats['totalHits'];
	else
		return $row_stats['totalHits'];
}


/*
 * stats_hit_percent(-)
 */
function stats_hit_percent(
	$decimals = 1,
	$dec_point = '.' )
{
	global $row_stats, $stats_total_hits;
	$percent = $row_stats['totalHits'] * 100 / $stats_total_hits;
	echo number_format( $percent, $decimals, $dec_point, '' ).'&nbsp;%';
}


/*
 * stats_blog_ID(-)
 */
function stats_blog_ID()
{
	global $row_stats;
	echo $row_stats['hit_coll_ID'];
}


/*
 * stats_blog_name(-)
 */
function stats_blog_name()
{
	global $row_stats;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID($row_stats['hit_coll_ID']);

	$Blog->disp('name');
}


/*
 * stats_referer(-)
 */
function stats_referer( $before='', $after='', $disp_ref = true )
{
	global $row_stats;
	$ref = trim($row_stats['hit_referer']);
	if( strlen($ref) > 0 )
	{
		echo $before;
		if( $disp_ref ) echo htmlentities( $ref );
		echo $after;
	}
}


/*
 * stats_basedomain(-)
 */
function stats_basedomain( $disp = true )
{
	global $row_stats;
	if( $disp )
		echo htmlentities( $row_stats['dom_name'] );
	else
		return $row_stats['dom_name'];
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

	$keyphrase = strmaxlen($keyphrase, $length, '...', 'raw');

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
	return mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255);
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

	load_class('items/model/_itemlistlight.class.php', 'ItemListLight');
	load_class('sessions/model/_hit.class.php', 'Hit');

	$links = array();

	$BlogCache = & get_BlogCache();

	$blogs_id = $BlogCache->load_public();

	foreach( $blogs_id as $blog_id )
	{	// Handle all public blogs:
			$listBlog = & $BlogCache->get_by_ID($blog_id);
		if (empty($listBlog))
		{
			continue;
		}

		$ItemList = new ItemListLight($listBlog);
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


		$ItemList->set_default_filters($filters);

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
				$links[] = array('link' => '/' . $listBlog->siteurl . '/' . $Chapter->get_url_path() . $Item->urltitle, // trim($Chapter->get_permanent_url(NULL ,' ')).
					'blog_id' => $blog_id);
			}
		}

		// add search links for all blogs
		$links[] = array('link' => url_add_param( '/' . $listBlog->siteurl, 's=$keywords$&disp=search&submit=Search', '&' ),
			'blog_id' => $blog_id);

		$links[] = array('link' => url_add_param( '/' . $listBlog->siteurl, 'disp=users', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'users');

		$links[] = array('link' => url_add_param( '/'.$listBlog->siteurl, 'disp=user&user_ID=1', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'users');

		$links[] = array('link' => url_add_param( '/' . $listBlog->siteurl, 'disp=threads', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'threads');

		$links[] = array('link' => url_add_param( '/' . $listBlog->siteurl, 'disp=profile', '&' ),
			'blog_id' => $blog_id,
			'disp' => 'profile');

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
		if ($lUserAgent[0] == 'robot')
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
					, 'ARRAY_A');

	$users_count = count( $users_array );
	$devices_count = count( $devices );

	if( empty( $users_count ) )
	{
		$Messages->add( 'Cannot generate statistics without valid users.' );
		return;
	}

	// Calculate the period of testing
	$cur_time = time();
	$past_time = mktime(date("H"), date("i"), date("s"), date("m"), date("d") - $days, date("Y"));

	$insert_data = '';
	$insert_data_count = 0;

	// create session array for testing
	$sessions = array();
	mt_srand(crc32(microtime()));
	for ($i = 0; $i <= $users_count - 1; $i++)
	{
		$sessions[] = array(
				'sess_ID'          => -1,
				'sess_key'         => generate_random_key(32),
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
	for ($time_shift = $past_time; $cur_time > $time_shift; $time_shift += mt_rand($min_interval, $max_interval))
	{
		//mt_srand(crc32(microtime()));
		$insert_data_count = $insert_data_count + 1;

		$rand_i = mt_rand(0, $users_count - 1);
		$rand_link = mt_rand(0, $links_count - 1);
		$cur_seesion = $sessions[$rand_i];
		$rand_request_method = $request_methods[ mt_rand( 0, $request_methods_count - 1 ) ];


		if (strstr($links[$rand_link]['link'], '$keywords$'))
		{ // check if the current search link is selected randomly.
			// If yes, generate search link and add it to DB
			//mt_srand(crc32(microtime()+ $time_shift));
			$keywords = 'fake search ' . mt_rand(0, 9);
			$links[$rand_link]['link'] = str_replace('$keywords$', urlencode($keywords), $links[$rand_link]['link']);
			if (strstr($links[$rand_link]['link'], 's='))
			{
				$links[$rand_link]['s'] = $keywords;
			}
		}

		if ($cur_seesion['sess_ID'] == -1)
		{ // This session needs initialization:
			$cur_seesion['sess_start_ts'] = $time_shift - 1;
			$cur_seesion['sess_lastseen_ts'] = $time_shift;

			$DB->query("
					INSERT INTO T_sessions ( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_user_ID, sess_device )
					VALUES (
						'" . $cur_seesion['sess_key'] . "',
						'" . date('Y-m-d H:i:s', $cur_seesion['sess_start_ts']) . "',
						'" . date('Y-m-d H:i:s', $cur_seesion['sess_lastseen_ts']) . "',
						" . $DB->quote( $cur_seesion['sess_ipaddress'] ) . ",
						" . $cur_seesion['sess_user_ID'] . ",
						" . $DB->quote( $cur_seesion['sess_device'] ) . "
					)");

			$cur_seesion['sess_ID'] = $DB->insert_id;
			$sessions[$rand_i] = $cur_seesion;

			// Check if current url is api request:
			$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

			$Test_hit = new Hit('', $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $links[$rand_link]);
			$Test_hit->method = $rand_request_method;
			$Test_hit->log();
		}
		else
		{
			if (($time_shift - $cur_seesion['sess_lastseen_ts']) > 3000 || !empty($cur_seesion['robot']))
			{ // This session last updated more than 3000 sec ago. Instead of this session create a new session.
				$cur_seesion = array(
					'sess_ID'          => -1,
					'sess_key'         => generate_random_key(32),
					'sess_start_ts'    => 0,
					'sess_lastseen_ts' => 0,
					'sess_ipaddress'   => generate_random_ip(),
					'sess_user_ID'     => $users_array[mt_rand(0, $users_count - 1)]['user_ID'],
					'sess_device'      => $devices[ mt_rand( 0, $devices_count - 1 ) ],
					'pervios_link'     => '',
					'robot'            => ''
				);

				$cur_seesion['sess_start_ts'] = $time_shift - 1;
				$cur_seesion['sess_lastseen_ts'] = $time_shift;
				$r_num = mt_rand(0, 100);
				if ($r_num > 40)
				{ // Create anonymous user and make double insert into hits.
					$cur_seesion['sess_user_ID'] = -1;
					$DB->query("
							INSERT INTO T_sessions ( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_device )
							VALUES (
								'" . $cur_seesion['sess_key'] . "',
								'" . date('Y-m-d H:i:s', $cur_seesion['sess_start_ts']) . "',
								'" . date('Y-m-d H:i:s', $cur_seesion['sess_lastseen_ts']) . "',
								" . $DB->quote( $cur_seesion['sess_ipaddress'] ) . ",
								" . $DB->quote( $cur_seesion['sess_device'] ) . "
							)");

					if ($r_num >= 80)
					{ // Create robot hit
						$cur_seesion['robot'] = $robots[mt_rand(0, $robots_count)];
					}
				}
				else
				{
					$DB->query("
							INSERT INTO T_sessions( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_user_ID, sess_device )
							VALUES (
								'" . $cur_seesion['sess_key'] . "',
								'" . date('Y-m-d H:i:s', $cur_seesion['sess_start_ts']) . "',
								'" . date('Y-m-d H:i:s', $cur_seesion['sess_lastseen_ts']) . "',
								" . $DB->quote($cur_seesion['sess_ipaddress']) . ",
								" . $cur_seesion['sess_user_ID'] . ",
								" . $DB->quote( $cur_seesion['sess_device'] ) . "
							)");
				}

				$cur_seesion['sess_ID'] = $DB->insert_id;

				if (mt_rand(0, 100) > 20)
				{
					//$ref_count
					$ref_link = $referes[mt_rand(0, $ref_count)];
					if (strstr($ref_link, '$keywords$'))
					{ // check if the current search link is selected randomly.
						$keywords = 'fake search ' . mt_rand(0, 9);
						$ref_link = str_replace('$keywords$', urlencode($keywords), $ref_link);
					}
				}
				else
				{
					$ref_link = '';
				}

				if ($cur_seesion['sess_user_ID'] == -1)
				{
					if (empty($cur_seesion['robot']))
					{
						$link = array('link' => '/htsrv/login.php',
							'blog_id' => 1);

						// This is NOT api request:
						$is_api_request = false;

						$Test_hit = new Hit($ref_link, $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $link);

						$Test_hit->method = $rand_request_method;

						$Test_hit->log();

						$link = array('link' => '/htsrv/login.php?redirect_to=fake_stat',
							'blog_id' => 1);

						$Test_hit = new Hit($baseurlroot, $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'] + 3, 1, $link);

						$Test_hit->method = $rand_request_method;

						$Test_hit->log();

						$cur_seesion['pervios_link'] = $baseurlroot . $link['link'];
					}
					else
					{
						// Check if current url is api request:
						$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

						if (mt_rand(0, 100) < 50)
						{ // robot hit
							$Test_hit = new Hit('', $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $links[$rand_link], $cur_seesion['robot']);
						}
						else
						{ // rss/atom hit
							$Test_hit = new Hit('', $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $links[$rand_link], NULL, NULL, 1);
						}
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
					}
				}
				else
				{
					if (mt_rand(0, 100) < 10)
					{ // Test hit to admin page

						// This is NOT api request:
						$is_api_request = false;

						$Test_hit = new Hit('', $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $admin_link, NULL, 1);
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
						$cur_seesion['pervios_link'] = $admin_url;
					}
					else
					{
						// Check if current url is api request:
						$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

						$Test_hit = new Hit($ref_link, $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $links[$rand_link]);
						$Test_hit->method = $rand_request_method;
						$Test_hit->log();
						$cur_seesion['pervios_link'] = $baseurlroot . $links[$rand_link]['link'];
					}
				}
			}
			else
			{
				// Update session
				$cur_seesion['sess_lastseen_ts'] = $time_shift;

				// Check if current url is api request:
				$is_api_request = ( strpos( $links[$rand_link]['link'], '/api/v1' ) === 0 || strpos( $links[$rand_link]['link'], '/xmlsrv/xmlrpc.php' ) === 0 );

				$Test_hit = new Hit($cur_seesion['pervios_link'], $cur_seesion['sess_ipaddress'], $cur_seesion['sess_ID'], $cur_seesion['sess_lastseen_ts'], 1, $links[$rand_link]);
				$Test_hit->method = $rand_request_method;
				$Test_hit->log();



				$sql = "UPDATE T_sessions SET
								sess_lastseen_ts = '" . date('Y-m-d H:i:s', $cur_seesion['sess_lastseen_ts']) . "'
								WHERE sess_ID = {$cur_seesion['sess_ID']}";

				$DB->query($sql, 'Update session');

				$cur_seesion['pervios_link'] = $baseurlroot . $links[$rand_link]['link'];

				$sessions[$rand_i] = $cur_seesion;
			}
		}

		$sessions[$rand_i] = $cur_seesion;

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
	$ids = $DB->get_row( $sql, "ARRAY_A", NULL, ' Get max/min hits ids of unextracted keyphrases' );

	if ( ! empty ( $ids['min'] ) && ! empty ( $ids['max'] ) )
	{ // Extract keyphrases if needed:

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
?>