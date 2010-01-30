<?php
/**
 * This file handles trackback requests
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package htsrv
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
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

/**
 * Send a trackback response and exits.
 *
 * @param integer Error code
 * @param string Error message
 */
function trackback_response( $error = 0, $error_message = '' )
{ // trackback - reply
	global $io_charset;

	echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.">\n";
	echo "<response>\n";
	echo "<error>$error</error>\n";
	echo "<message>$error_message</message>\n";
	echo "</response>";
	exit(0);
}

// statuses allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private' );

param( 'tb_id', 'integer' );
param( 'url', 'string' );
param( 'title', 'string' );
param( 'excerpt', 'html' );
param( 'blog_name', 'string' );


if( empty($tb_id) )
{ // No parameter for ID, get if from URL:
	$path_elements = explode( '/', $ReqPath, 30 );
	$tb_id = intval( $path_elements[count($path_elements)-1] );
}


if( ! empty($_GET['__mode']) )
{ // some MT extension (AFAIK), that we do not support
	return;
}

if( empty($tb_id) )
{
	trackback_response( 1, 'No trackback post ID given.' ); // exits
}
if( empty($url) )
{
	trackback_response( 1, 'No url to your permanent entry given.' ); // exits
}

@header('Content-Type: text/xml');

$ItemCache = & get_ItemCache();
if( !( $commented_Item = & $ItemCache->get_by_ID( $tb_id, false ) ) )
{
	trackback_response( 1, 'Sorry, the requested post doesn\'t exist.' ); // exits
}

if( !( $Blog = & $commented_Item->get_Blog() ) )
{
	trackback_response( 1, 'Sorry, could not get the post\'s weblog.' ); // exits
}

if( ! $Blog->get('allowtrackbacks') )
{
	trackback_response( 1, 'Sorry, this weblog does not allow you to trackback its posts.' ); // exits
}

// Commented out again, because it's comment specific: if( ! $commented_Item->can_comment( NULL ) )
// "BeforeTrackbackInsert" should be hooked instead!
if( $commented_Item->comment_status != 'open' )
{
	trackback_response( 1, 'Sorry, this item does not accept trackbacks.' ); // exits
}


// CHECK content
if( $error = validate_url( $url, 'commenting' ) )
{
	$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
}

if( $Messages->count('error') )
{
	trackback_response( 1, $Messages->get_string( '', '', 'all', "\n" ) ); // exits
}

// TODO: dh> title and excerpt should be htmlbody, too, no?
$title = strmaxlen(strip_tags($title), 255, '...', 'raw');
$excerpt = strmaxlen(strip_tags($excerpt), 255, '...', 'raw');
$blog_name = strmaxlen($blog_name, 255, '...', 'htmlbody');

$comment = '';
if( ! empty($title) )
{
	$comment .= '<strong>'.$title.'</strong>';

	if( ! empty($excerpt) )
	{
		$comment .= '<br />';
	}
}
$comment .= $excerpt;

$comment = format_to_post( $comment, 1, 1 ); // includes antispam
if( empty($comment) )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comment'), 'error' );
}


/**
 * @global Comment Trackback object
 */
$Comment = new Comment();
$Comment->set( 'type', 'trackback' );
$Comment->set_Item( $commented_Item );
$Comment->set( 'author', $blog_name );
$Comment->set( 'author_url', $url );
$Comment->set( 'author_IP', $Hit->IP );
$Comment->set( 'date', date('Y-m-d H:i:s', $localtimenow ) );
$Comment->set( 'content', $comment );
// Assign default status for new comments:
$Comment->set( 'status', $commented_Item->Blog->get_setting('new_feedback_status') );


// Trigger event, which may add a message of category "error":
$Plugins->trigger_event( 'BeforeTrackbackInsert', array( 'Comment' => & $Comment ) );


// Display errors:
if( $errstring = $Messages->get_string( 'Cannot insert trackback, please correct these errors:', '' ) )
{
	trackback_response(1, $errstring);
	// tblue> Note: the spec at <http://www.sixapart.com/pronet/docs/trackback_spec>
	//	only shows error code 1 in the example response
	//	and we also only check for code 1 in TB answers.
}


// Record trackback into DB:
$Comment->dbinsert();


if( $Comment->ID == 0 )
{
	// Exit silently! Wz don't want to give an easy tool to try and pass the filters.
	trackback_response( 0, 'ok' );
}


/*
 * ----------------------------
 * New trackback notification:
 * ----------------------------
 */
// TODO: dh> this should only send published feedback probably and should also use "outbound_notifications_mode"
$Comment->send_email_notifications();


// Trigger event: a Plugin should cleanup any temporary data here..
// fp>> WARNING: won't be called if trackback gets deleted by antispam
$Plugins->trigger_event( 'AfterTrackbackInsert', array( 'Comment' => & $Comment ) );


// fp>TODO: warn about moderation
trackback_response( 0, 'ok' );


/*
 * $Log$
 * Revision 1.72  2010/01/30 18:55:15  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.71  2009/09/27 12:57:29  blueyed
 * strmaxlen: add format param, which is used on the (possibly) cropped string.
 *
 * Revision 1.70  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.69  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.68  2009/09/20 18:54:58  blueyed
 * Use strmaxlen
 *
 * Revision 1.67  2009/03/08 23:57:37  fplanque
 * 2009
 *
 * Revision 1.66  2008/09/28 05:05:06  fplanque
 * minor
 *
 * Revision 1.65  2008/09/27 16:52:30  tblue246
 * Exit with a trackback error instead of debug_die()ing when there's no post with the supplied post ID.
 *
 * Revision 1.64  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.63  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.62  2008/01/19 18:24:25  fplanque
 * antispam checking refactored
 *
 * Revision 1.61  2008/01/19 15:45:29  fplanque
 * refactoring
 *
 * Revision 1.60  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.59  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.58  2007/02/13 01:30:31  blueyed
 * TODO: do not notify about not published comments / use "outbound_notifications_mode" setting for comments, too
 *
 * Revision 1.57  2006/12/22 00:26:41  blueyed
 * Require absolute URL for trackback source; Correct charset for trackback_response()
 *
 * Revision 1.56  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.55  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.54  2006/07/03 21:04:48  fplanque
 * translation cleanup
 *
 * Revision 1.53  2006/05/30 00:15:11  blueyed
 * Do not use Item::can_comment here.
 *
 * Revision 1.52  2006/05/29 23:57:13  blueyed
 * todo
 *
 * Revision 1.51  2006/05/29 22:27:46  blueyed
 * Use NULL instead of false for "no display".
 *
 * Revision 1.50  2006/05/29 19:54:45  fplanque
 * no message
 *
 * Revision 1.49  2006/05/20 01:56:07  blueyed
 * ItemCanComment hook; "disable anonymous feedback" through basic antispam plugin
 *
 * Revision 1.48  2006/05/19 18:15:04  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.47.2.1  2006/05/19 15:06:23  fplanque
 * dirty sync
 *
 * Revision 1.47  2006/05/02 04:36:24  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.46  2006/05/01 05:20:38  blueyed
 * Check for duplicate content in comments/trackback.
 *
 * Revision 1.45  2006/05/01 04:25:04  blueyed
 * Normalization
 *
 * Revision 1.44  2006/04/27 21:03:51  blueyed
 * Cleanup, fix and add Plugin hook
 *
 * Revision 1.43  2006/04/20 16:31:29  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.42  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.41  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.40  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>