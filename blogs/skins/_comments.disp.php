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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$CommentList = & new CommentList( $Blog, "'comment','trackback','pingback'", array('published'), '',	'',	'DESC',	'',	20 );

$CommentList->display_if_empty();

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	// Load comment's Item object:
	$Comment->get_Item();
	?>
	<!-- ========== START of a COMMENT ========== -->
	<?php $Comment->anchor() ?>
	<div class="bComment">
		<h3 class="bTitle">
			<?php echo T_('In response to:') ?>
			<?php $Comment->Item->title( array(
					'link_type' => 'permalink',
				) ); ?>
		</h3>
		<div class="bCommentTitle">
			<?php $Comment->author() ?>
			<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
		</div>
		<div class="bCommentText">
			<?php $Comment->content() ?>
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


/*
 * $Log$
 * Revision 1.4  2009/03/08 23:57:53  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/12/18 23:51:33  fplanque
 * nofollow handling in comment urls
 *
 * Revision 1.1  2007/11/29 19:29:22  fplanque
 * normalized skin filenames
 *
 * Revision 1.35  2007/11/03 21:04:28  fplanque
 * skin cleanup
 *
 * Revision 1.34  2007/11/03 04:56:04  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.33  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.32  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.31  2007/01/25 13:41:51  fplanque
 * wording
 *
 * Revision 1.30  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.29  2006/12/14 21:56:25  fplanque
 * minor optimization
 *
 * Revision 1.28  2006/11/20 22:15:50  blueyed
 * whitespace/comment format
 *
 * Revision 1.27  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.26  2006/05/30 20:32:57  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.25  2006/04/19 13:05:22  fplanque
 * minor
 *
 * Revision 1.24  2006/04/18 19:29:52  fplanque
 * basic comment status implementation
 *
 * Revision 1.23  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>