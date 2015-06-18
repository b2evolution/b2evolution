<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $cat;
global $posttypes_specialtypes;

// Default params:
$params = array_merge( array(
		'feature_block'     => false,
		'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'        => 'evo_post',
		'item_type_class'   => 'evo_post__ptyp_',
		'item_status_class' => 'evo_post__',
		'image_class'       => 'img-responsive',
		'image_size'        => 'fit-1280x720',
		'disp_comment_form' => true,
		'item_link_type'    => 'post',
	), $params );

if( $disp == 'single' )
{ // Display the breadcrumb path
	if( empty( $cat ) )
	{ // Set a category as main of current Item
		$cat = $Item->main_cat_ID;

		// Display the breadcrumbs only when global $cat is empty before line above
		// Otherwise it is already displayed in header file
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'breadcrumb_path',
				// Optional display params
				'block_start'      => '<ol class="breadcrumb">',
				'block_end'        => '</ol>',
				'separator'        => '',
				'item_mask'        => '<li><a href="$url$">$title$</a></li>',
				'item_active_mask' => '<li class="active">$title$</li>',
			) );
	}
}
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

	$action_links = $Item->get_edit_link( array( // Link to backoffice for editing
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => button_class( 'text' ),
		) );
	if( $Item->is_intro() && $Item->ityp_ID > 1500 )
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
	if( $Item->status != 'published' )
	{
		$Item->format_status( array(
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right">$status_title$</div>',
			) );
	}
	$Item->title( array(
			'link_type'  => $params['item_link_type'],
			'before'     => '<div class="evo_post_title"><h1>',
			'after'      => '</h1><div class="'.button_class( 'group' ).'">'.$action_links.'</div></div>',
			'nav_target' => false,
		) );
	?>

	<?php
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
			$Item->lastedit_user( array(
					'before'    => T_('Last edit by '),
					'after'     => T_(' on ').$Item->get_mod_date( 'F jS, Y' ),
					'link_text' => 'name',
				) );
			'</p>';
			echo $Item->get_history_link( array(
					'before'    => ' &bull; ',
					'link_text' => T_('View history')
				) );
		}
	?>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( $params, array(
				'before_section_title' => '<h3 class="comments_list_title">',
				'after_section_title'  => '</h3>',
			) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>