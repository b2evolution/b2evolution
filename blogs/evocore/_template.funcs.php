<?php
/**
 * This file implements misc functions to be called from the templates.
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
 * @author blueyed: Daniel HAHLER.
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template function: output base URL to b2evo's image folder
 *
 * {@internal imgbase(-)}}
 */
function imgbase()
{
	global $img_url;
	echo $img_url;
}


/**
 * Display a global title matching filter params
 *
 * Outputs the title of the category when you load the page with <code>?cat=</code>
 * Display "Archive Directory" title if it has been requested
 * Display "Last comments" title if these have been requested
 * Display "Statistics" title if these have been requested
 * Display "User profile" title if it has been requested
 *
 * @todo single month: Respect locales datefmt
 * @todo single post: posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 * @todo make it complete with all possible params!
 *
 * @param string prefix to display if a title is generated
 * @param string suffix to display if a title is generated
 * @param string glue to use if multiple title elements are generated
 * @param string format to output, default 'htmlbody'
 * @param boolean display title? (if false: return)
 * @param boolean show the year as link to year's archive (in monthly mode)
 */
function request_title( $prefix = ' ', $suffix = '', $glue = ' - ', $format = 'htmlbody',
												$display = true, $linktoyeararchive = true, $blogurl = '', $params = '' )
{
	global $cat_modifier, $cat_array, $m, $w, $month, $p, $title, $preview, $ItemCache, $disp, $s, $author;

	$r = array();

	switch( $disp )
	{
		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = T_('Archive Directory');
			break;
			
		case 'comments':
			// We are requesting the last comments:
			$r[] = T_('Last comments');
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
		
		default:
			// We are displaying (a) message(s)...
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = T_('PREVIEW');
			}
			elseif( intval($p) )
			{	// We are requesting a specific post by ID:
				if( $Item = & $ItemCache->get_by_ID( $p, false ) )
				{
					$r[] = T_('Post details').': '.$Item->get('title');
				}
			}
			elseif( !empty( $title ) )
			{	// We are requesting a specific post by title:
				if( $Item = & $ItemCache->get_by_urltitle( $title, false ) )
				{
					$r[] = T_('Post details').': '.$Item->get('title');
				}
			}
			else
			{	// Multiple messages...

				// CATEGORIES:
				if( !empty($cat_array) )
				{ // We have requested specific categories...
					$cat_names = array();
					foreach( $cat_array as $cat_ID )
					{
						if( ($my_cat = get_the_category_by_ID( $cat_ID, false ) ) !== false )
						{ // It is almost never meaningful to die over an invalid cat when generating title
							$cat_names[] = $my_cat['cat_name'];
						}
					}
					if( $cat_modifier == '*' )
					{
						$cat_names_string = implode( ' + ', $cat_names );
					}
					else
					{
						$cat_names_string = implode( ', ', $cat_names );
					}
					if( !empty( $cat_names_string ) )
					{
						if( $cat_modifier == '-' )
						{
							$cat_names_string = T_('All but ').$cat_names_string;
							$r[] = T_('Categories').': '.$cat_names_string;
						}
						else
						{
							if( count($cat_array) > 1 )
								$r[] = T_('Categories').': '.$cat_names_string;
							else
								$r[] = T_('Category').': '.$cat_names_string;
						}
					}
				}


				// TIMEFRAME:
				if( !empty($m) )
				{	// We have asked for a specific timeframe:

					$my_year = substr($m,0,4);
					if( strlen($m) > 4 )
					{ // We have requested a month too:
						$my_month = T_($month[substr($m,4,2)]);
					}
					else
					{
						$my_month = '';
					}
					// Requested a day?
					$my_day = substr($m,6,2);

					if( $format == 'htmlbody' && !empty( $my_month ) && $linktoyeararchive )
					{ // display year as link to year's archive
						$my_year = '<a href="' . archive_link( $my_year, '', '', '', false, $blogurl, $params ) . '">' . $my_year . '</a>';
					}

					$arch = T_('Archives for').': '.$my_month.' '.$my_year;

					if( !empty( $my_day ) )
					{	// We also want to display a day
						$arch .= ", $my_day";
					}

					if( !empty($w) && ($w>=0) ) // Note: week # can be 0
					{	// We also want to display a week number
						$arch .= ", week $w";
					}

					$r[] = $arch;
				}


				// KEYWORDS:
				if( !empty($s) )
				{
					$r[] = T_('Keyword(s)').': '.$s;
				}


				// AUTHORS:
				if( !empty($author) )
				{
					$r[] = T_('Author(s)').': '.$author;
				}

			}
	}

	if( !empty( $r ) )
	{ // We have something to display:
		$r = implode( $glue, $r );
		$r = $prefix.format_to_output( $r, $format ).$suffix;
		if( $display )
			echo $r;
		else
			return $r;
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
 * {@internal archive_link(-)}}
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

	if( (! $Settings->get('links_extrapath')) || (!empty($params)) )
	{	// We reference by Query: Dirty but explicit permalinks
		$link = url_add_param( $link, $params );
		$link = url_add_param( $link, 'm=' );
		$separator = '';
	}
	else
	{
		$link .= '/';
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
		if( ! $Settings->get('links_extrapath') )
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