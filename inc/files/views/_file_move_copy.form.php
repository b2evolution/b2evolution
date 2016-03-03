<?php
/**
 * This file implements the UI for file move/copy
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @global Filelist
 */
global $source_Filelist;

/**
 * @global string
 */
global $new_names;


$Form = new Form( NULL, 'fm_movecopy_checkchanges' );

$Form->global_icon( T_('Cancel move/copy!'), 'close', regenerate_url('fm_selected,action,fm_sources_root') );

$Form->begin_form( 'fform', T_('Move/Copy') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'confirmed', 1 );

	$source_Filelist->restart();
	while( $loop_src_File = & $source_Filelist->get_next() )
	{
		$Form->begin_fieldset( T_('File').': '.$loop_src_File->get_rdfp_rel_path() );

		$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 64,
									T_('New name'), $loop_src_File->dget('title'), 255 );

		$Form->end_fieldset();
	}


$Form->end_form( array(
		array( 'submit', 'actionArray[move]', T_('Move'), 'SaveButton' ),
		array( 'submit', 'actionArray[copy]', T_('Copy'), 'SaveButton' )
	) );

echo '<p class="notes"><strong>'.T_('You are in copy/move mode.')
				.'</strong> '.T_('Please navigate to the desired target location.').'</p>';

?>