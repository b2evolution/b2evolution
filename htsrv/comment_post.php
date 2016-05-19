<?php
/**
 * This file posts a comment!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// Stop a request from the blocked IP addresses or Domains
antispam_block_request();

// Check if the request exceed the post max size. If it does then the function will a call header_redirect.
check_post_max_size_exceeded();

// Getting GET or POST parameters:
param( 'comment_item_ID', 'integer', true ); // required
param( 'comment_type', 'string', 'feedback' );
param( 'redirect_to', 'url', '' );
param( 'reply_ID', 'integer', 0 );

// Only logged in users can post the meta comments
$comment_type = is_logged_in() ? $comment_type : 'feedback';

$action = param_arrayindex( 'submit_comment_post_'.$comment_item_ID, 'save' );

$ItemCache = & get_ItemCache();
$commented_Item = & $ItemCache->get_by_ID( $comment_item_ID );
// Make sure Blog is loaded
$commented_Item->load_Blog();
$blog = $commented_Item->Blog->ID;
// Initialize global $Blog to avoid restriction of redirect to external URL, for example, when collection URL is subdomain:
$Blog = $commented_Item->Blog;

// Re-Init charset handling, in case current_charset has changed:
locale_activate( $commented_Item->Blog->get('locale') );

if( init_charsets( $current_charset ) )
{ // Reload Blog(s) (for encoding of name, tagline etc):
	$BlogCache->clear();
	$commented_Item->load_Blog();
}

header( 'Content-Type: text/html; charset='.$io_charset );

if( $Settings->get('system_lock') )
{ // System is locked for maintenance, users cannot send a comment
	$Messages->add( T_('You cannot leave a comment at this time because the system is under maintenance. Please try again in a few moments.'), 'error' );
	header_redirect(); // Will save $Messages into Session
}

// Check user permissions to post this comment:
if( $comment_type == 'meta' )
{ // Meta comment
	if( ! $current_User->check_perm( 'meta_comment', 'view', false, $commented_Item ) )
	{ // Current user has no permission to post a meta comment
		$Messages->add( T_('You cannot leave meta comments on this post!'), 'error' );
		header_redirect(); // Will save $Messages into Session
	}
}
else // 'feedback'
{ // Normal/Standard comment
	if( ! $commented_Item->can_comment( NULL ) )
	{ // Current user has no permission to post a normal comment
		$Messages->add( T_('You cannot leave comments on this post!'), 'error' );
		header_redirect(); // Will save $Messages into Session
	}
}

if( $commented_Item->Blog->get_setting( 'allow_html_comment' ) )
{	// HTML is allowed for this comment
	$text_format = 'html';
}
else
{	// HTML is disallowed for this comment
	$text_format = 'htmlspecialchars';
}

// Note: we use funky field names to defeat the most basic guestbook spam bots and/or their most basic authors
$comment = param( $dummy_fields[ 'content' ], $text_format );
// Don't allow the hidden text in comment content
$comment = str_replace( '<!', '&lt;!', $comment );

if( is_logged_in( false ) )
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
	$author = param( $dummy_fields[ 'name' ], 'string' );
	$email = utf8_strtolower( param( $dummy_fields[ 'email' ], 'string' ) );
	$url = param( $dummy_fields[ 'url' ], 'string' );

	if( $url != '' && ! $commented_Item->Blog->get_setting( 'allow_anon_url' ) )
	{	// It's an automated/malicious submit and we want to reject it the hard way
		exit(0);
	}
	param( 'comment_cookies', 'integer', 0 );
	param( 'comment_allow_msgform', 'integer', 0 ); // checkbox
}

param( 'comment_rating', 'integer', NULL );

// Manually fetch crumb_comment here, to pass it to/through CommentFormSent
param( 'crumb_comment', 'string', NULL );


$now = date( 'Y-m-d H:i:s', $localtimenow );


// VALIDATION:

$original_comment = $comment;

$comment_renderers = param( 'renderers', 'array:string', array() );

// Trigger event: a Plugin could add a $category="error" message here..
// This must get triggered before any internal validation and must pass all relevant params.
// The OpenID plugin will validate a given OpenID here (via redirect and coming back here).
$Plugins->trigger_event( 'CommentFormSent', array(
		'comment_item_ID' => $comment_item_ID,
		'comment' => & $comment,
		'original_comment' => & $original_comment,
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
		'renderers' => $comment_renderers,
	) );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'comment' );

$comments_email_is_detected = false;

if( $User )
{	// User is logged in (or provided, e.g. via OpenID plugin)
	// Does user have permission to edit?
	$perm_comment_edit = $User->check_perm( 'blog_comments', 'edit', false, $commented_Item->Blog->ID );
}
else
{	// User is still not logged in
	// NO permission to edit!
	$perm_comment_edit = false;

	// we need some id info from the anonymous user:
	if ( $require_name_email )
	{ // We want Name and EMail with comments
		if( empty( $author ) )
		{
			$Messages->add( T_('Please fill in your name.'), 'error' );
		}
		if( empty( $email ) )
		{
			$Messages->add( T_('Please fill in your email.'), 'error' );
		}
	}

	if( !empty( $author ) && ( $block = antispam_check( $author ) ) )
	{
		// Log incident in system log
		syslog_insert( sprintf( T_('Antispam: Supplied name "%s" contains blacklisted word "%s".'), $author, $block ), 'error', 'comment', $comment_item_ID );

		$Messages->add( T_('Supplied name is invalid.'), 'error' );
	}

	if( !empty( $email )
		&& ( !is_email( $email )|| ( $block = antispam_check( $email ) ) ) )
	{
		// Log incident in system log
		syslog_insert( sprintf( T_('Antispam: Supplied email address "%s" contains blacklisted word "%s".'), $email, $block ), 'error', 'comment', $comment_item_ID );

		$Messages->add( T_('Supplied email address is invalid.'), 'error' );
	}


	if( !stristr( $url, '://' ) && !stristr( $url, '@' ) )
	{ // add 'http://' if no protocol defined for URL; but not if the user seems to be entering an email address alone
		$url = 'http://'.$url;
	}

	if( strlen( $url ) <= 8 )
	{	// ex: https:// is 8 chars
		$url = '';
	}

	// Note: as part of the validation we require the url to be absolute; otherwise we cannot detect bozos typing in
	// a title for their comment or whatever...
	if( $error = validate_url( $url, 'commenting' ) )
	{
		$Messages->add( T_('Supplied website address is invalid: ').$error, 'error' );
	}

	if( $commented_Item->Blog->get_setting( 'comments_detect_email' ) )
	{	// Detect email addresses in comments
		if( preg_match( '/(([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,}))/i', $comment ) )
		{	// Comment contains an email address
			$action = 'preview';
			$comments_email_is_detected = true;
		}
	}
}

// CHECK and FORMAT content
$saved_comment = $comment;
// Following call says "WARNING: this does *NOT* (necessarilly) make the HTML code safe.":
$comment = check_html_sanity( $comment, $perm_comment_edit ? 'posting' : 'commenting', $User );
if( $comment === false )
{	// ERROR! Restore original comment for further editing:
	$comment = $saved_comment;
}

// Flood protection was here and SHOULD NOT have moved down!

/**
 * Create comment object. Gets validated, before recording it into DB:
 */
