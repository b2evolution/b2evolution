<?php
/**
 * This template generates an RSS 0.92 feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id: index.main.php 3157 2013-03-06 04:34:44Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

// What level of detail do we want?
$feed_content = $Blog->get_setting('feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	// This will normaly have been detected earlier but just for security:
	debug_die( 'Feeds are disabled.');
}

$image_size = $Blog->get_setting( 'image_size' );

headers_content_mightcache( 'application/xml' );		// In most situations, you do NOT want to cache dynamic content!

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';

?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rss version="0.92">
	<channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
			request_title( array(
					'title_before'=> ' - ',
					'title_after' => '',
					'title_none'  => '',
					'glue'        => ' - ',
					'title_single_disp' => true,
					'format'      => 'xml',
				) );
			// ------------------------------ END OF REQUEST TITLE -----------------------------
		?></title>
		<link><?php $Blog->disp( 'url', 'xml' ) ?></link>
		<description><?php $Blog->disp( 'shortdesc' ,'xml' ) ?></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<?php
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
		?>
		<item>
			<title><?php $Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
			<?php
				if( $feed_content == 'excerpt' )
				{	// EXCERPTS ---------------------------------------------------------------------
					?>
			<description><?php
				$content = $Item->get_excerpt();

				// Get content as "htmlbody", otherwise make_rel_links_abs() can't catch <a> and <img> tags
				// TODO: clean solution : work in format_to_output!
				echo format_to_output( make_rel_links_abs($content), 'entityencoded' );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'entityencoded',
					) );
			?></description>
					<?php
				}
				elseif( $feed_content == 'normal'
							|| $feed_content == 'full' )
				{	// POST CONTENTS -----------------------------------------------------------------
					?>
			<description><?php
				// URL link, if the post has one:
				$Item->url_link( array(
						'before'        => '<p>',
						'after'         => '</p>',
						'format'        => 'entityencoded',
						'podcast'       => false,
					) );

				// Display images that are linked to this post:
				$content = $Item->get_images( array(
						'before' =>              '<div>',
						'before_image' =>        '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend' =>  '</i></div>',
						'after_image' =>         '</div>',
						'after' =>               '</div>',
						'image_size' =>          $image_size,
					), 'htmlbody' );

				$content .= $Item->get_content_teaser( 1, false );

				if( $feed_content == 'normal' )
				{	// Teasers only
					$content .= $Item->get_more_link( array(
							'before'    => '',
							'after'     => '',
							'disppage'  => 1,
						) );
				}
				else
				{	// Full contents
					$content .= $Item->get_content_extension( 1, true );
				}

				// Get content as "htmlbody", otherwise make_rel_links_abs() can't catch <a> and <img> tags
				// TODO: clean solution : work in format_to_output!
				echo format_to_output( make_rel_links_abs($content), 'entityencoded' );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'entityencoded',
					) );
			?></description>
			<link><?php $Item->permanent_url( 'single' ) ?></link>
					<?php
				}
			?>
		</item>
		<?php
		}
		?>
	</channel>
</rss>
