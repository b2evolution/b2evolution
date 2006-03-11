<?php
/**
 * This file implements the comment browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
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

/*
 * Display comments:
 */
?>
<div class="bFeedback">
<h2><?php echo T_('Feedback (Comments, Trackbacks...)') ?></h2>
<?php

$CommentList->display_if_empty(
							'<div class="bComment"><p>' .
							T_('No feedback yet...') .
							'</p></div>' );

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	?>
	<!-- ========== START of a COMMENT/TB/PB ========== -->
	<div class="bComment">
		<div class="bSmallHead">
			<strong><?php $Comment->date(); ?></strong>
			@
			<strong><?php $Comment->time( 'H:i' ); ?></strong>
			<?php
			if( $Comment->author_url( '', ' &middot; Url: ', '' )
					&& $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{ // There is an URL and we have permission to ban...
				// TODO: really ban the base domain! - not by keyword
				?>
				<a href="?ctrl=antispam&amp;action=ban&amp;keyword=<?php
					echo rawurlencode(getBaseDomain($Comment->author_url))
					?>"><img src="img/noicon.gif" class="middle" alt="<?php echo /* TRANS: Abbrev. */ T_('Ban') ?>" title="<?php echo T_('Ban this domain!') ?>" /></a>&nbsp;
				<?php
			}
			$Comment->author_email( '', ' &middot; Email: ' );
			$Comment->author_ip( ' &middot; IP: ' );
			echo ' &middot; ';
			$Comment->spam_karma( T_('Karma').': %s%', T_('No karma') );
		 ?>
		</div>
		<div class="bCommentContent">
		<div class="bTitle">
			<?php echo T_('In response to:').' <a href=?ctrl=browse&amp;blog='.$Blog->ID.'&amp;tab=exp&amp;p='.$Comment->Item->ID
								.'&amp;c=1&amp;tb=1&amp;pb=1" class="" title="'.T_('Edit this task...').'">'.$Comment->Item->dget('title').'</a>';
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

			// Display delete button if current user has the rights:
			$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton');
		?>
		</div>

	</div>
	<!-- ========== END of a COMMENT/TB/PB ========== -->
	<?php //end of the loop, don't delete
}
?>
</div>

<?php
/*
 * $Log$
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