<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004-2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * The University of North Carolina at Charlotte grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 * @author jeffbearer: Jeff BEARER
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - cafelog (team)
 *            - t3dworld
 * }}
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once (dirname(__FILE__). '/_main.inc.php');

// Getting GET or POST parameters:
$Request->param( 'blog', 'integer', 0, true );  // Can't use $default_to_blog because the param must always be included in regenerate_url() when present
$Request->param( 'p', 'integer', '', true );              // Specific post number to display
$Request->param( 'title', 'string', '', true );						// urtitle of post to display
$Request->param( 'm', 'integer', '', true );              // YearMonth(Day) to display
$Request->param( 'w', 'integer', -1, true );              // Week number
$Request->param( 'dstart', 'integer', '', true );         // YearMonth(Day) to start at
$Request->param( 'unit', 'string', '', true );            // list unit: 'posts' or 'days'

$Request->param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', '', true ); // List of cats to restrict to
$Request->param( 'catsel', 'array', array(), true );  // Array of cats to restrict to
// Let's compile those values right away (we use them in several different places):
$cat_array = array();
$cat_modifier = '';
compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, $blog == 1 ? 0 : $blog );

$Request->param( 'author', 'integer', '', true );         // List of authors to restrict to

$Request->param( 'order', 'string', 'DESC', true );       // ASC or DESC
$Request->param( 'orderby', 'string', '', true );         // list of fields to order by

$Request->param( 'posts', 'integer', 0, true );           // # of units to display on the page
$Request->param( 'paged', 'integer', '', true );          // List page number in paged display

$Request->param( 'poststart', 'integer', '', true );      // Start results at this position
$Request->param( 'postend', 'integer', '', true );        // End results at this position

$Request->param( 's', 'string', '', true );               // Search string
$Request->param( 'sentence', 'string', 'AND', true );     // Search for sentence or for words
$Request->param( 'exact', 'integer', '', true );          // Require exact match of title or contents

$Request->param( 'preview', 'integer', 0, true );         // Is this preview ?

$Request->param( 'calendar', 'string', '', true );        // Display a specific month in the calendar

$Request->param( 'page', 'integer', '', true );
$Request->param( 'more', 'integer', 0, true );

$Request->param( 'c', 'string', '', true );
$Request->param( 'tb', 'integer', 0, true );
$Request->param( 'pb', 'integer', 0, true );

$Request->param( 'disp', 'string', 'posts', true );
$Request->param( 'stats', 'integer', 0 );                 // deprecated

$Request->param( 'tempskin', 'string', '', true );


if( !isset($timestamp_min) ) $timestamp_min = '';
if( !isset($timestamp_max) ) $timestamp_max = '';

if( $preview )
{ // Ignore this hit
	$Hit->ignore = true;
}

if( empty($disp) )
{ // Conversion support for old params:
	if( $c == 'last')
	{ // Translate old last comments caller
		$disp = 'comments';
	}
	elseif( $stats )
	{ // Translate old stats caller
		$disp = 'stats';
	}
}

if( $disp == 'stats' )
{
	require dirname(__FILE__).'/_410_stats_gone.page.php'; // error & exit
}

// Getting current blog info:
$Blog = Blog_get_by_ID( $blog ); /* TODO: TMP: */ $blogparams = get_blogparams_by_ID( $blog );

// Activate matching locale:
$Debuglog->add( 'Activating blog locale: '.$Blog->get('locale'), 'locale' );
locale_activate( $Blog->get('locale') );

// -------------------------
// Extra path info decoding:
// -------------------------
if ( !empty($tempskin) || !empty($generating_static) )
{ // We don't want extra path resolution on rss files and when generating static pages
	$resolve_extra_path = false;
}
if( !isset( $resolve_extra_path ) ) { $resolve_extra_path = true; }

