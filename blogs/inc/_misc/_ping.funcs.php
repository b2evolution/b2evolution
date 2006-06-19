<?php
/**
 * This file implements functions to ping external sites/directories.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE.
 * @author vegarg
 *
 * @todo Make these plugins
 * @todo Link messages to HTML-Anchor tags, e.g. "Pinging <a href="%s">b2evolution.net</a>", to the site where the update can be seen.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * pings b2evolution.net
 */
function pingb2evonet( & $blogparams, $post_ID, $post_title, $display = true )
{
	global $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;
	global $baseurl;

	if( !get_bloginfo('pingb2evonet',$blogparams) )
	{
		return false;
	}
	// echo 'ping b2evo.net';
	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Pinging b2evolution.net...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) || ( $evonetsrv_host == 'localhost' ) )
	{	// Local install can only ping to local test server
		// Construct XML-RPC client:
		$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port);
		$client->debug = ($debug && $display);

		$message = new xmlrpcmsg( 'b2evo.ping', array(
															new xmlrpcval('id') ,			// Reserved
															new xmlrpcval('user'),		// Reserved
															new xmlrpcval('pass'),		// Reserved
															new xmlrpcval(bloginfo('name', 'xml', false, $blogparams)),
															new xmlrpcval(bloginfo('blogurl', 'xml', false, $blogparams)),
															new xmlrpcval(bloginfo('locale', 'xml', false, $blogparams)),
															new xmlrpcval(format_to_output( $post_title, 'xml' ))
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result, $display );
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
	global $baseurl;
	if( !get_bloginfo('pingweblogs',$blogparams) ) return false;
	// echo 'ping Weblogs.com';
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
		$ret = xmlrpc_displayresult( $result, $display );
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
 * pingBlogs(-)
 *
 * pings Blo.gs
 */
function pingBlogs( & $blogparams, $display = true )
{
	global $use_blodotgsping, $use_rss, $blogname, $baseurl, $blogfilename;


	// When pinging http://blo.gs, use extended ping to RSS?
	$use_rss = 1;

	
	if( !get_bloginfo('pingblodotgs', $blogparams) ) return false;
	// echo 'ping Blo.gs';
	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo "<h3>", T_('Pinging Blo.gs...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#',$baseurl) )
	{
		flush();
		$url = get_bloginfo('blogurl');
		$client = new xmlrpc_client('/', 'ping.blo.gs', 80);
		if( $use_rss )
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
		$ret = xmlrpc_displayresult( $result, $display );
		if( $display ) echo "<p>", T_('Done.'), "</p>\n</div>\n";
		return($ret);
	}
	else
	{
		if( $display ) echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}

/**
 * Pings technorati.com
 *
 * Code by {@link http://isaacschlueter.com/ Isaac Schlueter}
 * Adapted from the b2 ping instructions listed at
 * {@link http://developers.technorati.com/wiki/pingConfigurations}.
 */
function pingTechnorati(& $blogparams, $display = true )
{
	global $baseurl, $blogfilename;

	if( !get_bloginfo('pingtechnorati', $blogparams) ) return false;
	// echo 'ping technorati';

	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Pinging technorati.com...'), "</h3>\n";
	}

	if( !preg_match( '#^http://localhost[/:]#', $baseurl) )
	{
		$client = new xmlrpc_client("/rpc/ping", "rpc.technorati.com", 80);
		$message = new xmlrpcmsg("weblogUpdates.ping",
										array(new xmlrpcval(get_bloginfo('name', $blogparams)),
													new xmlrpcval(get_bloginfo('blogurl', $blogparams)) ));
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result, $display );
		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
		return(true);
	}
	else
	{
		if( $display ) echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}

/*
 * $Log$
 * Revision 1.3  2006/06/19 16:49:10  fplanque
 * minor
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.9  2006/02/10 20:37:10  blueyed
 * *** empty log message ***
 *
 * Revision 1.8  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.7  2005/11/18 18:32:42  fplanque
 * Fixed xmlrpc logging insanity
 * (object should have been passed by reference but you can't pass NULL by ref)
 * And the code was geeky/unreadable anyway.
 *
 * Revision 1.6  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/08/08 22:51:57  blueyed
 * todo
 *
 * Revision 1.4  2005/05/25 18:31:01  fplanque
 * implemented email notifications for new posts
 *
 * Revision 1.3  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.28  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 * Revision 1.9  2004/2/1 20:6:9  vegarg
 * Added technorati.com ping support.
 */
?>