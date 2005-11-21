<?php
/**
 * This template generates an Atom feed for the requested blog's latest comments
 *
 * See {@link http://www.mnot.net/drafts/draft-nottingham-atom-format-02.html}
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
 * @subpackage atom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );

?>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<generator url="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<modified><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></modified>
	<?php while( $Comment = $CommentList->get_next() )
	{ // Loop through comments: ?>
	<entry>
		<title type="text/plain" mode="xml"><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permalink() ?>" />
		<author>
			<name><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<url>', "</url>\n", false ) ?>
		</author>
		<id><?php $Comment->permalink() ?></id>
		<issued><?php $Comment->date( 'isoZ', true ); ?></issued>
		<modified><?php $Comment->date( 'isoZ', true ); ?></modified>
		<content type="text/html" mode="escaped"><![CDATA[<?php $Comment->content() ?>]]></content>
	</entry>
	<?php } // End of comment loop. ?>