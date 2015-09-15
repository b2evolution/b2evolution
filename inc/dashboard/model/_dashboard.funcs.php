<?php
/**
 * This file implements the support functions for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

 /**
 * Get updates from b2evolution.net
 *
 * @param boolean useful when trying to upgrade to a release that has just been published (in the last 12 hours)
 * @return NULL|boolean True if there have been updates, false on error,
 *                      NULL if the user has turned off updates.
 */
function b2evonet_get_updates( $force_short_delay = false )
{
	global $allow_evo_stats; // Possible values: true, false, 'anonymous'
	global $DB, $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri, $servertimenow, $evo_charset;
	global $Messages, $Settings, $baseurl, $instance_name, $app_name, $app_version, $app_date;
	global $Debuglog;
	global $Timer;
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	if( ! isset( $allow_evo_stats ) )
	{	// Set default value:
		$allow_evo_stats = true; // allow (non-anonymous) stats
	}
	if( $allow_evo_stats === false )
	{ // Get outta here:
		return NULL;
	}

	if( $debug == 2 )
	{
		$update_every = 8;
		$attempt_every = 3;
	}
	elseif( $force_short_delay )
	{
		$update_every = 180; // 3 minutes
		$attempt_every = 60; // 1 minute
	}
	else
	{
		$update_every = 3600*12; // 12 hours
		$attempt_every = 3600*4; // 4 hours
	}

	// Note: do not put $baseurl in here since it would cause too frequently updates, when you have the same install with different $baseurls.
	//           Everytime this method gets called on another baseurl, there's a new check for updates!
	$version_id = $instance_name.' '.$app_name.' '.$app_version.' '.$app_date;
	// This is the last version we checked against the server:
	$last_version_checked =  $Settings->get( 'evonet_last_version_checked' );

	$servertime_last_update = $Settings->get( 'evonet_last_update' );
	$servertime_last_attempt = $Settings->get( 'evonet_last_attempt' );

	if( $last_version_checked == $version_id )
	{	// Current version has already been checked, don't check too often:

		if( $servertime_last_update > $servertimenow - $update_every )
		{	// The previous update was less than 12 hours ago, skip this
			// echo 'recent update';
			return false;
		}

		if( $servertime_last_attempt > $servertimenow - $attempt_every)
		{	// The previous update attempt was less than 4 hours ago, skip this
			// This is so all b2evo's don't go crazy if the server ever is down
			// echo 'recent attempt';
			return false;
		}
	}

	$Timer->resume('evonet: check for updates');
	$Debuglog->add( sprintf('Getting updates from %s.', $evonetsrv_host), 'evonet' );
	if( $debug )
	{
		$Messages->add( sprintf(T_('Getting updates from %s.'), $evonetsrv_host), 'note' );
	}
	$Settings->set( 'evonet_last_attempt', $servertimenow );
	$Settings->dbupdate();

	// Construct XML-RPC client:
	load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
	$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port );
	if( $debug > 1 )
	{
		$client->debug = 1;
	}

	// Set proxy for outgoing connections:
	if( !empty($outgoing_proxy_hostname) )
	{
		$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
	}

	// Run system checks:
	load_funcs( 'tools/model/_system.funcs.php' );

	// Get system stats to display:
	$system_stats = get_system_stats();

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.getupdates',                           // Function to be called
								array(
									new xmlrpcval( ( $allow_evo_stats === 'anonymous' ? md5( $baseurl ) : $baseurl ), 'string'),	// Unique identifier part 1
									new xmlrpcval( $instance_name, 'string'),		// Unique identifier part 2
									new xmlrpcval( $app_name, 'string'),		    // Version number
									new xmlrpcval( $app_version, 'string'),	  	// Version number
									new xmlrpcval( $app_date, 'string'),		    // Version number
									new xmlrpcval( array(
											'this_update' => new xmlrpcval( $servertimenow, 'string' ),
											'last_update' => new xmlrpcval( $servertime_last_update, 'string' ),
											'mediadir_status' => new xmlrpcval( $system_stats['mediadir_status'], 'int' ), // If error, then the host is potentially borked
											'install_removed' => new xmlrpcval( ($system_stats['install_removed'] == 'ok') ? 1 : 0, 'int' ), // How many people do go through this extra measure?
											'evo_charset' => new xmlrpcval( $system_stats['evo_charset'], 'string' ),			// Do people actually use UTF8?
											'evo_blog_count' => new xmlrpcval( $system_stats['evo_blog_count'], 'int'),   // How many users do use multiblogging?
											'cachedir_status' => new xmlrpcval( $system_stats['cachedir_status'], 'int'),
											'cachedir_size' => new xmlrpcval( $system_stats['cachedir_size'], 'int'),
											'general_pagecache_enabled' => new xmlrpcval( $system_stats['general_pagecache_enabled'] ? 1 : 0, 'int' ),
											'blog_pagecaches_enabled' => new xmlrpcval( $system_stats['blog_pagecaches_enabled'], 'int' ),
											'db_version' => new xmlrpcval( $system_stats['db_version'], 'string'),	// If a version >95% we make it the new default.
											'db_utf8' => new xmlrpcval( $system_stats['db_utf8'] ? 1 : 0, 'int' ),	// if support >95%, we'll make it the default
											// How many "low security" hosts still active?; we'd like to standardize security best practices... on suphp?
											'php_uid' => new xmlrpcval( $system_stats['php_uid'], 'int' ),
											'php_uname' => new xmlrpcval( $system_stats['php_uname'], 'string' ),	// Potential unsecure hosts will use names like 'nobody', 'www-data'
											'php_gid' => new xmlrpcval( $system_stats['php_gid'], 'int' ),
											'php_gname' => new xmlrpcval( $system_stats['php_gname'], 'string' ),	// Potential unsecure hosts will use names like 'nobody', 'www-data'
											'php_version' => new xmlrpcval( $system_stats['php_version'], 'string' ),			// Target minimum version: PHP 5.2
											'php_reg_globals' => new xmlrpcval( $system_stats['php_reg_globals'] ? 1 : 0, 'int' ), // if <5% we may actually refuse to run future version on this
											'php_allow_url_include' => new xmlrpcval( $system_stats['php_allow_url_include'] ? 1 : 0, 'int' ),
											'php_allow_url_fopen' => new xmlrpcval( $system_stats['php_allow_url_fopen'] ? 1 : 0, 'int' ),
											// TODO php_magic quotes
											'php_upload_max' => new xmlrpcval( $system_stats['php_upload_max'], 'int' ),
											'php_post_max' => new xmlrpcval( $system_stats['php_post_max'], 'int' ),
											'php_memory' => new xmlrpcval( $system_stats['php_memory'], 'int'), // how much room does b2evo have to move on a typical server?
											'php_mbstring' => new xmlrpcval( $system_stats['php_mbstring'] ? 1 : 0, 'int' ),
											'php_xml' => new xmlrpcval( $system_stats['php_xml'] ? 1 : 0, 'int' ),
											'php_imap' => new xmlrpcval( $system_stats['php_imap'] ? 1 : 0, 'int' ),	// Does it make sense to rely on IMAP to handle undelivered emails (for user registrations/antispam)
											'php_opcode_cache' => new xmlrpcval( $system_stats['php_opcode_cache'], 'string' ), // How many use one? Which is the most popular?
											'gd_version' => new xmlrpcval( $system_stats['gd_version'], 'string' ),
											// TODO: add missing system stats
										), 'struct' ),
								)
							);

	$result = $client->send($message);

	if( $ret = xmlrpc_logresult( $result, $Messages, false ) )
	{ // Response is not an error, let's process it:
		$response = $result->value();
		if( $response->kindOf() == 'struct' )
		{ // Decode struct:
			$response = xmlrpc_decode_recurse($response);

			/**
			 * @var AbstractSettings
			 */
			global $global_Cache;

			foreach( $response as $key=>$data )
			{
				$global_Cache->set( $key, serialize($data) );
			}

			$global_Cache->delete( 'evonet_updates' );	// Cleanup

			$global_Cache->dbupdate();

			$Settings->set( 'evonet_last_update', $servertimenow );
			$Settings->set( 'evonet_last_version_checked', $version_id );
			$Settings->dbupdate();

			$Debuglog->add( 'Updates saved', 'evonet' );

			$Timer->pause('evonet: check for updates');
			return true;
		}
		else
		{
			$Debuglog->add( 'Invalid updates received', 'evonet' );
			$Messages->add( T_('Invalid updates received'), 'error' );
		}
	}

	$Timer->pause('evonet: check for updates');
	return false;
}


