<?php
	/**
	 * This is the template that displays the links to the last comments for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=comments
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	if( $disp != 'comments' ) 
	{	// We have not asked for comments to be displayed...
		return false;		// Nothing to do here!
	}
	
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );
	
	$CommentList->display_if_empty( '<div class="bComment"><p>'.T_('No comment yet...').'</p></div>' );

	while( $Comment = $CommentList->get_next() )
	{	// Loop through comments:	?>
		<!-- ========== START of a COMMENT ========== -->
		<a name="c<?php $Comment->ID() ?>"></a>
		<div class="bComment">
			<h3 class="bTitle">
				<?php echo T_('In response to:') ?> 
				<a href="<?php $Comment->Item->permalink() ?>"><?php $Comment->Item->title( '', '', false ) ?></a>
			</h3>
			<div class="bCommentTitle">
				<?php $Comment->author() ?>
				<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
			</div>
			<div class="bCommentText">
				<?php $Comment->content() ?>
			</div>
			<div class="bCommentSmallPrint">
				<a href="<?php $Comment->permalink() ?>" title="<?php echo T_('Permanent link to this comment') ?>" class="permalink_right"><img src="<?php imgbase() ?>chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
				<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
				<?php $Comment->edit_link( ' &middot; ' ) // Link to backoffice for editing ?>
			</div>
		</div>
		<!-- ========== END of a COMMENT ========== -->
		<?php 
	}	// End of comment loop.
?>
