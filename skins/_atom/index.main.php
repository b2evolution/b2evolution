<?php
/**
 * This template generates an Atom feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
 *
 * @package evoskins
 * @subpackage atom
 *
 * @todo dh> isn't this missing a call to skin_init()!? - if so, other feeds are missing it, too. fp> no this is very much on purpose. there is a comment somewhere (don't know exactly)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// EXTERNAL FEED PROVIDER?
$atom_redirect = $Blog->get_setting( 'atom_redirect' );
if ( ! empty( $atom_redirect ) && empty( $Chapter ) && $redir == 'yes' )
{
	header_redirect( $atom_redirect, 301 );
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


if($debug)
{
	headers_content_mightcache( 'application/xml' );		// In most situations, you do NOT want to cache dynamic content!
}
else
{
	headers_content_mightcache( 'application/atom+xml' );		// In most situations, you do NOT want to cache dynamic content!
}


echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<feed xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://www.w3.org/2005/Atom">
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
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'url', 'xml' ) ?>" />
	<link rel="self" type="application/atom+xml" href="<?php $Blog->disp( 'atom_url', 'xmlattr' ) ?>" />
	<id><?php $Blog->disp( 'atom_url', 'xmlattr' ); /* TODO: may need a regenerate_url() */ ?></id>
	<subtitle><?php $Blog->disp( 'shortdesc', 'xml' ) ?></subtitle>
	<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></updated>
	<?php
	while( $Item = & mainlist_get_item() )
	{	// For each blog post, do everything below up to the closing curly brace "}"
		// Load Item's creator User:
		$Item->get_creator_User();
		?>

	<entry>
		<title type="text"><?php $Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
		<link rel="alternate" type="text/html" href="<?php $Item->permanent_url( 'single' ) ?>" />
		<author>
			<name><?php $Item->creator_User->preferred_name( 'xml' ) ?></name>
			<?php $Item->creator_User->url( '<uri>', "</uri>\n", 'xml' ) ?>
		</author>
		<?php
		$Chapters = $Item->get_Chapters();
		foreach ( $Chapters as $Chapter )
		{
			// walter> if adding categories in the feed for all categories is expensive,
			// we can add it just for the main category
			//  $Chapter = $Item->get_main_Chapter();
			//  $cat_name = $Chapter->dget( 'name', 'xmlattr' );
			$cat_name = $Chapter->dget( 'name', 'xmlattr' );
		?>
		<category term="<?php echo $cat_name; ?>" />
		<?php
		}
		?>
		<id><?php $Item->permanent_url( 'single' ) ?></id>
		<?php
			$Item->issue_date( array(
					'before'      => '<published>',
					'after'       => '</published>',
					'date_format' => 'isoZ',
					'use_GMT'     => true,
				) );
		?>
		<updated><?php $Item->mod_date( 'isoZ', true ) ?></updated>
		<?php
			if( $feed_content == 'excerpt' )
			{	// EXCERPTS ---------------------------------------------------------------------
				?>
		<content type="html"><![CDATA[<?php
				echo $Item->get_excerpt('htmlfeed');

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlfeed',
					) );
		?>]]></content>
				<?php
			}
			elseif( $feed_content == 'normal'
						|| $feed_content == 'full' )
			{	// POST CONTENTS -----------------------------------------------------------------
				?>
		<content type="html"><![CDATA[<?php
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
						'image_size' =>          $image_size,
					), 'htmlfeed' );

				$Item->content_teaser( array(
						'disppage'            => 1,
						'stripteaser'         => false,
						'format'              => 'htmlfeed',
						'before_image'        => '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend'  => '</i></div>',
						'after_image'         => '</div>',
						'image_size'          => $image_size,
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
		?>]]></content>
			<?php
		}
	?>
	</entry>

	<?php
	}
	?>
</feed>
