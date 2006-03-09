<?php
/**
 * This file implements Pingback functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
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
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Sending pingback
 *
 * original code by Mort (http://mort.mine.nu:8080)
 *
 * {@internal pingback(-)}}
 */
function pingback(
	$post_pingback,
	$content,
	$post_title,
	$post_url,
	$post_ID,
	& $blogparams,
	$display = true)
{
	global $app_name, $app_version, $debug, $ItemCache;

	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Sending pingbacks...'), '</h3>', "\n";
	}

	if( ! $post_pingback )
	{
		if( $display ) echo '<p>', T_('No pingback to be done.'), '</p>', "\n";
	}
	else
	{
		$log = debug_fopen('./pingback.log', 'a');
		$post_links = array();
		debug_fwrite($log, T_('BEGIN').' '.time()."\n");

		// Variables
		$ltrs = '\w';
		$gunk = '/#~:.?+=&%@!\-';
		$punc = '.:?\-';
		$any = $ltrs.$gunk.$punc;
		$pingback_str_dquote = 'rel="pingback"';
		$pingback_str_squote = 'rel=\'pingback\'';
		$x_pingback_str = 'x-pingback: ';
		$pingback_href_original_pos = 27;

		$Item = & $ItemCache->get_by_ID( $post_ID );
		$pagelinkedfrom = $Item->get_permanent_url();

		if( !empty($post_url) )
		{
			$content = '<a href="'.$post_url.'">'.$post_title.'</a>'.$post_url;
		}

		// Step 1
		// Parsing the post, external links (if any) are stored in the $post_links array
		// This regexp comes straigth from phpfreaks.com
		// http://www.phpfreaks.com/quickcode/Extract_All_URLs_on_a_Page/15.php
		// preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);
		// fplanque: \b is for word boundary
		// trailing x is to ignore whitespace
		// we need to simplify and allow ; in the URL
		 preg_match_all("{\b http:// [0-9A-Za-z:/_~+\-%.?&=;]+}x", $content, $post_links_temp);

		// Debug
		debug_fwrite($log, T_('Post contents').':');
		debug_fwrite($log, $content."\n");

		// Step 2.
		// Walking thru the links array
		// first we get rid of links pointing to sites, not to specific files
		// Example:
		// http://dummy-weblog.org
		// http://dummy-weblog.org/
		// http://dummy-weblog.org/post.php
		// We don't wanna ping first and second types, even if they have a valid <link/>

		foreach($post_links_temp[0] as $link_test)
		{
			//echo "testing: $link_test <br />";
			$test = parse_url($link_test);
			if( $test['scheme'] == 'http' )
			{
				if (isset($test['query']))
				{
					$post_links[] = $link_test;
				}
				elseif(($test['path'] != '/') && ($test['path'] != ''))
				{
					$post_links[] = $link_test;
				}
			}
		}

		foreach ($post_links as $pagelinkedto)
		{
			if( $display ) echo '<p>', T_('Processing:'), ' ', $pagelinkedto, "<br />\n";
			debug_fwrite($log, T_('Processing:').' '.$pagelinkedto."\n\n");

			$bits = parse_url($pagelinkedto);
			if (!isset($bits['host'])) {
				if( $display ) echo T_('Couldn\'t find a hostname for:'),' ',$pagelinkedto, "<br />\n";
				debug_fwrite($log, T_('Couldn\'t find a hostname for:').' '.$pagelinkedto."\n\n");
				continue;
			}
			$host = $bits['host'];
			$path = isset($bits['path']) ? $bits['path'] : '';
			if (isset($bits['query'])) {
				$path .= '?'.$bits['query'];
			}
			if (!$path) {
				$path = '/';
			}
			$port = isset($bits['port']) ? $bits['port'] : 80;

			// Try to connect to the server at $host
			if( $display ) echo T_('Connect to server at:'), ' ',$host;
			$fp = fsockopen($host, $port, $errno, $errstr, 30);
			if (!$fp)
			{
				if( $display ) echo T_('Couldn\'t open a connection to:'), ' ', $pagelinkedto, "<br />\n";
				debug_fwrite($log, T_('Couldn\'t open a connection to:').' '.$host."\n\n");
				continue;
			}
			echo "<br />\n";

			// Send the GET request
			$request = "GET $path HTTP/1.1\r\nHost: $host\r\nUser-Agent: $app_name/$app_version PHP/" . phpversion() . "\r\n\r\n";
			ob_end_flush();
			fputs($fp, $request);

			// Start receiving headers and content
			debug_fwrite($log, T_('Start receiving headers and content')."\n");
			$contents = '';
			$headers = '';
			$gettingHeaders = true;
			$found_pingback_server = 0;
			while (!feof($fp))
			{
				$line = fgets($fp, 4096);
				// echo "line (".strlen($line)."): [",htmlspecialchars($line),"] <br />\n";
				if (trim($line) == '')  // ligne blanche = fin des headers
				{
					$gettingHeaders = false;
				}
				$pingback_link_offset_dquote = 0;
				$pingback_link_offset_squote = 0;
				$x_pingback_header_offset = 0;
				if (!$gettingHeaders)
				{
					// echo 'CONTENT';
					$contents .= trim($line)."\n";
					// localise rel="'pingback"' :
					$pingback_link_offset_dquote = strpos($contents, $pingback_str_dquote);
					$pingback_link_offset_squote = strpos($contents, $pingback_str_squote);
				}
				else
				{
					// echo 'HEADER';
					$headers .= trim($line)."\n";
					$x_pingback_header_offset = strpos(strtolower($headers), $x_pingback_str);
				}
				if ($x_pingback_header_offset)
				{	// on a trouvé dans les headers
					preg_match('#x-pingback: (.+)#is', $headers, $matches);
					$pingback_server_url = trim($matches[1]);
					echo T_('Pingback server found from X-Pingback header:'), ' ', $pingback_server_url, "<br />\n";
					debug_fwrite($log, T_('Pingback server found from X-Pingback header:').' '.$pingback_server_url."\n");
					$found_pingback_server = 1;
					break;
				}
				if( $pingback_link_offset_dquote || $pingback_link_offset_squote )
				{	// on a trouvé dans les données
					$quote = ($pingback_link_offset_dquote) ? '"' : '\'';
					$pingback_link_offset = ($quote=='"') ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
					$pingback_href_pos = strpos($contents, 'href=', $pingback_link_offset);
					$pingback_href_start = $pingback_href_pos+6;
					$pingback_href_end = strpos($contents, $quote, $pingback_href_start);
					$pingback_server_url_len = $pingback_href_end-$pingback_href_start;
					$pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);
					echo T_('Pingback server found from Pingback <link /> tag:'), ' ', $pingback_server_url, "<br />\n";
					debug_fwrite($log,  T_('Pingback server found from Pingback <link /> tag:').' '.$pingback_server_url."\n");
					$found_pingback_server = 1;
					break;
				}
			}

			if(!$found_pingback_server)
			{
				if( $display )	echo T_('Pingback server not found in headers and content'), "<br />\n";
				debug_fwrite($log, T_('Pingback server not found in headers and content'). "\n\n*************************\n\n");
				@fclose($fp);
			}
			elseif( empty($pingback_server_url) )
			{
				if( $display )	echo T_('Pingback server URL is empty (may be an internal PHP fgets error)'), "<br />\n";
				debug_fwrite($log, T_('Pingback server URL is empty (may be an internal PHP fgets error)'). "\n\n*************************\n\n");
				@fclose($fp);
			}
			else
			{
				debug_fwrite($log,"\n\n". T_('Pingback server data'). "\n");

				$parsed_url = parse_url( $pingback_server_url );
				debug_fwrite($log, 'host: '.$parsed_url['host']."\n");
				$port = isset($parsed_url['port']) ? $parsed_url['port'] : 80;
				debug_fwrite($log, 'port: '.$port."\n");
				debug_fwrite($log, 'path: '.$parsed_url['path']."\n\n");

				 // Now, the RPC call
				$method = 'pingback.ping';
				if( $display )	echo T_('Page Linked To:'), " $pagelinkedto<br />\n";
				debug_fwrite($log, T_('Page Linked To:').' '.$pagelinkedto."\n");
				if( $display )	echo T_('Page Linked From:'), " $pagelinkedfrom<br />\n";
				debug_fwrite($log, T_('Page Linked From:').' '.$pagelinkedfrom."\n");

				$client = new xmlrpc_client( $parsed_url['path'], $parsed_url['host'], $port);
				$client->setDebug( $debug );
				$message = new xmlrpcmsg($method, array(new xmlrpcval($pagelinkedfrom), new xmlrpcval($pagelinkedto)));
				printf( T_('Pinging %s...')."<br />\n", $host );
				$result = $client->send($message);

				// Display response
				$ret = xmlrpc_displayresult( $result, true, $log );
				@fclose($fp);
			}
			if( $display )	echo "</p>\n";
		}

		debug_fwrite($log, "\n". T_('END'). ": ".time()."\n****************************\n\r");
		debug_fclose($log);
		if( $display )	echo "<p>", T_('Pingbacks done.'), "<p>\n";
	}
	if( $display )	echo "</div>\n";
}



