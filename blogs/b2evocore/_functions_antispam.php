<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

/*
 * b2evonet_report_abuse(-)
 *
 * pings b2evolution.net to report abuse from a particular domain
 */
function b2evonet_report_abuse( $abuse_string, $display = true ) 
{
	$test = 0;

	global $baseurl;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Reporting abuse to b2evolution.net...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) || $test ) 
	{
		// Construct XML-RPC client:
		if( $test == 2 )
		{
		 	$client = new xmlrpc_client('/b2evolution/blogs/evonetsrv/xmlrpc.php', 'localhost', 8088);
			$client->debug = 1;
		}
		else
		{
			$client = new xmlrpc_client('/evonetsrv/xmlrpc.php', 'b2evolution.net', 80);
			// $client->debug = 1;
		}
		
		// Construct XML-RPC message:
		$message = new xmlrpcmsg( 
									'b2evo.reportabuse',	 											// Function to be called
									array( 
										new xmlrpcval(0,'int'),										// Reserved
										new xmlrpcval('annonymous','string'),			// Reserved
										new xmlrpcval('nopassrequired','string'),	// Reserved
										new xmlrpcval($abuse_string,'string'),		// The abusive string to report
										new xmlrpcval($baseurl,'string')					// The base URL of this b2evo
									)  
								);
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );

		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
		return($ret);
	} 
	else 
	{
		if( $display ) echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}


/*
 * b2evonet_poll_abuse(-)
 *
 * request abuse list from central blacklist
 */
function b2evonet_poll_abuse( $display = true ) 
{
	$test = 0;

	global $baseurl;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Requesting abuse list from b2evolution.net...'), "</h3>\n";
	}

	// Construct XML-RPC client:
	if( $test == 2 )
	{
		$client = new xmlrpc_client('/b2evolution/blogs/evonetsrv/xmlrpc.php', 'localhost', 8088);
		$client->debug = 1;
	}
	else
	{
		$client = new xmlrpc_client('/evonetsrv/xmlrpc.php', 'b2evolution.net', 80);
		$client->debug = 1;
	}
	
	// Construct XML-RPC message:
	$message = new xmlrpcmsg( 
								'b2evo.pollabuse',	 											// Function to be called
								array( 
									new xmlrpcval(0,'int'),										// Reserved
									new xmlrpcval('annonymous','string'),			// Reserved
									new xmlrpcval('nopassrequired','string')	// Reserved
								)  
							);
	$result = $client->send($message);
	
	if( $ret = xmlrpc_displayresult( $result ) )
	{	// Response is not an error, let's process it:
		$value = xmlrpc_decode($result->value());
		if (is_array($value))
		{	// We got an array of strings:
			echo '<p>Adding strings to local blacklist:</p><ul>';
			foreach($value as $banned_string)
			{
				echo '<li>Adding: [', $banned_string, '] : ';
				echo antispam_create( $banned_string ) ? 'OK.' : 'Not necessary! (Already handled)';
				echo '</li>';
			}
			echo '</ul>';
		}
		else
		{
			echo T_('Invalid reponse.')."\n";
			$ret = false;
		}
	}

	if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
	return($ret);
}


?>