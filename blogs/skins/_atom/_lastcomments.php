<?php
/**
 * This template generates an Atom feed for the requested blog's latest comments
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
 *
 * This file is not meant to be called directly.
 * It is meant to be called automagically by the main template (_main.php).
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 * @subpackage atom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$CommentList = & new CommentList( $blog, "'comment'", array('published'), '',	'',	'DESC',	'',	20 );

?>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<link rel="self" type="text/html" href="<?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) ?>" />
	<id><?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) /* TODO: may need a regenerate_url() */ ?></id>
	<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></updated>
	<?php while( $Comment = & $CommentList->get_next() )
	{ // Loop through comments: ?>
	<entry>
		<title type="text"><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permanent_url() ?>" />
		<author>
			<name><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<uri>', "</uri>\n", false ) ?>
		</author>
		<id><?php $Comment->permanent_url() ?></id>
    <published><?php $Comment->date( 'isoZ', true ); ?></published>
		<updated><?php $Comment->date( 'isoZ', true ); ?></updated>
		<content type="html"><![CDATA[<?php $Comment->content() ?>]]></content>
	</entry>
	<?php } // End of comment loop. ?>