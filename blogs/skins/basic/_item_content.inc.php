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
				'image_size' =>          'fit-400x320',
			) );

		// Links to post pages (for multipage posts):
		$Item->page_links( array(
				'before'      => '<p class="right">'.T_('Pages:').' ',
				'separator'   => ' &middot; ',
			) );

		// Display Item footer text (text can be edited in Blog Settings):
		$Item->footer( array(
				'mode'        => '#',				// Will detect 'single' from $disp automatically
				'block_start' => '<div class="item_footer">',
				'block_end'   => '</div>',
			) );

		// Display location info
		$Item->location( '<div class="item_location"><strong>'.T_('Location').': </strong>', '</div>' );

		if( $disp == 'single' )
		{	// Display custom fields
			$Item->custom_fields();
		}
	?>
</div>
