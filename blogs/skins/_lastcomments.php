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
	
	$CommentList->display_if_empty( '<div class="bComment"><p>'.T_('No comment yet...').'</p></div>' );

	while( $Comment = $CommentList->get_next() )
	{	// Loop through comments:	?>
		<!-- ---------- START of a COMMENT ---------- -->
		<a name="c<?php $Comment->ID() ?>"></a>
		<div class="bComment">
			<h3 class="bTitle">
				<?php echo T_('In response to:') ?> 
				<a href="<?php $Comment->post_link() ?>" title="<?php echo T_('Original post on:') ?> <?php  $Comment->disp( 'blog_name', 'htmlattr' ) ?>"><?php $Comment->post_title() ?></a>
			</h3>
			<div class="bCommentTitle">
				<?php $Comment->author() ?>
				<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
			</div>
			<div class="bCommentText">
				<?php $Comment->content() ?>
			</div>
			<div class="bCommentSmallPrint">
				<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
			</div>
		</div>
		<!-- ---------- END of a COMMENT ---------- -->
		<?php 
	}	// End of comment loop.
?>
