<?php
	/*
	 * This is the template that displays the links to the last comments for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=comments
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	if( $disp != 'comments' ) 
	{	// We have not asked for comments to be displayed...
		return false;		// Nothing to do here!
	}
	
	$CommentList = new CommentList( $blog, "'comment'", $show_statuses );
	
	if( $CommentList->get_num_rows() == 0 )
	{	// No comment has been found:
	?>
		<p>No comment yet...</p>	
	<?php
	}
	else 
	{	// Loop through comments:
		while($rowc = mysql_fetch_object($CommentList->result))
		{	// ---------------------------- START OF COMMENTS -------------------------------
			$commentdata = get_commentdata($rowc->comment_ID); 
			?>
	
			<a name="c<?php comment_ID() ?>"></a>
			<div class="bComment">
				<h3 class="bTitle">In response to: <a href="<?php comment_post_link() ?>" title="Original post on <?php comment_blog_name() ?>"><?php comment_post_title(); ?></a></h3>
				<div class="bCommentTitle"><?php comment_author() ?> <?php comment_author_url_link("", " &middot; ", "") ?></div>
				<div class="bCommentText">
					<?php comment_text() ?>
				</div>
				<div class="bCommentSmallPrint">
					<?php comment_date() ?> @ <?php comment_time("H:i") ?>
				</div>
			</div>
	
			<?php 
		} // ---------------------------- END OF COMMENTS -------------------------------
	}
?>
