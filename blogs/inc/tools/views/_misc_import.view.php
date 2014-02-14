<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$block_item_Widget = new Widget( 'block_item' );

// fp> TODO: pluginize MT! :P
$block_item_Widget->title = T_('Movable Type Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
?>
	<ol>
		<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
		<li><?php printf( T_('Follow the instructions in <a %s>Daniel\'s Movable Type Importer</a>.'), ' href="?ctrl=mtimport"' ) ?></li>
	</ol>
<?php
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('WordPress DB Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
printf( '<p>'.T_('You can import contents from your WordPress 2.3 database into your b2evolution database by using <a %s>Hari\'s WordPress Importer</a>.').'</p>', ' href="?ctrl=wpimport"' );
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('WordPress XML Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
printf( '<p>'.T_('You can import contents from your WordPress XML file into your b2evolution database by using <a %s>Wordpress XML Importer</a>.').'</p>', ' href="?ctrl=wpimportxml"' );
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('phpBB Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
printf( '<p>'.T_('You can import contents from your phpBB 2.x database into your b2evolution database by using <a %s>phpBB Importer</a>.').'</p>', ' href="?ctrl=phpbbimport"' );
$block_item_Widget->disp_template_raw( 'block_end' );

?>