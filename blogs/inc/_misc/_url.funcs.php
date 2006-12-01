<?php
/**
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
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
 * @author blueyed: Daniel HAHLER
 * @author Danny Ferguson
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Fetch remote page
 * Attempt to retrieve a remote page, first with cURL, then fopen, then fsockopen.
 * @param string URL
 * @param array Info (by reference)
 *        'error': holds error message, if any
 *        'status': HTTP status (e.g. 200 or 404)
 * @return string|false The remote page as a string; false in case of error
 */
function fetch_remote_page( $url, & $info )
{
	$info = array(
		'error' => '',
		'status' => NULL );

	// CURL:
	if( extension_loaded('curl') )
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if( ! empty($params['method']) && $params['method'] == 'HEAD'  )
		{
			curl_setopt($ch, CURLOPT_NOBODY, true);
		}
		$r = curl_exec($ch);
		$info['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $r;
	}

	// FOPEN:
	if( ini_get('allow_url_fopen') )
	{
		$fp = @fopen($url, 'r');
		if( ! $fp )
		{
			return false;
		}

		// headers:
		$meta = stream_get_meta_data($fp);
		if( ! $meta || ! preg_match( '~^HTTP/\d+\.\d+ (\d+)~', $meta['wrapper_data'][0], $match ) )
		{
			$info['error'] = 'Invalid response.';
			$r = false;
		}
		else
		{
			$info['status'] = $match[1];
			$r = '';
			while( $buf = fread($fp, 4096) )
			{ //read the complete file (binary safe)
				$r .= $buf;
			}
		}
		fclose($fp);

		return $r;
	}


	// As a last resort, try fsockopen:
	$url_parsed = parse_url($url);
	if( empty($url_parsed['scheme']) ) {
		$url_parsed = parse_url('http://'.$url);
	}

	$host = $url_parsed['host'];
	$port = ( empty($url_parsed['port']) ? 80 : $url_parsed['port'] );
	$path = empty($url_parsed['path']) ? '/' : $url_parsed['path'];
	if( ! empty($url_parsed['query']) )
	{
		$path .= '?'.$url_parsed['query'];
	}
	if( ! empty($url_parsed['fragment']) )
	{
		$path .= '#'.$url_parsed['fragment'];
	}

	$out = "GET $path HTTP/1.0\r\n";
	$out .= "Host: $host:$port\r\n";
	$out .= "Connection: Close\r\n\r\n";

	$fp = @fsockopen($host, $port, $errno, $errstr, 30);
	if( ! $fp )
	{
		$info['error'] = $errstr.' ('.$errstr.')';
		return false;
	}

	// Set timeout for data:
	if( function_exists('stream_set_timeout') )
		stream_set_timeout( $fp, 20 ); // PHP 4.3.0
	else
		socket_set_timeout( $fp, 20 ); // PHP 4

	// Send request:
	fwrite($fp, $out);

	// Read response:
	$r = '';
	// First line:
	$s = fgets($fp, 4096);
	if( ! preg_match( '~^HTTP/\d+\.\d+ (\d+)~', $s, $match ) )
	{
		$info['error'] = 'Invalid response.';
		$r = false;
	}
	else
	{
		$info['status'] = $match[1];

		$foundBody = false;
		while( ! feof($fp) )
		{
			$s = fgets($fp, 4096);
			if( $s == "\r\n" )
			{
				$foundBody = true;
				continue;
			}
			if( $foundBody )
			{
				$r .= $s;
			}
		}
	}
	fclose($fp);

	return $r;
}


/**
 * Get $url with the same protocol (http/https) as $other_url.
 *
 * @param string URL
 * @param string other URL (defaults to {@link $ReqHost})
 * @return string
 */
function url_same_protocol( $url, $other_url = NULL )
{
	if( is_null($other_url) )
	{
		global $ReqHost;

		$other_url = $ReqHost;
	}

	// change protocol of $url to same of admin ('https' <=> 'http')
	if( substr( $url, 0, 7 ) == 'http://' )
	{
		if( substr( $other_url, 0, 8 ) == 'https://' )
		{
			$url = 'https://'.substr( $url, 7 );
		}
	}
	elseif( substr( $url, 0, 8 ) == 'https://' )
	{
		if( substr( $other_url, 0, 7 ) == 'http://' )
		{
			$url = 'http://'.substr( $url, 8 );
		}
	}

	return $url;
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.3  2006/12/01 17:31:38  blueyed
 * Fixed url_fopen method for fetch_remote_page
 *
 * Revision 1.2  2006/11/29 20:48:46  blueyed
 * Moved url_rel_to_same_host() from _misc.funcs.php to _url.funcs.php
 *
 * Revision 1.1  2006/11/25 23:00:39  blueyed
 * Added file for URL handling. Includes fetch_remote_page()
 *
 * }}}
 */
?>
