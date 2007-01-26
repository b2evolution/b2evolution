<?php
/**
 * This file implements misc functions to be called from the templates.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

	switch( $disp )
	{
		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = T_('Archive Directory');
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


/*
 * single_month_title(-)
 * arcdir_title(-)
 *
 * @movedTo _obsolete092.php
 */


/**
 * Create a link to archive, using either params or extra path info.
 *
 * @param string year
 * @param string month
 * @param string day
 * @param string week
 * @param boolean show or return
 * @param string link, instead of blogurl
 * @param string GET params for 'file'
 */
function archive_link( $year, $month, $day = '', $week = '', $show = true, $file = '', $params = '' )
{
	global $Settings;

	if( empty($file) )
		$link = get_bloginfo('blogurl');
	else
		$link = $file;

	if( ($Settings->get('links_extrapath') == 'disabled') || (!empty($params)) )
	{	// We reference by Query: Dirty but explicit permalinks
		$link = url_add_param( $link, $params );
		$link = url_add_param( $link, 'm=' );
		$separator = '';
	}
	else
	{
		$link = trailing_slash( $link ); // there can already be a slash from a siteurl like 'http://example.com/'
		$separator = '/';
	}

	$link .= $year;

	if( !empty( $month ) )
	{
		$link .= $separator.zeroise($month,2);
		if( !empty( $day ) )
		{
			$link .= $separator.zeroise($day,2);
		}
	}
	elseif( $week !== '' )  // Note: week # can be 0 !
	{
		if( $Settings->get('links_extrapath') == 'disabled' )
		{	// We reference by Query: Dirty but explicit permalinks
			$link = url_add_param( $link, 'w='.$week );
		}
		else
		{
			$link .= '/w'.zeroise($week,2);
		}
	}

	$link .= $separator;

	if( $show )
	{
		echo $link;
	}
	return $link;
}

/*
 * $Log$
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
 *
 * Revision 1.12  2006/09/10 20:59:18  fplanque
 * extended extra path info setting
 *
 * Revision 1.11  2006/09/07 00:48:55  fplanque
 * lc parameter for locale filtering of posts
 *
 * Revision 1.10  2006/08/24 00:38:18  fplanque
 * allow full control over the default title
 *
 * Revision 1.9  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.8  2006/08/17 16:32:29  fplanque
 * enhanced titles
 *
 * Revision 1.7  2006/08/05 17:59:52  fplanque
 * minor
 *
 * Revision 1.6  2006/05/12 21:36:00  blueyed
 * Fixed E_NOTICE
 *
 * Revision 1.5  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/03/16 23:22:45  blueyed
 * Fix extra slash in archive_link() for only "siteurl"
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/09 15:23:27  fplanque
 * fixed broken images
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.15  2005/12/20 18:12:50  fplanque
 * enhanced filtering/titling framework
 *
 * Revision 1.14  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.13  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.12  2005/10/29 20:49:39  matthiasmiller
 * An invalid category previously resulted in either a blank page (with the error indicated in the title) or an incorrectly formed feed. It is almost never meaningful to die over an invalid category when generating a title.
 *
 * Revision 1.11  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.10  2005/08/26 16:34:51  fplanque
 * no message
 *
 * Revision 1.9  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.8  2005/08/24 18:43:09  fplanque
 * Removed public stats to prevent spamfests.
 * Added context browsing to Archives plugin.
 *
 * Revision 1.7  2005/05/24 18:46:26  fplanque
 * implemented blog email subscriptions (part 1)
 *
 * Revision 1.6  2005/03/15 19:19:48  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.5  2005/03/09 19:23:34  blueyed
 * doc
 *
 * Revision 1.4  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.3  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.23  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>