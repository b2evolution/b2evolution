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
 * pingWeblogs(-)
 *
 * pings Weblogs.com
 */
function pingWeblogs( $blog_ID = 1, $display = true ) 
{
	// original function by Dries Buytaert for Drupal
	global $baseurl, $use_weblogsping;
	if( ! $use_weblogsping ) return false;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', _('Pinging Weblogs.com...'), "</h3>\n";
	}
	if( ! preg_match("/localhost\//",$baseurl) ) 
	{
		$client = new xmlrpc_client("/RPC2", "rpc.weblogs.com", 80);
		$message = new xmlrpcmsg("weblogUpdates.ping", array( new xmlrpcval(get_bloginfo('name')) , 
															new xmlrpcval(get_bloginfo('blogurl')) )  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		if( $display ) echo '<p>', _('Done.'), "</p>\n</div>\n";
		return($ret);
	} else {
		if( $display ) echo "<p>", _('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}


/*
 * pingWeblogsRss(-)
 *
 * pings Weblogs.com/rssUpdates
 */
function pingWeblogsRss($rss_url) 
{
	global $baseurl, $use_weblogsrssping, $blogname;
	if( ! $use_weblogsrssping ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", _('Pinging Weblogs.com/rssUpdates...'), "</h3>\n";
	if( !preg_match("/localhost\//",$baseurl) ) 
	{
		flush();
		$client = new xmlrpc_client('/RPC2', 'rssrpc.weblogs.com', 80);
		$message = new xmlrpcmsg('rssUpdate', array(new xmlrpcval($blogname), new xmlrpcval($rss_url)));
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", _('Done.'), "</p>\n</div>\n";
		return($ret);
	} else {
		echo "<p>", _('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}


/*
 * pingCafelog(-)
 *
 * pings CaféLog.com
 */
function pingCafelog($cafelogID,$title='',$p='') 
{
	global $use_cafelogping, $blogname, $baseurl, $blogfilename;
	if( (! $use_cafelogping) || (empty($cafelogID)) ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", _('Pinging Cafelog.com...'), "</h3>\n";
	if ( !preg_match("/localhost\//",$baseurl) ) 
	{
		flush();
		$client = new xmlrpc_client("/", "cafelog.tidakada.com", 80);
		$message = new xmlrpcmsg("b2.ping", array(new xmlrpcval($cafelogID), new xmlrpcval($title), new xmlrpcval($p)));
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", _('Done.'), "</p>\n</div>\n";
		return($ret);
	} else {
		echo "<p>", _('Aborted (Running on localhost).'), "</p>\n</div>\n";
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
function pingBlogs() 
{
	global $use_blodotgsping, $use_rss, $blogname, $baseurl, $blogfilename;
	if( ! $use_blodotgsping ) return false;
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", _('Pinging Blo.gs...'), "</h3>\n";
	if( !preg_match('/localhost\//',$baseurl) ) 
	{
		flush();
		$url = get_bloginfo('blogurl');
		$client = new xmlrpc_client('/', 'ping.blo.gs', 80);
		if ($use_rss) 
		{
			$message = new xmlrpcmsg('weblogUpdates.extendedPing',
								 array( new xmlrpcval($blogname), 
								 				new xmlrpcval($url), 
												new xmlrpcval($url), 
												new xmlrpcval( get_bloginfo('rss_url') ) 
											) );
		}
		else 
		{
			$message = new xmlrpcmsg('weblogUpdates.ping', array(new xmlrpcval($blogname), new xmlrpcval($url)));
		}
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		echo "<p>", _('Done.'), "</p>\n</div>\n";
		return($ret);
	} 
	else 
	{
		echo "<p>", _('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}

?>