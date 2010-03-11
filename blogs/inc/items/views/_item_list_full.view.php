<?php
/**
 * This file implements the post browsing
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

global $action, $dispatcher, $blog, $posts, $poststart, $postend, $ReqURI;
global $edit_item_url, $delete_item_url, $htsrv_url, $p;
global $comment_allowed_tags, $comments_use_autobr;

if( $highlight = param( 'highlight', 'integer', NULL ) )
{	// There are lines we want to highlight:
	global $rsc_url;
	echo '<script type="text/javascript" src="'.$rsc_url.'js/fadeout.js"></script>';
	echo '<script type="text/javascript">addEvent( window, "load", Fat.fade_all, false);</script>';
}


// Run the query:
$ItemList->query();

// Old style globals for category.funcs:
global $postIDlist;
$postIDlist = $ItemList->get_page_ID_list();
global $postIDarray;
$postIDarray = $ItemList->get_page_ID_array();



$block_item_Widget = new Widget( 'block_item' );

if( $action == 'view' )
{	// We are displaying a single post:
	$block_item_Widget->title = $ItemList->get_filter_title( '', '', ' - ', NULL, 'htmlbody' );
	$block_item_Widget->global_icon( T_('Close post'), 'close',
				regenerate_url( 'p,action', 'filter=restore&amp;highlight='.$p ), T_('close'), 4, 1 );
}
else
{	// We are displaying multiple posts
	$block_item_Widget->title = T_('Full posts');
	if( $ItemList->is_filtered() )
	{	// List is filtered, offer option to reset filters:
		$block_item_Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3 );
	}

	$block_item_Widget->global_icon( T_('Create multiple posts...'), 'new', '?ctrl=items&amp;action=new_mass&amp;blog='.$blog, T_('Mass create').' &raquo;', 3, 4 );
	$block_item_Widget->global_icon( T_('Write a new post...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$blog, T_('New post').' &raquo;', 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );



if( $action == 'view' )
{
	// Initialize things in order to be ready for displaying.
	$display_params = array(
					'header_start' => '',
						'header_text_single' => '',
					'header_end' => '',
					'footer_start' => '',
						'footer_text_single' => '',
					'footer_end' => ''
				);
}
else
{ // Not a single post!
	// Display title depending on selection params:
	echo $ItemList->get_filter_title( '<h3>', '</h3>', '<br />', NULL, 'htmlbody' );

	// Initialize things in order to be ready for displaying.
	$display_params = array(
					'header_start' => '<div class="NavBar center">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => T_('1 page'),
					'header_end' => '</div>',
					'footer_start' => '',
						'footer_text' => '<div class="NavBar center"><strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$</div>',
						'footer_text_single' => '',
							'prev_text' => T_('Previous'),
							'next_text' => T_('Next'),
							'list_prev_text' => T_('...'),
							'list_next_text' => T_('...'),
							'list_span' => 11,
							'scroll_list_range' => 5,
					'footer_end' => ''
				);
}

$ItemList->display_init( $display_params );

// Display navigation:
$ItemList->display_nav( 'header' );

/*
 * Display posts:
 */
