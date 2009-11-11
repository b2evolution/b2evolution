<?php
/**
 * This is the template that displays the contents for a post
 * (images, teaser, more link, body, etc...)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * @package evoskins
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $more;

// Display images that are linked to this post:
$Item->images( array(
		'before' =>              '<table cellspacing="5">',
		'before_image' =>        '<tr><td align="center">',
		'before_image_legend' => '<br><small>',
		'after_image_legend' =>  '</small>',
		'after_image' =>         '</td></tr>',
		'after' =>               '</table>',
		'image_size' =>          'fit-400x320',
		// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
		'restrict_to_image_position' => $Item->has_content_parts($params) ? 'teaser' : '',
	) );
?>

<div>
	<?php
		// Increment view count of first post on page:
		$Item->count_view( array(
				'allow_multiple_counts_per_page' => false,
			) );

		// Display CONTENT:
		$Item->content_teaser( array(
				'before'      => '',
				'after'       => '',
			) );
		$Item->more_link();

		if( $more )
		{	// Display images that are linked after "more" in this post:
			$Item->images( array(
					'before' =>              '<table cellspacing="5">',
					'before_image' =>        '<tr><td align="center">',
					'before_image_legend' => '<br><small>',
					'after_image_legend' =>  '</small>',
					'after_image' =>         '</td></tr>',
					'after' =>               '</table>',
					'image_size' =>          'fit-400x320',
					'restrict_to_image_position' => 'aftermore',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
				) );
		}
		$Item->content_extension( array(
				'before'      => '',
				'after'       => '',
			) );

		// Links to post pages (for multipage posts):
		$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );

		// Display Item footer text (text can be edited in Blog Settings):
		$Item->footer( array(
				'mode'        => '#',				// Will detect 'single' from $disp automatically
				'block_start' => '<div class="item_footer">',
				'block_end'   => '</div>',
			) );
	?>
</div>