$Comment = new Comment();
if( $reply_ID > 0 )
{	// Set parent ID if this comment is reply to other comment
	$Comment->set( 'in_reply_to_cmt_ID', $reply_ID );
}
$Comment->set( 'type',( $comment_type == 'meta' ? 'meta' : 'comment' ) );
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

// Renderers:
if( param( 'renderers_displayed', 'integer', 0 ) )
{ // use "renderers" value only if it has been displayed (may be empty)
	global $Plugins;
	$renderers = $Plugins->validate_renderer_list( $comment_renderers, array( 'Comment' => & $Comment ) );
	$Comment->set_renderers( $renderers );
}

// Def status will be the highest publish status what the current User ( or anonymous user if there is no current user ) can post
$def_status = $Comment->is_meta() ? 'published' : get_highest_publish_status( 'comment', $commented_Item->Blog->ID, false );
$Comment->set( 'status', $def_status );

// Restrict comment status by parent item:
$Comment->restrict_status_by_item( true );

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
								OR comment_author_email = '.$DB->quote( $Comment->get_author_email() );
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

// get already attached file ids
param( 'preview_attachments', 'string', '' );
// finally checked attachments
$checked_attachments = '';
$checked_attachments_count = 0;
if( !empty( $preview_attachments ) )
{ // Get checked attachments. Some attachments was already unchecked, so needs to be separated.
	$attachments = explode( ',', $preview_attachments );
	foreach( $attachments as $attachment_ID )
	{ // iterate through all attachments and select checked ones
		if( ( $commented_Item->get_attachments_limit() === 'unlimit' || $checked_attachments_count < (int)$commented_Item->get_attachments_limit() ) &&
		    param( 'preview_attachment'.$attachment_ID, 'integer', 0 ) )
		{ // this attachment checkbox was checked in, so it needs to be attached
			$checked_attachments = $checked_attachments.$attachment_ID.',';
			$checked_attachments_count++;
		}
	}
	if( !empty( $checked_attachments ) )
	{ // cut the last comma
		$checked_attachments = substr( $checked_attachments, 0, strlen( $checked_attachments ) - 1 );
	}
}

