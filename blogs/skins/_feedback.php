<?php
/**
 * This is the template that displays the feedback for a post
 * (comments, trackback, pingback...)
 *
 * You may want to call this file multiple time in a row with different $c $tb $pb params.
 * This allow to seprate different kinds of feedbacks instead of displaying them mixed together
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=1&more=1&c=1&tb=1&pb=1
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( empty($c) )
{	// Comments not requested
	$disp_comments = 0;					// DO NOT Display the comments if not requested
	$disp_comment_form = 0;			// DO NOT Display the comments form if not requested
}

if( empty($tb) || !$Blog->get( 'allowtrackbacks' ) )
{	// Trackback not requested or not allowed
	$disp_trackbacks = 0;				// DO NOT Display the trackbacks if not requested
	$disp_trackback_url = 0;		// DO NOT Display the trackback URL if not requested
}

if( empty($pb) )
{	// Pingback not requested
	$disp_pingbacks = 0;				// DO NOT Display the pingbacks if not requested
}

if( ! ($disp_comments || $disp_comment_form || $disp_trackbacks || $disp_trackback_url || $disp_pingbacks ) )
{	// Nothing more to do....
	return false;
}

echo '<a id="feedbacks"></a>';

$type_list = array();
$disp_title = array();
if( $disp_comments )
{	// We requested to display comments
	if( $Item->can_see_comments() )
	{ // User can see a comments
		$type_list[] = "'comment'";
		$disp_title[] = T_("Comments");
	}
	else
	{ // Use cannot see comments
		$disp_comments = false;
	}
	echo '<a id="comments"></a>';
}
if( $disp_trackbacks )
{
	$type_list[] = "'trackback'";
	$disp_title[] = T_("Trackbacks");
	echo '<a id="trackbacks"></a>';
}
if( $disp_pingbacks )
{
	$type_list[] = "'pingback'";
	$disp_title[] = T_("Pingbacks");
	echo '<a id="pingbacks"></a>';
}

if( $disp_trackback_url )
{ // We want to display the trackback URL:
	?>
	<h4><?php echo T_('Trackback address for this post:') ?></h4>

	<?php
	/*
	 * Trigger plugin event, which could display a captcha form, before generating a whitelisted URL:
	 */
	if( ! $Plugins->trigger_event_first_true( 'DisplayTrackbackAddr', array('Item' => & $Item, 'template' => '<code>%url%</code>') ) )
	{ // No plugin displayed a payload, so we just display the default:
		?>
		<code><?php $Item->trackback_url() ?></code>
		<?php
	}
}