if( $resolve_extra_path )
{
	// Check and Remove blog baseurl from ReqPath:
	// if Blog is installed on separate domain
	// use this domain setting as base for the actual path
	if( $Blog->get( 'siteurl' ) )
	{
		$blog_baseurl = substr( $Blog->get( 'siteurl' ), strlen( $baseurlroot ) );
	}
	else
	{
		$blog_baseurl = substr( $Blog->get( 'baseurl' ), strlen( $baseurlroot ) );
	}

	if( ($pos = strpos( $ReqPath, $blog_baseurl )) !== false )
	{ // note: $pos will typically be 0
		$path_string = substr( $ReqPath, $pos+strlen( $blog_baseurl ) );

		$Debuglog->add( 'Extra path info found! path_string=' . $path_string , 'params' );
		//echo "path=[$path_string]<br />";

		// Slice the path:
		$path_split = explode( '/', $path_string, 20 );

		// Remove empty slots:
		$path_elements = array();
		foreach( $path_split as $path_element )
		{
			if( !empty( $path_element ) )
			{
				$path_elements[] = $path_element;
			}
		}
		// echo count( $path_elements );

		$path_error = 0;
		$i=0;
		// echo $path_elements[$i];
		if( isset( $path_elements[$i] ) && preg_match( '#.+\.php[0-9]?#', $path_elements[$i] ) ) // QUESTION: add "$" at the end of the pattern to avoid false matches?
		{ // Ignore *.php
			$i++;
			$Debuglog->add( 'Ignoring *.php in extra path info' , 'params' );
		}

		if( isset( $path_elements[$i] ) && preg_match( '#^'.$Blog->get( 'stub' ).'(\.php)?$#', $path_elements[$i] )  )
		{ // Ignore stub file
			$i++;
			$Debuglog->add( 'Ignoring stub file in extra path info' , 'params' );
		}

		// echo $path_elements[$i];
		if( isset( $path_elements[$i] ) )
		{
			if( is_numeric( $path_elements[$i] ) )
			{ // We'll consider this to be the year
				$m = $path_elements[$i++];
				$Debuglog->add( 'Setting year from extra path info. $m=' . $m , 'params' );

				if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
				{ // We'll consider this to be the month
					$m .= $path_elements[$i++];
					$Debuglog->add( 'Setting month from extra path info. $m=' . $m , 'params' );

					if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
					{ // We'll consider this to be the day
						$m .= $path_elements[$i++];
						$Debuglog->add( 'Setting day from extra path info. $m=' . $m , 'params' );

						if( isset( $path_elements[$i] ) && (!empty( $path_elements[$i] )) )
						{ // We'll consider this to be a ref to a post
							// We are accessing a post by permalink
							// Set a lot of defaults as if we had received a complex URL:
							$m = '';
							$more = 1; // Display the extended entries' text
							$c = 1;    // Display comments
							$tb = 1;   // Display trackbacks
							$pb = 1;   // Display pingbacks

							if( preg_match( "#^p([0-9]+)$#", $path_elements[$i], $req_post ) )
							{ // The last param is of the form p000
								// echo 'post number';
								$p = $req_post[1];		// Post to display
							}
							else
							{ // Last param is a string, we'll consider this to be a post urltitle
								$title = $path_elements[$i];
								// echo 'post title : ', $title;
							}
						}
					}
				}
				elseif( isset( $path_elements[$i] ) && substr( $path_elements[$i], 0, 1 ) == 'w' )
				{ // We consider this a week number
					$w = substr( $path_elements[$i], 1, 2 );
				}
			}
			else
			{	// We did not get a number/year...
				$path_error = 404;
			}
		}

		if( $path_error == 404 )
		{	// The request points to something we won't be able to resolve:
			require dirname(__FILE__).'/_404_not_found.page.php'; // error & exit
		}
	}
}

if( (!empty($p)) || (!empty($title)) || (!empty($preview)) )
{ // We are going to display a single post
	$disp = 'single';
}

if( empty( $disp ) )
{ // default display:
	$disp = 'posts';
}

if( ($disp == 'posts') || ($disp == 'single') )
{ // If we are going to display posts and not something special...

	// On single post requests, check if we're on the right blog!
	if( $redirect_to_postblog && ( $disp == 'single' ) )
	{ // Yes we need to check.
		if( !empty($p) )
			$Item = $ItemCache->get_by_ID( $p, false );
		else
			$Item = $ItemCache->get_by_urltitle( $title, false );

		if( ($Item !== false) && ($Item->blog_ID != $blog) )
		{ // We're on the wrong blog (probably an old permalink) let's redirect
			$new_permalink = $Item->gen_permalink( '', '', false, '&' );

			header ("Location: $new_permalink");
			exit();
		}
	}

	// Note: even if we request the same post, the following will do more restrictions (dates, etc.)
	$MainList = & new ItemList(
		$blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order,
		$orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact,
		$preview, $unit, $timestamp_min, $timestamp_max, $title, $dstart );

	// DO we still use those old style globals? :
	$result_num_rows = $MainList->get_num_rows();
	$postIDlist = & $MainList->postIDlist;
	$postIDarray = & $MainList->postIDarray;
}
else
{ // we are not trying to display posts:
	$result_num_rows = 0;
}

