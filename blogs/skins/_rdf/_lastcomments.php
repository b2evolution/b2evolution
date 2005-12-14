<?php
/**
 * This template generates an RSS 1.0 (RDF) feed for the requested blog's latest comments
 *
 * See {@link http://web.resource.org/rss/1.0/}
 *
 * This file is not meant to be called directly.
 * It is meant to be called automagically by the main template (_main.php).
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evoskins
 * @subpackage rdf
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );

?>
<channel rdf:about="<?php $Blog->disp( 'blogurl', 'xmlattr' ) ?>">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		request_title( ' - ', '', ' - ', 'xml' );
	?></title>
	<link><?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?></link>
	<description></description>
	<dc:language><?php $Blog->disp( 'locale', 'xml' ) ?></dc:language>
	<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
	<items>
		<rdf:Seq>
		<?php while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments: ?>
			<rdf:li rdf:resource="<?php $Comment->permalink() ?>"/>
		<?php } // End of comment loop. ?>
		</rdf:Seq>
	</items>
</channel>
<?php
$CommentList->restart();
while( $Comment = & $CommentList->get_next() )
{ // Loop through comments: ?>
<item rdf:about="<?php $Comment->permalink() ?>">
	<title><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
	<link><?php $Comment->permalink() ?></link>
	<dc:date><?php $Comment->date( 'isoZ', true ); ?></dc:date>
	<dc:creator><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></dc:creator>
	<description><?php $Comment->content( 'xml' ) ?></description>
	<content:encoded><![CDATA[<?php $Comment->content() ?>]]></content:encoded>
</item>
<?php } // End of comment loop. ?>