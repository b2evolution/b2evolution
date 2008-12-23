<?php
/**
 * This file implements misc functions that handle output of the HTML page.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template tag. Output content-type header
 *
 * @param string content-type; override for RSS feeds
 */
function header_content_type( $type = 'text/html', $charset = '#' )
{
	global $io_charset;
	global $content_type_header;

	$content_type_header = 'Content-type: '.$type;

	if( !empty($charset) )
	{
		if( $charset == '#' )
		{
			$charset = $io_charset;
		}

		$content_type_header .= '; charset='.$charset;
	}

	header( $content_type_header );
}



/**
 * Sends HTTP header to redirect to the previous location (which
 * can be given as function parameter, GET parameter (redirect_to),
 * is taken from {@link Hit::$referer} or {@link $baseurl}).
 *
 * {@link $Debuglog} and {@link $Messages} get stored in {@link $Session}, so they
 * are available after the redirect.
 *
 * NOTE: This function {@link exit() exits} the php script execution.
 *
 * @todo fp> do NOT allow $redirect_to = NULL. This leads to spaghetti code and unpredictable behavior.
 *
 * @param string Destination URL to redirect to
 * @param boolean|integer is this a permanent redirect? if true, send a 301; otherwise a 303 OR response code 301,302,303
 */
function header_redirect( $redirect_to = NULL, $permanent = false )
{
	global $Hit, $baseurl, $Blog, $htsrv_url_sensitive;
	global $Session, $Debuglog, $Messages;

	// TODO: fp> get this out to the caller, make a helper func like get_returnto_url()
	if( empty($redirect_to) )
	{ // see if there's a redirect_to request param given:
		$redirect_to = param( 'redirect_to', 'string', '' );

		if( empty($redirect_to) )
		{
			if( ! empty($Hit->referer) )
			{
				$redirect_to = $Hit->referer;
			}
			elseif( isset($Blog) && is_object($Blog) )
			{
				$redirect_to = $Blog->get('url');
			}
			else
			{
				$redirect_to = $baseurl;
			}
		}
		elseif( $redirect_to[0] == '/' )
		{ // relative URL, prepend current host:
			global $ReqHost;
			$redirect_to = $ReqHost.$redirect_to;
		}
	}
	// <fp

	if( $redirect_to[0] == '/' )
	{
		// TODO: until all calls to header_redirect are cleaned up:
		global $ReqHost;
		$redirect_to = $ReqHost.$redirect_to;
		// debug_die( '$redirect_to must be an absolute URL' );
	}

	if( strpos($redirect_to, $htsrv_url_sensitive) === 0 /* we're going somewhere on $htsrv_url_sensitive */
	 || strpos($redirect_to, $baseurl) === 0   /* we're going somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		// blueyed> Removed the removing of "action" here, as it is used to trigger certain views. Instead, "confirm(ed)?" gets removed now
		// fp> which views please (important to list in order to remove asap)
		// dh> sorry, don't remember
		// TODO: fp> action should actually not be used to trigger views. This should be changed at some point.
		// TODO: fp> confirm should be normalized to confirmed
		$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd|confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}

	// TODO: fp> change $permanent to $status in the params
	if( is_integer($permanent) )
	{
		$status = $permanent;
	}
	else
	{
		$status = $permanent ? 301 : 303;
	}
 	$Debuglog->add('Redirecting to '.$redirect_to.' (status '.$status.')');

	// Transfer of Debuglog to next page:
	if( $Debuglog->count('all') )
	{ // Save Debuglog into Session, so that it's available after redirect (gets loaded by Session constructor):
		$sess_Debuglogs = $Session->get('Debuglogs');
		if( empty($sess_Debuglogs) )
			$sess_Debuglogs = array();

		$sess_Debuglogs[] = $Debuglog;
		$Session->set( 'Debuglogs', $sess_Debuglogs, 60 /* expire in 60 seconds */ );
	}

	// Transfer of Messages to next page:
	if( $Messages->count('all') )
	{ // Set Messages into user's session, so they get restored on the next page (after redirect):
		$Session->set( 'Messages', $Messages );
	}

	$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.

	// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	switch( $status )
	{
		case 301:
			// This should be a permanent move redirect!
			header( 'HTTP/1.1 301 Moved Permanently' );
			break;

		case 303:
			// This should be a "follow up" redirect
			// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
			header( 'HTTP/1.1 303 See Other' );
			break;

		case 302:
		default:
			header( 'HTTP/1.1 302 Found' );
	}

	header( 'Location: '.$redirect_to, true, $status ); // explictly setting the status is required for (fast)cgi
	exit(0);
}


