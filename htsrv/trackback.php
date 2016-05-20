<?php
/**
 * This file handles trackback requests
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

if( $Settings->get('system_lock') )
{ // System is locked for maintenance, trackbacks are not allowed
	$Messages->add( T_('You cannot leave a comment at this time because the system is under maintenance. Please try again in a few moments.'), 'error' );
	header_redirect(); // Will save $Messages into Session
}

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

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

if( ! $commented_Item->can_receive_pings() )
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

if( $Messages->has_errors() )
{
	trackback_response( 1, $Messages->get_string( '', '', "\n" ) ); // exits
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

$comment = format_to_post( $comment, 1 ); // includes antispam
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
// asimo> this handles moderators and general users as well and use "outbound_notifications_mode" in case of general users
// Moderators will get emails about every new trackback
// Subscribed user will only get emails about new published trackback
$Comment->handle_notifications( NULL, true );


// Trigger event: a Plugin should cleanup any temporary data here..
// fp>> WARNING: won't be called if trackback gets deleted by antispam
$Plugins->trigger_event( 'AfterTrackbackInsert', array( 'Comment' => & $Comment ) );


// fp>TODO: warn about moderation
trackback_response( 0, 'ok' );

?>