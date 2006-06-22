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
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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

header( 'Content-type: application/xml; charset='.$io_charset );
echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rss version="0.92">
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
			  <description><?php $Blog->disp( 'shortdesc' ,'xml' ) ?></description>
			  <language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
			  <docs>http://backend.userland.com/rss092</docs>
			  <?php while( $Item = $MainList->get_item() ) { ?>
			  <item>
			    <title><?php $Item->title( '', '', false, 'xml' ) ?></title>
			    <description><?php
			      $Item->url_link( '', ' ', '%s', array(), 'entityencoded' );
			      $Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'entityencoded' );
			    ?></description>
			    <link><?php $Item->permanent_url( 'single' ) ?></link>
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