<?php
/**
 * This file implements functions that got obsolete with version 2.0.
 *
 * For performance reasons you should delete (or rename) this file, but if you use some
 * of these functions in your skin or hack you'll have to leave it for obvious compatibility
 * reasons.
 * Of course, this file will not be (automatically) included at some point, so please
 * upgrade your skins and hacks.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Blog funcs

/**
 * Get Blog for specified ID
 *
 * @todo on a heavy multiblog system, cache them one by one...
 * @todo move over to BlogCache?!
 *
 * @param integer ID of Blog we want
 */
function Blog_get_by_ID( $blog_ID )
{
	global $cache_blogs;

	if( $blog_ID < 1 ) debug_die( 'No blog is selected!' );

	if( empty($cache_blogs[$blog_ID]) )
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) debug_die( T_('Requested blog does not exist!') );

	return new Blog( $cache_blogs[$blog_ID] ); // COPY !
}








// end blog funcs




/*
 * autoquote(-)
 */
function autoquote( & $string )
{
	if( strpos( $string, "'" ) !== 0 )
	{ // no quote at position 0
		$string = "'".$string."'";
	}
}




// xmlrpc:

$pingback_ping_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString));

$pingback_ping_doc = 'gets a pingback and registers it as a comment prefixed by &lt;pingback /&gt;';



/**
 * pingback_ping(-)
 *
 * This is the pingback receiver!
 *
 * original code by Mort (http://mort.mine.nu:8080)
 * fplanque: every time you come here you can correct a couple of bugs...
 */
