<?php
/**
 * This file posts a comment!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package htsrv
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// statuses allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private' );

// Only for 0.9.0.11, for users who will not update their conf! :/
if( !isset($minimum_comment_interval) ) $minimum_comment_interval = 30;

// Getting GET or POST parameters:
param( 'comment_post_ID', 'integer', true ); // required

$commented_Item = & $ItemCache->get_by_ID( $comment_post_ID );

if( ! $commented_Item->can_comment( '', '', '', '' ) )
{
	$Messages->add( T_('You cannot leave comments on this post!'), 'error' );
}

// Trigger event: a Plugin could add a $category="error" message here..
$Plugins->trigger_event( 'CommentFormSent', array( 'Item' => & $commented_Item ) );

param( 'comment', 'html' );
param( 'comment_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

if( ! is_logged_in() )
{	// User is not logged in (registered users), we need some id info from him:
	param( 'author', 'string' );
	param( 'email', 'string' );
	param( 'url', 'string' );
	param( 'comment_cookies', 'integer', 0 );
	param( 'comment_allow_msgform', 'integer', 0 ); // checkbox

	if ($require_name_email)
	{ // We want Name and EMail with comments
		if( empty($author) ) $Messages->add( T_('Please fill in the name field'), 'error' );
		if( empty($email) ) $Messages->add( T_('Please fill in the email field'), 'error' );
	}

	if( !empty($author) && antispam_check( $author ) )
	{
		$Messages->add( T_('Supplied name is invalid'), 'error' );
	}

	if( !empty($email)
		&& ( !is_email($email)|| antispam_check( $email ) ) )
	{
		$Messages->add( T_('Supplied email address is invalid'), 'error' );
	}

	// add 'http://' if no protocol defined for URL
	$url = ((!stristr($url, '://')) && ($url != '')) ? 'http://' . $url : $url;
	if( strlen($url) < 7 ){
		$url = '';
	}
	if( $error = validate_url( $url, $comments_allowed_uri_scheme ) )
	{
		$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
	}
}

$now = date( 'Y-m-d H:i:s', $localtimenow );

// CHECK and FORMAT content
//echo 'allowed tags:',htmlspecialchars($comment_allowed_tags);
$original_comment = strip_tags($comment, $comment_allowed_tags);
$comment = format_to_post($original_comment, $comment_autobr, 1);

if( empty($comment) )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comment'), 'error' );
}
elseif( antispam_check( strip_tags($comment) ) )
{
	$Messages->add( T_('Supplied comment is invalid'), 'error' );
}


/*
 * Flood-protection
 */
$query = 'SELECT MAX(comment_date)
            FROM T_comments
           WHERE comment_author_IP = '.$DB->quote($Hit->IP);
$ok = 1;
if( $then = $DB->get_var( $query ) )
{
	$time_lastcomment = mysql2date("U",$then);
	$time_newcomment = mysql2date("U",$now);
	if( ($time_newcomment - $time_lastcomment) < $minimum_comment_interval )
		$ok = 0;
}
if( !$ok )
{
	$Messages->add( sprintf( T_('You can only post a new comment every %d seconds.'), $minimum_comment_interval ), 'error' );
}
/* end flood-protection */


/*
 * Error messages:
 */
if( $Messages->count('error') )
{
	$Messages->display( T_('Cannot post comment, please correct these errors:'),
	'[<a href="javascript:history.go(-1)">'. T_('Back to comment editing') . '</a>]' );

	debug_info();  // output debug info, useful to see what a plugin might have done
	exit(); // TODO: nicer displaying here (but do NOT die() or debug_die() because this is not a BUG/user hack, it's a plain user input error (any bozo can produce it)
}


/*
 * --------------------------
 * Create and record comment:
 * --------------------------
 */
$Comment = & new Comment();
$Comment->set( 'type', 'comment' );
$Comment->set_Item( $commented_Item );
if( is_logged_in() )
{ // User is logged in, we'll use his ID
	$Comment->set_author_User( $current_User );
}
else
{	// User is not logged in:
	$Comment->set( 'author', $author );
	$Comment->set( 'author_email', $email );
	$Comment->set( 'author_url', $url );
	$Comment->set( 'allow_msgform', $comment_allow_msgform );
}
$Comment->set( 'author_IP', $Hit->IP );
$Comment->set( 'date', $now );
$Comment->set( 'content', $comment );

$commented_Item->get_Blog(); // Make sure Blog is loaded

// Assign default status for new comments:
$Comment->set( 'status', $commented_Item->Blog->get_setting('new_feedback_status') );

$Comment->dbinsert();

/*
 * ---------------
 * Handle cookies:
 * ---------------
 */
if( !is_logged_in() )
{
	if( $comment_cookies )
	{	// Set cookies:
		if ($email == '')
			$email = ' '; // this to make sure a cookie is set for 'no email'
		if ($url == '')
			$url = ' '; // this to make sure a cookie is set for 'no url'

		// fplanque: made cookies available for whole site
		setcookie( $cookie_name, $author, $cookie_expires, $cookie_path, $cookie_domain);
		setcookie( $cookie_email, $email, $cookie_expires, $cookie_path, $cookie_domain);
		setcookie( $cookie_url, $url, $cookie_expires, $cookie_path, $cookie_domain);
	}
	else
	{	// Erase cookies:
		if( !empty($_COOKIE[$cookie_name]) )
		{
			// echo "del1<br />";
			setcookie('comment_author', '', $cookie_expired, '/');
			setcookie('comment_author', '', $cookie_expired, $cookie_path, $cookie_domain);
			setcookie( $cookie_name, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
		if( !empty($_COOKIE['comment_author_email']) )
		{
			// echo "del2<br />";
			setcookie('comment_author_email', '', $cookie_expired, '/');
			setcookie('comment_author_email', '', $cookie_expired, $cookie_path, $cookie_domain);
			setcookie( $cookie_email, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
		if( !empty($_COOKIE['comment_author_url']) )
		{
			// echo "del3<br />";
			setcookie('comment_author_url', '', $cookie_expired, '/');
			setcookie('comment_author_url', '', $cookie_expired, $cookie_path, $cookie_domain);
			setcookie( $cookie_url, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
	}
}

/*
 * --------------------------
 * New comment notifications:
 * --------------------------
 */
$Comment->send_email_notifications();


// Set Messages into user's session, so they get restored on the next page (after redirect):
// TODO: look at $Comment->status and use different messages
$Messages->add( T_('Your comment has been submitted.'), 'success' );
$Session->set( 'Messages', $Messages );


header_nocache();
header_redirect();


/*
 * $Log$
 * Revision 1.66  2006/04/20 16:31:29  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.65  2006/04/19 23:50:39  blueyed
 * Normalized Messages handling (error displaying and transport in Session)
 *
 * Revision 1.64  2006/04/19 22:26:24  blueyed
 * cleanup/polish
 *
 * Revision 1.63  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.62  2006/03/22 01:07:05  blueyed
 * bad brackets
 *
 * Revision 1.61  2006/03/20 22:28:34  blueyed
 * Changed defaults for Log's display methods to "all" categories.
 *
 * Revision 1.60  2006/03/19 17:54:25  blueyed
 * Opt-out for email through message form.
 *
 * Revision 1.58  2006/03/07 19:30:22  fplanque
 * comments
 *
 * Revision 1.57  2006/03/06 20:40:13  blueyed
 * debug_info() added in case of errors.
 *
 * Revision 1.52  2006/02/23 21:11:47  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 */
?>