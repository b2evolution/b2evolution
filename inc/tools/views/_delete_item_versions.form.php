<?php
/**
 * This file implements the UI for clearing item versions table
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$confirm_title = T_('Clear all item versions?');

$block_item_Widget = new Widget( 'block_item' );

$block_item_Widget->title = $confirm_title;
echo str_replace( 'panel-default', 'panel-danger', $block_item_Widget->replace_vars( $block_item_Widget->params[ 'block_start' ] ) );

echo '<p class="warning text-danger">'.T_('You are about to empty the item versions table.').'</p>';
echo '<p class="warning text-danger">'.T_('THIS CANNOT BE UNDONE!').'</p>';
$Form = new Form( '' );

$Form->begin_form( 'inline' );

$Form->add_crumb( 'tools' );
$Form->hidden( 'action', 'delete_item_versions' );
$Form->hidden( 'confirmed', 1 );
$Form->button( array( 'submit', '', T_('I am sure!'), 'DeleteButton btn-danger' ) );
$Form->end_form();

$Form = new Form( get_dispctrl_url( 'tools' ), 'form_cancel', 'get', '' );
$Form->begin_form( 'inline' );
$Form->button( array( 'submit', '', T_('Cancel'), 'CancelButton' ) );
$Form->end_form();

$block_item_Widget->disp_template_replaced( 'block_end' );
?>