<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */


/*
 * trackbacks(-)
 *
 * Do multiple trackbacks
 *
 * fplanque: added
 */
function trackbacks( $post_trackbacks, $content, $post_title, $post_ID )
{
	echo "<div class=\"panelinfo\">\n";
	echo "<h3>", T_('Sending trackbacks...'), "</h3>\n";
	if(empty($post_trackbacks)) 
	{
		echo "<p>", T_('No trackback to be sent.'), "</p>\n";
	}
	else
	{
		$excerpt = (strlen(strip_tags($content)) > 255) ? substr(strip_tags($content), 0, 252).'...' : strip_tags($content);
		$excerpt = stripslashes($excerpt);
		echo "<p>", T_('Excerpt to be sent:'), " $excerpt</p>\n";
		$trackback_urls = split('( )+', $post_trackbacks,10);		// fplanque: ;
		foreach($trackback_urls as $tb_url) 
		{	// trackback each url:
			$tb_url = trim($tb_url);
			if( empty( $tb_url ) ) continue;
			trackback($tb_url, stripslashes($post_title), $excerpt, $post_ID);
		}
		echo "<p>", T_('Trackbacks done.'), "</p>\n";
	}
	echo "</div>\n";
}

/*
 * trackback(-)
 *
 * sending Trackback to single URL:
 * TODO: add autodiscovery
 */
function trackback($trackback_url, $title, $excerpt, 
					$ID) // post ID 
{	
	echo "<p>", T_('Sending trackback to:'), " $trackback_url ...\n";

	$title = urlencode($title);
	$excerpt = urlencode(stripslashes($excerpt));
	$blog_name = urlencode(get_bloginfo('name'));
	$url = gen_permalink( get_bloginfo('blogurl'), $ID );
	// dis is the trackback stuff to be sent:
	$query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	// echo "url:$trackback_url<br>$sending:$query_string<br />";
	if (strstr($trackback_url, '?')) 
	{
		echo '[get]';
		$trackback_url .= "&".$query_string;;
		flush();
		$fp = fopen($trackback_url, 'r');
		$result = fread($fp, 4096);
		fclose($fp);
/* debug code
		$debug_file = 'trackback.log';
		$fp = fopen($debug_file, 'a');
		fwrite($fp, "\n*****\nTrackback URL query:\n\n$trackback_url\n\nResponse:\n\n");
		fwrite($fp, $result);
		fwrite($fp, "\n\n");
		fclose($fp);
*/

	} 
	else
	{
		echo '[post]';
		$trackback_url = parse_url($trackback_url);
		$port = isset($trackback_url['port']) ? $trackback_url['port'] : 80;
		$http_request  = 'POST '.$trackback_url['path']." HTTP/1.0\r\n";
		$http_request .= 'Host: '.$trackback_url['host']."\r\n";
		$http_request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
		$http_request .= "\r\n";
		$http_request .= $query_string;
		flush();
		$fs = fsockopen($trackback_url['host'], $port);
		fputs($fs, $http_request);
		$result = '';
		while(!feof($fs)) {
			$result .= fgets($fs, 4096);
		}
/* debug code
		$debug_file = 'trackback.log';
		$fp = fopen($debug_file, 'a');
		fwrite($fp, "\n*****\nRequest:\n\n$http_request\n\nResponse:\n\n$result");
		while(!@feof($fs)) {
			fwrite($fp, @fgets($fs, 4096));
		}
		fwrite($fp, "\n\n");
		fclose($fp);
*/
		fclose($fs);
	}
	echo "<br \>", T_('Response:'), " $result</p>\n";	
	return $result;
}

function trackback_response($error = 0, $error_message = '') 
{	// trackback - reply
	echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
	echo "<response>\n";
	echo "<error>$error</error>\n";
	echo "<message>$error_message</message>\n";
	echo "</response>";
	die();
}


/* 
 * TEMPLATE FUNCTIONS:
 */



/*****
 * Trackback tags 
 *****/

/**
 *
 * @deprecated deprecated by {@link Item::trackback_url()}
 */
function trackback_url($display = 1) {
	global $htsrv_url, $id, $use_extra_path_info;
	if( $use_extra_path_info ) {
		$tb_url = "$htsrv_url/trackback.php/$id";
	} else {
		$tb_url = "$htsrv_url/trackback.php?tb_id=$id";
	}
	if ($display) {
		echo $tb_url;
	} else {
		return $tb_url;
	}
}

/*
 * trackback_number(-)
 */
function trackback_number( $zero='#', $one='#', $more='#' ) 
{
	if( $zero == '#' ) $zero = T_('Trackback (0)');
	if( $one == '#' ) $one = T_('Trackback (1)');
	if( $more == '#' ) $more = T_('Trackbacks (%)');

	global $id, $tablecomments, $tb, $querycount, $cache_trackbacknumber, $use_cache;
	$number = generic_ctp_number($id, 'trackbacks');
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
 * Displays link to the trackback page
 */
function trackback_link($file='',$c=0,$pb=0) 
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo $file.'?p='.$id;
	if( $c == 1 )
	{	// include comments 
		echo '&amp;c=1';
	}
	echo '&amp;tb=1';
	if( $pb == 1 )
	{	// include pingback 
		echo '&amp;pb=1';
	}
	echo '#trackbacks';		
}

function trackback_popup_link($zero='#', $one='#', $more='#', $CSSclass='') 
{
	global $blog, $id, $b2trackbackpopupfile, $b2commentsjavascript;
	echo '<a href="';
	if ($b2commentsjavascript) {
		echo get_bloginfo('blogurl').'?template=popup&amp;p='.$id.'&amp;tb=1';
		echo '" onclick="b2open(this.href); return false"';
	} else {
		// if comments_popup_script() is not in the template, display simple comment link
		trackback_link();
		echo '"';
	}
	if (!empty($CSSclass)) {
		echo ' class="'.$CSSclass.'"';
	}
	echo '>';
	trackback_number($zero, $one, $more);
	echo '</a>';
}

/**
 * This adds trackback autodiscovery information
 *
 * @deprecated deprecated by {@link Item::trackback_rdf()}
 */
function trackback_rdf($timezone=0) 
{
	global $id, $blogfilename;	// fplanque added: $blogfilename
	// if (!stristr($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')) {
	// fplanque WARNING: this isn't a very clean way to validate :/
	// fplanque added: html comments (not perfect but better way of validating!)
		echo "<!--\n";
		echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '."\n";
		echo '    xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
		echo '    xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n";
		echo '<rdf:Description'."\n";
		echo '    rdf:about="';
		permalink_single();
		echo '"'."\n";
		echo '    dc:identifier="';
		permalink_single();
		echo '"'."\n";
		echo '    dc:title="'.addslashes(str_replace("--","&#x2d;&#x2d;",format_to_output(get_the_title(),'xmlattr'))).'"'."\n";
		echo '    trackback:ping="'.trackback_url(0).'" />'."\n";
		echo '</rdf:RDF>';
		echo "-->\n";
	// }
}

/*****
 * /Trackback tags 
 *****/





?>