<?php
/**
 * Get updates from b2evolution.net
 *
 * @return
 */
function b2evonet_get_updates()
{
	global $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri, $servertimenow;
	global $Messages, $Settings, $baseurl, $instance_name, $app_name, $app_version, $app_date;

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

	$Messages->add( T_('Getting updates from ').$evonetsrv_host, 'notes' );
	$Settings->set( 'evonet_last_attempt', $servertimenow );
	$Settings->dbupdate();

	// Construct XML-RPC client:
	load_funcs('_ext/xmlrpc/_xmlrpc.php' );
	$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port );
	//$client->debug = $debug;

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
													'test1' => new xmlrpcval( $app_name, 'string'),
													'test2' => new xmlrpcval( $app_date, 'string'),
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
			$global_Cache->set( 'evonet_updates', serialize($response) );
			$global_Cache->dbupdate();

			$Settings->set( 'evonet_last_update', $servertimenow );
			$Settings->dbupdate();

			return true;
		}
		else
		{
			$Messages->add( T_('Invalid updates received'), 'error' );
		}
	}

	return false;
}


?>
