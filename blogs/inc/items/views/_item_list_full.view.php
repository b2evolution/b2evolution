<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
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
global $edit_item_url, $delete_item_url, $htsrv_url, $p, $dummy_fields;
global $comment_allowed_tags;

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

	if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
	{
		$block_item_Widget->global_icon( T_('Create multiple posts...'), 'new', '?ctrl=items&amp;action=new_mass&amp;blog='.$blog, T_('Mass create').' &raquo;', 3, 4 );
		$block_item_Widget->global_icon( T_('Mass edit the current post list...'), '', '?ctrl=items&amp;action=mass_edit&amp;filter=restore&amp;blog='.$blog.'&amp;redirect_to='.regenerate_url( 'action', '', '', '&'), T_('Mass edit').' &raquo;', 3, 4 );
		$block_item_Widget->global_icon( T_('Write a new post...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$blog, T_('New post').' &raquo;', 3, 4 );
	}
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
					'footer_end' => '',
					'disp_rating_summary'  => true,
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
						'footer_text' => '<div class="NavBar center"><strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$<br />$page_size$</div>',
						'footer_text_single' => '<div class="NavBar center">$page_size$</div>',
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
				echo ' ', T_('by'), ' ', $Item->creator_User->get_identity_link( array( 'link_text' => 'text' ) );

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
				echo '</span>';
				$Item->permanent_link( array(
						'before' => '<br />',
						'text'   => '#text#'
					) );
				// Item slug control:
				$Item->tinyurl_link( array(
						'before' => ' - '.T_('Short').': ',
						'after'  => ''
					) );
				global $admin_url;
				if( $current_User->check_perm( 'slugs', 'view' ) )
				{ // user has permission to view slugs:
					echo '&nbsp;'.action_icon( T_('Edit slugs...'), 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$Item->ID,
						NULL, NULL, NULL, array( 'class' => 'small' ) );
				}
				echo '</div>';

				echo '<br />';

				$Item->categories( array(
					'before'          => T_('Categories').': <span class="bCategories">',
					'after'           => '</span>',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => false,
					'show_locked'     => true,
				) );
			?>
		</div>

		<div class="bContent">
			<?php $Item->status( array( 'format' => 'styled' ) ); ?>
			<!-- TODO: Tblue> Do not display link if item does not get displayed in the frontend (e. g. not published). -->
			<h3 class="bTitle"><?php $Item->title( array( 'target_blog' => '' )) ?></h3>

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
					// Uncomment this if you want to count views in backoffice:
					/*
					$Item->count_view( array(
							'allow_multiple_counts_per_page' => false,
						) );
					*/

					// Display CONTENT:
					$Item->content_teaser( array(
							'before'              => '',
							'after'               => '',
							'before_image'        => '<div class="image_block">',
							'before_image_legend' => '<div class="image_legend">',
							'after_image_legend'  =>  '</div>',
							'after_image'         => '</div>',
							'image_size'          => 'fit-320x320',
						) );
					$Item->more_link();
					$Item->content_extension( array(
							'before'      => '',
							'after'       => '',
						) );

					// Links to post pages (for multipage posts):
					$Item->page_links( array(
							'separator' => ' &middot; ',
						) );
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

			echo '<span class="roundbutton_group">';
			if( $action != 'view' )
			{
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'" class="roundbutton_text">'.get_icon( 'magnifier' ).T_('View').'</a>';
			}

			if( isset($GLOBALS['files_Module']) && $current_User->check_perm( 'files', 'view' ) )
			{
				echo '<a href="'.url_add_param( $Blog->get_filemanager_link(), 'fm_mode=link_object&amp;link_type=item&amp;link_object_ID='.$Item->ID )
							.'" class="roundbutton_text">'.get_icon( 'folder' ).T_('Files').'</a>';
			}

			if( $Blog->get_setting( 'allow_comments' ) != 'never' )
			{
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'#comments" class="roundbutton_text">';
				// TRANS: Link to comments for current post
				$comments_number = generic_ctp_number( $Item->ID, 'comments', 'total' );
				echo get_icon( $comments_number > 0 ? 'comments' : 'nocomment' );
				comments_number( T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
				load_funcs('comments/_trackback.funcs.php'); // TODO: use newer call below
				trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
				echo '</a>';
			}
			echo '</span>';

			echo '<span class="roundbutton_group">';
			// Display edit button if current user has the rights:
			$Item->edit_link( array( // Link to backoffice for editing
					'before' => ' ',
					'after'  => '',
					'class'  => 'roundbutton_text'
				) );

			// Display copy button if current user has the rights:
			$Item->copy_link( array( // Link to backoffice for coping
					'before' => '',
					'after'  => ' ',
					'text'   => '#icon#',
					'class'  => 'roundbutton'
				) );
			echo '</span>';

			echo '<span class="roundbutton_group">';
			// Display publish NOW button if current user has the rights:
			$Item->publish_link( ' ', '', '#', '#', 'roundbutton_text' );

			// Display deprecate button if current user has the rights:
			$Item->deprecate_link( '', '', '#', '#', 'roundbutton_text' );

			// Display delete button if current user has the rights:
			$Item->delete_link( '', ' ', '#', '#', 'roundbutton_text', false );
			echo '</span>';

			?>

			<div class="clear"></div>
		</div>

		<?php

		// _____________________________________ Displayed in SINGLE VIEW mode only _____________________________________

		if( $action == 'view' )
		{ // We are looking at a single post, include files and comments:

			if( isset($GLOBALS['files_Module']) )
			{	// Files:
				echo '<div class="bFeedback">';	// TODO
	
				/**
				 * Needed by file display funcs
				 * @var Item
				 */
				global $LinkOwner;
				$LinkOwner = new LinkItem( $Item );
				require $inc_path.'links/views/_link_list.inc.php';
				echo '</div>';
			}


  		// ---------- comments ----------

			$total_comments_number = generic_ctp_number( $Item->ID, 'total', 'total' );
			$draft_comments_number = generic_ctp_number( $Item->ID, 'total', 'draft' );
			// decide to show all comments, or only drafts
			if( $total_comments_number > 5 && $draft_comments_number > 0 )
			{ // show only drafts
				$statuses = array( 'draft' );
				$show_comments = 'draft';
				param( 'comments_number', 'integer', $draft_comments_number );
			}
			else
			{ // show all comments
				$statuses = get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) );
				$show_comments = 'all';
				param( 'comments_number', 'integer', $total_comments_number );
			}

			global $CommentList;
			$CommentList = new CommentList2( $Blog );

			// Filter list:
			$CommentList->set_filters( array(
				'types' => array( 'comment','trackback','pingback' ),
				'statuses' => $statuses,
				'order' => 'ASC',
				'post_ID' => $Item->ID,
				'comments' => 20,
			) );
			$CommentList->query();

			// We do not want to comment actions use new redirect
			param( 'save_context', 'boolean', false );
			param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$Item->ID, '&' ), false, true );
			param( 'item_id', 'integer', $Item->ID );
			param( 'currentpage', 'integer', 1 );
			param( 'show_comments', 'string', $show_comments, false, true );

			// display status filter
			?>
			<div class="bFeedback">
			<a id="comments"></a>
			<h4>
			<?php 
				echo T_('Comments'), ', ', T_('Trackbacks'), ', ', T_('Pingbacks').' ('.generic_ctp_number( $Item->ID, 'feedbacks', 'total' ).')';
				$opentrash_link = get_opentrash_link();
				$refresh_link = '<span class="floatright">'.action_icon( T_('Refresh comment list'), 'refresh', 'javascript:startRefreshComments('.$Item->ID.')' ).'</span> ';
				echo $refresh_link.$opentrash_link;
			?>:</h4>
			<?php

			if( $display_params['disp_rating_summary'] )
			{	// Display a ratings summary
				echo '<h3>'.$Item->get_feedback_title( 'comments', '#', '#', '#', 'total' ).'</h3>';
				echo $Item->get_rating_summary();
				echo '<br />';
			}

			?>
			<div class="tile"><label><?php echo T_('Show').':' ?></label></div>
			<div class="tile">
				<input type="radio" name="show_comments" value="draft" id="only_draft" class="radio" <?php if( $show_comments == 'draft' ) echo 'checked="checked" '?> />
				<label for="only_draft"><?php echo T_('Drafts') ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="show_comments" value="published" id="only_published" class="radio" <?php if( $show_comments == 'published' ) echo 'checked="checked" '?> />
				<label for="only_published"><?php echo T_('Published') ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="show_comments" value="all" id="show_all" class="radio" <?php if( $show_comments == 'all' ) echo 'checked="checked" '?> />
				<label for="show_all"><?php echo T_('All comments') ?></label>
			</div>
			<?php

			load_funcs( 'comments/model/_comment_js.funcs.php' );

			// comments_container value shows, current Item ID
			echo '<div id="comments_container" value="'.$Item->ID.'">';
			// display comments
			$CommentList->display_if_empty( array(
					'before'    => '<div class="bComment"><p>',
					'after'     => '</p></div>',
					'msg_empty' => T_('No feedback for this post yet...'),
				) );

			require $inc_path.'comments/views/_comment_list.inc.php';
			echo '</div>'; // comments_container div

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
						<strong><?php echo $current_User->get_identity_link( array( 'link_text' => 'text' ) )?></strong>
						<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
						</div>
				</fieldset>
			<?php
			$Form->textarea( $dummy_fields[ 'content' ], '', 12, T_('Comment text'), '', 40, 'bComment' );

			global $Plugins;
			$Form->info( T_('Text Renderers'), $Plugins->get_renderer_checkboxes( array( 'default') , array( 'Blog' => & $Blog, 'setting_name' => 'coll_apply_comment_rendering' ) ) );

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

echo_show_comments_changed();

// Display navigation:
$ItemList->display_nav( 'footer' );


$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.57  2013/11/06 08:04:24  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>