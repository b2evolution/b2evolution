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
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * @subpackage glossyblue
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

<div class="entry">
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
	?>
</div>