<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $cat;
global $posttypes_specialtypes;

// Default params:
$params = array_merge( array(
		'feature_block'     => false,
		'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'        => 'bPost',
		'image_size'        => 'fit-640x480',
		'disp_comment_form' => true,
		'item_link_type'    => 'permalink',
	), $params );

echo '<div id="styled_content_block">'; // Beginning of post display
if( ( $disp == 'single' ) && empty( $cat ) )
{ // Display breadcrumb, but only if it was not displayed yet. When category is set then breadcrumbs is already displayed.
	$Skin->display_breadcrumbs( $Item->main_cat_ID );
}
?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'block_start' => '<div class="posts_navigation">',
				'separator'   => ' :: ',
				'block_end'   => '</div>',
				'target_blog' => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
				'post_navigation' => 'same_category', // force to stay in the same category in this skin
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------

	$action_links = $Item->get_edit_link( array( // Link to backoffice for editing
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => 'roundbutton roundbutton_text',
		) );
	if( $Item->is_intro() && $Item->ptyp_ID > 1500 )
	{ // Link to edit category
		$ItemChapter = & $Item->get_main_Chapter();
		if( !empty( $ItemChapter ) )
		{
			$action_links .= $ItemChapter->get_edit_link( array(
					'text'          => get_icon( 'edit' ).' '.T_('Edit Cat'),
					'class'         => 'roundbutton roundbutton_text',
					'redirect_page' => 'front',
				) );
		}
	}
	if( $Item->status != 'published' )
	{
		$Item->status( array( 'format' => 'styled' ) );
	}
	$Item->title( array(
			'link_type'  => $params['item_link_type'],
			'before'     => '<div class="bTitle linked"><h1>',
			'after'      => '</h1><div class="roundbutton_group">'.$action_links.'</div><div class="clear"></div></div>',
			'nav_target' => false,
		) );

		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>

	<?php
		// List all tags attached to this post:
		$Item->tags( array(
				'before' =>         '<div class="bSmallPrint">'.T_('Tags').': ',
				'after' =>          '</div>',
				'separator' =>      ', ',
			) );

		echo '<p class="notes">';
		$Item->author( array(
				'before'    => T_('Created by '),
				'after'     => ' &bull; ',
				'link_text' => 'login',
			) );
		$Item->lastedit_user( array(
				'before'    => T_('Last edit by '),
				'after'     => T_(' on ').$Item->get_mod_date( 'F jS, Y' ),
				'link_text' => 'login',
			) );
		'</p>';
		echo $Item->get_history_link( array(
				'before'    => ' &bull; ',
				'link_text' => T_('View history')
			) );

		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( $params, array(
				'before_section_title' => '<h2 class="comments_list_title">',
				'after_section_title'  => '</h2>',
				'form_title_start'     => '<h3 class="comments_form_title">',
				'form_title_end'       => '</h3>',
			) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>
<?php 
echo '</div>'; // End of post display
?>