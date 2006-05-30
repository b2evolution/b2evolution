<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	?>
	<!-- ========== START of a COMMENT/TB/PB ========== -->
	<div class="bComment bComment<?php $Comment->status('raw') ?>">
		<div class="bSmallHead">
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
			if( $Comment->author_url( '', ' &middot; Url: <span class="bUrl">', '</span>' )
					&& $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{ // There is an URL and we have permission to ban...
				// TODO: really ban the base domain! - not by keyword
				echo ' <a href="'.$dispatcher.'?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode(get_ban_domain($Comment->author_url))
					.'">'.get_icon( 'ban' ).'</a> ';
			}
			$Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
			$Comment->author_ip( ' &middot; IP: <span class="bIP">', '</span>' );
			echo ' &middot; <span class="bKarma">';
			$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
			echo '</span>';
		 ?>
		</div>
		<div class="bCommentContent">
		<div class="bTitle">
			<?php
			$comment_Item = & $Comment->get_Item();
			echo T_('In response to:')
				.' <a href=?ctrl=browse&amp;blog='.$Blog->ID.'&amp;tab=posts&amp;p='.$comment_Item->ID
				.'&amp;c=1&amp;tb=1&amp;pb=1" class="" title="'.T_('Edit this task...').'">'.$comment_Item->dget('title').'</a>';
			?>
		</div>
		<div class="bCommentTitle">
		<?php
			switch( $Comment->get( 'type' ) )
			{
				case 'comment': // Display a comment:
					echo T_('Comment from:') ?>
					<?php break;

				case 'trackback': // Display a trackback:
					echo T_('Trackback from:') ?>
					<?php break;

				case 'pingback': // Display a pingback:
					echo T_('Pingback from:') ?>
					<?php break;
			}
		?>
		<?php $Comment->author() ?>
		</div>
		<div class="bCommentText">
			<?php $Comment->content() ?>
		</div>
		</div>
		<div class="CommentActionsArea">
		<?php
			$Comment->permanent_link( '#', '#', 'permalink_right' );

			// Display edit button if current user has the rights:
			$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton');

			// Display publish NOW button if current user has the rights:
			$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', true );

			// Display deprecate button if current user has the rights:
			$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', true );

			// Display delete button if current user has the rights:
			$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton');
		?>
		</div>

	</div>
	<!-- ========== END of a COMMENT/TB/PB ========== -->
	<?php //end of the loop, don't delete
}

/*
 * $Log$
 * Revision 1.7  2006/05/30 20:32:57  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.6  2006/04/27 20:10:34  fplanque
 * changed banning of domains. Suggest a prefix by default.
 *
 * Revision 1.5  2006/04/22 16:30:02  blueyed
 * cleanup
 *
 * Revision 1.4  2006/04/19 20:06:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.3  2006/04/19 17:26:58  blueyed
 * Prefix "ban" basedomain with "://"
 *
 * Revision 1.2  2006/04/18 20:17:26  fplanque
 * fast comment status switching
 *
 * Revision 1.1  2006/04/18 19:35:58  fplanque
 * basic comment status implementation
 *
 * Revision 1.10  2006/04/06 19:45:12  blueyed
 * whitespace
 *
 * Revision 1.9  2006/03/23 21:02:12  fplanque
 * cleanup
 *
 * Revision 1.7  2006/03/15 00:58:55  blueyed
 * cosmetics
 *
 * Revision 1.6  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.5  2006/03/12 03:18:01  blueyed
 * Fixed "ban" icon.
 *
 * Revision 1.4  2006/03/11 21:50:16  blueyed
 * Display spam_karma with comments
 *
 * Revision 1.3  2006/03/09 21:58:52  fplanque
 * cleaned up permalinks
 *
 * Revision 1.2  2006/03/08 19:53:16  fplanque
 * fixed quite a few broken things...
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.2  2006/01/09 17:21:06  fplanque
 * no message
 *
 * Revision 1.1  2005/12/19 19:29:46  fplanque
 * added browse latest comments (to be enhanced...)
 * Feel free to add status management / moderation
 *
 */
?>