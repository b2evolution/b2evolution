<?php 
	/*
	 * This is the template that displays the feedback for a post
	 * (comments, trackbak, pingback...)
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?p=1&more=1&c=1&tb=1&pb=1
	 * Note: don't code this URL by hand, use the template functions to generate it!
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	// --- //

	if( ! $c ) 
	{	// Comments not requested
		$disp_comments = 0;					// DO NOT Display the comments if not requested
		$disp_comment_form = 0;			// DO NOT Display the comments form if not requested
	}
	
	if( ! $tb ) 
	{	// Trackback not requested
		$disp_trackbacks = 0;				// DO NOT Display the trackbacks if not requested
		$disp_trackback_url = 0;		// DO NOT Display the trackback URL if not requested
	}
	
	if( ! $pb ) 
	{	// Pingback not requested
		$disp_pingbacks = 0;				// DO NOT Display the pingbacks if not requested
	}
	
	if( ! ($disp_comments || $disp_comment_form || $disp_trackbacks || $disp_trackback_url || $disp_pingbacks ) )
	{	// Nothing more to do....
		return false;
	}
	
	$type_list = array();
	$disp_title = array();
	if(  $disp_comments ) { 
		$type_list[] = "'comment'";
		$disp_title[] = T_("Comments"); ?>
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
	
	<?php if( $disp_trackback_url ) {	// We want to display the trackback URL: ?>
	<h4><?php echo T_('Trackback address for this post:') ?></h4>
	<code><?php trackback_url() ?></code>
	<?php } ?>
	
	<?php
	if( $disp_comments || $disp_trackbacks || $disp_pingbacks  )
	{
		
		if( $disp_comments ) 
	?>
	
	<!-- Title for comments, tbs, pbs... -->
	<h4><?php echo implode( ", ", $disp_title) ?>:</h4>
	
	
	<?php
		$queryc = "SELECT * FROM $tablecomments WHERE comment_post_ID = $id AND comment_type IN (".implode(',', $type_list).") ORDER BY comment_date";
		$resultc = mysql_query($queryc) or mysql_oops( $queryc );
		if ($resultc)
		{
		$wxcvbn_c=0; 
		while($rowc = mysql_fetch_object($resultc)) 
		{
			$wxcvbn_c++; 
			$commentdata = get_commentdata($rowc->comment_ID); 
			switch( $commentdata['comment_type'] )
			{
			case 'comment': // Display a comment: ?>
				<!-- comment -->
				<a name="c<?php comment_ID() ?>"></a>
				<h5><?php echo T_('Comment from:') ?> <?php comment_author() ?> <?php comment_author_url_link("", " &middot; ", "") ?></h5>
				<blockquote>
					<small><?php comment_date() ?> @ <?php comment_time("H:i") ?></small>
					<div><?php comment_text() ?></div>
				</blockquote>
				<!-- /comment -->
			<?php break;
			
			case 'trackback': // Display a trackback: ?>
				<!-- trackback -->
				<a name="tb<?php comment_ID() ?>"></a>
				<h5><?php echo T_('Trackback from:') ?> <a href="<?php comment_author_url(); ?>" title="<?php comment_author() ?>"><?php comment_author() ?></a></h5>
				<blockquote>
					<small><?php comment_date() ?> @ <?php comment_time("H:i") ?></small>
					<div><?php comment_text() ?></div>
				</blockquote>
				<!-- /trackback -->
			<?php break;
			
			case 'pingback': // Display a pingback: ?>
				<!-- pingback -->
				<a name="pb<?php comment_ID() ?>"></a>
				<h5><?php echo T_('Pingback from:') ?> <a href="<?php comment_author_url(); ?>" title="<?php comment_author() ?>"><?php comment_author() ?></a></h5>
				<blockquote>
					<small><?php comment_date() ?> @ <?php comment_time("H:i") ?></small>
					<div><?php comment_text() ?></div>
				</blockquote>
				<!-- /pingback -->
			<?php break;
			}
		} // end of the loop, don't delete
	} 
	if ($wxcvbn_c == 0) { ?>
	<!-- this is displayed if there are no comments so far -->
	<p><?php printf( /* TRANS: NO comments/trackabcks/pingbacks/ FOR THIS POST... */ T_('No %s for this post yet...'), implode( "/", $disp_title) ); ?></p>
	<?php /* if you delete this the sky will fall on your head */ } ?>
	
	
	<?php 
	if( $disp_comment_form ) 
	{	// We want to display the comments form: 
		if( $postdata['comments'] != 'open' )
		{ ?>
		<p><em><?php echo  T_('Comments are closed for this post.') ?></em></p>
		<?php }
		else
		{
		?>
		<h4><?php echo T_('Leave a comment') ?>:</h4>
		
		<?php
			$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
			$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
			$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
		?>
		
		<!-- form to add a comment -->
		<form action="<?php echo $htsrv_url ?>/comment_post.php" method="post" class="bComment">
		
			<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo regenerate_url() ?>" />
		<table>
			<tr valign="top">
				<td align="right"><label for="author"><strong><?php echo T_('Name') ?>:</strong></label></td>
				<td align="left"><input type="text" name="author" id="author" value="<?php echo $comment_author ?>" size="40" tabindex="1" class="bComment" /></td>
			</tr>
	
			
			<tr valign="top">
				<td align="right"><label for="email"><strong><?php echo T_('Email') ?>:</strong></label></td>
				<td align="left"><input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" class="bComment" /><br />
					<small><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></small>
				</td>
			</tr>
			
			<tr valign="top">
				<td align="right"><label for="url"><strong><?php echo T_('Site/Url') ?>:</strong></label></td>
				<td align="left"><input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" class="bComment" /><br />
					<small><?php echo T_('Your URL will be displayed.') ?></small>
				</td>
			</tr>
					
			<tr valign="top">
				<td align="right"><label for="comment"><strong><?php echo T_('Comment text') ?>:</strong></label></td>
				<td align="left"><textarea cols="40" rows="12" name="comment" id="comment" tabindex="4" class="bComment"></textarea><br />
					<small><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?><br />
					<?php echo T_('URLs, email, AIM and ICQs will be converted automatically.') ?></small>
				</td>
			</tr>
					
			<tr valign="top">
				<td align="right"><strong><?php echo T_('Options') ?>:</strong></td>
				<td align="left">
				
				<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
				<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><strong><?php echo T_('Auto-BR') ?></strong></label> <small>(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</small><br />
				<?php } ?>
	
				<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><strong><?php echo T_('Remember me') ?></strong></label> <small><?php echo T_('(Set cookies for name, email &amp; url)') ?></small>
				</td>
			</tr>
		
			<tr valign="top">
				<td colspan="2" align="center">
					<input type="submit" name="submit" class="buttonarea" value="<?php echo T_('Send comment') ?>" tabindex="8" />
				</td>
			</tr>
		</table>	
		
		</form>
	<?php 
		} 
	}
	?>

<?php } // if you delete this the sky will fall on your head ?>