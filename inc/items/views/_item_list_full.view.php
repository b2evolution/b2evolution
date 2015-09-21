<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
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
global $comment_allowed_tags, $comment_type;

$highlight = param( 'highlight', 'integer', NULL );

// Run the query:
$ItemList->query();

// Old style globals for category.funcs:
global $postIDlist;
$postIDlist = $ItemList->get_page_ID_list();
global $postIDarray;
$postIDarray = $ItemList->get_page_ID_array();


$block_item_Widget = new Widget( 'block_item' );

// This block is used to keep correct css style for the post status banners
echo '<div id="styled_content_block" class="evo_content_block">';

if( $action == 'view' )
{ // We are displaying a single post:
	echo '<div class="global_actions">'
			.action_icon( T_('Close post'), 'close', regenerate_url( 'p,action', 'filter=restore&amp;highlight='.$p ),
				NULL, NULL, NULL, array( 'class' => 'action_icon btn btn-default' ) )
		.'</div>';

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
{ // We are displaying multiple posts ( Not a single post! )
	$block_item_Widget->title = T_('Posts Browser').get_manual_link( 'browse-edit-tab' );
	if( $ItemList->is_filtered() )
	{ // List is filtered, offer option to reset filters:
		$block_item_Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
	}

	// Generate global icons depending on seleted tab with item type
	item_type_global_icons( $block_item_Widget );

	$block_item_Widget->disp_template_replaced( 'block_start' );

	// --------------------------------- START OF CURRENT FILTERS --------------------------------
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_current_filters',
			// Optional display params
			'ItemList'             => $ItemList,
			'block_start'          => '',
			'block_end'            => '',
			'block_title_start'    => '<b>',
			'block_title_end'      => ':</b> ',
			'show_filters'         => array( 'time' => 1 ),
			'display_button_reset' => false,
			'display_empty_filter' => true,
		) );
	// ---------------------------------- END OF CURRENT FILTERS ---------------------------------

	$block_item_Widget->disp_template_replaced( 'block_end' );

	global $AdminUI;
	$admin_template = $AdminUI->get_template( 'Results' );

	// Initialize things in order to be ready for displaying.
	$display_params = array(
			'header_start' => str_replace( 'class="', 'class="NavBar center ', $admin_template['header_start'] ),
			'footer_start' => str_replace( 'class="', 'class="NavBar center ', $admin_template['footer_start'] ),
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
				$Item->permanent_link( array(
						'before' => '',
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
				If( !empty( $Item->order ) )
				{
					echo T_('Order').': '.$Item->order;
				}
				$Item->locale_flag( array(' class' => 'flagtop' ) );
				echo '</div>';

				$Item->issue_date( array(
						'before'      => '<span class="bDate">',
						'after'       => '</span>',
						'date_format' => '#',
					) );

				$Item->issue_time( array(
						'before'      => ' @ <span class="bTime">',
						'after'       => '</span>',
						'time_format' => '#short_time',
					) );

				// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
				echo ' ', T_('by'), ' ', $Item->creator_User->get_identity_link( array( 'link_text' => 'name' ) );

				echo $Item->get_history_link( array( 'before' => ' ' ) );

				echo '<br />';
				$Item->type( T_('Type').': <span class="bType">', '</span> &nbsp; ' );

				if( $Blog->get_setting( 'use_workflow' ) )
				{ // Only display workflow properties, if activated for this blog.
					$Item->priority( T_('Priority').': <span class="bPriority">', '</span> &nbsp; ' );
					$Item->assigned_to( T_('Assigned to').': <span class="bAssignee">', '</span> &nbsp; ' );
					$Item->extra_status( T_('Task Status').': <span class="bExtStatus">', '</span> &nbsp; ' );
					if( ! empty( $Item->datedeadline ) )
					{ // Display deadline date
						echo T_('Deadline').': <span class="bDate">';
						$Item->deadline_date();
						echo '</span>';
					}
				}
				echo '&nbsp;';

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
			<?php
				$Item->format_status( array(
						'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
					) );
			?>
			<!-- TODO: Tblue> Do not display link if item does not get displayed in the frontend (e. g. not published). -->
			<h3 class="bTitle"><?php $Item->title( array( 'target_blog' => '' )) ?></h3>

			<?php
				// Display images that are linked to this post:
				$Item->images( array(
						'before'              => '<div class="evo_post_images">',
						'before_image'        => '<figure class="evo_image_block">',
						'before_image_legend' => '<figcaption class="evo_image_legend">',
						'after_image_legend'  => '</figcaption>',
						'after_image'         => '</figure>',
						'after'               => '</div>',
						'image_size'          => 'fit-320x320',
						// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
						'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink',
					) );
			?>

			<div class="bText">
				<?php
					// Display CONTENT:
					$Item->content_teaser( array(
							'before'              => '',
							'after'               => '',
							'before_image'        => '<figure class="evo_image_block">',
							'before_image_legend' => '<figcaption class="evo_image_legend">',
							'after_image_legend'  => '</figcaption>',
							'after_image'         => '</figure>',
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

			echo '<span class="'.button_class( 'group' ).'">';
			if( $action != 'view' )
			{
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'" class="'.button_class( 'text' ).'">'.get_icon( 'magnifier' ).' '.T_('View').'</a>';
			}

			if( isset( $GLOBALS['files_Module'] )
			    && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item )
			    && $current_User->check_perm( 'files', 'view' ) )
			{	// Display a button to view the files of the post only if current user has a permissions:
				echo '<a href="'.url_add_param( $Blog->get_filemanager_link(), 'fm_mode=link_object&amp;link_type=item&amp;link_object_ID='.$Item->ID )
							.'" class="'.button_class( 'text' ).'">'.get_icon( 'folder' ).' '.T_('Files').'</a>';
			}

			if( $Blog->get_setting( 'allow_comments' ) != 'never' )
			{
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'#comments" class="'.button_class( 'text' ).'">';
				$comments_number = generic_ctp_number( $Item->ID, 'comments', 'total' );
				echo get_icon( $comments_number > 0 ? 'comments' : 'nocomment' ).' ';
				// TRANS: Link to comments for current post
				comments_number( T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
				load_funcs('comments/_trackback.funcs.php'); // TODO: use newer call below
				trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
				echo '</a>';
			}
			echo '</span>';

			echo '<span class="'.button_class( 'group' ).'"> ';
			// Display edit button if current user has the rights:
			$Item->edit_link( array( // Link to backoffice for editing
					'before' => '',
					'after'  => '',
					'class'  => button_class( 'text_primary' ),
					'text'   => get_icon( 'edit_button' ).' '.T_('Edit')
				) );

			// Display copy button if current user has the rights:
			$Item->copy_link( array( // Link to backoffice for coping
					'before' => '',
					'after'  => '',
					'text'   => '#icon#',
					'class'  => button_class()
				) );
			echo '</span>';

			echo '<span class="'.button_class( 'group' ).'"> ';
			// Display the moderate buttons if current user has the rights:
			$status_link_params = array(
					'class'       => button_class( 'text' ),
					'redirect_to' => regenerate_url( '', '&highlight='.$Item->ID.'#item_'.$Item->ID, '', '&' ),
				);
			$Item->next_status_link( $status_link_params, true );
			$Item->next_status_link( $status_link_params, false );

			$next_status_in_row = $Item->get_next_status( false );
			if( $next_status_in_row && $next_status_in_row[0] != 'deprecated' )
			{ // Display deprecate button if current user has the rights:
				$Item->deprecate_link( '', '', get_icon( 'move_down_grey', 'imgtag', array( 'title' => '' ) ), '#', button_class() );
			}

			// Display delete button if current user has the rights:
			$Item->delete_link( '', ' ', '#', '#', button_class( 'text' ), false );
			echo '</span>';

			?>

			<div class="clear"></div>
		</div>

		<?php

		// _____________________________________ Displayed in SINGLE VIEW mode only _____________________________________

		if( $action == 'view' )
		{ // We are looking at a single post, include files and comments:

			if( $comment_type == 'meta' && ! $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
			{ // Current user cannot views meta comments
				$comment_type = 'feedback';
			}

			if( isset($GLOBALS['files_Module']) )
			{ // Files:
				echo '<div class="bPostAttachments">';	// TODO
	
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

			// Actions "Recycle bin" and "Refresh"
			echo '<div class="feedback-actions">';
			echo action_icon( T_('Refresh comment list'), 'refresh', url_add_param( $admin_url, 'ctrl=items&amp;blog='.$blog.'&amp;p='.$Item->ID.'#comments' ),
					' '.T_('Refresh'), 3, 4, array(
						'onclick' => 'startRefreshComments( \''.request_from().'\', '.$Item->ID.', 1, \''.$comment_type.'\' ); return false;',
						'class'   => 'btn btn-default'
					) );
			if( $comment_type != 'meta' )
			{ // Don't display "Recycle bin" link for meta comments, because they are deleted directly without recycle bin
				echo get_opentrash_link( true, false, array(
						'before' => ' <span id="recycle_bin">',
						'after' => '</span>',
						'class' => 'btn btn-default'
					) );
			}
			echo '</div>';

			if( $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
			{ // Display tabs to switch between user and meta comments Only if current user can views meta comments
				$switch_comment_type_url = $admin_url.'?ctrl=items&amp;blog='.$blog.'&amp;p='.$Item->ID;
				$metas_count = generic_ctp_number( $Item->ID, 'metas', 'total' );
				$switch_comment_type_tabs = array(
						'feedback' => array(
							'url'   => $switch_comment_type_url.'&amp;comment_type=feedback#comments',
							'title' => T_('User comments').' <span class="badge">'.generic_ctp_number( $Item->ID, 'feedbacks', 'total' ).'</span>' ),
						'meta' => array(
							'url'   => $switch_comment_type_url.'&amp;comment_type=meta#comments',
							'title' => T_('Meta discussion').' <span class="badge'.( $metas_count > 0 ? ' badge-important' : '' ).'">'.$metas_count.'</span>' )
					);
				?>
				<div class="feedback-tabs btn-group">
				<?php
					foreach( $switch_comment_type_tabs as $comment_tab_type => $comment_tab )
					{
						echo '<a href="'.$comment_tab['url'].'" class="btn'.( $comment_type == $comment_tab_type ? ' btn-primary' : ' btn-default' ).'">'.$comment_tab['title'].'</a>';
					}
				?>
				</div>
				<?php
			}

			echo '<div class="clear"></div>';

			$currentpage = param( 'currentpage', 'integer', 1 );
			$total_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), 'total' );
			$draft_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), 'draft' );
			// decide to show all comments, or only drafts
			if( ( $comment_type != 'meta' ) && // Display all comments in meta mode by default
			    ( $total_comments_number > 5 && $draft_comments_number > 0 ) )
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

			$show_comments_expiry = param( 'show_comments_expiry', 'string', 'active', false, true );
			$expiry_statuses = array( 'active' );
			if( $show_comments_expiry == 'all' )
			{ // Display also the expired comments
				$expiry_statuses[] = 'expired';
			}

			global $CommentList, $UserSettings;
			$CommentList = new CommentList2( $Blog );

			// Filter list:
			$CommentList->set_filters( array(
				'types' => $comment_type == 'meta' ? array( 'meta' ) : array( 'comment','trackback','pingback' ),
				'statuses' => $statuses,
				'order' => $comment_type == 'meta' ? 'DESC' : 'ASC',
				'post_ID' => $Item->ID,
				'comments' => $UserSettings->get( 'results_per_page' ),
				'page' => $currentpage,
				'expiry_statuses' => $expiry_statuses,
			) );
			$CommentList->query();

			// We do not want to comment actions use new redirect
			param( 'save_context', 'boolean', false );
			param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$Item->ID, '&' ), false, true );
			param( 'item_id', 'integer', $Item->ID );
			param( 'show_comments', 'string', $show_comments, false, true );

			// Display status filter
			?>
			<div class="bFeedback">
			<a id="comments"></a>
			<?php
			if( $display_params['disp_rating_summary'] )
			{ // Display a ratings summary
				echo $Item->get_rating_summary();
			}

			if( $comment_type != 'meta' )
			{ // Display this filter only for not meta comments
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
				$expiry_delay = $Item->get_setting( 'comment_expiry_delay' );
				if( ! empty( $expiry_delay ) )
				{ // A filter to display even the expired comments
				?>
				<div class="tile">
					&nbsp; | &nbsp;
					<input type="radio" name="show_comments_expiry" value="expiry" id="show_expiry_delay" class="radio" <?php if( $show_comments_expiry == 'active' ) echo 'checked="checked" '?> />
					<label for="show_expiry_delay"><?php echo get_duration_title( $expiry_delay ); ?></label>
				</div>
				<div class="tile">
					<input type="radio" name="show_comments_expiry" value="all" id="show_expiry_all" class="radio" <?php if( $show_comments_expiry == 'all' ) echo 'checked="checked" '?> />
					<label for="show_expiry_all"><?php echo T_('All comments') ?></label>
				</div>
				<?php
				}
			}

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

			if( ( $comment_type == 'meta' && $current_User->check_perm( 'meta_comment', 'add', false, $Item ) ) // User can add meta comment on the Item
			    || $Item->can_comment() ) // User can add standard comment
			{
			?>
			<!-- ========== FORM to add a comment ========== -->
			<h4><?php echo $comment_type == 'meta' ? T_('Leave a meta comment') : T_('Leave a comment'); ?>:</h4>

			<?php

			$Form = new Form( $htsrv_url.'comment_post.php', 'comment_checkchanges' );

			$Form->begin_form( 'bComment evo_form evo_form__comment '.( $comment_type == 'meta' ? ' evo_form__comment_meta' : '' ) );

			if( $comment_type == 'meta' )
			{
				echo '<b class="form_info">'.T_('Please remember: this comment will be included in a private discussion view and <u>only will be visible to other admins</u>').'</b>';
			}

			$Form->add_crumb( 'comment' );
			$Form->hidden( 'comment_item_ID', $Item->ID );
			$Form->hidden( 'comment_type', $comment_type );
			$Form->hidden( 'redirect_to', $ReqURI );

			$Form->info( T_('User'), $current_User->get_identity_link( array( 'link_text' => 'name' ) ).' '.get_user_profile_link( ' [', ']', T_('Edit profile') )  );
			$Form->textarea( $dummy_fields[ 'content' ], '', 12, T_('Comment text'), '', 40, 'bComment autocomplete_usernames' );

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

if( $action == 'view' )
{ // Load JS functions to work with comments
	load_funcs( 'comments/model/_comment_js.funcs.php' );

	// Handle show_comments radioboxes
	echo_show_comments_changed( $comment_type );
}

// Display navigation:
$ItemList->display_nav( 'footer' );

echo '</div>';// END OF <div id="styled_content_block">

?>