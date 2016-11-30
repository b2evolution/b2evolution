<?php
/**
 * This is the template that displays the posts with categories for a blog
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


global $Collection, $Blog;

// Default params:
$params = array_merge( array(
		'item_class'        => 'evo_post evo_content_block',
		'item_type_class'   => 'evo_post__ptyp_',
		'item_status_class' => 'evo_post__',
	), $params );

// ------------------------------- START OF INTRO POST -------------------------------
init_MainList( $Blog->get_setting('posts_per_page') );
if( $Item = & get_featured_Item( 'catdir' ) )
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
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
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
		'before' => '<div class="msg_nothing">',
		'after'  => '</div>',
		'msg_empty_logged_in'     => T_('Sorry, there is nothing to display...'),
		// This will display if the collection has not been made private. Otherwise we will be redirected to a login screen anyways
		'msg_empty_not_logged_in' => T_('This site has no public contents.')
	);
// Get only root categories of this blog
$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, 0, true );
// Boolean var to know when at least one post is displayed
$no_content_to_display = true;
if( ! empty( $chapters ) )
{ // Display the posts with chapters
	foreach( $chapters as $Chapter )
	{
		// Get the posts of current category
		$ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max() );
		$ItemList->set_filters( array(
				'cat_array'    => array( $Chapter->ID ), // Limit only by selected cat (exclude posts from child categories)
				'cat_modifier' => NULL,
				'unit'         => 'all', // Display all items of this category, Don't limit by page
			) );
		$ItemList->query();
		if( $ItemList->result_num_rows > 0 )
		{
			$no_content_to_display = false;
?>
<div class="posts_list">
	<div class="category_title clear"><h2><a href="<?php echo $Chapter->get_permanent_url(); ?>"><?php echo $Chapter->get( 'name' ); ?></a></h2></div>
<?php
			while( $Item = & $ItemList->get_item() )
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
						'gallery_image_limit'        => 0, // Don't use images from attached folders.
						'limit'                      => 1, // Get only first attached image depending on position priority, see param below:
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
				// Flag:
				$item_flag = $Item->get_flag( array(
						'only_flagged' => true
					) );
				// Display a title
				echo $Item->get_title( array(
					'before' => $item_first_image.'<br />'.$item_flag,
					) );
				// Restore previous locale (Blog locale)
				locale_restore_previous();
?>
</div>
<?php
			}
		}
?>
</div>
<?php
	}
} // ---------------------------------- END OF POSTS ------------------------------------
if( $no_content_to_display )
{ // No category and no post in this blog
	echo $params_no_content['before']
		.( is_logged_in() ? $params_no_content['msg_empty_logged_in'] : $params_no_content['msg_empty_not_logged_in'] )
		.$params_no_content['after'];
}
?>