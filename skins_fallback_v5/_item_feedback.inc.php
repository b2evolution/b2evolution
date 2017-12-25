<?php
/**
 * This is the template that displays the feedback for a post (comments, trackback, pingback...)
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
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<!-- ===================== START OF FEEDBACK ===================== -->
<?php

// Default params:
$params = array_merge( array(
		'Item'                 => NULL,
		'disp_comments'        => true,
		'disp_comment_form'    => true,
		'disp_trackbacks'      => true,
		'disp_trackback_url'   => true,
		'disp_pingbacks'       => true,
		'disp_section_title'   => true,
		'disp_rating_summary'  => true,
		'before_section_title' => '<h3>',
		'after_section_title'  => '</h3>',
		'comments_title_text'   => '',
		'comment_list_start'    => "\n\n",
		'comment_list_end'      => "\n\n",
		'comment_start'         => '<div class="bComment">',
		'comment_end'           => '</div>',
		'comment_avatar_before' => '<span class="bComment-avatar">',
		'comment_avatar_after'  => '</span>',
		'comment_title_before'  => '<div class="bCommentTitle">',
		'comment_title_after'   => '</div>',
		'comment_rating_before' => '<div class="comment_rating">',
		'comment_rating_after'  => '</div>',
		'comment_text_before'   => '<div class="bCommentText">',
		'comment_text_after'    => '</div>',
		'comment_info_before'   => '<div class="bCommentSmallPrint">',
		'comment_info_after'    => '</div>',
		'preview_start'        => '<div class="bComment" id="comment_preview">',
		'preview_end'          => '</div>',
		'comment_error_start'  => '<div class="bComment" id="comment_error">',
		'comment_error_end'    => '</div>',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'comment_image_size'   => 'fit-400x320',
		'author_link_text'     => 'auto', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'link_to'              => 'userurl>userpage',		    // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'form_title_start'     => '<h3>',
		'form_title_end'       => '</h3>',
		'disp_notification'    => true,
		'notification_before'  => '<div class="comment_notification">',
		'notification_text'    => T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ),
		'notification_text2'   => T_( 'You will be notified by email when someone comments here.' ),
		'notification_text3'   => T_( 'Notify me by email when someone comments here.' ),
		'notification_after'   => '</div>',
		'feed_title'           => '#',
		'disp_nav_top'         => true,
		'disp_nav_bottom'      => true,
		'nav_top_inside'       => false, // TRUE to display it after start of comments list (inside), FALSE to display a page navigation before comments list
		'nav_bottom_inside'    => false, // TRUE to display it before end of comments list (inside), FALSE to display a page navigation after comments list
		'nav_block_start'      => '<p class="center">',
		'nav_block_end'        => '</p>',
		'nav_prev_text'        => '&lt;&lt;',
		'nav_next_text'        => '&gt;&gt;',
		'nav_prev_class'       => '',
		'nav_next_class'       => '',
	), $params );


global $c, $tb, $pb, $redir;

if( !empty( $params['Item'] ) && is_object( $params['Item'] ) )
{	// Set Item object from params
	$Item = $params['Item'];
	// Unset params Item object because the params array should be json encodable and we must avoid recursions. We already have the Item for further use.
	unset( $params['Item'] );
}

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
			$Item->load_Blog();
			$comment_inskin_statuses = explode( ',', $Item->Blog->get_setting( 'comment_inskin_statuses' ) );

			if( !empty( $params['comments_title_text'] ) )
			{
				$disp_title[] = $params['comments_title_text'];
			}
			else if( $title = $Item->get_feedback_title( 'comments', '#', '#', '#', $comment_inskin_statuses ) )
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
			if( $title = $Item->get_feedback_title( 'feedbacks', '', T_('Feedback awaiting moderation'), T_('Feedback awaiting moderation'), '#moderation#', false ) )
			{ // We have some feedback awaiting moderation: we'll want to show that in the title
				$disp_title[] = $title;
			}
		}

		if( empty($disp_title) )
		{	// Still no title
			$disp_title[] = T_('No feedback yet');
		}

		if( $params['disp_section_title'] )
		{	// Display title
			echo $params['before_section_title'];
			echo implode( ', ', $disp_title);
			echo $params['after_section_title'];
		}
		echo $rating_summary;

		global $CommentList;

		$comments_per_page = !$Blog->get_setting( 'threaded_comments' ) ? $Blog->get_setting( 'comments_per_page' ) : 1000;
		$CommentList = new CommentList2( $Blog, $comments_per_page, 'CommentCache', 'c_' );

		// Filter list:
		$CommentList->set_default_filters( array(
				'types' => $type_list,
				'statuses' => get_inskin_statuses( $Blog->ID, 'comment' ),
				'post_ID' => $Item->ID,
				'order' => $Blog->get_setting( 'comments_orderdir' ),
				'threaded_comments' => $Blog->get_setting( 'threaded_comments' ),
			) );

		$CommentList->load_from_Request();

		// Get ready for display (runs the query):
		$CommentList->display_init();

		// Set redir=no in order to open comment pages
		memorize_param( 'redir', 'string', '', 'no' );

		if( $params['nav_top_inside'] )
		{ // To use comments page navigation inside list (Useful for table markup)
			echo $params['comment_list_start'];
		}

		if( $params['disp_nav_top'] && $Blog->get_setting( 'paged_comments' ) )
		{ // Prev/Next page navigation
			$CommentList->page_links( array(
					'page_url' => url_add_tail( $Item->get_permanent_url(), '#comments' ),
					'block_start' => $params['nav_block_start'],
					'block_end'   => $params['nav_block_end'],
					'prev_text'   => $params['nav_prev_text'],
					'next_text'   => $params['nav_next_text'],
					'prev_class'  => $params['nav_prev_class'],
					'next_class'  => $params['nav_next_class'],
				) );
		}


		if( $Blog->get_setting( 'threaded_comments' ) )
		{	// Array to store the comment replies
			global $CommentReplies;
			$CommentReplies = array();

			if( $Comment = get_comment_from_session( 'preview' ) )
			{	// Init PREVIEW comment
				if( $Comment->item_ID == $Item->ID )
				{
					$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array( $Comment );
				}
			}
		}

		if( ! $params['nav_top_inside'] )
		{ // To use comments page navigation before list
			echo $params['comment_list_start'];
		}

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
					'Comment'               => & $Comment,
					'comment_start'         => $params['comment_start'],
					'comment_end'           => $params['comment_end'],
					'comment_title_before'  => $params['comment_title_before'],
					'comment_title_after'   => $params['comment_title_after'],
					'comment_avatar_before' => $params['comment_avatar_before'],
					'comment_avatar_after'  => $params['comment_avatar_after'],
					'comment_rating_before' => $params['comment_rating_before'],
					'comment_rating_after'  => $params['comment_rating_after'],
					'comment_text_before'   => $params['comment_text_before'],
					'comment_text_after'    => $params['comment_text_after'],
					'comment_info_before'   => $params['comment_info_before'],
					'comment_info_after'    => $params['comment_info_after'],
					'author_link_text'      => $params['author_link_text'],
					'link_to'               => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
					'author_link_text'      => $params['author_link_text'],
					'image_size'            => $params['comment_image_size'],
				) );
			// Note: You can customize the default item comment by copying the generic
			// /skins/_item_comment.inc.php file into the current skin folder.
			// ---------------------- END OF COMMENT ---------------------

			if( $Blog->get_setting( 'threaded_comments' ) )
			{	// Display the comment replies
				display_comment_replies( $Comment->ID, $params );
			}
		}	// End of comment list loop.

		if( ! $params['nav_bottom_inside'] )
		{ // To use comments page navigation after list
			echo $params['comment_list_end'];
		}

		if( $params['disp_nav_bottom'] && $Blog->get_setting( 'paged_comments' ) )
		{ // Prev/Next page navigation
			$CommentList->page_links( array(
					'page_url'    => url_add_tail( $Item->get_permanent_url(), '#comments' ),
					'block_start' => $params['nav_block_start'],
					'block_end'   => $params['nav_block_end'],
					'prev_text'   => $params['nav_prev_text'],
					'next_text'   => $params['nav_next_text'],
					'prev_class'  => $params['nav_prev_class'],
					'next_class'  => $params['nav_next_class'],
				) );
		}

		if( $params['nav_bottom_inside'] )
		{ // To use comments page navigation inside list (Useful for table markup)
			echo $params['comment_list_end'];
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
if( $params['disp_comment_form'] && // if enabled by skin param
    $Blog->get_setting( 'allow_comments' ) != 'never' && // if enabled by collection setting
    $Item->get_type_setting( 'use_comments' ) ) // if enabled by item type setting
{	// Display a comment form only if it is enabled:
	if( $Blog->get_ajax_form_enabled() )
	{
		// The following params will be piped through the AJAX request...
		$json_params = array(
			'action' => 'get_comment_form',
			'p' => $Item->ID,
			'blog' => $Blog->ID,
			'reply_ID' => param( 'reply_ID', 'integer', 0 ),
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

// ----------- Register for item's comment notification -----------
if( is_logged_in() && $Item->can_comment( NULL ) )
{
	global $DB;
	global $UserSettings;

	$not_subscribed = true;
	$creator_User = $Item->get_creator_User();

	if( $Blog->get_setting( 'allow_comment_subscriptions' ) )
	{
		$sql = 'SELECT count( sub_user_ID )
							FROM (
								SELECT DISTINCT sub_user_ID
								FROM T_subscriptions
								WHERE sub_user_ID = '.$current_User->ID.' AND sub_coll_ID = '.$Blog->ID.' AND sub_comments <> 0

								UNION

								SELECT user_ID
								FROM T_coll_settings AS opt
								INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
								INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
								LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
								LEFT JOIN T_users ON ( user_grp_ID = bloggroup_group_ID )
								LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = user_ID )
								WHERE opt.cset_coll_ID = '.$Blog->ID.'
									AND opt.cset_name = "opt_out_comment_subscription"
									AND opt.cset_value = 1
									AND user_ID = '.$current_User->ID.'
									AND ( sub_comments IS NULL OR sub_comments <> 0 )

								UNION

								SELECT sug_user_ID
								FROM T_coll_settings AS opt
								INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
								INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
								LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
								LEFT JOIN T_users__secondary_user_groups ON ( sug_grp_ID = bloggroup_group_ID )
								LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = sug_user_ID )
								WHERE opt.cset_coll_ID = '.$Blog->ID.'
									AND opt.cset_name = "opt_out_comment_subscription"
									AND opt.cset_value = 1
									AND sug_user_ID = '.$current_User->ID.'
									AND ( sub_comments IS NULL OR sub_comments <> 0 )

								UNION

								SELECT bloguser_user_ID
								FROM T_coll_settings AS opt
								INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
								INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
								LEFT JOIN T_coll_user_perms ON ( bloguser_blog_ID = opt.cset_coll_ID AND bloguser_ismember = 1 )
								LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = bloguser_user_ID )
								WHERE opt.cset_coll_ID = '.$Blog->ID.'
									AND opt.cset_name = "opt_out_comment_subscription"
									AND opt.cset_value = 1
									AND bloguser_user_ID = '.$current_User->ID.'
									AND ( sub_comments IS NULL OR sub_comments <> 0 )
							) AS users';

		if( $DB->get_var( $sql ) > 0 )
		{
			echo '<p>'.T_( 'You are receiving notifications when anyone comments on any post.' );
			echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></p>';
			$not_subscribed = false;
		}
	}

	if( $params['disp_notification'] )
	{	// Display notification link
		echo $params['notification_before'];

		$notification_icon = get_icon( 'notification' );

		if( $not_subscribed && ( $creator_User->ID == $current_User->ID ) && ( $UserSettings->get( 'notify_published_comments', $current_User->ID ) != 0 ) )
		{
			echo '<p>'.$notification_icon.' <span>'.$params['notification_text'];
			echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
			$not_subscribed = false;
		}
		if( $not_subscribed && $Blog->get_setting( 'allow_item_subscriptions' ) )
		{
			if( get_user_isubscription( $current_User->ID, $Item->ID ) )
			{
				echo '<p>'.$notification_icon.' <span>'.$params['notification_text2'];
				echo ' <a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=0&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Click here to unsubscribe.' ).'</a></span></p>';
			}
			else
			{
				echo '<p>'.$notification_icon.' <span><a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=1&amp;'.url_crumb( 'collections_isubs_update' ).'">'.$params['notification_text3'].'</a></span></p>';
			}
		}

		echo $params['notification_after'];
	}
}


if( $Item->can_see_comments( false ) && ( $params['disp_comments'] || $params['disp_trackbacks'] || $params['disp_pingbacks'] ) )
{	// user is allowed to see comments
	// Display link for comments feed:
	$Item->feedback_feed_link( '_rss2', '<div class="feedback_feed_msg"><p>', '</p></div>', $params['feed_title'] );
}

?>