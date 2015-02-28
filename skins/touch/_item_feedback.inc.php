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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage touch
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<!-- ===================== START OF FEEDBACK ===================== -->
<?php

// Default params:
$params = array_merge( array(
		'disp_comments'        => true,
		'disp_comment_form'    => true,
		'disp_trackbacks'      => true,
		'disp_trackback_url'   => true,
		'disp_pingbacks'       => true,
		'disp_section_title'   => true,
		'disp_rating_summary'  => true,
		'before_section_title' => '<h3 id="com-head" onclick="bnc_showhide_coms_toggle();"><img alt="arrow" src="'.$Skin->get_url().'img/com_arrow.png" id="com-arrow">',
		'after_section_title'  => '</h3>',
		'comment_list_start'   => '<ol id="commentlist" class="commentlist">',
		'comment_list_end'     => '</ol>',
		'comment_start'        => '<li><div class="comwrap">',
		'comment_end'          => '</div></li>',
		'preview_start'        => '<h3 id="com-head-preview" class="comhead-open">'.T_('Preview comment').'</h3><ol id="commentlist" class="commentlist" style="display:block"><li id="comment_preview"><div class="comwrap">',
		'preview_end'          => '</div></li></ol>',
		'comment_error_start'  => '<div class="bComment" id="comment_error">',
		'comment_error_end'    => '</div>',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'author_link_text'     => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'link_to'              => 'userurl>userpage',		    // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'form_title_start'     => '<h3 id="respond">',
		'form_title_end'       => '</h3>',
		'notification_text'    => T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ),
	), $params );

global $c, $tb, $pb, $redir;

echo '<div id="comment_wrapper">';

// ----------------- MODULES "Before Comments" EVENT -----------------
modules_call_method( 'before_comments', $params );
// -------------------- END OF MODULES EVENT ---------------------

// Check if user is allowed to see comments, display corresponding message if not allowed
if( $Item->can_see_comments( true ) )
{ // user is allowed to see comments
	if( empty($c) )
	{	// Comments not requested
		$params['disp_comments'] = false;					// DO NOT Display the comments if not requested
		$params['disp_comment_form'] = false;			// DO NOT Display the comments form if not requested
	}

	if( empty($tb) || !$Item->can_receive_pings() )
	{	// Trackback not requested or not allowed
		$params['disp_trackbacks'] = false;				// DO NOT Display the trackbacks if not requested
		$params['disp_trackback_url'] = false;		// DO NOT Display the trackback URL if not requested
	}

	if( empty($pb) )
	{	// Pingback not requested
		$params['disp_pingbacks'] = false;				// DO NOT Display the pingbacks if not requested
	}

	if( ! ($params['disp_comments'] || $params['disp_comment_form'] || $params['disp_trackbacks'] || $params['disp_trackback_url'] || $params['disp_pingbacks'] ) )
	{	// Nothing more to do....
		return false;
	}

	echo '<a id="feedbacks"></a>';

	$type_list = array();
	$disp_title = array();
	$rating_summary = '';

	if( $params['disp_comments'] )
	{	// We requested to display comments
		if( $Item->can_see_comments() )
		{	// User can see a comments
			$type_list[] = 'comment';
			if( $title = $Item->get_feedback_title( 'comments' ) )
			{
				$disp_title[] = $title;
			}

			if( $params['disp_rating_summary'] )
			{	// We requested to display rating summary
				$rating_summary = $Item->get_rating_summary( $params );
			}
		}
		else
		{	// User cannot see comments
			$params['disp_comments'] = false;
		}
		echo '<a id="comments"></a>';
	}

	if( $params['disp_trackbacks'] )
	{
		$type_list[] = 'trackback';
		if( $title = $Item->get_feedback_title( 'trackbacks' ) )
		{
			$disp_title[] = $title;
		}
		echo '<a id="trackbacks"></a>';
	}

	if( $params['disp_pingbacks'] )
	{
		$type_list[] = 'pingback';
		if( $title = $Item->get_feedback_title( 'pingbacks' ) )
		{
			$disp_title[] = $title;
		}
		echo '<a id="pingbacks"></a>';
	}

	if( $params['disp_trackback_url'] )
	{ // We want to display the trackback URL:

		echo $params['before_section_title'];
		echo T_('Trackback address for this post');
		echo $params['after_section_title'];

		/*
		 * Trigger plugin event, which could display a captcha form, before generating a whitelisted URL:
		 */
		if( ! $Plugins->trigger_event_first_true( 'DisplayTrackbackAddr', array('Item' => & $Item, 'template' => '<code>%url%</code>') ) )
		{ // No plugin displayed a payload, so we just display the default:
			echo '<p class="trackback_url"><a href="'.$Item->get_trackback_url().'">'.T_('Trackback URL (right click and copy shortcut/link location)').'</a></p>';
		}
	}


	if( $params['disp_comments'] || $params['disp_trackbacks'] || $params['disp_pingbacks']  )
	{
		if( empty($disp_title) )
		{	// No title yet
			if( $title = $Item->get_feedback_title( 'feedbacks', '', T_('Feedback awaiting moderation'), T_('Feedback awaiting moderation'), array( 'review', 'draft' ), false ) )
			{ // We have some feedback awaiting moderation: we'll want to show that in the title
				$disp_title[] = $title;
			}
		}

		if( empty($disp_title) )
		{	// Still no title
			$disp_title[] = T_('No feedback yet');
		}

		$comments_per_page = !$Blog->get_setting( 'threaded_comments' ) ? $Blog->get_setting( 'comments_per_page' ) : 1000;
		$CommentList = new CommentList2( $Blog, $comments_per_page, 'CommentCache', 'c_' );

		// Filter list:
		$CommentList->set_default_filters( array(
				'types' => $type_list,
				'statuses' => NULL,
				'post_ID' => $Item->ID,
				'order' => $Blog->get_setting( 'comments_orderdir' ),
				'threaded_comments' => $Blog->get_setting( 'threaded_comments' ),
			) );

		$CommentList->load_from_Request();

		// Get ready for display (runs the query):
		$CommentList->display_init();

		// Set redir=no in order to open comment pages
		memorize_param( 'redir', 'string', '', 'no' );

		if( $Blog->get_setting( 'paged_comments' ) )
		{ // Prev/Next page navigation
			$CommentList->page_links( array(
					'page_url' => url_add_tail( $Item->get_permanent_url(), '#comments' ),
				) );
		}


		if( $Blog->get_setting( 'threaded_comments' ) )
		{	// Array to store the comment replies
			global $CommentReplies;
			$CommentReplies = array();

			if( $Comment = $Session->get('core.preview_Comment') )
			{	// Init PREVIEW comment
				if( $Comment->item_ID == $Item->ID )
				{
					$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array( $Comment );

					// Change the styles for preview comment
					$params['before_section_title'] = '<h3 id="com-head" onclick="bnc_showhide_coms_toggle();" class="comhead-open"><img alt="arrow" src="'.$Skin->get_url().'img/com_arrow.png" id="com-arrow" class="com-arrow-down" />';
					$params['comment_list_start'] = '<ol id="commentlist" class="commentlist" style="display:block">';
					$params['preview_start'] = '<li id="comment_preview"><div class="comwrap">';
					$params['preview_end'] = '</div></li>';
				}
			}
		}

		echo $rating_summary;

		if( $params['disp_section_title'] )
		{	// Display title
			echo $params['before_section_title'];
			echo implode( ', ', $disp_title);
			echo $params['after_section_title'];
		}

		echo $params['comment_list_start'];
		/**
		 * @var Comment
		 */
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
					'author_link_text' => $params['author_link_text'],
					'link_to'         => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				) );
			// Note: You can customize the default item comment by copying the generic
			// /skins/_item_comment.inc.php file into the current skin folder.
			// ---------------------- END OF COMMENT ---------------------

			if( $Blog->get_setting( 'threaded_comments' ) )
			{	// Display the comment replies
				display_comment_replies( $Comment->ID, $params );
			}
		}	// End of comment list loop.
		echo $params['comment_list_end'];


		if( $Blog->get_setting( 'paged_comments' ) )
		{ // Prev/Next page navigation
			$CommentList->page_links( array(
					'page_url' => url_add_tail( $Item->get_permanent_url(), '#comments' ),
				) );
		}

		// Restore "redir" param
		forget_param('redir');

		// _______________________________________________________________
		// Display count of comments to be moderated:
		$Item->feedback_moderation( 'feedbacks', '<div class="moderation_msg"><p>', '</p></div>', '',
				T_('This post has 1 feedback awaiting moderation... %s'),
				T_('This post has %d feedbacks awaiting moderation... %s') );
		// _______________________________________________________________
	}
}