function pingback_ping( $m )
{
	global $DB, $notify_from, $xmlrpcerruser;
	global $baseurl;
	global $localtimenow, $Messages;

	$log = debug_fopen('./xmlrpc.log', 'w');

	$title = '';

	$pagelinkedfrom = $m->getParam(0);
	$pagelinkedfrom = $pagelinkedfrom->scalarval();

	$pagelinkedto = $m->getParam(1);
	$pagelinkedto = $pagelinkedto->scalarval();

	$pagelinkedfrom = str_replace('&amp;', '&', $pagelinkedfrom);
	$pagelinkedto = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedto);

	debug_fwrite($log, 'BEGIN '.time().' - '.date('Y-m-d H:i:s')."\n\n");
	debug_fwrite($log, 'Page linked from: '.$pagelinkedfrom."\n");
	debug_fwrite($log, 'Page linked to: '.$pagelinkedto."\n");

	$messages = array(
		htmlentities("Pingback from ".$pagelinkedfrom." to ".$pagelinkedto." registered. Keep the web talking! :-)"),
		htmlentities("We can't find the URL to the post you are trying to link to in your entry. Please check how you wrote the post's permalink in your entry."),
		htmlentities("We can't find the post you are trying to link to. Please check the post's permalink.")
	);

	$resp_message = $messages[0];

	// Check if the page linked to is in our site
	// fplanque: TODO: coz we don't have a single siteurl any longer
	$pos1 = strpos( $pagelinkedto, preg_replace( '#^https?://(www\.)?#', '', $baseurl ));
	if( $pos1 !== false )
	{
		// let's find which post is linked to
		$urltest = parse_url($pagelinkedto);
		if( preg_match('#/p([0-9]+)#', $urltest['path'], $match) )
		{ // the path defines the post_ID (yyyy/mm/dd/pXXXX)
			$post_ID = $match[1];
			$way = 'from the path (1)';
		}
		elseif (preg_match('#p/[0-9]+#', $urltest['path'], $match) )
		{
			// the path defines the post_ID (archives/p/XXXX)
			$blah = explode('/', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the path (2)';
		}
		elseif (preg_match('#p=[0-9]+#', $urltest['query'], $match)	 )
		{
			// the querystring defines the post_ID (?p=XXXX)
			$blah = explode('=', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the querystring';
		}
		elseif (isset($urltest['fragment']))
		{
			// an #anchor is there, it's either...
			if (intval($urltest['fragment']))
			{ // ...an integer #XXXX (simpliest case)
				$post_ID = $urltest['fragment'];
				$way = 'from the fragment (numeric)';
			}
			elseif (is_string($urltest['fragment']))
			{ // ...or a string #title, a little more complicated
				$title = preg_replace('/[^a-zA-Z0-9]/', '.', $urltest['fragment']);
				$sql = "SELECT post_ID
								FROM T_posts
								WHERE post_title RLIKE '$title'";
				$blah = $DB->get_row( $sql, ARRAY_A );
				if( $DB->error )
				{ // DB error
					return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
				}
				$post_ID = $blah['post_ID'];
				$way = 'from the fragment (title)';
			}
		}
		else
		{
			$post_ID = -1;
		}

		debug_fwrite($log, "Found post ID $way: $post_ID\n");

		$postdata = get_postdata($post_ID);
		$blog = $postdata['Blog'];
		xmlrpc_debugmsg( 'Blog='.$blog );

		$BlogCache = & get_Cache( 'BlogCache' );
		$tBlog = & $BlogCache->get_by_ID( $blog );
		if( !$tBlog->get('allowpingbacks') )
		{
			return new xmlrpcresp(new xmlrpcval('Sorry, this weblog does not allow you to pingback its posts.'));
		}


		// Check that post exists
		$sql = 'SELECT post_creator_user_ID
						FROM T_posts
						WHERE post_ID = '.$post_ID;
		$rows = $DB->get_results( $sql );
		if( $DB->error )
		{ // DB error
			return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
		}

		if(count($rows))
		{
			debug_fwrite($log, 'Post exists'."\n");

			// Let's check that the remote site didn't already pingback this entry
			$sql = "SELECT * FROM T_comments
							WHERE comment_post_ID = $post_ID
								AND comment_author_url = '".$DB->escape(preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedfrom))."'
								AND comment_type = 'pingback'";
			$rows = $DB->get_results( $sql );
			if( $DB->error )
			{ // DB error
				return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
			}

			xmlrpc_debugmsg( $sql.' Already found='.count($rows) );

			if( ! count($rows) )
			{
				// very stupid, but gives time to the 'from' server to publish !
				sleep(1);

				// Let's check the remote site
				$fp = @fopen($pagelinkedfrom, 'r');

				$puntero = 4096;
				$linea = "";
				while($fbuffer = fread($fp, $puntero))
				{ // fplanque: dis is da place where da bug was >:[
					$linea .= $fbuffer;		// dis is da fix!
				}
				fclose($fp);
				$linea = strip_tags($linea, '<a><title>');

				preg_match('|<title>([^<]*?)</title>|is', $linea, $matchtitle);

				// You never know what kind of crap you may have gotten on the web...
				$linea = convert_chars( $linea, 'html' );

				$pagelinkedto = convert_chars( $pagelinkedto, 'html' );
				$linea = strip_all_but_one_link($linea, $pagelinkedto, $log);
				// fplanque: removed $linea = preg_replace('#&([^amp\;])#is', '&amp;$1', $linea);

				debug_fwrite($log, 'SECOND SEARCH '.$pagelinkedto.' in text block #####'.$linea."####\n\n");
				$pos2 = strpos($linea, $pagelinkedto);
				$pos3 = strpos($linea, str_replace('http://www.', 'http://', $pagelinkedto));
				if (is_integer($pos2) || is_integer($pos3))
				{
					debug_fwrite($log, 'The page really links to us :)'."\n");
					$pos4 = (is_integer($pos2)) ? $pos2 : $pos3;
					$start = $pos4-100;
					$context = substr($linea, $start, 250);
					$context = str_replace("\n", ' ', $context);
					$context = str_replace('&amp;', '&', $context);

					global $admin_url, $comments_allowed_uri_scheme;

					$pagelinkedfrom = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedfrom);
					$title = (!strlen($matchtitle[1])) ? $pagelinkedfrom : $matchtitle[1];
					$original_context = $context;
					$context = '[...] '.trim($context).' [...]';

					// CHECK and FORMAT content
					if( $error = validate_url( $pagelinkedfrom, $comments_allowed_uri_scheme ) )
					{
						$Messages->add( T_('Supplied URL is invalid: ').$error );
					}
					$context = format_to_post($context,1,1);

					if( ! ($message = $Messages->get_string( 'Cannot insert pingback, please correct these errors:', '' )) )
					{ // No validation error:
						$original_pagelinkedfrom = $pagelinkedfrom;
						$original_title = $title;
						$title = strip_tags(trim($title));
						$now = date('Y-m-d H:i:s', $localtimenow );
						$sql = "INSERT INTO T_comments( comment_post_ID, comment_type, comment_author,
																								comment_author_url, comment_date, comment_content)
										VALUES( $post_ID, 'pingback', '".$DB->escape($title)."',
														'".$DB->escape($pagelinkedfrom)."', '$now',
														'".$DB->escape($context)."')";
						$DB->query( $sql );
						if( $DB->error )
						{ // DB error
							return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
						}

						/*
						 * New pingback notification:
						 */
						$UserCache = & get_Cache( 'UserCache' );
						$AuthorUser = & $UserCache->get_by_ID( $postdata['Author_ID'] );
						if( $AuthorUser->get( 'notify' ) )
						{ // Author wants to be notified:
							locale_temp_switch( $AuthorUser->get( 'locale' ) );

							$recipient = $AuthorUser->get( 'email' );
							$subject = sprintf( T_('New pingback on your post #%d "%s"'), $post_ID, $postdata['Title'] );

							$comment_Blog = & $BlogCache->get_by_ID( $blog );

							$notify_message  = sprintf( T_('New pingback on your post #%d "%s"'), $post_ID, $postdata['Title'] )."\n";
							$notify_message .= url_add_param( $comment_Blog->get('blogurl'), "p=$post_ID&pb=1\n\n", '&' );
							$notify_message .= T_('Website'). ": $original_title\n";
							$notify_message .= T_('Url'). ": $original_pagelinkedfrom\n";
							$notify_message .= T_('Excerpt'). ": \n[...] $original_context [...]\n\n";
							$notify_message .= T_('Edit/Delete').': '.$admin_url.'?ctrl=browse&amp;blog='.$blog.'&p='.$post_ID."&c=1\n\n";

							send_mail( $recipient, $subject, $notify_message, $notify_from );

							locale_restore_previous();
						}
					}
				}
				else
				{ // URL pattern not found - page doesn't link to us:
					debug_fwrite($log, 'The page doesn\'t link to us!'."\n");
					$resp_message = "Page linked to: $pagelinkedto\nPage linked from: $pagelinkedfrom\nTitle: $title\n\n".$messages[1];
				}
			}
			else
			{ // We already have a Pingback from this URL
				$resp_message = "Sorry, you already did a pingback to $pagelinkedto from $pagelinkedfrom.";
			}
		}
		else
		{ // Post_ID not found
			$resp_message = $messages[2];
			debug_fwrite($log, 'Post doesn\'t exist'."\n");
		}
	} // / in siteurl

	// xmlrpc_debugmsg( 'Okay'.$messages[0] );

	return new xmlrpcresp(new xmlrpcval($resp_message));
}


