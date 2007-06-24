<?php
/**
 * This file implements misc functions to be called from the templates.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @todo fp> get_request_title() if needed
 *
 * @param string prefix to display if a title is generated
 * @param string suffix to display if a title is generated
 * @param string glue to use if multiple title elements are generated
 * @param string format to output, default 'htmlbody'
 * @param array params
 * @param boolean do we want to display title for single posts
 * @param default text to display if nothing else
 */
function request_title( $prefix = ' ', $suffix = '', $glue = ' - ', $format = 'htmlbody',
												$params = array(), $disp_single_title = true, $default = '' )
{
	global $MainList, $preview, $disp;

	$r = array();

	$params = array_merge( array(
			'arcdir_text' => T_('Archive directory'),
			'catdir_text' => T_('Category directory'),
		), $params );

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
			$r[] = T_('Latest comments');
			break;

		case 'feedback-popup':
			// We are requesting the comments on a specific post:
			// Should be in first position
			$Item = & $MainList->get_by_idx( 0 );
			$r[] = T_('Feeback on ').$Item->get('title');
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
			// We are displaying a single message:
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = T_('PREVIEW');
			}
			elseif( $disp_single_title && isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
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
		$r = implode( $glue, $r );
		$r = $prefix.format_to_output( $r, $format ).$suffix;
	}
	elseif( !empty( $default ) )
	{
		$r = $default;
	}

	if( !empty( $r ) )
	{ // We have something to display:
		echo $r;
	}

}


/**
 * Robots tag
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

	if( isset( $Blog ) )
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
 * Accepts absolute urls, filenames (with or without the '.js') relative to the rsc/js directory and certain aliases, like 'jquery' and 'jquery_debug'
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug' is automatically swapped in.
 * Any javascript added to the page is also added to the $required_js array, which is then checked to prevent adding the same code twice
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
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
  elseif ( strtolower( substr( $js_file, -3 ) ) != '.js' )
  { // The file was named without the .js, so add it on
    $js_file = $js_file . '.js';
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
  elseif ( strtolower( substr( $css_file, -4 ) ) != '.css' )
  { // The file was named without the .css, so add it on
    $css_url = $css_file.'.css';
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
 * fp> TODO rename to include_required_files()
 * fp> TODO also handle CSS
 * fp> TODO use in backoffice too.
 */
function add_html_head_lines()
{
  global $headlines;
  $r = implode( "\n\t", $headlines )."\n\t";
  echo $r;
}


function add_headline($headline)
{
  global $headlines;
  $headlines[] = $headline;
}


/*
 * $Log$
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