// ------------------ COMMENT FORM INCLUDED HERE ------------------
if( $Blog->get_ajax_form_enabled() && ( $Blog->get_setting( 'allow_comments' ) != 'never' ) )
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
// Note: You can customize the default item comment form by copying the generic
// /skins/_item_comment_form.inc.php file into the current skin folder.
// ---------------------- END OF COMMENT FORM ---------------------

// ----------- Register for item's comment notification -----------
if( is_logged_in() && $Item->can_comment( NULL ) )
{
	global $DB, $htsrv_url;
	global $UserSettings;

	echo '<div class="comment_notification">';

	$not_subscribed = true;
	$creator_User = $Item->get_creator_User();

	if( $Blog->get_setting( 'allow_subscriptions' ) )
	{
		$sql = 'SELECT count( sub_user_ID ) FROM T_subscriptions
					WHERE sub_user_ID = '.$current_User->ID.' AND sub_coll_ID = '.$Blog->ID.' AND sub_comments <> 0';
		if( $DB->get_var( $sql ) > 0 )
		{
			echo '<p>'.T_( 'You are receiving notifications when anyone comments on any post.' );
			echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></p>';
			$not_subscribed = false;
		}
	}

	if( $not_subscribed && ( $creator_User->ID == $current_User->ID ) && ( $UserSettings->get( 'notify_published_comments', $current_User->ID ) != 0 ) )
	{
		echo '<p>'.$params['notification_text'];
		echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></p>';
		$not_subscribed = false;
	}
	if( $not_subscribed && $Blog->get_setting( 'allow_item_subscriptions' ) )
	{
		if( get_user_isubscription( $current_User->ID, $Item->ID ) )
		{
			echo '<p>'.T_( 'You will be notified by email when someone comments here.' );
			echo ' <a href="'.$samedomain_htsrv_url.'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=0&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Click here to unsubscribe.' ).'</a></p>';
		}
		else
		{
			echo '<p><a href="'.$samedomain_htsrv_url.'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=1&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Notify me by email when someone comments here.' ).'</a></p>';
		}
	}

	echo '</div>';
}


if( $Item->can_see_comments( false ) && ( $params['disp_comments'] || $params['disp_trackbacks'] || $params['disp_pingbacks'] ) )
{	// user is allowed to see comments
	// Display link for comments feed:
	$Item->feedback_feed_link( '_rss2', '<div class="feedback_feed_msg"><p>', '</p></div>' );
}

echo '</div>';// End of <div id="comment_wrapper">

?>