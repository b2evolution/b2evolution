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
	), $params );


global $c, $tb, $pb, $comment_allowed_tags, $comments_use_autobr;

global $cookie_name, $cookie_email, $cookie_url;


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
		$type_list[] = "'comment'";
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
	$type_list[] = "'trackback'";
	if( $title = $Item->get_feedback_title( 'trackbacks' ) )
	{
		$disp_title[] = $title;
	}
	echo '<a id="trackbacks"></a>';
}

if( $params['disp_pingbacks'] )
{
	$type_list[] = "'pingback'";
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

	$CommentList = & new CommentList( NULL, implode(',', $type_list), array('published'), $Item->ID, '', 'ASC' );

	// $CommentList->display_if_empty( '<div class="bComment"><p>'.T_('No feedback for this post yet...').'</p></div>' );

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
						$Comment->permanent_link( T_('Comment') );
						echo ' '.T_('from:').' ';
						$Comment->author();
						$Comment->msgform_link( $Blog->get('msgformurl') );
						$Comment->author_url( '', ' &middot; ', '' );
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
			</div>
			<?php $Comment->rating(); ?>
			<div class="bCommentText">
				<?php $Comment->content() ?>
			</div>
			<div class="bCommentSmallPrint">
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
	$Item->feedback_moderation( 'feedbacks', '<div class="moderation_msg"><p>', '</p></div>', '',
			T_('This post has 1 feedback awaiting moderation... %s'),
			T_('This post has %d feedbacks awaiting moderation... %s') );

	// _______________________________________________________________

	// Display link for comments feed:
	$Item->feedback_feed_link( '_rss2', '<div class="feedback_feed_msg"><p>', '</p></div>' );

	// _______________________________________________________________

}



/*
 * Comment form:
 */
if( $params['disp_comment_form'] && $Item->can_comment() )
{ // We want to display the comments form and the item can be commented on:



	if( $Comment = $Session->get('core.preview_Comment') )
	{	// We have a comment to preview
		if( $Comment->item_ID == $Item->ID )
		{ // display PREVIEW:
			?>
			<div class="bComment" id="comment_preview">
				<div class="bCommentTitle">
				<?php
					echo T_('PREVIEW Comment from:').' ';
					$Comment->author();
					$Comment->msgform_link( $Blog->get('msgformurl') );
					$Comment->author_url( '', ' &middot; ', '' );
				?>
				</div>
				<div class="bCommentText">
					<?php $Comment->content() ?>
				</div>
				<div class="bCommentSmallPrint">
					<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
				</div>
			</div>

			<?php
			// Form fields:
			$comment_content = $Comment->original_content;
			// for visitors:
			$comment_author = $Comment->author;
			$comment_author_email = $Comment->author_email;
			$comment_author_url = $Comment->author_url;
		}

		// delete any preview comment from session data:
		$Session->delete( 'core.preview_Comment' );
	}
	else
	{ // New comment:
		$Comment = & new Comment();
		$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
		$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
		$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
		if( empty($comment_author_url) )
		{	// Even if we have a blank cookie, let's reset this to remind the bozos what it's for
			$comment_author_url = 'http://';
		}
		$comment_content = '';
	}


	echo $params['form_title_start'];
	echo T_('Leave a comment');
	echo $params['form_title_end'];


	$Form = & new Form( $htsrv_url.'comment_post.php', 'bComment_form_id_'.$Item->ID, 'post' );
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
		$Form->text( 'i', $comment_author_email, 40, T_('Email'), '<br />'.T_('Your email address will <strong>not</strong> be revealed on this site.'), 100, 'bComment' );
		$Form->text( 'o', $comment_author_url, 40, T_('Website'), '<br />'.T_('Your URL will be displayed.'), 100, 'bComment' );
	}

	if( $Item->can_rate() )
	{	// Comment rating:
		$Form->begin_fieldset();
			echo $Form->begin_field( NULL, T_('Your vote'), true );
			$Comment->rating_input();
			echo $Form->end_field();
		$Form->end_fieldset();
	}

	echo '<div class="comment_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayCommentToolbar', array() );
	echo '</div>';

	// Message field:
	$note = '';
	// $note = T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags));
	$Form->textarea( 'p', $comment_content, 10, T_('Comment text'), $note, 40, 'bComment' );

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "p" );</script>';

	$comment_options = array();

	if( substr($comments_use_autobr,0,4) == 'opt-')
	{
		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_autobr" tabindex="6"'
													.( ($comments_use_autobr == 'opt-out') ? ' checked="checked"' : '' )
													.' value="1" /> '.T_('Auto-BR').'</label>'
													.' <span class="note">('.T_('Line breaks become &lt;br /&gt;').')</span>';
	}

	if( ! is_logged_in() )
	{ // User is not logged in:
		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_cookies" tabindex="7"'
													.' checked="checked" value="1" /> '.T_('Remember me').'</label>'
													.' <span class="note">('.T_('Name, email &amp; website').')</span>';
		// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).

		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_allow_msgform" tabindex="8"'
													.' checked="checked" value="1" /> '.T_('Allow message form').'</label>'
													.' <span class="note">('.T_('Allow users to contact you through a message form (your email will <strong>not</strong> be revealed.').')</span>';
		// TODO: If we have an email in a cookie, Add links called "Add a contact icon to all my previous comments" and "Remove contact icon from all my previous comments".
	}

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


/*
 * $Log$
 * Revision 1.9  2007/11/02 01:55:57  fplanque
 * comment ratings
 *
 * Revision 1.8  2007/11/01 19:52:46  fplanque
 * better comment forms
 *
 * Revision 1.7  2007/09/28 02:18:10  fplanque
 * minor
 *
 * Revision 1.6  2007/09/26 21:54:00  fplanque
 * minor
 *
 * Revision 1.5  2007/09/16 22:07:06  fplanque
 * cleaned up feedback form
 *
 * Revision 1.4  2007/09/08 19:31:28  fplanque
 * cleanup of XML feeds for comments on individual posts.
 *
 * Revision 1.3  2007/06/24 22:26:34  fplanque
 * improved feedback template
 *
 * Revision 1.2  2007/06/24 01:05:31  fplanque
 * skin_include() now does all the template magic for skins 2.0.
 * .disp.php templates still need to be cleaned up.
 *
 * Revision 1.1  2007/06/23 22:09:30  fplanque
 * feedback and item content templates.
 * Interim check-in before massive changes ahead.
 *
 * Revision 1.91  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.90  2007/04/03 19:22:22  blueyed
 * Fixed WhiteSpace
 *
 * Revision 1.89  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
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
