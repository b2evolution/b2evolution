<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog's latest posts
 *
 * See {@link http://backend.userland.com/rss}
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
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header( 'Content-type: application/xml; charset='.$io_charset );

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';

?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			request_title( ' - ', '', ' - ', 'xml' );
		?></title>
		<?php
		switch( $disp )
		{
			case 'comments':
				// this includes the last comments if requested:
				require( dirname(__FILE__).'/_lastcomments.php' );
				break;

			default:
				?>
				<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
				<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
				<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
				<docs>http://backend.userland.com/rss</docs>
				<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
				<ttl>60</ttl>
				<?php while( $Item = $MainList->get_item() ) {	?>
				<item>
					<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
					<link><?php $Item->permanent_url( 'single' ) ?></link>
					<pubDate><?php $Item->issue_date( 'r', true ) ?></pubDate>
					<dc:creator><?php $Item->get_creator_User(); $Item->creator_User->preferred_name('xml') ?></dc:creator>
					<?php $Item->categories( false, '<category domain="main">', '</category>', '<category domain="alt">', '</category>', '<category domain="external">', '</category>', "\n", 'xml' ) ?>
					<guid isPermaLink="false"><?php $Item->ID() ?>@<?php echo $baseurl ?></guid>
					<description><?php
						$Item->url_link( '', ' ', '%s', array(), 'xml' );
						$Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'xml', $rss_excerpt_length );
					?></description>
					<content:encoded><![CDATA[<?php
						$Item->url_link( '<p>', '</p>' );
						$Item->content()
					?>]]></content:encoded>
					<comments><?php comments_link( '', 1, 1 ) ?></comments>
				</item>
				<?php }
		}
		?>
	</channel>
</rss>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>
