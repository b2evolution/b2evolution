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
 * pingback(-)
 */
function pingback( $post_pingback, $content, $post_title, $post_url, $post_ID, $blog_ID, $display = true) 
{	// Sending pingback
	// original code by Mort (http://mort.mine.nu:8080)
	global $b2_version;

	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo "<h3>Sending pingbacks...</h3>\n";
	}

	if( ! $post_pingback )
	{
		if( $display ) echo "<p>No pingback to be done.<p>\n";
	}
	else
	{
		$log = debug_fopen('./pingback.log', 'a');
		$post_links = array();
		debug_fwrite($log, 'BEGIN '.time()."\n");
	
		// Variables
		$ltrs = '\w';
		$gunk = '/#~:.?+=&%@!\-';
		$punc = '.:?\-';
		$any = $ltrs.$gunk.$punc;
		$pingback_str_dquote = 'rel="pingback"';
		$pingback_str_squote = 'rel=\'pingback\'';
		$x_pingback_str = 'x-pingback: ';
		$pingback_href_original_pos = 27;
	
		$blogurl = get_bloginfo('blogurl', get_blogparams_by_ID( $blog_ID ) );
		$pagelinkedfrom = gen_permalink( $blogurl, $post_ID );

		if( !empty($post_url) )
		{
			$content = '<a href="'.$post_url.'">'.$post_title.'</a>'.$post_url;
		}

		// Step 1
		// Parsing the post, external links (if any) are stored in the $post_links array
		// This regexp comes straigth from phpfreaks.com
		// http://www.phpfreaks.com/quickcode/Extract_All_URLs_on_a_Page/15.php
		preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);
	
		// Debug
		debug_fwrite($log, 'Post contents:');
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
			$test = parse_url($link_test);
			if (isset($test['query'])) {
				$post_links[] = $link_test;
			} elseif(($test['path'] != '/') && ($test['path'] != '')) {
				$post_links[] = $link_test;
			}
		}
	
		foreach ($post_links as $pagelinkedto)
		{
			if( $display ) echo "<p>Processing: $pagelinkedto<br />\n";
			debug_fwrite($log, 'Processing -- '.$pagelinkedto."\n\n");
	
			$bits = parse_url($pagelinkedto);
			if (!isset($bits['host'])) {
				if( $display ) echo "Couldn't find a hostname for: $pagelinkedto<br />\n";
				debug_fwrite($log, 'Couldn\'t find a hostname for '.$pagelinkedto."\n\n");
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
			if( $display ) echo 'connect to server at ',$host;
			$fp = fsockopen($host, $port, $errno, $errstr, 30);
			if (!$fp) {
				if( $display ) echo "Couldn't open a connection to: $pagelinkedto<br />\n";
				debug_fwrite($log, 'Couldn\'t open a connection to '.$host."\n\n");
				continue;
			}
	
			// Send the GET request
			$request = "GET $path HTTP/1.1\r\nHost: $host\r\nUser-Agent: b2evolution/$b2_version PHP/" . phpversion() . "\r\n\r\n";
			ob_end_flush();
			fputs($fp, $request);
	
			// Start receiving headers and content
			debug_fwrite($log, 'Start receiving headers and content\n');
			$contents = '';
			$headers = '';
			$gettingHeaders = true;
			$found_pingback_server = 0;
			while (!feof($fp)) {
				$line = fgets($fp, 4096);
				// echo "line (".strlen($line)."): [",htmlspecialchars($line),"] <br />\n";
				if (trim($line) == '')  // ligne blanche = fin des headers
				{
					$gettingHeaders = false;
				}
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
					echo "Pingback server found from X-Pingback header: $pingback_server_url<br />\n";
					debug_fwrite($log, "Pingback server found from X-Pingback header @ $pingback_server_url\n");
					$found_pingback_server = 1;
					break;
				}	
				if ($pingback_link_offset_dquote || $pingback_link_offset_squote) 
				{	// on a trouvé dans les données
					$quote = ($pingback_link_offset_dquote) ? '"' : '\'';
					$pingback_link_offset = ($quote=='"') ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
					$pingback_href_pos = strpos($contents, 'href=', $pingback_link_offset);
					$pingback_href_start = $pingback_href_pos+6;
					$pingback_href_end = strpos($contents, $quote, $pingback_href_start);
					$pingback_server_url_len = $pingback_href_end-$pingback_href_start;
					$pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);
					echo "Pingback server found from Pingback <link /> tag: $pingback_server_url<br />\n";
					debug_fwrite($log, "Pingback server found from Pingback <link /> tag @ $pingback_server_url\n");
					$found_pingback_server = 1;
					break;
				}
			}
	
			if(!$found_pingback_server) 
			{
				if( $display )	echo "Pingback server not found in headers and content<br />\n";
				debug_fwrite($log, "Pingback server not found in headers and content\n\n*************************\n\n");
				@fclose($fp);
			} 
			elseif( empty($pingback_server_url) )
			{
				if( $display )	echo "Pingback server URL is empty (may be an internal PHP fgets error)<br />\n";
				debug_fwrite($log, "Pingback server URL is empty\n\n*************************\n\n");
				@fclose($fp);
			}
			else 
			{
				debug_fwrite($log,"\n\nPingback server data\n");
				// Assuming there's a "http://" bit, let's get rid of it
				$host_clear = substr($pingback_server_url, 7);
	
				//  the trailing slash marks the end of the server name
				$host_end = strpos($host_clear, '/');
	
				// Another clear cut
				$host_len = $host_end-$host_start;
				$host = substr($host_clear, 0, $host_len);
				debug_fwrite($log, 'host: '.$host."\n");
	
				// If we got the server name right, the rest of the string is the server path
				$path = substr($host_clear,$host_end);
				debug_fwrite($log, 'path: '.$path."\n\n");
	
				 // Now, the RPC call
				$method = 'pingback.ping';
				if( $display )	echo "Page Linked To: $pagelinkedto<br />\n";
				debug_fwrite($log, 'Page Linked To: '.$pagelinkedto."\n");
				if( $display )	echo "Page Linked From: $pagelinkedfrom<br />\n";
				debug_fwrite($log, 'Page Linked From: '.$pagelinkedfrom."\n");

				$client = new xmlrpc_client($path, $host, 80);
				// $client->setDebug(true);		// fplanque :))
				$message = new xmlrpcmsg($method, array(new xmlrpcval($pagelinkedfrom), new xmlrpcval($pagelinkedto)));
				echo "pinging $host...";
				$result = $client->send($message);

				// Display response
				xmlrpc_displayresult( $result, $log );
				@fclose($fp);
			}
			if( $display )	echo "</p>\n";
		}

		debug_fwrite($log, "\nEND: ".time()."\n****************************\n\r");
		debug_fclose($log);
		if( $display )	echo "<p>Pingbacks done.<p>\n";
	}
	if( $display )	echo "</div>\n";	
}



