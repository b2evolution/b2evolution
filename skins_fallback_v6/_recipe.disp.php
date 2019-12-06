<?php
/**
 * This is the template that displays a recipe in a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		// Classes for the <article> tag:
		'item_class'               => 'evo_post evo_content_block',
		'item_type_class'          => 'evo_post__ptyp_',
		'item_status_class'        => 'evo_post__',
		'item_style'               => '',
		// Controlling the title:
		'item_title_before'        => '<h2>',
		'item_title_after'         => '</h2>',
		// Controlling the content:
		'author_link_text'         => 'auto',
		'before_content_teaser'    => '<p>',
		'after_content_teaser'     => '</p>',
		'before_content_extension' => '',
		'after_content_extension'  => '',
		// Controlling the images:
		'image_positions'          => 'cover,teaser,teaserperm,teaserlink',
		'before_images'            => '<div class="evo_post_images">',
		'before_image'             => '<figure class="evo_image_block">',
		'before_image_legend'      => '<figcaption class="evo_image_legend">',
		'after_image_legend'       => '</figcaption>',
		'after_image'              => '</figure>',
		'after_images'             => '</div>',
		'image_class'              => 'img-responsive',
		'image_size'               => 'crop-320x320',
		'image_limit'              =>  1000,
		'image_link_to'            => 'original', // Can be 'original', 'single' or empty
		'excerpt_image_class'      => '',
		'excerpt_image_size'       => 'fit-80x80',
		'excerpt_image_limit'      => 0,
		'excerpt_image_link_to'    => 'single',
		'before_gallery'           => '<div class="evo_post_gallery">',
		'after_gallery'            => '</div>',
		'gallery_table_start'      => '',
		'gallery_table_end'        => '',
		'gallery_row_start'        => '',
		'gallery_row_end'          => '',
		'gallery_cell_start'       => '<div class="evo_post_gallery__image">',
		'gallery_cell_end'         => '</div>',
		'gallery_image_size'       => 'crop-80x80',
		'gallery_image_limit'      => 1000,
		'gallery_colls'            => 5,
		'gallery_order'            => '', // Can be 'ASC', 'DESC', 'RAND' or empty

		'page_links_start'         => '<p class="evo_post_pagination">'.T_('Pages').': ',
		'page_links_end'           => '</p>',
		'page_links_separator'     => '&middot; ',
		'page_links_single'        => '',
		'page_links_current_page'  => '#',
		'page_links_pagelink'      => '%d',
		'page_links_url'           => '',

		'footer_text_mode'         => '#', // 'single', 'xml' or empty. Will detect 'single' from $disp automatically.
		'footer_text_start'        => '<div class="evo_post_footer">',
		'footer_text_end'          => '</div>',
	), $params );

// Display message if no post:
display_if_empty( array(
		'before'      => '<p class="msg_nothing">',
		'after'       => '</p>',
		'msg_empty'   => T_('Sorry, there is nothing to display...'),
	) );

if( mainlist_get_item() )
{	// If Item is found by requested slug:

	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
?>

<div class="evo_content_block">

<article id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>"<?php
	echo empty( $params['item_style'] ) ? '' : ' style="'.format_to_output( $params['item_style'], 'htmlattr' ).'"' ?>>

	<header>
		<?php
			// ------------------------- "Item Single - Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container contents:
			widget_container( 'item_single_header', array(
				'widget_context' => 'item',	// Signal that we are displaying within an Item
				// The following (optional) params will be used as defaults for widgets included in this container:
				'container_display_if_empty' => false, // If no widget, don't display container at all
				// This will enclose each widget in a block:
				'block_start' => '<div class="evo_widget $wi_class$">',
				'block_end' => '</div>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h3>',
				'block_title_end' => '</h3>',

				'author_link_text' => $params['author_link_text'],

				// Controlling the title:
				'widget_item_title_display' => false,
				// Item Previous Next widget
				'widget_item_next_previous_params' => array(
					),
				// Item Visibility Badge widge template
				'widget_item_visibility_badge_display' => ( ! $Item->is_intro() && $Item->status != 'published' ),
				'widget_item_visibility_badge_params'  => array(
						'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					),
			) );
			// ----------------------------- END OF "Item Single - Header" CONTAINER -----------------------------
		?>
	</header>
	
	<div class="row">
		<div class="col-sm-5">
		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before'              => $params['before_images'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'after'               => $params['after_images'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'before_gallery'      => $params['before_gallery'],
					'after_gallery'       => $params['after_gallery'],
					'gallery_table_start' => $params['gallery_table_start'],
					'gallery_table_end'   => $params['gallery_table_end'],
					'gallery_row_start'   => $params['gallery_row_start'],
					'gallery_row_end'     => $params['gallery_row_end'],
					'gallery_cell_start'  => $params['gallery_cell_start'],
					'gallery_cell_end'    => $params['gallery_cell_end'],
					'gallery_image_size'  => $params['gallery_image_size'],
					'gallery_image_limit' => $params['gallery_image_limit'],
					'gallery_colls'       => $params['gallery_colls'],
					'gallery_order'       => $params['gallery_order'],
					// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
					'restrict_to_image_position' => $params['image_positions'],
				) );
		?>
		</div>
		<div class="col-sm-7">
		<?php
			// Item Title:
			$Item->title( array(
					'before'    => $params['item_title_before'],
					'after'     => $params['item_title_after'],
					'link_type' => '#'
				) );

			// Item Content Teaser:
			$Item->content_teaser( array(
					'before'              => $params['before_content_teaser'],
					'after'               => $params['after_content_teaser'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'before_gallery'      => $params['before_gallery'],
					'after_gallery'       => $params['after_gallery'],
					'gallery_table_start' => $params['gallery_table_start'],
					'gallery_table_end'   => $params['gallery_table_end'],
					'gallery_row_start'   => $params['gallery_row_start'],
					'gallery_row_end'     => $params['gallery_row_end'],
					'gallery_cell_start'  => $params['gallery_cell_start'],
					'gallery_cell_end'    => $params['gallery_cell_end'],
					'gallery_image_size'  => $params['gallery_image_size'],
					'gallery_image_limit' => $params['gallery_image_limit'],
					'gallery_colls'       => $params['gallery_colls'],
					'gallery_order'       => $params['gallery_order'],
				) );

			// Tags:
			$Item->tags( array(
					'before'    => '<nav class="small post_tags">',
					'after'     => '</nav>',
					'separator' => ' ',
				) );

			// Custom fields: Course, Cuisine, Servings (if they exist for current Item):
			$Item->custom_fields( array(
					'fields'                               => 'course,cuisine,servings',
					'custom_fields_table_start'            => '',
					'custom_fields_row_start'              => '<div class="row"$row_attrs$>',
					'custom_fields_row_header_field'       => '<div class="col-xs-3 $header_cell_class$"><b>$field_title$$field_description_icon$</b></div>',
					'custom_fields_description_icon_class' => 'grey',
					'custom_fields_value_default'          => '<div class="col-xs-9 $data_cell_class$"$data_cell_attrs$>$field_value$</div>',
					'custom_fields_row_end'                => '</div>',
					'custom_fields_table_end'              => '',
				) );

			// Custom fields: Prep Time, Cook Time, Passive Time, Total time (if they exist for current Item):
			$Item->custom_fields( array(
					'fields'                               => 'prep_time,cook_time,passive_time,total_time',
					'custom_fields_table_start'            => '<br /><div class="row">',
					'custom_fields_row_start'              => '<span$row_attrs$>',
					'custom_fields_row_header_field'       => '<div class="col-sm-3 col-xs-6 $header_cell_class$"><b>$field_title$$field_description_icon$</b>',
					'custom_fields_description_icon_class' => 'grey',
					'custom_fields_value_default'          => '<br /><span class="$data_cell_class$"$data_cell_attrs$>$field_value$</span></div>',
					'custom_fields_row_end'                => '</span>',
					'custom_fields_table_end'              => '</div>',
					'hide_empty_lines'                     => true,
				) );
		?>
		</div>
	</div>

	<div class="row">
		<?php
		// Custom field "Ingredients" (if it exists for current Item):
		$ingredients = $Item->get_custom_field_formatted( 'ingredients' );
		if( $ingredients !== false )
		{	// Display "Ingredients" only if this custom field exists for the current Item:
		?>
		<div class="col-lg-3 col-sm-4">
			<h4><?php echo $Item->get_custom_field_title( 'ingredients' ); ?></h4>
			<p><?php echo $ingredients; ?></p>
		</div>
		<?php
			$directions_col_size = 'col-lg-9 col-sm-8';
		}
		else
		{	// Use full width if ingredients field is not detected:
			$directions_col_size = 'col-sm-12';
		}

		// Directions:
		?>
		<div class="<?php echo $directions_col_size; ?>">
			<h4><?php echo T_('Directions'); ?></h4>
			<?php
			// Display the "after more" part of the text: (part after "[teaserbreak]")
			$Item->content_extension( array(
					'before'              => $params['before_content_extension'],
					'after'               => $params['after_content_extension'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'force_more'          => true,
				) );

			// Links to post pages (for multipage posts):
			$Item->page_links( array(
					'before'      => $params['page_links_start'],
					'after'       => $params['page_links_end'],
					'separator'   => $params['page_links_separator'],
					'single'      => $params['page_links_single'],
					'current_page'=> $params['page_links_current_page'],
					'pagelink'    => $params['page_links_pagelink'],
					'url'         => $params['page_links_url'],
				) );
			?>
		</div>
	</div>

	<footer>
		<?php
			// Display Item footer text (text can be edited in Blog Settings):
			$Item->footer( array(
					'mode'        => $params['footer_text_mode'], // Will detect 'single' from $disp automatically
					'block_start' => $params['footer_text_start'],
					'block_end'   => $params['footer_text_end'],
				) );
		?>
		<nav class="post_comments_link">
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
					'type' => 'comments',
					'link_before' => '',
					'link_after' => '',
					'link_text_zero' => '#',
					'link_text_one' => '#',
					'link_text_more' => '#',
					'link_title' => '#',
					// fp> WARNING: creates problem on home page: 'link_class' => 'btn btn-default btn-sm',
					// But why do we even have a comment link on the home page ? (only when logged in)
				) );

			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
					'type' => 'trackbacks',
					'link_before' => ' &bull; ',
					'link_after' => '',
					'link_text_zero' => '#',
					'link_text_one' => '#',
					'link_text_more' => '#',
					'link_title' => '#',
				) );
		?>
		</nav>
	</footer>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( array(
				'disp_comments'        => true,
				'disp_comment_form'    => true,
				'disp_trackbacks'      => true,
				'disp_trackback_url'   => true,
				'disp_pingbacks'       => true,
				'disp_webmentions'     => true,
				'disp_meta_comments'   => false,
				'before_section_title' => '<div class="clearfix"></div><h3 class="evo_comment__list_title">',
				'after_section_title'  => '</h3>',
			), $params ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>
</article>

</div>
<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
}
?>