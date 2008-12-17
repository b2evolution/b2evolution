<?php
/**
 * This file implements the support functions for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @return boolean True if there have been updates.
 */
function b2evonet_get_updates()
{
	global $DB, $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri, $servertimenow, $evo_charset;
	global $Messages, $Settings, $baseurl, $instance_name, $app_name, $app_version, $app_date;
	global $Debuglog;

	$update_every = 3600*12; // 12 hours
	$attempt_every = 3600*4; // 4 hours

	/* DEBUG: *
	$update_every = 10;
	$attempt_every = 5;
	*/

	$servertime_last_update = $Settings->get( 'evonet_last_update' );
	if( $servertime_last_update > $servertimenow - $update_every )
	{	// The previous update was less than 12 hours ago, skip this
		// echo 'recent update';
		return false;
	}

	$servertime_last_attempt = $Settings->get( 'evonet_last_attempt' );
	if( $servertime_last_attempt > $servertimenow - $attempt_every)
	{	// The previous update attempt was less than 4 hours ago, skip this
		// This is so all b2evo's don't go crazy if the server ever is down
		// echo 'recent attempt';
		return false;
	}

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
	// $client->debug = $debug;

	// Run system checks:
	load_funcs( 'tools/model/_system.funcs.php' );
	list( $mediadir_status ) = system_check_media_dir();
	list( $uid, $uname ) = system_check_process_user();
	list( $gid, $gname ) = system_check_process_group();

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.getupdates',                           // Function to be called
								array(
									new xmlrpcval( $baseurl, 'string'),					// Unique identifier part 1
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
			$Settings->dbupdate();

			$Debuglog->add( 'Updates saved', 'evonet' );

			return true;
		}
		else
		{
			$Debuglog->add( 'Invalid updates received', 'evonet' );
			$Messages->add( T_('Invalid updates received'), 'error' );
		}
	}

	return false;
}

/*
 * $Log$
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