/* 
 * TEMPLATE FUNCTIONS:
 */



/*****
 * PingBack tags 
 *****/

function pingback_number($zero='no pingback', $one='1 pingback', $more='% pingbacks') {
	global $id, $tablecomments, $tb, $querycount, $cache_pingbacknumber, $use_cache;
	$number = generic_ctp_number($id, 'pingbacks');
	if ($number == 0) {
		$blah = $zero;
	} elseif ($number == 1) {
		$blah = $one;
	} elseif ($number  > 1) {
		$n = $number;
		$more=str_replace('%', $n, $more);
		$blah = $more;
	}
	echo $blah;
}

/*
 * Displays link to the pingback page
 */
function pingback_link($file='',$c=0,$tb=0) 
{
	global $id;
	global $querystring_start, $querystring_equal, $querystring_separator;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo $file.$querystring_start.'p'.$querystring_equal.$id;
	if( $c == 1 )
	{	// include comments // fplanque: added
		echo $querystring_separator.'c'.$querystring_equal.'1';
	}
	if( $tb == 1 )
	{	// include trackback // fplanque: added
		echo $querystring_separator.'tb'.$querystring_equal.'1';
	}
	echo $querystring_separator.'pb'.$querystring_equal.'1#pingbacks';
}

function pingback_popup_link($zero='no pingback', $one='1 pingback', $more='% pingbacks', $CSSclass='')
{
	global $blog, $id, $b2pingbackpopupfile, $b2commentsjavascript;
	global $querystring_start, $querystring_equal, $querystring_separator;
	echo '<a href="';
	if ($b2commentsjavascript) {
		echo get_bloginfo('blogurl').$querystring_start.'template'.$querystring_equal.'popup'.
					$querystring_separator.'p'.$querystring_equal.$id.
					$querystring_separator.'pb'.$querystring_equal.'1';
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



/***** // PingBack tags *****/



?>