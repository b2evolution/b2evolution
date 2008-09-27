<?php
/**
 * This template generates an Atom feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
 *
 * @package evoskins
 * @subpackage atom
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// EXTERNAL FEED PROVIDER?
$atom_redirect = $Blog->get_setting('atom_redirect');
if (!empty($atom_redirect))
{
	if( $redir == 'yes' )
	{
		header_redirect( $atom_redirect, 301 );
		exit(0);
	}
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

if( $debug)
{
	skin_content_header( 'application/xml' );	// Sets charset!
}
else
{
	skin_content_header( 'application/atom+xml' );	// Sets charset!
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
				$content = $Item->get_excerpt( 'entityencoded' );

				// fp> this is another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output!
				echo make_rel_links_abs( $content );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlbody',
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
						'before'        => '<p>',
						'after'         => '</p>',
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
						'image_size' =>          'fit-320x320'
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

				// fp> this is another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output! --- we probably need 'htmlfeed' as 'htmlbody+absolute'
				echo make_rel_links_abs( $content );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlbody',
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
