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
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

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
						echo T_('Comment from:') ?> 
						<?php $Comment->author() ?> 
						<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
						<?php break;

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
					<a href="<?php echo msgform_url($Comment->author_User->ID,$Comment->Item->ID,$Comment->ID) ?>" title="<?php echo T_('E-mail the author of the comment') ?>"><img src="<?php imgbase() ?>envelope.gif" height="10" width="13" border="0" class="middle"></a>
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
		<form action="<?php echo $htsrv_url ?>/comment_post.php" method="post" class="bComment">
		
			<input type="hidden" name="comment_post_ID" value="<?php $Item->ID() ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo regenerate_url() ?>" />

			<?php 
			if( is_logged_in() ) 
			{ // User is logged in: 
				?>
				<fieldset>
					<div class="label"><?php echo T_('User') ?>:</div>
					<div class="info">
						<strong><?php $current_User->prefered_name()?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
						</div>
				</fieldset>
				<?php 
			} 
			else
			{ // User is not loggued in: 
				?>
				<fieldset>
					<div class="label"><label for="author"><?php echo T_('Name') ?>:</label></div>
					<div class="input">
							<input type="text" name="author" id="author" value="<?php echo $comment_author ?>" size="40" tabindex="1" class="bComment" />
					</div>
				</fieldset>
				
				<fieldset>
					<div class="label"><label for="email"><?php echo T_('Email') ?>:</label></div>
					<div class="input"><input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" class="bComment" /><br />
						<span class="notes"><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></span>
					</div>
				</fieldset>
				
				<fieldset>
					<div class="label"><label for="url"><?php echo T_('Site/Url') ?>:</label></div>
					<div class="input"><input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" class="bComment" /><br />
						<span class="notes"><?php echo T_('Your URL will be displayed.') ?></span>
					</div>
				</fieldset>

				<?php 
				} 
			?>
					
			<fieldset>
				<div class="label"><label for="comment"><?php echo T_('Comment text') ?>:</label></div>
				<div class="input"><textarea cols="40" rows="12" name="comment" id="comment" tabindex="4" class="bComment"></textarea><br />
					<span class="notes"><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?><br />
					<?php echo T_('URLs, email, AIM and ICQs will be converted automatically.') ?></span>
				</div>
			</fieldset>
					
			<fieldset>
				<div class="label"><?php echo T_('Options') ?>:</div>
				<div class="input">
				
				<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
				<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><?php echo T_('Auto-BR') ?></label> <span class="notes">(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</span><br />
				<?php } 
				if( ! is_logged_in() ) 
				{ // User is not logged in: 
					?>
					<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><?php echo T_('Remember me') ?></label> <span class="notes"><?php echo T_('(Set cookies for name, email &amp; url)') ?></span>
					<?php 
				} ?>
				</div>
			</fieldset>
		
			<fieldset>
				<div class="input">
					<input type="submit" name="submit" class="buttonarea" value="<?php echo T_('Send comment') ?>" tabindex="8" />
				</div>
			</fieldset>
		
			<div class="clear"></div>
		
		</form>
		<?php 
		} 
	}
	?>

<?php } // if you delete this the sky will fall on your head ?>