<?php
/**
 * This is the template that displays the links to the latest comments for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment', 'trackback', 'pingback' ),
		'statuses' => get_inskin_statuses(),
		'order' => 'DESC',
		'comments' => 50,
		// fp> I don't think it's necessary to add a restriction here. (use case?)
		// 'timestamp_min' => $Blog->get_timestamp_min(),
		// 'timestamp_max' => $Blog->get_timestamp_max(),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

$CommentList->display_if_empty();

echo '<div id="styled_content_block">';
while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	// Load comment's Item object:
	$Comment->get_Item();
	?>
	<!-- ========== START of a COMMENT ========== -->
	<?php $Comment->anchor() ?>
	<div class="bComment">
		<?php
		if( $Comment->status != 'published' )
		{
			$Comment->status( 'styled' );
		}
		$Comment->avatar();
		?>
		<h3 class="bTitle">
			<?php echo T_('In response to:') ?>
			<?php $Comment->Item->title( array(
					'link_type' => 'permalink',
				) ); ?>
		</h3>
		<div class="bCommentTitle">
			<?php $Comment->author(
				/* before: */ '',
				/* after:  */ '#',
				/* before_user: */ '',
				/* after_user:  */ '#',
				/* format: */ 'htmlbody',
				/* makelink: */ true ) ?>
			<?php /* $Comment->author_url( '', ' &middot; ', '' ) */ ?>
		</div>
		<div class="bCommentText">
			<?php
			$Comment->content();
			?>
		</div>
		<div class="bCommentSmallPrint">
			<?php
				$Comment->permanent_link( array(
						'class'    => 'permalink_right',
						'nofollow' => true,
					) );
			?>
			<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
			<?php $Comment->edit_link( ' &middot; ' ) /* Link to backoffice for editing */ ?>
			<?php $Comment->delete_link( ' &middot; ' ); /* Link to backoffice for deleting */ ?>
		</div>
	</div>
	<!-- ========== END of a COMMENT ========== -->
	<?php
}	// End of comment loop.
echo '</div>';


/*
 * $Log$
 * Revision 1.19  2013/11/06 08:05:36  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>