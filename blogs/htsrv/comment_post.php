<?php
/**
 * This file posts a comment!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

// Getting GET or POST parameters:
param( 'comment_post_ID', 'integer', true ); // required
param( 'redirect_to', 'string', '' );


$action = param_arrayindex( 'submit_comment_post_'.$comment_post_ID, 'save' );


$ItemCache = & get_ItemCache();
$commented_Item = & $ItemCache->get_by_ID( $comment_post_ID );

if( ! $commented_Item->can_comment( NULL ) )
{
	$Messages->add( T_('You cannot leave comments on this post!'), 'error' );
}

// Note: we use funky field names to defeat the most basic guestbook spam bots and/or their most basic authors
$comment = param( 'p', 'html' );

param( 'comment_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

if( is_logged_in() )
{
	/**
	 * @var User
	 */
	$User = & $current_User;
	$author = null;
	$email = null;
	$url = null;
	$comment_cookies = null;
	$comment_allow_msgform = null;
}
else
{	// User is not logged in (registered users), we need some id info from him:
	$User = NULL;
	// Note: we use funky field names to defeat the most basic guestbook spam bots and/or their most basic authors
	$author = param( 'u', 'string' );
	$email = param( 'i', 'string' );
	$url = param( 'o', 'string' );
	param( 'comment_cookies', 'integer', 0 );
	param( 'comment_allow_msgform', 'integer', 0 ); // checkbox
}

param( 'comment_rating', 'integer', NULL );

// Manually fetch crumb_comment here, to pass it to/through CommentFormSent
param( 'crumb_comment', 'string', NULL );


$now = date( 'Y-m-d H:i:s', $localtimenow );


// VALIDATION:

$original_comment = $comment;

// Trigger event: a Plugin could add a $category="error" message here..
// This must get triggered before any internal validation and must pass all relevant params.
// The OpenID plugin will validate a given OpenID here (via redirect and coming back here).
$Plugins->trigger_event( 'CommentFormSent', array(
		'comment_post_ID' => $comment_post_ID,
		'comment' => & $comment,
		'original_comment' => & $original_comment,
		'comment_autobr' => & $comment_autobr,
		'action' => & $action,
		'anon_name' => & $author,
		'anon_email' => & $email,
		'anon_url' => & $url,
		'rating' => & $comment_rating,
		'anon_allow_msgform' => & $comment_allow_msgform,
		'anon_cookies' => & $comment_cookies,
		'User' => & $User,
		'redirect_to' => & $redirect_to,
		'crumb_comment' => & $crumb_comment,
	) );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'comment' );

$commented_Item->get_Blog(); // Make sure Blog is loaded (will be needed wether logged in or not)

if( $User )
{	// User is logged in (or provided, e.g. via OpenID plugin)
	// Does user have permission to edit?
	$perm_comment_edit = $User->check_perm( 'blog_published_comments', 'edit', false, $commented_Item->Blog->ID );
}
else
{	// User is still not logged in
	// NO permission to edit!
	$perm_comment_edit = false;

	// we need some id info from the anonymous user:
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


	if( !stristr($url, '://') && !stristr($url, '@') )
	{ // add 'http://' if no protocol defined for URL; but not if the user seems to be entering an email address alone
		$url = 'http://'.$url;
	}

	if( strlen($url) <= 8 )
	{	// ex: https:// is 8 chars
		$url = '';
	}

	// Note: as part of the validation we require the url to be absolute; otherwise we cannot detect bozos typing in
	// a title for their comment or whatever...
	if( $error = validate_url( $url, 'commenting' ) )
	{
		$Messages->add( T_('Supplied website address is invalid: ').$error, 'error' );
	}
}

// CHECK and FORMAT content
// TODO: AutoBR should really be a "comment renderer" (like with Items)
// OLD stub: $comment = format_to_post( $comment, $comment_autobr, 1 ); // includes antispam
$saved_comment = $comment;
$comment = check_html_sanity( $comment, $perm_comment_edit ? 'posting' : 'commenting', $comment_autobr, $User );
if( $comment === false )
{	// ERROR
	$comment = $saved_comment;
}

if( empty($comment) )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comments.'), 'error' );
}

// Flood protection was here and SHOULD NOT have moved down!

/**
 * Create comment object. Gets validated, before recording it into DB:
 */
$Comment = new Comment();
$Comment->set( 'type', 'comment' );
$Comment->set_Item( $commented_Item );
if( $User )
{ // User is logged in, we'll use his ID
	$Comment->set_author_User( $User );
}
else
{	// User is not logged in:
	$Comment->set( 'author', $author );
	$Comment->set( 'author_email', $email );
	$Comment->set( 'author_url', $url );
	$Comment->set( 'allow_msgform', $comment_allow_msgform );
}

if( $commented_Item->can_rate() )
{	// Comment rating:
	$Comment->set( 'rating', $comment_rating );
}
$Comment->set( 'author_IP', $Hit->IP );
$Comment->set( 'date', $now );
$Comment->set( 'content', $comment );

