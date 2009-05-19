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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp_detail;

// Default params:
$params = array_merge( array(
		'content_mode'        => 'auto',	// Can be 'excerpt' or 'full'. 'auto' will auto select depending on $disp-detail
		'content_start_excerpt' => '<div class="content_excerpt">',
		'content_end_excerpt' => '</div>',
		'content_start_full'  => '<div class="content_full">',
		'content_end_full' => '</div>',
		'before_images'       => '<div class="bImages">',
		'before_image'        => '<div class="image_block">',
		'before_image_legend' => '<div class="image_legend">',
		'after_image_legend'  => '</div>',
		'after_image'         => '</div>',
		'after_images'        => '</div>',
		'image_size'	        => 'fit-400x320',
		'excerpt_image_size'	=> 'fit-80x80',
		'before_url_link'     => '<p class="post_link">'.T_('Link:').' ',
		'after_url_link'      => '</p>',
		'url_link_text_template' => '$url$',
		'force_more'  				=> false,
		'before_more_link'    => '<p class="bMore">',
		'after_more_link'     => '</p>',
		'more_link_text'      => '#',
		'excerpt_before_text' => '<div class="excerpt">',
		'excerpt_after_text'  => '</div>',
		'excerpt_before_more' => ' <span class="excerpt_more">',
		'excerpt_after_more'  => '</span>',
		'excerpt_more_text'   => T_('more').' &raquo;',
	), $params );


// Determine content mode to use..
$content_mode = $params['content_mode'];
if( $content_mode == 'auto' )
{
	// echo $disp_detail;
	switch( $disp_detail )
	{
		case 'posts-cat':
		case 'posts-tag':
		case 'posts-date':

		case 'posts-filtered': // This one feels a bit risky to put to 'excerpt' by default... so remove it if controversy. However this is the mode for search results... so it kind amakes sense to have reduced results.

			// fp> TODO: there should be (SEO) settings in the backoffice to easily enable/disable except mode or each disp type
			// (right now it requires changing the content_mode param in the skin)

			// fp> note that at the same time it woudl be useful to have individual (for each disp type) settings for $posts aka number of results per page

			// Reduced/excerpt display:
			$content_mode = 'excerpt';
			break;


		case 'posts-default':  // home page 1
		case 'posts-next':		 // next page 2, 3, etc
		default:
			$content_mode = 'full';
			// Regular/"full" post display:
	}
}

// echo $content_mode;

switch( $content_mode )
{
	case 'excerpt':
		// Reduced display:
		echo $params['content_start_excerpt'];

		if( !empty($params['excerpt_image_size']) )
		{
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              $params['before_images'],
					'before_image' =>        $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend' =>  $params['after_image_legend'],
					'after_image' =>         $params['after_image_legend'],
					'after' =>               $params['after_images'],
					'image_size' =>					 $params['excerpt_image_size'],
					'image_link_to' =>       'single',
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
	default:
		// Full dislpay:
		echo $params['content_start_full'];

		// Increment view count of first post on page:
		$Item->count_view( array(
				'allow_multiple_counts_per_page' => false,
			) );

		if( !empty($params['image_size']) )
		{
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              $params['before_images'],
					'before_image' =>        $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend' =>  $params['after_image_legend'],
					'after_image' =>         $params['after_image_legend'],
					'after' =>               $params['after_images'],
					'image_size' =>					 $params['image_size'],
				) );
		}

		?>
		<div class="bText">
			<?php

				// URL link, if the post has one:
				$Item->url_link( array(
						'before'        => $params['before_url_link'],
						'after'         => $params['after_url_link'],
						'text_template' => $params['url_link_text_template'],
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
						'force_more'  => $params['force_more'],
						'before'      => $params['before_more_link'],
						'after'       => $params['after_more_link'],
						'link_text'   => $params['more_link_text'],
					) );
				$Item->content_extension( array(
						'before'      => '',
						'after'       => '',
						'force_more'  => $params['force_more'],
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
		echo $params['content_end_full'];

}
/*
 * $Log$
 * Revision 1.13  2009/05/19 14:34:32  fplanque
 * Category, tag, archive and serahc page snow only display post excerpts by default. (Requires a 3.x skin; otherwise the skin will display full posts as before). This can be controlled with the ''content_mode'' param in the skin tags.
 *
 * Revision 1.12  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.11  2009/01/21 23:30:12  fplanque
 * feature/intro posts display adjustments
 *
 * Revision 1.10  2008/09/15 10:44:17  fplanque
 * skin cleanup
 *
 * Revision 1.9  2008/05/26 19:22:07  fplanque
 * fixes
 *
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