/**
 * Get comments awaiting moderation number
 *
 * @param integer blog ID
 * @return integer
 */
function get_comments_awaiting_moderation_number( $blog_ID )
{
	global $DB;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
	$moderation_statuses = $Blog->get_setting( 'moderation_statuses' );
	$moderation_statuses_condition = '\''.str_replace( ',', '\',\'', $moderation_statuses ).'\'';

	$sql = 'SELECT COUNT(DISTINCT(comment_ID))
				FROM T_comments
					INNER JOIN T_items__item ON comment_item_ID = post_ID ';

	$sql .= 'INNER JOIN T_postcats ON post_ID = postcat_post_ID
				INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID ';

	$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('othercats.cat_blog_ID');
	$sql .= ' AND comment_type IN (\'comment\',\'trackback\',\'pingback\') ';
	$sql .= ' AND comment_status IN ( '.$moderation_statuses_condition.' )';
	$sql .= ' AND '.statuses_where_clause();

	return $DB->get_var( $sql );
}


/**
 * Show comments awaiting moderation
 *
 * @todo fp> move this to a more appropriate place
 *
 * @param integer blog ID
 * @param object CommentList
 * @param integer limit
 * @param array comment IDs to exclude
 * @param boolean TRUE - for script
 */
function show_comments_awaiting_moderation( $blog_ID, $CommentList = NULL, $limit = 5, $comment_IDs = array(), $script = true )
{
	global $current_User, $dispatcher;

	if( is_null( $CommentList ) )
	{ // Inititalize CommentList
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

		$CommentList = new CommentList2( $Blog, NULL, 'CommentCache', 'cmnt_fullview_', 'fullview' );
		$exlude_ID_list = NULL;
		if( !empty($comment_IDs) )
		{
			$exlude_ID_list = '-'.implode( ",", $comment_IDs );
		}

		$moderation_statuses = explode( ',', $Blog->get_setting( 'moderation_statuses' ) );

		// Filter list:
		$CommentList->set_filters( array(
				'types' => array( 'comment', 'trackback', 'pingback' ),
				'statuses' => $moderation_statuses,
				'comment_ID_list' => $exlude_ID_list,
				'post_statuses' => array( 'published', 'community', 'protected' ),
				'order' => 'DESC',
				'comments' => $limit,
			) );

		// Get ready for display (runs the query):
		$CommentList->display_init();
	}

	$index = 0;
	$new_comment_IDs = array();
	while( $Comment = & $CommentList->get_next() )
	{ // Loop through comments:
		$new_comment_IDs[] = $Comment->ID;
		$index = $index + 1;
		// Only 5 commens should be visible, set hidden status for the rest
		$hidden_status = ( $index > 5 ) ? ' hidden_comment' : '';

		echo '<div id="comment_'.$Comment->ID.'" class="dashboard_post dashboard_post_'.($CommentList->current_idx % 2 ? 'even' : 'odd' ).$hidden_status.'">';

/* OLD:
		echo '<div class="floatright"><span class="note status_'.$Comment->status.'"><span>';
		$Comment->status();
		echo '</span></span></div>';
	NEW:
*/
		$Comment->format_status( array(
				'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
			) );

		echo $Comment->get_author( array(
				'before'      => '<div class="dashboard_comment_avatar">',
				'after'       => '</div>',
				'before_user' => '<div class="dashboard_comment_avatar">',
				'after_user'  => '</div>',
				'link_text'   => 'only_avatar',
				'link_class'  => 'user',
				'thumb_size'  => 'crop-top-80x80',
				'thumb_class' => 'user',
			) );

		echo '<h3 class="dashboard_comment_title">';
		if( ( $Comment->status !== 'draft' ) || ( $Comment->author_user_ID == $current_User->ID ) )
		{ // Display Comment permalink icon
			echo $Comment->get_permanent_link( '#icon#' ).' ';
		}
		echo $Comment->get_title( array(
				'author_format' => '<strong>%s</strong>',
				'link_text'     => 'login',
			) );
		$comment_Item = & $Comment->get_Item();
		echo ' '.T_('in response to')
				.' <a href="?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'"><strong>'.$comment_Item->dget('title').'</strong></a>';

		echo '</h3>';

		echo '<div class="notes">';
		$Comment->rating( array(
				'before'      => '<div class="dashboard_rating">',
				'after'       => '</div> &bull; ',
			) );
		$Comment->date();
		$Comment->author_url_with_actions( '', true );
		$Comment->author_email( '', ' &bull; Email: <span class="bEmail">', '</span> &bull; ' );
		$Comment->author_ip( 'IP: <span class="bIP">', '</span> ', true, true );
		$Comment->ip_country();
		$Comment->spam_karma( ' &bull; '.T_('Spam Karma').': %s%', ' &bull; '.T_('No Spam Karma') );
		echo '</div>';

		$Comment->content( 'htmlbody', true );

		echo '<div class="dashboard_action_area">';
		// Display edit button if current user has the rights:
		$redirect_to = NULL;
		if( ! $script )
		{ // Set page, where to redirect, because the function is called from async.php (regenerate_url gives => async.php)
			global $admin_url;
			$redirect_to = $admin_url.'?ctrl=dashboard&blog='.$blog_ID;
		}

		echo '<div class="floatleft">';

		$Comment->edit_link( ' ', ' ', get_icon( 'edit_button' ).' '.T_('Edit'), '#', button_class( 'text_primary' ).' btn-sm w80px', '&amp;', true, $redirect_to );

		echo '<span class="'.button_class( 'group' ).' btn-group-sm">';
		// Display publish NOW button if current user has the rights:
		$Comment->publish_link( '', '', '#', '#', button_class( 'text' ), '&amp;', true, true );

		// Display deprecate button if current user has the rights:
		$Comment->deprecate_link( '', '', '#', '#', button_class( 'text' ), '&amp;', true, true );

		// Display delete button if current user has the rights:
		$Comment->delete_link( '', '', '#', '#', button_class( 'text' ), false, '&amp;', true, true );
		echo '</span>';

		echo '</div>';

		// Display Spam Voting system
		$Comment->vote_spam( '', '', '&amp;', true, true, array( 'button_group_class' => button_class( 'group' ).' btn-group-sm' ) );

		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}

	if( !$script )
	{
		echo '<input type="hidden" id="new_badge" value="'.$CommentList->get_total_rows().'"/>';
	}
}


