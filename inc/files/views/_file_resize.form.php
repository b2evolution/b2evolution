<?php
/**
 * This file implements the UI for image file resizing
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @global Filelist
 */
global $Settings, $selected_Filelist;


$Form = new Form( NULL );

$Form->global_icon( T_('Cancel resize!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Resize') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'action', 'resize' );
	$Form->hidden( 'confirmed', 1 );

	$Form->begin_fieldset( T_('Confirm resize') );

	echo sprintf( T_('%s images will be resized to fit %s. Are you sure?'), $selected_Filelist->count(),
			$Settings->get( 'fm_resize_width' ).'x'.$Settings->get( 'fm_resize_height' ) );

	$selected_Filelist->restart();
	echo '<ul>';
		while( $l_File = & $selected_Filelist->get_next() )
		{
			echo '<li>'.$l_File->get_prefixed_name().'</li>';
		}
	echo '</ul>';

	$Form->end_fieldset();

$Form->end_form( array(
		array( 'submit', 'submit', T_('Resize'), 'SaveButton' ) ) );

?>