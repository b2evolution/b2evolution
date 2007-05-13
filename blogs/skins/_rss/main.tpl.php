<?php
/**
 * This template generates an RSS 0.92 feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @package evoskins
 * @subpackage rss
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
<rss version="0.92">
	<channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			request_title( ' - ', '', ' - ', 'xml' );
		?></title>
		<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
		<description><?php $Blog->disp( 'shortdesc' ,'xml' ) ?></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<?php
		while( $Item = & $MainList->get_item() )
		{
		?>
		<item>
			<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
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
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>