// Default display params:

// Displaying of blog list on templates?
if( !isset($display_blog_list) )
{ // If not already set in stub:
	$display_blog_list = $Blog->get('disp_bloglist');
}


/*
 * Now, we'll jump to displaying!
 */

// Check if an rss syndication was requested

// This will be handled like any other skin, except that it will not be stored in a cookie
if( !empty($tempskin) )
{
	$tempskin = basename_dironly( $tempskin ); // make sure to have no relative paths in there
	$Debuglog->add( 'Sanitized $tempskin: '.$tempskin, 'skin' );

	if( !empty($tempskin) )
	{
		if( !empty($disp) && $disp == 'comments' && skin_exists( $tempskin, '_lastcomments.php' ) )
		{
			$Debuglog->add( 'Including tempskin: '.$tempskin.' (comments)', 'skin' );

			require( get_path( 'skins' ).$tempskin.'/_lastcomments.php' );
			exit;
		}
		elseif( skin_exists( $tempskin, '_main.php' ) )
		{
			$Debuglog->add( 'Including tempskin: '.$tempskin.' (main)', 'skin' );
			require( get_path( 'skins' ).$tempskin.'/_main.php' );
			exit;
		}
	}
	$Debuglog->add( 'tempskin requested, but skin not found ('.$tempskin.')', array( 'skin', 'error' ) );
	Log::display( '', '', 'tempskin requested, but not found/invalid.', 'error' );
	debug_die();
}

// Let's check if a skin has been forced in the stub file:
// Note: URL skin requests are handled with param() 20 lines below
if( !isset( $skin ) )
{ // No skin forced in stub (not even '' for no-skin)...
	$Debuglog->add( 'No skin forced.', 'skin' );
	// We're going to need a default skin:
	if( !isset( $default_skin )             // No default skin forced in stub
			|| !skin_exists( $default_skin ) )  // Or the forced default does not exist
	{ // Use default from the database
		$default_skin = $Blog->get('default_skin');
	}

	if( !skin_exists( $default_skin ) || empty( $default_skin ) )
	{ // blog's default skin does not exist
		// Because a lot of bloggers will set themseleves a cookie and delete the default skin,
		// we have to make this fool proof extra checking!
		printf( T_('The default skin [%s] set for blog [%s] does not exist. It must be properly set in the <a %s>blog properties</a> or properly overriden in a stub file. Contact the <a %s>webmaster</a>...'), $default_skin , $Blog->dget('shortname'), 'href="'.$admin_url.'blogs.php?action=edit&amp;blog='.$Blog->ID.'"', 'href="mailto:'.$admin_email.'"');
		debug_die();
	}
	$Debuglog->add( '$default_skin = '.$default_skin, 'skin' );

	if( $Blog->get('force_skin') )
	{ // Blog params tell us to force the use of default skin
		$skin = $default_skin;
		$Debuglog->add( 'Forced skin: '.$skin, 'skin' );
	}
	else
	{ // Get the saved skin in cookie or default:
		$Request->param( $cookie_state, 'string', $default_skin );
		$Debuglog->add( 'Skin after looking at cookie: '.$$cookie_state, 'skin' );
		// Get skin by params or default to cookie
		// (if cookie was not set, the $$cookie_state contains default skin!)
		$Request->param( 'skin', 'string', $$cookie_state );
		$Debuglog->add( 'Skin after looking at params: '.$skin, 'skin' );
	}
}


// At this point $skin holds the name of the skin we want to use, or '' for no skin!

// check to see if we want to display the popup or the main template
$Request->param( 'template', 'string', 'main', true );