/**
 * Sends HTTP headers to avoid caching of the page.
 */
function header_nocache()
{
	header('Expires: Tue, 25 Mar 2003 05:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
}


/**
 * Display a global title matching filter params
 *
 * Outputs the title of the category when you load the page with <code>?cat=</code>
 * Display "Archive Directory" title if it has been requested
 * Display "Latest comments" title if these have been requested
 * Display "Statistics" title if these have been requested
 * Display "User profile" title if it has been requested
 *
 * @todo single month: Respect locales datefmt
 * @todo single post: posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 * @todo make it complete with all possible params!
 *
 * @param array params
 */
function request_title( $params = array() )
{
	global $MainList, $preview, $disp;

	$r = array();

	$params = array_merge( array(
			'auto_pilot'          => 'none',
			'title_before'        => '',
			'title_after'         => '',
			'title_none'          => '',
			'title_single_disp'   => true,
			'title_single_before' => '#',
			'title_single_after'  => '#',
			'title_page_disp'     => true,
			'title_page_before'   => '#',
			'title_page_after'    => '#',
			'glue'                => ' - ',
			'format'              => 'htmlbody',
			'arcdir_text'         => T_('Archive directory'),
			'catdir_text'         => T_('Category directory'),
		), $params );

	if( $params['auto_pilot'] == 'seo_title' )
	{	// We want to use the SEO title autopilot. Do overrides:
		global $Blog;
		$params['format'] = 'htmlhead';
		$params['title_after'] = $params['glue'].$Blog->get('name');
		$params['title_single_after'] = '';
		$params['title_page_after'] = '';
		$params['title_none'] = $Blog->dget('name','htmlhead');
	}


	$before = $params['title_before'];
	$after = $params['title_after'];

	switch( $disp )
	{
		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = $params['arcdir_text'];
			break;

		case 'catdir':
			// We are requesting the archive directory:
			$r[] = $params['catdir_text'];
			break;

		case 'comments':
			// We are requesting the latest comments:
			global $Item;
			if( isset( $Item ) )
			{
				$r[] = sprintf( /* TRANS: %s is an item title */ T_('Latest comments on %s'), $Item->get('title') );
			}
			else
			{
				$r[] = T_('Latest comments');
			}
			break;

		case 'feedback-popup':
			// We are requesting the comments on a specific post:
			// Should be in first position
			$Item = & $MainList->get_by_idx( 0 );
			$r[] = sprintf( /* TRANS: %s is an item title */ T_('Feedback on %s'), $Item->get('title') );
			break;

		case 'profile':
			// We are requesting the user profile:
			$r[] = T_('User profile');
			break;

		case 'subs':
			// We are requesting the subscriptions screen:
			$r[] = T_('Subscriptions');
			break;

		case 'msgform':
			// We are requesting the message form:
			$r[] = T_('Send an email message');
			break;

		case 'single':
		case 'page':
			// We are displaying a single message:
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = T_('PREVIEW');
			}
			elseif( $params['title_'.$disp.'_disp'] && isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			if( $params['title_'.$disp.'_before'] != '#' )
			{
				$before = $params['title_'.$disp.'_before'];
			}
			if( $params['title_'.$disp.'_after'] != '#' )
			{
				$after = $params['title_'.$disp.'_after'];
			}
			break;

		case 'user':
			// We are requesting the message form:
			$r[] = T_('User');
			break;

		default:
			if( isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			break;
	}


	if( ! empty( $r ) )
	{
		$r = implode( $params['glue'], $r );
		$r = $before.format_to_output( $r, $params['format'] ).$after;
	}
	elseif( !empty( $params['title_none'] ) )
	{
		$r = $params['title_none'];
	}

	if( !empty( $r ) )
	{ // We have something to display:
		echo $r;
	}

}


/**
 * Returns a "<base />" tag and remembers that we've used it ({@link regenerate_url()} needs this).
 *
 * @param string URL to use (this gets used as base URL for all relative links on the HTML page)
 * @return string
 */
function base_tag( $url, $target = NULL )
{
	global $base_tag_set;
	$base_tag_set = $url;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo " />\n";
}


/**
 * Robots tag
 *
 * Outputs the robots meta tag if necessary
 */
function robots_tag()
{
	global $robots_index, $robots_follow;

	if( is_null($robots_index) && is_null($robots_follow) )
	{
		return;
	}

	$r = '<meta name="robots" content="';

	if( $robots_index === false )
		$r .= 'NOINDEX';
	else
		$r .= 'INDEX';

	$r .= ',';

	if( $robots_follow === false )
		$r .= 'NOFOLLOW';
	else
		$r .= 'FOLLOW';

	$r .= '" />'."\n";

	echo $r;
}


/**
 * Output a link to current blog.
 *
 * We need this function because if no Blog is currently active (some admin pages or site pages)
 * then we'll go to the general home.
 */
function blog_home_link( $before = '', $after = '', $blog_text = 'Blog', $home_text = 'Home' )
{
	global $Blog, $baseurl;

	if( !empty( $Blog ) )
	{
		echo $before.'<a href="'.$Blog->get( 'url' ).'">'.$blog_text.'</a>'.$after;
	}
	elseif( !empty($home_text) )
	{
		echo $before.'<a href="'.$baseurl.'">'.$home_text.'</a>'.$after;
	}
}


/**
 * Require/Include a given JavaScript file.
 *
 * This gets added to the HTML headlines and multiple includes
 * are detected.
 *
 * Accepts aliases (e.g. 'jquery'), absolute filenames, absolute
 * urls, filenames relative to rsc/js or {@link $basepath}.
 *
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug'
 * is automatically swapped in.
 *
 * @uses get_url_filepath_for_rsc_file()
 * @param string URL or filename/path (can be absolute, relative to rsc/js or basepath)
 */
function require_js( $js_file )
{
	static $required_js;

	$js_aliases = array(
		'#jquery#' => 'jquery.min.js',
		'#jquery_debug#' => 'jquery.js',
		'#jqueryUI#' => 'jquery.ui.all.min.js',
		'#jqueryUI_debug#' => 'jquery.ui.all.js',
	);

	// TODO: dh> I think dependencies should get handled where the files are included!
	if( in_array( $js_file, array( '#jqueryUI#', '#jqueryUI_debug#' ) ) )
	{	// Dependency : ensure jQuery is loaded
		require_js( '#jquery#' );
	}
	elseif( $js_file == 'communication.js' )
	{ // jQuery dependency
		require_js( '#jquery#' );
	}

	// Alias handling:
	if( ! empty( $js_aliases[$js_file]) )
	{ // It's an alias
		global $debug;
		if ( $debug && $js_file == '#jquery#' ) $js_file = '#jquery_debug#';
		$js_file = $js_aliases[$js_file];
	}

	$js_url = array_shift(get_url_filepath_for_rsc_file($js_file, 'js'));

	// Add to headlines, if not done already:
	if( empty( $required_js ) || ! in_array( strtolower($js_url), $required_js ) )
	{
		$required_js[] = strtolower($js_url);
		add_headline( '<script type="text/javascript" src="'.$js_url.'"></script>' );
	}
}


/**
 * Require/Include a given CSS file.
 *
 * This gets added to the HTML headlines and multiple includes
 * are detected.
 *
 * Accepts absolute filename, absolute urls, filenames relative
 * to rsc/js or {@link $basepath}.
 *
 * @uses get_url_filepath_for_rsc_file()
 * @param string URL or filename/path (can be absolute, relative to rsc/js or basepath)
 * @param array Link params (like "title" or "media")
 */
function require_css( $css_file, $link_params = array() )
{
	static $required_css;

	if( is_bool($link_params) )
	{ // compatibility for b2evo 2.x: translate to $link_params
		global $Debuglog;
		$func_args = func_get_args();
		$link_params = array();
		if( isset($func_args[2]) )
		{
			$link_params['title'] = $func_args[2];
		}
		if( isset($func_args[3]) )
		{
			$link_params['media'] = $func_args[3];
		}
		$Debuglog->add('require_css() called in a deprecated way (args: '.var_export($func_args, true).'). Please adjust.'
			."\n".debug_get_backtrace(), 'deprecated');
	}

	$css_url = array_shift(get_url_filepath_for_rsc_file($css_file, 'css'));

	// Add to headlines, if not done already:
	if( empty( $required_css ) || ! in_array( strtolower($css_url), $required_css ) )
	{
		$required_css[] = strtolower($css_url);

		$headline = '<link rel="stylesheet"';
		foreach( $link_params as $k => $v )
		{
			$headline .= ' '.htmlspecialchars($k).'="'.htmlspecialchars($v).'"';
		}
		$headline .= ' type="text/css" href="'.$css_url.'" />';
		add_headline( $headline );
	}
}


/**
 * Helper function for {@link require_css()} and {@link require_js()}.
 * Please use those functions instead.
 *
 * The filename gets checked in this order:
 *  - absolute URL (will return false for $filepath)
 *  - absolute filename (must be below $basepath and accessible
 *                       through $baseurl)
 *  - $rsc_path/$rsc_type/ (e.g. 'rsc/js/')
 *  - $basepath
 *
 * This method returns both the absolute filepath and URL, so that
 * the caller can easily bundle files into a single resourcebundle
 * (used in whissip branch and may get adopted later).
 *
 * @param string Filename / Filepath
 * @param string Resource type ("css", "js")
 * @return array ($url, $filepath)
 *         Returns list of URL and absolute filepath.
 *         The URL is made relative to {@link $ReqHost}.
 */
function get_url_filepath_for_rsc_file($rsc_file, $rsc_type)
{
	global $rsc_path, $rsc_url, $basepath, $baseurl, $ReqHost;

	if( preg_match('~^https?://~', $rsc_file ) )
	{ // It's an absolute url
		$url = $rsc_file;
		$filepath = false;
	}
	elseif( is_absolute_filename($rsc_file) )
	{
		if( substr($rsc_file, 0, strlen($basepath)) == $basepath )
		{
			$url = $baseurl.substr($rsc_file, strlen($basepath));
		}
		else
		{ // Path not below $basepath:
			debug_die('get_url_for_rsc_file: Passed absolute filename, but it is not below $basepath ('.htmlspecialchars($rsc_file).').');
		}
		$filepath = $rsc_file;
	}
	else
	{
		$include_paths = array(
			$rsc_path.$rsc_type.'/' => $rsc_url.$rsc_type.'/',
			$basepath => $baseurl );

		foreach( $include_paths as $include_path => $include_url )
		{
			$filepath = $include_path.$rsc_file;
			if( file_exists($filepath) )
			{
				$url = $include_url.$rsc_file;
				break;
			}
		}

		if( ! isset($url) )
		{
			debug_die('get_url_for_rsc_file: No URL for $rsc_file found ('.htmlspecialchars($rsc_file).')');
		}
	}

	$url = url_rel_to_same_host($url, $ReqHost);

	return array($url, $filepath);
}


/**
 * Add a headline, which then gets output in the HTML HEAD section.
 * If you want to include CSS or JavaScript files, please use
 * {@link require_css()} and {@link require_js()} instead.
 * This avoids duplicates and allows caching/concatenating those files
 * later (not implemented yet)
 * @param string
 */
function add_headline($headline)
{
	global $headlines;
	$headlines[] = $headline;
}


/**
 * Add a Javascript headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string Javascript
 */
function add_js_headline($headline)
{
	add_headline("<script type=\"text/javascript\">/* <![CDATA[ */\n\t"
		.$headline."\n\t/* ]]> */\n\t</script>");
}


/**
 * Add a CSS headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string CSS
 */
function add_css_headline($headline)
{
	add_headline("<style type=\"text/css\">\n\t".$headline."\n\t</style>");
}


/**
 * Outputs the collected HTML HEAD lines.
 * @see add_headline()
 * @return string
 */
function include_headlines()
{
	global $headlines;

	if( $headlines )
	{
		echo "\n\t<!-- headlines: -->\n\t".implode( "\n\t", $headlines );
		echo "\n\n";
	}
}


/**
 * Template tag.
 */
function app_version()
{
	global $app_version;
	echo $app_version;
}


/**
 * Displays an empty or a full bullet based on boolean
 *
 * @param boolean true for full bullet, false for empty bullet
 */
function bullet( $bool )
{
	if( $bool )
		return get_icon( 'bullet_full', 'imgtag' );

	return get_icon( 'bullet_empty', 'imgtag' );
}




/**
 * Stub: Links to previous and next post in single post mode
 */
function item_prevnext_links( $params = array() )
{
	global $MainList;

	if( isset($MainList) )
	{
		$MainList->prevnext_item_links( $params );
	}
}


/**
 * Stub
 */
function messages( $params = array() )
{
	global $Messages;

	$Messages->disp( $params['block_start'], $params['block_end'] );
}


/**
 * Stub: Links to list pages:
 */
function mainlist_page_links( $params = array() )
{
	global $MainList;

	if( isset($MainList) )
	{
		$MainList->page_links( $params );
	}
}


/**
 * Stub
 *
 * @return Item
 */
function & mainlist_get_item()
{
	global $MainList;

	if( isset($MainList) )
	{
		$r = $MainList->get_item();
	}
	else
	{
		$r = NULL;
	}
	return $r;
}


/**
 * Stub
 *
 * @return boolean true if empty MainList
 */
function display_if_empty( $params = array() )
{
	global $MainList;

	if( isset($MainList) )
	{
		return $MainList->display_if_empty( $params );
	}

	return NULL;
}


/**
 * Template tag for credits
 *
 * Note: You can limit (and even disable) the number of links being displayed here though the Admin interface:
 * Blog Settings > Advanced > Software credits
 *
 * @param array
 */
function credits( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;
	global $Blog;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'list_start'  => ' ',
			'list_end'    => ' ',
			'item_start'  => ' ',
			'item_end'    => ' ',
			'separator'   => ',',
			'after_item'  => '#',
		), $params );


	$cred_links = $global_Cache->get( 'creds' );
	if( empty( $cred_links ) )
	{	// Use basic default:
		$cred_links = unserialize('a:2:{i:0;a:2:{i:0;s:24:"http://b2evolution.net/r";i:1;s:18:"free blog software";}i:1;a:2:{i:0;s:36:"http://b2evolution.net/web-hosting/r";i:1;s:19:"quality web hosting";}}');
	}

	$max_credits = (empty($Blog) ? NULL : $Blog->get_setting( 'max_footer_credits' ));

	display_list( $cred_links, $params['list_start'], $params['list_end'], $params['separator'], $params['item_start'], $params['item_end'], NULL, $max_credits );
}


