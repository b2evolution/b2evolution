<?php
/**
 * This file implements trackback functions.
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
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jmuto: Jun MUTO
 * @author sakichan: Nobuo SAKIYAMA.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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
		{ // trackback each url:
			$tb_url = trim($tb_url);
			if( empty( $tb_url ) ) continue;
			trackback($tb_url, $post_title, $excerpt, $post_ID);
		}
		echo "<p>", T_('Trackbacks done.'), "</p>\n";
	}
	echo "</div>\n";
}


/**
 * Send Trackback to single URL
 *
 * trackback(-)
 *
 * @todo add autodiscovery
 */
function trackback(
	$trackback_url,
	$title,
	$excerpt,
	$ID) // post ID
{
	global $ItemCache, $app_name, $app_version;

	echo '<p>', T_('Sending trackback to:'), ' ', htmlspecialchars($trackback_url), " ...\n";

	$title = rawurlencode($title);
	$excerpt = rawurlencode($excerpt);
	$blog_name = rawurlencode(get_bloginfo('name'));
	$Item = & $ItemCache->get_by_ID( $ID );
	$url = rawurlencode( $Item->get_permanent_url('', '', false, '&') );
	// dis is the trackback stuff to be sent:
	$query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	// echo "url:$trackback_url<br>$sending:$query_string<br />";

	$result = '';
	if (strstr($trackback_url, '?'))
	{
		echo '[get]';
		$trackback_url .= "&".$query_string;;
		flush();
		if( $fp = fopen($trackback_url, 'r') )
		{
			// blueyed>> why do we here just read the first 4kb, but in the POSTed response everything?
			// fp>> this is dirty code... I've never really reviewed it entirely. Feel free to refactor as much as needed.
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
		$http_request .= "User-Agent: $app_name/$app_version\r\n";
		$http_request .= "\r\n";
		$http_request .= $query_string;
		flush();
		if( $fs = fsockopen($trackback_url['host'], $port) )
		{
			fputs($fs, $http_request);
			$result = '';
			while(!feof($fs))
			{
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


/**
 * Send a trackback response and exits.
 *
 * @param integer Error code
 * @param string Error message
 */
function trackback_response( $error = 0, $error_message = '' )
{ // trackback - reply
	echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
	echo "<response>\n";
	echo "<error>$error</error>\n";
	echo "<message>$error_message</message>\n";
	echo "</response>";
	die();
}


/**
 * trackback_number(-)
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_number( $zero='#', $one='#', $more='#', $post_ID = NULL )
{
	if( $zero == '#' ) $zero = T_('Trackback (0)');
	if( $one == '#' ) $one = T_('Trackback (1)');
	if( $more == '#' ) $more = T_('Trackbacks (%d)');

	if( empty( $post_ID ) )
	{
		global $id;
		$post_ID = $id;
	}
	$number = generic_ctp_number($post_ID, 'trackbacks');
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
 * Revision 1.15  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.14  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.13  2005/12/11 19:59:51  blueyed
 * Renamed gen_permalink() to get_permalink()
 *
 * Revision 1.12  2005/12/06 01:55:40  blueyed
 * Fix line ending for User-Agent; Also fix the infinite loop it was causing.
 * Revision 1.10  2005/12/04 00:23:11  blueyed
 * trackback(): send User-Agent header. This seems to be good behaviour.
 *
 * Revision 1.9  2005/11/20 18:03:01  blueyed
 * Fix sending wrong encoded url on trackbacks. Fix by knj (http://forums.b2evolution.net/viewtopic.php?t=5890)
 *
 * Revision 1.8  2005/10/31 05:51:06  blueyed
 * Use rawurlencode() instead of urlencode()
 *
 * Revision 1.7  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.6  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.5  2005/02/15 22:05:09  blueyed
 * Started moving obsolete functions to _obsolete092.php..
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