if( $commented_Item->can_attach() && ( ( $action == 'preview' ) || $ok ) &&
    !empty( $_FILES['uploadfile'] ) && !empty( $_FILES['uploadfile']['size'] ) && !empty( $_FILES['uploadfile']['size'][0] ) )
{ // attaching files is permitted
	$FileRootCache = & get_FileRootCache();
	if( is_logged_in() )
	{ // registered user
		$root = FileRoot::gen_ID( 'user', $current_User->ID );
		$path = 'comments/p'.$commented_Item->ID;
	}
	else
	{ // anonymous user
		$root = FileRoot::gen_ID( 'collection', $commented_Item->Blog->ID );
		$path = 'anonymous_comments/p'.$commented_Item->ID;
	}

	// process upload
	$result = process_upload( $root, $path, true, false, false, false );
	if( !empty( $result ) )
	{
		$uploadedFiles = $result['uploadedFiles'];
		if( !empty( $result['failedFiles'] ) )
		{ // upload failed
			$Messages->add( T_( 'Couldn\'t attach selected file:' ).$result['failedFiles'][0], 'warning' );
		}
		if( !empty( $uploadedFiles ) )
		{ // upload succeeded
			foreach( $uploadedFiles as $File )
			{
				if( empty( $preview_attachments ) )
				{
					$preview_attachments = $File->ID;//get_rdfp_rel_path();
					// newly uploaded file must be checked by default
					$checked_attachments = $File->ID;
				}
				else
				{
					$preview_attachments .= ','.$File->ID;//get_rdfp_rel_path();
					// newly uploaded file must be checked by default
					if( empty( $checked_attachments ) )
					{
						$checked_attachments = $File->ID;
					}
					else
					{
						$checked_attachments = $checked_attachments.','.$File->ID;
					}
				}
				$checked_attachments_count++;
			}
		}
	}
}

