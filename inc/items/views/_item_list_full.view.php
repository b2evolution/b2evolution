<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;
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
global $edit_item_url, $delete_item_url, $p, $dummy_fields;
global $comment_allowed_tags, $comment_type;
global $Plugins, $DB, $UserSettings, $Session, $Messages;

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
echo '<div class="evo_content_block">';

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
	<div id="<?php $Item->anchor_id() ?>" class="panel panel-default evo_post evo_post__status_<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
		// We don't switch locales in the backoffice, since we use the user pref anyway
		// Load item's creator user:
		$Item->get_creator_User();
		?>
		<div class="panel-heading small <?php
		if( $Item->ID == $highlight )
		{
			echo 'fadeout-ffff00" id="fadeout-1';
		}
		?>">
			<?php
				echo '<div class="pull-right">';
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

		<div class="panel-body">
			<?php
				$Item->format_status( array(
						'template' => '<div class="pull-right"><span class="note status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$"><span>$status_title$</span></span></div>',
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
						'before_gallery'      => '<div class="evo_post_gallery">',
						'after_gallery'       => '</div>',
						'gallery_table_start' => '',
						'gallery_table_end'   => '',
						'gallery_row_start'   => '',
						'gallery_row_end'     => '',
						'gallery_cell_start'  => '<div class="evo_post_gallery__image">',
						'gallery_cell_end'    => '</div>',
						'gallery_image_limit' => 1000,
						'gallery_link_rel'    => 'lightbox[p'.$Item->ID.']',
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
					'before' =>         '<div class="panel-body small text-muted evo_post__tags">'.T_('Tags').': ',
					'after' =>          '</div>',
					'separator' =>      ', ',
				) );
		?>

		<div class="panel-footer">
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

			<div class="clearfix"></div>
		</div>

		<?php

		// _____________________________________ Displayed in SINGLE VIEW mode only _____________________________________

		if( $action == 'view' )
		{ // We are looking at a single post, include files and comments:

			if( $comment_type == 'meta' && ! $Item->can_see_meta_comments() )
			{ // Current user cannot views meta comments
				$comment_type = 'feedback';
			}

			if( isset($GLOBALS['files_Module']) )
			{ // Files:
				echo '<div class="evo_post__attachments">';	// TODO

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

			if( $Item->can_see_meta_comments() )
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

			echo '<div class="clearfix"></div>';

			$comment_moderation_statuses = explode( ',', $Blog->get_setting( 'moderation_statuses' ) );

			$currentpage = param( 'currentpage', 'integer', 1 );
			$total_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), 'total' );
			$moderation_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), $comment_moderation_statuses );
			// Decide to show all comments, or only which require moderation:
			if( ( $comment_type != 'meta' ) && // Display all comments in meta mode by default
			    ( $total_comments_number > 5 && $moderation_comments_number > 0 ) )
			{	// Show only requiring moderation comments:
				$statuses = $comment_moderation_statuses;
				$show_comments = 'moderation';
				param( 'comments_number', 'integer', $moderation_comments_number );
			}
			else
			{	// Show all comments:
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

			// We do not want to comment actions use new redirect
			param( 'save_context', 'boolean', false );
			param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$Item->ID, '&' ), false, true );
			param( 'item_id', 'integer', $Item->ID );
			param( 'show_comments', 'string', $show_comments, false, true );
			$comment_reply_ID = param( 'reply_ID', 'integer', 0 );

			// Display status filter
			?>
			<div class="evo_post__comments">
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
					<input type="radio" name="show_comments" value="moderation" id="only_moderation" class="radio" <?php if( $show_comments == 'moderation' ) echo 'checked="checked" '?> />
					<label for="only_moderation"><?php echo T_('Requiring moderation') ?></label>
				</div>
				<div class="tile">
					<input type="radio" name="show_comments" value="valid" id="only_valid" class="radio" <?php if( $show_comments == 'valid' ) echo 'checked="checked" '?> />
					<label for="only_valid"><?php echo T_('Valid') ?></label>
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

			// Display comments of the viewed Item:
			echo '<div id="comments_container" value="'.$Item->ID.'" class="evo_comments_container">';
			echo_item_comments( $blog, $Item->ID, $statuses, $currentpage, NULL, array(), '', $expiry_statuses, $comment_type );
			echo '</div>';

			if( ( $comment_type == 'meta' && $Item->can_meta_comment() ) // User can add meta comment on the Item
			    || $Item->can_comment() ) // User can add standard comment
			{

			// Try to get a previewed Comment and check if it is for current viewed Item:
			$preview_Comment = get_comment_from_session( 'preview', $comment_type );
			$preview_Comment = ( empty( $preview_Comment ) || $preview_Comment->item_ID != $Item->ID ) ? false : $preview_Comment;

			if( $preview_Comment )
			{	// If preview comment is displayed currently

				if( empty( $comment_reply_ID ) || empty( $preview_Comment->in_reply_to_cmt_ID ) )
				{	// Display a previewed comment under all comments only if it is not replied on any other comment:
					echo '<div class="evo_comments_container">';
					echo_comment( $preview_Comment );
					echo '</div>';
				}

				// Display the error message again after preview of comment:
				$Messages->add( T_('This is a preview only! Do not forget to send your comment!'), 'error' );
				$Messages->display();
			}

			?>
			<!-- ========== FORM to add a comment ========== -->
			<h4><?php echo $comment_type == 'meta' ? T_('Leave a meta comment') : T_('Leave a comment'); ?>:</h4>

			<?php

			if( $preview_Comment )
			{	// Get a Comment properties from preview request:
				$Comment = $preview_Comment;

				// Form fields:
				$comment_content = $Comment->original_content;
				// All file IDs that have been attached:
				$comment_attachments = $Comment->preview_attachments;
				// All attachment file IDs which checkbox was checked in:
				$checked_attachments = $Comment->checked_attachments;
				// Get what renderer checkboxes were selected on form:
				$comment_renderers = explode( '.', $Comment->get( 'renderers' ) );
			}
			else
			{	// Create new Comment:
				if( ( $Comment = get_comment_from_session( 'unsaved', $comment_type ) ) === NULL )
				{	// There is no saved Comment in Session
					$Comment = new Comment();
					$comment_attachments = '';
					$checked_attachments = '';
				}
				else
				{	// Get params from Session:
					// comment_attachments contains all file IDs that have been attached
					$comment_attachments = $Comment->preview_attachments;
					// checked_attachments contains all attachment file IDs which checkbox was checked in
					$checked_attachments = $Comment->checked_attachments;
				}
				$comment_content = $Comment->get( 'content' );
				$comment_renderers = $Comment->get_renderers();
			}

			$Form = new Form( get_htsrv_url().'comment_post.php', 'comment_checkchanges', 'post', NULL, 'multipart/form-data' );

			$Form->begin_form( 'evo_form evo_form__comment '.( $comment_type == 'meta' ? ' evo_form__comment_meta' : '' ) );

			if( ! empty( $comment_reply_ID ) )
			{
				$Form->hidden( 'reply_ID', $comment_reply_ID );
				// Display a link to scroll back up to replying comment:
				echo '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$Item->Blog->ID.'&amp;p='.$Item->ID.'&amp;reply_ID='.$comment_reply_ID.'#c'.$comment_reply_ID.'" class="comment_reply_current" rel="'.$comment_reply_ID.'">'.T_('You are currently replying to a specific comment').'</a>';
			}

			if( $comment_type == 'meta' )
			{
				echo '<b class="form_info">'.T_('Please remember: this comment will be included in a private discussion view and <u>only will be visible to other admins</u>').'</b>';
			}

			$Form->add_crumb( 'comment' );
			$Form->hidden( 'comment_item_ID', $Item->ID );
			$Form->hidden( 'comment_type', $comment_type );
			$Form->hidden( 'redirect_to', $admin_url.'?ctrl=items&blog='.$Item->Blog->ID.'&p='.$Item->ID.'&comment_type='.$comment_type );

			$Form->info( T_('User'), $current_User->get_identity_link( array( 'link_text' => 'name' ) ).' '.get_user_profile_link( ' [', ']', T_('Edit profile') )  );

			if( $comment_type != 'meta' && $Item->can_rate() )
			{	// Comment rating:
				ob_start();
				$Comment->rating_input( array( 'item_ID' => $Item->ID ) );
				$comment_rating = ob_get_clean();
				$Form->info_field( T_('Your vote'), $comment_rating );
			}

			// Display plugin toolbars:
			ob_start();
			echo '<div class="comment_toolbars">';
			$Plugins->trigger_event( 'DisplayCommentToolbar', array( 'Comment' => & $Comment, 'Item' => & $Item ) );
			echo '</div>';
			$comment_toolbar = ob_get_clean();

			// Message field:
			$form_inputstart = $Form->inputstart;
			$Form->inputstart .= $comment_toolbar;
			$Form->textarea_input( $dummy_fields['content'], $comment_content, 12, T_('Comment text'), array(
					'cols'  => 40,
					'class' => 'autocomplete_usernames'
				) );
			$Form->inputstart = $form_inputstart;

			// Set b2evoCanvas for plugins:
			echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "'.$dummy_fields['content'].'" );</script>';

			// Attach files:
			if( !empty( $comment_attachments ) )
			{	// display already attached files checkboxes
				$FileCache = & get_FileCache();
				$attachments = explode( ',', $comment_attachments );
				$final_attachments = explode( ',', $checked_attachments );
				// create attachments checklist
				$list_options = array();
				foreach( $attachments as $attachment_ID )
				{
					$attachment_File = $FileCache->get_by_ID( $attachment_ID, false );
					if( $attachment_File )
					{
						// checkbox should be checked only if the corresponding file id is in the final attachments array
						$checked = in_array( $attachment_ID, $final_attachments );
						$list_options[] = array( 'preview_attachment'.$attachment_ID, 1, $attachment_File->get( 'name' ), $checked, false );
					}
				}
				if( !empty( $list_options ) )
				{	// display list
					$Form->checklist( $list_options, 'comment_attachments', T_( 'Attached files' ) );
				}
				// memorize all attachments ids
				$Form->hidden( 'preview_attachments', $comment_attachments );
			}
			if( $Item->can_attach() )
			{	// Display attach file input field:
				$Form->input_field( array(
						'label' => T_('Attach files'),
						'note'  => get_icon( 'help', 'imgtag', array(
								'data-toggle'    => 'tooltip',
								'data-placement' => 'top',
								'data-html'      => 'true',
								'title'          => htmlspecialchars( get_upload_restriction( array(
										'block_after'     => '',
										'block_separator' => '<br /><br />' ) ) )
							) ),
						'name'  => 'uploadfile[]',
						'type'  => 'file'
					) );
			}

			$Form->info( T_('Text Renderers'), $Plugins->get_renderer_checkboxes( $comment_renderers, array(
					'Blog'         => & $Blog,
					'setting_name' => 'coll_apply_comment_rendering'
				) ) );

			$preview_text = ( $Item->can_attach() ) ? T_('Preview/Add file') : T_('Preview');
			$Form->buttons_input( array(
					array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview btn-info', 'value' => $preview_text ),
					array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit SaveButton', 'value' => T_('Send comment') )
				) );

			?>

				<div class="clearfix"></div>
			<?php
				$Form->end_form();
			?>
			<!-- ========== END of FORM to add a comment ========== -->
			<?php

			// ========== START of links to manage subscriptions ========== //
			echo '<br /><nav class="evo_post_comment_notification">';

			$notification_icon = get_icon( 'notification' );

			$not_subscribed = true;
			$creator_User = $Item->get_creator_User();

			if( $Blog->get_setting( 'allow_comment_subscriptions' ) )
			{
				$sql = 'SELECT count( sub_user_ID ) FROM T_subscriptions
							WHERE sub_user_ID = '.$current_User->ID.' AND sub_coll_ID = '.$Blog->ID.' AND sub_comments <> 0';
				if( $DB->get_var( $sql ) > 0 )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'You are receiving notifications when anyone comments on any post.' );
					echo ' <a href="'.get_notifications_url().'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
					$not_subscribed = false;
				}
			}

			if( $not_subscribed && ( $creator_User->ID == $current_User->ID ) && ( $UserSettings->get( 'notify_published_comments', $current_User->ID ) != 0 ) )
			{
				echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' );
				echo ' <a href="'.get_notifications_url().'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
				$not_subscribed = false;
			}
			if( $not_subscribed && $Blog->get_setting( 'allow_item_subscriptions' ) )
			{
				if( get_user_isubscription( $current_User->ID, $Item->ID ) )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'You will be notified by email when someone comments here.' );
					echo ' <a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=0&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Click here to unsubscribe.' ).'</a></span></p>';
				}
				else
				{
					echo '<p class="text-center"><a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=1&amp;'.url_crumb( 'collections_isubs_update' ).'" class="btn btn-default">'.$notification_icon.' '.T_( 'Notify me by email when someone comments here.' ).'</a></p>';
				}
			}

			echo '</nav>';
			// ========== END of links to manage subscriptions ========== //

			} // / can comment

			// ========== START of item workflow properties ========== //
			if( is_logged_in() &&
					$Blog->get_setting( 'use_workflow' ) &&
					$current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) &&
					$current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
			{	// Display workflow properties if current user can edit this post:
				$Form = new Form( get_htsrv_url().'item_edit.php' );

				$Form->add_crumb( 'item' );
				$Form->hidden( 'blog', $Blog->ID );
				$Form->hidden( 'post_ID', $Item->ID );
				$Form->hidden( 'redirect_to', $admin_url.'?ctrl=items&blog='.$Blog->ID.'&p='.$Item->ID );

				$Form->begin_form( 'evo_item_workflow_form' );

				$Form->begin_fieldset( T_('Workflow properties') );

				echo '<div class="evo_item_workflow_form__fields">';

				$Form->select_input_array( 'item_priority', $Item->priority, item_priority_titles(), T_('Priority'), '', array( 'force_keys_as_values' => true ) );

				// Load current blog members into cache:
				$UserCache = & get_UserCache();
				// Load only first 21 users to know when we should display an input box instead of full users list
				$UserCache->load_blogmembers( $Blog->ID, 21, false );

				if( count( $UserCache->cache ) > 20 )
				{
					$assigned_User = & $UserCache->get_by_ID( $Item->get( 'assigned_user_ID' ), false, false );
					$Form->username( 'item_assigned_user_login', $assigned_User, T_('Assigned to'), '', 'only_assignees' );
				}
				else
				{
					$Form->select_object( 'item_assigned_user_ID', NULL, $Item, T_('Assigned to'),
															'', true, '', 'get_assigned_user_options' );
				}

				$ItemStatusCache = & get_ItemStatusCache();
				$ItemStatusCache->load_all();
				$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $Item->pst_ID, true ), T_('Task status') );

				$Form->date( 'item_deadline', $Item->get('datedeadline'), T_('Deadline') );

				$Form->button( array( 'submit', 'actionArray[update_workflow]', T_('Update'), 'SaveButton' ) );

				echo '</div>';

				$Form->end_fieldset();

				$Form->end_form();
			}
			// ========== END of item workflow properties ========== //
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

echo '</div>';// END OF <div class="evo_content_block">

?>