// end xmlrpc

/**
 * Sending pingback
 *
 * original code by Mort (http://mort.mine.nu:8080)
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
	global $app_name, $app_version, $debug;

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

		$ItemCache = & get_Cache( 'ItemCache' );
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
			$fp = fsockopen($host, $port, $errno, $errstr, 20); // this timeout is just for setting up the socket
			if (!$fp)
			{
				if( $display ) echo T_('Couldn\'t open a connection to:'), ' ', $pagelinkedto, "<br />\n";
				debug_fwrite($log, T_('Couldn\'t open a connection to:').' '.$host."\n\n");
				continue;
			}
			echo "<br />\n";

			// Set timeout for data:
			if( function_exists('stream_set_timeout') )
			{
				stream_set_timeout( $fp, 20 ); // PHP 4.3.0
			}
			else
			{
				socket_set_timeout( $fp, 20 ); // PHP 4
			}

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

				load_funcs( '_misc/ext/_xmlrpc.php' );
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


/**
 * vegarg: small bug when using $more_file fixed
 */
function link_pages( $before='#', $after='#', $next_or_number='number', $nextpagelink='#', $previouspagelink='#',
										$pagelink='%d', $more_file='')
{
	global $id, $page, $numpages, $multipage, $more;

	if( $before == '#' ) $before = '<p>'.T_('Pages:').' ';
	if( $after == '#' ) $after = '</p>';
	if( $nextpagelink == '#' ) $nextpagelink = T_('Next page');
	if( $previouspagelink == '#' ) $previouspagelink = T_('Previous page');

	if ($more_file != '')
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	if( $multipage )
	{
		echo $before;
		if( $next_or_number == 'number' )
		{
			for ($i = 1; $i < ($numpages+1); $i = $i + 1)
			{
				$j = str_replace('%d', $i, $pagelink);
				echo ' ';
				if( ($i != $page) || ( (!$more) && ($page==1) ))
					echo '<a href="'.url_add_param($file, 'p='.$id.'&amp;more=1&amp;page='.$i).'">';
				echo $j;
				if( ($i != $page) || ( (!$more) && ($page==1) ))
					echo '</a>';
			}
		}
		else
		{
			$i = $page - 1;
			if( $i )
				echo ' <a href="'.url_add_param($file, 'p='.$id.'&amp;page='.$i).'">'.$previouspagelink.'</a>';

			$i = $page+1;

			if( $i <= $numpages )
				echo ' <a href="'.url_add_param($file, 'p='.$id.'&amp;page='.$i).'">'.$nextpagelink.'</a>';
		}
		echo $after;
	}
}




