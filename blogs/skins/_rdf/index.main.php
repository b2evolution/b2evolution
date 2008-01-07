<?php
/**
 * This template generates an RSS 1.0 (RDF) feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://web.resource.org/rss/1.0/}
 *
 * @package evoskins
 * @subpackage rdf
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( '_core/_url.funcs.php' );

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

// What level of detail do we want?
$feed_content = $Blog->get_setting('feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	global $skins_path;
	require $skins_path.'_404_not_found.main.php';
	exit();
}


skin_content_header( 'application/xml' );	// Sets charset!

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';

?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel rdf:about="<?php $Blog->disp( 'url', 'xmlattr' ) ?>">
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
	<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
	<dc:language><?php $Blog->disp( 'locale', 'xml' ) ?></dc:language>
	<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
	<items>
		<rdf:Seq>
		<?php
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
			?>
			<rdf:li rdf:resource="<?php $Item->permanent_url( 'single' ) ?>"/>
		<?php } ?>
		</rdf:Seq>
	</items>
</channel>
<?php
$MainList->restart();
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	// Load Item's creator User:
	$Item->get_creator_User();
	?>

<item rdf:about="<?php $Item->permanent_url( 'single' ) ?>">
	<title><?php $Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
	<link><?php $Item->permanent_url( 'single' ) ?></link>
	<?php
		$Item->issue_date( array(
				'before'      => '<dc:date>',
				'after'       => '</dc:date>',
				'date_format' => 'isoZ',
   			'use_GMT'     => true,
			) );
	?>
	<dc:creator><?php $Item->creator_User->preferred_name( 'xml' ) ?></dc:creator>
	<dc:subject><?php $Item->main_category( 'xml' ) ?></dc:subject>
	<?php
		if( $feed_content == 'excerpt' )
		{	// EXCERPTS ---------------------------------------------------------------------
			?>
	<description><?php
		$content = $Item->get_excerpt( 'entityencoded' );

		// fp> this is another one of these "oooooh it's just a tiny little change"
		// and "we only need to make the links absolute in RSS"
		// and then you get half baked code! The URL LINK stays RELATIVE!! :((
		// TODO: clean solution : work in format_to_output!
		echo make_rel_links_abs( $content );
	?></description>
	<content:encoded><![CDATA[<?php
		// Display images that are linked to this post:
		$content = $Item->get_excerpt( 'htmlbody' );

		// fp> this is another one of these "oooooh it's just a tiny little change"
		// and "we only need to make the links absolute in RSS"
		// and then you get half baked code! The URL LINK stays RELATIVE!! :((
		// TODO: clean solution : work in format_to_output! --- we probably need 'htmlfeed' as 'htmlbody+absolute'
		echo make_rel_links_abs( $content );
	?>]]></content:encoded>
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
			), 'entityencoded' );

		$content .= $Item->get_content_teaser( 1, false, 'entityencoded' );

		if( $feed_content == 'normal' )
		{	// Teasers only
			$content .= $Item->get_more_link( array(
					'before'    => '',
					'after'     => '',
					'disppage'  => 1,
					'format'    => 'entityencoded',
				) );
		}
		else
		{	// Full contents
			$content .= $Item->get_content_extension( 1, true, 'entityencoded' );
		}

		// fp> this is another one of these "oooooh it's just a tiny little change"
		// and "we only need to make the links absolute in RSS"
		// and then you get half baked code! The URL LINK stays RELATIVE!! :((
		// TODO: clean solution : work in format_to_output!
		echo make_rel_links_abs( $content );
	?></description>
	<content:encoded><![CDATA[<?php
		// URL link, if the post has one:
		$Item->url_link( array(
				'before'        => '<p>',
				'after'         => '</p>',
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
	?>]]></content:encoded>
			<?php
		}
	?>
</item>
<?php
}
?>
</rdf:RDF>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>