<?php
	/**
	 * This is the template that displays the feedback for a post
	 * (comments, trackback, pingback...)
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?p=1&more=1&c=1&tb=1&pb=1
	 * Note: don't code this URL by hand, use the template functions to generate it!
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	// --- //

	if( ! $c )
	{	// Comments not requested
		$disp_comments = 0;					// DO NOT Display the comments if not requested
		$disp_comment_form = 0;			// DO NOT Display the comments form if not requested
	}

	if( (!$tb) || (!$Blog->get( 'allowtrackbacks' )) )
	{	// Trackback not requested or not allowed
		$disp_trackbacks = 0;				// DO NOT Display the trackbacks if not requested
		$disp_trackback_url = 0;		// DO NOT Display the trackback URL if not requested
	}

	if( (!$pb) || (!$Blog->get( 'allowpingbacks' )) )
	{	// Pingback not requested or not allowed
		$disp_pingbacks = 0;				// DO NOT Display the pingbacks if not requested
	}

	if( ! ($disp_comments || $disp_comment_form || $disp_trackbacks || $disp_trackback_url || $disp_pingbacks ) )
	{	// Nothing more to do....
		return false;
	}

	?>
	<a name="feedbacks"></a>
	<?php

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
		?>
		<a name="comments"></a>
		<?php
	}
	if( $disp_trackbacks )
	{
		$type_list[] = "'trackback'";
		$disp_title[] = T_("Trackbacks"); ?>
		<a name="trackbacks"></a>
		<?php
	}
	if( $disp_pingbacks )
	{
		$type_list[] = "'pingback'";
		$disp_title[] = T_("Pingbacks"); ?>
		<a name="pingbacks"></a>
		<?php
	}

	if( $disp_trackback_url )
	{ // We want to display the trackback URL:
		?>
		<h4><?php echo T_('Trackback address for this post:') ?></h4>
		<code><?php $Item->trackback_url() ?></code>
		<?php
	}
	?>

	<?php
	if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
	{
	?>

	<!-- Title for comments, tbs, pbs... -->
	<h4><?php echo implode( ", ", $disp_title) ?>:</h4>

	<?php
	$CommentList = & new CommentList( 0, implode(',', $type_list), array(), $Item->ID, '', 'ASC' );

	$CommentList->display_if_empty(
								'<div class="bComment"><p>' .
								sprintf( /* TRANS: NO comments/trackabcks/pingbacks/ FOR THIS POST... */
													T_('No %s for this post yet...'), implode( "/", $disp_title) ) .
								'</p></div>' );

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

				$Comment->edit_link( ' &middot; ' ) // Link to backoffice for editing
			?>
			</div>
			<div class="bCommentText">
				<?php $Comment->content() ?>
			</div>
			<div class="bCommentSmallPrint">
				<?php	$Comment->permanent_link( '#', '#', 'permalink_right' ); ?>
				<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
			</div>
		</div>
		<!-- ========== END of a COMMENT/TB/PB ========== -->
		<?php
	}

	if( $disp_comment_form )
	{	// We want to display the comments form:
		if( $Item->can_comment() )
		{ // User can leave a comment
			?>
			<h4><?php echo T_('Leave a comment') ?>:</h4>

			<?php
			$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
			$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
			$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
			?>

			<!-- form to add a comment -->
			<?php
			$Form = & new Form( $htsrv_url.'comment_post.php', 'bComment_form_id_'.$Item->ID );
			$Form->begin_form( 'bComment' );

			$Form->hidden( 'comment_post_ID', $Item->ID );
			$Form->hidden( 'redirect_to',
					// Make sure we get back to the right page (on the right domain)
					// fplanque>> TODO: check if we can use the permalink instead but we must check that application wide,
					// that is to say: check with the comments in a pop-up etc...
					regenerate_url( '', '', $Blog->get('blogurl') ) );

			if( is_logged_in() )
			{ // User is logged in:
				$Form->begin_fieldset();
				$Form->info_field( T_('User'), '<strong>'.$current_User->get_preferred_name().'</strong>'
					.' '.get_user_profile_link( ' [', ']', T_('Edit profile') ) );
				$Form->end_fieldset();
			}
			else
			{ // User is not loggued in:
				$Form->text( 'author', $comment_author, 40, T_('Name'), '', 100, 'bComment' );

				$Form->text( 'email', $comment_author_email, 40, T_('Email'), T_('Your email address will <strong>not</strong> be displayed on this site.'), 100, 'bComment' );

				$Form->text( 'url', $comment_author_url, 40, T_('Site/Url'), T_('Your URL will be displayed.'), 100, 'bComment' );
			}

			// TODO: use a smaller textarea when using c=1 GET param
			$Form->textarea( 'comment', '', 10, T_('Comment text'),
											T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)), 40, 'bComment' );


			$comment_options = '';
			$Form->output = false;
			$Form->label_to_the_left = false;
			$old_label_suffix = $Form->label_suffix;
			$Form->label_suffix = '';
			$Form->switch_layout('inline');
			if( substr($comments_use_autobr,0,4) == 'opt-')
			{
				$comment_options .= $Form->checkbox_input( 'comment_autobr', ($comments_use_autobr == 'opt-out'), T_('Auto-BR'), array(
					'note' => '('.T_('Line breaks become &lt;br /&gt;').')', 'tabindex' => 6 ) ).'<br />';
			}
			if( ! is_logged_in() )
			{ // User is not logged in:
				$comment_options .= $Form->checkbox_input( 'comment_cookies', true, T_('Remember me'), array(
					'note' => '('.T_('Set cookies for name, email and url').')', 'tabindex' => 7 ) );
			}
			$Form->output = true;
			$Form->label_to_the_left = true;
			$Form->label_suffix = $old_label_suffix;
			$Form->switch_layout(NULL);

			if( ! empty($comment_options) )
			{
				$Form->begin_fieldset();
					echo $Form->begin_field( NULL, T_('Options'), true );
					echo $comment_options;
					echo $Form->end_field();
				$Form->end_fieldset();
			}

			$Plugins->trigger_event( 'DisplayCommentFormFieldset', array( 'Form' => & $Form, 'Item' => & $Item ) );

			$Form->begin_fieldset();
				?>
				<div class="input">
				<?php
				$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID, 'class' => 'submit', 'value' => T_('Send comment'), 'tabindex' => 8 ) );

				$Plugins->trigger_event( 'DisplayCommentFormButton', array( 'Form' => & $Form, 'Item' => & $Item ) );
				?>

				</div>

				<?php
			$Form->end_fieldset();
			?>

			<div class="clear"></div>

			<?php
			$Form->end_form();
		}
	}
	?>

<?php } // if you delete this the sky will fall on your head ?>