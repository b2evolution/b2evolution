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

	if( ! ( $withcomments || $c ) ) 
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
		$disp_title[] = "Comments"; ?>
		<a name="comments"></a>
	<?php } 
	if( $disp_trackbacks ) { 
		$type_list[] = "'trackback'";
		$disp_title[] = "Trackbacks"; ?>
		<a name="trackbacks"></a>
	<?php }
	if( $disp_pingbacks ) { 
		$type_list[] = "'pingback'";
		$disp_title[] = "Pingbacks"; ?>
		<a name="pingbacks"></a>
	<?php } ?>
	
	<?php if( $disp_trackback_url ) {	// We want to display the trackback URL: ?>
	<h4>Trackback address for this post:</h4>
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
				<div class="bComment">
					<div class="bCommentTitle">Comment from <?php comment_author() ?> <?php comment_author_url_link("", " &middot; ", "") ?></div>
					<div class="bCommentText">
					<?php comment_text() ?>
					</div>
					<div class="bCommentSmallPrint">
					<?php comment_date() ?> @ <?php comment_time("H:i") ?>
					</div>
				</div>
				<!-- /comment -->
			<?php break;
			
			case 'trackback': // Display a trackback: ?>
				<!-- trackback -->
				<a name="tb<?php comment_ID() ?>"></a>
				<div class="bComment">
					<div class="bCommentTitle">Trackback from <a href="<?php comment_author_url(); ?>" title="<?php comment_author() ?>"><?php comment_author() ?></a></div>
					<div class="bCommentText">
					<?php comment_text() ?>
					</div>
					<div class="bCommentSmallPrint">
					<?php comment_date() ?> @ <?php comment_time("H:i") ?>
					</div>
				</div>
				<!-- /trackback -->
			<?php break;
			
			case 'pingback': // Display a pingback: ?>
				<!-- pingback -->
				<a name="pb<?php comment_ID() ?>"></a>
				<div class="bComment">
					<div class="bCommentTitle">Pingback from <a href="<?php comment_author_url(); ?>" title="<?php comment_author() ?>"><?php comment_author() ?></a></div>
					<div class="bCommentText">
					<?php comment_text() ?>
					</div>
					<div class="bCommentSmallPrint">
					<?php comment_date() ?> @ <?php comment_time("H:i") ?>
					</div>
				</div>
				<!-- /pingback -->
			<?php break;
			}
		} // end of the loop, don't delete
	} 
	if ($wxcvbn_c == 0) { ?>
	<!-- this is displayed if there are no comments so far -->
	<p>No <?php echo implode( "/", $disp_title); ?> for this post yet...</p>
	<?php /* if you delete this the sky will fall on your head */ } ?>
	
	
	<?php if( $disp_comment_form ) {	// We want to display the comments form: ?>
	<h4>Leave a comment:</h4>
	
	<?php
		$comment_author = trim($_COOKIE[$cookie_name]);
		$comment_author_email = trim($_COOKIE[$cookie_email]);
		$comment_author_url = trim($_COOKIE[$cookie_url]);
	?>
	
	<!-- form to add a comment -->
	<form action="<?php echo $baseurl, '/', $pathhtsrv ?>/comment_post.php" method="post" class="bComment">
	
		<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
		<!-- fp: for some reason this would not work on 'free', using REFFERER instead ... input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" /-->
		
		<fieldset>
			<div class="label"><label for="author">Name:</label></div>
			<div class="input"><input type="text" name="author" id="author" value="<?php echo $comment_author ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		
		<fieldset>
			<div class="label"><label for="email">Email:</label></div>
			<div class="input"><input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" class="bComment" /><br />
				<span class="notes">Your email address will <strong>not</strong> be displayed on this site.</span>
			</div>
		</fieldset>
		
		<fieldset>
			<div class="label"><label for="url">Site/Url:</label></div>
			<div class="input"><input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" class="bComment" /><br />
				<span class="notes">Your URL will be displayed.</span>
			</div>
		</fieldset>
				
		<fieldset>
			<div class="label"><label for="comment">Comment text:</label></div>
			<div class="input"><textarea cols="40" rows="12" name="comment" id="comment" tabindex="4" class="bComment"></textarea><br />
				<span class="notes">Allowed XHTML tags: <?php echo htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?><br />
				URLs, email, AIM and ICQs will be converted automatically.</span>
			</div>
		</fieldset>
				
		<fieldset>
			<div class="label">Options:</div>
			<div class="input">
			
			<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
			<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo " checked=\"checked\"" ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr">Auto-BR</label> <span class="notes">(Line breaks become &lt;br&gt;)</span><br />
			<?php } ?>

			<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies">Remember me</label> <span class="notes">(Set cookies for name, email &amp; url)</span>
			</div>
		</fieldset>
	
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" class="buttonarea" value="Send comment" tabindex="8" />
			</div>
		</fieldset>
	
		<div class="clear"></div>
	
	</form>
	<?php } // end of comment form ?>

<?php } // if you delete this the sky will fall on your head ?>