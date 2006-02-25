<?php
/**
 * This file implements the post browsing
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
 * @global Blog
 */
global $Blog;
/**
 * @global ItemList2
 */
global $ItemList;
/**
 * Note: definition only (does not need to be a global)
 * @global Item
 */
global $Item;

/**
 * @global string
 */
global $add_item_url;


// Display title depending on selection params:
echo $ItemList->get_filter_title( '<h2>', '</h2>', '<br />', NULL, 'htmlbody' );

// Init display features:
$display_params = array(
					'header_start' => '<div class="NavBar center">',
						'header_text' => '<strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => T_('1 page'),
					'header_end' => '</div>',
					'footer_start' => '',
						'footer_text' => '<div class="NavBar center"><strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$</div>\n\n',
						'footer_text_single' => '',
							'prev_text' => T_('Previous'),
							'next_text' => T_('Next'),
							'list_prev_text' => T_('...'),
							'list_next_text' => T_('...'),
							'list_span' => 11,
							'scroll_list_range' => 5,
					'footer_end' => "",
				);
$ItemList->display_init( $display_params );

// Display navigation:
$ItemList->display_nav( 'header' );

/*
 * Display posts:
 */
while( $Item = & $ItemList->get_item() )
{
	?>
	<div class="bPost bPost<?php $Item->status( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
		<?php
		// We don't switch locales in the backoffice, since we use the user pref anyway
		$Item->anchor(); ?>
		<div class="bSmallHead">
			<?php
				echo '<div class="bSmallHeadRight">';
				locale_flag( $Item->locale, 'h10px' );
				echo '</div>';

				echo '<strong>';
				$Item->issue_date();
 				echo '</strong> @ <strong>';
 				$Item->issue_time( 'H:i' );
 				echo '</strong>';
				// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
				echo ' ', T_('by'), ' <acronym title="';
				$Item->Author->login();
				echo ', '.T_('level:');
				$Item->Author->level();
				echo '"><strong>';
				$Item->Author->preferred_name();
				echo '</strong></acronym>';

				echo '<div class="bSmallHeadRight">';
				echo T_('Visibility').': ';
				echo '<span class="Status">';
				$Item->status();
				echo '</span>';
				echo '</div>';

				echo '<br />';
				$Item->type( T_('Type').': <strong>', '</strong> &nbsp; ' );
				$Item->priority( T_('Priority').': <strong>', '</strong> &nbsp; ' );
				$Item->assigned_to( T_('Assigned to:').' <strong>', '</strong> &nbsp; ' );
				$Item->extra_status( T_('Task Status').': <strong>', '</strong>' );

				echo '<div class="bSmallHeadRight">';
				$Item->views();
				echo '</div>';

				echo '<br />'.T_('Categories').': ';
				$Item->categories( false );
			?>
		</div>

		<div class="bContent">
			<h3 class="bTitle"><?php $Item->title() ?></h3>
			<div class="bText">
				<?php
					$Item->content();
					link_pages( '<p class="right">'.T_('Pages:'), '</p>' );
				?>
			</div>
		</div>

		<div class="PostActionsArea">
			<?php
			echo '<a href="';
			$Item->permalink();
			echo '" title="'.T_('Permanent link to full entry').'" class="permalink_right">'.get_icon( 'permalink' ).'</a>';

			// Display edit button if current user has the rights:
			$Item->edit_link( ' ', ' ', '#', '#', 'ActionButton' );

			// Display publish NOW button if current user has the rights:
			$Item->publish_link( ' ', ' ', '#', '#', 'PublishButton');

			// Display deprecate button if current user has the rights:
			$Item->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton');

			// Display delete button if current user has the rights:
			$Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false );

			if( $Blog->allowcomments != 'never' )
			{
 				echo '<a href="?ctrl=browse&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'&amp;c=1" class="ActionButton">';
				// TRANS: Link to comments for current post
				comments_number(T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
				trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
				pingback_number('', ' &middot; '.T_('1 Pingback'), ' &middot; '.T_('%d Pingbacks'), $Item->ID);
				echo '</a>';
			} ?>
		</div>

		<?php
		// ---------- comments ----------
		global $c;
		if( $c )
		{ // We have request display of comments
			?>
 			<div class="bFeedback">
			<a name="comments"></a>
			<h4><?php echo T_('Comments'), ', ', T_('Trackbacks'), ', ', T_('Pingbacks') ?>:</h4>
			<?php

			$CommentList = & new CommentList( 0, "'comment','trackback','pingback'", $show_statuses, $Item->ID, '', 'ASC' );

			$CommentList->display_if_empty(
										'<div class="bComment"><p>' .
										T_('No feedback for this post yet...') .
										'</p></div>' );

			while( $Comment = & $CommentList->get_next() )
			{ // Loop through comments:
				?>
				<!-- ========== START of a COMMENT/TB/PB ========== -->
				<div class="bComment">
					<div class="bSmallHead">
						<?php
						$Comment->date();
						echo ' @ ';
						$Comment->time( 'H:i' );
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
					 ?>
					</div>
					<div class="bCommentContent">
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
					<a href="<?php $Comment->permalink() ?>" title="<?php echo T_('Permanent link to this comment')	?>" class="permalink_right"><img src="img/chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
					<?php
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

			if( $Item->can_comment() )
			{ // User can leave a comment
			?>
			<!-- ========== FORM to add a comment ========== -->
			<h4><?php echo T_('Leave a comment') ?>:</h4>

			<?php

			$Form = & new Form( $htsrv_url.'comment_post.php', 'comment_checkchanges' );

			$Form->begin_form( 'bComment' );

			$Form->hidden( 'comment_post_ID', $Item->ID );
			$Form->hidden( 'redirect_to', htmlspecialchars($ReqURI) );
			?>
				<fieldset>
					<div class="label"><?php echo T_('User') ?>:</div>
					<div class="info">
						<strong><?php $current_User->preferred_name()?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
						</div>
				</fieldset>
			<?php
			$Form->textarea( 'comment', '', 12, T_('Comment text'),
												T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)), 40, 'bComment' );

			if(substr($comments_use_autobr,0,4) == 'opt-')
			{
				echo $Form->fieldstart;
				echo $Form->labelstart;
			?>
			<label><?php echo T_('Options') ?>:</label>

			<?php
				echo $Form->labelend;
				echo $Form->inputstart;
				$Form->checkbox( 'comment_autobr', 1, T_('Auto-BR'), T_('(Line breaks become &lt;br&gt;)'), 'checkbox' );
				echo $Form->inputend;
				$Form->end_fieldset();

			}

				echo $Form->fieldstart;
				echo $Form->inputstart;
				$Form->submit( array ('submit', T_('Send comment'), 'SaveButton' ) );
				echo $Form->inputend;
				$Form->end_fieldset();

			?>

				<div class="clear"></div>
			<?php
				$Form->end_form();
			?>
			<!-- ========== END of FORM to add a comment ========== -->
			<?php
			} // / can comment
		?>
		</div>
		<?php
	} // / comments requested
?>
</div>
<?php
}

// Display navigation:
$ItemList->display_nav( 'footer' );
?>

<p class="center">
  <a href="<?php echo $add_item_url ?>"><img src="img/new.gif" width="13" height="13" class="middle" alt="" />
    <?php echo T_('New post...') ?></a>
</p>

<?php
/*
 * $Log$
 * Revision 1.2  2006/02/25 22:53:11  blueyed
 * fix
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.8  2006/01/25 18:24:21  fplanque
 * hooked bozo validator in several different places
 *
 * Revision 1.7  2006/01/09 17:21:06  fplanque
 * no message
 *
 * Revision 1.6  2005/12/22 15:53:37  fplanque
 * Splitted display and display init
 *
 * Revision 1.5  2005/12/20 18:12:50  fplanque
 * enhanced filtering/titling framework
 *
 * Revision 1.4  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.3  2005/12/19 18:10:18  fplanque
 * Normalized the exp and tracker tabs.
 *
 * Revision 1.2  2005/12/14 17:00:24  blueyed
 * assign return value of get_next() by reference
 *
 * Revision 1.1  2005/12/08 13:13:33  fplanque
 * no message
 *
 */
?>