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
global $more;

// Default params:
$params = array_merge( array(
		'content_mode'        => 'auto',	// Can be 'excerpt', 'normal' or 'full'. 'auto' will auto select depending on backoffice SEO settings for $disp-detail
		'intro_mode'          => 'auto',	// same as above. This will typically be forced to "normal" when displaying an intro section so that intro posts always display as normal there
		'force_more'          => false,		// This will be set to true id 'content_mode' resolves to 'full'.
		'content_start_excerpt' => '<div class="content_excerpt">',
		'content_end_excerpt' => '</div>',
		'content_start_full'  => '<div class="content_full">',
		'content_end_full'    => '</div>',
		'before_images'       => '<div class="bImages">',
		'before_image'        => '<div class="image_block">',
		'before_image_legend' => '<div class="image_legend">',
		'after_image_legend'  => '</div>',
		'after_image'         => '</div>',
		'after_images'        => '</div>',
		'image_size'          => 'fit-400x320',
		'image_limit'         =>  1000,
		'image_link_to'       => 'original', // can be 'original', 'single' or empty
		'excerpt_image_size'  => 'fit-80x80',
		'excerpt_image_limit' =>  1,
		'excerpt_image_link_to'  => 'single',
		'before_url_link'     => '<p class="post_link">'.T_('Link:').' ',
		'after_url_link'      => '</p>',
		'url_link_text_template' => '$url$', // If evaluates to empty, nothing will be displayed (except player if podcast)
		'before_more_link'    => '<p class="bMore">',
		'after_more_link'     => '</p>',
		'more_link_text'      => '#',
		'anchor_text'         => '<p class="bMore">...</p>',		// text to display as the more anchor (once the more link has been clicked, '#' defaults to "Follow up:")
		'excerpt_before_text' => '<div class="excerpt">',
		'excerpt_after_text'  => '</div>',
		'excerpt_before_more' => ' <span class="excerpt_more">',
		'excerpt_after_more'  => '</span>',
		'excerpt_more_text'   => T_('more').' &raquo;',
		'limit_attach'        => 1000,
		'attach_list_start'   => '<div class="attachments"><h3>'.T_('Attachments').':</h3><ul class="bFiles">',
		'attach_list_end'     => '</ul></div>',
		'attach_start'        => '<li>',
		'attach_end'          => '</li>',
		'before_attach_size'  => ' <span class="file_size">',
		'after_attach_size'   => '</span>',
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
			$content_mode = $Blog->get_setting('chapter_content');
			break;

		case 'posts-tag':
			$content_mode = $Blog->get_setting('tag_content');
			break;

		case 'posts-date':
			$content_mode = $Blog->get_setting('archive_content');
			break;

		case 'posts-filtered':
			$content_mode = $Blog->get_setting('filtered_content');
			break;

		case 'posts-default':  // home page 1
		case 'posts-next':		 // next page 2, 3, etc
		default:
			$content_mode = $Blog->get_setting('main_content');
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
					'image_size' =>          $params['excerpt_image_size'],
					'limit' =>               $params['excerpt_image_limit'],
					'image_link_to' =>       $params['excerpt_image_link_to'],
					'restrict_to_image_position' => 'teaser',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
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
		$params['force_more'] = true;
		$params['anchor_text'] = '';
		/* continue down */
	case 'normal':
	default:
		// Full dislpay:
		echo $params['content_start_full'];

		// Increment view count of first post on page:
		$Item->count_view( array(
				'allow_multiple_counts_per_page' => false,
			) );

		if( ! empty($params['image_size']) )
		{
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              $params['before_images'],
					'before_image' =>        $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend' =>  $params['after_image_legend'],
					'after_image' =>         $params['after_image_legend'],
					'after' =>               $params['after_images'],
					'image_size' =>          $params['image_size'],
					'image' =>               $params['image_limit'],
					'image_link_to' =>       $params['image_link_to'],
					// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
					'restrict_to_image_position' => $Item->has_content_parts($params) ? 'teaser' : '',
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
						'anchor_text' => $params['anchor_text'],
					) );
				if( ! empty($params['image_size']) && $more && $Item->has_content_parts($params) /* only if not displayed all images already */ )
				{
					// Display images that are linked to this post:
					$Item->images( array(
							'before' =>              $params['before_images'],
							'before_image' =>        $params['before_image'],
							'before_image_legend' => $params['before_image_legend'],
							'after_image_legend' =>  $params['after_image_legend'],
							'after_image' =>         $params['after_image_legend'],
							'after' =>               $params['after_images'],
							'image_size' =>          $params['image_size'],
							'restrict_to_image_position' => 'aftermore',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
						) );
				}
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


		if( ! empty($params['limit_attach'])
			&& ( $more || ! $Item->has_content_parts($params) ) )
		{	// Display attachments/files that are linked to this post:
			$Item->files( array(
					'before' =>              $params['attach_list_start'],
					'before_attach' =>         $params['attach_start'],
					'before_attach_size' =>    $params['before_attach_size'],
					'after_attach_size' =>     $params['after_attach_size'],
					'after_attach' =>          $params['attach_end'],
					'after' =>               $params['attach_list_end'],
					'limit_attach' =>         $params['limit_attach'],
				) );
		}

		echo $params['content_end_full'];

}
/*
 * $Log$
 * Revision 1.29  2010/01/19 19:38:33  fplanque
 * minor
 *
 * Revision 1.28  2010/01/18 08:06:30  sam2kb
 * ~file renamed to ~attach
 *
 * Revision 1.27  2009/12/24 02:43:32  sam2kb
 * Added 'image_link_to' param to images()
 * See http://forums.b2evolution.net//viewtopic.php?t=20140
 *
 * Revision 1.26  2009/12/11 22:55:33  fplanque
 * Changing default of "Follow up:" to "..."
 *
 * Revision 1.25  2009/11/11 03:24:51  fplanque
 * misc/cleanup
 *
 * Revision 1.24  2009/11/10 02:44:38  fplanque
 * no message
 *
 * Revision 1.23  2009/11/04 04:34:16  sam2kb
 * Llimit the number of linked images displayed in excerpt and full modes
 *
 * Revision 1.22  2009/10/25 21:55:21  blueyed
 * Display attached images only once, if they are positioned as 'aftermore' and there is no 'more'.
 *
 * Revision 1.21  2009/10/11 03:00:11  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.20  2009/10/10 20:10:34  blueyed
 * Some refactoring in Item class.
 * Add get_content_parts, has_content_parts and hidden_teaser.
 * Apart from making the code more readable, this allows for more
 * abstraction in the future, e.g. not storing this in the posts itself.
 *
 * This takes us to the "do not display linked files with teaser" feature:
 * Attached images and files are not displayed with teasers anymore, but
 * only with the full post.
 *
 * Revision 1.19  2009/09/28 23:58:16  blueyed
 * whitespace
 *
 * Revision 1.18  2009/09/11 18:29:26  blueyed
 * Fix indent
 *
 * Revision 1.17  2009/05/22 06:35:58  sam2kb
 * minor
 *
 * Revision 1.16  2009/05/21 13:05:59  fplanque
 * doc + moved attachments below post in skins
 *
 * Revision 1.15  2009/05/21 12:34:39  fplanque
 * Options to select how much content to display (excerpt|teaser|normal) on different types of pages.
 *
 * Revision 1.14  2009/05/21 04:53:37  sam2kb
 * Display a list of files attached to post
 * See http://forums.b2evolution.net/viewtopic.php?t=18749
 *
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
