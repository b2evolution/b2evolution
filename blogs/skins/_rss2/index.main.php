<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @todo iTunes podcast tags: http://www.apple.com/itunes/store/podcaststechspecs.html
 * Note: itunes support: .m4a, .mp3, .mov, .mp4, .m4v, and .pdf.
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id: index.main.php 3157 2013-03-06 04:34:44Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// EXTERNAL FEED PROVIDER?
$rss2_redirect = $Blog->get_setting( 'rss2_redirect' );
if ( ! empty( $rss2_redirect ) && empty( $Chapter ) && $redir == 'yes' )
{
	header_redirect( $rss2_redirect, 301 );
	exit( 0 );
}


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

// Add caching headers
header('Last-Modified: '.$MainList->get_lastpostdate('r'));
header('Expires: '.date('r', time() + 300)); // TODO: dh> should be a centralized setting. Maybe through the Skin class, if type is "feed"?


echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:atom="http://www.w3.org/2005/Atom">
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
		<atom:link rel="self" type="application/rss+xml" href="<?php $Blog->disp( 'rss2_url', 'xmlattr' ); ?>" />
		<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
		<ttl>60</ttl>
		<?php
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
			?>
		<item>
			<title><?php $Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
			<link><?php $Item->permanent_url( 'single' ) ?></link>
			<?php
				$Item->issue_date( array(
						'before'      => '<pubDate>',
						'after'       => '</pubDate>',
						'date_format' => 'r',
   					'use_GMT'     => true,
					) );
			?>
			<dc:creator><?php $Item->get_creator_User(); $Item->creator_User->preferred_name('xml') ?></dc:creator>
			<?php
				$Item->categories( array(
					'before'          => '',
					'after'           => '',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'before_main'     => '<category domain="main">',
					'after_main'      => '</category>',
					'before_other'    => '<category domain="alt">',
					'after_other'     => '</category>',
					'before_external' => '<category domain="external">',
					'after_external'  => '</category>',
					'link_categories' => false,
					'separator'       => "\n",
					'format'          => 'htmlbody', // TODO: "xml" eats away the tags!!
				) );
			?>
			<guid isPermaLink="false"><?php $Item->ID() ?>@<?php echo $baseurl ?></guid>
			<?php
				// PODCAST ------------------------------------------------------------------------
				if( $Item->ptyp_ID == 2000 )
				{	// This is a podcast Item !
					echo '<enclosure url="'.$Item->url.'" />';
					// TODO: add length="12216320" type="audio/mpeg"
				}

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
			<content:encoded><![CDATA[<?php
				echo $Item->get_excerpt( 'htmlfeed' );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlfeed',
					) );
			?>]]></content:encoded>
					<?php

				}
				elseif( $feed_content == 'normal'
							|| $feed_content == 'full' )
				{	// POST CONTENTS -----------------------------------------------------------------

					?>
			<description><?php
				// URL link, if the post has one: (TODO: move below the text, because in summaries or podcasts it blows to have this on top)
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
			<content:encoded><![CDATA[<?php
				// URL link, if the post has one:
				$Item->url_link( array(
						'before'    => '<p>',
						'after'     => '</p>',
						'podcast'   => false,
						'format'    => 'htmlfeed',
					) );

				// Display images that are linked to this post:
				$Item->images( array(
						'before' =>              '<div>',
						'before_image' =>        '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend' =>  '</i></div>',
						'after_image' =>         '</div>',
						'after' =>               '</div>',
						'image_size' =>          'fit-320x320'
					), 'htmlfeed' );

				$Item->content_teaser( array(
						'disppage'            => 1,
						'stripteaser'         => false,
						'format'              => 'htmlfeed',
						'before_image'        => '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend'  => '</i></div>',
						'after_image'         => '</div>',
						'image_size'          => 'fit-320x320',
					) );

				if( $feed_content == 'normal' )
				{	// Teasers only
					$Item->more_link( array(
							'before'    => '',
							'after'     => '',
							'disppage'  => 1,
							'format'    => 'htmlfeed',
						) );
				}
				else
				{	// Full contents
					$Item->content_extension( array(
							'disppage'    => 1,
							'force_more'  => true,
							'format'      => 'htmlfeed',
						) );
				}

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlfeed',
					) );
			?>]]></content:encoded>
					<?php
				}
			?>
			<comments><?php echo $Item->get_single_url( 'auto' ); ?>#comments</comments>
			<wfw:commentRss><?php echo format_to_output( $Item->get_feedback_feed_url( '_rss2' ), 'xml' ); ?></wfw:commentRss>
		</item>
		<?php
		}
		?>
	</channel>
</rss>
