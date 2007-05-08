<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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

// NOTE: it is okay to call this file before including config!

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/_main.inc.php';
require_once $model_path.'items/_itemlist2.class.php';

$Timer->start( '_blog_main.inc' );

// Getting GET or POST parameters:

/*
 * blog ID. This is a little bit special.
 * If it has been explicitely memorized already, we don't touch it.
 * Note: explicitely != auto_register_globals != stub file just setting it with $blog=x
 * Note: stub files should probably memorize the param explicitely!
 */
if( ! param_ismemorized('blog') )
{	// Not explicitely memorized yet, get param from GET or auto_register_globals OR a stub $blog = x:
	$Debuglog->add( 'blog param not memorized before _blog_main.inc', 'params' );
	// We default to 0 here because the param should always be included in regenerate_url() when present.
	// This will prevent weird indexing/search results in case the default changes after indexing.
  // On some occasions, we'll manually filter it out of regenerate_url() because we know we go through a stub for example.
	param( 'blog', 'integer', 0, true );
}

// Getting current blog info:
$BlogCache = & get_Cache( 'BlogCache' );
/**
 * @var Blog
 */
$Blog = & $BlogCache->get_by_ID( $blog, false );
if( empty( $Blog ) )
{
	require $view_path.'errors/_404_blog_not_found.page.php'; // error & exit
	// EXIT.
}

/*
 * _______________________________ Locale / Charset for the Blog _________________________________
 *
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


/*
 * _____________________________ Extra path info decoding ________________________________
 *
 * This will translate extra path into 'regular' params.
 *
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

		if( isset( $path_elements[0] )
				&& ( $path_elements[0] == $Blog->stub
						|| $path_elements[0] == $Blog->urlname ) )
		{ // Ignore stub file (if it ends with .php it should already have been filtered out above)
			array_shift( $path_elements );
			$Debuglog->add( 'Ignoring stub filename OR blog urlname in extra path info' , 'params' );
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


/*
 * ____________________________ Query params ____________________________
 *
 * Note: if the params have been set by the extra-path-info above, param() will not touch them.
 */
param( 'p', 'integer', '', true );              // Specific post number to display
param( 'title', 'string', '', true );						// urtitle of post to display
param( 'redir', 'string', 'yes', false );				// Do we allow redirection to canonical URL? (allows to force a 'single post' URL for commenting)

param( 'preview', 'integer', 0, true );         // Is this preview ?
if( $preview )
{ // Ignore this hit
	$Hit->ignore = true;
}

param( 'stats', 'integer', 0 );									// Deprecated but might still be used by spambots
param( 'disp', 'string', 'posts', true );

// In case these were not set by the stub:
if( !isset($timestamp_min) ) $timestamp_min = '';
if( !isset($timestamp_max) ) $timestamp_max = '';


/*
 * ____________________________ Get specific Item if requested ____________________________
 */
if( !empty($p) || !empty($title) )
{ // We are going to display a single post
	// Make sure the single post we're requesting (still) exists:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( !empty($p) )
	{
		$Item = & $ItemCache->get_by_ID( $p, false );
	}
	else
	{
		$title = preg_replace( '/[^A-Za-z0-9_]/', '-', $title );
		$Item = & $ItemCache->get_by_urltitle( $title, false );
	}
	if( empty( $Item ) )
	{	// Post doesn't exist! Let's go 404!
		// fp> TODO: ->viewing_allowed() for draft, private, protected and deprecated...
		require $view_path.'errors/_404_not_found.page.php'; // error & exit
	}
}


/*
 * ____________________________ "Clean up" the request ____________________________
 *
 * Make sure that:
 * 1) disp is set to "single" if single post requested
 * 2) URL is canonical if:
 *    - some content was requested in a weird/deprecated way
 *    - or if content identifiers have changed
 */
