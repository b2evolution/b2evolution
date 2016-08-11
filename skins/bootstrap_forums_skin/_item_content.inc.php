<?php
/**
 * This is the template that displays the contents for a post (images, teaser, more link, body, etc...)
 * It's typically called by the item_block template.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp_detail;
global $more;

// Default params:
$params = array_merge( array(
		'content_mode'             => 'auto', // Can be 'excerpt', 'normal' or 'full'. 'auto' will auto select depending on backoffice SEO settings for $disp-detail
		'intro_mode'               => 'auto', // Same as above. This will typically be forced to "normal" when displaying an intro section so that intro posts always display as normal there
		'force_more'               => false, // This will be set to true id 'content_mode' resolves to 'full'.

		'content_display_full'     => true, // Do we want to display all post content? false to display only images/attachments

		// Wrap images and text:
		'content_start_excerpt'    => '<section class="evo_post__excerpt">',		// In case of compact display
		'content_end_excerpt'      => '</section>',
		'content_start_full'       => '<section class="evo_post__full">',			// In case of full display
		'content_end_full'         => '</section>',

		// In case we display a compact version of the post:
		'excerpt_before_text'      => '<div class="evo_post__excerpt_text">',
		'excerpt_after_text'       => '</div>',

		'excerpt_before_more'      => ' <span class="evo_post__excerpt_more_link">',
		'excerpt_after_more'       => '</span>',
		'excerpt_more_text'        => T_('more').' &raquo;',

		// In case we display a full version of the post:
		'content_start_full_text'  => '<div class="evo_post__full_text clearfix">',
		'content_end_full_text'    => '</div>',

		'before_content_teaser'    => '',
		'after_content_teaser'     => '',
		'before_content_extension' => '',
		'after_content_extension'  => '',

		'before_images'            => '<div class="evo_post_images raised">',
		'before_image'             => '<figure class="evo_image_block raised">',
		'before_image_legend'      => '<figcaption class="evo_image_legend">',
		'after_image_legend'       => '</figcaption>',
		'after_image'              => '</figure>',
		'after_images'             => '</div>',
		'image_class'              => 'img-responsive',
		'image_size'               => 'fit-1280x720',
		'image_limit'              =>  1000,
		'image_link_to'            => 'original', // Can be 'original', 'single' or empty
		'excerpt_image_class'      => '',
		'excerpt_image_size'       => 'fit-80x80',
		'excerpt_image_limit'      => 0,
		'excerpt_image_link_to'    => 'single',
		'include_cover_images'     => false, // Set to true if you want cover images to appear with teaser images.

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

		'before_url_link'          => '<p class="evo_post_link">'.T_('Link:').' ',
		'after_url_link'           => '</p>',
		'url_link_text_template'   => '$url$', // If evaluates to empty, nothing will be displayed (except player if podcast)
		'url_link_url_template'    => '$url$', // $url$ will be replaced with saved URL address
		'url_link_target'          => '', // Link target attribute e.g. '_blank'

		'parent_link_before'       => '<p class="evo_post_parent">'.T_('Parent').': ',
		'parent_link_after'        => '</p>',
		'parent_link_not_found'    => '<i>'.T_('Item not found.').'</i>',

		'before_more_link'         => '<p class="evo_post_more_link">',
		'after_more_link'          => '</p>',
		'more_link_text'           => '#',
		'more_link_to'             => 'single#anchor', // Can be 'single' or 'single#anchor' which is permalink + "#more55" where 55 is item ID
		'anchor_text'              => '<p class="evo_post_more_anchor">...</p>', // Text to display as the more anchor (once the more link has been clicked, '#' defaults to "Follow up:")

		'limit_attach'             => 1000,
		'attach_list_start'        => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
		'attach_list_end'          => '</ul></div>',
		'attach_start'             => '<li class="evo_file">',
		'attach_end'               => '</li>',
		'before_attach_size'       => ' <span class="evo_file_size">(',
		'after_attach_size'        => ')</span>',

		'page_links_start'         => '<p class="evo_post_pagination">'.T_('Pages:').' ',
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

// Determine content mode to use..
if( $Item->is_intro() )
{
	$content_mode = $params['intro_mode'];
}
else
{
	$content_mode = $params['content_mode'];
}
if( $content_mode == 'auto' )
{
	// echo $disp_detail;
	switch( $disp_detail )
	{
		case 'posts-cat':
		case 'posts-subcat':
			$content_mode = $Blog->get_setting('chapter_content');
			break;

		case 'posts-tag':
			$content_mode = $Blog->get_setting('tag_content');
			break;

		case 'posts-date':
			$content_mode = $Blog->get_setting('archive_content');
			break;

		case 'posts-filtered':
		case 'search':
			$content_mode = $Blog->get_setting('filtered_content');
			break;

		case 'single':
		case 'page':
			$content_mode = 'full';
			break;

		case 'posts-default':  // home page 1
		case 'posts-next':     // next page 2, 3, etc
		default:
			$content_mode = $Blog->get_setting('main_content');
	}
}

if( $params['include_cover_images'] )
{ // Include the cover images on teaser place
	$teaser_image_positions = 'cover,teaser,teaserperm,teaserlink';
}
else
{ // Don't include the cover images
	$teaser_image_positions = 'teaser,teaserperm,teaserlink';
}

switch( $content_mode )
{
	case 'excerpt':
		// Compact display:
		echo $params['content_start_excerpt'];

		if( !empty($params['excerpt_image_size']) && !empty($params['excerpt_image_limit']) )
		{
			// Display images that are linked to this post:
			$Item->images( array(
					'before'              => $params['before_images'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'after'               => $params['after_images'],
					'image_class'         => $params['excerpt_image_class'],
					'image_size'          => $params['excerpt_image_size'],
					'limit'               => $params['excerpt_image_limit'],
					'image_link_to'       => $params['excerpt_image_link_to'],
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
					'restrict_to_image_position' => $teaser_image_positions,
				) );
		}

		$Item->excerpt( array(
			'before'              => $params['excerpt_before_text'],
			'after'               => $params['excerpt_after_text'],
			'excerpt_before_more' => $params['excerpt_before_more'],
			'excerpt_after_more'  => $params['excerpt_after_more'],
			'excerpt_more_text'   => $params['excerpt_more_text'],
			) );

		echo $params['content_end_excerpt'];
		break;

	case 'full':
		// Full display:
		$params['force_more'] = true;
		$params['anchor_text'] = '';
		/* continue down */
	case 'normal':
	default:
		// Normal dislpay:  (and Full display if force_more is true)
		echo $params['content_start_full'];

		if( ! empty($params['image_size']) )
		{
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
					'restrict_to_image_position' => $teaser_image_positions,
				) );
		}

		if( $params['content_display_full'] )
		{	// We want to display text, not just images:

			echo $params['content_start_full_text'];

			// URL link, if the post has one:
			$Item->url_link( array(
					'before'        => $params['before_url_link'],
					'after'         => $params['after_url_link'],
					'text_template' => $params['url_link_text_template'],
					'url_template'  => $params['url_link_url_template'],
					'target'        => $params['url_link_target'],
					'podcast'       => '#', // Auto display mp3 player if post type is podcast (=> false, to disable)
				) );

			// Parent link, if the post has one:
			$Item->parent_link( array(
					'before'         => $params['parent_link_before'],
					'after'          => $params['parent_link_after'],
					'not_found_text' => $params['parent_link_not_found'],
				) );

			// Display CONTENT (at least the TEASER part):
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
				) );

			// Display either the "Read more"/"Full story" link OR the #anchor for #direct linking to the "after more" part:
			$Item->more_link( array(
					'force_more'  => $params['force_more'],
					'before'      => $params['before_more_link'],
					'after'       => $params['after_more_link'],
					'link_text'   => $params['more_link_text'],		// text to display as the more link
					'anchor_text' => $params['anchor_text'],			// text to display as the more anchor (once the more link has been clicked, # defaults to "Follow up:")
					'link_to'     => $params['more_link_to'],
				) );

			if( ! empty( $params['image_size'] ) && ( $more || $params['force_more'] ) && $Item->has_content_parts( $params ) /* only if not displayed all images already */ )
			{	// Display images that are linked "after more" to this post:
				$Item->images( array(
						'before'              => $params['before_images'],
						'before_image'        => '<figure class="evo_image_block raised">',
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
						'restrict_to_image_position' => 'aftermore',
					) );
			}

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
					'force_more'          => $params['force_more'],
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

			// Display Item footer text (text can be edited in Blog Settings):
			$Item->footer( array(
					'mode'        => $params['footer_text_mode'], // Will detect 'single' from $disp automatically
					'block_start' => $params['footer_text_start'],
					'block_end'   => $params['footer_text_end'],
				) );

			echo $params['content_end_full_text'];
		}

		if( ! empty($params['limit_attach'])
			&& ( $more || ! $Item->has_content_parts($params) ) )
		{	// Display attachments/files that are linked to this post:
			$Item->files( array(
					'before' =>              $params['attach_list_start'],
					'before_attach' =>       $params['attach_start'],
					'before_attach_size' =>  $params['before_attach_size'],
					'after_attach_size' =>   $params['after_attach_size'],
					'after_attach' =>        $params['attach_end'],
					'after' =>               $params['attach_list_end'],
					'limit_attach' =>        $params['limit_attach'],
				) );
		}

		// Display location info
		$Item->location( '<div class="evo_post_location"><strong>'.T_('Location').': </strong>', '</div>' );

		if( $disp == 'single' )
		{	// Display custom fields
			$Item->custom_fields();
		}

		echo $params['content_end_full'];

}
?>