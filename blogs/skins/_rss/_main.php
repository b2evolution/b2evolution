<?php
/**
 * This template generates an RSS 0.92 feed for the requested blog's latest posts
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * This file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 * @subpackage rss
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

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

				// fp> this is  another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output!
				echo make_rel_links_abs( $content );
			?></description>
			<link><?php $Item->permanent_url( 'single' ) ?></link>
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