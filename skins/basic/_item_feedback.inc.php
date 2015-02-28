<?php
/**
 * This is the template that displays the feedback for a post
 * (comments, trackback, pingback...)
 *
 * You may want to call this file multiple time in a row with different $c $tb $pb params.
 * This allow to seprate different kinds of feedbacks instead of displaying them mixed together
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=1&more=1&c=1&tb=1&pb=1
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
		'comment_start'        => '<div>',
		'comment_end'          => '</div>',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
	), $params );


global $c, $cookie_name, $cookie_email, $cookie_url, $comment_allowed_tags;

// Display filters:
// You can change these and call this template multiple time if you want to separate comments from trackbacks
$disp_comments = 1;					// Display the comments if requested
$disp_comment_form = 1;			// Display the comments form if comments requested
$disp_trackbacks = 1;				// Display the trackbacks if requested
$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
$disp_pingbacks = 1;        // pingbacks (deprecated)



if( empty($c) )
{	// Comments not requested
	$disp_comments = 0;					// DO NOT Display the comments if not requested
	$disp_comment_form = 0;			// DO NOT Display the comments form if not requested
}

if( empty($tb) || !$Item->can_receive_pings() )
{	// Trackback not requested or not allowed
	$disp_trackbacks = 0;				// DO NOT Display the trackbacks if not requested
	$disp_trackback_url = 0;		// DO NOT Display the trackback URL if not requested
}

if( empty($pb) )
{	// Pingback not requested
	$disp_pingbacks = 0;				// DO NOT Display the pingbacks if not requested
}

?>
<a id="feedbacks"></a>
<?php

if( ! ($disp_comments || $disp_comment_form || $disp_trackbacks || $disp_trackback_url || $disp_pingbacks ) )
{	// Nothing more to do....
	return false;
}

$type_list = array();
$disp_title = array();
if(  $disp_comments )
{	// We requested to display comments
	if( $Item->can_see_comments( true ) )
	{ // User can see a comments
		$type_list[] = 'comment';
		$disp_title[] = T_("Comments");
	}
	else
	{ // Use cannot see comments
		$disp_comments = false;
	}
	?>
	<a id="comments"></a>
<?php }
if( $disp_trackbacks ) {
	$type_list[] = 'trackback';
	$disp_title[] = T_("Trackbacks"); ?>
	<a id="trackbacks"></a>
<?php }
if( $disp_pingbacks ) {
	$type_list[] = 'pingback';
	$disp_title[] = T_("Pingbacks"); ?>
	<a id="pingbacks"></a>
<?php } ?>

<?php
if( $disp_trackback_url )
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
if( $disp_comments || $disp_trackbacks || $disp_pingbacks )
{
	if( $disp_comments )
?>

<!-- Title for comments, tbs, pbs... -->
<h4><?php echo implode( ", ", $disp_title) ?>:</h4>

<?php
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
		'msg_empty' => sprintf( /* TRANS: NO comments/trackbacks/pingbacks/ FOR THIS POST... */
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
if( $disp_comment_form )
{ // We want to display the comments form:
	if( $Item->can_comment( '<p><em>', '</em></p>', '#', '#', '<h4>'.T_('Leave a comment').':</h4>' ) )
	{ // User can leave a comment
		if( $Blog->get_ajax_form_enabled() )
		{
			$json_params = array(
				'action' => 'get_comment_form',
				'p' => $Item->ID,
				'blog' => $Blog->ID,
				'disp' => $disp,
				'params' => $params );
			display_ajax_form( $json_params );
		}
		else
		{
			skin_include( '_item_comment_form.inc.php', $params );
		}
	}
	// Note: You can customize the default item comment form by copying the generic
	// /skins/_item_comment_form.inc.php file into the current skin folder.
}
// ---------------------- END OF COMMENT FORM ---------------------
?>