<?php
/**
 * This is the template that displays the feedback for a post
 * (comments, trackback, pingback, webmention...)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=1&more=1
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * @package evoskins
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'disp_comments'        => is_single_page(),
		'disp_comment_form'    => is_single_page(),
		'disp_trackbacks'      => is_single_page(),
		'disp_trackback_url'   => is_single_page(),
		'disp_pingbacks'       => is_single_page(),
		'disp_webmentions'     => is_single_page(),
		'comment_start'        => '<div>',
		'comment_end'          => '</div>',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
	), $params );


global $cookie_name, $cookie_email, $cookie_url, $comment_allowed_tags;

if( ! $Item->can_receive_pings() )
{	// Trackbacks are not allowed
	$params['disp_trackbacks'] = false;				// DO NOT Display the trackbacks if not allowed
	$params['disp_trackback_url'] = false;		// DO NOT Display the trackback URL if not allowed
}
?>
<a id="feedbacks"></a>
<?php

if( ! ( $params['disp_comments'] || $params['disp_comment_form'] || $params['disp_trackbacks'] || $params['disp_trackback_url'] || $params['disp_pingbacks'] || $params['disp_webmentions'] ) )
{	// Nothing more to do....
	return false;
}

$type_list = array();
$disp_title = array();
if(  $params['disp_comments'] )
{	// We requested to display comments
	if( $Item->can_see_comments( true ) )
	{ // User can see a comments
		$type_list[] = 'comment';
		$disp_title[] = T_("Comments");
	}
	else
	{ // Use cannot see comments
		$params['disp_comments'] = false;
	}
	?>
	<a id="comments"></a>
<?php }
if( $params['disp_trackbacks'] ) {
	$type_list[] = 'trackback';
	$disp_title[] = T_("Trackbacks"); ?>
	<a id="trackbacks"></a>
<?php }
if( $params['disp_pingbacks'] ) {
	$type_list[] = 'pingback';
	$disp_title[] = T_("Pingbacks"); ?>
	<a id="pingbacks"></a>
<?php }
if( $params['disp_webmentions'] ) {
	$type_list[] = 'webmention';
	$disp_title[] = T_('Webmentions'); ?>
	<a id="webmentions"></a>
<?php } ?>

<?php
if( $params['disp_trackback_url'] )
{	// We want to display the trackback URL:
	?>
	<h4><?php echo T_('Trackback address for this post:') ?></h4>
	<?php
	/*
	 * Trigger plugin event, which could display a captcha form, before generating a whitelisted URL:
	 * fp> What's the difference between a "whitelisted" URL and a normal trackback URL ??
	 */
	if( ! $Plugins->trigger_event_first_true( 'DisplayTrackbackAddr', array('Item' => & $Item, 'template' => '<code>%url%</code>') ) )
	{ // No plugin displayed a payload, so we just display the default:
		?>
		<code><?php $Item->trackback_url() ?></code>
		<?php
	}
}
?>

<?php
if( $params['disp_comments'] || $params['disp_trackbacks'] || $params['disp_pingbacks'] || $params['disp_webmentions'] )
{
?>

<!-- Title for comments, tbs, pbs... -->
<h4><?php echo implode( ", ", $disp_title) ?>:</h4>

<?php
global $CommentList;

$comments_per_page = !$Blog->get_setting( 'threaded_comments' ) ? $Blog->get_setting( 'comments_per_page' ) : 1000;
$CommentList = new CommentList2( $Blog, $comments_per_page );

// Filter list:
$CommentList->set_filters( array(
		'types' => $type_list,
		'statuses' => get_inskin_statuses( $Blog->ID, 'comment' ),
		'post_ID' => $Item->ID,
		'order' => $Blog->get_setting( 'comments_orderdir' ),
		'threaded_comments' => $Blog->get_setting( 'threaded_comments' ),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

$CommentList->display_if_empty( array(
		'msg_empty' => sprintf( /* TRANS: NO comments/trackbacks/pingbacks/webmentions FOR THIS POST... */
				T_('No %s for this post yet...'), implode( "/", $disp_title) ),
	 ) );


if( $Blog->get_setting( 'threaded_comments' ) )
{	// Array to store the comment replies
	global $CommentReplies;
	$CommentReplies = array();
}

while( $Comment = & $CommentList->get_next() )
{	// Loop through comments:

	if( $Blog->get_setting( 'threaded_comments' ) && $Comment->in_reply_to_cmt_ID > 0 )
	{	// Store the replies in a special array
		if( !isset( $CommentReplies[ $Comment->in_reply_to_cmt_ID ] ) )
		{
			$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array();
		}
		$CommentReplies[ $Comment->in_reply_to_cmt_ID ][] = $Comment;
		continue; // Skip dispay a comment reply here in order to dispay it after parent comment by function display_comment_replies()
	}

	// ------------------ COMMENT INCLUDED HERE ------------------
	skin_include( $params['comment_template'], array(
			'Comment'         => & $Comment,
			'comment_start'   => $params['comment_start'],
			'comment_end'     => $params['comment_end'],
		) );
	// Note: You can customize the default item comment by copying the generic
	// /skins/_item_comment.inc.php file into the current skin folder.
	// ---------------------- END OF COMMENT ---------------------

	if( $Blog->get_setting( 'threaded_comments' ) )
	{	// Display the comment replies
		display_comment_replies( $Comment->ID, $params );
	}
}
}

// ------------------ COMMENT FORM INCLUDED HERE ------------------
if( $params['disp_comment_form'] && // if enabled by skin param
    $Blog->get_setting( 'allow_comments' ) != 'never' && // if enabled by collection setting
    $Item->get_type_setting( 'use_comments' ) ) // if enabled by item type setting
{	// Display a comment form only if it is enabled:
	if( $Blog->get_ajax_form_enabled() )
	{
		$json_params = array(
			'action' => 'get_comment_form',
			'p' => $Item->ID,
			'blog' => $Blog->ID,
			'reply_ID' => param( 'reply_ID', 'integer', 0 ),
			'quote_post' => param( 'quote_post', 'integer', 0 ),
			'quote_comment' => param( 'quote_comment', 'integer', 0 ),
			'disp' => $disp,
			'params' => $params );
		display_ajax_form( $json_params );
	}
	else
	{
		skin_include( '_item_comment_form.inc.php', $params );
	}
	// Note: You can customize the default item comment form by copying the generic
	// /skins/_item_comment_form.inc.php file into the current skin folder.
}
// ---------------------- END OF COMMENT FORM ---------------------
?>