if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
{
	?>

	<!-- Title for comments, tbs, pbs... -->
	<h4><?php echo implode( ", ", $disp_title) ?>:</h4>

	<?php
	$CommentList = & new CommentList( NULL, implode(',', $type_list), array('published'), $Item->ID, '', 'ASC' );

	$CommentList->display_if_empty(
								'<div class="bComment"><p>' .
								sprintf( /* TRANS: NO comments/trackabcks/pingbacks/ FOR THIS POST... */
													T_('No %s for this post yet...'), implode( "/", $disp_title) ) .
								'</p></div>' );

  /**
	 * @var Comment
	 */
	while( $Comment = & $CommentList->get_next() )
	{	// Loop through comments:
		?>
		<!-- ========== START of a COMMENT/TB/PB ========== -->
		<?php $Comment->anchor() ?>
		<div class="bComment">
			<div class="bCommentTitle">
			<?php
				switch( $Comment->get( 'type' ) )
				{
					case 'comment': // Display a comment:
						echo T_('Comment from:').' ';
						$Comment->author();
						$Comment->msgform_link( $Blog->get('msgformurl') );
						$Comment->author_url( '', ' &middot; ', '' );
						break;

					case 'trackback': // Display a trackback:
						echo T_('Trackback from:') ?>
						<?php $Comment->author( '', '#', '', '#', 'htmlbody', true ) ?>
						<?php break;

					case 'pingback': // Display a pingback:
						echo T_('Pingback from:') ?>
						<?php $Comment->author( '', '#', '', '#', 'htmlbody', true ) ?>
						<?php break;
				}
			?>
			</div>
			<div class="bCommentText">
				<?php $Comment->content() ?>
			</div>
			<div class="bCommentSmallPrint">
				<?php $Comment->permanent_link( '#', '#', 'permalink_right' ); ?>

				<?php $Comment->edit_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for editing */ ?>
				<?php $Comment->delete_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for deleting */ ?>

				<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
			</div>
		</div>
		<!-- ========== END of a COMMENT/TB/PB ========== -->
		<?php
	}	// End of comment list loop.


	// _______________________________________________________________

	// Display count of comments to be moderated:
	$Item->feedback_moderation( 'feedbacks', '<div class="moderation_msg"><p>', '</p></div>',
			T_('This post has no feedback awaiting moderation...'),
			T_('This post has 1 feedback awaiting moderation... %s'),
			T_('This post has %d feedbacks awaiting moderation... %s') );

	// _______________________________________________________________


	// Comment form:
	if( $disp_comment_form && $Item->can_comment() )
	{ // We want to display the comments form and the item can be commented on:

		// Default form params:
		$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
		$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
		$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
		$comment_content = '';

		// PREVIEW:
		$preview_Comment = $Session->get('core.preview_Comment');

		if( $preview_Comment )
		{
			if( $preview_Comment->item_ID == $Item->ID )
			{ // display PREVIEW:
				?>
				<div class="bComment" id="comment_preview">
					<div class="bCommentTitle">
					<?php
						echo T_('PREVIEW Comment from:').' ';
						$preview_Comment->author();
						$preview_Comment->msgform_link( $Blog->get('msgformurl') );
						$preview_Comment->author_url( '', ' &middot; ', '' );
					?>
					</div>
					<div class="bCommentText">
						<?php $preview_Comment->content() ?>
					</div>
					<div class="bCommentSmallPrint">
						<?php $preview_Comment->date() ?> @ <?php $preview_Comment->time( 'H:i' ) ?>
					</div>
				</div>

				<?php
				// Form fields:
				$comment_content = $preview_Comment->original_content;
				// for visitors:
				$comment_author = $preview_Comment->author;
				$comment_author_email = $preview_Comment->author_email;
				$comment_author_url = $preview_Comment->author_url;
			}

			// delete any preview comment from session data:
			$Session->delete( 'core.preview_Comment' );
			$preview_Comment = NULL;
		}

		?>
		<h4 class="bCommentLeaveHead"><?php echo T_('Leave a comment') ?>:</h4>

		<!-- form to add a comment -->
		<?php
		$Form = & new Form( $htsrv_url.'comment_post.php', 'bComment_form_id_'.$Item->ID );
		$Form->begin_form( 'bComment', '', array( 'target' => '_self' ) );

		// TODO: dh> a plugin hook would be useful here to add something to the top of the Form.
		//           Actually, the best would be, if the $Form object could be changed by a plugin
		//           before display!

		$Form->hidden( 'comment_post_ID', $Item->ID );
		$Form->hidden( 'redirect_to',
				// Make sure we get back to the right page (on the right domain)
				// fplanque>> TODO: check if we can use the permalink instead but we must check that application wide,
				// that is to say: check with the comments in a pop-up etc...
				url_rel_to_same_host(regenerate_url( '', '', $Blog->get('blogurl'), '&' ), $htsrv_url) );

		if( is_logged_in() )
		{ // User is logged in:
			$Form->begin_fieldset();
			$Form->info_field( T_('User'), '<strong>'.$current_User->get_preferred_name().'</strong>'
				.' '.get_user_profile_link( ' [', ']', T_('Edit profile') ) );
			$Form->end_fieldset();
		}
		else
		{ // User is not logged in:
			// Note: we use funky field names to defeat the most basic guestbook spam bots
			$Form->text( 'u', $comment_author, 40, T_('Name'), '', 100, 'bComment' );
			$Form->text( 'i', $comment_author_email, 40, T_('Email'), T_('Your email address will <strong>not</strong> be displayed on this site.'), 100, 'bComment' );
			$Form->text( 'o', $comment_author_url, 40, T_('Site/Url'), T_('Your URL will be displayed.'), 100, 'bComment' );
		}

		echo '<div class="comment_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'DisplayCommentToolbar', array() );
		echo '</div>';

		// Message field:
		// TODO: dh> this uses "id" "p" - should be more distinctive..
		$Form->textarea( 'p', $comment_content, 10, T_('Comment text'),
										T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)), 40, 'bComment' );
		// set b2evoCanvas for plugins
		echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "p" );</script>';
		$comment_options = array();
		$Form->output = false;
		$Form->label_to_the_left = false;
		$old_label_suffix = $Form->label_suffix;
		$Form->label_suffix = '';
		$Form->switch_layout('inline');
		if( substr($comments_use_autobr,0,4) == 'opt-')
		{
			$comment_options[] = $Form->checkbox_input( 'comment_autobr', ($comments_use_autobr == 'opt-out'), T_('Auto-BR'), array(
				'note' => '('.T_('Line breaks become &lt;br /&gt;').')', 'tabindex' => 6 ) );
		}
		if( ! is_logged_in() )
		{ // User is not logged in:
			$comment_options[] = $Form->checkbox_input( 'comment_cookies', true, T_('Remember me'), array(
				'note' => '('.T_('Set cookies for name, email and url').')', 'tabindex' => 7 ) );
			// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).

			$comment_options[] = $Form->checkbox_input( 'comment_allow_msgform', true, T_('Allow message form'), array(
				'note' => '('.T_('Allow users to contact you through a message form (your email will NOT be displayed.)').')', 'tabindex' => 8 ) );
			// TODO: If we have an email in a cookie, Add links called "Add a contact icon to all my previous comments" and "Remove contact icon from all my previous comments".
		}
		$Form->output = true;
		$Form->label_to_the_left = true;
		$Form->label_suffix = $old_label_suffix;
		$Form->switch_layout(NULL);

		if( ! empty($comment_options) )
		{
			$Form->begin_fieldset();
				echo $Form->begin_field( NULL, T_('Options'), true );
				echo implode( '<br />', $comment_options );
				echo $Form->end_field();
			$Form->end_fieldset();
		}

		$Plugins->trigger_event( 'DisplayCommentFormFieldset', array( 'Form' => & $Form, 'Item' => & $Item ) );

		$Form->begin_fieldset();
			echo '<div class="input">';

			$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit', 'value' => T_('Send comment'), 'tabindex' => 10 ) );
			$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview', 'value' => T_('Preview'), 'tabindex' => 9 ) );

			$Plugins->trigger_event( 'DisplayCommentFormButton', array( 'Form' => & $Form, 'Item' => & $Item ) );

			echo '</div>';
		$Form->end_fieldset();
		?>

		<div class="clear"></div>

		<?php
		$Form->end_form();
	}

}


/*
 * $Log$
 * Revision 1.88  2007/01/26 04:49:17  fplanque
 * cleanup
 *
 * Revision 1.87  2007/01/18 22:28:53  fplanque
 * no unnecessary complexity
 *
 * Revision 1.86  2007/01/16 22:53:38  blueyed
 * TODOs
 *
 * Revision 1.85  2006/12/28 23:20:40  fplanque
 * added plugin event for displaying comment form toolbars
 * used by smilies plugin
 *
 * Revision 1.84  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.83  2006/11/20 22:15:30  blueyed
 * whitespace
 *
 * Revision 1.82  2006/10/23 22:19:03  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.81  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 */
?>
