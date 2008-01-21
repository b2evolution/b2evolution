<?php
/**
 * This is the template that displays the contents for a post
 * (images, teaser, more link, body, etc...)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'image_size'	     => 'fit-400x320',
		'before_url_link'  => '<p class="post_link">'.T_('Link:').' ',
		'after_url_link'   => '</p>',
		'before_more_link' => '<p class="bMore">',
		'after_more_link'  => '</p>',
		'more_link_text'   => '#',
	), $params );


if( !empty($params['image_size']) )
{
	// Display images that are linked to this post:
	$Item->images( array(
			'before' =>              '<div class="bImages">',
			'before_image' =>        '<div class="image_block">',
			'before_image_legend' => '<div class="image_legend">',
			'after_image_legend' =>  '</div>',
			'after_image' =>         '</div>',
			'after' =>               '</div>',
			'image_size' =>					 $params['image_size'],
		) );
}
?>

<div class="bText">
	<?php
		// Increment view count of first post on page:
		$Item->count_view( array(
				'allow_multiple_counts_per_page' => false,
			) );

		// URL link, if the post has one:
		$Item->url_link( array(
				'before'        => $params['before_url_link'],
				'after'         => $params['after_url_link'],
				'text_template' => '$url$',
				'url_template'  => '$url$',
				'target'        => '',
				'podcast'       => '#',        // auto display mp3 player if post type is podcast (=> false, to disable)
			) );

		// Display CONTENT:
		$Item->content_teaser( array(
				'before'      => '',
				'after'       => '',
			) );
		$Item->more_link( array(
				'before'    => $params['before_more_link'],
				'after'     => $params['after_more_link'],
				'link_text' => $params['more_link_text'],
			) );
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

<?php
/*
 * $Log$
 * Revision 1.8  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/17 14:38:32  fplanque
 * Item Footer template tag
 *
 * Revision 1.6  2008/01/08 03:31:51  fplanque
 * podcast support
 *
 * Revision 1.5  2007/11/29 20:53:45  fplanque
 * Fixed missing url link in basically all skins ...
 *
 * Revision 1.4  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.3  2007/09/28 02:18:10  fplanque
 * minor
 *
 * Revision 1.2  2007/06/24 01:05:31  fplanque
 * skin_include() now does all the template magic for skins 2.0.
 * .disp.php templates still need to be cleaned up.
 *
 * Revision 1.1  2007/06/23 22:09:29  fplanque
 * feedback and item content templates.
 * Interim check-in before massive changes ahead.
 *
 */
?>
