<?php
/**
 * This file implements trackback functions.
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
	global $app_name, $app_version;

	echo '<p>', T_('Sending trackback to:'), ' ', htmlspecialchars($trackback_url), " ...\n";

	$title = rawurlencode($title);
	$excerpt = rawurlencode($excerpt);
	$blog_name = rawurlencode(get_bloginfo('name'));
	$ItemCache = & get_Cache( 'ItemCache' );
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
		if( ! empty($trackback_url['host']) && ! empty($trackback_url['path']) )
		{ // Only try trackback if we have host and path:
			$port = isset($trackback_url['port']) ? $trackback_url['port'] : 80;
			$http_request  = 'POST '.$trackback_url['path']." HTTP/1.0\r\n";
			$http_request .= 'Host: '.$trackback_url['host']."\r\n";
			$http_request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
			$http_request .= "User-Agent: $app_name/$app_version\r\n";
			$http_request .= "\r\n";
			$http_request .= $query_string;
			flush();
			if( $fs = @fsockopen($trackback_url['host'], $port, $errno, $errst, 20) ) // this timeout is just for setting up the socket
			{
				// Set timeout for data:
				if( function_exists('stream_set_timeout') )
				{
					stream_set_timeout( $fs, 20 ); // PHP 4.3.0
				}
				else
				{
					socket_set_timeout( $fs, 20 ); // PHP 4
				}
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
	}
	// extract the error code and message, then make the error code readable
	if ( preg_match("/<error>[\r\n\t ]*(\d+)[\r\n\t ]*<\/error>/", $result, $error) )
	{
		preg_match("/<message>(.*?)<\/message>/", $result, $error_message);

		$message = isset($error_message[1]) ? $error_message[1] : '';

		switch ($error[1]) {
			case '0':
				$result_message = '[' . T_('Succeeded') . '] ' . $message;
				break;
			case '1':
				$result_message = '[' . T_('Failed') . '] ' . $message;
				break;
			default:
				$result_message = '[' . T_('Unknown error') . ' (' . $error[1] . ')] ' . $message;
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
 * Revision 1.10  2006/09/15 23:42:15  blueyed
 * Fixed possible E_NOTICE when sending a successful trackback
 *
 * Revision 1.9  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.8  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.7  2006/07/04 17:32:30  fplanque
 * no message
 */
?>