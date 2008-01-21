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
 * @param string URL to redirect to (overrides detection)
 * @param boolean is this a permanent redirect? if true, send a 301; otherwise a 303
 */
function header_redirect( $redirect_to = NULL, $permanent = false )
{
	global $Hit, $baseurl, $Blog, $htsrv_url_sensitive;
	global $Session, $Debuglog, $Messages;

	// fp> get this out
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
	}
	// <fp

	/* fp>why do we need this?
	   dh>because Location: redirects are supposed to be absolute.
	if( substr($redirect_to, 0, 1) == '/' )
	{ // relative URL, prepend current host:
		global $ReqHost;

		$redirect_to = $ReqHost.$redirect_to;
	}
	*/

	if( strpos($redirect_to, $htsrv_url_sensitive) === 0 /* we're going somewhere on $htsrv_url_sensitive */
	 || strpos($redirect_to, $baseurl) === 0   /* we're going somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		// blueyed> Removed the removing of "action" here, as it is used to trigger certain views. Instead, "confirm(ed)?" gets removed now
		// fp> which views please (important to list in order to remove asap)
		// dh> sorry, don't remember
		// TODO: fp> action should actually not be used to trigger views. This should be changed at some point.
		$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd|confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}


	$status = $permanent ? 301 : 303;
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
	if( $permanent )
	{	// This should be a permanent move redirect!
		header( 'HTTP/1.1 301 Moved Permanently' );
	}
	else
	{	// This should be a "follow up" redirect
		// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
		header( 'HTTP/1.1 303 See Other' );
	}

	header( 'Location: '.$redirect_to, true, $status ); // explictly setting the status is required for (fast)cgi
	exit();
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

	$base_tag_set = true;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo ' />';
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
 * Memorize that a specific javascript file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/js directory and certain aliases, like 'jquery' and 'jquery_debug'
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug' is automatically swapped in.
 * Any javascript added to the page is also added to the $required_js array, which is then checked to prevent adding the same code twice
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean relative_to_base.  False (default) if the file is in the rsc/js folder.  True to make it relative
 */
function require_js( $js_file, $relative_to_base = FALSE )
{
  global $required_js, $rsc_url, $debug;

  $js_aliases = array(
    '#jquery#' => 'jquery.min.js',
    '#jquery_debug#' => 'jquery.js',
    );

  // First get the real filename or url
  $absolute = FALSE;
  if( stristr( $js_file, 'http://' ) )
  { // It's an absolute url
    $js_url = $js_file;
    $absolute = TRUE;
  }
  elseif( !empty( $js_aliases[$js_file]) )
  { // It's an alias
    if ( $js_file == '#jquery#' and $debug ) $js_file = '#jquery_debug#';
    $js_file = $js_aliases[$js_file];
  }

  if ( $relative_to_base or $absolute )
  {
    $js_url = $js_file;
  }
  else
  { // Add on the $rsc_url
    $js_url = $rsc_url . 'js/' . $js_file;
  }

  // Then check to see if it has already been added
  if ( empty( $required_js ) or !in_array( strtolower( $js_url ), $required_js ) )
  { // Not required before, add it to the array, so the next plugin won't add it again
		$start_script_tag = '<script type="text/javascript" src="';
		$end_script_tag = '"></script>';
		add_headline( $start_script_tag . $js_url . $end_script_tag );
    $required_js[] = $js_url;
  }

}

/**
 * Memorize that a specific css that file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/css directory.  Set $relative_to_base to TRUE to prevent this function from adding on the rsc_path
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean relative_to_base.  False (default) if the file is in the rsc/css folder.  True to make it relative
 * @param string title.  The title for the link tag
 * @param string media.  ie, 'print'
 */
function require_css( $css_file, $relative_to_base = FALSE, $title = NULL, $media = NULL )
{
  global $required_css, $rsc_url, $debug;

  $css_aliases = array();

  // First get the real filename or url
  $absolute = FALSE;
  if( stristr( $css_file, 'http://' ) )
  { // It's an absolute url
    $css_url = $css_file;
    $absolute = TRUE;
  }
  elseif( !empty( $css_aliases[$css_file]) )
  { // It's an alias
    $css_url = $css_aliases[$css_file];
  }

  if ( $relative_to_base or $absolute )
  {
    $css_url = $css_file;
  }
  else
  { // The add on the $rsc_url
    $css_url = $rsc_url . 'css/' . $css_file;
  }

  // Then check to see if it has already been added
  if ( empty( $required_css ) or !in_array( strtolower( $css_url ), $required_css ) )
  { // Not required before, add it to the array, so it won't be added again
		$start_link_tag = '<link rel="stylesheet"';
		if ( !empty( $title ) ) $start_link_tag .= ' title="' . $title . '"';
		if ( !empty( $media ) ) $start_link_tag .= ' media="' . $media . '"';
		$start_link_tag .= ' type="text/css" href="';
		$end_link_tag = '" />';
		add_headline( $start_link_tag . $css_url . $end_link_tag );
    $required_css[] = $css_url;
  }

}


/**
 *
 */
function add_headline($headline)
{
  global $headlines;
  $headlines[] = $headline;
}


/**
 *
 */
function include_headlines()
{
  global $headlines;
  $r = implode( "\n\t", $headlines )."\n\t";
  echo $r;
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
 * Template tag
 */
function credits( $params = array() )
{
	global $credit_links;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'list_start'  => ' ',
			'list_end'    => ' ',
			'item_start'  => ' ',
			'item_end'    => ' ',
			'separator'   => ',',
			'after_item'  => '#',
		), $params );

	display_list( $credit_links, $params['list_start'], $params['list_end'], $params['separator'], $params['item_start'], $params['item_end'] );
}




/*
 * $Log$
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