/**
 * Display rating as 5 stars
 */
function star_rating( $stars, $class = 'middle' )
{
	if( is_null($stars) )
	{
		return;
	}

	for( $i=1; $i<=5; $i++ )
	{
		if( $i <= $stars )
		{
			echo get_icon( 'star_on', 'imgtag', array( 'class'=>$class ) );
		}
		elseif( $i-.5 <= $stars )
		{
			echo get_icon( 'star_half', 'imgtag', array( 'class'=>$class ) );
		}
		else
		{
			echo get_icon( 'star_off', 'imgtag', array( 'class'=>$class ) );
		}
	}
}


/**
 * Display "powered by b2evolution" logo
 */
function powered_by( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;

	global $rsc_url;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'block_start' => '<div class="powered_by">',
			'block_end'   => '</div>',
			'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
			'img_width'   => '',
			'img_height'  => '',
		), $params );

	echo $params['block_start'];

	$img_url = str_replace( '$rsc$', $rsc_url, $params['img_url'] );

	$evo_links = $global_Cache->get( 'evo_links' );
	if( empty( $evo_links ) )
	{	// Use basic default:
		$evo_links = unserialize('a:1:{s:0:"";a:1:{i:0;a:3:{i:0;i:100;i:1;s:23:"http://b2evolution.net/";i:2;a:2:{i:0;a:2:{i:0;i:55;i:1;s:36:"powered by b2evolution blog software";}i:1;a:2:{i:0;i:100;i:1;s:29:"powered by free blog software";}}}}}');
	}

	echo resolve_link_params( $evo_links, NULL, array(
			'type'        => 'img',
			'img_url'     => $img_url,
			'img_width'   => $params['img_width'],
			'img_height'  => $params['img_height'],
			'title'       => 'b2evolution: next generation blog software',
		) );

	echo $params['block_end'];
}



