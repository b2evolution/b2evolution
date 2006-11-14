<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/_main.inc.php';
require_once $model_path.'items/_itemlist2.class.php';

$Timer->start( '_blog_main.inc' );

// Getting GET or POST parameters:

/*
 * blog ID. This is a little bit special.
 * We can't default to $default_to_blog here because the param should always be included in regenerate_url() when present.
 * This will prevent weird indexing/search results in case the default changes after indexing.
 * On some occasions, we'll manually filter it out of rgenerate_url() because we know wer go through a stub for example.
 */
param( 'blog', 'integer', 0, true );

param( 'p', 'integer', '', true );              // Specific post number to display
param( 'title', 'string', '', true );						// urtitle of post to display

param( 'preview', 'integer', 0, true );         // Is this preview ?

param( 'disp', 'string', 'posts', true );

param( 'tempskin', 'string', '', true );


if( !isset($timestamp_min) ) $timestamp_min = '';
if( !isset($timestamp_max) ) $timestamp_max = '';

if( $preview )
{ // Ignore this hit
	$Hit->ignore = true;
}

if( param( 'stats', 'integer', 0 ) || $disp == 'stats' )
{
	require $view_path.'errors/_410_stats_gone.page.php'; // error & exit
}

// Getting current blog info:
$BlogCache = & get_Cache( 'BlogCache' );
$Blog = & $BlogCache->get_by_ID( $blog );


/*
	TODO: blueyed>> This should get moved as default to the locale detection in _main.inc.php,
	        as we only want to activate the I/O charset, which is probably the user's..
	        It prevents using a locale/charset in the front office, apart from the one given as default for the blog!!
fp>there is no blog defined in _main and there should not be any
blueyed> Sure, but that means we should either split it, or use the locale here only, if there's no-one given with higher priority.
*/
// Activate matching locale:
$Debuglog->add( 'Activating blog locale: '.$Blog->get('locale'), 'locale' );
locale_activate( $Blog->get('locale') );


// Re-Init charset handling, in case current_charset has changed:
if( init_charsets( $current_charset ) )
{
  // Reload Blog(s) (for encoding of name, tagline etc):
  $BlogCache->clear();

  $Blog = & $BlogCache->get_by_ID( $blog );
}


/* -------------------------
 * Extra path info decoding:
 * -------------------------
 * Decoding should try to work like this:
 *
 * baseurl/blog-urlname/junk/.../junk/post-title    -> points to a single post (no ending slash)
 * baseurl/blog-urlname/junk/.../junk/p142          -> points to a single post
 * baseurl/blog-urlname/2006/                       -> points to a yearly archive because of ending slash + 4 digits
 * baseurl/blog-urlname/2006/12/                    -> points to a monthly archive
 * baseurl/blog-urlname/2006/12/31/                 -> points to a daily archive
 * baseurl/blog-urlname/2006/w53/                   -> points to a weekly archive
 * baseurl/blog-urlname/junk/.../junk/chap-urlname/ -> points to a single chapter (because of ending slash)
 * Note: category names cannot be named like this [a-z][0-9]+
 */
