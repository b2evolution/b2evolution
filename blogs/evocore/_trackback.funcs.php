<?php
/**
 * This file implements trackback functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jmuto: Jun MUTO
 * @author sakichan: Nobuo SAKIYAMA.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
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
		echo "<p>", T_('Excerpt to be sent:'), " $excerpt</p>\n";
		$trackback_urls = split('( )+', $post_trackbacks,10);		// fplanque: ;
		foreach($trackback_urls as $tb_url)
		{	// trackback each url:
			$tb_url = trim($tb_url);
			if( empty( $tb_url ) ) continue;
			trackback($tb_url, $post_title, $excerpt, $post_ID);
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
function trackback(
	$trackback_url,
	$title,
	$excerpt,
	$ID) // post ID
{
	global $ItemCache;

	echo '<p>', T_('Sending trackback to:'), ' ', htmlspecialchars($trackback_url), " ...\n";

	$title = urlencode($title);
	$excerpt = urlencode($excerpt);
	$blog_name = urlencode(get_bloginfo('name'));
	$Item = $ItemCache->get_by_ID( $ID );
	$url = $Item->gen_permalink();
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
	// extract the error code and message, then make the error code readable
	if ( preg_match("/<error>[\r\n\t ]*(\d+)[\r\n\t ]*<\/error>/", $result, $error) )
	{
		preg_match("/<message>(.*?)<\/message>/", $result, $error_message);
		switch ($error[1]) {
			case '0':
				$result_message = '[' . T_('Succeeded') . '] ' . $error_message[1];
				break;
			case '1':
				$result_message = '[' . T_('Failed') . '] ' . $error_message[1];
				break;
			default:
				$result_message = '[' . T_('Unknown error') . ' (' . $error[1] . ')] ' . $error_message[1];
				break;
		}
	}
	else
	{
		$result_message = T_('No valid trackback response. Maybe the given url is not a Trackback url.') . ' &quot;' . $result . '&quot;';
	}
	echo '<br />', T_('Response:'), ' ', strip_tags($result_message), "</p>\n";
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
function trackback_url($display = 1)
{
	global $htsrv_url, $id;
	global $Settings;

	if( $Settings->get('links_extrapath') )
	{
		$tb_url = $htsrv_url.'trackback.php/'.$id;
	}
	else
	{
		$tb_url = $htsrv_url.'trackback.php?tb_id='.$id;
	}
	if ($display) {
		echo $tb_url;
	} else {
		return $tb_url;
	}
}

/**
 * trackback_number(-)
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_number( $zero='#', $one='#', $more='#' )
{
	if( $zero == '#' ) $zero = T_('Trackback (0)');
	if( $one == '#' ) $one = T_('Trackback (1)');
	if( $more == '#' ) $more = T_('Trackbacks (%d)');

	global $id, $tb, $querycount, $cache_trackbacknumber, $use_cache;
	$number = generic_ctp_number($id, 'trackbacks');
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
 * Displays link to the trackback page
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_link($file='',$c=0,$pb=0)
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo url_add_param( $file, 'p='.$id );
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

/**
 *
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_popup_link($zero='#', $one='#', $more='#', $CSSclass='')
{
	global $blog, $id, $b2trackbackpopupfile, $b2commentsjavascript;
	echo '<a href="';
	if ($b2commentsjavascript) {
		echo url_add_param( get_bloginfo('blogurl'), 'template=popup&amp;p='.$id.'&amp;tb=1' );
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
		echo '    dc:title="'.format_to_output(get_the_title(),'xmlattr').'"'."\n";
		echo '    trackback:ping="'.trackback_url(0).'" />'."\n";
		echo '</rdf:RDF>';
		echo "-->\n";
	// }
}

/*****
 * /Trackback tags
 *****/


/*
 * $Log$
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.33  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 * Revision 1.11  004/2/7 21:27:27  vegarg
 * Trackback URLs are now 'clean' (or not 'clean') according to . (contrib by topolino)
 *
 * Revision 1.6  2003/8/29 18:25:51  sakichan
 * SECURITY: XSS vulnerability fix.
 *
 * Revision 1.1.1.1.2.1  2003/8/31 6:23:31  sakichan
 * Security fixes for various XSS vulnerability and SQL injection vulnerability
 */
?>