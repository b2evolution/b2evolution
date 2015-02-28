<?php
/**
 * This file implements the UI for file permissions
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @global Filelist
 */
global $selected_Filelist;

global $more_than_one_selected_file, $perms_read_readonly, $field_options_read_readonly;


$Form = new Form( NULL, 'fm_perms_checkchanges' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Change permissions') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'action', 'edit_perms' );

	if( $more_than_one_selected_file )
	{ // more than one file, provide default
		$Form->begin_fieldset( T_('Default') );

		if( $perms_read_readonly )
		{
			$Form->radio_input( 'edit_perms_default', $edit_perms_default, $field_options_read_readonly, T_('Default permissions') );
		}
		else
		{
			$Form->text_input( 'edit_perms_default', $edit_perms_default, 3, T_('Default permissions') );
		}

		$Form->info_field( '', '<a id="checkallspan_edit_perms_set" href="#" onclick="toggleCheckboxes(\'fm_perms_checkchanges\', \'use_default_perms[]\', \'edit_perms_set\'); return false;">'.T_('check all').'</a>' );

		$Form->end_fieldset();
	}

	if( $more_than_one_selected_file )
	{ // generate checkbox input to use with every file
		$Form->output = false;
		$Form->switch_layout('none');
		$use_default_perms_checkbox = $Form->checkbox_input(
			'use_default_perms[]', $checked = 0, T_('Use default value'),
			array( 'value' => '%file_ID%', 'id' => 'use_default_perms_%file_ID%' ) );
		$Form->switch_layout(NULL);
		$Form->output = true;
	}

	$selected_Filelist->restart();

	$Form->begin_fieldset();
	while( $l_File = & $selected_Filelist->get_next() )
	{
		if( $perms_read_readonly )
		{ // readonly/write only (WINDOWS): display radio inputs to change readonly/write
			$field_options = $field_options_read_readonly;
			$field_params = array();
			if( !empty($use_default_perms_checkbox) )
			{
				$field_params['field_suffix'] = str_replace( '%file_ID%', $l_File->get_md5_ID(), $use_default_perms_checkbox );
			}

			$l_perms = $l_File->get_perms( 'octal' );
			if( $l_perms == 555 )
			{
				$field_options[0]['checked'] = 'checked';
			}
			else
			{
				$field_options[1]['checked'] = 'checked';
			}
			$Form->radio_input( 'perms['.$l_File->get_md5_ID().']', false, $field_options, $l_File->get_rdfp_rel_path(), $field_params );
		}
		else
		{ // display a text input with UNIX perms
			$field_params = array();
			if( !empty($use_default_perms_checkbox) )
			{
				$field_params['field_suffix'] = str_replace( '%file_ID%', $l_File->get_md5_ID(), $use_default_perms_checkbox );
			}

			$Form->text_input( 'perms['.$l_File->get_md5_ID().']', $l_File->get_perms( 'octal' ), 3, $l_File->get_rdfp_rel_path(), $field_params );
		}
	}
	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Set new permissions'), 'ActionButton' ) ) );

?>