<?php
/**
 * This template generates an RSS 1.0 (RDF) feed for the requested blog's latest posts
 *
 * See {@link http://web.resource.org/rss/1.0/}
 *
 * This file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 * @subpackage rdf
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header( 'Content-type: application/xml; charset='.$io_charset );
echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"					xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<?php
switch( $disp )
{
	case 'comments':
		// this includes the last comments if requested:
		require( dirname(__FILE__).'/_lastcomments.php' );
		break;

	default:
		?>
		<channel rdf:about="<?php $Blog->disp( 'blogurl', 'xmlattr' ) ?>">
			<title><?php
				$Blog->disp( 'name', 'xml' );
				request_title( ' - ', '', ' - ', 'xml' );
			?></title>
			<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
			<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
			<dc:language><?php $Blog->disp( 'locale', 'xml' ) ?></dc:language>
			<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
			<sy:updatePeriod>hourly</sy:updatePeriod>
			<sy:updateFrequency>1</sy:updateFrequency>
			<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
			<items>
				<rdf:Seq>
				<?php while( $Item = $MainList->get_item() ) { ?>
					<rdf:li rdf:resource="<?php $Item->permanent_url( 'single' ) ?>"/>
				<?php } ?>
				</rdf:Seq>
			</items>
		</channel>
		<?php
		$MainList->restart();
		while( $Item = $MainList->get_item() )
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
			<description><?php
				$Item->url_link( '', ' ', '%s', array(), 'xml' );
				$Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'xml', $rss_excerpt_length );
			?></description>
			<content:encoded><![CDATA[<?php
				$Item->url_link( '<p>', '</p>' );
				$Item->content()
			?>]]></content:encoded>
		</item>

		<?php
		}
}
?>
</rdf:RDF>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>