if( empty( $comment ) && $checked_attachments_count == 0 )
{ // comment should not be empty!
	$Messages->add( T_('Please do not send empty comments.'), 'error' );
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
if( $Messages->has_errors() && $action != 'preview' )
{
	$Comment->set( 'preview_attachments', $preview_attachments );
	$Comment->set( 'checked_attachments', $checked_attachments );
	save_comment_to_session( $Comment );

	if( !empty( $reply_ID ) )
	{
		$redirect_to = url_add_param( $redirect_to, 'reply_ID='.$reply_ID.'&redir=no', '&' );
	}

	header_redirect(); // 303 redirect
	// exited here

	/* asimo>fp I think we may delete this commented part below:
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
	exit(0);*/
}

if( $action == 'preview' )
{ // set the Comment into user's session and redirect.
	$Comment->set( 'original_content', html_entity_decode( $original_comment ) ); // used in the textarea input field again
	$Comment->set( 'preview_attachments', $preview_attachments ); // memorize attachments
	$Comment->set( 'checked_attachments', $checked_attachments ); // memorize checked attachments
	$Comment->set( 'email_is_detected', $comments_email_is_detected ); // used to change a style of the comment
	// Set Comment Item object to NULL, so this way the Item object won't be serialized, but the item_ID is still set
	$Comment->Item = NULL;
	$Session->set( 'core.preview_Comment', $Comment );
	$Session->set( 'core.no_CachePageContent', 1 );
	$Session->dbsave();

	// This message serves the purpose that the next page will not even try to retrieve preview from cache... (and won't collect data to be cached)
	// This is session based, so it's not 100% safe to prevent caching. We are also using explicit caching prevention whenever personal data is displayed
	$Messages->add( T_('This is a preview only! Do not forget to send your comment!'), 'error' );

	if( $comments_email_is_detected )
	{ // Comment contains an email address, We should show an error about this
		if( $Settings->get( 'newusers_canregister' ) == 'yes' && $Settings->get( 'registration_is_public' ) )
		{ // Users can register and we give them a links to log in and registration
			if( is_null( $commented_Item ) )
			{ // Initialize the commented Item object
				$commented_Item = & $ItemCache->get_by_ID( $comment_item_ID );
			}
			$link_log_in = 'href="'.get_login_url( 'blocked comment email', $commented_Item->get_url( 'public_view' ) ).'"';
			$link_register = 'href="'.get_user_register_url( $commented_Item->get_url( 'public_view' ), 'blocked comment email' ).'"';
			$Messages->add( sprintf( T_('Your comment contains an email address. Please <a %s>log in</a> or <a %s>create an account now</a> instead. This will allow people to send you private messages without revealing your email address to SPAM robots.'), $link_log_in, $link_register ), 'error' );

			// Save the user data if he will go to register form after this action
			$register_user = array(
				'name' => $Comment->author,
				'email' => $Comment->author_email
			);
			$Session->set( 'core.register_user', $register_user );
		}
		else
		{	// No registration
			$Messages->add( T_('Your comment contains an email address. We recommend you check the box "Allow message form." below instead. This will allow people to contact you without revealing your email address to SPAM robots.'), 'error' );
		}
	}

	// Passthrough comment_cookies & comment_allow_msgform params:
	// fp> moved this down here in order to keep return URLs clean whenever this is not needed.
	$redirect_to = url_add_param( $redirect_to, 'redir=no&comment_cookies='.$comment_cookies
		.'&comment_allow_msgform='.$comment_allow_msgform, '&' );

	if( !empty( $reply_ID ) )
	{
		$redirect_to = url_add_param( $redirect_to, 'reply_ID='.$reply_ID, '&' );
	}

	$redirect_to .= '#comment_preview';

	header_redirect();
	exit(0);
}
else
{ // delete any preview comment from session data:
	$Session->delete( 'core.preview_Comment' );
}


// RECORD comment:
$result = $Comment->dbinsert();

// Create links
if( $result && ( !empty( $preview_attachments ) ) )
{
	global $DB;
	load_class( 'links/model/_linkcomment.class.php', 'LinkComment' );

	$order = 1;
	$FileCache = & get_FileCache();
	$attachments = explode( ',', $preview_attachments );
	$final_attachments = explode( ',', $checked_attachments );
	$LinkOwner = new LinkComment( $Comment );
	$attachment_dir = NULL;

	// No need transaction here, because if one file can't be attached, the rest should be still attached
	foreach( $attachments as $file_ID )
	{ // create links between comment and attached files
		if( in_array( $file_ID, $final_attachments ) )
		{ // attachment checkbox was checked, create the link
			$LinkOwner->add_link( $file_ID, 'aftermore', $order, false );
			$order++;
		}
		else
		{ // attachment checkbox was not checked, remove unused uploaded file
			$unused_File = $FileCache->get_by_ID( $file_ID, false );
			if( $unused_File )
			{
				if( empty( $checked_attachments ) && empty( $attachment_dir ) )
				{ // None of the previously attached file was checked to be attached
					$attachment_dir = dirname( $unused_File->get_full_path() ).'/';
				}
				$unused_File->unlink();
			}
		}
	}

	if( empty( $checked_attachments ) && ( ! empty( $attachment_dir ) ) )
	{ // None of the previously attached files were finally attached
		$dir_content = get_filenames( $attachment_dir, array( 'recurse' => false, 'inc_hidden' => false ) );
		if( empty( $dir_content ) )
		{ // The attachment dir is empty, delete the directory
			rmdir_r( $attachment_dir );
		}
	}
}


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
		evo_setcookie( $cookie_name, $author, $cookie_expires, $cookie_path, $cookie_domain, false, true );
		evo_setcookie( $cookie_email, $email, $cookie_expires, $cookie_path, $cookie_domain, false, true );
		evo_setcookie( $cookie_url, $url, $cookie_expires, $cookie_path, $cookie_domain, false, true );
	}
	else
	{	// Erase cookies:
		if( !empty($_COOKIE[$cookie_name]) )
		{
			evo_setcookie( $cookie_name, '', $cookie_expired, $cookie_path, $cookie_domain, false, true );
		}
		if( !empty($_COOKIE[$cookie_email]) )
		{
			evo_setcookie( $cookie_email, '', $cookie_expired, $cookie_path, $cookie_domain, false, true );
		}
		if( !empty($_COOKIE[$cookie_url]) )
		{
			evo_setcookie( $cookie_url, '', $cookie_expired, $cookie_path, $cookie_domain, false, true );
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
	// asimo> this handle moderators and general users as well and use "outbound_notifications_mode" in case of general users
	// Moderators will get emails about every new comment
	// Subscribed user will only get emails about new published comments
	$Comment->handle_notifications( NULL, true );


	// Add a message, according to the comment's status:
	switch( $Comment->status )
	{
		case 'published':
			$success_message = T_('Your comment has been submitted.');
			// Append anchor to the redirect_to param, so the user sees his comment:
			$redirect_to .= '#'.$Comment->get_anchor();
			break;
		case 'community':
			$success_message = T_('Your comment is now visible by the community.');
			break;
		case 'protected':
			$success_message = T_('Your comment is now visible by the blog members.');
			break;
		case 'review':
			if( is_logged_in() && $current_User->check_perm( 'blog_comment!review', 'create', false, $blog ) )
			{
				$success_message = T_('Your comment is now visible by moderators only (+You).');
				break;
			}
		default:
			$success_message = T_('Your comment has been submitted. It will appear once it has been approved.');
			break;
	}
	$Messages->add( $success_message, 'success' );

	if( !is_logged_in() )
	{
		if( $Settings->get( 'newusers_canregister' ) == 'yes' && $Settings->get( 'registration_is_public' ) && $Comment->Item->Blog->get_setting( 'comments_register' ) )
		{ // Redirect to the registration form
			$Messages->add( T_('ATTENTION: Create a user account now so that other users can contact you after reading your comment.'), 'error' );

			$register_user = array(
				'name' => $Comment->author,
				'email' => $Comment->author_email
			);
			$Session->set( 'core.register_user', $register_user );

			header_redirect( get_user_register_url( $Comment->Item->get_url( 'public_view' ), 'reg after comment', false, '&' ) );
		}

		// Not logged in user. We want him to see his comment has not vanished if he checks back on the Item page
		// before the cache has expired. Invalidate cache for that page:
		// Note: this is approximative and may not cover all URLs where the user expects to see the comment...
		// TODO: fp> solution: touch dates?
		load_class( '_core/model/_pagecache.class.php', 'PageCache' );
		$PageCache = new PageCache( $Comment->Item->Blog );
		$PageCache->invalidate( $Comment->Item->get_single_url() );
	}
}


header_redirect(); // Will save $Messages into Session

?>