if( $stats || $disp == 'stats' )
{	// This used to be a spamfest...
	require $view_path.'errors/_410_stats_gone.page.php'; // error & exit
	// EXIT.
}
elseif( !empty($preview) )
{	// Preview
	$disp = 'single';
}
elseif( $disp == 'posts' && !empty($Item) )
{ // We are going to display a single post
	if( $Item->typ_ID == 1000 )
	{
		$disp = 'page';
	}
	else
	{
		$disp = 'single';
	}

	if( $redir == 'yes' )
	{ // $redir=no here allows to force a 'single post' URL for commenting

		// Check if the post has 'redirected' status:
		if( $Item->status == 'redirected' )
		{	// Redirect to the URL specified in the post:
			$Debuglog->add( 'Redirecting to post URL ['.$Item->url.'].' );
			header_redirect( $Item->url, true );
		}

		// Check if we want to redirect to a canonical URL for the post
		// Please document encountered problems.
		if( $redirect_to_canonical_url && $redir == 'yes' )
		{	// We want to redirect to the Item's canonical URL:

			$canoncical_url = $Item->get_permanent_url( '', '', '&' );
			// pre_dump( $canoncical_url, $ReqHost.$ReqURI );
			// There may be some parameters additional at the end of the URL, but the beginning should be canoncial.
			if( strpos( $ReqHost.$ReqURI, $Item->get_permanent_url( '', '', '&' ) ) !== 0 )
			{	// The requested URL does not look like the canonical URL for this post,
				// REDIRECT TO THE CANONICAL URL:
				// fp> TODO: we're going to lose the additional params, it would be better to keep them...
				$Debuglog->add( 'Redirecting to canonical URL ['.$canoncical_url.'].' );
				header_redirect( $canoncical_url, true );
				// EXITED.
			}
		}
	}
}
elseif( $disp == 'posts' )
{ // default display:
	// EXPERIMENTAL: Check if we want to redirect to a canonical URL for the category
	// Please document encountered problems.
	if( $redirect_to_canonical_url && $redir == 'yes' )
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
elseif( $disp == 'msgform' )
{	// We prefer robots not to index these pages:
	$robots_index = false;
}


/*
 * ______________________ DETERMINE WHICH SKIN TO USE FOR DISPLAY _______________________
 */

// Check if a temporary skin has been requested (used for RSS syndication for example):
param( 'tempskin', 'string', '', true );
if( !empty( $tempskin ) )
{ // This will be handled like any other skin:
	$skin = $tempskin;
}

if( isset( $skin ) )
{	// A skin has been requested by folder_name (url or stub):

	// Check validity of requested skin name:
	if( ereg( '([^-A-Za-z0-9._]|\.\.)', $skin ) )
	{
		debug_die( 'The requested skin name is invalid.' );
	}

	// Because a lot of bloggers will delete skins, we have to make this fool proof with extra checking:
	if( !empty( $skin ) && !skin_exists( $skin ) )
	{ // We want to use a skin, but it doesn't exist!
		$err_msg = sprintf( T_('The skin [%s] set for blog [%s] does not exist. It must be properly set in the <a %s>blog properties</a> or properly overriden in a stub file.'),
			htmlspecialchars($skin), $Blog->dget('shortname'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=display&amp;action=edit&amp;blog='.$Blog->ID.'"' );
		debug_die( $err_msg );
	}

	// EXPERIMENTAL:
	load_class( 'MODEL/skins/_skin.class.php' );
	$Skin = & new Skin();

}
else
{ // Use default skin from the database
	$SkinCache = & get_cache( 'SkinCache' );

	$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );

	$skin = $Skin->folder;
}


$Timer->pause( '_blog_main.inc');


/*
 * _______________________________ READY TO DISPLAY _______________________________
 *
 * At this point $skin holds the name of the skin we want to use, or '' for no skin!
 */


// Trigger plugin event:
// fp> TODO: please doc with example of what this can be used for
$Plugins->trigger_event( 'BeforeBlogDisplay', array('skin'=>$skin) );


if( !empty( $skin ) )
{ // We want to display with a skin now:

	if( $skin_provided_by_plugin = skin_provided_by_plugin($skin) )
	{
		$Plugins->call_method( $skin_provided_by_plugin, 'DisplaySkin', $tmp_params = array('skin'=>$skin) );
	}
	else
	{
		// Path for the current skin:
		$ads_current_skin_path = $skins_path.$skin.'/';

		$disp_handlers = array(
				'feedback-popup' => 'feedback_popup.tpl.php',
				// 'arcdir'   => 'arcdir.tpl.php',
				'comments' => 'latestcom.tpl.php',
				// 'msgform'  => 'msgform.tpl.php',
				// 'profile'  => 'profile.tpl.php',
				// 'subs'     => 'subscriptions.tpl.php',
				// All others will default to main.tpl.php
			);

		if( !empty($disp_handlers[$disp])
				&& file_exists( $disp_handler = $ads_current_skin_path.$disp_handlers[$disp] ) )
		{	// The skin has a customized page handler for this display:
			require $disp_handler;
		}
		else
		{	// Use the default handler from the skins dir:
			require $ads_current_skin_path.'main.tpl.php';
		}
	}

	// log the hit on this page (in case the skin hasn't already done so)
	$Hit->log();
}
else
{	// We don't use a skin. Hopefully the caller will do some displaying.
	// Set a few vars with default values, just in case...
	$ads_current_skin_path = $htsrv_path;

	// We'll just return to the caller now... (if we have not used a skin, the caller should do the display after this)
}


/*
 * $Log$
 * Revision 1.77  2007/05/08 00:54:31  fplanque
 * public blog list as a widget
 *
 * Revision 1.76  2007/05/07 18:59:45  fplanque
 * renamed skin .page.php files to .tpl.php
 *
 * Revision 1.75  2007/05/02 20:39:27  fplanque
 * meta robots handling
 *
 * Revision 1.74  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.73  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.72  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.71  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.70  2007/03/18 00:31:18  fplanque
 * Delegated MainList init to skin *pages* which need it.
 *
 * Revision 1.69  2007/03/12 00:03:47  fplanque
 * And finally: the redirect action :)
 *
 * Revision 1.68  2007/01/28 17:50:54  fplanque
 * started moving towards 2.0 skin structure
 *
 * Revision 1.67  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.66  2007/01/23 08:17:49  fplanque
 * another simplification...
 *
 * Revision 1.65  2007/01/23 08:07:16  fplanque
 * Fixed blog URLs including urlnames
 *
 * Revision 1.64  2007/01/09 00:55:16  blueyed
 * fixed typo(s)
 *
 * Revision 1.63  2007/01/08 02:11:55  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.62  2007/01/07 05:32:11  fplanque
 * added some more DB skin handling (install+uninstall+edit properties ok)
 * still useless though :P
 * next step: discover containers in installed skins
 *
 * Revision 1.61  2006/12/28 18:31:30  fplanque
 * prevent impersonating of blog in multiblog situation
 *
 * Revision 1.60  2006/12/26 00:55:58  fplanque
 * wording
 *
 * Revision 1.59  2006/12/24 00:45:51  fplanque
 * bugfix
 *
 * Revision 1.58  2006/12/18 00:56:16  fplanque
 * non existent blog error handling
 *
 * Revision 1.57  2006/12/14 22:05:18  fplanque
 * doc
 *
 * Revision 1.56  2006/12/14 21:54:52  blueyed
 * todo
 *
 * Revision 1.55  2006/12/14 21:41:15  fplanque
 * Allow different number of items in feeds than on site
 *
 * Revision 1.54  2006/12/14 21:35:05  fplanque
 * block reordering tentative
 *
 * Revision 1.53  2006/12/14 20:57:55  fplanque
 * Hum... this really needed some cleaning up!
 *
 * Revision 1.52  2006/12/05 00:39:56  fplanque
 * fixed some more permalinks/archive links
 *
 * Revision 1.51  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.50  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.49  2006/11/30 22:34:15  fplanque
 * bleh
 *
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
 */
?>