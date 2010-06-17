<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Comment
 */
global $Comment;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var CommentList
 */
global $CommentList;

global $dispatcher;

$redirect_to = rawurlencode( regenerate_url( '', '', '', '&' ) );

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	?>
	<!-- ========== START of a COMMENT/TB/PB ========== -->
	<div id="c<?php echo $Comment->ID ?>" class="bComment bComment<?php $Comment->status('raw') ?>">
		<div class="bSmallHead">
         <div>
			<?php
				echo '<div class="bSmallHeadRight">';
				echo T_('Visibility').': ';
				echo '<span class="bStatus">';
				$Comment->status();
				echo '</span>';
				echo '</div>';
			?>
			<span class="bDate"><?php $Comment->date(); ?></span>
			@
			<span class="bTime"><?php $Comment->time( 'H:i' ); ?></span>
			<?php
			$Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
			echo ' &middot; <span class="bKarma">';
			$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
			echo '</span>';
			?>
         </div>
         <div style="padding-top:3px">
         	<?php
			$Comment->author_ip( 'IP: <span class="bIP">', '</span> &middot; ' );
			$Comment->author_url_with_actions( $redirect_to, false, true );
		 	?>
         </div>
		</div>
		<div class="bCommentContent">
		<div class="bTitle">
			<?php
			$comment_Item = & $Comment->get_Item();
			echo T_('In response to:')
				.' <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$comment_Item->ID.'">'.$comment_Item->dget('title').'</a>';
			?>
		</div>
		<div class="bCommentTitle">
			<?php echo $Comment->get_title(); ?>
		</div>
		<div class="bCommentText">
			<?php $Comment->rating(); ?>
			<?php $Comment->avatar(); ?>
			<?php $Comment->content() ?>
		</div>
		</div>
		<div class="CommentActionsArea">
			<?php
				$Comment->permanent_link( array(
						'class'    => 'permalink_right'
					) );

				// Display edit button if current user has the rights:
				$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton', '&amp;', true, $redirect_to );

				// Display publish NOW button if current user has the rights:
				$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', true );

				// Display deprecate button if current user has the rights:
				$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', true );

				// Display delete button if current user has the rights:
				$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton');
			?>
   		<div class="clear"></div>
		</div>

	</div>
	<!-- ========== END of a COMMENT/TB/PB ========== -->
	<?php //end of the loop, don't delete
}

/*
 * $Log$
 * Revision 1.17  2010/06/17 06:42:44  efy-asimo
 * Fix comment actions redirect on item full page
 *
 * Revision 1.16  2010/05/24 19:04:19  sam2kb
 * Comment header split into 2 lines
 *
 * Revision 1.15  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.14  2010/02/28 23:38:39  fplanque
 * minor changes
 *
 * Revision 1.13  2010/02/26 08:34:33  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.12  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.11  2010/01/31 17:40:04  efy-asimo
 * delete url from comments in dashboard and comments form
 *
 * Revision 1.10  2010/01/22 13:42:22  efy-isaias
 * avatar
 *
 * Revision 1.9  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.8  2008/12/18 00:34:13  blueyed
 * - Add Comment::get_author() and make Comment::author() use it
 * - Add Comment::get_title() and use it in Dashboard and Admin comment list
 *
 * Revision 1.7  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/12/18 23:51:33  fplanque
 * nofollow handling in comment urls
 *
 * Revision 1.5  2007/11/29 21:00:10  fplanque
 * comment ratings shown in BO
 *
 * Revision 1.4  2007/09/07 21:11:10  fplanque
 * superstylin' (not even close)
 *
 * Revision 1.3  2007/09/04 19:51:28  fplanque
 * in-context comment editing
 *
 * Revision 1.2  2007/09/03 18:32:50  fplanque
 * enhanced dashboard / comment moderation
 *
 * Revision 1.1  2007/06/25 10:59:43  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.12  2007/05/20 20:54:49  fplanque
 * better comment moderation links
 *
 * Revision 1.11  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.10  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 */
?>