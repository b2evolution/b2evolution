<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */


/*
 * pingb2evonet(-)
 *
 * pings b2evolution.net
 * EXPERIMENTAL
 */
function pingb2evonet( & $blogparams, $post_ID, $post_title, $display = true ) 
{
	$test = 0;

	global $baseurl, $use_b2evonetping;
	if( ! $use_b2evonetping ) return false;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Pinging b2evolution.net...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) || $test ) 
	{
		if( $test )
		{
		 	$client = new xmlrpc_client('/b2evolution/blogs/evonetsrv/xmlrpc.php', 'localhost', 8088);
			$client->debug = 1;
		}
		else
		{
			$client = new xmlrpc_client('/evonetsrv/xmlrpc.php', 'b2evolution.net', 80);
			// $client->debug = 1;
		}
		
		$message = new xmlrpcmsg( 'b2evo.ping', array( 
															new xmlrpcval('id') ,			// Reserved
															new xmlrpcval('user'),		// Reserved
															new xmlrpcval('pass'),		// Reserved
															new xmlrpcval(bloginfo('name', 'xml', false, $blogparams)), 
															new xmlrpcval(bloginfo('blogurl', 'xml', false, $blogparams)),
															new xmlrpcval(bloginfo('lang', 'xml', false, $blogparams)),
															new xmlrpcval(format_to_output( $post_title, 'xml' ))
														)  );
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
 * pingWeblogs(-)
 *
 * pings Weblogs.com
 * original function by Dries Buytaert for Drupal
 */
function pingWeblogs( & $blogparams, $display = true ) 
{
	global $baseurl, $use_weblogsping;
	if( ! $use_weblogsping ) return false;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Pinging Weblogs.com...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) ) 
	{
		$client = new xmlrpc_client("/RPC2", "rpc.weblogs.com", 80);
		$message = new xmlrpcmsg( 'weblogUpdates.ping', array( 
															new xmlrpcval(get_bloginfo('name', $blogparams)) , 
															new xmlrpcval(get_bloginfo('blogurl', $blogparams)) )  );
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
 * pingWeblogsRss(-)
 *
 * pings Weblogs.com/rssUpdates
 */
/*function pingWeblogsRss($rss_url) 
{
	global $baseurl, $use_weblogsrssping, $blogname;
	if( ! $use_weblogsrssping ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", T_('Pinging Weblogs.com/rssUpdates...'), "</h3>\n";
	if( !preg_match( '#^http://localhost[/:]#',$baseurl) ) 
	{
		flush();
		$client = new xmlrpc_client('/RPC2', 'rssrpc.weblogs.com', 80);
		$message = new xmlrpcmsg('rssUpdate', array(new xmlrpcval($blogname), new xmlrpcval($rss_url)));
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", T_('Done.'), "</p>\n</div>\n";
		return($ret);
	} else {
		echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}
*/

/*
 * pingCafelog(-)
 *
 * pings CaféLog.com
 */
function pingCafelog( $cafelogID, $title='', $p='') 
{
	global $use_cafelogping, $blogname, $baseurl, $blogfilename;
	if( (! $use_cafelogping) || (empty($cafelogID)) ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", T_('Pinging Cafelog.com...'), "</h3>\n";
	if ( !preg_match( '#^http://localhost[/:]#',$baseurl) ) 
	{
		flush();
		$client = new xmlrpc_client("/", "cafelog.tidakada.com", 80);
		$message = new xmlrpcmsg("b2.ping", array(
										new xmlrpcval($cafelogID),
										new xmlrpcval($title),
										new xmlrpcval($p)));
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", T_('Done.'), "</p>\n</div>\n";
		return($ret);
	} else {
		echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}


/*
 * pingBlogs(-)
 *
 * pings Blo.gs
 *
 * fplanque removed useless $blodotgsping_url
 */
function pingBlogs( & $blogparams ) 
{
	global $use_blodotgsping, $use_rss, $blogname, $baseurl, $blogfilename;
	if( ! $use_blodotgsping ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", T_('Pinging Blo.gs...'), "</h3>\n";
	if( !preg_match( '#^http://localhost[/:]#',$baseurl) ) 
	{
		flush();
		$url = get_bloginfo('blogurl');
		$client = new xmlrpc_client('/', 'ping.blo.gs', 80);
		if ($use_rss) 
		{
			$message = new xmlrpcmsg('weblogUpdates.extendedPing',
								 array( new xmlrpcval( get_bloginfo('name', $blogparams) ), 
								 				new xmlrpcval( get_bloginfo('blogurl', $blogparams) ), 
												new xmlrpcval( get_bloginfo('blogurl', $blogparams) ), 
												new xmlrpcval( get_bloginfo('rss_url', $blogparams) ) 
											) );
		}
		else 
		{
			$message = new xmlrpcmsg('weblogUpdates.ping', 
								array(new xmlrpcval( get_bloginfo('name', $blogparams) ), 
								 				new xmlrpcval( get_bloginfo('blogurl', $blogparams) )
											));
		}
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", T_('Done.'), "</p>\n</div>\n";
		return($ret);
	} 
	else 
	{
		echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}

?>