if( ! isset( $resolve_extra_path ) ) { $resolve_extra_path = true; }
if( $resolve_extra_path )
{
	// Check and Remove blog base URI from ReqPath:
	$blog_baseuri = substr( $Blog->get('baseurl'), strlen( $Blog->get('baseurlroot') ) );
	$Debuglog->add( 'blog_baseuri: "'.$blog_baseuri.'"', 'params' );

	if( ($pos = strpos( $ReqPath, $blog_baseuri )) !== false )
	{ // note: $pos will typically be 0
		$path_string = substr( $ReqPath, $pos+strlen( $blog_baseuri ) );

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
		// pre_dump( $path_elements );

		if( isset( $path_elements[0] ) && preg_match( '#.+\.php[0-9]?$#', $path_elements[0] ) )
		{ // Ignore element ending with .php
			array_shift( $path_elements );
			$Debuglog->add( 'Ignoring *.php in extra path info' , 'params' );
		}

		if( isset( $path_elements[0] ) && preg_match( '#^'.$Blog->get( 'stub' ).'$#', $path_elements[0] )  )
		{ // Ignore stub file (if it ends with .php it should aready have been filtered out above)
			array_shift( $path_elements );
			$Debuglog->add( 'Ignoring stub file in extra path info' , 'params' );
		}
		// pre_dump( $path_elements );

		$path_error = 0;

		// Do we still have extra path info to decode?
		if( count($path_elements) )
		{
			// Does the pathinfo end with a / ?
			if( substr( $path_string, -1 ) != '/' )
			{ // NO ENDING SLASH -> We'll consider this to be a ref to a post:
				// Set a lot of defaults as if we had received a complex URL:
				$m = '';
				$more = 1; // Display the extended entries' text
				$c = 1;    // Display comments
				$tb = 1;   // Display trackbacks
				$pb = 1;   // Display pingbacks

				$path_element = $path_elements[count($path_elements)-1];
				if( preg_match( "#^p([0-9]+)$#", $path_element, $req_post ) )
				{ // The last param is of the form p000
					// echo 'post number';
					$p = $req_post[1];		// Post to display
				}
				else
				{ // Last param is a string, we'll consider this to be a post urltitle
					$title = $path_element;
					// echo 'post title : ', $title;
				}
			}
			else
			{	// ENDING SLASH -> we are looking for a daterange OR a chapter:

				$last_part = $path_elements[count($path_elements)-1];
				// echo $last_part;
				if( preg_match( '|^w?[0-9]+$|', $last_part ) )
				{ // Last part is a number or a "week" number:
					$i=0;
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
				}
				elseif( preg_match( '|^[A-Za-z0-9\-]+$|', $last_part ) )
				{	// We are pointing to a chapter/category:
					$ChapterCache = & get_Cache( 'ChapterCache' );
					/**
					 * @var Chapter
					 */
					$Chapter = & $ChapterCache->get_by_urlname( $last_part, false );
					if( empty( $Chapter ) )
					{	// We could not match a chapter...
						$path_error = 404;
					}
					else
					{	// We could match a chapter from the extra path:
						$cat = $Chapter->ID;
			    }
				}
				else
				{	// We did not get anything we can decode...
					// echo 'neither number nor cat';
					$path_error = 404;
				}
			}
		}

		if( $path_error == 404 )
		{	// The request points to something we won't be able to resolve:
			require $view_path.'errors/_404_not_found.page.php'; // error & exit
		}
	}
}


if( !empty($preview) )
{
	$disp = 'single';
}
elseif( !empty($p) || !empty($title) )
{ // We are going to display a single post
	$disp = 'single';

	// Make sure the single post we're requesting (still) exists:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( !empty($p) )
	{
		$Item = & $ItemCache->get_by_ID( $p, false );
	}
	else
	{
		$title = preg_replace( '/[^A-Za-z0-9]/', '-', $title );
		$Item = & $ItemCache->get_by_urltitle( $title, false );
	}
	if( empty( $Item ) )
	{	// Post doesn't exist! Let's go 404!
		// fp> TODO: ->viewing_allowed() for draft, private, protected and deprecated...
		require $view_path.'errors/_404_not_found.page.php'; // error & exit
	}

	// EXPERIMENTAL:
	// Please document encountered problems.
	if( $redirect_to_canonical_url )
	{
		$canoncical_url = $Item->get_permanent_url( '', '', false, '&' );
		// pre_dump( $canoncical_url, $ReqHost.$ReqURI );
		// There may be some parameters additional at the end of the URL, but the beginning should be canoncial.
		if( strpos( $ReqHost.$ReqURI, $Item->get_permanent_url( '', '', false, '&' ) ) !== 0 )
		{	// The requested URL does not look like the canonical URL for this post,
			// REDIRECT TO THE CANONICAL URL:
			// fp> TODO: we're going to lose the additional params, it would be better to keep them...
			$Debuglog->add( 'Redirecting to canonical URL ['.$canoncical_url.'].' );
			header_redirect( $canoncical_url, true );
		}
	}

}


