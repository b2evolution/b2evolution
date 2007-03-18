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
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

$CommentList = & new CommentList( $Blog, "'comment'", array('published'), '',	'',	'DESC',	'',	$Blog->get_setting('posts_per_feed') );

if( $debug)
{
	skin_content_header( 'application/xml' );	// Sets charset!
}
else
{
	skin_content_header( 'application/atom+xml' );	// Sets charset!
}

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<feed xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://www.w3.org/2005/Atom">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		request_title( ' - ', '', ' - ', 'xml' );
	?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<link rel="self" type="application/atom+xml" href="<?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) ?>" />
	<id><?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) /* TODO: may need a regenerate_url() */ ?></id>
	<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></updated>
	<?php while( $Comment = & $CommentList->get_next() )
	{ /* Loop through comments: */ ?>
	<entry>
		<title type="text"><?php
			echo format_to_output( T_('In response to:'), 'xml' ).' ';
			$Comment->get_Item();
			$Comment->Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permanent_url() ?>" />
		<author>
			<name><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<uri>', "</uri>\n", false ) ?>
		</author>
		<id><?php $Comment->permanent_url() ?></id>
		<published><?php $Comment->date( 'isoZ', true ); ?></published>
		<updated><?php $Comment->date( 'isoZ', true ); ?></updated>
		<content type="html"><![CDATA[<?php echo make_rel_links_abs( $Comment->get_content() ); ?>]]></content>
	</entry>
	<?php
	} // End of comment loop.
	?>
</feed>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>