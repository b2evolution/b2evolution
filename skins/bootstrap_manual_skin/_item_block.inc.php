<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $Collection, $Blog, $cat, $app_version;

// Default params:
$params = array_merge( array(
		'feature_block'     => false,
		'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'        => 'evo_post',
		'item_type_class'   => 'evo_post__ptyp_',
		'item_status_class' => 'evo_post__',
		'image_class'       => 'img-responsive',
		'image_size'        => get_skin_setting( 'main_content_image_size', 'fit-1280x720' ),
		'item_link_type'    => 'post',
		// Controlling the title:
		'item_title_line_before'     => '<div class="evo_post_title">',	// Note: we use an extra class because it facilitates styling
			'item_title_before'          => '<h2>',
			'item_title_after'           => '</h2>',
			'item_title_single_before'   => '<h1>',	// This replaces the above in case of disp=single or disp=page
			'item_title_single_after'    => '</h1>',
		'item_title_line_after'      => '</div>',
	), $params );
?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// Comment out prev/next links display until it is not correctly implemented to get cats and items
		// in the same order as they are in the sidebar
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		/*item_prevnext_links( array(
				'block_start' => '<ul class="pager">',
				'block_end'   => '</ul>',
				'template' => '$prev$$next$',
				'prev_start' => '<li class="previous">',
				'prev_text' => '<span aria-hidden="true">&larr;</span> $title$',
				'prev_end' => '</li>',
				'next_start' => '<li class="next">',
				'next_text' => '$title$ <span aria-hidden="true">&rarr;</span>',
				'next_end' => '</li>',
				'target_blog' => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
				'post_navigation' => 'same_category', // force to stay in the same category in this skin
			) );*/
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------

	// Link for editing:
	$action_links = $Item->get_edit_link( array(
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => button_class( 'text' ),
		) );
	// Link for duplicating:
	$action_links .= $Item->get_copy_link( array(
			'before' => '',
			'after'  => '',
			'text'   => '#icon#',
			'class'  => button_class(),
		) );
	if( $Item->is_intro() && ! empty( $cat ) )
	{ // Link to edit category
		$ItemChapter = & $Item->get_main_Chapter();
		if( !empty( $ItemChapter ) )
		{
			$action_links .= $ItemChapter->get_edit_link( array(
					'text'          => get_icon( 'edit' ).' '.T_('Edit Cat'),
					'class'         => button_class( 'text' ),
					'redirect_page' => 'front',
				) );
		}
	}
	if( ! empty( $action_links ) )
	{	// Group all action icons:
		$action_links = '<div class="'.button_class( 'group' ).'">'.$action_links.'</div>';
	}

	if( $disp != 'single' )
	{
		$Item->title( array(
				'link_type'  => $params['item_link_type'],
				'before'     => $params['item_title_line_before'].$params['item_title_before'],
				'after'      => $params['item_title_after'].$action_links.$params['item_title_line_after'],
				'nav_target' => false,
			) );
	}
	?>

	<?php
	if( $disp == 'single' )
	{
		// ------------------------- "Item Single" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		widget_container( 'item_single', array(
			'widget_context' => 'item',	// Signal that we are displaying within an Item
			// The following (optional) params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// This will enclose each widget in a block:
			'block_start' => '<div class="evo_widget $wi_class$">',
			'block_end' => '</div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<h3>',
			'block_title_end' => '</h3>',
			// Template params for "Breadcrumb Path" widget:
			'override_params_for_breadcrumb_path' => array(
					'block_start'      => '<div class="evo_widget $wi_class$"><ol class="breadcrumb">',
					'block_end'        => '</ol></div>',
					'separator'        => '',
					'item_mask'        => '<li><a href="$url$">$title$</a></li>',
					'item_active_mask' => '<li class="active">$title$</li>',
				),
			// Template params for "Item Title" widget:
			'widget_item_title_params'  => array(
					'before' => $params['item_title_line_before'].$params['item_title_single_before'],
					'after' => $params['item_title_single_after'].$action_links.$params['item_title_line_after'],
					'link_type' => $params['item_link_type'],
				),
			// Template params for "Item Visibility Badge" widget:
			'widget_item_visibility_badge_display' => ( ! $Item->is_intro() && $Item->status != 'published' ),
			'widget_item_visibility_badge_params'  => array(
					'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
				),
			// Template params for "Item Link" widget:
			'widget_item_link_before'    => '<p class="evo_post_link">',
			'widget_item_link_after'     => '</p>',
			// Template params for "Item Tags" widget:
			'widget_item_tags_before'    => '<nav class="small post_tags text-muted">',
			'widget_item_tags_after'     => '</nav>',
			'widget_item_tags_separator' => ', ',
			// Template params for "Small Print" widget:
			'widget_item_small_print_before'         => '<p class="small text-muted">',
			'widget_item_small_print_after'          => '</p>',
			'widget_item_small_print_display_author' => false,
			// Params for skin file "_item_content.inc.php":
			'widget_item_content_params' => $params,
			// Template params for "Item Attachments" widget:
			'widget_item_attachments_params' => array(
					'limit_attach'       => 1000,
					'before'             => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
					'after'              => '</ul></div>',
					'before_attach'      => '<li class="evo_file">',
					'after_attach'       => '</li>',
					'before_attach_size' => ' <span class="evo_file_size">(',
					'after_attach_size'  => ')</span>',
				),
		) );
		// ----------------------------- END OF "Item Single" CONTAINER -----------------------------
	}
	elseif( $disp == 'page' )
	{
		?>
		<div class="evo_container evo_container__item_page">
		<?php
		// ------------------------- "Item Page" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		widget_container( 'item_page', array(
			'widget_context' => 'item',	// Signal that we are displaying within an Item
			// The following (optional) params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// This will enclose each widget in a block:
			'block_start' => '<div class="evo_widget $wi_class$">',
			'block_end' => '</div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<h3>',
			'block_title_end' => '</h3>',
			// Params for skin file "_item_content.inc.php"
			'widget_item_content_params' => $params,
			// Template params for "Item Attachments" widget:
			'widget_item_attachments_params' => array(
					'limit_attach'       => 1000,
					'before'             => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
					'after'              => '</ul></div>',
					'before_attach'      => '<li class="evo_file">',
					'after_attach'       => '</li>',
					'before_attach_size' => ' <span class="evo_file_size">(',
					'after_attach_size'  => ')</span>',
				),
		) );
		// ----------------------------- END OF "Item Page" CONTAINER -----------------------------
	}
	else
	{
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------

		if( ! $Item->is_intro() && ! $Item->is_featured() )
		{ // Don't display this additional info for intro posts

			// List all tags attached to this post:
			$Item->tags( array(
					'before'    => '<div class="small text-muted">'.T_('Tags').': ',
					'after'     => '</div>',
					'separator' => ', ',
				) );

			echo '<p class="small text-muted">';
			$Item->author( array(
					'before'    => T_('Created by '),
					'after'     => ' &bull; ',
					'link_text' => 'auto',
				) );
			$Item->lastedit_user( array(
					'before'    => T_('Last edit by '),
					'after'     => T_(' on ').$Item->get_mod_date( locale_extdatefmt() ),
					'link_text' => 'auto',
				) );
			'</p>';
			echo $Item->get_history_link( array(
					'before'    => ' &bull; ',
					'link_text' => T_('View change history')
				) );
		}
	}
	?>

	<?php
	if( is_single_page() )
	{	// Display comments only on single Item's page:
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( $params, array(
				'disp_comments'        => true,
				'disp_comment_form'    => true,
				'disp_trackbacks'      => true,
				'disp_trackback_url'   => true,
				'disp_pingbacks'       => true,
				'disp_webmentions'     => true,
				'disp_meta_comments'   => false,
				'before_section_title' => '<h3 class="evo_comment__list_title">',
				'after_section_title'  => '</h3>',
			) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	}
	?>

	<?php
	if( evo_version_compare( $app_version, '6.7' ) >= 0 )
	{	// We are running at least b2evo 6.7, so we can include this file:
		// ------------------ WORKFLOW PROPERTIES INCLUDED HERE ------------------
		skin_include( '_item_workflow.inc.php' );
		// ---------------------- END OF WORKFLOW PROPERTIES ---------------------
	}
	?>

	<?php
	if( evo_version_compare( $app_version, '6.7' ) >= 0 )
	{	// We are running at least b2evo 6.7, so we can include this file:
		// ------------------ META COMMENTS INCLUDED HERE ------------------
		skin_include( '_item_meta_comments.inc.php', array(
				'comment_start'         => '<article class="evo_comment evo_comment__meta panel panel-default">',
				'comment_end'           => '</article>',
			) );
		// ---------------------- END OF META COMMENTS ---------------------
	}
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>