while( $Item = & $ItemList->get_item() )
{
	?>
	<div id="<?php $Item->anchor_id() ?>" class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
		// We don't switch locales in the backoffice, since we use the user pref anyway
		// Load item's creator user:
		$Item->get_creator_User();
		?>
		<div class="bSmallHead <?php
		if( $Item->ID == $highlight )
		{
			echo 'fadeout-ffff00" id="fadeout-1';
		}
	 	?>">
			<?php
				echo '<div class="bSmallHeadRight">';
				If( !empty( $Item->order ) )
				{
					echo T_('Order').': '.$Item->order;
				}
				$Item->locale_flag(array('class'=>'flagtop'));
				echo '</div>';

				$Item->issue_date( array(
						'before'      => '<span class="bDate">',
						'after'       => '</span>',
						'date_format' => '#',
					) );

				$Item->issue_time( array(
						'before'      => ' @ <span class="bTime">',
						'after'      => '</span>',
					) );

				// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
				echo ' ', T_('by'), ' <acronym title="';
				$Item->creator_User->login();
				echo ', '.T_('level:');
				$Item->creator_User->level();
				echo '"><span class="bAuthor">';
				$Item->creator_User->preferred_name();
				echo '</span></acronym>';

				echo '<div class="bSmallHeadRight">';
				$Item->status( array(
						'before' => T_('Visibility').': <span class="bStatus">',
						'after'  => '</span>',
					) );
				echo '</div>';

				echo '<br />';
				$Item->type( T_('Type').': <span class="bType">', '</span> &nbsp; ' );

				if( $Blog->get_setting( 'use_workflow' ) )
				{ // Only display workflow properties, if activated for this blog.
					$Item->priority( T_('Priority').': <span class="bPriority">', '</span> &nbsp; ' );
					$Item->assigned_to( T_('Assigned to').': <span class="bAssignee">', '</span> &nbsp; ' );
					$Item->extra_status( T_('Task Status').': <span class="bExtStatus">', '</span>' );
				}
				echo '&nbsp;';

				echo '<div class="bSmallHeadRight"><span class="bViews">';
				$Item->views();
				echo '</span></div>';

				echo '<br />';

				$Item->categories( array(
					'before'          => T_('Categories').': <span class="bCategories">',
					'after'           => '</span>',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => false,
				) );
			?>
		</div>

		<div class="bContent">
			<!-- TODO: Tblue> Do not display link if item does not get displayed in the frontend (e. g. not published). -->
			<h3 class="bTitle"><?php $Item->title() ?></h3>

			<?php
				// Display images that are linked to this post:
				$Item->images( array(
						'before' =>              '<div class="bImages">',
						'before_image' =>        '<div class="image_block">',
						'before_image_legend' => '<div class="image_legend">',
						'after_image_legend' =>  '</div>',
						'after_image' =>         '</div>',
						'after' =>               '</div>',
						'image_size' =>          'fit-320x320',
						'restrict_to_image_position' => 'teaser',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
					) );
			?>

			<div class="bText">
				<?php
					// Uncomment this in case you wnt to count view in backoffice:
					/*
					$Item->count_view( array(
							'allow_multiple_counts_per_page' => false,
						) );
					*/

					// Display CONTENT:
					$Item->content_teaser( array(
							'before'      => '',
							'after'       => '',
						) );
					$Item->more_link();
					$Item->content_extension( array(
							'before'      => '',
							'after'       => '',
						) );

					// Links to post pages (for multipage posts):
					$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
				?>
			</div>

		</div>

		<?php
			// List all tags attached to this post:
			$Item->tags( array(
					'url' =>            regenerate_url( 'tag' ),
					'before' =>         '<div class="bSmallPrint">'.T_('Tags').': ',
					'after' =>          '</div>',
					'separator' =>      ', ',
				) );
		?>

		<div class="PostActionsArea">
			<?php
			$Item->permanent_link( array(
					'class' => 'permalink_right',
				) );

			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'" class="ActionButton">'.T_('View...').'</a>';

			// Display edit button if current user has the rights:
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton'
				) );

			echo '<a href="'.url_add_param( $Blog->get_filemanager_link(), 'fm_mode=link_item&amp;item_ID='.$Item->ID )
							.'" class="ActionButton">'.get_icon( 'folder', 'imgtag' ).' '.T_('Files...').'</a>';

			// Display publish NOW button if current user has the rights:
			$Item->publish_link( ' ', ' ', '#', '#', 'PublishButton');

			// Display deprecate button if current user has the rights:
			$Item->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton');

			// Display delete button if current user has the rights:
			$Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false );

			if( $Blog->allowcomments != 'never' )
			{
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'#comments" class="ActionButton">';
				// TRANS: Link to comments for current post
				comments_number(T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
				load_funcs('comments/_trackback.funcs.php'); // TODO: use newer call below
				trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
				echo '</a>';
			} ?>

   		<div class="clear"></div>
		</div>

		<?php

		// _____________________________________ Displayed in SINGLE VIEW mode only _____________________________________

		if( $action == 'view' )
		{ // We are looking at a single post, include files and comments:

			// Files:
			echo '<div class="bFeedback">';	// TODO

			/**
			 * Needed by file display funcs
			 * @var Item
			 */
			global $edited_Item;
			$edited_Item = $Item;	// COPY or it will be out of scope for display funcs
			require dirname(__FILE__).'/inc/_item_links.inc.php';
			echo '</div>';



  		// ---------- comments ----------
			?>
			<div class="bFeedback">
			<a id="comments"></a>
			<h4><?php echo T_('Comments'), ', ', T_('Trackbacks'), ', ', T_('Pingbacks') ?>:</h4>
			<?php
			global $CommentList;

			$CommentList = new CommentList2( $Blog );
	
			// Filter list:
			$CommentList->set_filters( array(
				'types' => array( 'comment','trackback','pingback' ),
				'order' => 'ASC',
				'post_ID' => $Item->ID,
			) );

			// Get ready for display (runs the query):
			$CommentList->display_init();

			$CommentList->display_if_empty( array(
					'before'    => '<div class="bComment"><p>',
					'after'     => '</p></div>',
					'msg_empty' => T_('No feedback for this post yet...'),
				) );

			// Display list of comments:
			require $inc_path.'comments/views/_comment_list.inc.php';

			if( $Item->can_comment() )
			{ // User can leave a comment
			?>
			<!-- ========== FORM to add a comment ========== -->
			<h4><?php echo T_('Leave a comment') ?>:</h4>

			<?php

			$Form = new Form( $htsrv_url.'comment_post.php', 'comment_checkchanges' );

			$Form->begin_form( 'bComment' );

			$Form->add_crumb( 'comment' );
			$Form->hidden( 'comment_post_ID', $Item->ID );
			$Form->hidden( 'redirect_to', $ReqURI );
			?>
				<fieldset>
					<div class="label"><?php echo T_('User') ?>:</div>
					<div class="info">
						<strong><?php $current_User->preferred_name()?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
						</div>
				</fieldset>
			<?php
			$Form->textarea( 'p', '', 12, T_('Comment text'), '', 40, 'bComment' );

			if(substr($comments_use_autobr,0,4) == 'opt-')
			{
				$Form->checkbox( 'comment_autobr', 1, T_('Auto-BR'), T_('(Line breaks become &lt;br&gt;)'), 'checkbox' );
			}

			$Form->buttons_input( array(array('name'=>'submit', 'value'=>T_('Send comment'), 'class'=>'SaveButton' )) );
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


$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.31  2010/03/11 10:35:05  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.30  2010/02/08 17:53:19  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.29  2010/01/30 18:55:31  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.28  2010/01/19 21:10:28  efy-yury
 * update: crumbs
 *
 * Revision 1.27  2010/01/17 16:15:22  sam2kb
 * Localization clean-up
 *
 * Revision 1.26  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.25  2009/11/11 03:24:52  fplanque
 * misc/cleanup
 *
 * Revision 1.24  2009/10/18 11:29:43  efy-maxim
 * 1. mass create in 'All' tab; 2. "Text Renderers" and "Comments"
 *
 * Revision 1.23  2009/10/11 03:00:11  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.22  2009/09/19 13:59:45  tblue246
 * Doc
 *
 * Revision 1.21  2009/05/28 20:21:27  blueyed
 * Streamline code to display form buttons.
 *
 * Revision 1.20  2009/04/28 19:19:33  blueyed
 * Full item list: do not display workflow properties, if not activated for this blog.
 *
 * Revision 1.19  2009/04/28 19:10:06  blueyed
 * trans fix, simplification. might want to use %s here?
 *
 * Revision 1.18  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.17  2009/01/27 22:54:01  fplanque
 * commenting cleanup
 *
 * Revision 1.16  2009/01/21 22:26:26  fplanque
 * Added tabs to post browsing admin screen All/Posts/Pages/Intros/Podcasts/Comments
 *
 * Revision 1.15  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.14  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.13  2008/01/23 17:55:01  fplanque
 * fix
 *
 * Revision 1.12  2008/01/23 12:51:20  fplanque
 * posts now have divs with IDs
 *
 * Revision 1.11  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.10  2007/11/24 21:24:14  fplanque
 * display tags in backoffice
 *
 * Revision 1.9  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.8  2007/11/03 23:54:39  fplanque
 * skin cleanup continued
 *
 * Revision 1.7  2007/11/03 21:04:27  fplanque
 * skin cleanup
 *
 * Revision 1.6  2007/11/03 04:56:03  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.5  2007/09/26 20:26:36  fplanque
 * improved ItemList filters
 *
 * Revision 1.4  2007/09/08 20:23:04  fplanque
 * action icons / wording
 *
 * Revision 1.3  2007/09/07 21:11:10  fplanque
 * superstylin' (not even close)
 *
 * Revision 1.2  2007/09/03 19:36:06  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 11:00:30  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.36  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.35  2007/03/21 02:21:37  fplanque
 * item controller: highlight current (step 2)
 *
 * Revision 1.34  2007/03/21 01:44:51  fplanque
 * item controller: better return to current filterset - step 1
 *
 * Revision 1.33  2007/03/06 12:18:09  fplanque
 * got rid of dirty Item::content()
 * Advantage: the more link is now independant. it can be put werever people want it
 *
 * Revision 1.32  2007/03/05 02:12:56  fplanque
 * minor
 *
 * Revision 1.31  2007/01/19 10:57:46  fplanque
 * UI
 *
 * Revision 1.30  2007/01/19 10:45:42  fplanque
 * images everywhere :D
 * At this point the photoblogging code can be considered operational.
 *
 * Revision 1.29  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.28  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.27  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.26  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.25  2006/12/07 22:29:26  fplanque
 * reorganized menus / basic dashboard
 *
 * Revision 1.24  2006/12/04 18:16:51  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.23  2006/11/27 19:14:14  fplanque
 * i18n
 *
 * Revision 1.22  2006/10/23 22:19:03  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 */
?>