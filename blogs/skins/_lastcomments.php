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
	
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses );
	
	if( $CommentList->get_num_rows() == 0 )
	{	// No comment has been found:
		echo '<p>', T_('No comment yet...'), '</p>';
	}
	else 
	{	// Loop through comments:
		while($Comment = $CommentList->get_next() )
		{	// ---------------------------- START OF COMMENTS -------------------------------
			?>
	
			<a name="c<?php $Comment->disp('ID', 'raw') ?>"></a>
			<div class="bComment">
				<h3 class="bTitle"><?php echo T_('In response to:') ?> <a href="<?php $Comment->disp('post_link', 'raw') ?>" title="<?php printf( T_('Original post on %s'), $Comment->get('blog_name') ); ?>"><?php $Comment->disp('post_title') ?></a></h3>
				<div class="bCommentTitle"><?php $Comment->disp('author') ?> <?php $Comment->author_url_link( '', ' &middot; ', '' ) ?></div>
				<div class="bCommentText">
					<?php $Comment->text() ?>
				</div>
				<div class="bCommentSmallPrint">
					<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
				</div>
			</div>
	
			<?php 
		} // ---------------------------- END OF COMMENTS -------------------------------
	}
?>
