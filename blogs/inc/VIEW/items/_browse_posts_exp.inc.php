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
 * @var Blog
 */
global $Blog;
/**
 * @var ItemList2
 */
global $ItemList;
/**
 * Note: definition only (does not need to be a global)
 * @var Item
 */
global $Item;

global $dispatcher, $blog, $posts, $posts_per_page, $poststart, $postend, $c, $show_statuses, $ReqURI;
global $add_item_url, $edit_item_url, $delete_item_url, $htsrv_url;
global $comment_allowed_tags, $comments_use_autobr;


// Display title depending on selection params:
echo $ItemList->get_filter_title( '<h2>', '</h2>', '<br />', NULL, 'htmlbody' );

// Init display features:
$display_params = array(
					'header_start' => '<div class="NavBar center">',
						'header_text' => '<strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => T_('1 page'),
					'header_end' => '</div>',
					'footer_start' => '',
						'footer_text' => '<div class="NavBar center"><strong>Pages</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$</div>',
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
		// Load item's creator user:
		$Item->get_creator_User();
		$Item->anchor(); ?>
		<div class="bSmallHead">
			<?php
				echo '<div class="bSmallHeadRight">';
				locale_flag( $Item->locale, 'h10px' );
				echo '</div>';

				echo '<span class="bDate">';
				$Item->issue_date();
				echo '</span> @ <span class="bTime">';
				$Item->issue_time( 'H:i' );
				echo '</span>';
				// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
				echo ' ', T_('by'), ' <acronym title="';
				$Item->creator_User->login();
				echo ', '.T_('level:');
				$Item->creator_User->level();
				echo '"><span class="bAuthor">';
				$Item->creator_User->preferred_name();
				echo '</span></acronym>';

				echo '<div class="bSmallHeadRight">';
				echo T_('Visibility').': ';
				echo '<span class="bStatus">';
				$Item->status();
				echo '</span>';
				echo '</div>';

				echo '<br />';
				$Item->type( T_('Type').': <span class="bType">', '</span> &nbsp; ' );
				$Item->priority( T_('Priority').': <span class="bPriority">', '</span> &nbsp; ' );
				$Item->assigned_to( T_('Assigned to:').' <span class="bAssignee">', '</span> &nbsp; ' );
				$Item->extra_status( T_('Task Status').': <span class="bExtStatus">', '</span>' );

				echo '<div class="bSmallHeadRight"><span class="bViews">';
				$Item->views();
				echo '</span></div>';

				echo '<br />'.T_('Categories').': <span class="bCategories">';
				$Item->categories( false );
				echo '</span>';
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
			$Item->permanent_link( '#', '#', 'permalink_right' );

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
				echo '<a href="?ctrl=browse&amp;tab=posts&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'&amp;c=1" class="ActionButton">';
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
			global $CommentList;

			$CommentList = new CommentList( 0, "'comment','trackback','pingback'", array(), $Item->ID, '', 'ASC' );

			$CommentList->display_if_empty(
										'<div class="bComment"><p>' .
										T_('No feedback for this post yet...') .
										'</p></div>' );

			// Display list of comments:
			require dirname(__FILE__).'/../comments/inc/_comment_list.inc.php';

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
			$Form->textarea( 'p', '', 12, T_('Comment text'),
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
	<?php
	echo action_icon( T_('New post...'), 'new', $add_item_url, T_('New post...'), 3, 4 );
	?>
</p>


<?php
/*
 * $Log$
 * Revision 1.15  2006/06/22 18:37:47  fplanque
 * fixes
 *
 * Revision 1.14  2006/06/19 20:07:22  fplanque
 * minor
 *
 * Revision 1.13  2006/05/30 20:32:57  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.12  2006/04/24 20:36:45  fplanque
 * fixes
 *
 * Revision 1.11  2006/04/18 19:29:52  fplanque
 * basic comment status implementation
 *
 * Revision 1.10  2006/04/14 19:21:55  fplanque
 * icon cleanup + fixes
 *
 * Revision 1.9  2006/03/28 22:23:16  blueyed
 * Display spam karma for comments in posts list
 *
 * Revision 1.8  2006/03/18 18:31:53  blueyed
 * Fixed "new" icon
 *
 * Revision 1.7  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.6  2006/03/12 03:18:01  blueyed
 * Fixed "ban" icon.
 *
 * Revision 1.5  2006/03/09 21:58:53  fplanque
 * cleaned up permalinks
 *
 * Revision 1.4  2006/03/08 19:53:16  fplanque
 * fixed quite a few broken things...
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
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