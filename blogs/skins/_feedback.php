<?php
	/**
	 * This is the template that displays the feedback for a post
	 * (comments, trackbak, pingback...)
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
	<?php }
	if( $disp_trackbacks ) {
		$type_list[] = "'trackback'";
		$disp_title[] = T_("Trackbacks"); ?>
		<a name="trackbacks"></a>
	<?php }
	if( $disp_pingbacks ) {
		$type_list[] = "'pingback'";
		$disp_title[] = T_("Pingbacks"); ?>
		<a name="pingbacks"></a>
	<?php } ?>

	<?php if( $disp_trackback_url )
	{	// We want to display the trackback URL: ?>
	<h4><?php echo T_('Trackback address for this post:') ?></h4>
	<code><?php $Item->trackback_url() ?></code>
	<?php } ?>

	<?php
	if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
	{
	?>

	<!-- Title for comments, tbs, pbs... -->
	<h4><?php echo implode( ", ", $disp_title) ?>:</h4>

	<?php
	$CommentList = & new CommentList( 0, implode(',', $type_list), array(), $id, '', 'ASC' );

	$CommentList->display_if_empty(
								'<div class="bComment"><p>' .
								sprintf( /* TRANS: NO comments/trackabcks/pingbacks/ FOR THIS POST... */
													T_('No %s for this post yet...'), implode( "/", $disp_title) ) .
								'</p></div>' );

	while( $Comment = $CommentList->get_next() )
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
						echo T_('Comment from:');
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
				<span class="bIcons">
					<a href="<?php $Comment->permalink() ?>" title="<?php echo T_('Permanent link to this comment') ?>"><img src="<?php imgbase() ?>chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
				</span>
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
		<form action="<?php echo $htsrv_url ?>comment_post.php" method="post" class="bComment">

			<input type="hidden" name="comment_post_ID" value="<?php $Item->ID() ?>" />
			<input type="hidden" name="redirect_to" value="<?php
					// Make sure we get back to the right page (on the right domain)
					// fplanque>> TODO: check if we can use the permalink instead but we must check that application wide,
					// that is to say: check with the comments in a pop-up etc...
					echo regenerate_url( '', '', $Blog->get('blogurl') );
				?>" />

			<?php
			if( is_logged_in() )
			{ // User is logged in:
				?>
				<fieldset>
					<div class="label"><?php echo T_('User') ?>:</div>
					<div class="info">
						<strong><?php $current_User->preferred_name()?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
						</div>
				</fieldset>
				<?php
			}
			else
			{ // User is not loggued in:
				form_text( 'author', $comment_author, 40, T_('Name'), '', 100, 'bComment' );

				form_text( 'email', $comment_author_email, 40, T_('Email'), T_('Your email address will <strong>not</strong> be displayed on this site.'), 100, 'bComment' );

				form_text( 'url', $comment_author_url, 40, T_('Site/Url'), T_('Your URL will be displayed.'), 100, 'bComment' );
			}

			// TODO: use a smaller textarea when using c=1 GET param
			form_textarea( 'comment', '', 10, T_('Comment text'),
											T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)), 40, 'bComment' );
			?>

			<fieldset>
				<div class="label"><?php echo T_('Options') ?>:
				<?php if( (substr($comments_use_autobr,0,4) == 'opt-') && (! is_logged_in()) )
				{ // Ladies and gentlemen, check out the biggest piece of anti IE-layout-bugs
					// crap you've ever seen:
					echo '<br />&nbsp;'; // make the float a little higher
				} ?>
				</div>
				<div class="input">
				<?php if( substr($comments_use_autobr,0,4) == 'opt-') { ?>
				<input type="checkbox" class="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><?php echo T_('Auto-BR') ?></label> <span class="notes">(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</span><br />
				<?php }
				if( ! is_logged_in() )
				{ // User is not logged in:
					?>
					<input type="checkbox" class="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><?php echo T_('Remember me') ?></label> <span class="notes"><?php echo T_('(Set cookies for name, email &amp; url)') ?></span>
					<?php
				} ?>
				</div>
			</fieldset>

			<fieldset>
				<div class="input">
					<input type="submit" name="submit" class="submit" value="<?php echo T_('Send comment') ?>" tabindex="8" />
				</div>
			</fieldset>

			<div class="clear"></div>

		</form>
		<?php
		}
	}
	?>

<?php } // if you delete this the sky will fall on your head ?>