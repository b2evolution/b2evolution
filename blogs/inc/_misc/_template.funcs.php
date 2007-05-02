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
 * @author cafelog (team)
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


/*
 * $Log$
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