// TODO: dh> we should first handle $skin, so that if it's provided by a plugin, we do not have to set $MainList etc..


if( $disp == 'posts' )
{ // default display:
	// EXPERIMENTAL:
	// Please document encountered problems.
	if( $redirect_to_canonical_url )
	{
    param_compile_cat_array();  // fp> is this overkill here?

    if( preg_match( '|^[0-9]+$|', $cat ) )
    { // We are requesting a specific category (either byparam or extra path)
		  // Check if this was a complex request or just a category request:
      // fp> Note: catsel[]= will not be redirected on purpose
		  if( empty($_SERVER['QUERY_STRING'])		// no additional param
			  || preg_match( '|^cat=[0-9]+$|', $_SERVER['QUERY_STRING'] ) )	// just a single cat param and nothing else
		  {	// This was just a category request, let's check if the URL was canonical:
        if( !isset( $Chapter ) )
        {
					$ChapterCache = & get_Cache( 'ChapterCache' );
					$Chapter = & $ChapterCache->get_by_ID( $cat_array[0], false );
        }
			  $canoncical_url = $Chapter->get_permanent_url( NULL, NULL, '&' );
			  if( $ReqHost.$ReqURI != $canoncical_url )
			  {
				  // REDIRECT TO THE CANONICAL URL:
				  // fp> TODO: we're going to lose the additional params, it would be better to keep them...
				  header_redirect( $canoncical_url, true );
			  }
		  }
    }
  }
}


