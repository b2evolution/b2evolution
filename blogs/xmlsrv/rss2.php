<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog
 *
 * See {@link http://backend.userland.com/rss}
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package xmlsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */

$skin = '';										// We don't want this do be displayed in a skin !
$show_statuses = array();			// Restrict to published posts
$timestamp_min = '';					// Show past
$timestamp_max = 'now';				// Hide future
/**
 * Initialize everything:
 */
require dirname(__FILE__).'/../evocore/_blog_main.inc.php' ;
header( 'Content-type: application/xml' );

echo '<?xml version="1.0"?'.'>';

?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			single_cat_title( ' - ', 'xml' );
			single_month_title( ' - ', 'xml' );
			single_post_title( ' - ', 'xml' );
		?></title>
		<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
		<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://backend.userland.com/rss</docs>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
		<ttl>60</ttl>
		<?php while( $Item = $MainList->get_item() ) {	?>
		<item>
			<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
			<link><?php $Item->permalink( 'single' ) ?></link>
			<pubDate><?php $Item->issue_date( 'r', true ) ?></pubDate>
			<?php /* Disabled because of spambots: <author><php $Item->Author->email( 'xml' ) ></author> */ ?>
			<?php $Item->categories( false, '<category domain="main">', '</category>', '<category domain="alt">', '</category>', '<category domain="external">', '</category>', "\n", 'xml' ) ?>
			<guid isPermaLink="false"><?php $Item->ID() ?>@<?php echo $baseurl ?></guid>
			<description><?php
				$Item->url_link( '', ' ', 'xml' );
				$Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'xml', $rss_excerpt_length );
			?></description>
			<content:encoded><![CDATA[<?php
				$Item->url_link( '<p>', '</p>' );
				$Item->content()
			?>]]></content:encoded>
			<comments><?php comments_link( '', 1, 1 ) ?></comments>
		</item>
		<?php } ?>
	</channel>
</rss>
<?php $Hit->log(); // log the hit on this page ?>