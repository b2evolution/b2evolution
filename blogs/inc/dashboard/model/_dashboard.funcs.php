<?php
/**
 * This file implements the support functions for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */

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

	if( ! isset( $allow_evo_stats ) )
	{	// Set default value:
		$allow_evo_stats = true; // allow (non-anonymous) stats
	}
	if( $allow_evo_stats === false )
	{ // Get outta here as fast as you can, EdB style:
		return NULL;
	}

	if( $force_short_delay )
	{
		$update_every = 180; // 3 minutes
		$attempt_every = 60; // 1 minute
	}
	else
	{
		$update_every = 3600*12; // 12 hours
		$attempt_every = 3600*4; // 4 hours
	}

	/* DEBUG: */
	#$update_every = 10;
	#$attempt_every = 5;


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
		$Messages->add( sprintf(T_('Getting updates from %s.'), $evonetsrv_host), 'notes' );
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

	// Run system checks:
	load_funcs( 'tools/model/_system.funcs.php' );
	list( $mediadir_status ) = system_check_media_dir();
	list( $uid, $uname ) = system_check_process_user();
	list( $gid, $gname ) = system_check_process_group();

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.getupdates',                           // Function to be called
								array(
									new xmlrpcval( ( $allow_evo_stats == 'anonymous' /* this might even get EdB to send you stats ;) */ ? md5( $baseurl ) : $baseurl ), 'string'),					// Unique identifier part 1
									new xmlrpcval( $instance_name, 'string'),		// Unique identifier part 2
									new xmlrpcval( $app_name, 'string'),		    // Version number
									new xmlrpcval( $app_version, 'string'),	  	// Version number
									new xmlrpcval( $app_date, 'string'),		    // Version number
									new xmlrpcval( array(
											'this_update' => new xmlrpcval( $servertimenow, 'string' ),
											'last_update' => new xmlrpcval( $servertime_last_update, 'string' ),
											'db_version' => new xmlrpcval( $DB->get_version(), 'string'),	// If a version >95% we make it the new default.
											'db_utf8' => new xmlrpcval( system_check_db_utf8() ? 1 : 0, 'int' ),	// if support >95%, we'll make it the default
											'evo_charset' => new xmlrpcval( $evo_charset, 'string' ),
											'php_version' => new xmlrpcval( PHP_VERSION, 'string' ),
											'php_xml' => new xmlrpcval( extension_loaded('xml') ? 1 : 0, 'int' ),
											'php_mbstring' => new xmlrpcval( extension_loaded('mbstring') ? 1 : 0, 'int' ),
											'php_memory' => new xmlrpcval( system_check_memory_limit(), 'int'), // how much room does b2evo have to move on a typical server?
											'php_upload_max' => new xmlrpcval( system_check_upload_max_filesize(), 'int' ),
											'php_post_max' => new xmlrpcval( system_check_post_max_size(), 'int' ),
											'mediadir_status' => new xmlrpcval( $mediadir_status, 'string' ), // If error, then the host is potentially borked
											'install_removed' => new xmlrpcval( system_check_install_removed() ? 1 : 0, 'int' ), // How many people do go through this extra measure?
											// How many "low security" hosts still active?; we'd like to standardize security best practices... on suphp?
											'php_uid' => new xmlrpcval( $uid, 'int' ),
											'php_uname' => new xmlrpcval( $uname, 'string' ),	// Potential unsecure hosts will use names like 'nobody', 'www-data'
											'php_gid' => new xmlrpcval( $gid, 'int' ),
											'php_gname' => new xmlrpcval( $gname, 'string' ),	// Potential unsecure hosts will use names like 'nobody', 'www-data'
											'php_reg_globals' => new xmlrpcval( ini_get('register_globals') ? 1 : 0, 'int' ), // if <5% we may actually refuse to run future version on this
											'php_opcode_cache' => new xmlrpcval( get_active_opcode_cache(), 'string' ), // How many use one? Which is the most popular?
											'gd_version' => new xmlrpcval( system_check_gd_version(), 'string' ),
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
 * Show comments awaiting moderation
 *
 * @todo fp> move this to a more appropriate place
 *
 * @param integer blog ID
 * @param integer limit
 * @param array comment IDs to exclude
 */
function show_comments_awaiting_moderation( $blog_ID, $limit = 5, $comment_IDs = array(), $script = true )
{
	global $current_User, $dispatcher;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	$CommentList = new CommentList2( $Blog );
	$exlude_ID_list = NULL;
	if( !empty($comment_IDs) )
	{
		$exlude_ID_list = '-'.implode( ",", $comment_IDs );
	}

	// Filter list:
	$CommentList->set_filters( array(
			'types' => array( 'comment', 'trackback', 'pingback' ),
			'statuses' => array ( 'draft' ),
			'comment_ID_list' => $exlude_ID_list,
			'order' => 'DESC',
			'comments' => $limit,
		) );

	// Get ready for display (runs the query):
	$CommentList->display_init();
	
	$new_comment_IDs = array();
	while( $Comment = & $CommentList->get_next() )
	{ // Loop through comments:
		$new_comment_IDs[] = $Comment->ID;

		echo '<div id="comment_'.$Comment->ID.'" class="dashboard_post dashboard_post_'.($CommentList->current_idx % 2 ? 'even' : 'odd' ).'">';
		echo '<div class="floatright"><span class="note status_'.$Comment->status.'">';
		$Comment->status();
		echo '</div>';

		echo '<h3 class="dashboard_post_title">';
		echo $Comment->get_title(array('author_format'=>'<strong>%s</strong>'));
		$comment_Item = & $Comment->get_Item();
		echo ' '.T_('in response to')
				.' <a href="?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'"><strong>'.$comment_Item->dget('title').'</strong></a>';

		echo '</h3>';

		echo '<div class="notes">';
		$Comment->rating( array(
				'before'      => '',
				'after'       => ' &bull; ',
				'star_class'  => 'top',
			) );
		$Comment->date();
		$Comment->author_url_with_actions( '', true );
		$Comment->author_email( '', ' &bull; Email: <span class="bEmail">', '</span> &bull; ' );
		$Comment->author_ip( 'IP: <span class="bIP">', '</span> &bull; ' );
		$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
		echo '</div>';

		echo '<div class="small">';
		$Comment->content();
		echo '</div>';

		echo '<div class="dashboard_action_area">';
		// Display edit button if current user has the rights:
		$redirect_to = NULL;
		if( ! $script )
		{ // Set page, where to redirect, because the function is called from async.php (regenerate_url gives => async.php)
			global $admin_url;
			$redirect_to = $admin_url.'?ctrl=dashboard&blog='.$blog_ID;
		}
		$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton', '&amp;', true, $redirect_to );

		// Display publish NOW button if current user has the rights:
		$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', true, true );

		// Display deprecate button if current user has the rights:
		$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', true, true );

		// Display delete button if current user has the rights:
		$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, '&amp;', true, true );
		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}

	if( $script )
	{	// Show script to know which comments IDs have been already loaded. This code is needed for AJAX.
		echo '<script type="text/javascript">';
		foreach( $new_comment_IDs as $new_comment_ID )
		{
			echo 'commentIds[\'comment_'.$new_comment_ID.'\'] = '.$new_comment_ID.';';
		}
		echo '</script>';
	}
	else
	{
		$ind = param( 'ind', 'string' );
		echo '<input type="hidden" id="comments_'.$ind.'" value="'.implode( ',', $new_comment_IDs ).'"/>';
		echo '<input type="hidden" id="badge_'.$ind.'" value="'.get_comments_awaiting_moderation_number( $blog_ID ).'"/>';
	}
}


/*
 * $Log$
 * Revision 1.36  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.35  2010/03/28 19:27:47  fplanque
 * minor
 *
 * Revision 1.34  2010/03/11 13:10:09  efy-asimo
 * Fix ajax refresh on dashboard
 *
 * Revision 1.33  2010/03/11 10:35:03  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.32  2010/03/02 12:37:23  efy-asimo
 * remove show_comments_awaiting_moderation function from _misc_funcs.php to _dashboard.func.php
 *
 * Revision 1.31  2010/02/08 17:52:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.30  2009/12/22 08:02:11  fplanque
 * doc
 *
 * Revision 1.29  2009/12/22 02:22:03  blueyed
 * b2evonet_get_updates: TODO about baseurl in version_id, causing too many update checks.
 *
 * Revision 1.28  2009/12/22 02:11:30  blueyed
 * Add Timer for getting updates from evonet.
 *
 * Revision 1.27  2009/12/11 23:20:55  fplanque
 * no message
 *
 * Revision 1.26  2009/12/10 20:41:46  blueyed
 * Comment debug code out, which caused looking for updates nearly always.
 *
 * Revision 1.25  2009/12/06 22:55:22  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.24  2009/12/06 03:24:11  fplanque
 * minor/doc/fixes
 *
 * Revision 1.23  2009/11/30 01:22:23  fplanque
 * fix wrong version status message rigth after upgrade
 *
 * Revision 1.22  2009/11/30 01:08:27  fplanque
 * extended system optimization checks
 *
 * Revision 1.21  2009/11/15 19:44:02  fplanque
 * minor
 *
 * Revision 1.20  2009/07/04 16:40:56  tblue246
 * - b2evonet_get_updates():
 * 	- PHPdoc.
 * 	- Bugfix: $allow_evo_stats is also used when sending the XML-RPC message (line 89), thus it is not sufficient to only check whether it is set when checking if it equals false (line 38).
 * 	- The function now explicitly returns NULL when $allow_evo_stats === false.
 *
 * Revision 1.19  2009/07/01 23:46:28  fplanque
 * minor
 *
 * Revision 1.18  2009/06/13 13:47:40  yabs
 * minor
 *
 * Revision 1.17  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.16  2008/12/17 23:14:29  blueyed
 * Trans fix
 *
 * Revision 1.15  2008/09/15 03:10:40  fplanque
 * simplified updates
 *
 * Revision 1.14  2008/09/13 11:07:43  fplanque
 * speed up display of dashboard on first login of the day
 *
 * Revision 1.13  2008/04/27 02:42:39  fplanque
 * fix
 *
 * Revision 1.12  2008/04/26 22:20:45  fplanque
 * Improved compatibility with older skins.
 *
 * Revision 1.11  2008/04/24 22:05:59  fplanque
 * factorized system checks
 *
 * Revision 1.10  2008/04/09 17:15:33  fplanque
 * date stuff
 *
 * Revision 1.9  2008/04/09 15:37:41  fplanque
 * doc
 *
 */
?>
