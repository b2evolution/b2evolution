<?php
/**
 * This file handles trackback requests
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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

$comment_post_ID = $tb_id;
$commented_Item = & $ItemCache->get_by_ID( $comment_post_ID );
if( !( $Blog = & $commented_Item->get_Blog() ) )
{
	trackback_response( 1, 'Sorry, could not get the post\'s weblog.' ); // exits
}

if( ! $Blog->get('allowtrackbacks') )
{
	trackback_response( 1, 'Sorry, this weblog does not allow you to trackback its posts.' ); // exits
}

if( ! $commented_Item->can_comment( NULL ) )
{
	trackback_response( 1, 'Sorry, this item does not accept trackbacks.' ); // exits
}


// CHECK content
if( $error = validate_url( $url, $comments_allowed_uri_scheme ) )
{
	$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
}

if( $Messages->count('error') )
{
	trackback_response( 1, $Messages->get_string( '', '', 'all', "\n" ) ); // exits
}


$title = strip_tags($title);
$title = (strlen($title) > 255) ? substr($title, 0, 252).'...' : $title;
$excerpt = strip_tags($excerpt);
$excerpt = (strlen($excerpt) > 255) ? substr($excerpt, 0, 252).'...' : $excerpt;
$blog_name = htmlspecialchars($blog_name);
$blog_name = (strlen($blog_name) > 255) ? substr($blog_name, 0, 252).'...' : $blog_name;

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

$comment = format_to_post($comment,1,1);
if( empty($comment) )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comment'), 'error' );
}
elseif( antispam_check( strip_tags($comment) ) )
{
	$Messages->add( T_('Supplied comment is invalid'), 'error' );
}


/**
 * @global Comment Trackback object
 */
$Comment = & new Comment();
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
	trackback_response(2, $errstring);	// TODO: check TRACKBACK SPEC that error code 2 is ok; blueyed> Why should we use 2?
}


// Record trackback into DB:
$Comment->dbinsert();


if( $Comment->ID == 0 )
{
	// fp>TODO: exit silently! You don't want to give an easy tool to try and pass the filters
	trackback_response( 1, T_('Sorry, your trackback has been deleted, because it has been detected as spam.') );
}


/*
 * ----------------------------
 * New trackback notification:
 * ----------------------------
 */
$Comment->send_email_notifications();


// Trigger event: a Plugin should cleanup any temporary data here..
// fp>> WARNING: won't be called if trackback gets deleted by antispam
$Plugins->trigger_event( 'AfterTrackbackInsert', array( 'Comment' => & $Comment ) );


// fp>TODO: warn about moderation
trackback_response( 0, 'ok' );


/*
 * $Log$
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