/**
 * DEPRECATED
 */
function bloginfo( $what )
{
	global $Blog;
	$Blog->disp( $what );
}

/**
 * DEPRECATED
 */
function link_pages()
{
	echo '<!-- link_pages() is DEPRECATED -->';
}


/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	return number_format( $hit_count * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}

function addup_percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	static $addup = 0;

	$addup += $hit_count;
	return number_format( $addup * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}



/*
 * $Log$
 * Revision 1.45  2008/12/23 18:55:31  blueyed
 * Refactored require_css()/require_js(). This does not duplicate
 * code for detecting filename/URL anymore and makes it easier
 * to include resource bundle support (as done in whissip branch).
 *  - Drop relative_to_base param
 *  - Use include_paths instead (rsc/css and $basepath)
 *  - Use $link_params for require_css() (since argument list changed
 *    anyway), but add compatibility layer for 2.x syntax
 *    (no plugin in evocms-plugins uses old $media or $title)
 *  - Support absolute filenames, which is convenient from a skin, e.g.
 *    if you want to include some external JS script
 *  - Add helper function get_url_filepath_for_rsc_file()
 *  - Add helper function is_absolute_filename()
 *  - Adjust calls to require_js()/require_css()
 *
 * Revision 1.44  2008/11/12 13:59:19  blueyed
 * Fix add_css_headline(): remove unnecessary comment
 *
 * Revision 1.43  2008/11/07 20:07:14  blueyed
 * - Use add_headline() in add_js_headline()
 * - Add add_css_headline()
 *
 * Revision 1.42  2008/10/02 23:33:08  blueyed
 * - require_js(): remove dirty dependency handling for communication.js.
 * - Add add_js_headline() for adding inline JS and use it for admin already.
 *
 * Revision 1.41  2008/09/28 08:06:05  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.40  2008/09/28 05:05:06  fplanque
 * minor
 *
 * Revision 1.39  2008/09/27 00:05:35  fplanque
 * doc, minor
 *
 * Revision 1.38  2008/09/15 21:53:09  blueyed
 * Fix lowercase check in require_css() again; broke it in last merge
 *
 * Revision 1.37  2008/07/10 23:21:42  blueyed
 * Merge trivial changes (I hope so) from my bzr branch
 *
 * Revision 1.36  2008/07/10 21:29:23  blueyed
 * base_tag(): remember used URL in , so this can be used/queried later.
 *
 * Revision 1.35  2008/07/10 21:26:52  blueyed
 * Fix deprecated message for link_pages()
 *
 * Revision 1.34  2008/07/03 19:25:10  blueyed
 * Remove var_dump
 *
 * Revision 1.33  2008/07/03 19:15:19  blueyed
 * require_js(): add TODOs about dependency handling; fix 'already included?' check (case insensitivity)
 * require_css(): fix 'already included?' check (case insensitivity)
 *
 * Revision 1.32  2008/07/03 10:35:22  yabs
 * minor fix
 *
 * Revision 1.31  2008/07/03 09:51:52  yabs
 * widget UI
 *
 * Revision 1.30  2008/05/11 01:09:42  fplanque
 * always output charset header + meta
 *
 * Revision 1.29  2008/04/26 22:20:44  fplanque
 * Improved compatibility with older skins.
 *
 * Revision 1.28  2008/04/13 23:38:53  fplanque
 * Basic public user profiles
 *
 * Revision 1.27  2008/04/04 23:56:02  fplanque
 * avoid duplicate content in meta tags
 *
 * Revision 1.26  2008/04/04 16:02:14  fplanque
 * uncool feature about limiting credits
 *
 * Revision 1.25  2008/03/31 00:27:49  fplanque
 * Enhanced comment moderation
 *
 * Revision 1.24  2008/03/30 23:37:22  fplanque
 * TODO
 *
 * Revision 1.22  2008/03/24 03:07:40  blueyed
 * Enable make-redirects-absolute in header_redirect() again
 *
 * Revision 1.21  2008/03/21 19:42:44  fplanque
 * enhanced 404 handling
 *
 * Revision 1.20  2008/03/16 14:19:38  fplanque
 * no message
 *
 * Revision 1.19  2008/03/15 19:07:25  fplanque
 * no message
 *
 * Revision 1.18  2008/02/22 00:39:29  blueyed
 * doc
 *
 * Revision 1.17  2008/02/19 11:11:17  fplanque
 * no message
 *
 * Revision 1.16  2008/02/12 04:59:01  fplanque
 * more custom field handling
 *
 * Revision 1.15  2008/02/10 00:58:57  fplanque
 * no message
 *
 * Revision 1.14  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.13  2008/01/05 02:25:23  fplanque
 * refact
 *
 * Revision 1.12  2007/11/08 17:54:23  blueyed
 * mainlist_get_item(): fixed return by reference (patch by Austriaco)
 *
 * Revision 1.11  2007/11/03 23:54:39  fplanque
 * skin cleanup continued
 *
 * Revision 1.10  2007/11/03 21:04:25  fplanque
 * skin cleanup
 *
 * Revision 1.9  2007/10/01 01:06:31  fplanque
 * Skin/template functions cleanup.
 *
 * Revision 1.8  2007/09/30 04:55:34  fplanque
 * request_title() cleanup
 *
 * Revision 1.7  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.6  2007/09/23 18:55:17  fplanque
 * attempting to debloat. The Log class is insane.
 *
 * Revision 1.5  2007/09/13 23:39:50  blueyed
 * trans: use printf
 *
 * Revision 1.4  2007/08/05 17:23:33  waltercruz
 * Feed of the comments on a specific post. Just add the &id=? or &title=? to the URL
 *
 * Revision 1.3  2007/07/01 03:57:20  fplanque
 * toolbar eveywhere
 *
 * Revision 1.2  2007/06/30 22:03:34  fplanque
 * cleanup
 *
 * Revision 1.1  2007/06/25 10:58:53  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.30  2007/06/24 20:19:00  personman2
 * Don't add .js or .css on when they're not there.  Added documentation for require_css
 *
 * Revision 1.29  2007/06/24 19:43:39  personman2
 * changing backoffice over to new js and css handling
 *
 * Revision 1.26  2007/06/24 01:05:31  fplanque
 * skin_include() now does all the template magic for skins 2.0.
 * .disp.php templates still need to be cleaned up.
 *
 * Revision 1.25  2007/06/23 00:12:26  fplanque
 * doc
 *
 * Revision 1.24  2007/06/22 15:44:25  personman2
 * Moved output of require_js() to another callback, as Daniel suggested
 *
 * Revision 1.23  2007/06/22 02:30:12  personman2
 * Added require_js() function to add javascript files.  Can be called from a skin or from a plugin using the SkinBeginHtmlHead hook.
 *
 * Revision 1.22  2007/05/02 20:39:27  fplanque
 * meta robots handling
 *
 * Revision 1.21  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.20  2007/03/25 10:20:02  fplanque
 * cleaned up archive urls
 *
 * Revision 1.19  2007/03/04 21:42:49  fplanque
 * category directory / albums
 *
 * Revision 1.18  2007/03/04 19:47:37  fplanque
 * enhanced toolbar menu
 *
 * Revision 1.17  2007/03/04 05:24:52  fplanque
 * some progress on the toolbar menu
 *
 * Revision 1.16  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.15  2007/01/25 13:41:52  fplanque
 * wording
 *
 * Revision 1.14  2006/12/05 00:01:15  fplanque
 * enhanced photoblog skin
 *
 * Revision 1.13  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