/*
 * TEMPLATE FUNCTIONS:
 */



/*****
 * Pingback tags
 *****/

/**
 * pingback_number(-)
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function pingback_number($zero='#', $one='#', $more='#', $post_ID = NULL )
{
	if( $zero == '#' ) $zero = T_('Pingback (0)');
	if( $one == '#' ) $one = T_('Pingback (1)');
	if( $more == '#' ) $more = T_('Pingbacks (%d)');

	if( empty( $post_ID ) )
	{
		global $id;
		$post_ID = $id;
	}
	$number = generic_ctp_number($post_ID, 'pingbacks');
	if ($number == 0) {
		$blah = $zero;
	} elseif ($number == 1) {
		$blah = $one;
	} elseif ($number  > 1) {
		$n = $number;
		$more = str_replace('%d', $n, $more);
		$blah = $more;
	}
	echo $blah;
}

/**
 * Displays link to the pingback page
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function pingback_link($file='',$c=0,$tb=0)
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo url_add_param( $file, 'p='.$id );
	if( $c == 1 )
	{	// include comments // fplanque: added
		echo '&amp;c=1';
	}
	if( $tb == 1 )
	{	// include trackback // fplanque: added
		echo '&amp;tb=1';
	}
	echo '&amp;pb=1#pingbacks';
}

/**
 *
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function pingback_popup_link($zero='#', $one='#', $more='#', $CSSclass='')
{
	global $blog, $id, $b2pingbackpopupfile, $b2commentsjavascript;
	echo '<a href="';
	if ($b2commentsjavascript) {
		echo url_add_param( get_bloginfo('blogurl'), 'template=popup&amp;p='.$id.'&amp;pb=1' );
		echo '" onclick="b2open(this.href); return false"';
	} else {
		// if comments_popup_script() is not in the template, display simple comment link
		pingback_link();
		echo '"';
	}
	if (!empty($CSSclass)) {
		echo ' class="'.$CSSclass.'"';
	}
	echo '>';
	pingback_number($zero, $one, $more);
	echo '</a>';
}



/***** // Pingback tags *****/

/*
 * $Log$
 * Revision 1.2  2006/03/09 22:29:59  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.11  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.10  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.9  2005/12/11 19:59:51  blueyed
 * Renamed gen_permalink() to get_permalink()
 *
 * Revision 1.8  2005/12/05 18:17:19  fplanque
 * Added new browsing features for the Tracker Use Case.
 * Revision 1.7  2005/11/18 18:32:42  fplanque
 * Fixed xmlrpc logging insanity
 * (object should have been passed by reference but you can't pass NULL by ref)
 * And the code was geeky/unreadable anyway.
 *
 * Revision 1.6  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.4  2004/12/17 20:41:14  fplanque
 * cleanup
 *
 * Revision 1.3  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.25  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>