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

header( 'Content-Type: text/html; charset='.$io_charset );

// statuses allowed for acting on:
// fp> rem 06/09/06 $show_statuses = array( 'published', 'protected', 'private' );

// Only for 0.9.0.11, for users who will not update their conf! :/
if( !isset($minimum_comment_interval) ) $minimum_comment_interval = 30;

// Getting GET or POST parameters:
param( 'comment_post_ID', 'integer', true ); // required

$ItemCache = & get_Cache( 'ItemCache' );
$commented_Item = & $ItemCache->get_by_ID( $comment_post_ID );

if( ! $commented_Item->can_comment( NULL ) )
{
	$Messages->add( T_('You cannot leave comments on this post!'), 'error' );
}


// Note: we use funky field names to defeat the most basic guestbook spam bots and/or their most basic authors
$comment = param( 'p', 'html' );
param( 'comment_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

if( ! is_logged_in() )
{	// User is not logged in (registered users), we need some id info from him:
	// Note: we use funky field names to defeat the most basic guestbook spam bots and/or their most basic authors
	$author = param( 'u', 'string' );
	$email = param( 'i', 'string' );
	$url = param( 'o', 'string' );
	param( 'comment_cookies', 'integer', 0 );
	param( 'comment_allow_msgform', 'integer', 0 ); // checkbox

	if ($require_name_email)
	{ // We want Name and EMail with comments
		if( empty($author) )
		{
			$Messages->add( T_('Please fill in your name.'), 'error' );
		}
		if( empty($email) )
		{
			$Messages->add( T_('Please fill in your email.'), 'error' );
		}
	}

	if( !empty($author) && antispam_check( $author ) )
	{
		$Messages->add( T_('Supplied name is invalid.'), 'error' );
	}

	if( !empty($email)
		&& ( !is_email($email)|| antispam_check( $email ) ) )
	{
		$Messages->add( T_('Supplied email address is invalid.'), 'error' );
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
$original_comment = $comment;
//echo 'allowed tags:',htmlspecialchars($comment_allowed_tags);
$comment = strip_tags($comment, $comment_allowed_tags);
// TODO: AutoBR should really be a "comment renderer" (like with Items)
$comment = format_to_post($original_comment, $comment_autobr, 1);

if( empty($comment) )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comments.'), 'error' );
}
elseif( antispam_check( strip_tags($comment) ) )
{
	$Messages->add( T_('Supplied comment is invalid.'), 'error' );
}

// Flood protection was here and SHOULD NOT have moved down!

/**
 * Create comment object. Gets validated, before recording it into DB:
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


// Check if we want to PREVIEW:
$action = param_arrayindex( 'submit_comment_post_'.$commented_Item->ID, 'save' );

if( $action != 'preview' )
{
	/*
	 * Flood-protection
	 * TODO: Put time check into query?
	 * TODO: move that as far !!UP!! as possible! We want to waste minimum resources on Floods
	 * TODO: have several thresholds. For example:
	 * 1 comment max every 30 sec + 5 comments max every 10 minutes + 15 comments max every 24 hours
	 * TODO: factorize with trackback
	 */
	$query = 'SELECT MAX(comment_date)
							FROM T_comments
						 WHERE comment_author_IP = '.$DB->quote($Hit->IP).'
								OR comment_author_email = '.$DB->quote($Comment->get_author_email());
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
}


// Trigger event: a Plugin could add a $category="error" message here..
$Plugins->trigger_event('BeforeCommentFormInsert', array(
	'Comment' => & $Comment,
	'original_comment' => & $original_comment,
	'is_preview' => ($action == 'preview') ) );


/*
 * Display error messages:
 */
if( $Messages->count('error') )
{
	if( ! isset($page_title) )
	{
		$page_title = T_('Errors during processing your comment');
	}
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<head>
		<title><?php echo $app_shortname.$admin_path_seprator.$page_title ?></title>
		<meta name="ROBOTS" content="NOINDEX" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $io_charset ?>" />
		<?php
		// Insert HEAD lines, which have been defined before:
		// dh> TODO: currently this may be affected by register_globals=ON
		// dh> TODO: fp, is this ok? It should maybe be a func and available everywhere we output <HEAD> tags..?
		if( isset($evo_html_headlines) ) foreach( $evo_html_headlines as $v )
		{
			echo $v;
		}
		?>
	</head>
	<body>
	<?php
	$Messages->display( T_('Cannot post comment, please correct these errors:'),
	'[<a href="javascript:history.go(-1)">'. T_('Back to comment editing') . '</a>]' );

	debug_info();  // output debug info, useful to see what a plugin might have done
	?>
	</body>
	</html>
	<?php
	exit();
}

if( $action == 'preview' )
{ // set the Comment into user's session and redirect. _feeback.php of the skin should display it.
	$Comment->set( 'original_content', $original_comment ); // used in the textarea input field again
	$Session->set( 'core.preview_Comment', $Comment );
	$Session->set( 'core.no_CachePageContent', 1 );
	$Session->dbsave();

	param( 'redirect_to', 'string', '' );
	$redirect_to .= '#comment_preview';

	header_nocache();
	header_redirect();
	exit();
}
else
{ // delete any preview comment from session data:
	$Session->delete( 'core.preview_Comment' );
}


// RECORD comment:

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

// Note: we don't give any clue that we have automatically deleted a comment. Il would only give spammers the perfect tool to find out how to pass the filter.

if( $Comment->ID )
{ // comment has not been deleted
	// Trigger event: a Plugin should cleanup any temporary data here..
	$Plugins->trigger_event( 'AfterCommentFormInsert', array( 'Comment' => & $Comment, 'original_comment' => $original_comment ) );

	/*
	 * --------------------------
	 * New comment notifications:
	 * --------------------------
	 */
	$Comment->send_email_notifications();


	// Add a message, according to the comment's status:
	if( $Comment->status == 'published' )
	{
		$Messages->add( T_('Your comment has been submitted.'), 'success' );

		// Append anchor to the redirect_to param, so the user sees his comment:
		param( 'redirect_to', 'string', '' );
		$redirect_to .= '#'.$Comment->get_anchor();
	}
	else
	{
		$Messages->add( T_('Your comment has been submitted. It will appear once it has been approved.'), 'success' );
	}
}
// Set Messages into user's session, so they get restored on the next page (after redirect):
$Session->set( 'Messages', $Messages );


header_nocache();
header_redirect();


/*
 * $Log$
 * Revision 1.89  2006/10/30 13:48:56  blueyed
 * Fixed charset/HTML for comment-post page (errors)
 *
 * Revision 1.88  2006/09/11 19:35:34  fplanque
 * minor
 *
 * Revision 1.87  2006/09/10 00:00:57  blueyed
 * "Solved" Session related todos.
 *
 * Revision 1.86  2006/09/06 20:45:31  fplanque
 * ItemList2 fixes
 *
 * Revision 1.85  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.84  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.83  2006/06/16 20:34:19  fplanque
 * basic spambot defeating
 *
 * Revision 1.82  2006/06/14 17:26:13  fplanque
 * minor
 *
 * Revision 1.81  2006/05/29 22:27:46  blueyed
 * Use NULL instead of false for "no display".
 *
 * Revision 1.80  2006/05/29 21:13:18  fplanque
 * no message
 *
 * Revision 1.79  2006/05/24 20:43:19  blueyed
 * Pass "Item" as param to Render* event methods.
 *
 * Revision 1.78  2006/05/20 01:56:07  blueyed
 * ItemCanComment hook; "disable anonymous feedback" through basic antispam plugin
 *
 * Revision 1.76.2.1  2006/05/19 15:06:23  fplanque
 * dirty sync
 *
 * Revision 1.76  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.75  2006/05/04 10:32:41  blueyed
 * Use original comment content in preview's form.
 *
 * Revision 1.74  2006/05/04 04:07:24  blueyed
 * After posting a comment, add the anchor to the redirect param; also use more distinctive anchor name for comments
 *
 * Revision 1.73  2006/05/02 22:25:27  blueyed
 * Comment preview for frontoffice.
 *
 * Revision 1.72  2006/05/02 04:36:24  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.71  2006/05/01 04:25:04  blueyed
 * Normalization
 *
 * Revision 1.70  2006/04/24 15:43:35  fplanque
 * no message
 *
 * Revision 1.69  2006/04/21 23:14:16  blueyed
 * Add Messages according to Comment's status.
 *
 * Revision 1.68  2006/04/21 18:10:53  blueyed
 * todos
 *
 * Revision 1.67  2006/04/20 22:24:07  blueyed
 * plugin hooks cleanup
 *
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