if( ($disp == 'posts') || ($disp == 'single') )
{ // If we are going to display posts and not something special...

	// Note: even if we request the same post as $Item above, the following will do more restrictions (dates, etc.)

	$MainList = & new ItemList2( $Blog, $timestamp_min, $timestamp_max, $Settings->get('posts_per_page') );

	if( ! $preview )
	{
		// pre_dump( $MainList->default_filters );
		$MainList->load_from_Request( false );
		// pre_dump( $MainList->filters );

		// Run the query:
		$MainList->query();

		// Old style globals for category.funcs:
		$postIDlist = $MainList->get_page_ID_list();
		$postIDarray = $MainList->get_page_ID_array();
	}
	else
	{	// We want to preview a single post, we are going to fake a lot of things...
		$MainList->preview_from_request();

		// Legacy for the category display
		$cat_array = array();
	}

	param( 'more', 'integer', 0, true );
	param( 'page', 'integer', 1, true ); // Post page to show
	param( 'c',    'integer', 0, true ); // Display comments?
	param( 'tb',   'integer', 0, true ); // Display trackbacks?
	param( 'pb',   'integer', 0, true ); // Display pingbacks?
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

// Check if a temporary skin has been requested (used for RSS syndication for example):
if( !empty($tempskin) )
{
	// This will be handled like any other skin, except that it will not be stored in a cookie:
	$skin = $tempskin;
	$default_skin = '_rss'; // That's gonna be the fallback for now.
	// TODO [post-phoenix]: fp> decide when or when not you are allowed to override a 'force_skin' directive,
	//   and when or when not you are allowed to fall back to $default_skin.
	// TODO: dh> there should be no $default_skin and with an invalid $tempskin there should be rather a page with a notice/debug_die()
  //       Use case: a Plugin registers a skin param and gets disabled some day: there should be an error instead of a default
}

// Let's check if a skin has been forced in the stub file:
// Note: URL skin requests are handled with param() 20 lines below
// Note: with "register_globals = On" this may be set from URL.. (in which case the code 20 line sbelow becomes useless)
//       blueyed>> You've said that it's not security issue etc.. but I still would init $skin in /conf/_advanced.php and use empty() here.
if( !isset( $skin ) )
{ // No skin forced in stub (not even '' for no-skin)...
	$Debuglog->add( 'No skin forced.', 'skin' );
	// We're going to need a default skin:
	if(  ( !isset( $default_skin ) )          // No default skin forced in stub
		|| ( !skin_exists( $default_skin ) ) )  // Or the forced default does not exist
	{ // Use default from the database
		$default_skin = $Blog->get('default_skin');
	}

	if( !skin_exists( $default_skin ) || empty( $default_skin ) )
	{ // blog's default skin does not exist
		// Because a lot of bloggers will set themseleves a cookie and delete the default skin,
		// we have to make this fool proof extra checking!
		printf( T_('The default skin [%s] set for blog [%s] does not exist. It must be properly set in the <a %s>blog properties</a> or properly overriden in a stub file. Contact the <a %s>webmaster</a>...'), $default_skin , $Blog->dget('shortname'), 'href="'.$admin_url.'?ctrl=collections&amp;action=edit&amp;blog='.$Blog->ID.'"', 'href="mailto:'.$admin_email.'"');
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
		param( $cookie_state, 'string', $default_skin, false, true ); // override (in case there has been "param($cookie_state)" before, which set it already to '')
		$Debuglog->add( 'Skin after looking at cookie: '.$$cookie_state, 'skin' );
		// Get skin by params or default to cookie
		// (if cookie was not set, the $$cookie_state contains default skin!)
		param( 'skin', 'string', $$cookie_state );
		$Debuglog->add( 'Skin after looking at params: '.$skin, 'skin' );
	}
}


$Timer->pause( '_blog_main.inc');


// At this point $skin holds the name of the skin we want to use, or '' for no skin!

// check to see if we want to display the popup or the main template
param( 'template', 'string', 'main', true );


// Trigger plugin event:
$Plugins->trigger_event( 'BeforeBlogDisplay', array('skin'=>$skin) );


if( !empty( $skin ) )
{ // We want to display now:

	if( empty( $tempskin )
	 && ( !empty($_GET['skin']) || !empty($_POST['skin'] ) ) )
	{ // We have just asked for a skin change explicitely
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

	// TODO: sanitize $template and allow any request on _xxx.tpl.php or sth like that.
	if( $template == 'popup' )
	{ // Do the popup display
		require( $skins_path.$skin.'/_popup.php' );
	}
	else
	{ // Do the main display
		$Timer->start( 'skin/_main.inc' );
		if( $skin_provided_by_plugin = skin_provided_by_plugin($skin) )
		{
			$Plugins->call_method( $skin_provided_by_plugin, 'DisplaySkin', $tmp_params = array('skin'=>$skin) );
		}
		else
		{
			require( $skins_path.$skin.'/_main.php' );
		}
		$Timer->pause( 'skin/_main.inc' );
	}

	// log the hit on this page (in case the skin hasn't already done so)
	$Hit->log();
}
else
{ // we don't want to use a skin
	if( $template == 'popup' )
	{ // Do the popup display
		require( $skins_path.'_popup.php' );

		// log the hit on this page (in case the skin hasn't already done so)
		$Hit->log();

		exit();
	}

	$Debuglog->add( 'No skin or popup requested.', 'skin' );
	// If we end up here the blog file should be a full template, not just a stub...
	// Note: The full template SHOULD call $Hit->log();
}


/*
 * $Log$
 * Revision 1.48  2006/11/14 21:56:11  blueyed
 * Debuglog-entry, when redirecting to $canoncical_url
 *
 * Revision 1.47  2006/11/11 20:33:14  blueyed
 * Moved BeforeBlogDisplay hook to after $skin has been determined
 *
 * Revision 1.46  2006/10/24 14:03:52  blueyed
 * Type $c param ("Display comments?") to integer
 *
 * Revision 1.45  2006/10/08 22:59:30  blueyed
 * Added GetProvidedSkins and DisplaySkin hooks. Allow for optimization in Plugins::trigger_event_first_return()
 *
 * Revision 1.44  2006/10/04 12:55:24  blueyed
 * - Reload $Blog, if charset has changed for Blog locale
 * - only update DB connection charset, if not forced with $db_config['connection_charset']
 *
 * Revision 1.43  2006/09/20 14:28:34  blueyed
 * Fixed typo in function name Item::peview_request()
 *
 * Revision 1.42  2006/09/12 00:31:30  fplanque
 * 301 redirects to canonical URLs
 * EXPERIMENTAL - please report problems
 *
 * Revision 1.41  2006/09/11 20:53:33  fplanque
 * clean chapter paths with decoding, finally :)
 *
 * Revision 1.40  2006/09/11 00:43:03  fplanque
 * transposed decoding. So far it doesn't decode much more but it's more laxist regarding junk before a post title
 *
 * Revision 1.39  2006/09/07 00:48:55  fplanque
 * lc parameter for locale filtering of posts
 *
 * Revision 1.38  2006/09/06 21:39:21  fplanque
 * ItemList2 fixes
 *
 * Revision 1.37  2006/09/06 18:34:04  fplanque
 * Finally killed the old stinkin' ItemList(1) class which is deprecated by ItemList2
 *
 * Revision 1.36  2006/08/29 00:26:11  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.34  2006/08/26 20:30:42  fplanque
 * made URL titles Google friendly
 *
 * Revision 1.33  2006/08/21 00:03:12  fplanque
 * obsoleted some dirty old thing
 *
 * Revision 1.32  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.31  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.30  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.29  2006/08/19 00:41:16  fplanque
 * planted some freaky timers!
 *
 * Revision 1.28  2006/08/07 16:49:35  fplanque
 * doc
 *
 * Revision 1.27  2006/08/07 00:32:07  blueyed
 * Fixed previewing, if $redirect_to_postblog setting is used
 *
 * Revision 1.26  2006/07/24 00:05:44  fplanque
 * cleaned up skins
 *
 * Revision 1.25  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 * Revision 1.24  2006/06/30 22:58:13  blueyed
 * Abstracted charset conversation, not much tested.
 *
 * Revision 1.23  2006/05/29 19:54:45  fplanque
 * no message
 *
 * Revision 1.22  2006/05/19 17:03:58  blueyed
 * locale activation fix from v-1-8, abstraction of setting DB connection charset
 *
 * Revision 1.21  2006/05/14 17:59:59  blueyed
 * "try/catch" SET NAMES (Thanks, bodo)
 *
 * Revision 1.20  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.19  2006/05/05 15:46:03  blueyed
 * Nasty bug that produces empty pages..
 *
 * Revision 1.18  2006/05/03 01:53:42  blueyed
 * Encode subject in mails correctly (if mbstrings is available)
 *
 * Revision 1.17  2006/04/29 01:24:04  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.16  2006/04/24 17:54:18  blueyed
 * todo
 *
 * Revision 1.15  2006/04/24 15:43:35  fplanque
 * no message
 *
 * Revision 1.14  2006/04/21 17:05:08  blueyed
 * cleanup
 *
 * Revision 1.13  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.12  2006/04/11 20:37:57  blueyed
 * Re-activated /admin/-redirect
 *
 * Revision 1.11  2006/04/11 17:10:22  fplanque
 * The view is not a place for redirects!
 *
 * Revision 1.10  2006/04/10 22:05:26  blueyed
 * Fixed path to stats gone page
 *
 * Revision 1.8  2006/03/18 14:38:36  blueyed
 * fix
 *
 * Revision 1.6  2006/03/16 23:33:53  blueyed
 * Fixed path to 404-error-page
 *
 * Revision 1.5  2006/03/12 23:46:13  fplanque
 * experimental
 *
 * Revision 1.4  2006/03/12 23:08:53  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/12 03:48:51  blueyed
 * bugfix
 *
 * Revision 1.2  2006/03/09 22:29:59  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.1  2006/02/23 21:11:55  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.45  2006/01/30 16:09:34  blueyed
 * doc
 *
 * Revision 1.44  2006/01/26 21:27:21  blueyed
 * add debug
 *
 * Revision 1.43  2006/01/26 19:27:58  fplanque
 * no message
 *
 * Revision 1.42  2006/01/25 19:19:17  blueyed
 * Fixes for blogurl handling. Thanks to BenFranske for pointing out the biggest issue (http://forums.b2evolution.net/viewtopic.php?t=6844)
 *
 * Revision 1.41  2006/01/22 22:41:59  blueyed
 * Timer, doc
 *
 * Revision 1.40  2006/01/04 19:07:48  fplanque
 * allow filtering on assignees
 */
?>