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
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment', 'trackback', 'pingback' ),
		'statuses' => array ( 'published' ),
		'order' => 'DESC',
		'comments' => 20,
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

$CommentList->display_if_empty();

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	// Load comment's Item object:
	$Comment->get_Item();
	?>
	<!-- ========== START of a COMMENT ========== -->
	<?php $Comment->anchor() ?>
	<div class="bComment">
		<?php
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


/*
 * $Log$
 * Revision 1.11  2010/03/11 10:35:13  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.10  2010/02/08 17:56:10  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.9  2010/01/30 18:55:37  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.8  2009/09/29 00:19:12  blueyed
 * Link author of comments by default, instead of displaying the URL separately. Document template function call. Leave old call to author_url commented.
 *
 * Revision 1.7  2009/09/28 21:19:39  blueyed
 * Comments list: display avatar 'on top', which is especially useful when letting it float to the right.
 *
 * Revision 1.6  2009/09/16 22:03:41  fplanque
 * doc
 *
 * Revision 1.5  2009/09/16 21:29:31  sam2kb
 * Display user/visitor avatar in comments
 *
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
