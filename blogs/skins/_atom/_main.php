<?php
/**
 * This template generates an Atom feed for the requested blog's latest posts
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
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
 * @subpackage atom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
			<link rel="self" type="text/html" href="<?php $Blog->disp( 'atom_url', 'xmlattr' ) ?>" />
			<id><?php $Blog->disp( 'atom_url', 'xmlattr' ); /* TODO: may need a regenerate_url() */ ?></id>
			<subtitle><?php $Blog->disp( 'shortdesc', 'xml' ) ?></subtitle>
			<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
			<updated><?php $MainList->mod_date( 'isoZ', true ) ?></updated>
			<?php
			while( $Item = & $MainList->get_item() )
			{
				// Load Item's creator User:
				$Item->get_creator_User();
				?>

			<entry>
				<title type="text"><?php $Item->title( '', '', false, 'xml' ) ?></title>
				<link rel="alternate" type="text/html" href="<?php $Item->permanent_url( 'single' ) ?>" />
				<author>
					<name><?php $Item->creator_User->preferred_name( 'xml' ) ?></name>
					<?php $Item->creator_User->url( '<uri>', "</uri>\n", 'xml' ) ?>
				</author>
				<id><?php $Item->permanent_url( 'single' ) ?></id>
				<published><?php $Item->issue_date( 'isoZ', true ) ?></published>
				<updated><?php $Item->mod_date( 'isoZ', true ) ?></updated>
				<content type="html"><![CDATA[<?php
					$Item->url_link( '<p>', '</p>' );
					$Item->content()
				?>]]></content>
			</entry>

			<?php
			}
	}
	?>
</feed>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>