/**
 * Links to previous/next page
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function posts_nav_link( $sep=' :: ', $prelabel='#', $nxtlabel='#' )
{
	global $p, $Settings, $MainList;

	if( !empty( $MainList->sql ) && empty($p) )
	{
		$max_paged = $MainList->total_pages;
		if( $max_paged > 1 )
		{
			previous_posts_link( $prelabel );
			echo htmlspecialchars($sep);
			next_posts_link( $nxtlabel, $max_paged );
		}
	}
}


/**
 * Display a link to previous page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function previous_posts_link( $label='#' )
{
	global $Settings, $p, $paged, $Blog;

	if( $label == '#' ) $label = '<< '.T_('Previous Page');

	if( empty($p) && ($paged > 1) )
	{
		/*
		// fplanque>> this code was supposed to make this work on multiple domains, but it breaks stub files !
		// blueyed>> it looks like using $Blog->get('url') should do it.
		$siteurl = $Blog->get( 'siteurl', 'raw');
		if ( !empty( $siteurl ) )
		{
			$parsed_url = parse_url( $Blog->get( 'siteurl', 'raw' ) );
			$page = $parsed_url['scheme'] . '://' .
					$parsed_url['host'] .
					$parsed_url['path'];
		}
		*/

		echo '<a href="';
		echo previous_posts( );
		echo '">'.htmlspecialchars($label).'</a>';
	}
}



/**
 * Display a link to next page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function next_posts_link($label='#', $max_page=0 )
{
	global $p, $paged, $result, $Settings, $MainList, $Blog, $Item;

	if( $label == '#' ) $label = T_('Next Page').' >>';

	if (!$max_page) $max_page = $MainList->get_max_paged();
	if (!$paged) $paged = 1;
	$nextpage = intval($paged) + 1;
	if (empty($p) && (empty($paged) || $nextpage <= $max_page))
	{
		/*
		// fplanque>> this code was supposed to make this work on multiple domains, but it breaks stub files !
		// blueyed>> it looks like using $Blog->get('url') should do it.
		$siteurl = $Blog->get( 'siteurl', 'raw');
		if ( !empty( $siteurl ) )
		{
			$parsed_url = parse_url( $Blog->get( 'siteurl', 'raw' ) );
			$page = $parsed_url['scheme'] . '://' .
					$parsed_url['host'] .
					$parsed_url['path'];
		}
		*/

		echo '<a href="';
		echo next_posts( $max_page );
		echo '">'. htmlspecialchars($label) .'</a>';
	}
}



/**
 * Display a link to previous page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function previous_posts( )
{
	global $p, $paged, $Settings, $edited_Blog, $Blog, $generating_static;

	if( empty($p) )
	{
		$nextpage = intval($paged) - 1;
		if ($nextpage < 1) $nextpage = 1;

		if( !isset($generating_static) && isset($Blog) )
		{ // We are not generating a static page here:
			echo regenerate_url( 'blog,paged', 'paged='.$nextpage, $Blog->get('dynurl') );
		}
		elseif( isset($generating_static) && isset($edited_Blog) )
		{ // We are generating a static page
			echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage );
		}
		else
		{
			debug_die( 'unhandled previous page' );
		}
	}
}



/**
 * Display a link to next page of posts
 *
 * Note: remove this tag from skin template if you don't want this functionality
 *
 * @todo move to ItemList
 */
function next_posts( $max_page = 0 )
{
	global $p, $paged, $Settings, $edited_Blog, $generating_static;

	/**
	 * @var Blog
	 */
	global $Blog;

	if( empty($p) )
	{
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (!$max_page || $max_page >= $nextpage)
		{
			if( !isset($generating_static) && isset($Blog) )
			{ // We are not generating a static page here:
				echo regenerate_url( 'blog,paged', 'paged='.$nextpage, $Blog->get('dynurl') );
			}
			elseif( isset($generating_static) && isset($edited_Blog) )
			{ // We are generating a static page
				echo url_add_param( $edited_Blog->get('dynurl'), 'paged='.$nextpage );
			}
			else
			{
				debug_die( 'unhandled next page' );
			}
		}
	}
}

/**
 * the_weekday(-)
 *
 *
 */
function the_weekday()
{
	global $weekday,$id,$postdata;
	$the_weekday = T_($weekday[mysql2date('w', $postdata['Date'])]);
	echo $the_weekday;
}



/**
 * the_weekday_date(-)
 *
 *
 */
function the_weekday_date($before='',$after='')
{
	global $weekday,$id,$postdata,$day,$previousweekday;
	$the_weekday_date = '';
	if ($day != $previousweekday) {
		$the_weekday_date .= $before;
		$the_weekday_date .= T_($weekday[mysql2date('w', $postdata['Date'])]);
		$the_weekday_date .= $after;
		$previousweekday = $day;
	}

	echo $the_weekday_date;
}



?>