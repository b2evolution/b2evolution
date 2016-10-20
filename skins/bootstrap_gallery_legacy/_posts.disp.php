<?php
/**
 * This is the template that displays the posts for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_gallery_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// ------------------------------- START OF INTRO POST -------------------------------
if( $Item = & get_featured_Item() )
{ // We have a intro-front post to display:
?>
<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( array( 'item_class' => 'jumbotron evo_content_block evo_post' ) ) ?>" lang="<?php $Item->lang() ?>">

	<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

	$action_links = $Item->get_edit_link( array( // Link to backoffice for editing
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => button_class( 'text' ),
		) );
	if( $Item->status != 'published' )
	{
		$Item->format_status( array(
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right">$status_title$</div>',
			) );
	}
	$Item->title( array(
			'link_type'  => 'none',
			'before'     => '<div class="evo_post_title"><h1>',
			'after'      => '</h1><div class="'.button_class( 'group' ).'">'.$action_links.'</div></div>',
			'nav_target' => false,
		) );

	// ---------------------- POST CONTENT INCLUDED HERE ----------------------
	skin_include( '_item_content.inc.php', array_merge( $params, array( 'Item' => $Item ) ) );
	// Note: You can customize the default item content by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------

	locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>
<?php
// ------------------------------- END OF INTRO-FRONT POST -------------------------------
}

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
$params_no_content = array(
					'before'      => '<div class="msg_nothing">',
					'after'       => '</div>' );
if( ! is_logged_in() )
{ // fp> the following is kind of a hack. It's not really correct.
	$url = get_login_url( 'no public content' );
	$params_no_content['msg_empty'] = '<p>'.T_('This site has no public contents.').'</p><p><a href="'.$url.'">'.T_('Log in now!').'</a></p>';
}
$list_is_empty = display_if_empty( $params_no_content );

if( ! $list_is_empty )
{
?>
<div class="posts_list">
<?php
	while( $Item = & mainlist_get_item() )
	{ // For each blog post, do everything below up to the closing curly brace "}"

		// Temporarily switch to post locale (useful for multilingual blogs)
		$Item->locale_temp_switch();
?>
<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">
<?php
		// Display images that are linked to this post:
		$item_first_image = $Item->get_images( array(
				'before'              => '',
				'before_image'        => '',
				'before_image_legend' => '',
				'after_image_legend'  => '',
				'after_image'         => '',
				'after'               => '',
				'image_size'          => $Skin->get_setting( 'posts_thumb_size' ),
				'image_link_to'       => 'single',
				'image_desc'          => '',
				'limit'                      => 1,
				'restrict_to_image_position' => 'cover,teaser,aftermore,inline',
				'get_rendered_attachments'   => false,
				// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
				'links_sql_select'           => ', CASE '
						.'WHEN link_position = "cover"     THEN "1" '
						.'WHEN link_position = "teaser"    THEN "2" '
						.'WHEN link_position = "aftermore" THEN "3" '
						.'WHEN link_position = "inline"    THEN "4" '
						// .'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
					.'END AS position_order',
				'links_sql_orderby'          => 'position_order, link_order',
			) );

		if( empty( $item_first_image ) )
		{ // No images in this post, Display an empty block
			$item_first_image = $Item->get_permanent_link( '<b>'.T_('No pictures yet').'</b>', '#', 'album_nopic' );
		}
		else if( $item_first_image == 'plugin_render_attachments' )
		{ // No images, but some attachments(e.g. videos) are rendered by plugins
			$item_first_image = $Item->get_permanent_link( '<b>'.T_('Click to see contents').'</b>', '#', 'album_nopic' );
		}

		// Display a title
		echo $Item->get_title( array(
			'before' => $item_first_image.'<br />',
			) );

		// Restore previous locale (Blog locale)
		locale_restore_previous();
?>
</div>
<?php
	} // ---------------------------------- END OF POSTS ------------------------------------
?>
</div>
<?php
}
?>