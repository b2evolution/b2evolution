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
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Display images that are linked to this post:
$Item->images( array(
		'before' =>              '<table cellspacing="5">',
		'before_image' =>        '<tr><td align="center">',
		'before_image_legend' => '<br><small>',
		'after_image_legend' =>  '</small>',
		'after_image' =>         '</td></tr>',
		'after' =>               '</table>',
		'image_size' =>          'fit-400x320'
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
		$Item->content_extension( array(
				'before'      => '',
				'after'       => '',
			) );

		// Links to post pages (for multipage posts):
		$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
	?>
</div>

<?php
/*
 * $Log$
 * Revision 1.2  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.1  2007/06/23 22:09:30  fplanque
 * feedback and item content templates.
 * Interim check-in before massive changes ahead.
 *
 */
?>
