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
	 * @subpackage wpc
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

	<?php
	if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
	{	?>
		<!-- Title for comments, tbs, pbs... -->
		<h2 id="comments"><?php echo implode( ", ", $disp_title) ?></h2>
	<?php
	}

	if( $disp_trackback_url )
	{	// We want to display the trackback URL: ?>
		<p><?php echo T_("The <acronym title=\"Uniform Resource Identifier\">URI</acronym> to TrackBack this entry is:"); ?> <em><?php $Item->trackback_url() ?></em></p>
		<?php
	}

	if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
	{
	$CommentList = & new CommentList( 0, implode(',', $type_list), array(), $Item->ID, '', 'ASC' );

	if( ! $CommentList->display_if_empty(
								'<div class="bComment"><p>' .
								sprintf( /* TRANS: NO comments/trackabcks/pingbacks/ FOR THIS POST... */
													T_('No %s for this post yet...'), implode( "/", $disp_title) ) .
								'</p></div>' ) )
	{
		echo '<ol id="commentlist">';

		while( $Comment = & $CommentList->get_next() )
		{	// Loop through comments:
			?>
			<!-- ========== START of a COMMENT/TB/PB ========== -->
			<li>
				<?php $Comment->anchor() ?>
				<?php $Comment->content() ?>
				<p><cite>
				<?php
					switch( $Comment->get( 'type' ) )
					{
						case 'comment': // Display a comment:
							echo T_('Comment by') ?>
							<?php $Comment->author() ?>
							<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
							<?php break;

						case 'trackback': // Display a trackback:
							echo T_('Trackback from') ?>
							<?php $Comment->author( '', '#', '', '#', 'htmlbody', true ) ?>
							<?php break;

						case 'pingback': // Display a pingback:
							echo T_('Pingback from') ?>
							<?php $Comment->author( '', '#', '', '#', 'htmlbody', true ) ?>
							<?php break;
					}
				?>
				&#8212;
				<?php $Comment->date() ?> @ <a href="<?php $Comment->permanent_url() ?>" title="<?php echo T_('Permanent link to this comment') ?>"><?php $Comment->time( 'H:i' ) ?></a>
				<?php $Comment->edit_link( ' | ', '', T_('Edit This') ) // Link to backoffice for editing ?>
				</cite></p>
			</li>
			<!-- ========== END of a COMMENT/TB/PB ========== -->
			<?php
		}
		echo '</ol>';
	}

	if( $disp_comment_form )
	{	// We want to display the comments form:
		if( $Item->can_comment() )
		{ // User can leave a comment
		?>
		<h2 id="postcomment"><?php echo T_('Leave a comment') ?></h2>

		<p><?php echo T_('Allowed XHTML tags'), ': <code>', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?></code></p>

		<?php
			$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
			$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
			$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
		?>

		<!-- form to add a comment -->
		<form action="<?php echo $htsrv_url ?>comment_post.php" method="post" id="commentform">

			<input type="hidden" name="comment_post_ID" value="<?php $Item->ID() ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo regenerate_url() ?>" />

			<fieldset style="border: none">

			<?php
			if( is_logged_in() )
			{ // User is logged in:
				?>
				<p>
					<?php echo T_('User') ?>:
						<strong><?php $current_User->preferred_name()?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
				</p>
				<?php
			}
			else
			{ // User is not loggued in:
				?>
				<p>
					<input type="text" name="author" id="author" value="<?php echo $comment_author ?>" size="40" tabindex="1" class="bComment" />
					<label for="author"><?php echo T_('Name') ?></label>
				</p>

				<p>
					<input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" class="bComment" />
					<label for="email"><?php echo T_('E-mail') ?></label>
				</p>

				<p>
					<input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" class="bComment" />
					<label for="url"><acronym title=\"Uniform Resource Identifier\">URI</acronym></label>
				</p>

				<?php
				}
			?>

		<p>
			<label for="comment"><?php echo T_('Your Comment'); ?></label>
			<br />
			<textarea name="comment" id="comment" cols="70" rows="4" tabindex="4"></textarea>
		</p>

			<p>
				<?php echo T_('Options') ?>:<br />

				<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
				<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><?php echo T_('Auto-BR') ?></label> <span class="notes">(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</span><br />
				<?php }
				if( ! is_logged_in() )
				{ // User is not logged in:
					?>
					<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><?php echo T_('Remember me') ?></label> <span class="notes"><?php echo T_('(Set cookies for name, email &amp; url)') ?></span>
					<?php
				} ?>
			</p>

			<p>
				<input name="submit" type="submit" tabindex="5" value="<?php echo T_("Say It!"); ?>" />
			</p>

			</fieldset>

		</form>
		<?php
		}
	}
	?>

<?php } // if you delete this the sky will fall on your head ?>