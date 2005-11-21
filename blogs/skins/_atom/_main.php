<?php
/**
 * This template generates an Atom feed for the requested blog's latest posts
 *
 * See {@link http://www.mnot.net/drafts/draft-nottingham-atom-format-02.html}
 *
 * This file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
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
 * @subpackage atom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header("Content-type: application/atom+xml");
// header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<feed version="0.3" xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://purl.org/atom/ns#">
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
			<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'blogurl', 'xml' ) ?>" />
			<tagline><?php $Blog->disp( 'shortdesc', 'xml' ) ?></tagline>
			<generator url="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
			<modified><?php $MainList->mod_date( 'isoZ', true ) ?></modified>
			<?php while( $Item = $MainList->get_item() ) {	?>
			<entry>
				<title type="text/plain" mode="xml"><?php $Item->title( '', '', false, 'xml' ) ?></title>
				<link rel="alternate" type="text/html" href="<?php $Item->permalink( 'single' ) ?>" />
				<author>
					<name><?php $Item->Author->preferred_name( 'xml' ) ?></name>
					<?php $Item->Author->url( '<url>', "</url>\n", 'xml' ) ?>
				</author>
				<id><?php $Item->permalink( 'single' ) ?></id>
				<issued><?php $Item->issue_date( 'isoZ', true ) ?></issued>
				<modified><?php $Item->mod_date( 'isoZ', true ) ?></modified>
				<content type="text/html" mode="escaped"><![CDATA[<?php
					$Item->url_link( '<p>', '</p>' );
					$Item->content()
				?>]]></content>
			</entry>
			<?php }
	}
	?>
</feed>
<?php $Hit->log(); // log the hit on this page ?>