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
param( 'blog', 'integer', 0, true );  // Can't use $default_to_blog because the param must always be included in regenerate_url() when present
param( 'p', 'integer', '', true );              // Specific post number to display
param( 'm', 'integer', '', true );              // YearMonth(Day) to display
param( 'w', 'integer', -1, true );              // Week number
param( 'cat', 'string', '', true );             // List of cats to restrict to
param( 'catsel', 'array', array(), true );      // Array of cats to restrict to
param( 'author', 'integer', '', true );         // List of authors to restrict to
param( 'order', 'string', 'DESC', true );       // ASC or DESC
param( 'orderby', 'string', '', true );         // list of fields to order by
param( 'posts', 'integer', '', true );          // # of posts to display on the page
param( 'paged', 'integer', '', true );          // List page number in paged display
param( 'poststart', 'integer', '', true );      // Start results at this position
param( 'postend', 'integer', '', true );        // End results at this position
// param( 'search', 'string' );                 // obsolete (dangerous!)
param( 's', 'string', '', true );               // Search string
param( 'sentence', 'string', 'AND', true );     // Search for sentence or for words
param( 'exact', 'integer', '', true );          // Require exact match of title or contents
param( 'preview', 'integer', 0, true );         // Is this preview ?
param( 'calendar', 'string', '', true );        // Display a specific month in the calendar
param( 'c', 'string', '', true );
param( 'page', 'integer', '', true );
param( 'more', 'integer', 0, true );
param( 'title', 'string', '', true );						// urtitle of post to display
param( 'tb', 'integer', 0, true );
param( 'pb', 'integer', 0, true );
param( 'disp', 'string', '', true );
param( 'stats', 'integer', 0 );                 // deprecated
if( !isset($timestamp_min) ) $timestamp_min = '';
if( !isset($timestamp_max) ) $timestamp_max = '';

if( !empty($preview) )
{	// Preview is a special hit type:
	$Debuglog->add( 'filter_hit sequel: preview', 'hit' );
	$hit_type = 'preview';
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

// Getting current blog info:
$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );

// Activate matching locale:
$Debuglog->add( 'Activating blog locale: '.$Blog->get('locale'), 'locale' );
locale_activate( $Blog->get('locale') );

// -------------------------
// Extra path info decoding:
// -------------------------
// Check and Remove blog baseurl from ReqPath:
$blog_baseurl = substr( $Blog->get( 'baseurl' ), strlen( $baseurlroot ) );
if( ($pos = strpos( $ReqPath, $blog_baseurl )) !== false )
{ // note: $pos will typically be 0
	$path_string = substr( $ReqPath, $pos+strlen( $blog_baseurl ) );

	$Debuglog->add( 'Extra path info found! path_string=' . $path_string , 'params' );
		// echo "path=$path_string <br>";
	$path_elements = explode( '/', $path_string, 20 );  // slice it
	$i=0;
	// echo $path_elements[$i];
	if( isset( $path_elements[$i] ) && $path_elements[$i] == 'index.php' )
	{ // Ignore index.html
		$i++;
		$Debuglog->add( 'Ignoring index.php in extra path info' , 'params' );
	}

	if( isset( $path_elements[$i] ) && preg_match( '#^'.$Blog->get( 'stub' ).'(\.php)?$#', $path_elements[$i] )  )
	{ // Ignore stub file
		$i++;
		$Debuglog->add( 'Ignoring stub file in extra path info' , 'params' );
	}

	// echo $path_elements[$i];
	if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
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
      # echo $new_permalink;
			header ("Location: $new_permalink");
			exit();
		}
	}

	// Note: even if we request the same post, the following will do more restrictions (dates, etc.)
	$MainList = & new ItemList( $blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order,
															$orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact,
															$preview, '', '', $timestamp_min, $timestamp_max, $title );

	$posts_per_page = $MainList->posts_per_page;
	$what_to_show = $MainList->what_to_show;
	$request = & $MainList->request;
	// $result = & $MainList->result;
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
if( !isset( $skin ) )
{ // No skin forced in stub (not even '' for no-skin)...

	// We're going to need a default skin:
	if(  ( !isset( $default_skin ) ) 					// No default skin forced in stub
		|| ( !skin_exists( $default_skin ) ) )	// Or the forced default does not exist
	{ // Use default from the datatabse
		$default_skin = $Blog->get('default_skin');
	}

	if( !skin_exists( $default_skin )	|| empty( $default_skin ) )
	{ // blog's default skin does not exist
		// Because a lot of bloggers will set themseleves a cookie and delete the default skin,
		// we have to make this fool proof extra checking!
		printf( T_('The default skin [%s] set for blog [%s] does not exist. It must be properly set in the <a %s>blog properties</a> or properly overriden in a stub file. Contact the <a %s>webmaster</a>...'), $default_skin , $Blog->dget('shortname'), 'href="'.$admin_url.'blogs.php?action=edit&amp;blog='.$Blog->ID.'"', 'href="mailto:'.$admin_email.'"');
		die();
	}

	if( $Blog->get('force_skin') )
	{ // Blog params tell us to force the use of default skin
		$skin = $default_skin;
	}
	else
	{ // Get the saved skin in cookie or default:
		param( $cookie_state, 'string', $default_skin );
		// Get skin by params or default to cookie
		// (if cookie was not set, the $$cookie_state contains default skin!)
		param( 'skin', 'string', $$cookie_state );
	}
}

// At this point $skin holds the name of the skin we want to use, or '' for no skin!

// check to see if we want to display the popup or the main template
param( 'template', 'string', 'main', true );

if( !empty( $skin ) )
{ // We want to display now:

	if( (!empty($_GET['skin'])) || (!empty($_POST['skin'])) )
	{ // We have just asked for the skin explicitely
		// Set a cookie to remember it:
		// Including config and functions files   ??

		if( ! setcookie( $cookie_state, $skin, $cookie_expires, $Blog->get('cookie_path'), $Blog->get('cookie_domain')) )
		{ // This damn failed !
			echo "<p>setcookie failed!</p>";
		}
	}

	if( ereg( '([^-A-Za-z0-9._]|\.\.)', $skin ) )
	{
		// echo ("<p>Invalid skin name!</p>");
		$skin = $default_skin;
	}
	elseif( !skin_exists($skin) )
	{
		// echo "<p>Oops, no such skin!</p>";
		$skin = $default_skin;
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
	// If we end up here the blog file should be a full template, not just a stub...
}

/*
 * $Log$
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