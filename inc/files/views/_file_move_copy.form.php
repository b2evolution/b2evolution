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


global $source_Filelist, $new_names, $action;

if( !empty( $new_names ) )
{
	$Form = new Form( NULL, 'fm_movecopy_checkchanges' );

	$Form->global_icon( TB_('Cancel move/copy!'), 'close', regenerate_url('fm_selected,action,fm_sources_root') );

	if( $action == 'file_move' )
	{
		$form_title = TB_('Move');
	}
	elseif( $action == 'file_copy' )
	{
		$form_title = TB_('Copy');
	}
	else
	{
		$form_title = TB_('Move').'/'.TB_('Copy');
	}
	$Form->begin_form( 'fform', $form_title );

		$Form->add_crumb( 'file' );
		$Form->hidden_ctrl();
		$Form->hiddens_by_key( get_memorized() );
		$Form->hidden( 'confirmed', 1 );

		$source_Filelist->restart();
		while( $loop_src_File = & $source_Filelist->get_next() )
		{
			if( !isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{
				continue;
			}

			$Form->begin_fieldset( TB_('File').': '.$loop_src_File->get_rdfp_rel_path() );

			$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 64,
										TB_('New name'), $loop_src_File->dget('title'), 255 );

			$Form->end_fieldset();
		}

	$buttons = array();
	if( $action == 'move_copy' || $action == 'file_move' )
	{	// Display a button to move files/folders:
		$buttons[] = array( 'submit', 'actionArray[move]', TB_('Move'), 'SaveButton' );
	}
	if( $action == 'move_copy' || $action == 'file_copy' )
	{	// Display a button to copy files/folders:
		$buttons[] = array( 'submit', 'actionArray[copy]', TB_('Copy'), 'SaveButton' );
	}
	$Form->end_form( $buttons );

	echo '<p class="notes"><strong>'.TB_('You are in copy/move mode.')
					.'</strong> '.TB_('Please navigate to the desired target location.').'</p>';
}
?>