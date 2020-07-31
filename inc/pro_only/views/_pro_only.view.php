<?php
/**
 * This file display info for NOT PRO version.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$info_Widget = new Widget( 'block_item' );

$info_Widget->title = TB_('PRO version information');
$info_Widget->disp_template_replaced( 'block_start' );

echo TB_('This feature is available only in b2evolution PRO.');

$info_Widget->disp_template_raw( 'block_end' );
?>