if( !empty( $skin ) )
{ // We want to display now:
	$skin = basename_dironly( $skin ); // make sure to have no relative path in there
	$Debuglog->add( 'Sanitized skin: '.$skin, 'skin' );

	if( !skin_exists($skin) )
	{
		// echo "<p>Oops, no such skin!</p>";
		$skin = $default_skin;
		$Debuglog->add( 'Skin does not exist. Using default skin: '.$default_skin, 'skin' );
	}
	elseif( !empty($_GET['skin']) || !empty($_POST['skin']) )
	{ // We have just asked for the skin explicitely (and it is valid)
		// Set a cookie to remember it:
		if( !setcookie( $cookie_state, $skin, $cookie_expires, $Blog->get('cookie_path'), $Blog->get('cookie_domain')) )
		{ // This damn failed !
			Log::display( '', '', 'setcookie failed (skin)!', 'error' );
		}
		else
		{
			$Debuglog->add( 'Setting skin cookie: '.$skin, 'skin' );
		}
	}

	if( $template == 'popup' )
	{ // Do the popup display
		require( get_path( 'skins' ).$skin.'/_popup.php' );
	}
	else
	{ // Do the main display
		require( get_path( 'skins' ).$skin.'/_main.php' );
	}
}
else
{ // we don't want to use a skin
	if( $template == 'popup' )
	{ // Do the popup display
		require( get_path( 'skins' ).'_popup.php' );
		exit();
	}

	$Debuglog->add( 'No skin or popup requested.', 'skin' );
	// If we end up here the blog file should be a full template, not just a stub...
}


/*
 * $Log$
 * Revision 1.28  2005/11/19 01:39:02  blueyed
 * Fix tempskin handling (patch by marian) and add debugging output (also to skin handling). Also, remove ereg() call that isn't necessary anymore when using basename_dironly()
 *
 * Revision 1.27  2005/11/18 22:05:41  fplanque
 * no message
 *
 * Revision 1.26  2005/11/16 21:53:49  fplanque
 * minor
 *
 * Revision 1.25  2005/11/14 18:57:05  blueyed
 * Do not resolve extra path when generating static pages. This fixes the 404 error when generating static pages from the backoffice, but is not what we want for memcache'd pages really!
 *
 * Revision 1.24  2005/11/14 18:23:13  blueyed
 * Remove experimental memcache support.
 *
 * Revision 1.23  2005/11/06 10:43:19  marian
 * changes to make the multi-domain feature working
 *
 * Revision 1.22  2005/10/30 03:47:43  blueyed
 * todo, question, indent
 *
 * Revision 1.21  2005/10/27 15:25:03  fplanque
 * Normalization; doc; comments.
 *
 * Revision 1.20  2005/10/19 09:07:15  marian
 * Changes regarding multi-domain feature
 *
 * Revision 1.19  2005/10/18 18:45:58  fplanque
 * some rollbacks...
 *
 * Revision 1.18  2005/10/18 11:04:16  marian
 * Added extra functionality to support multi-domain feature.
 *
 * Revision 1.17  2005/09/16 13:38:20  fplanque
 * bugfix
 *
 * Revision 1.16  2005/09/14 18:10:47  fplanque
 * bugfix
 *
 * Revision 1.15  2005/09/13 20:36:42  fplanque
 * a little more antispam
 *
 * Revision 1.14  2005/08/25 19:02:10  fplanque
 * categories plugin phase 2
 *
 * Revision 1.13  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.12  2005/04/06 13:33:29  fplanque
 * minor changes
 *
 * Revision 1.11  2005/04/05 13:44:22  jwedgeco
 * Added experimental memcached support. Needs much more work. Use at your own risk.
 *
 * Revision 1.10  2005/03/10 16:08:39  fplanque
 * added dstart param
 *
 * Revision 1.9  2005/03/09 20:29:39  fplanque
 * added 'unit' param to allow choice between displaying x days or x posts
 * deprecated 'paged' mode (ultimately, everything should be pageable)
 *
 * Revision 1.8  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.7  2005/02/24 00:20:03  blueyed
 * DB_USER is not always defined (scripts from /xmlsrc)
 *
 * Revision 1.6  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.5  2005/02/15 22:05:03  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.4  2005/01/21 21:03:57  jwedgeco
 * Added some debug statements to log the decoding of the date from the request uri.
 *
 * Revision 1.3  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.66  2004/10/11 18:49:10  fplanque
 * Edited code documentation.
 *
 * Revision 1.42  2004/5/28 17:18:58  jeffbearer
 * added function to keep the active session fresh, part of the who's online feature
 */
?>