/**
 * Get a count of the records in the DB table
 *
 * @param string Table name
 * @param string SQL WHERE
 * @param string SQL FROM
 * @return integer A count of the records
 */
function get_table_count( $table_name, $sql_where = '', $sql_from = '' )
{
	global $DB;

	$SQL = new SQL();
	$SQL->SELECT( 'COUNT( * )' );
	$SQL->FROM( $table_name );
	if( !empty( $sql_from ) )
	{ // Additional sql for FROM clause
		$SQL->FROM_add( $sql_from );
	}
	if( !empty( $sql_where ) )
	{ // Additional sql for WHERE clause
		$SQL->WHERE( $sql_where );
	}

	return intval( $DB->get_var( $SQL->get() ) );
}


/**
 * Dispaly posts awaiting moderation with the given status
 *
 * @param string visibility status
 * @param object block_item_Widget
 * @return boolean true if items were displayed, false otherwise
 */
function display_posts_awaiting_moderation( $status, & $block_item_Widget )
{
	global $Blog, $current_User;

	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( $status ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	if( !$ItemList->result_num_rows )
	{ // We don't have posts awaiting moderation with the given status
		return false;
	}

	switch( $status )
	{
		case 'draft':
			$block_title = T_('Recent drafts');
			break;

		case 'review':
			$block_title = T_('Recent posts to review');
			break;

		case 'protected':
			$block_title = T_('Recent member posts awaiting moderation');
			break;

		case 'community':
			$block_title = T_('Recent community posts awaiting moderation');
			break;

		default:
			$block_title = T_('Recent posts awaiting moderation');
			break;
	}
	$block_item_Widget->title = $block_title;
	$block_item_Widget->disp_template_replaced( 'block_start' );

	while( $Item = & $ItemList->get_item() )
	{
		echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
		// We don't switch locales in the backoffice, since we use the user pref anyway
		// Load item's creator user:
		$Item->get_creator_User();

		$Item->format_status( array(
				'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
			) );

		echo '<div class="dashboard_float_actions">';
		$Item->edit_link( array( // Link to backoffice for editing
				'before'    => ' ',
				'after'     => ' ',
				'class'     => 'ActionButton btn btn-primary',
				'text'      => get_icon( 'edit_button' ).' '.T_('Edit')
			) );
		$Item->publish_link( '', '', '#', '#', 'PublishButton btn btn-status-published' );
		echo get_icon( 'pixel' );
		echo '</div>';

		if( ( $Item->status !== 'draft' ) || ( $Item->creator_user_ID == $current_User->ID ) )
		{ // Display Item permalink icon
			echo '<span style="float: left; padding-right: 5px; margin-top: 4px">'.$Item->get_permanent_link( '#icon#' ).'</span>';
		}
		echo '<h3 class="dashboard_post_title">';
		$item_title = $Item->dget('title');
		if( ! strlen($item_title) )
		{
			$item_title = '['.format_to_output(T_('No title')).']';
		}
		echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
		echo ' <span class="dashboard_post_details">';
		echo '</span>';
		echo '</h3>';

		echo '</div>';
	}

	$block_item_Widget->disp_template_raw( 'block_end' );

	return true;
}


/**
 * Get percent by function log10()
 *
 * @param integer Value
 * @return integer Percent
 */
function log10_percent( $value )
{
	$percent = log10( intval( $value ) ) * 2 * 10;
	return intval( $percent > 100 ? 100 : $percent );
}


/**
 * Display charts
 *
 * @param array Chart data
 */
function display_charts( $chart_data )
{
	if( empty( $chart_data ) )
	{ // No data
		return;
	}

	echo '<div class="charts">';
	foreach( $chart_data as $chart_item )
	{
		if( $chart_item['type'] == 'number' )
		{ // Calculate a percent with log10 where max value is 100000
			$chart_percent = empty( $chart_item['value'] ) ? 0 : log10_percent( $chart_item['value'] );
			// Set a color for value, from green(0%) to red(100%)
			$chart_color = get_color_by_percent( '#61bd4f', '#f2d600', '#ffab4a', $chart_percent );
		}
		else
		{ // Calculate a real percent
			$chart_percent = empty( $chart_item['100%'] ) ? 0 : floor( intval( $chart_item['value'] ) / $chart_item['100%'] ) * 100;
			$chart_item['value'] = $chart_percent.'%';
			$chart_color = '#00F';
		}
		if( $chart_item['value'] > 0 && $chart_percent == 0 )
		{ // Display a little chart for not null values
			$chart_percent = 0.01;
		}
		// Display chart
		echo '<div class="chart">
				<div class="'.$chart_item['type'].'" data-percent="'.$chart_percent.'"><b style="color:'.$chart_color.'">'.$chart_item['value'].'</b></div>
				<div class="label">'.$chart_item['title'].'</div>
			</div>';
	}
	echo '</div>';

	echo '<div class="clear"></div>';
}


/**
 * Convert color #FFFFFF to array( 'R' => 255, 'G' => 255, 'B' => 255 )
 *
 * @param string Color in hex format
 * @return array Color in RGB format
 */
function color_hex2rgb( $color )
{
	$color = str_replace( '#', '', $color );
	$color = str_split( $color, strlen( $color ) / 3 );
	foreach( $color as $c => $hex )
	{
		$color[ $c ] = strlen( $hex ) == 1 ? $hex.$hex : $hex;
	}
	return array(
			'R' => hexdec( $color[0] ),
			'G' => hexdec( $color[1] ),
			'B' => hexdec( $color[2] ),
		);
}


/**
 * Get color by percent between three colors
 *
 * @param string Start Color in hex format
 * @param string Middle Color in hex format
 * @param string End Color in hex format
 * @param integer Percent
 * @return string Result Color in hex format
 */
function get_color_by_percent( $color_from, $color_middle, $color_to, $percent )
{
	if( $percent < 50 )
	{ // First 50 percents
		$color_to = $color_middle;
		$percent *= 2;
	}
	else
	{ // Last 50 percents
		$color_from = $color_middle;
		$percent = ( $percent - 50 ) * 2;
	}
	$color_from = color_hex2rgb( $color_from );
	$color_to = color_hex2rgb( $color_to );

	$new_color = '#';
	foreach( $color_from as $rgb_key => $rgb_color )
	{
		$rgb_percent = $color_from[ $rgb_key ] + round( ( $color_to[ $rgb_key ] - $color_from[ $rgb_key ] ) * ( $percent / 100 ) );
		$rgb_percent = $rgb_percent > 255 ? 255 : ( $rgb_percent < 0 ? 0 : $rgb_percent );

		$new_color .= str_pad( dechex( $rgb_percent ), 2, 0, STR_PAD_LEFT );
	}

	return $new_color;
}
?>