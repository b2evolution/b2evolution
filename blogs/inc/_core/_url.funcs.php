<?php
/**
 * URL manipulation functions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _url.funcs.php 6225 2014-03-16 10:01:05Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Check the validity of a given URL
 *
 * Checks allowed URI schemes and URL ban list.
 * URL can be empty.
 *
 * Note: We have a problem when trying to "antispam" a keyword which is already blacklisted
 * If that keyword appears in the URL... then the next page has a bad referer! :/
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Url to validate
 * @param string Context ("posting", "commenting")
 * @param boolean also do an antispam check on the url
 * @return mixed false (which means OK) or error message
 */
function validate_url( $url, $context = 'posting', $antispam_check = true )
{
	global $Debuglog, $debug;

	if( empty($url) )
	{ // Empty URL, no problem
		return false;
	}

	$verbose = $debug || $context != 'commenting';

	$allowed_uri_schemes = get_allowed_uri_schemes( $context );

	// Validate URL structure
	if( $url[0] == '$' )
	{	// This is a 'special replace code' URL (used in footers)
 		if( ! preg_match( '~\$([a-z_]+)\$~', $url ) )
		{
			return T_('Invalid URL $code$ format');
		}
	}
	elseif( preg_match( '~^\w+:~', $url ) )
	{ // there's a scheme and therefor an absolute URL:
		if( substr($url, 0, 7) == 'mailto:' )
		{ // mailto:link
			if( ! in_array( 'mailto', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'mailto:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), evo_htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			preg_match( '~^(mailto):(.*?)(\?.*)?$~', $url, $match );
			if( ! $match )
			{
				return $verbose
					? sprintf( T_('Invalid email link: %s.'), evo_htmlspecialchars($url) )
					: T_('Invalid email link.');
			}
      elseif( ! is_email($match[2]) )
			{
				return $verbose
					? sprintf( T_('Supplied email address (%s) is invalid.'), evo_htmlspecialchars($match[2]) )
					: T_('Invalid email address.');
			}
		}
		elseif( substr($url, 0, 6) == 'clsid:' )
		{ // clsid:link
			if( ! in_array( 'clsid', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'clsid:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), evo_htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			if( ! preg_match( '~^(clsid):([a-fA-F0-9\-]+)$~', $url, $match) )
			{
				return T_('Invalid class ID format');
			}
		}
		elseif( substr($url, 0, 11) == 'javascript:' )
		{ // javascript:
			// Basically there could be anything here
			if( ! in_array( 'javascript', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'javascript:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), evo_htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			preg_match( '~^(javascript):~', $url, $match );
		}
		else
		{
			// convert URL to IDN:
			$url = idna_encode($url);

			if( ! preg_match('~^           # start
				([a-z][a-z0-9+.\-]*)             # scheme
				://                              # authorize absolute URLs only ( // not present in clsid: -- problem? ; mailto: handled above)
				(\w+(:\w+)?@)?                   # username or username and password (optional)
				( localhost |
						[a-z0-9]([a-z0-9\-])*            # Don t allow anything too funky like entities
						\.                               # require at least 1 dot
						[a-z0-9]([a-z0-9.\-])+           # Don t allow anything too funky like entities
				)
				(:[0-9]+)?                       # optional port specification
				.*                               # allow anything in the path (including spaces - used in FileManager - but no newlines).
				$~ix', $url, $match) )
			{ // Cannot validate URL structure
				$Debuglog->add( 'URL &laquo;'.$url.'&raquo; does not match url pattern!', 'error' );
				return $verbose
					? sprintf( T_('Invalid URL format (%s).'), evo_htmlspecialchars($url) )
					: T_('Invalid URL format.');
			}

			$scheme = strtolower($match[1]);
			if( ! in_array( $scheme, $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), evo_htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}
		}
	}
	else
	{ // URL is relative..
		if( $context == 'commenting' )
		{	// We do not allow relative URLs in comments
			return $verbose ? sprintf( T_('URL "%s" must be absolute.'), evo_htmlspecialchars($url) ) : T_('URL must be absolute.');
		}

		$char = substr($url, 0, 1);
		if( $char != '/' && $char != '#' )
		{ // must start with a slash or hash (for HTML anchors to the same page)
			return $verbose
				? sprintf( T_('URL "%s" must be a full path starting with "/" or an anchor starting with "#".'), evo_htmlspecialchars($url) )
				: T_('URL must be a full path starting with "/" or an anchor starting with "#".');
		}
	}


	if( $antispam_check )
	{	// Search for blocked keywords:
		if( $block = antispam_check($url) )
		{
			return $verbose
				? sprintf( T_('URL "%s" not allowed: blacklisted word "%s".'), evo_htmlspecialchars($url), $block )
				: T_('URL not allowed');
		}
	}

	return false; // OK
}


/**
 * Get allowed URI schemes for a given context.
 * @param string Context ("posting", "commenting")
 * @return array
 */
function get_allowed_uri_schemes( $context = 'posting' )
{
  /**
	 * @var User
	 */
	global $current_User;

	$schemes = array(
			'http',
			'https',
			'ftp',
			'gopher',
			'nntp',
			'news',
			'mailto',
			'irc',
			'aim',
			'icq'
		);

	if( $context == 'commenting' )
	{
		return $schemes;
	}

	if( !empty( $current_User ) )
	{	// Add additional permissions the current User may have:

		$Group = & $current_User->get_Group();

		if( $Group->perm_xhtml_javascript )
		{
			$schemes[] = 'javascript';
		}

		if( $Group->perm_xhtml_objects )
		{
			$schemes[] = 'clsid';
		}

	}

	return $schemes;
}


/**
 * Get the last HTTP status code received by the HTTP/HTTPS wrapper of PHP.
 *
 * @param array The $http_response_header array (by reference).
 * @return integer|boolean False if no HTTP status header could be found,
 *                         the HTTP status code otherwise.
 */
function _http_wrapper_last_status( & $headers )
{
	for( $i = count( $headers ) - 1; $i >= 0; --$i )
	{
		if( preg_match( '|^HTTP/\d+\.\d+ (\d+)|', $headers[$i], $matches ) )
		{
			return $matches[1];
		}
	}

	return false;
}


/**
 * Fetch remote page
 *
 * Attempt to retrieve a remote page using a HTTP GET request, first with
 * cURL, then fsockopen, then fopen.
 *
 * cURL gets skipped, if $max_size_kb is requested, since there appears to be no
 * method to control this.
 * {@internal (CURLOPT_READFUNCTION maybe? But it has not been called for me.. seems
 *            to affect sending, not fetching?!)}}
 *
 * @todo dh> Should we try remaining methods, if the previous one(s) failed?
 * @todo Tblue> Also allow HTTP POST.
 *
 * @param string URL
 * @param array Info (by reference)
 *        'error': holds error message, if any
 *        'status': HTTP status (e.g. 200 or 404)
 *        'used_method': Used method ("curl", "fopen", "fsockopen" or null if no method
 *                       is available)
 * @param integer Timeout (default: 15 seconds)
 * @param integer Maximum size in kB
 * @return string|false The remote page as a string; false in case of error
 */
function fetch_remote_page( $url, & $info, $timeout = NULL, $max_size_kb = NULL )
{
	$info = array(
		'error' => '',
		'status' => NULL,
		'mimetype' => NULL,
		'used_method' => NULL,
	);

	if( ! isset($timeout) )
		$timeout = 15;

	if( extension_loaded('curl') && ! $max_size_kb ) // dh> I could not find an option to support "maximum size" for curl (to abort during download => memory limit).
	{	// CURL:
		$info['used_method'] = 'curl';

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // made silent due to possible errors with safe_mode/open_basedir(?)
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
		$r = curl_exec( $ch );

		$info['mimetype'] = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
		$info['status'] = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$info['error'] = curl_error( $ch );
		if( ( $errno = curl_errno( $ch ) ) )
		{
			$info['error'] .= ' (#'.$errno.')';
		}
		curl_close( $ch );

		return $r;
	}

	if( function_exists( 'fsockopen' ) ) // may have been disabled
	{	// FSOCKOPEN:
		$info['used_method'] = 'fsockopen';

		if ( ( $url_parsed = @parse_url( $url ) ) === false
			 || ! isset( $url_parsed['host'] ) )
		{
			$info['error'] = 'Could not parse URL';
			return false;
		}

		$host = $url_parsed['host'];
		$port = empty( $url_parsed['port'] ) ? 80 : $url_parsed['port'];
		$path = empty( $url_parsed['path'] ) ? '/' : $url_parsed['path'];
		if( ! empty( $url_parsed['query'] ) )
		{
			$path .= '?'.$url_parsed['query'];
		}

		$out = 'GET '.$path.' HTTP/1.1'."\r\n";
		$out .= 'Host: '.$host;
		if( ! empty( $url_parsed['port'] ) )
		{	// we don't want to add :80 if not specified. remote end may not resolve it. (e-g b2evo multiblog does not)
			$out .= ':'.$port;
		}
		$out .= "\r\n".'Connection: Close'."\r\n\r\n";

		$fp = @fsockopen( $host, $port, $errno, $errstr, $timeout );
		if( ! $fp )
		{
			$info['error'] = $errstr.' (#'.$errno.')';
			return false;
		}

		// Send request:
		fwrite( $fp, $out );

		// Set timeout for data:
		if( function_exists( 'stream_set_timeout' ) )
		{
			stream_set_timeout( $fp, $timeout ); // PHP 4.3.0
		}
		else
		{
			socket_set_timeout( $fp, $timeout ); // PHP 4
		}

		// Read response:
		$r = '';
		// First line:
		$s = fgets( $fp );
		if( ! preg_match( '~^HTTP/\d+\.\d+ (\d+)~', $s, $match ) )
		{
			$info['error'] = 'Invalid response.';
			fclose( $fp );
			return false;
		}

		while( ! feof( $fp ) )
		{
			$r .= fgets( $fp );
			if( $max_size_kb && evo_bytes($r) >= $max_size_kb*1024 )
			{
				$info['error'] = sprintf('Maximum size of %d kB reached.', $max_size_kb);
				return false;
			}
		}
		fclose($fp);

		if ( ( $pos = strpos( $r, "\r\n\r\n" ) ) === false )
		{
			$info['error'] = 'Could not locate end of headers';
			return false;
		}

		// Remember headers to extract info at the end
		$headers = explode("\r\n", substr($r, 0, $pos));

		$info['status'] = $match[1];
		$r = substr( $r, $pos + 4 );
	}
	elseif( ini_get( 'allow_url_fopen' ) )
	{	// URL FOPEN:
		$info['used_method'] = 'fopen';

		$fp = @fopen( $url, 'r' );
		if( ! $fp )
		{
			if( isset( $http_response_header )
			    && ( $code = _http_wrapper_last_status( $http_response_header ) ) !== false )
			{	// fopen() returned false because it got a bad HTTP code:
				$info['error'] = 'Invalid response';
				$info['status'] = $code;
				return '';
			}

			$info['error'] = 'fopen() failed';
			return false;
		}
		// Check just to be sure:
		else if ( ! isset( $http_response_header )
		          || ( $code = _http_wrapper_last_status( $http_response_header ) ) === false )
		{
			$info['error'] = 'Invalid response';
			return false;
		}
		else
		{
			// Used to get info at the end
			$headers = $http_response_header;

			// Retrieve contents
			$r = '';
			while( ! feof( $fp ) )
			{
				$r .= fgets( $fp );
				if( $max_size_kb && evo_bytes($r) >= $max_size_kb*1024 )
				{
					$info['error'] = sprintf('Maximum size of %d kB reached.', $max_size_kb);
					return false;
				}
			}

			$info['status'] = $code;
		}
		fclose( $fp );
	}

	// Extract mimetype info from the headers (for fsockopen/fopen)
	if( isset($r) )
	{
		foreach($headers as $header)
		{
			$header = strtolower($header);
			if( substr($header, 0, 13) == 'content-type:' )
			{
				$info['mimetype'] = trim(substr($header, 13));
				break; // only looking for mimetype
			}
		}

		return $r;
	}

	// All failed:
	$info['error'] = 'No method available to access URL!';
	return false;
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


/**
 * Add param(s) at the end of an URL, using either "?" or "&amp;" depending on existing url
 *
 * @param string existing url
 * @param string|array Params to add (string as-is) or array, which gets urlencoded.
 * @param string delimiter to use for more params
 */
function url_add_param( $url, $param, $glue = '&amp;' )
{
	if( empty($param) )
	{
		return $url;
	}

	if( ($anchor_pos = strpos($url, '#')) !== false )
	{ // There's an "#anchor" in the URL
		$anchor = substr($url, $anchor_pos);
		$url = substr($url, 0, $anchor_pos);
	}
	else
	{ // URL without "#anchor"
		$anchor = '';
	}

	// Handle array use case
	if( is_array($param) )
	{ // list of key => value pairs
		$param_list = array();
		foreach( $param as $k => $v )
		{
			$param_list[] = get_param_urlencoded($k, $v, $glue);
		}
		$param = implode($glue, $param_list);
	}

	if( strpos($url, '?') !== false )
	{ // There are already params in the URL
		$r = $url;
		if( substr($url, -1) != '?' )
		{ // the "?" is not the last char
			$r .= $glue;
		}
		return $r.$param.$anchor;
	}

	// These are the first params
	return $url.'?'.$param.$anchor;
}


/**
 * Add a tail (starting with "/") at the end of an URL before any params (starting with "?")
 *
 * @param string existing url
 * @param string tail to add
 */
function url_add_tail( $url, $tail )
{
	$parts = explode( '?', $url );
	if( substr($parts[0], -1) == '/' )
	{
		$parts[0] = substr($parts[0], 0, -1);
	}
	if( isset($parts[1]) )
	{
		return $parts[0].$tail.'?'.$parts[1];
	}

	return $parts[0].$tail;
}


/**
 * Create a crumb param to be passed in action urls...
 *
 * @access public
 * @param string crumb_name
 */
function url_crumb( $crumb_name )
{
	return 'crumb_'.$crumb_name.'='.get_crumb($crumb_name);
}


/**
 * Get crumb via {@link $Session}.
 * @access public
 * @param string crumb_name
 * @return string
 */
function get_crumb($crumb_name)
{
	global $Session;
	return isset( $Session ) ? $Session->create_crumb( $crumb_name ) : '';
}


/**
 * Try to make $url relative to $target_url, if scheme, host, user and pass matches.
 *
 * This is useful for redirect_to params, to keep them short and avoid mod_security
 * rejecting the request as "Not Acceptable" (whole URL as param).
 *
 * @param string URL to handle
 * @param string URL where we want to make $url relative to
 * @return string
 */
function url_rel_to_same_host( $url, $target_url )
{
	// Prepend fake scheme to URLs starting with "//" (relative to current protocol), since
	// parse_url fails to handle them correctly otherwise (recognizes them as path-only)
	$mangled_url = substr($url, 0, 2) == '//' ? 'noprotocolscheme:'.$url : $url;

	if( substr($target_url, 0, 2) == '//' )
		$target_url = 'noprotocolscheme:'.$target_url;


	$parsed_url = @parse_url( $mangled_url );
	if( ! $parsed_url )
	{ // invalid url
		return $url;
	}
	if( empty($parsed_url['scheme']) || empty($parsed_url['host']) )
	{ // no protocol or host information
		return $url;
	}

	$target_url = @parse_url( $target_url );
	if( ! $target_url )
	{ // invalid url
		return $url;
	}
	if( ! empty($target_url['scheme']) && $target_url['scheme'] != $parsed_url['scheme']
		&& $parsed_url['scheme'] != 'noprotocolscheme' )
	{ // scheme/protocol is different
		return $url;
	}
	if( ! empty($target_url['host']) )
	{
		if( empty($target_url['scheme']) || $target_url['host'] != $parsed_url['host'] )
		{ // target has no scheme (but a host) or hosts differ
			return $url;
		}

		if( @$target_url['port'] != @$parsed_url['port'] )
			return $url;
		if( @$target_url['user'] != @$parsed_url['user'] )
			return $url;
		if( @$target_url['pass'] != @$parsed_url['pass'] )
			return $url;
	}

	// We can make the URL relative:
	$r = '';
	if( isset($parsed_url['path']) && strlen($parsed_url['path']) )
		$r .= $parsed_url['path'];

	if( isset($parsed_url['query']) && strlen($parsed_url['query']) )
		$r .= '?'.$parsed_url['query'];

	if( isset($parsed_url['fragment']) && strlen($parsed_url['fragment']) )
		$r .= '#'.$parsed_url['fragment'];

	return $r;
}


/**
 * Make an $url absolute according to $host, if it is not absolute yet.
 *
 * @param string URL
 * @param string Base (including protocol, e.g. 'http://example.com'); autodedected
 * @return string
 */
function url_absolute( $url, $base = NULL )
{
	load_funcs('_ext/_url_rel2abs.php');

	if( is_absolute_url($url) )
	{	// URL is already absolute
		return $url;
	}

	if( empty($base) )
	{	// Detect current page base
		global $Blog, $ReqHost, $base_tag_set, $baseurl;

		if( $base_tag_set )
		{	// <base> tag is set
			$base = $base_tag_set;
		}
		else
		{
			if( ! empty( $Blog ) )
			{	// Get original blog skin, not passed with 'tempskin' param
				$SkinCache = & get_SkinCache();
				if( ($Skin = $SkinCache->get_by_ID( $Blog->get_skin_ID(), false )) !== false )
				{
					$base = $Blog->get_local_skins_url().$Skin->folder.'/';
				}
				else
				{ // Skin not set:
					$base = $Blog->gen_baseurl();
				}
			}
			else
			{	// We are displaying a general page that is not specific to a blog:
				$base = $ReqHost;
			}
		}
	}

	if( ($absurl = url_to_absolute($url, $base)) === false )
	{	// Return relative URL in case of error
		$absurl = $url;
	}
	return $absurl;
}


/**
 * Make links in $s absolute.
 *
 * It searches for "src" and "href" HTML tag attributes and makes the absolute.
 *
 * @uses url_absolute()
 * @param string content
 * @param string Hostname including scheme, e.g. http://example.com; defaults to $ReqHost
 * @return string
 */
function make_rel_links_abs( $s, $host = NULL )
{
	$s = preg_replace_callback( '~(<[^>]+?)\b((?:src|href)\s*=\s*)(["\'])?([^\\3]+?)(\\3)~i', create_function( '$m', '
		return $m[1].$m[2].$m[3].url_absolute($m[4], "'.$host.'").$m[5];' ), $s );
	return $s;
}


/**
 * Display an URL, constrained to a max length
 *
 * @param string
 * @param integer
 */
function disp_url( $url, $max_length = NULL )
{
	if( !empty($max_length) && evo_strlen($url) > $max_length )
	{
		$disp_url = evo_htmlspecialchars(substr( $url, 0, $max_length-1 )).'&hellip;';
	}
	else
	{
		$disp_url = evo_htmlspecialchars($url);
	}
	echo '<a href="'.$url.'">'.$disp_url.'</a>';
}


/**
 * Is a given URL absolute?
 * Note: "//foo/bar" is absolute - leaving the protocol out.
 *
 * @param string URL
 * @return boolean
 */
function is_absolute_url( $url )
{
	load_funcs('_ext/_url_rel2abs.php');

	if( ($parsed_url = split_url($url)) !== false )
	{
		if( !empty($parsed_url['scheme']) || !empty($parsed_url['host']) )
		{
			return true;
		}
	}
	return false;
}


/**
 * Compare two given URLs, if they are the same.
 * This converts all urlencoded chars (e.g. "%AA") to lowercase.
 * It appears that some webservers use lowercase for the chars (Apache),
 * while others use uppercase (lighttpd).
 * @return boolean
 */
function is_same_url( $a, $b )
{
	$a = preg_replace_callback('~%[0-9A-F]{2}~', create_function('$m', 'return strtolower($m[0]);'), $a);
	$b = preg_replace_callback('~%[0-9A-F]{2}~', create_function('$m', 'return strtolower($m[0]);'), $b);
	return $a == $b;
}


/**
 * IDNA-Encode URL to Punycode.
 * @param string URL
 * @return string Encoded URL (ASCII)
 */
function idna_encode( $url )
{
	global $evo_charset;

	$url_utf8 = convert_charset( $url, 'utf-8', $evo_charset );

	if( version_compare(PHP_VERSION, '5', '>=') )
	{
		load_class('_ext/idna/_idna_convert.class.php', 'idna_convert' );
		$IDNA = new idna_convert();
	}
	else
	{
		load_class('_ext/idna/_idna_convert.class.php4', 'Net_IDNA_php4' );
		$IDNA = new Net_IDNA_php4();
	}

	//echo '['.$url_utf8.'] ';
	$url = $IDNA->encode( $url_utf8 );
	/* if( $idna_error = $IDNA->get_last_error() )
	{
		echo $idna_error;
	} */
	// echo '['.$url.']<br>';

	return $url;
}


/**
 * Decode IDNA puny-code ("xn--..") to UTF-8 name.
 *
 * @param string
 * @return string The decoded puny-code ("xn--..") (UTF8!)
 */
function idna_decode( $url )
{
	if( version_compare(PHP_VERSION, '5', '>=') )
	{
		load_class('_ext/idna/_idna_convert.class.php', 'idna_convert' );
		$IDNA = new idna_convert();
	}
	else
	{
		load_class('_ext/idna/_idna_convert.class.php4', 'Net_IDNA_php4' );
		$IDNA = new Net_IDNA_php4();
	}
	return $IDNA->decode($url);
}


/**
 * Get disp urls for Frontoffice part OR ctrl urls for Backoffice
 *
 * @param string specific sub entry url
 * @param string additional params
 */
function get_dispctrl_url( $dispctrl, $params = '' )
{
	global $Blog;

	if( $params != '' )
	{
		$params = '&amp;'.$params;
	}

	if( is_admin_page() || empty( $Blog ) )
	{	// Backoffice part
		global $admin_url;
		return url_add_param( $admin_url, 'ctrl='.$dispctrl.$params );
	}

	return url_add_param( $Blog->gen_blogurl(), 'disp='.$dispctrl.$params );
}


/**
 * Get link tag
 *
 * @param string Url
 * @param string Link Text
 * @param string Link class
 * @param integer Max length of url when url is used as link text
 * @return string HTML link tag
 */
function get_link_tag( $url, $text = '', $class='', $max_url_length = 50 )
{
	if( empty( $text ) )
	{ // Link text is empty, Use url
		$text = $url;
		if( strlen( $text ) > $max_url_length )
		{ // Crop url text
			$text = substr( $text, 0, $max_url_length ).'&hellip;';
		}
	}

	return '<a class="'.$class.'" href="'.str_replace('&amp;', '&', $url ).'">'.$text.'</a>';
}


/**
 * Get part of url, Based on function parse_url()
 *
 * @param string URL
 * @param string Part name:
 *    scheme - e.g. http
 *    host
 *    port
 *    user
 *    pass
 *    path
 *    query - after the question mark ?
 *    fragment - after the hashmark #
 * @return string Part of url
 */
function url_part( $url, $part )
{
	$url_data = @parse_url( $url );
	if( $url_data && ! empty( $url_data[ $part ] ) )
	{
		return $url_data[ $part ];
	}

	return '';
}
?>