if( $perm_comment_edit )
{	// User has perm to moderate comments, publish automatically:
	$Comment->set( 'status', 'published' );
}
else
{ // Assign default status for new comments:
	$Comment->set( 'status', $commented_Item->Blog->get_setting('new_feedback_status') );
}

if( $action != 'preview' )
{
	/*
	 * Flood-protection
	 * NOTE: devs can override the flood protection delay in /conf/_overrides_TEST.php
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
	'original_comment' => $original_comment,
	'is_preview' => ($action == 'preview'),
	'action' => & $action ) );


/*
 * Display error messages:
 */
if( $Messages->count('error') )
{
	if( ! isset($page_title) )
	{
		$page_title = T_('Errors while processing your comment');
	}
	// TODO: dh> HEAD part should be some global front end include file..
	// fp> actually, I'd like the error messages to de displayed in a skinnable file. Something that looks like the _main skin file but with minimum extra gadgets (in order to save on DB requests at each "spam denied" error)
	// fp> So please don't waste time on implementing a half baked solution.
	// fp> We may want to rethink skins more deeply beofre implementing this.
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<head>
		<title><?php echo $app_shortname.' &rsaquo; '.$page_title ?></title>
		<meta name="ROBOTS" content="NOINDEX" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $io_charset ?>" />
	</head>
	<body>
	<?php
	$Messages->display( T_('Cannot post comment, please correct these errors:'),
	'[<a href="javascript:history.go(-1)">'.T_('Back to comment editing').'</a>]' );
	?>
	</body>
	</html>
	<?php
	exit(0);
}

if( $action == 'preview' )
{ // set the Comment into user's session and redirect.
	$Comment->set( 'original_content', $original_comment ); // used in the textarea input field again
	$Session->set( 'core.preview_Comment', $Comment );
	$Session->set( 'core.no_CachePageContent', 1 );
	$Session->dbsave();

	// This message serves the purpose that the next page will not even try to retrieve preview from cache... (and won't collect data to be cached)
	// This is session based, so it's not 100% safe to prevent caching. We are also using explicit caching prevention whenever personal data is displayed
	$Messages->add( T_('This is a preview only! Do not forget to send your comment!'), 'error' );

	// Passthrough comment_cookies & comment_allow_msgform params:
	// fp> moved this down here in order to keep return URLs clean whenever this is not needed.
	$redirect_to = url_add_param($redirect_to, 'redir=no&comment_cookies='.$comment_cookies
		.'&comment_allow_msgform='.$comment_allow_msgform, '&');

	$redirect_to .= '#comment_preview';

	header_redirect();
	exit(0);
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
			setcookie( $cookie_name, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
		if( !empty($_COOKIE[$cookie_email]) )
		{
			setcookie( $cookie_email, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
		if( !empty($_COOKIE[$cookie_url]) )
		{
			setcookie( $cookie_url, '', $cookie_expired, $cookie_path, $cookie_domain);
		}
	}
}

// Note: we don't give any clue that we have automatically deleted a comment. It would only give spammers the perfect tool to find out how to pass the filter.

if( $Comment->ID )
{ // comment has not been deleted
	// Trigger event: a Plugin should cleanup any temporary data here..
	$Plugins->trigger_event( 'AfterCommentFormInsert', array( 'Comment' => & $Comment, 'original_comment' => $original_comment ) );

	/*
	 * --------------------------
	 * New comment notifications:
	 * --------------------------
	 */
	// TODO: dh> this should only send published feedback probably and should also use "outbound_notifications_mode"
	// fp> yes for general users, but comment moderators need to receive notifications for new unpublished comments
	$Comment->send_email_notifications();


	// Add a message, according to the comment's status:
	if( $Comment->status == 'published' )
	{
		$Messages->add( T_('Your comment has been submitted.'), 'success' );

		// Append anchor to the redirect_to param, so the user sees his comment:
		$redirect_to .= '#'.$Comment->get_anchor();
	}
	else
	{
		$Messages->add( T_('Your comment has been submitted. It will appear once it has been approved.'), 'success' );
	}

	if( !is_logged_in() )
	{ // Not logged in user. We want him to see his comment has not vanished if he checks back on the Item page
		// before the cache has expired. Invalidate cache for that page:
		// Note: this is approximative and may not cover all URLs where the user expects to see the comment...
		// TODO: fp> solution: touch dates?
		load_class( '_core/model/_pagecache.class.php', 'PageCache' );
		$PageCache = new PageCache( $Comment->Item->Blog );
		$PageCache->invalidate( $Comment->Item->get_single_url() );
	}
}


header_redirect(); // Will save $Messages into Session


/*
 * $Log$
 * Revision 1.144  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.143  2010/05/02 16:38:34  fplanque
 * minor
 *
 * Revision 1.142  2010/03/19 01:31:42  blueyed
 * check_html_sanity: add User param, defaulting to current User. This is required if posting User is not logged in (e.g. commenting via OpenID, but logged out).
 *
 * Revision 1.141  2010/03/18 22:53:38  blueyed
 * Fix param params
 *
 * Revision 1.140  2010/03/18 21:58:32  blueyed
 * comment_post.php: pass crumb_comment to CommentFormSent plugin hook and assert the valid crumb after this hook (required to fix OpenID).
 *
 * Revision 1.139  2010/02/08 17:50:53  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.138  2010/01/30 18:55:15  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.137  2010/01/19 21:10:18  efy-yury
 * update: crumbs
 *
 * Revision 1.136  2009/12/04 23:27:48  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.135  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.134  2009/09/25 07:32:51  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.133  2009/09/14 14:03:02  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.132  2009/05/20 13:53:34  fplanque
 * Return to a clean url after posting a comment
 *
 * Revision 1.131  2009/03/08 23:57:36  fplanque
 * 2009
 *
 * Revision 1.130  2009/01/27 23:45:41  fplanque
 * theoretically this is a better implementation because the check_perm is supposed to check for perms on the currentblog here.
 * needs some more testing though.
 *
 * Revision 1.129  2009/01/27 22:54:01  fplanque
 * commenting cleanup
 *
 * Revision 1.128  2009/01/27 22:30:32  fplanque
 * Whoever has permission to *edit* comments will now have extended permissions on *new* comments too, including posting <a> tags.
 *
 * Revision 1.127  2008/09/29 08:22:47  fplanque
 * bugfix
 *
 * Revision 1.126  2008/09/28 08:06:03  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.125  2008/09/27 07:54:33  fplanque
 * minor
 *
 * Revision 1.124  2008/06/26 21:21:12  blueyed
 * Fix indent
 *
 * Revision 1.123  2008/06/22 18:17:55  blueyed
 * comment_post: Passthrough comment_cookies & comment_allow_msgform params
 *
 * Revision 1.122  2008/06/22 17:50:51  blueyed
 * Use vars for cookie names; typo
 *
 * Revision 1.121  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.120  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.119  2008/01/19 18:24:25  fplanque
 * antispam checking refactored
 *
 * Revision 1.118  2008/01/19 15:45:29  fplanque
 * refactoring
 *
 * Revision 1.117  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.116  2008/01/10 19:59:52  fplanque
 * reduced comment PITA
 *
 * Revision 1.115  2007/11/02 01:57:57  fplanque
 * comment ratings
 *
 * Revision 1.114  2007/11/01 19:52:47  fplanque
 * better comment forms
 *
 * Revision 1.113  2007/07/09 21:24:12  fplanque
 * cleanup of admin page top
 *
 * Revision 1.112  2007/06/23 22:04:17  fplanque
 * minor
 *
 * Revision 1.111  2007/05/20 20:54:49  fplanque
 * better comment moderation links
 *
 * Revision 1.110  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.109  2007/02/28 23:21:53  blueyed
 * Pass $original_comment to CommentFormSent and "action" to BeforeCommentFormInsert
 *
 * Revision 1.108  2007/02/22 22:14:14  blueyed
 * Improved CommentFormSent hook
 *
 * Revision 1.107  2007/02/21 23:52:26  fplanque
 * doc
 *
 * Revision 1.106  2007/02/13 01:30:31  blueyed
 * TODO: do not notify about not published comments / use "outbound_notifications_mode" setting for comments, too
 *
 * Revision 1.105  2007/02/03 18:52:15  fplanque
 * doc
 *
 * Revision 1.104  2007/01/28 23:58:46  blueyed
 * - Added hook CommentFormSent
 * - Re-ordered comment_post.php to: init, validate, process
 * - RegisterFormSent hook can now filter the form values in a clean way
 *
 * Revision 1.103  2007/01/25 00:59:49  blueyed
 * Do not pass "original_comment" in BeforeCommentFormInsert as a reference: makes no sense
 *
 * Revision 1.102  2007/01/21 23:26:31  fplanque
 * preserve "fail on spam" by default
 *
 * Revision 1.101  2007/01/21 22:51:17  blueyed
 * Security fix: tags have not been stripped
 *
 * Revision 1.100  2007/01/21 02:05:48  fplanque
 * cleanup
 *
 * Revision 1.99  2007/01/16 22:48:13  blueyed
 * Plugin hook TODO
 *
 * Revision 1.98  2006/12/26 00:08:30  fplanque
 * wording
 *
 * Revision 1.97  2006/12/03 04:34:44  fplanque
 * doc
 *
 * Revision 1.96  2006/12/03 02:01:19  blueyed
 * Removed unused $evo_html_headlines handling
 *
 * Revision 1.95  2006/12/03 01:58:27  blueyed
 * Renamed $admin_path_seprator to $admin_path_separator and AdminUI_general::pathSeperator to AdminUI::pathSeparator
 *
 * Revision 1.94  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.93  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.92  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.91  2006/11/16 01:59:14  fplanque
 * doc
 *
 * Revision 1.89  2006/10/30 13:48:56  blueyed
 * Fixed charset/HTML for comment-post page (errors)
 */
?>
