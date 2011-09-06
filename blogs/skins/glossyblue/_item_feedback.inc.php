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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * @subpackage glossyblue
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<!-- ===================== START OF FEEDBACK ===================== -->
<?php

// Default params:
$params = array_merge( array(
		'disp_comments'      =>	true,
		'disp_comment_form'	 =>	true,
		'disp_trackbacks'	   =>	true,
		'disp_trackback_url' =>	true,
		'disp_pingbacks'	   =>	true,
		'before_section_title' => '<h3>',
		'after_section_title'  => '</h3>',
		'form_title_start' => '<h3>',
		'form_title_end'  => '</h3>',
		'before_comment_error' => '<p><em>',
		'after_comment_error'  => '</em></p>',
		'before_comment_form'  => '',
		'after_comment_form'   => '',
	), $params );


global $c, $tb, $pb, $redir, $comment_allowed_tags, $comments_use_autobr;

global $cookie_name, $cookie_email, $cookie_url;


if( $Item->can_see_comments( true ) )
{
	if( empty($c) )
	{	// Comments not requested
		$params['disp_comments'] = false;					// DO NOT Display the comments if not requested
		$params['disp_comment_form'] = false;			// DO NOT Display the comments form if not requested
	}

	if( empty($tb) || !$Blog->get( 'allowtrackbacks' ) )
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

	if( $params['disp_comments'] )
	{	// We requested to display comments
		if( $Item->can_see_comments() )
		{ // User can see a comments
			$type_list[] = 'comment';
			if( $title = $Item->get_feedback_title( 'comments' ) )
			{
				$disp_title[] = $title;
			}
		}
		else
		{ // Use cannot see comments
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
			if( $title = $Item->get_feedback_title( 'feedbacks', '', T_('Feedback awaiting moderation'), T_('Feedback awaiting moderation'), 'draft' ) )
			{ // We have some feedback awaiting moderation: we'll want to show that in the title
				$disp_title[] = $title;
			}
		}

		if( empty($disp_title) )
		{	// Still no title
			$disp_title[] = T_('No feedback yet');
		}

		echo $params['before_section_title'];
		echo implode( ', ', $disp_title);
		echo $params['after_section_title'];

		$CommentList = new CommentList2( $Blog, $Blog->get_setting('comments_per_page'), 'CommentCache', 'c_' );

		// Filter list:
		$CommentList->set_default_filters( array(
				'types' => $type_list,
				'statuses' => array ( 'published' ),
				'post_ID' => $Item->ID,
				'order' => $Blog->get_setting( 'comments_orderdir' ),
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
	?>

	<ol class="commentlist">
	<?php
		/* This variable is for alternating comment background */
			$oddcomment = 'alt';
		/**
		 * @var Comment
		 */
		while( $Comment = & $CommentList->get_next() )
		{	// Loop through comments:
			?>
			<!-- ========== START of a COMMENT/TB/PB ========== -->

			<li id="comment-<?php $Comment->ID(); ?>" class="<?php echo $oddcomment; ?>"><?php $Comment->anchor() ?>

				<?php
					switch( $Comment->get( 'type' ) )
					{
						case 'comment': // Display a comment:
							$Comment->permanent_link( array(
								'before'    => '',
								'after'     => ' ',
								'text' => '&#167; ',
								'nofollow' => true,
							) );
							$Comment->author('','','','&#174;','htmlbody',true);
							$Comment->msgform_link( $Blog->get('msgformurl') );

							echo ' '.T_('said on :').' <small class="commentmetadata">';
							$Comment->date() ?> @ <?php $Comment->time( 'H:i' ); $Comment->edit_link( '','',get_icon( 'edit' ) ); $Comment->delete_link( '','',get_icon( 'delete' ) ); echo '</small>';
							break;

						case 'trackback': // Display a trackback:
							$Comment->permanent_link( T_('Trackback') );
							echo ' '.T_('from:').' ';
							$Comment->author( '', '#', '', '#', 'htmlbody', true );
							break;

						case 'pingback': // Display a pingback:
							$Comment->permanent_link( T_('Pingback') );
							echo ' '.T_('from:').' ';
							$Comment->author( '', '#', '', '#', 'htmlbody', true );
							break;
					}
				?>

				<?php $Comment->rating(); ?>
				<div class="bCommentText">
					<?php $Comment->content() ?>
				</div>
				<div class="bCommentSmallPrint">



				</div>
			</li>
			<?php /* Changes every other comment to a different class */
			if ('alt' == $oddcomment) $oddcomment = '';
			else $oddcomment = 'alt';
		?>
			<!-- ========== END of a COMMENT/TB/PB ========== -->
			<?php
		}	// End of comment list loop.
	?>
	</ol><?php

		if( $Blog->get_setting( 'paged_comments' ) )
		{ // Prev/Next page navigation
			$CommentList->page_links( array(
					'page_url' => url_add_tail( $Item->get_permanent_url(), '#comments' ),
				) );
		}

		// Restore "redir" param
		forget_param('redir');

		// Display count of comments to be moderated:
		$Item->feedback_moderation( 'feedbacks', '<div class="moderation_msg"><p>', '</p></div>', '',
				T_('This post has 1 feedback awaiting moderation... %s'),
				T_('This post has %d feedbacks awaiting moderation... %s') );

		// _______________________________________________________________

		// Display link for comments feed:
		$Item->feedback_feed_link( '_rss2', '<div class="feedback_feed_msg"><p>', '</p></div>' );
	}

		// _______________________________________________________________

}

// ------------------ COMMENT FORM INCLUDED HERE ------------------
if( $Blog->get_setting( 'ajax_form_enabled' ) )
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
// ---------------------- END OF COMMENT FORM ---------------------

?>