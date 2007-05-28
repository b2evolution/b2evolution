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

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

// What level of detail do we want?
$feed_content = $Blog->get_setting('feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	global $view_path;
	require $view_path.'errors/_404_not_found.page.php';
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
		request_title( ' - ', '', ' - ', 'xml' );
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
		<?php while( $Item = & $MainList->get_item() ) { ?>
			<rdf:li rdf:resource="<?php $Item->permanent_url( 'single' ) ?>"/>
		<?php } ?>
		</rdf:Seq>
	</items>
</channel>
<?php
$MainList->restart();
while( $Item = & $MainList->get_item() )
{
	// Load Item's creator User:
	$Item->get_creator_User();
	?>

<item rdf:about="<?php $Item->permanent_url( 'single' ) ?>">
	<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
	<link><?php $Item->permanent_url( 'single' ) ?></link>
	<dc:date><?php $Item->issue_date( 'isoZ', true ) ?></dc:date>
	<dc:creator><?php $Item->creator_User->preferred_name( 'xml' ) ?></dc:creator>
	<dc:subject><?php $Item->main_category( 'xml' ) ?></dc:subject>
	<?php
		if( $feed_content == 'excerpt' )
		{
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
		elseif( $feed_content == 'normal' )
		{
			?>
	<description><?php
		// fp> TODO: make a clear decision on wether or not $before &nd $after get formatted to output or not.
		$Item->url_link( '&lt;p&gt;', '&lt;/p&gt;', '%s', array(), 'entityencoded' );

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

		$content .= $Item->get_more_link( '', '', '#', '', 1, 'entityencoded' );

		// fp> this is another one of these "oooooh it's just a tiny little change"
		// and "we only need to make the links absolute in RSS"
		// and then you get half baked code! The URL LINK stays RELATIVE!! :((
		// TODO: clean solution : work in format_to_output!
		echo make_rel_links_abs( $content );
	?></description>
	<content:encoded><![CDATA[<?php
		$Item->url_link( '<p>', '</p>' );
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

		$content .= $Item->get_more_link( '', '', '#', '', 1 );

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