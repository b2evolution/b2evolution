<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$block_item_Widget = new Widget( 'block_item' );

// fp> TODO: pluginize MT! :P
$block_item_Widget->title = T_('Movable Type Import').get_manual_link( 'import-tab' );
$block_item_Widget->disp_template_replaced( 'block_start' );
?>
	<ol>
		<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
		<li><?php printf( T_('Follow the instructions in <a %s>Daniel\'s Movable Type Importer</a>.'), ' href="?ctrl=mtimport"' ) ?></li>
	</ol>
<?php
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('WordPress Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
echo '<ul>';
printf( '<li>'.T_('Use the <a %s>WordPress XML Importer</a> to import contents previously exported as a wordpress XML file.').'</li>', ' href="?ctrl=wpimportxml"' );
echo '</ul>';
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('phpBB Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
printf( '<p>'.T_('You can import contents from your phpBB 2.x or 3.x database into your b2evolution database by using <a %s>phpBB Importer</a> or <a %s>phpBB 3 Importer</a> .').'</p>', ' href="?ctrl=phpbbimport"', ' href="?ctrl=phpbbimport&ver=3"' );
$block_item_Widget->disp_template_raw( 'block_end' );

?>