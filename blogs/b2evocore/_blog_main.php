<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file loads the blog!
 */

// Initialize everything:
require_once (dirname(__FILE__).'/_main.php');

// Getting GET or POST parameters:
if(!isset($default_to_blog)) $default_to_blog = 1;
set_param( 'blog', 'integer', $default_to_blog, true );
set_param( 'p', 'integer' );							// Specific post number to display
set_param( 'm', 'integer', '', true );							// YearMonth(Day) to display
set_param( 'w', 'integer', '', true );							// Week number
set_param( 'cat', 'string', '', true );							// List of cats to restrict to
set_param( 'catsel', 'array', array(), true );	// Array of cats to restrict to
set_param( 'author', 'integer', '', true );					// List of authors to restrict to
set_param( 'order', 'string', 'DESC', true );		// ASC or DESC
set_param( 'orderby', 'string', '', true );					// list of fields to order by
set_param( 'posts', 'integer', '', true );					// # of posts to display on the page
set_param( 'paged', 'integer', '', true );					// List page number in paged display
set_param( 'poststart', 'integer', '', true );			// Start results at this position
set_param( 'postend', 'integer', '', true );				// End results at this position
// set_param( 'search', 'string' );				// obsolete (dangerous!)
set_param( 's', 'string', '', true );								// Search string
set_param( 'sentence', 'string', 'AND', true );				// Search for sentence or for words
set_param( 'exact', 'integer', '', true );					// Require exact match of title or contents
set_param( 'preview', 'integer', 0, true );				// Is this preview ?
set_param( 'calendar', 'string', '', true );				// Display a specific month in the calendar
set_param( 'c', 'string' );
set_param( 'withcomments', 'integer' );
set_param( 'page', 'integer' );
set_param( 'more', 'integer', 0 );
set_param( 'tb', 'integer', 0 );
set_param( 'pb', 'integer', 0 );
set_param( 'disp', 'string' );
set_param( 'stats', 'integer', 0 );					// deprecated
if(!isset($timestamp_min)) $timestamp_min = '';
if(!isset($timestamp_max)) $timestamp_max = '';

if( empty($disp) )
{
	if( $c == 'last') 
	{	// Trabslate old last comments caller
		$disp = 'comments';
	}
	elseif( $stats )
	{	// Trabslate old stats caller
		$disp = 'stats';
	}
}

// Getting current blog info:
get_blogparams();

// Activate matching locale:
locale_activate( locale_by_lang( get_bloginfo('lang') ) );

// Extra path info decoding:
$ReqURI = $_SERVER['REQUEST_URI'];
// echo ":".$ReqURI."<br />";
$path_string = explode( '?', $ReqURI, 2 );
$path_elements = explode( '/', $path_string[0], 20 );						// slice it
$stub = get_bloginfo( 'stub' );
// echo "stub=", $stub;
for( $i = count( $path_elements )-1; $i >= 0; $i-- )
{
	// echo ' -- i=', $i, ' this=', $path_elements[$i] ;
	if( $path_elements[$i] == $stub )
	{
		// echo " FOUND!";
		break;
	}
}
$i++;
if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
{	// We'll consider this to be the year
	$m = $path_elements[$i++];
	
	if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
	{	// We'll consider this to be the month
		$m .= $path_elements[$i++];

		if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
		{	// We'll consider this to be the day
			$m .= $path_elements[$i++];

			if( isset( $path_elements[$i] ) && ereg( "p([0-9]+)", $path_elements[$i], $req_post )  )
			{	// The last param is of the form p000
				// We are accessing a post by permalink
				// Set a lot of defaults as if we had received a complex URL:
				$p = $req_post[1];		// Post to display
				$m = '';
				$more=1;							// display the extended entries' text
				$c=1;									// Display comments
				$tb=1;								// Display trackbacks
				$pb=1;								// Display pingbacks
			
				// TODO: allow overrides
			}
		}
	}
	elseif( substr( $path_elements[$i], 0, 1 ) == 'w' )
	{	// We consider this a week number
		$w = substr( $path_elements[$i], 1, 2 );
	}
}
// else echo "not numeric: ",  $path_elements[$i];



if ( empty( $disp ) )
{ // If we are going to display posts and not something special...

	$MainList = new ItemList( $blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order, $orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact, $preview, '', '', $timestamp_min, $timestamp_max );
	
	$posts_per_page = $MainList->posts_per_page;
	$what_to_show = $MainList->what_to_show;
	$request = & $MainList->request;
	// $result = & $MainList->result;
	$result_num_rows = $MainList->get_num_rows();
	$postIDlist = & $MainList->postIDlist;
	$postIDarray = & $MainList->postIDarray;
}
else
{	// we are not trying to display posts:
	$result_num_rows = 0;
}


/*
 * Now, we'll jump to displaying!
 */
// Get the saved skin in cookie or default:
if(!isset($default_skin)) $default_skin = '';
set_param( $cookie_state, 'string', $default_skin );
// Get skin by params or default to cookie or default
set_param( 'skin', 'string', $$cookie_state );
// check to see if we want to display the popup or the main template
set_param( 'template', 'string', 'main' );
if( $skin != '' )
{	// We want to display now:
	$skin_folder = get_path( 'skins' );
	if( ereg( '([^-A-Za-z0-9._]|\.\.)', $skin ) )
	{
		// echo ("<p>Invalid skin name!</p>");
		$skin = $default_skin;
	}
	elseif( !is_dir($skin_folder.'/'.$skin) )
	{
		// echo "<p>Oops, no such skin!</p>";
		$skin = $default_skin;
	}
	elseif( (!empty($_GET['skin'])) || (!empty($_POST['skin'])) )
	{	// We have just asked for the skin explicitely
		// Set a cookie to remember it:
		// Including config and functions files
		if( ! setcookie( $cookie_state, $skin, $cookie_expires, $cookie_path, $cookie_domain) )
		{	// This damn failed !
			echo "<p>setcookie failed!</p>";
		}
		// Erase OLD versions cookies:
		if( ! setcookie( 'b2evostate', '', $cookie_expired, $cookie_path, $cookie_domain) )
		{	// This damn failed !
			echo "<p>setcookie failed!</p>";
		}
		if( ! setcookie( 'b2evostate', '', $cookie_expired, '/') )
		{	// This damn failed !
			echo "<p>setcookie failed!</p>";
		}
	}

	if( $template == 'popup' )
	{	// Do the popup display
		require "$skin_folder/$skin/_popup.php";
	}
	else
	{	// Do the main display
		require "$skin_folder/$skin/_main.php";
	}
}
else
{	// no skin specified !
	if( $template == 'popup' )
	{	// Do the popup display
		require dirname(__FILE__).'/'.$pathcore_out.'/_popup.php';
		exit();
	}
}
?>